<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Core/Repositories/BookingRepository.php';
require_once __DIR__ . '/../helpers/language.php';
require_once __DIR__ . '/../helpers/logger.php';
require_once __DIR__ . '/../helpers/refund-policy.php';

use Aurora\Core\Repositories\UserRepository;
use Aurora\Core\Repositories\BookingRepository;

class ProfileController {
    private UserRepository $userRepository;
    private BookingRepository $bookingRepository;

    public function __construct() {
        $db = getDB();
        $this->userRepository = new UserRepository($db);
        $this->bookingRepository = new BookingRepository($db);
    }

    private function checkAuth() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public function index() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        $active_tab = $_GET['tab'] ?? 'info';

        return [
            'user' => $this->userRepository->getProfileWithLoyalty($userId),
            'stats' => $this->bookingRepository->getUserStats($userId),
            'bookings' => $this->bookingRepository->getRecentBookings($userId, 5),
            'points_history' => $this->userRepository->getPointsHistory($userId, 5),
            'contact_history' => $this->userRepository->getContactHistory($userId, 5),
            'active_tab' => $active_tab
        ];
    }

    public function edit() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        $success = '';
        $error = '';

        $user = $this->userRepository->findById($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'date_of_birth' => $_POST['date_of_birth'] ?? null,
                'gender' => $_POST['gender'] ?? null
            ];

            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($data['full_name'])) {
                $error = 'Họ và tên không được để trống';
            } elseif (!empty($data['phone']) && !preg_match('/^(0|\+84)[0-9]{9,10}$/', str_replace(' ', '', $data['phone']))) {
                $error = 'Số điện thoại không hợp lệ';
            } elseif (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = 'Vui lòng nhập mật khẩu hiện tại để thay đổi mật khẩu';
                } elseif (!password_verify($current_password, $user['password_hash'])) {
                    $error = 'Mật khẩu hiện tại không đúng';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Xác nhận mật khẩu không khớp';
                }
            }

            if (!$error) {
                if ($this->userRepository->updateProfile($userId, $data)) {
                    if (!empty($new_password)) {
                        $this->userRepository->updatePassword($userId, password_hash($new_password, PASSWORD_DEFAULT));
                    }
                    
                    $_SESSION['user_name'] = $data['full_name'];
                    $success = 'Cập nhật thông tin thành công!';
                    
                    // Log activity
                    $logger = getLogger();
                    $logger->logActivity($userId, 'profile_update', 'user', $userId, 'User updated profile information');
                    
                    $user = $this->userRepository->findById($userId);
                } else {
                    $error = 'Có lỗi xảy ra khi cập nhật thông tin.';
                }
            }
        }

        return [
            'user' => $user,
            'success' => $success,
            'error' => $error
        ];
    }

    public function bookings() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];

        $filters = [
            'status' => $_GET['status'] ?? '',
            'payment_status' => $_GET['payment_status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => trim($_GET['search'] ?? '')
        ];
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = 10;

        $result = $this->bookingRepository->getUserBookings($userId, $filters, $page, $per_page);
        $stats = $this->bookingRepository->getUserStats($userId);

        return [
            'bookings' => $result['bookings'],
            'total_bookings' => $result['total'],
            'total_pages' => $result['total_pages'],
            'page' => $page,
            'per_page' => $per_page,
            'filters' => $filters,
            'stats' => $stats
        ];
    }

    public function bookingDetail() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        $bookingCode = $_GET['code'] ?? '';
        $bookingId = (int)($_GET['id'] ?? 0);
        $error = '';
        $booking = null;
        $booking_history = [];
        $can_cancel = false;
        $refund_info = null;

        if (!$bookingCode && !$bookingId) {
            $error = 'Mã đặt phòng không hợp lệ';
        } else {
            if ($bookingId) {
                $booking = $this->bookingRepository->findWithDetails($bookingId);
            } else {
                $booking = $this->bookingRepository->findByCode($bookingCode);
                // Need full details for view
                if ($booking) {
                    $booking = $this->bookingRepository->findWithDetails($booking['booking_id']);
                }
            }

            if (!$booking) {
                $error = 'Không tìm thấy thông tin đặt phòng';
            } elseif ($booking['user_id'] != $userId) {
                $error = 'Bạn không có quyền truy cập thông tin này';
                $booking = null;
            } else {
                $booking_history = $this->bookingRepository->getHistory($booking['booking_id']);
                // Note: using custom logic for can_cancel if needed, or stick to repo
                $check_in = new DateTime($booking['check_in_date']);
                $now = new DateTime();
                $hours_until_checkin = ($check_in->getTimestamp() - $now->getTimestamp()) / 3600;
                $can_cancel = in_array($booking['status'], ['pending', 'confirmed']) && $hours_until_checkin >= 24;
                
                if ($can_cancel) {
                    $refund_info = calculateRefundAmount($booking);
                }
            }
        }

        return [
            'booking' => $booking,
            'booking_history' => $booking_history,
            'can_cancel' => $can_cancel,
            'refund_info' => $refund_info,
            'error' => $error,
            'booking_code' => $bookingCode
        ];
    }

    public function loyalty() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];

        $this->userRepository->ensureLoyaltyRecord($userId);
        $loyalty = $this->userRepository->getLoyaltyInfo($userId);
        $transactions = $this->userRepository->getPointsHistory($userId, 10);
        $all_tiers = $this->userRepository->getMembershipTiers();
        $booking_stats = $this->bookingRepository->getUserStats($userId);

        return [
            'loyalty' => $loyalty,
            'transactions' => $transactions,
            'all_tiers' => $all_tiers,
            'booking_stats' => $booking_stats
        ];
    }

    public function security() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->findById($userId);

        return [
            'user' => $user
        ];
    }

    public function notifications() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        // Assuming a notifications table exists or just returning empty for now
        // This is a placeholder since the file was missing
        return [
            'notifications' => []
        ];
    }

    // API Handlers
    public function updateProfileApi() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        if ($this->userRepository->updateProfile($userId, $data)) {
            $_SESSION['user_name'] = $data['full_name'] ?? $_SESSION['user_name'];
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
    }

    public function changePasswordApi() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $user = $this->userRepository->findById($userId);

        if (!password_verify($data['current_password'], $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Current password incorrect']);
            return;
        }

        if ($this->userRepository->updatePassword($userId, password_hash($data['new_password'], PASSWORD_DEFAULT))) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to change password']);
        }
    }

    public function deleteAccountApi() {
        $this->checkAuth();
        $userId = $_SESSION['user_id'];
        
        if ($this->userRepository->deleteAccount($userId)) {
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
        }
    }
}
