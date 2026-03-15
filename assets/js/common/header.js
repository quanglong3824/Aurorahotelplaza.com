function toggleTrackForm(show) {
    const form = document.getElementById('topbarTrackForm');
    const input = document.getElementById('trackInput');

    if (show) {
        form.classList.remove('opacity-0', 'pointer-events-none');
        form.classList.add('opacity-100', 'pointer-events-auto');
        setTimeout(() => input.focus(), 300);
    } else {
        form.classList.remove('opacity-100', 'pointer-events-auto');
        form.classList.add('opacity-0', 'pointer-events-none');
        input.blur();
    }
}

function closeTrackingModal() {
    const modal = document.getElementById('trackingModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function openTrackingModal(htmlContent) {
    const modal = document.getElementById('trackingModal');
    if (modal) {
        document.getElementById('trackResultContent').innerHTML = htmlContent;
        modal.classList.add('active');
    }
}

function changeTrackMode(mode) {
    document.getElementById('trackMode').value = mode;

    // Update active button styling
    const btnLatest = document.getElementById('btnTrackLatest');
    const btnAll = document.getElementById('btnTrackAll');

    if (mode === 'latest') {
        btnLatest.className = "px-3 py-1 rounded-md font-medium bg-white text-gray-900 shadow-sm transition-all";
        btnAll.className = "px-3 py-1 rounded-md font-medium text-gray-500 hover:text-gray-700 transition-all";
    } else {
        btnAll.className = "px-3 py-1 rounded-md font-medium bg-white text-gray-900 shadow-sm transition-all";
        btnLatest.className = "px-3 py-1 rounded-md font-medium text-gray-500 hover:text-gray-700 transition-all";
    }

    // Retrigger search without submitting form
    if (document.getElementById('trackInput').value.trim() !== '') {
        performTrackSearch();
    }
}

async function handleTrackBooking(e) {
    e.preventDefault();
    await performTrackSearch();
}

async function performTrackSearch() {
    const input = document.getElementById('trackInput').value.trim();
    const mode = document.getElementById('trackMode').value;
    if (!input) {
        shakeTrackInput();
        return;
    }

    const submitBtn = document.getElementById('topbarTrackForm').querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin hidden sm:inline-block" style="font-size:16px;">refresh</span> ' + AuroraHeaderData.trackingLang.searching;
    submitBtn.disabled = true;

    document.getElementById('trackErrorMsg').classList.add('hidden');

    try {
        const res = await fetch(AuroraHeaderData.basePath + 'booking/api/track.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ query: input, mode: mode })
        });
        const data = await res.json();

        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;

        if (data.success && data.bookings && data.bookings.length > 0) {
            let html = '<div class="space-y-4">';

            data.bookings.forEach((bookingItem) => {
                let statusColor = 'bg-gray-100 text-gray-800';
                if (bookingItem.status_raw === 'confirmed') statusColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200';
                if (bookingItem.status_raw === 'checked_in') statusColor = 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200';
                if (bookingItem.status_raw === 'cancelled' || bookingItem.status_raw === 'no_show') statusColor = 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200';
                if (bookingItem.status_raw === 'pending') statusColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200';

                let statusLabel = AuroraHeaderData.trackingLang.statusText[bookingItem.status_raw] || bookingItem.status_raw;

                html += '<div class="bg-gray-50/80 dark:bg-gray-800/80 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">';
                html += '<h4 class="font-bold text-lg text-primary-600 dark:text-primary-400 border-b pb-2 mb-3">' + AuroraHeaderData.trackingLang.bookingCode + ': ' + bookingItem.booking_code + '</h4>';
                html += '<div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">';
                html += '<div class="flex justify-between items-center"><strong class="w-1/3">' + AuroraHeaderData.trackingLang.status + ':</strong> <span class="badge ' + statusColor + ' px-2 py-0.5 rounded font-semibold text-center flex-1">' + statusLabel + '</span></div>';
                html += '<div class="flex"><strong class="w-1/3">' + AuroraHeaderData.trackingLang.customer + ':</strong> <span class="flex-1">' + bookingItem.customer_name + '</span></div>';
                html += '<div class="flex"><strong class="w-1/3">' + AuroraHeaderData.trackingLang.checkIn + ':</strong> <span class="flex-1">' + bookingItem.check_in + '</span></div>';
                html += '<div class="flex"><strong class="w-1/3">' + AuroraHeaderData.trackingLang.checkOut + ':</strong> <span class="flex-1">' + bookingItem.check_out + '</span></div>';
                html += '<div class="flex"><strong class="w-1/3">' + AuroraHeaderData.trackingLang.phone + ':</strong> <span class="flex-1">' + (bookingItem.phone || '') + '</span></div>';
                html += '</div></div>';
                html += '<div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-3 pb-1">';
                html += '<strong class="text-gray-800 dark:text-gray-200">' + AuroraHeaderData.trackingLang.total + ':</strong>';
                html += '<span class="text-xl font-bold text-primary-600 mt-auto">' + new Intl.NumberFormat('vi-VN').format(bookingItem.total_amount) + ' VND</span>';
                html += '</div></div>';
                html += '<hr class="my-3 border-gray-200 dark:border-gray-700">';
            });

            html += '</div>';
            openTrackingModal(html);
        } else {
            // Not found or error → shake input and quick toast
            shakeTrackInput();
            if (data.error_code === 'system') {
                showTrackError(AuroraHeaderData.trackingLang.errorSystem + (data.message || ''));
            }
            // not_found / empty: just shake, no long text blocking the topbar
        }
    } catch (err) {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
        showTrackError(AuroraHeaderData.trackingLang.errorSystem + err.message);
    }
}

function showTrackError(message) {
    const errObj = document.getElementById('trackErrorMsg');
    errObj.querySelector('.error-text').innerText = message;
    errObj.classList.remove('hidden');
    setTimeout(() => errObj.classList.add('hidden'), 2500);
}

function shakeTrackInput() {
    const inputWrap = document.getElementById('trackInput').closest('.relative') || document.getElementById('trackInput');
    inputWrap.classList.add('track-shake');
    setTimeout(() => inputWrap.classList.remove('track-shake'), 600);
}

// Measure Header height dynamically for exact padding
function syncHeaderHeight() {
    const headerObj = document.getElementById('main-header');
    if (headerObj) {
        document.documentElement.style.setProperty('--header-height', headerObj.offsetHeight + 'px');
    }
}

(function () {
    try {
        if (!document.querySelector('link[rel="icon"]')) {
            const link = document.createElement('link');
            link.rel = 'icon';
            link.type = 'image/png';
            link.href = AuroraHeaderData.basePath + 'assets/img/src/logo/favicon.png';
            document.head.appendChild(link);
        }
    } catch (e) { }
})();

window.addEventListener('load', syncHeaderHeight);
window.addEventListener('resize', syncHeaderHeight);
document.addEventListener('DOMContentLoaded', syncHeaderHeight);
syncHeaderHeight(); // init
