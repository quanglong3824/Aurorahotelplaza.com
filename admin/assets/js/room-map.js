/**
 * Room Map JavaScript - Admin
 * Aurora Hotel Plaza
 */

// Change date filter
function changeDate() {
    const date = document.getElementById('checkDate').value;
    const urlParams = new URLSearchParams(window.location.search);
    const floor = urlParams.get('floor') || 'all';
    window.location.href = `room-map.php?date=${date}&floor=${floor}`;
}

// View room detail
function viewRoom(roomId) {
    document.getElementById('roomModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    fetch(`api/get-room-detail.php?room_id=${roomId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayRoomDetail(data.room, data.current_booking, data.booking_history);
            } else {
                document.getElementById('roomModalContent').innerHTML =
                    '<div class="text-center py-8 text-red-600">Không thể tải thông tin phòng</div>';
            }
        })
        .catch(err => {
            document.getElementById('roomModalContent').innerHTML =
                '<div class="text-center py-8 text-red-600">Có lỗi xảy ra</div>';
        });
}

// Display room detail in modal
function displayRoomDetail(room, currentBooking, history) {
    const statusColors = {
        'available': 'bg-green-100 text-green-800',
        'occupied': 'bg-red-100 text-red-800',
        'maintenance': 'bg-orange-100 text-orange-800',
        'cleaning': 'bg-blue-100 text-blue-800'
    };

    const statusLabels = {
        'available': 'Trống',
        'occupied': 'Đang ở',
        'maintenance': 'Bảo trì',
        'cleaning': 'Dọn dẹp'
    };

    let html = `
        <div class="space-y-6">
            <!-- Room Info -->
            <div class="bg-gradient-to-br from-[#d4af37]/10 to-[#b8941f]/10 rounded-xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="text-3xl font-bold mb-2" style="color: #d4af37;">Phòng ${room.room_number}</h4>
                        <p class="text-lg font-semibold">${room.type_name}</p>
                        <p class="text-sm text-gray-600">Tầng ${room.floor} - ${room.category === 'room' ? 'Phòng' : 'Căn hộ'}</p>
                    </div>
                    <span class="badge ${statusColors[room.status]} text-sm px-4 py-2">
                        ${statusLabels[room.status]}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">Giá cơ bản</div>
                        <div class="text-xl font-bold" style="color: #d4af37;">${parseInt(room.base_price).toLocaleString()}đ</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">Sức chứa</div>
                        <div class="text-xl font-bold">${room.max_occupancy} người</div>
                    </div>
                </div>
            </div>
            
            <!-- Current Booking -->
            ${currentBooking ? `
                <div class="card">
                    <div class="card-header">
                        <h5 class="font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined">person</span>
                            Khách đang ở
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="font-semibold text-lg">${currentBooking.guest_name}</p>
                                <p class="text-sm text-gray-600">${currentBooking.email || ''}</p>
                                <p class="text-sm text-gray-600">${currentBooking.phone || ''}</p>
                            </div>
                            <a href="booking-detail.php?id=${currentBooking.booking_id}" class="btn btn-primary btn-sm">
                                Xem chi tiết
                            </a>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Check-in:</span>
                                <p class="font-semibold">${new Date(currentBooking.check_in_date).toLocaleDateString('vi-VN')}</p>
                            </div>
                            <div>
                                <span class="text-gray-600">Check-out:</span>
                                <p class="font-semibold">${new Date(currentBooking.check_out_date).toLocaleDateString('vi-VN')}</p>
                            </div>
                            <div>
                                <span class="text-gray-600">Tổng tiền:</span>
                                <p class="font-semibold" style="color: #d4af37;">${parseInt(currentBooking.total_amount).toLocaleString()}đ</p>
                            </div>
                        </div>
                    </div>
                </div>
            ` : '<div class="text-center py-4 text-gray-500">Phòng hiện đang trống</div>'}
            
            <!-- Booking History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined">history</span>
                        Lịch sử đặt phòng
                    </h5>
                </div>
                <div class="card-body">
                    ${history && history.length > 0 ? `
                        <div class="space-y-3">
                            ${history.map(booking => `
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                                    <div class="flex-1">
                                        <p class="font-semibold">${booking.guest_name}</p>
                                        <p class="text-sm text-gray-600">
                                            ${new Date(booking.check_in_date).toLocaleDateString('vi-VN')} - 
                                            ${new Date(booking.check_out_date).toLocaleDateString('vi-VN')}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold" style="color: #d4af37;">${parseInt(booking.total_amount).toLocaleString()}đ</p>
                                        <span class="badge badge-${booking.status === 'completed' ? 'success' : 'secondary'} text-xs">
                                            ${booking.status === 'completed' ? 'Hoàn thành' : booking.status}
                                        </span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-center text-gray-500 py-4">Chưa có lịch sử đặt phòng</p>'}
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined">tune</span>
                        Thao tác nhanh
                    </h5>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="changeRoomType(${room.room_id}, '${room.room_number}')" class="btn btn-secondary w-full">
                            <span class="material-symbols-outlined text-sm">swap_horiz</span>
                            Đổi loại phòng
                        </button>
                        <button onclick="changeRoomStatus(${room.room_id}, '${room.status}', '${room.room_number}')" class="btn btn-secondary w-full">
                            <span class="material-symbols-outlined text-sm">toggle_on</span>
                            Đổi trạng thái
                        </button>
                        <a href="room-form.php?id=${room.room_id}" class="btn btn-secondary w-full">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Sửa chi tiết
                        </a>
                        ${room.status === 'available' ? `
                            <a href="bookings.php?room_id=${room.room_id}" class="btn btn-primary w-full">
                                <span class="material-symbols-outlined text-sm">add</span>
                                Tạo booking
                            </a>
                        ` : `
                            <button class="btn btn-secondary w-full" disabled>
                                <span class="material-symbols-outlined text-sm">block</span>
                                Đang có khách
                            </button>
                        `}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('roomModalContent').innerHTML = html;
}

function closeRoomModal() {
    document.getElementById('roomModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Change room type - Open modal
async function changeRoomType(roomId, roomNumber) {
    document.getElementById('typeRoomId').value = roomId;
    document.getElementById('typeRoomNumber').textContent = roomNumber || roomId;
    document.getElementById('roomTypeModal').classList.remove('hidden');

    // Load room types list
    try {
        const response = await fetch('api/get-room-types.php');
        const data = await response.json();

        if (!data.success) {
            document.getElementById('roomTypeList').innerHTML = '<p class="text-center text-red-500 py-4">Không thể tải danh sách loại phòng</p>';
            return;
        }

        const roomTypes = data.room_types;
        let html = '';

        roomTypes.forEach(type => {
            const isRoom = type.category === 'room';
            html += `
                <button onclick="selectRoomType(${type.room_type_id})" 
                        class="w-full flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-[#d4af37] bg-gray-50 dark:bg-slate-700/50 transition-all text-left">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br ${isRoom ? 'from-blue-500 to-blue-600' : 'from-purple-500 to-purple-600'} flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-white">${isRoom ? 'bed' : 'apartment'}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white">${type.type_name}</div>
                        <div class="text-sm text-gray-500">${isRoom ? 'Phòng' : 'Căn hộ'} • ${type.max_occupancy || 2} người</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="font-bold text-[#d4af37]">${parseInt(type.base_price).toLocaleString()}đ</div>
                        <div class="text-xs text-gray-500">/đêm</div>
                    </div>
                </button>
            `;
        });

        document.getElementById('roomTypeList').innerHTML = html;
    } catch (error) {
        document.getElementById('roomTypeList').innerHTML = '<p class="text-center text-red-500 py-4">Có lỗi xảy ra</p>';
    }
}

// Select room type
async function selectRoomType(typeId) {
    const roomId = document.getElementById('typeRoomId').value;

    try {
        const response = await fetch('api/update-room-type.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `room_id=${roomId}&room_type_id=${typeId}`
        });

        const data = await response.json();

        if (data.success) {
            closeRoomTypeModal();
            showToast('Đã đổi loại phòng thành công!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        showToast('Có lỗi xảy ra: ' + error.message, 'error');
    }
}

function closeRoomTypeModal() {
    document.getElementById('roomTypeModal').classList.add('hidden');
}

// Change room status - Open modal
function changeRoomStatus(roomId, currentStatus, roomNumber) {
    document.getElementById('statusRoomId').value = roomId;
    document.getElementById('statusRoomNumber').textContent = roomNumber || roomId;
    document.getElementById('statusModal').classList.remove('hidden');
}

// Select status
async function selectStatus(status) {
    const roomId = document.getElementById('statusRoomId').value;

    try {
        const response = await fetch('api/update-room-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `room_id=${roomId}&status=${status}`
        });

        const data = await response.json();

        if (data.success) {
            closeStatusModal();
            showToast('Đã đổi trạng thái thành công!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        showToast('Có lỗi xảy ra: ' + error.message, 'error');
    }
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

// Floor Maintenance Functions
function toggleFloorMaintenance(floor, isCurrentlyMaintenance) {
    document.getElementById('maintenanceFloor').value = floor;
    document.getElementById('maintenanceFloorNumber').textContent = floor;
    document.getElementById('maintenanceCurrentStatus').value = isCurrentlyMaintenance ? '1' : '0';

    // Reset form
    document.getElementById('maintenanceNote').value = '';
    document.getElementById('maintenanceStartDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('maintenanceEndDate').value = '';

    // Toggle buttons based on current status
    if (isCurrentlyMaintenance) {
        document.getElementById('btnSaveMaintenance').classList.add('hidden');
        document.getElementById('btnDisableMaintenance').classList.remove('hidden');
    } else {
        document.getElementById('btnSaveMaintenance').classList.remove('hidden');
        document.getElementById('btnDisableMaintenance').classList.add('hidden');
    }

    document.getElementById('floorMaintenanceModal').classList.remove('hidden');
}

async function saveFloorMaintenance() {
    const floor = document.getElementById('maintenanceFloor').value;
    const note = document.getElementById('maintenanceNote').value;
    const startDate = document.getElementById('maintenanceStartDate').value;
    const endDate = document.getElementById('maintenanceEndDate').value;

    try {
        const response = await fetch('api/floor-maintenance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `floor=${floor}&is_maintenance=1&maintenance_note=${encodeURIComponent(note)}&start_date=${startDate}&end_date=${endDate}`
        });

        const data = await response.json();

        if (data.success) {
            closeFloorMaintenanceModal();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        showToast('Có lỗi xảy ra: ' + error.message, 'error');
    }
}

async function disableFloorMaintenance() {
    const floor = document.getElementById('maintenanceFloor').value;

    if (!confirm(`Bạn có chắc muốn tắt bảo trì tầng ${floor}?`)) return;

    try {
        const response = await fetch('api/floor-maintenance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `floor=${floor}&is_maintenance=0`
        });

        const data = await response.json();

        if (data.success) {
            closeFloorMaintenanceModal();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        showToast('Có lỗi xảy ra: ' + error.message, 'error');
    }
}

function closeFloorMaintenanceModal() {
    document.getElementById('floorMaintenanceModal').classList.add('hidden');
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-[100] flex items-center gap-2 animate-fade-in`;
    toast.innerHTML = `
        <span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}</span>
        ${message}
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Quick Jump to Room by Number
async function quickJumpToRoom() {
    const input = document.getElementById('quickJumpInput');
    const roomNumber = input.value.trim();

    if (!roomNumber) {
        input.focus();
        return;
    }

    try {
        const response = await fetch(`api/get-room-by-number.php?room_number=${roomNumber}`);
        const data = await response.json();

        if (data.success && data.room) {
            viewRoom(data.room.room_id);
            highlightRoom(roomNumber);
            input.value = '';
        } else {
            alert(`Không tìm thấy phòng số ${roomNumber}`);
            input.select();
        }
    } catch (error) {
        alert('Có lỗi xảy ra: ' + error.message);
    }
}

// Highlight room on map
function highlightRoom(roomNumber) {
    document.querySelectorAll('.room-card').forEach(card => {
        card.style.transform = '';
        card.style.boxShadow = '';
    });

    const roomCards = document.querySelectorAll('.room-card');
    roomCards.forEach(card => {
        const cardNumber = card.querySelector('.room-number')?.textContent;
        if (cardNumber === roomNumber) {
            card.style.transform = 'scale(1.1)';
            card.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.8)';
            card.style.zIndex = '10';

            card.scrollIntoView({ behavior: 'smooth', block: 'center' });

            setTimeout(() => {
                card.style.transform = '';
                card.style.boxShadow = '';
                card.style.zIndex = '';
            }, 3000);
        }
    });
}

// Auto-focus on input when page loads
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('quickJumpInput')?.focus();
});

// Update floor status (Bulk update)
async function updateFloorStatus(floor, status) {
    const statusLabels = {
        'available': 'Trống',
        'occupied': 'Đang ở',
        'maintenance': 'Bảo trì',
        'cleaning': 'Dọn dẹp',
        'reserved': 'Đã đặt'
    };

    const label = statusLabels[status] || status;

    // Custom confirmation message for occupied/reserved which might be dangerous
    let confirmMsg = `Bạn có chắc muốn đổi tất cả phòng tầng ${floor} sang trạng thái "${label}"?`;
    if (status === 'occupied' || status === 'reserved') {
        confirmMsg += '\nLƯU Ý: Việc này sẽ thay đổi trạng thái của cả các phòng đang có khách!';
    }

    if (!confirm(confirmMsg)) return;

    try {
        const response = await fetch('api/update-floor-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `floor=${floor}&status=${status}`
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        showToast('Có lỗi xảy ra: ' + error.message, 'error');
    }
}
