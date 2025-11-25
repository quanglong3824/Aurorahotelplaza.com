<?php
/**
 * Performance Optimization Configuration
 * Tối ưu hóa tốc độ tải trang web
 */

// 1. Enable Output Buffering
if (!ob_get_level()) {
    ob_start();
}

// 2. Set proper headers for caching
header('Cache-Control: public, max-age=3600');
header('Pragma: cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// 3. Enable Gzip compression
if (!headers_sent() && extension_loaded('zlib')) {
    ini_set('zlib.output_compression', 1);
    ini_set('zlib.output_compression_level', 6);
}

// 4. Optimize database queries
define('QUERY_CACHE_ENABLED', true);
define('QUERY_CACHE_TTL', 3600); // 1 hour

// 5. Asset versioning for cache busting
function assetVersion($path) {
    $filePath = __DIR__ . '/../' . $path;
    if (file_exists($filePath)) {
        $mtime = filemtime($filePath);
        return asset($path) . '?v=' . $mtime;
    }
    return asset($path);
}

// 6. Lazy loading for images
function lazyImage($src, $alt = '', $class = '') {
    return sprintf(
        '<img src="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1 1%22%3E%3C/svg%3E" data-src="%s" alt="%s" class="lazy %s" loading="lazy">',
        htmlspecialchars($src),
        htmlspecialchars($alt),
        htmlspecialchars($class)
    );
}

// 7. Minify CSS inline
function minifyCSS($css) {
    $css = preg_replace('!/\*[^*]*\*+(?:[^/*][^*]*\*+)*/!', '', $css);
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  '], '', $css);
    $css = str_replace([' {', '{ ', ' }', '} ', ' :', ': ', ' ,', ', '], ['{', '{', '}', '}', ':', ':', ',', ','], $css);
    return trim($css);
}

// 8. Minify JavaScript inline
function minifyJS($js) {
    $js = preg_replace('!/\*[^*]*\*+(?:[^/*][^*]*\*+)*/!', '', $js);
    $js = preg_replace('!//.*?[\r\n]!', '', $js);
    $js = str_replace(["\r\n", "\r", "\n", "\t"], '', $js);
    return trim($js);
}

// 9. Image optimization helper
function optimizedImage($src, $alt = '', $sizes = '100vw', $class = '') {
    $webpSrc = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $src);
    
    return sprintf(
        '<picture>
            <source srcset="%s" type="image/webp" sizes="%s">
            <img src="%s" alt="%s" class="%s" loading="lazy">
        </picture>',
        htmlspecialchars($webpSrc),
        htmlspecialchars($sizes),
        htmlspecialchars($src),
        htmlspecialchars($alt),
        htmlspecialchars($class)
    );
}

// 10. Query result caching
class QueryCache {
    private static $cache = [];
    private static $ttl = QUERY_CACHE_TTL;
    
    public static function get($key) {
        if (isset(self::$cache[$key])) {
            $item = self::$cache[$key];
            if (time() - $item['time'] < self::$ttl) {
                return $item['data'];
            }
            unset(self::$cache[$key]);
        }
        return null;
    }
    
    public static function set($key, $data) {
        self::$cache[$key] = [
            'data' => $data,
            'time' => time()
        ];
    }
    
    public static function clear() {
        self::$cache = [];
    }
}

// 11. Performance monitoring
class PerformanceMonitor {
    private static $startTime;
    private static $marks = [];
    
    public static function start() {
        self::$startTime = microtime(true);
    }
    
    public static function mark($name) {
        self::$marks[$name] = microtime(true) - self::$startTime;
    }
    
    public static function getTime($name = null) {
        if ($name) {
            return self::$marks[$name] ?? 0;
        }
        return microtime(true) - self::$startTime;
    }
    
    public static function getMarks() {
        return self::$marks;
    }
}

// Start performance monitoring
PerformanceMonitor::start();

// 12. Defer non-critical CSS
function deferCSS($href) {
    return sprintf(
        '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">
        <noscript><link rel="stylesheet" href="%s"></noscript>',
        htmlspecialchars($href),
        htmlspecialchars($href)
    );
}

// 13. Async JavaScript loading
function asyncScript($src) {
    return sprintf('<script src="%s" async></script>', htmlspecialchars($src));
}

// 14. Defer JavaScript loading
function deferScript($src) {
    return sprintf('<script src="%s" defer></script>', htmlspecialchars($src));
}

// 15. Preload critical resources
function preloadResource($href, $as = 'script', $type = '') {
    $link = sprintf('<link rel="preload" href="%s" as="%s"', htmlspecialchars($href), htmlspecialchars($as));
    if ($type) {
        $link .= sprintf(' type="%s"', htmlspecialchars($type));
    }
    $link .= '>';
    return $link;
}

// 16. DNS prefetch for external resources
function dnsPrefetch($domain) {
    return sprintf('<link rel="dns-prefetch" href="%s">', htmlspecialchars($domain));
}

// 17. Preconnect to external resources
function preconnect($domain, $crossorigin = false) {
    $link = sprintf('<link rel="preconnect" href="%s"', htmlspecialchars($domain));
    if ($crossorigin) {
        $link .= ' crossorigin';
    }
    $link .= '>';
    return $link;
}

// 18. Critical CSS inline
function inlineCSS($path) {
    $filePath = __DIR__ . '/../' . $path;
    if (file_exists($filePath)) {
        $css = file_get_contents($filePath);
        return '<style>' . minifyCSS($css) . '</style>';
    }
    return '';
}

// 19. Critical JS inline
function inlineJS($path) {
    $filePath = __DIR__ . '/../' . $path;
    if (file_exists($filePath)) {
        $js = file_get_contents($filePath);
        return '<script>' . minifyJS($js) . '</script>';
    }
    return '';
}

// 20. Responsive image helper
function responsiveImage($src, $alt = '', $sizes = []) {
    $srcset = [];
    foreach ($sizes as $width => $path) {
        $srcset[] = "$path {$width}w";
    }
    
    return sprintf(
        '<img src="%s" alt="%s" srcset="%s" sizes="(max-width: 768px) 100vw, 50vw" loading="lazy">',
        htmlspecialchars($src),
        htmlspecialchars($alt),
        implode(', ', $srcset)
    );
}

// 21. Flush output buffer periodically
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// 22. Performance header
function addPerformanceHeader() {
    $time = PerformanceMonitor::getTime();
    header('X-Page-Load-Time: ' . round($time * 1000) . 'ms');
}

// Add performance header on shutdown
register_shutdown_function('addPerformanceHeader');
