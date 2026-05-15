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
                displayRoomDetail(data.room, data.current_booking, data.booking_history, data.activity_logs);
            } else {
                document.getElementById('roomModalContent').innerHTML =
                    '<div class="text-center py-8 text-red-600">' + (data.message || 'Không thể tải thông tin phòng') + '</div>';
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('roomModalContent').innerHTML =
                '<div class="text-center py-8 text-red-600">Có lỗi xảy ra khi tải thông tin phòng</div>';
        });
}

// Display room detail in modal - with Info & Edit tabs
function displayRoomDetail(room, currentBooking, history, activityLogs) {
    const statusColors = {
        'available':   'bg-green-100 text-green-800',
        'occupied':    'bg-red-100 text-red-800',
        'maintenance': 'bg-orange-100 text-orange-800',
        'cleaning':    'bg-blue-100 text-blue-800',
        'reserved':    'bg-purple-100 text-purple-800',
        'inactive':    'bg-gray-100 text-gray-500',
    };
    const statusLabels = {
        'available':   'Trống',
        'occupied':    'Đang ở',
        'maintenance': 'Bảo trì',
        'cleaning':    'Dọn dẹp',
        'reserved':    'Đã đặt',
        'inactive':    'Ngừng',
    };

    const esc = (v) => String(v || '').replace(/"/g, '&quot;').replace(/\'/g, '&#39;');
    const lastCleaned = room.last_cleaned ? room.last_cleaned.split(' ')[0] : '';
    const formatDate = (d) => d ? new Date(d).toLocaleDateString('vi-VN', {day:'2-digit',month:'2-digit',year:'numeric'}) : '—';
    const formatDateTime = (d) => d ? new Date(d).toLocaleString('vi-VN') : '—';
    const formatMoney = (v) => v ? parseInt(v).toLocaleString() + ' VND' : '—';

    let html = `
        <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-slate-800 rounded-xl p-1">
            <button onclick="switchRoomTab('info')" id="tab-info"
                class="flex-1 py-2 px-4 rounded-lg text-sm font-semibold transition-all bg-white dark:bg-slate-700 shadow text-gray-800 dark:text-white flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">info</span> Thông tin
            </button>
            <button onclick="switchRoomTab('guest')" id="tab-guest"
                class="flex-1 py-2 px-4 rounded-lg text-sm font-semibold transition-all text-gray-500 dark:text-gray-400 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">person</span> Khách
            </button>
            <button onclick="switchRoomTab('logs')" id="tab-logs"
                class="flex-1 py-2 px-4 rounded-lg text-sm font-semibold transition-all text-gray-500 dark:text-gray-400 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">history</span> Nhật ký
            </button>
            <button onclick="switchRoomTab('edit')" id="tab-edit"
                class="flex-1 py-2 px-4 rounded-lg text-sm font-semibold transition-all text-gray-500 dark:text-gray-400 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">edit</span> Sửa
            </button>
        </div>

        <!-- INFO TAB -->
        <div id="tab-panel-info" class="space-y-5">
            <div class="bg-gradient-to-br from-[#d4af37]/10 to-[#b8941f]/10 rounded-xl p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h4 class="text-3xl font-bold mb-1" style="color:#d4af37;">Phòng ${room.room_number}</h4>
                        <p class="text-lg font-semibold">${room.type_name}</p>
                        <p class="text-sm text-gray-500">Tầng ${room.floor} · ${room.category === 'room' ? 'Phòng' : 'Căn hộ'}</p>
                        ${room.notes ? `<p class="text-sm text-gray-400 mt-1 italic">"${esc(room.notes)}"</p>` : ''}
                    </div>
                    <span class="badge ${statusColors[room.status] || 'bg-gray-100 text-gray-600'} text-sm px-4 py-2">${statusLabels[room.status] || room.status}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                        <div class="text-xs text-gray-500 mb-1">Giá cơ bản</div>
                        <div class="text-lg font-bold" style="color:#d4af37;">${parseInt(room.base_price).toLocaleString()} VND</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                        <div class="text-xs text-gray-500 mb-1">Sức chứa</div>
                        <div class="text-lg font-bold">${room.max_occupancy} người</div>
                    </div>
                </div>
            </div>

            ${currentBooking ? `
                <div class="card">
                    <div class="card-header">
                        <h5 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined">person</span> Khách đang ở</h5>
                    </div>
                    <div class="card-body">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="font-semibold text-lg">${esc(currentBooking.guest_name)}</p>
                                <p class="text-sm text-gray-500">${esc(currentBooking.email || '')}</p>
                                <p class="text-sm text-gray-500">${esc(currentBooking.phone || '')}</p>
                            </div>
                            <a href="booking-detail.php?id=${currentBooking.booking_id}" class="btn btn-primary btn-sm">Xem chi tiết</a>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-sm mb-3">
                            <div><span class="text-gray-500">Check-in:</span><p class="font-semibold">${formatDate(currentBooking.check_in_date)}</p></div>
                            <div><span class="text-gray-500">Check-out:</span><p class="font-semibold">${formatDate(currentBooking.check_out_date)}</p></div>
                            <div><span class="text-gray-500">Tổng:</span><p class="font-semibold" style="color:#d4af37;">${formatMoney(currentBooking.total_amount)}</p></div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <div><span class="text-gray-500">Trạng thái:</span><p class="font-semibold">${currentBooking.status === 'checked_in' ? '🟢 Đang ở' : '🟡 Đã xác nhận'}</p></div>
                            <div><span class="text-gray-500">Người lớn / Trẻ em:</span><p class="font-semibold">${currentBooking.num_adults} / ${currentBooking.num_children || 0}</p></div>
                            <div><span class="text-gray-500">Giường phụ:</span><p class="font-semibold">${currentBooking.extra_beds || 0}</p></div>
                        </div>
                        ${currentBooking.special_requests ? `
                            <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200">
                                <p class="text-xs font-semibold text-amber-700 mb-1">Yêu cầu đặc biệt:</p>
                                <p class="text-sm text-amber-800">${esc(currentBooking.special_requests)}</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            ` : '<div class="text-center py-3 text-gray-400 text-sm">Phòng hiện đang trống</div>'}

            <div class="card">
                <div class="card-header">
                    <h5 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined">history</span> Lịch sử đặt phòng</h5>
                </div>
                <div class="card-body">
                    ${history && history.length > 0 ? `
                        <div class="space-y-2">
                            ${history.map(b => `
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                                    <div class="flex-1">
                                        <p class="font-semibold">${esc(b.guest_name)}</p>
                                        <p class="text-sm text-gray-500">${formatDate(b.check_in_date)} – ${formatDate(b.check_out_date)}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold" style="color:#d4af37;">${formatMoney(b.total_amount)}</p>
                                        <span class="badge badge-${b.status === 'completed' ? 'success' : b.status === 'cancelled' ? 'danger' : 'secondary'} text-xs">${b.status === 'completed' ? 'Hoàn thành' : b.status === 'cancelled' ? 'Đã hủy' : b.status}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-center text-gray-400 py-4 text-sm">Chưa có lịch sử đặt phòng</p>'}
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button onclick="switchRoomTab('edit')" class="btn btn-primary w-full">
                    <span class="material-symbols-outlined text-sm">edit</span> Chỉnh sửa phòng
                </button>
                ${room.status === 'available' ? `
                    <a href="bookings.php?room_id=${room.room_id}" class="btn btn-secondary w-full">
                        <span class="material-symbols-outlined text-sm">add</span> Tạo booking
                    </a>
                ` : `
                    <button onclick="changeRoomStatus(${room.room_id}, '${room.status}', '${esc(room.room_number)}')" class="btn btn-secondary w-full">
                        <span class="material-symbols-outlined text-sm">toggle_on</span> Đổi trạng thái
                    </button>
                `}
            </div>
        </div>

        <!-- GUEST TAB -->
        <div id="tab-panel-guest" class="hidden space-y-5">
            ${currentBooking ? `
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-5 border border-blue-200">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-14 h-14 rounded-full bg-blue-500 flex items-center justify-center text-white text-xl font-bold">
                            ${(currentBooking.guest_name || '?').charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="text-xl font-bold">${esc(currentBooking.guest_name)}</h4>
                            <p class="text-sm text-gray-500">Mã đơn: <strong>${esc(currentBooking.booking_code)}</strong></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                            <div class="text-xs text-gray-500 mb-1">Email</div>
                            <div class="font-semibold text-sm">${esc(currentBooking.email || '—')}</div>
                        </div>
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                            <div class="text-xs text-gray-500 mb-1">Điện thoại</div>
                            <div class="font-semibold text-sm">${esc(currentBooking.phone || '—')}</div>
                        </div>
                        ${currentBooking.id_number ? `
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                            <div class="text-xs text-gray-500 mb-1">CMND/CCCD</div>
                            <div class="font-semibold text-sm">${esc(currentBooking.id_number)}</div>
                        </div>` : ''}
                        ${currentBooking.date_of_birth ? `
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                            <div class="text-xs text-gray-500 mb-1">Ngày sinh</div>
                            <div class="font-semibold text-sm">${formatDate(currentBooking.date_of_birth)}</div>
                        </div>` : ''}
                        ${currentBooking.gender ? `
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                            <div class="text-xs text-gray-500 mb-1">Giới tính</div>
                            <div class="font-semibold text-sm">${currentBooking.gender === 'male' ? 'Nam' : currentBooking.gender === 'female' ? 'Nữ' : 'Khác'}</div>
                        </div>` : ''}
                        ${currentBooking.address ? `
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3 col-span-2">
                            <div class="text-xs text-gray-500 mb-1">Địa chỉ</div>
                            <div class="font-semibold text-sm">${esc(currentBooking.address)}</div>
                        </div>` : ''}
                        ${currentBooking.member_since ? `
                        <div class="bg-white dark:bg-slate-800 rounded-lg p-3">
                            <div class="text-xs text-gray-500 mb-1">Thành viên từ</div>
                            <div class="font-semibold text-sm">${formatDate(currentBooking.member_since)}</div>
                        </div>` : ''}
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined">receipt_long</span> Chi tiết lưu trú</h5>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 block mb-1">Loại phòng đặt</span>
                                <p class="font-semibold">${esc(currentBooking.room_type_name || currentBooking.type_name)}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Mã đặt phòng</span>
                                <p class="font-semibold font-mono">${esc(currentBooking.booking_code)}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Nhận phòng</span>
                                <p class="font-semibold">${formatDateTime(currentBooking.check_in_date)}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Trả phòng</span>
                                <p class="font-semibold">${formatDateTime(currentBooking.check_out_date)}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Số đêm</span>
                                <p class="font-semibold">${currentBooking.total_nights || Math.ceil((new Date(currentBooking.check_out_date) - new Date(currentBooking.check_in_date)) / 86400000)} đêm</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Số khách</span>
                                <p class="font-semibold">${currentBooking.num_adults} người lớn, ${currentBooking.num_children || 0} trẻ em</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Giường phụ</span>
                                <p class="font-semibold">${currentBooking.extra_beds || 0}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Phương thức thanh toán</span>
                                <p class="font-semibold">${currentBooking.payment_method === 'vnpay' ? 'VNPay' : currentBooking.payment_method === 'cash' ? 'Tiền mặt' : currentBooking.payment_method || '—'}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Trạng thái thanh toán</span>
                                <p class="font-semibold">${currentBooking.payment_status === 'paid' ? '✅ Đã thanh toán' : currentBooking.payment_status === 'partial' ? '🟡 Thanh toán một phần' : currentBooking.payment_status === 'pending' ? '⏳ Chưa thanh toán' : '—'}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-1">Tổng chi phí</span>
                                <p class="font-bold text-lg" style="color:#d4af37;">${formatMoney(currentBooking.total_amount)}</p>
                            </div>
                        </div>
                        ${currentBooking.special_requests ? `
                            <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200">
                                <p class="text-xs font-semibold text-amber-700 mb-1">Yêu cầu đặc biệt:</p>
                                <p class="text-sm text-amber-800">${esc(currentBooking.special_requests)}</p>
                            </div>
                        ` : ''}
                        <div class="mt-4 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Ngày đặt</p>
                            <p class="font-semibold text-sm">${formatDateTime(currentBooking.created_at)}</p>
                        </div>
                    </div>
                </div>
            ` : `
                <div class="text-center py-12">
                    <span class="material-symbols-outlined text-6xl text-gray-300">person_off</span>
                    <p class="text-gray-400 mt-4">Không có khách đang ở phòng này</p>
                </div>
            `}
        </div>

        <!-- LOGS TAB -->
        <div id="tab-panel-logs" class="hidden space-y-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined">history_toggle_off</span> Lịch sử thay đổi đơn phòng</h5>
                </div>
                <div class="card-body">
                    ${history && history.length > 0 ? `
                        <div class="space-y-2">
                            ${history.map(b => `
                                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-blue-600 text-sm">event_note</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold">${esc(b.guest_name)} — ${formatDate(b.check_in_date)} → ${formatDate(b.check_out_date)}</p>
                                        <p class="text-xs text-gray-500">${formatMoney(b.total_amount)}</p>
                                        <span class="badge badge-${b.status === 'completed' ? 'success' : b.status === 'cancelled' ? 'danger' : 'secondary'} text-xs">${b.status === 'completed' ? 'Hoàn thành' : b.status === 'cancelled' ? 'Đã hủy' : b.status}</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-center text-gray-400 py-4 text-sm">Chưa có lịch sử thay đổi</p>'}
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined">track_changes</span> Nhật ký hoạt động</h5>
                </div>
                <div class="card-body">
                    ${activityLogs && activityLogs.length > 0 ? `
                        <div class="space-y-2">
                            ${activityLogs.map(log => `
                                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-purple-600 text-sm">${log.action === 'assign_room' ? 'meeting_room' : log.action === 'check_in' ? 'login' : log.action === 'check_out' ? 'logout' : 'info'}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold">${esc(log.action || '').replace(/_/g, ' ').toUpperCase()}</p>
                                        <p class="text-xs text-gray-500">${esc(log.description || '')}</p>
                                        <p class="text-xs text-gray-400 mt-1">${formatDateTime(log.created_at)} · ${esc(log.admin_name || 'Hệ thống')}</p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-center text-gray-400 py-4 text-sm">Chưa có nhật ký hoạt động</p>'}
                </div>
            </div>
        </div>

        <!-- EDIT TAB -->
        <div id="tab-panel-edit" class="hidden space-y-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="material-symbols-outlined text-[#d4af37]">edit_square</span>
                <span class="font-semibold text-lg">Chỉnh sửa phòng <span class="text-[#d4af37]">${room.room_number}</span></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Số phòng *</label>
                    <input type="text" id="edit_room_number" value="${esc(room.room_number)}" class="form-input w-full" placeholder="VD: 101" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Tầng *</label>
                    <input type="number" id="edit_floor" value="${room.floor || 1}" min="1" max="50" class="form-input w-full" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Loại phòng *</label>
                <div id="edit_room_type_list" class="space-y-1.5 max-h-52 overflow-y-auto border border-gray-200 dark:border-slate-600 rounded-xl p-2 bg-gray-50 dark:bg-slate-800/50">
                    <div class="text-center py-4 text-gray-400 text-sm">Đang tải...</div>
                </div>
                <input type="hidden" id="edit_room_type_id" value="${room.room_type_id}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Trạng thái</label>
                <select id="edit_status" class="form-select w-full">
                    <option value="available"   ${room.status === 'available'   ? 'selected' : ''}>✅ Trống</option>
                    <option value="occupied"    ${room.status === 'occupied'    ? 'selected' : ''}>🔴 Đang có khách</option>
                    <option value="maintenance" ${room.status === 'maintenance' ? 'selected' : ''}>🟠 Bảo trì</option>
                    <option value="cleaning"    ${room.status === 'cleaning'    ? 'selected' : ''}>🔵 Dọn dẹp</option>
                    <option value="reserved"    ${room.status === 'reserved'    ? 'selected' : ''}>🟣 Đã đặt</option>
                    <option value="inactive"    ${room.status === 'inactive'    ? 'selected' : ''}>⚫ Ngừng hoạt động</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Dọn phòng lần cuối</label>
                    <input type="date" id="edit_last_cleaned" value="${lastCleaned}" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Ghi chú</label>
                    <input type="text" id="edit_notes" value="${esc(room.notes)}" class="form-input w-full" placeholder="Ghi chú nội bộ...">
                </div>
            </div>

            <div class="flex gap-3 pt-1">
                <button onclick="saveRoomFull(${room.room_id})" id="btnSaveRoomFull"
                    class="flex-1 btn btn-primary flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span> Lưu thay đổi
                </button>
                <button onclick="switchRoomTab('info')" class="btn btn-secondary flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">close</span> Huỷ
                </button>
            </div>
        </div>
    `;

    document.getElementById('roomModalContent').innerHTML = html;
    loadRoomTypesForEdit(room.room_type_id);
}

function switchRoomTab(tab) {
    ['info', 'guest', 'logs', 'edit'].forEach(t => {
        const btn   = document.getElementById('tab-' + t);
        const panel = document.getElementById('tab-panel-' + t);
        if (!btn || !panel) return;
        if (t === tab) {
            btn.classList.add('bg-white', 'shadow', 'text-gray-800');
            btn.classList.remove('text-gray-500', 'dark:text-gray-400');
            panel.classList.remove('hidden');
        } else {
            btn.classList.remove('bg-white', 'shadow', 'text-gray-800');
            btn.classList.add('text-gray-500', 'dark:text-gray-400');
            panel.classList.add('hidden');
        }
    });
}

async function loadRoomTypesForEdit(currentTypeId) {
    try {
        const res  = await fetch('api/get-room-types.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        const c = document.getElementById('edit_room_type_list');
        if (!c) return;

        const groups = { room: [], apartment: [] };
        data.room_types.forEach(t => { if (groups[t.category]) groups[t.category].push(t); });

        const renderGroup = (label, icon, types) => {
            if (!types.length) return '';
            return `<div class="text-xs font-bold text-gray-400 uppercase tracking-wider px-2 pt-2 pb-1">${icon} ${label}</div>`
                + types.map(t => {
                    const active = parseInt(t.room_type_id) === parseInt(currentTypeId);
                    return `<button type="button" onclick="selectEditRoomType(${t.room_type_id})"
                        id="rt-opt-${t.room_type_id}"
                        class="w-full flex items-center gap-3 p-2.5 rounded-lg border-2 text-left transition-all mb-1
                            ${active ? 'border-[#d4af37] bg-[#d4af37]/10' : 'border-transparent hover:border-gray-300 hover:bg-white dark:hover:bg-slate-700/50'}">
                        <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center
                            bg-gradient-to-br ${t.category === 'room' ? 'from-blue-500 to-blue-600' : 'from-purple-500 to-purple-600'}">
                            <span class="material-symbols-outlined text-white text-sm">${t.category === 'room' ? 'bed' : 'apartment'}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm truncate">${t.type_name}</div>
                            <div class="text-xs text-gray-500">${parseInt(t.base_price).toLocaleString()} VND · ${t.max_occupancy} ng</div>
                        </div>
                        ${active ? '<span class="material-symbols-outlined text-[#d4af37] text-sm flex-shrink-0">check_circle</span>' : ''}
                    </button>`;
                }).join('');
        };

        c.innerHTML = renderGroup('Phòng', '🏨', groups.room) + renderGroup('Căn hộ', '🏢', groups.apartment)
            || '<p class="text-center text-gray-400 py-4 text-sm">Không có loại phòng nào</p>';

    } catch (e) {
        const c = document.getElementById('edit_room_type_list');
        if (c) c.innerHTML = `<p class="text-red-500 text-sm text-center py-3">Lỗi: ${e.message}</p>`;
    }
}

function selectEditRoomType(typeId) {
    document.getElementById('edit_room_type_id').value = typeId;
    document.querySelectorAll('[id^="rt-opt-"]').forEach(btn => {
        const isActive = btn.id === 'rt-opt-' + typeId;
        btn.classList.toggle('border-[#d4af37]', isActive);
        btn.classList.toggle('bg-[#d4af37]/10', isActive);
        btn.classList.toggle('border-transparent', !isActive);
        const icon = btn.querySelector('.text-\[\#d4af37\]');
        if (isActive && !icon) {
            btn.insertAdjacentHTML('beforeend', '<span class="material-symbols-outlined text-[#d4af37] text-sm flex-shrink-0">check_circle</span>');
        } else if (!isActive && icon) {
            icon.remove();
        }
    });
}

async function saveRoomFull(roomId) {
    const roomTypeId  = document.getElementById('edit_room_type_id')?.value;
    const roomNumber  = document.getElementById('edit_room_number')?.value?.trim();
    const floor       = document.getElementById('edit_floor')?.value;
    const status      = document.getElementById('edit_status')?.value;
    const notes       = document.getElementById('edit_notes')?.value?.trim();
    const lastCleaned = document.getElementById('edit_last_cleaned')?.value;

    if (!roomNumber || !roomTypeId) {
        showToast('Vui lòng điền số phòng và chọn loại phòng', 'error');
        return;
    }

    const btn = document.getElementById('btnSaveRoomFull');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Đang lưu...'; }

    try {
        const res  = await fetch('api/update-room-full.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: roomId, room_type_id: roomTypeId, room_number: roomNumber, floor, status, notes, last_cleaned: lastCleaned })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Lưu thay đổi'; }
        }
    } catch (e) {
        showToast('Lỗi kết nối: ' + e.message, 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Lưu thay đổi'; }
    }
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
                        <div class="font-bold text-[#d4af37]">${parseInt(type.base_price).toLocaleString()} VND</div>
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
