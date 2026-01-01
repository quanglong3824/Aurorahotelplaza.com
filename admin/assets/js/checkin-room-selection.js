/**
 * Admin Check-in Room Selection
 * Allows admin to manually select a room during check-in instead of auto-assignment
 */

let selectedRoomForCheckin = null;

function openCheckinRoomSelectionModal(bookingId, booking, rooms) {
    // Store booking info globally
    window.currentCheckinBooking = booking;
    window.currentCheckinRooms = rooms;
    window.currentCheckinBookingId = bookingId;
    
    const modal = document.getElementById('checkinRoomSelectModal');
    if (!modal) {
        createCheckinRoomModal();
    }
    
    displayCheckinRoomsGrid(rooms);
    document.getElementById('checkinRoomSelectModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function createCheckinRoomModal() {
    const modal = document.createElement('div');
    modal.id = 'checkinRoomSelectModal';
    modal.className = 'fixed inset-0 z-[1000] hidden flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black/50" onclick="closeCheckinRoomModal()"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Chọn phòng để check-in</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bấm vào phòng để chọn</p>
                </div>
                <button onclick="closeCheckinRoomModal()" class="p-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="checkinRoomsList" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <div class="flex items-center justify-center py-12 col-span-full">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 flex items-center justify-between">
                <p id="selectedRoomInfo" class="text-sm text-gray-600 dark:text-gray-400">Chưa chọn phòng</p>
                <div class="flex gap-2">
                    <button onclick="closeCheckinRoomModal()" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600">
                        Hủy
                    </button>
                    <button onclick="confirmCheckinRoomSelection()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed" id="confirmCheckinBtn" disabled>
                        Xác nhận
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function displayCheckinRoomsGrid(rooms) {
    const list = document.getElementById('checkinRoomsList');
    list.innerHTML = '';
    
    if (!rooms || rooms.length === 0) {
        list.innerHTML = '<p class="text-center text-gray-500 py-8 col-span-full">Không có phòng khả dụng</p>';
        return;
    }
    
    rooms.forEach(room => {
        const isAvailable = room.is_available == 1;
        const roomCard = document.createElement('button');
        roomCard.type = 'button';
        roomCard.className = `p-4 rounded-lg border-2 transition-all text-center ${
            isAvailable
                ? 'border-green-300 bg-green-50 dark:bg-green-900/20 hover:border-green-500 hover:bg-green-100 dark:hover:bg-green-900/40 cursor-pointer'
                : 'border-gray-300 bg-gray-100 dark:bg-gray-700 text-gray-500 cursor-not-allowed opacity-60'
        }`;
        
        roomCard.innerHTML = `
            <div class="text-lg font-bold">${room.room_number}</div>
            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">${room.type_name}</div>
            <div class="text-xs mt-2">
                ${isAvailable 
                    ? '<span class="inline-flex items-center gap-1 px-2 py-1 bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 rounded text-xs"><span class="material-symbols-outlined text-sm">check_circle</span>Trống</span>'
                    : '<span class="text-gray-600">Không khả dụng</span>'
                }
            </div>
        `;
        
        if (isAvailable) {
            roomCard.onclick = () => selectCheckinRoom(room);
        }
        
        list.appendChild(roomCard);
    });
}

function selectCheckinRoom(room) {
    selectedRoomForCheckin = room;
    
    // Update UI
    document.querySelectorAll('#checkinRoomsList button').forEach(btn => {
        btn.classList.remove('border-amber-500', 'bg-amber-50', 'dark:bg-amber-900/20');
    });
    
    event.target.closest('button').classList.add('border-amber-500', 'bg-amber-50', 'dark:bg-amber-900/20');
    
    document.getElementById('selectedRoomInfo').textContent = `Đã chọn: Phòng ${room.room_number} (${room.type_name})`;
    document.getElementById('confirmCheckinBtn').disabled = false;
}

function confirmCheckinRoomSelection() {
    if (!selectedRoomForCheckin) {
        alert('Vui lòng chọn một phòng');
        return;
    }
    
    closeCheckinRoomModal();
    performCheckinWithRoom(window.currentCheckinBookingId, selectedRoomForCheckin.room_id);
}

function closeCheckinRoomModal() {
    const modal = document.getElementById('checkinRoomSelectModal');
    if (modal) modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    selectedRoomForCheckin = null;
}

function performCheckinWithRoom(bookingId, roomId) {
    const formData = new FormData();
    formData.append('booking_id', bookingId);
    formData.append('status', 'checked_in');
    formData.append('room_id', roomId);
    
    // Call the existing updateBookingStatus but with room assignment
    assignRoomAndCheckin(bookingId, roomId);
}

function assignRoomAndCheckin(bookingId, roomId) {
    fetch('api/assign-room.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `booking_id=${bookingId}&room_id=${roomId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Now perform check-in
            updateBookingStatus(bookingId, 'checked_in');
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể phân phòng'));
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Có lỗi xảy ra');
    });
}

// Function to load available rooms for check-in
function loadAvailableRoomsForCheckin(bookingId, booking) {
    fetch(`api/get-available-rooms.php?room_type_id=${booking.room_type_id}&check_in=${booking.check_in_date}&check_out=${booking.check_out_date}&booking_id=${bookingId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                openCheckinRoomSelectionModal(bookingId, booking, data.rooms);
            } else {
                alert('Không thể tải danh sách phòng: ' + (data.message || ''));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Có lỗi xảy ra khi tải danh sách phòng');
        });
}
