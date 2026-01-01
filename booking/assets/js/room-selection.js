// Handle room selection from map

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const selectedRoomId = params.get('selected_room_id');
    const selectedRoomNumber = params.get('selected_room_number');
    
    if (selectedRoomId && selectedRoomNumber) {
        // Set hidden fields with selected room
        const roomIdField = document.getElementById('selected_room_id');
        const roomNumberField = document.getElementById('selected_room_number');
        
        if (roomIdField) roomIdField.value = selectedRoomId;
        if (roomNumberField) roomNumberField.value = selectedRoomNumber;
        
        // Call the room map selection handler if it exists
        if (typeof handleRoomMapSelection === 'function') {
            handleRoomMapSelection();
        }
    }
});
