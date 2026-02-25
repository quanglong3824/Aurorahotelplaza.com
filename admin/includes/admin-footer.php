</main>
</div>

<!-- Overlay for mobile sidebar -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

<script>
    // Sidebar toggle for mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    }

    sidebarToggle?.addEventListener('click', toggleSidebar);
    sidebarOverlay?.addEventListener('click', toggleSidebar);

    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    themeToggle?.addEventListener('click', () => {
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    });

    // Load theme from localStorage
    if (localStorage.getItem('theme') === 'dark') {
        html.classList.add('dark');
    }

    // Auto-hide mobile sidebar on navigation
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                toggleSidebar();
            }
        });
    });

    // Confirm delete actions
    function confirmDelete(message = 'Bạn có chắc chắn muốn xóa?') {
        return confirm(message);
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 animate-slide-up ${type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                    type === 'warning' ? 'bg-yellow-500' :
                        'bg-blue-500'
            } text-white`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    // Format date
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    // Format datetime
    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
</script>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/checkin-room-selection.js"></script>

<script>
    // Chat unread badge — poll mỗi 30s trên mọi trang admin
    (function () {
        function fetchChatBadge() {
            fetch('/api/chat/get-conversations.php?status=active&page=1&limit=1')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const count = parseInt(data.stats?.total_unread) || 0;

                    const badges = [
                        document.getElementById('chatUnreadBadge'),
                        document.getElementById('chatSidebarBadge')
                    ];
                    badges.forEach(b => {
                        if (!b) return;
                        b.textContent = count > 99 ? '99+' : count;
                        if (count > 0) b.classList.remove('hidden');
                        else b.classList.add('hidden');
                    });

                    // Tab title prefix (chỉ khi không ở trang chat)
                    if (!window.location.pathname.endsWith('chat.php') && count > 0) {
                        const base = document.title.replace(/^\(\d+\+?\)\s/, '');
                        document.title = `(${count > 99 ? '99+' : count}) ${base}`;
                    }
                })
                .catch(() => { });
        }

        // Chạy ngay + mỗi 30s
        fetchChatBadge();
        setInterval(fetchChatBadge, 30000);
    })();
</script>
</body>

</html>