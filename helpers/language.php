<?php
/**
 * Aurora Hotel Plaza - Multi-language Helper
 * Hỗ trợ đa ngôn ngữ cho cả UI tĩnh và dữ liệu database
 */

// Ngôn ngữ mặc định
define('DEFAULT_LANG', 'vi');
define('SUPPORTED_LANGS', ['vi', 'en']);

/**
 * Khởi tạo ngôn ngữ từ session/cookie/browser
 */
function initLanguage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Ưu tiên: GET param > Session > Cookie > Browser > Default
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS)) {
        $_SESSION['lang'] = $_GET['lang'];
        setcookie('lang', $_GET['lang'], time() + (365 * 24 * 60 * 60), '/');
    } elseif (!isset($_SESSION['lang'])) {
        if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], SUPPORTED_LANGS)) {
            $_SESSION['lang'] = $_COOKIE['lang'];
        } else {
            // Detect từ browser
            $_SESSION['lang'] = detectBrowserLanguage();
        }
    }
    
    return $_SESSION['lang'];
}

/**
 * Detect ngôn ngữ từ browser
 */
function detectBrowserLanguage() {
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return DEFAULT_LANG;
    }
    
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    return in_array($browser_lang, SUPPORTED_LANGS) ? $browser_lang : DEFAULT_LANG;
}

/**
 * Lấy ngôn ngữ hiện tại
 */
function getLang() {
    return $_SESSION['lang'] ?? DEFAULT_LANG;
}

/**
 * Load file ngôn ngữ
 */
function loadTranslations($lang = null) {
    static $translations = [];
    
    $lang = $lang ?? getLang();
    
    if (!isset($translations[$lang])) {
        $lang_file = __DIR__ . '/../lang/' . $lang . '.php';
        if (file_exists($lang_file)) {
            $translations[$lang] = require $lang_file;
        } else {
            // Fallback to Vietnamese
            $translations[$lang] = require __DIR__ . '/../lang/vi.php';
        }
    }
    
    return $translations[$lang];
}

/**
 * Dịch text UI tĩnh
 * @param string $key - Key dạng "nav.home" hoặc "booking.confirm"
 * @param array $params - Tham số thay thế {:name}
 * @return string
 */
function __($key, $params = []) {
    $translations = loadTranslations();
    
    // Hỗ trợ nested keys: "nav.home" => $translations['nav']['home']
    $keys = explode('.', $key);
    $value = $translations;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            // Key không tồn tại, trả về key gốc
            return $key;
        }
    }
    
    // Thay thế params
    if (!empty($params) && is_string($value)) {
        foreach ($params as $param => $val) {
            $value = str_replace('{:' . $param . '}', $val, $value);
        }
    }
    
    return $value;
}

/**
 * Echo dịch text
 */
function _e($key, $params = []) {
    echo __($key, $params);
}

/**
 * Lấy giá trị từ database theo ngôn ngữ
 * @param array $row - Row từ database
 * @param string $field - Tên field (VD: 'type_name')
 * @return string
 */
function getLocalizedField($row, $field) {
    $lang = getLang();
    
    if ($lang !== 'vi') {
        // Thử lấy field_en, field_ko, etc.
        $localized_field = $field . '_' . $lang;
        if (!empty($row[$localized_field])) {
            return $row[$localized_field];
        }
    }
    
    // Fallback về field gốc (tiếng Việt)
    return $row[$field] ?? '';
}

/**
 * Shorthand cho getLocalizedField
 */
function _f($row, $field) {
    return getLocalizedField($row, $field);
}

/**
 * Format tiền tệ theo ngôn ngữ
 */
function formatMoney($amount, $lang = null) {
    $lang = $lang ?? getLang();
    
    if ($lang === 'en') {
        // Convert VND to USD (tỷ giá ước tính)
        $usd = $amount / 24000;
        return '$' . number_format($usd, 0);
    }
    
    return number_format($amount, 0, ',', '.') . ' đ';
}

/**
 * Format ngày theo ngôn ngữ
 */
function formatDate($date, $format = null, $lang = null) {
    $lang = $lang ?? getLang();
    $timestamp = is_string($date) ? strtotime($date) : $date;
    
    if ($format) {
        return date($format, $timestamp);
    }
    
    if ($lang === 'en') {
        return date('M d, Y', $timestamp);
    }
    
    return date('d/m/Y', $timestamp);
}

/**
 * Tạo URL với ngôn ngữ
 */
function langUrl($url, $lang = null) {
    $lang = $lang ?? getLang();
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . 'lang=' . $lang;
}

/**
 * Kiểm tra ngôn ngữ hiện tại
 */
function isLang($lang) {
    return getLang() === $lang;
}

/**
 * HTML cho language switcher
 */
function renderLanguageSwitcher($class = '') {
    $current = getLang();
    $langs = [
        'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸'],
    ];
    
    $html = '<div class="lang-switcher ' . $class . '">';
    $html .= '<button class="lang-current" onclick="toggleLangMenu()">';
    $html .= $langs[$current]['flag'] . ' <span class="hidden md:inline">' . strtoupper($current) . '</span>';
    $html .= '<span class="material-symbols-outlined text-sm">expand_more</span>';
    $html .= '</button>';
    $html .= '<div class="lang-menu hidden">';
    
    foreach ($langs as $code => $info) {
        $active = $code === $current ? 'active' : '';
        $html .= '<a href="?lang=' . $code . '" class="lang-option ' . $active . '">';
        $html .= $info['flag'] . ' ' . $info['name'];
        if ($code === $current) {
            $html .= ' <span class="material-symbols-outlined text-sm">check</span>';
        }
        $html .= '</a>';
    }
    
    $html .= '</div></div>';
    
    return $html;
}
