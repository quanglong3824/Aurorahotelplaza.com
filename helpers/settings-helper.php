<?php
/**
 * Settings Helper - Aurora Hotel Plaza
 * Đọc system_settings từ DB với static cache
 */

/**
 * Lấy một setting từ bảng system_settings
 * @param string $key     Tên setting
 * @param mixed  $default Giá trị mặc định nếu không có
 * @return mixed
 */
function getSystemSetting(string $key, $default = null) {
    static $cache = null;

    if ($cache === null) {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $cache = $rows ?: [];
        } catch (Exception $e) {
            $cache = [];
        }
    }

    return $cache[$key] ?? $default;
}

/**
 * Kiểm tra xem có hiển thị giá trên frontend không
 * Default = '0' (ẩn giá, hiển thị "Liên hệ")
 */
function showPrices(): bool {
    return getSystemSetting('show_prices', '0') === '1';
}

/**
 * Render giá hoặc "Liên hệ"
 * @param float|int $price      Giá trị số
 * @param string    $suffix     Hậu tố (VD: 'VND', '/đêm')
 * @param string    $cssClass   CSS class khi hiện giá
 */
function renderPrice($price, string $suffix = 'VND', string $cssClass = ''): string {
    if (showPrices()) {
        $formatted = number_format((float)$price, 0, ',', '.');
        $cls = $cssClass ? " class=\"{$cssClass}\"" : '';
        return "<span{$cls}>{$formatted} {$suffix}</span>";
    }
    return '<span class="price-contact">Liên hệ</span>';
}
