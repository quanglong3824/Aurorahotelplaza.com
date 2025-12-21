document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action*="booking/index.php"]');
    if (!form) return;

    const checkInInput = form.querySelector('input[name="check_in"]');
    const checkOutInput = form.querySelector('input[name="check_out"]');

    if (!checkInInput || !checkOutInput) return;

    // Set min date for check-in to today (Local Time)
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const today = `${year}-${month}-${day}`;

    checkInInput.min = today;

    // Default to today if empty
    if (!checkInInput.value) {
        checkInInput.value = today;
    }

    // Update check-out min date
    function updateCheckOutMin() {
        if (!checkInInput.value) return;

        // Parse local date from input
        const parts = checkInInput.value.split('-');
        const y = parseInt(parts[0]);
        const m = parseInt(parts[1]) - 1;
        const dt = parseInt(parts[2]);

        const nextDay = new Date(y, m, dt + 1);

        const ndYear = nextDay.getFullYear();
        const ndMonth = String(nextDay.getMonth() + 1).padStart(2, '0');
        const ndDay = String(nextDay.getDate()).padStart(2, '0');
        const minCheckOut = `${ndYear}-${ndMonth}-${ndDay}`;

        checkOutInput.min = minCheckOut;

        // If current checkout is invalid or empty, update it
        if (!checkOutInput.value || checkOutInput.value < minCheckOut) {
            checkOutInput.value = minCheckOut;
        }
    }

    checkInInput.addEventListener('change', updateCheckOutMin);

    // Initialize
    updateCheckOutMin();

    // Form validation
    form.addEventListener('submit', function (e) {
        if (!checkInInput.value || !checkOutInput.value) {
            e.preventDefault();
            alert('Vui lòng chọn ngày nhận và trả phòng');
            return;
        }

        if (checkOutInput.value <= checkInInput.value) {
            e.preventDefault();
            alert('Ngày trả phòng phải sau ngày nhận phòng');
        }
    });
});
