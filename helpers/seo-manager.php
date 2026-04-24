<?php
/**
 * SEO Manager - Aurora Hotel Plaza
 * Comprehensive SEO System
 *
 * Features:
 * - Dynamic Meta Tags from Database
 * - Open Graph & Twitter Cards
 * - Structured Data (JSON-LD)
 * - Dynamic Sitemap Generation
 * - FAQ Schema
 * - Multilingual SEO (vi/en)
 * - SEO Admin Integration
 */

require_once __DIR__ . '/../config/database.php';

class SEOManager {

    private static $db = null;
    private static $settings = null;
    private static $currentPage = null;

    /**
     * Initialize SEO Manager
     */
    public static function init() {
        self::$db = getDB();
        self::loadSettings();
        self::$currentPage = self::detectCurrentPage();
    }

    /**
     * Load global SEO settings
     */
    private static function loadSettings() {
        try {
            $stmt = self::$db->query("SELECT setting_key, setting_value FROM seo_settings");
            self::$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            // Fallback defaults
            self::$settings = [
                'site_name' => 'Aurora Hotel Plaza',
                'site_tagline_vi' => 'Khách sạn sang trọng tại Biên Hòa',
                'site_tagline_en' => 'Luxury Hotel in Bien Hoa',
                'default_og_image' => '/assets/img/og-image.jpg',
                'twitter_handle' => '@aurorahotelplaza',
                'schema_star_rating' => '4',
                'schema_price_range' => '$$',
                'enable_structured_data' => 'true',
                'enable_hreflang' => 'true'
            ];
        }
    }

    /**
     * Get setting value
     */
    public static function getSetting($key, $default = '') {
        return self::$settings[$key] ?? $default;
    }

    /**
     * Detect current page slug
     */
    private static function detectCurrentPage() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/');
        $path = str_replace('.php', '', $path);

        // Map paths to page slugs
        $mappings = [
            '/' => 'index',
            '/index' => 'index',
            '/phong-khach-san' => 'phong-khach-san',
            '/rooms' => 'phong-khach-san',
            '/can-ho' => 'can-ho',
            '/apartments' => 'can-ho',
            '/dat-phong' => 'dat-phong',
            '/booking' => 'dat-phong',
            '/dich-vu' => 'dich-vu',
            '/services' => 'dich-vu',
            '/dich-vu/wedding-service' => 'wedding-service',
            '/dich-vu/conference-service' => 'conference-service',
            '/dich-vu/aurora-restaurant' => 'aurora-restaurant',
            '/dich-vu/office-rental' => 'office-rental',
            '/gioi-thieu' => 'gioi-thieu',
            '/about' => 'gioi-thieu',
            '/thu-vien-anh' => 'thu-vien-anh',
            '/gallery' => 'thu-vien-anh',
            '/tin-tuc' => 'tin-tuc',
            '/blog' => 'tin-tuc',
            '/lien-he' => 'lien-he',
            '/contact' => 'lien-he',
            '/kham-pha' => 'kham-pha',
            '/explore' => 'kham-pha',
            '/chinh-sach-huy' => 'chinh-sach-huy',
            '/cancellation-policy' => 'chinh-sach-huy',
            '/chinh-sach-bao-mat' => 'chinh-sach-bao-mat',
            '/privacy' => 'chinh-sach-bao-mat',
            '/dieu-khoan' => 'dieu-khoan',
            '/terms' => 'dieu-khoan'
        ];

        return $mappings[$path] ?? 'index';
    }

    /**
     * Get SEO data for current page
     */
    public static function getPageSEO($pageSlug = null) {
        $slug = $pageSlug ?? self::$currentPage;

        try {
            $stmt = self::$db->prepare("SELECT * FROM seo_pages WHERE page_slug = ? AND is_active = 1");
            $stmt->execute([$slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get current language
     */
    private static function getLang() {
        return isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'vi';
    }

    /**
     * Generate Full Meta Tags for current page
     */
    public static function generateMetaTags($customData = []) {
        self::init();

        $lang = self::getLang();
        $seoData = self::getPageSEO();
        $siteName = self::getSetting('site_name', 'Aurora Hotel Plaza');
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        // Determine title
        $title = $customData['title'] ?? ($seoData ? $seoData['meta_title_' . $lang] : self::getDefaultTitle($lang));
        $title = substr($title, 0, 70); // Max 70 chars

        // Determine description
        $description = $customData['description'] ?? ($seoData ? $seoData['meta_description_' . $lang] : self::getDefaultDescription($lang));
        $description = substr($description, 0, 160); // Max 160 chars

        // Determine keywords
        $keywords = $customData['keywords'] ?? ($seoData ? $seoData['meta_keywords_' . $lang] : self::getDefaultKeywords($lang));

        // Determine image
        $image = $customData['image'] ?? ($seoData ? $seoData['og_image'] : self::getSetting('default_og_image'));

        // Current URL
        $currentUrl = $baseUrl . $_SERVER['REQUEST_URI'];
        $canonicalUrl = $customData['canonical'] ?? ($seoData ? $seoData['canonical_url'] : $currentUrl);

        // Robots directive
        $robots = $seoData ? $seoData['robotsDirective'] : 'index, follow';

        // Build HTML
        $html = '';
        $html .= '<meta charset="UTF-8">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">' . "\n";
        $html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";

        // Title
        $html .= '<title>' . htmlspecialchars($title) . '</title>' . "\n";

        // Description
        $html .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";

        // Keywords
        if ($keywords) {
            $html .= '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
        }

        // Author
        $html .= '<meta name="author" content="' . htmlspecialchars($siteName) . '">' . "\n";

        // Robots
        $html .= '<meta name="robots" content="' . htmlspecialchars($robots) . '">' . "\n";

        // Canonical
        $html .= '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl) . '">' . "\n";

        // Open Graph
        $html .= self::generateOpenGraph($title, $description, $image, $currentUrl);

        // Twitter Card
        $html .= self::generateTwitterCard($title, $description, $image);

        // Hreflang (multilingual)
        if (self::getSetting('enable_hreflang', 'true') === 'true') {
            $html .= self::generateHreflang($canonicalUrl);
        }

        // Additional meta
        $html .= '<meta name="theme-color" content="#d4af37">' . "\n";
        $html .= '<meta name="msapplication-TileColor" content="#d4af37">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";

        // Favicon
        $html .= '<link rel="icon" type="image/png" sizes="32x32" href="' . $baseUrl . '/assets/img/favicon-32x32.png">' . "\n";
        $html .= '<link rel="icon" type="image/png" sizes="16x16" href="' . $baseUrl . '/assets/img/favicon-16x16.png">' . "\n";
        $html .= '<link rel="apple-touch-icon" sizes="180x180" href="' . $baseUrl . '/assets/img/apple-touch-icon.png">' . "\n";

        // Verification codes (if set)
        $googleVerify = self::getSetting('google_site_verification');
        if ($googleVerify) {
            $html .= '<meta name="google-site-verification" content="' . htmlspecialchars($googleVerify) . '">' . "\n";
        }

        return $html;
    }

    /**
     * Generate Open Graph Tags
     */
    private static function generateOpenGraph($title, $description, $image, $url) {
        $siteName = self::getSetting('site_name', 'Aurora Hotel Plaza');
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';
        $lang = self::getLang();

        $html = '';
        $html .= '<meta property="og:site_name" content="' . htmlspecialchars($siteName) . '">' . "\n";
        $html .= '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
        $html .= '<meta property="og:type" content="website">' . "\n";
        $html .= '<meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
        $html .= '<meta property="og:image" content="' . htmlspecialchars($baseUrl . $image) . '">' . "\n";
        $html .= '<meta property="og:image:width" content="1200">' . "\n";
        $html .= '<meta property="og:image:height" content="630">' . "\n";
        $html .= '<meta property="og:locale" content="' . ($lang === 'vi' ? 'vi_VN' : 'en_US') . '">' . "\n";
        $html .= '<meta property="og:locale:alternate" content="' . ($lang === 'vi' ? 'en_US' : 'vi_VN') . '">' . "\n";

        return $html;
    }

    /**
     * Generate Twitter Card Tags
     */
    private static function generateTwitterCard($title, $description, $image) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';
        $twitterHandle = self::getSetting('twitter_handle', '@aurorahotelplaza');

        $html = '';
        $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $html .= '<meta name="twitter:site" content="' . htmlspecialchars($twitterHandle) . '">' . "\n";
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
        $html .= '<meta name="twitter:image" content="' . htmlspecialchars($baseUrl . $image) . '">' . "\n";

        return $html;
    }

    /**
     * Generate Hreflang Tags
     */
    private static function generateHreflang($canonicalUrl) {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://aurorahotelplaza.com';
        $path = parse_url($canonicalUrl, PHP_URL_PATH) ?? '/';

        $html = '';
        $html .= '<link rel="alternate" hreflang="vi" href="' . $baseUrl . $path . '?lang=vi">' . "\n";
        $html .= '<link rel="alternate" hreflang="en" href="' . $baseUrl . $path . '?lang=en">' . "\n";
        $html .= '<link rel="alternate" hreflang="x-default" href="' . $baseUrl . $path . '">' . "\n";

        return $html;
    }

    /**
     * Generate Structured Data (JSON-LD)
     */
    public static function generateStructuredData($type = 'Hotel', $customData = []) {
        if (self::getSetting('enable_structured_data', 'true') !== 'true') {
            return '';
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        switch ($type) {
            case 'Hotel':
                return self::generateHotelSchema($customData);
            case 'Room':
                return self::generateRoomSchema($customData);
            case 'Apartment':
                return self::generateApartmentSchema($customData);
            case 'Service':
                return self::generateServiceSchema($customData);
            case 'BlogPosting':
                return self::generateBlogSchema($customData);
            case 'FAQ':
                return self::generateFAQSchema($customData['page_slug'] ?? self::$currentPage);
            case 'LocalBusiness':
                return self::generateLocalBusinessSchema($customData);
            case 'Organization':
                return self::generateOrganizationSchema($customData);
            case 'BreadcrumbList':
                return self::generateBreadcrumbSchema($customData);
            default:
                return self::generateHotelSchema($customData);
        }
    }

    /**
     * Generate Hotel Schema
     */
    private static function generateHotelSchema($customData = []) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';
        $starRating = self::getSetting('schema_star_rating', '4');
        $priceRange = self::getSetting('schema_price_range', '$$');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Hotel',
            'name' => 'Aurora Hotel Plaza',
            'description' => 'Khách sạn 4 sao sang trọng tại trung tâm Biên Hòa, Đồng Nai với hơn 200 phòng nghỉ và căn hộ cao cấp.',
            'image' => [
                $baseUrl . '/assets/img/hotel-exterior.jpg',
                $baseUrl . '/assets/img/lobby.jpg',
                $baseUrl . '/assets/img/room-deluxe.jpg'
            ],
            'url' => $baseUrl,
            'telephone' => '+84-251-3918-888',
            'email' => 'info@aurorahotelplaza.com',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '253, Phạm Văn Thuận, KP2',
                'addressLocality' => 'Biên Hòa',
                'addressRegion' => 'Đồng Nai',
                'postalCode' => '810000',
                'addressCountry' => 'VN'
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '10.9510',
                'longitude' => '106.8340'
            ],
            'starRating' => [
                '@type' => 'Rating',
                'ratingValue' => $starRating
            ],
            'priceRange' => $priceRange,
            'numberOfRooms' => '200',
            'checkinTime' => '14:00',
            'checkoutTime' => '12:00',
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Free WiFi', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Swimming Pool', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Fitness Center', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Restaurant', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Spa', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Conference Room', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Free Parking', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => '24-hour Front Desk', 'value' => true]
            ],
            'sameAs' => [
                'https://www.facebook.com/aurorahotelplaza',
                'https://www.instagram.com/aurorahotelplaza'
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.5',
                'reviewCount' => '500',
                'bestRating' => '5',
                'worstRating' => '1'
            ]
        ];

        // Merge custom data
        if ($customData) {
            $data = array_merge($data, $customData);
        }

        return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate Room Schema
     */
    private static function generateRoomSchema($roomData) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'HotelRoom',
            'name' => $roomData['name'] ?? 'Deluxe Room',
            'description' => $roomData['description'] ?? 'Phòng Deluxe với đầy đủ tiện nghi',
            'image' => $baseUrl . ($roomData['image'] ?? '/assets/img/room-deluxe.jpg'),
            'url' => $baseUrl . ($roomData['url'] ?? '/phong/deluxe'),
            'floorSize' => [
                '@type' => 'QuantitativeValue',
                'value' => $roomData['size'] ?? 32,
                'unitText' => 'm²'
            ],
            'occupancy' => [
                '@type' => 'QuantitativeValue',
                'value' => $roomData['max_guests'] ?? 2
            ],
            'bed' => [
                '@type' => 'BedDetails',
                'numberOfBeds' => 1,
                'typeOfBed' => $roomData['bed_type'] ?? 'King'
            ],
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Air Conditioning', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'WiFi', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'TV', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Mini Bar', 'value' => true]
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $roomData['price'] ?? '1600000',
                'priceCurrency' => 'VND',
                'availability' => 'https://schema.org/InStock',
                'validFrom' => date('Y-m-d'),
                'validThrough' => '2030-12-31'
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate Apartment Schema
     */
    private static function generateApartmentSchema($aptData) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Apartment',
            'name' => $aptData['name'] ?? 'Studio Apartment',
            'description' => $aptData['description'] ?? 'Căn hộ Studio với đầy đủ tiện nghi',
            'image' => $baseUrl . ($aptData['image'] ?? '/assets/img/apartment-studio.jpg'),
            'url' => $baseUrl . ($aptData['url'] ?? '/can-ho/studio'),
            'floorSize' => [
                '@type' => 'QuantitativeValue',
                'value' => $aptData['size'] ?? 45,
                'unitText' => 'm²'
            ],
            'numberOfRooms' => $aptData['rooms'] ?? 1,
            'numberOfBedrooms' => $aptData['bedrooms'] ?? 1,
            'numberOfBathrooms' => $aptData['bathrooms'] ?? 1,
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Full Kitchen', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Washing Machine', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Air Conditioning', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'WiFi', 'value' => true]
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $aptData['price'] ?? '800000',
                'priceCurrency' => 'VND',
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'price' => $aptData['price_daily'] ?? '800000',
                    'priceCurrency' => 'VND',
                    'description' => 'Giá theo ngày'
                ]
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate Service Schema
     */
    private static function generateServiceSchema($serviceData) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $serviceData['name'] ?? 'Wedding Service',
            'description' => $serviceData['description'] ?? 'Dịch vụ tiệc cưới chuyên nghiệp',
            'image' => $baseUrl . ($serviceData['image'] ?? '/assets/img/wedding.jpg'),
            'url' => $baseUrl . ($serviceData['url'] ?? '/dich-vu/wedding-service'),
            'provider' => [
                '@type' => 'Hotel',
                'name' => 'Aurora Hotel Plaza',
                'url' => $baseUrl
            ],
            'areaServed' => [
                '@type' => 'City',
                'name' => 'Biên Hòa'
            ],
            'offers' => [
                '@type' => 'Offer',
                'availability' => 'https://schema.org/InStock'
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate Blog Posting Schema
     */
    private static function generateBlogSchema($blogData) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $blogData['title'] ?? 'Blog Post',
            'description' => $blogData['description'] ?? '',
            'image' => $baseUrl . ($blogData['image'] ?? '/assets/img/blog-default.jpg'),
            'url' => $baseUrl . ($blogData['url'] ?? '/tin-tuc'),
            'datePublished' => $blogData['date_published'] ?? date('Y-m-d'),
            'dateModified' => $blogData['date_modified'] ?? date('Y-m-d'),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Aurora Hotel Plaza',
                'url' => $baseUrl
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Aurora Hotel Plaza',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $baseUrl . '/assets/img/logo.png'
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $baseUrl . ($blogData['url'] ?? '/tin-tuc')
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate FAQ Schema
     */
    private static function generateFAQSchema($pageSlug) {
        try {
            $stmt = self::$db->prepare("SELECT * FROM seo_faqs WHERE page_slug = ? AND is_active = 1 ORDER BY display_order ASC");
            $stmt->execute([$pageSlug]);
            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($faqs)) {
                return '';
            }

            $lang = self::getLang();
            $items = [];

            foreach ($faqs as $faq) {
                $question = $lang === 'en' ? $faq['question_en'] : $faq['question_vi'];
                $answer = $lang === 'en' ? $faq['answer_en'] : $faq['answer_vi'];

                $items[] = [
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answer
                    ]
                ];
            }

            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $items
            ];

            return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Generate Local Business Schema
     */
    private static function generateLocalBusinessSchema($customData = []) {
        return self::generateHotelSchema($customData); // Hotel is a type of LocalBusiness
    }

    /**
     * Generate Organization Schema
     */
    private static function generateOrganizationSchema($customData = []) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Aurora Hotel Plaza',
            'url' => $baseUrl,
            'logo' => $baseUrl . '/assets/img/logo.png',
            'description' => 'Khách sạn 4 sao sang trọng tại Biên Hòa, Đồng Nai',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '253, Phạm Văn Thuận, KP2',
                'addressLocality' => 'Biên Hòa',
                'addressRegion' => 'Đồng Nai',
                'postalCode' => '810000',
                'addressCountry' => 'VN'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+84-251-3918-888',
                'contactType' => 'customer service',
                'availableLanguage' => ['Vietnamese', 'English']
            ],
            'sameAs' => [
                'https://www.facebook.com/aurorahotelplaza',
                'https://www.instagram.com/aurorahotelplaza'
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate Breadcrumb Schema
     */
    private static function generateBreadcrumbSchema($breadcrumbs) {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        $items = [];
        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $baseUrl . $crumb['url']
            ];
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];

        return '<script type="application/ld+json">' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate Dynamic Sitemap
     */
    public static function generateSitemap() {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://aurorahotelplaza.com';
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n\n";

        // Main pages from seo_pages
        try {
            $stmt = self::$db->query("SELECT * FROM seo_pages WHERE is_active = 1 ORDER BY priority DESC");
            $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pages as $page) {
                $lastmod = $page['last_modified'] ? date('Y-m-d', strtotime($page['last_modified'])) : date('Y-m-d');
                $urlPath = self::getSlugToUrl($page['page_slug']);

                $xml .= "  <url>\n";
                $xml .= "    <loc>{$baseUrl}{$urlPath}</loc>\n";
                $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
                $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
                $xml .= "    <priority>{$page['priority']}</priority>\n";

                // Hreflang
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"vi\" href=\"{$baseUrl}{$urlPath}?lang=vi\"/>\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"{$baseUrl}{$urlPath}?lang=en\"/>\n";

                $xml .= "  </url>\n\n";
            }

            // Blog posts
            $stmt = self::$db->query("SELECT post_id, slug, updated_at FROM blog_posts WHERE status = 'published'");
            $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($blogs as $blog) {
                $lastmod = $blog['updated_at'] ? date('Y-m-d', strtotime($blog['updated_at'])) : date('Y-m-d');
                $xml .= "  <url>\n";
                $xml .= "    <loc>{$baseUrl}/tin-tuc/{$blog['slug']}</loc>\n";
                $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
                $xml .= "    <changefreq>monthly</changefreq>\n";
                $xml .= "    <priority>0.6</priority>\n";
                $xml .= "  </url>\n\n";
            }

            // Room types
            $stmt = self::$db->query("SELECT type_id, slug FROM room_types WHERE status = 'active'");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rooms as $room) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>{$baseUrl}/phong/{$room['slug']}</loc>\n";
                $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
                $xml .= "    <changefreq>monthly</changefreq>\n";
                $xml .= "    <priority>0.8</priority>\n";
                $xml .= "  </url>\n\n";
            }

            // Apartment types
            $stmt = self::$db->query("SELECT type_id, slug FROM room_types WHERE status = 'active' AND type_category = 'apartment'");
            $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($apartments as $apt) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>{$baseUrl}/can-ho/{$apt['slug']}</loc>\n";
                $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
                $xml .= "    <changefreq>monthly</changefreq>\n";
                $xml .= "    <priority>0.7</priority>\n";
                $xml .= "  </url>\n\n";
            }

        } catch (Exception $e) {
            // Fallback static URLs
        }

        $xml .= "</urlset>";

        return $xml;
    }

    /**
     * Save Sitemap to file
     */
    public static function saveSitemap() {
        try {
            $sitemapContent = self::generateSitemap();
            $sitemapPath = dirname(__DIR__) . '/sitemap.xml';

            file_put_contents($sitemapPath, $sitemapContent);

            // Try to update setting (ignore if fails)
            try {
                self::$db->prepare("UPDATE seo_settings SET setting_value = ? WHERE setting_key = 'sitemap_last_generated'")
                    ->execute([date('Y-m-d H:i:s')]);
            } catch (Exception $e) {
                // Ignore - setting might not exist
            }

            return true;
        } catch (Exception $e) {
            error_log("SEOManager::saveSitemap error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert slug to URL path
     */
    private static function getSlugToUrl($slug) {
        $mappings = [
            'index' => '/',
            'phong-khach-san' => '/phong-khach-san',
            'can-ho' => '/can-ho',
            'dat-phong' => '/dat-phong',
            'dich-vu' => '/dich-vu',
            'wedding-service' => '/dich-vu/wedding-service',
            'conference-service' => '/dich-vu/conference-service',
            'aurora-restaurant' => '/dich-vu/aurora-restaurant',
            'office-rental' => '/dich-vu/office-rental',
            'gioi-thieu' => '/gioi-thieu',
            'thu-vien-anh' => '/thu-vien-anh',
            'tin-tuc' => '/tin-tuc',
            'lien-he' => '/lien-he',
            'kham-pha' => '/kham-pha',
            'chinh-sach-huy' => '/chinh-sach-huy',
            'chinh-sach-bao-mat' => '/chinh-sach-bao-mat',
            'dieu-khoan' => '/dieu-khoan'
        ];

        return $mappings[$slug] ?? '/' . $slug;
    }

    /**
     * Default SEO values
     */
    private static function getDefaultTitle($lang) {
        $siteName = self::getSetting('site_name', 'Aurora Hotel Plaza');
        $tagline = self::getSetting('site_tagline_' . $lang, $lang === 'vi' ? 'Khách sạn sang trọng tại Biên Hòa' : 'Luxury Hotel in Bien Hoa');
        return $siteName . ' - ' . $tagline;
    }

    private static function getDefaultDescription($lang) {
        return $lang === 'vi'
            ? 'Khách sạn Aurora Hotel Plaza 4 sao hàng đầu tại Biên Hòa, Đồng Nai. Phòng nghỉ cao cấp, căn hộ Indochine, tiệc cưới, hội nghị.'
            : 'Aurora Hotel Plaza - Premier 4-star hotel in Bien Hoa, Dong Nai. Luxury rooms, Indochine apartments, wedding venue, conference center.';
    }

    private static function getDefaultKeywords($lang) {
        return $lang === 'vi'
            ? 'khách sạn biên hòa, aurora hotel plaza, khách sạn 4 sao, khách sạn đồng nai, đặt phòng'
            : 'hotel bien hoa, aurora hotel plaza, 4 star hotel, dong nai hotel, booking';
    }

    /**
     * Render Full SEO HTML for a page
     */
    public static function render($customData = [], $schemaType = 'Hotel') {
        $html = self::generateMetaTags($customData);

        if ($schemaType) {
            $html .= "\n" . self::generateStructuredData($schemaType, $customData);
        }

        // Add FAQ schema if exists for this page
        $pageSlug = $customData['page_slug'] ?? self::$currentPage;
        $faqSchema = self::generateFAQSchema($pageSlug);
        if ($faqSchema) {
            $html .= "\n" . $faqSchema;
        }

        return $html;
    }

    /**
     * Quick render for admin pages
     */
    public static function renderAdmin($title, $description = '') {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'https://aurorahotelplaza.com';

        $html = '<meta charset="UTF-8">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '<title>' . htmlspecialchars($title) . ' | Aurora Admin</title>' . "\n";
        if ($description) {
            $html .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
        }
        $html .= '<meta name="robots" content="noindex, nofollow">' . "\n";
        $html .= '<link rel="icon" href="' . $baseUrl . '/assets/img/favicon.png">' . "\n";

        return $html;
    }
}

// Auto-initialize on load
SEOManager::init();