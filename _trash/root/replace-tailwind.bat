@echo off
echo Replacing tailwindcss-cdn.js with tailwind-output.css in all PHP files...

powershell -Command ^
    "(Get-Content 'terms.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'terms.php'"
powershell -Command ^
    "(Get-Content 'services.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'services.php'"
powershell -Command ^
    "(Get-Content 'service-detail.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'service-detail.php'"
powershell -Command ^
    "(Get-Content 'rooms.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'rooms.php'"
powershell -Command ^
    "(Get-Content 'room-map-user.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'room-map-user.php'"
powershell -Command ^
    "(Get-Content 'room-details\vip-suite.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'room-details\vip-suite.php'"
powershell -Command ^
    "(Get-Content 'room-details\premium-twin.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'room-details\premium-twin.php'"
powershell -Command ^
    "(Get-Content 'room-details\premium-deluxe.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'room-details\premium-deluxe.php'"
powershell -Command ^
    "(Get-Content 'room-details\deluxe.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'room-details\deluxe.php'"
powershell -Command ^
    "(Get-Content 'profile.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'profile.php'"
powershell -Command ^
    "(Get-Content 'profile\view-qrcode.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'profile\view-qrcode.php'"
powershell -Command ^
    "(Get-Content 'profile\loyalty.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'profile\loyalty.php'"
powershell -Command ^
    "(Get-Content 'profile\index.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'profile\index.php'"
powershell -Command ^
    "(Get-Content 'profile\edit.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'profile\edit.php'"
powershell -Command ^
    "(Get-Content 'profile\bookings.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'profile\bookings.php'"
powershell -Command ^
    "(Get-Content 'profile\booking-detail.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'profile\booking-detail.php'"
powershell -Command ^
    "(Get-Content 'privacy.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'privacy.php'"
powershell -Command ^
    "(Get-Content 'apartments.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'apartments.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\family-apartment.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\family-apartment.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\classical-premium.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\classical-premium.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\classical-family.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\classical-family.php'"
powershell -Command ^
    "(Get-Content 'gallery.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'gallery.php'"
powershell -Command ^
    "(Get-Content 'explore.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'explore.php'"
powershell -Command ^
    "(Get-Content 'contact.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'contact.php'"
powershell -Command ^
    "(Get-Content 'cancellation-policy.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'cancellation-policy.php'"
powershell -Command ^
    "(Get-Content 'booking\vnpay_return.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'booking\vnpay_return.php'"
powershell -Command ^
    "(Get-Content 'booking\confirmation.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'booking\confirmation.php'"
powershell -Command ^
    "(Get-Content 'admin\includes\admin-header.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'admin\includes\admin-header.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\modern-premium.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\modern-premium.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\indochine-studio.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\indochine-studio.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\indochine-family.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\indochine-family.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\modern-studio.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\modern-studio.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\premium-apartment.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\premium-apartment.php'"
powershell -Command ^
    "(Get-Content 'blog-detail.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'blog-detail.php'"
powershell -Command ^
    "(Get-Content 'blog.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'blog.php'"
powershell -Command ^
    "(Get-Content 'auth\reset-password.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'auth\reset-password.php'"
powershell -Command ^
    "(Get-Content 'auth\register.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'auth\register.php'"
powershell -Command ^
    "(Get-Content 'auth\logout-confirm.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'auth\logout-confirm.php'"
powershell -Command ^
    "(Get-Content 'auth\login.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'auth\login.php'"
powershell -Command ^
    "(Get-Content 'auth\forgot-password.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'auth\forgot-password.php'"
powershell -Command ^
    "(Get-Content 'auth\change-password.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'auth\change-password.php'"
powershell -Command ^
    "(Get-Content 'about.php') -replace 'assets/js/tailwindcss-cdn.js', 'assets/css/tailwind-output.css' | Set-Content 'about.php'"
powershell -Command ^
    "(Get-Content 'apartment-details\studio-apartment.php') -replace '../assets/js/tailwindcss-cdn.js', '../assets/css/tailwind-output.css' | Set-Content 'apartment-details\studio-apartment.php'"

echo Done!
