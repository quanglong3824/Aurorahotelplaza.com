<?php
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../helpers/language.php';

session_start();
initLanguage();

$controller = new ProfileController();
$data = $controller->index();
extract($data);

function getStatusBadge($status)
{
    $map = [
        'pending' => [__('booking_status.pending'), 'bg-yellow-100 text-yellow-800'],
        'confirmed' => [__('booking_status.confirmed'), 'bg-blue-100 text-blue-800'],
        'checked_in' => [__('booking_status.checked_in'), 'bg-green-100 text-green-800'],
        'checked_out' => [__('booking_status.checked_out'), 'bg-gray-100 text-gray-800'],
        'cancelled' => [__('booking_status.cancelled'), 'bg-red-100 text-red-800'],
    ];
    $info = $map[$status] ?? [$status, 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $info[1] . '">' . $info[0] . '</span>';
}

function getContactStatusBadge($status)
{
    $map = [
        'new' => ['Mới', 'bg-blue-100 text-blue-800'],
        'in_progress' => ['Đang xử lý', 'bg-yellow-100 text-yellow-800'],
        'resolved' => ['Đã giải quyết', 'bg-green-100 text-green-800'],
        'closed' => ['Đã đóng', 'bg-gray-100 text-gray-800'],
    ];
    $info = $map[$status] ?? [$status, 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $info[1] . '">' . $info[0] . '</span>';
}

// Header and footer are usually included in the view or entry point
// For consistency with other refactors, I'll include them in the entry point around the view

?>
<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">
<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('profile_page.title'); ?></title>
    <link href="../assets/css/tailwind-output.css" rel="stylesheet" />
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    <link rel="stylesheet" href="../assets/css/pages-glass.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>
<body class="glass-page font-body text-white">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include '../includes/header.php'; ?>
        
        <?php include '../views/profile/index.php'; ?>

        <?php include '../includes/footer.php'; ?>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
