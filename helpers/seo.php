<?php
/**
 * SEO Helper Class
 * Aurora Hotel Plaza
 * 
 * Features:
 * - Meta tags generation
 * - Open Graph tags
 * - Twitter Card tags
 * - Structured Data (JSON-LD)
 * - Canonical URLs
 * - Breadcrumbs
 */

class SEO {
    
    private static $site_name = 'Aurora Hotel Plaza';
    private static $site_url = 'https://aurorahotelplaza.com';
    private static $default_image = '/assets/img/og-image.jpg';
    private static $twitter_handle = '@aurorahotelplaza';
    
    /**
     * Generate Meta Tags
     * @param array $data
     * @return string
     */
    public static function generateMetaTags($data = []) {
        $defaults = [
            'title' => 'Aurora Hotel Plaza - Khách sạn sang trọng tại Biên Hòa',
            'description' => 'Aurora Hotel Plaza - Khách sạn 4 sao sang trọng tại trung tâm Biên Hòa. Phòng đẹp, dịch vụ chuyên nghiệp, tiện nghi hiện đại.',
            'keywords' => 'khách sạn biên hòa, aurora hotel plaza, khách sạn 4 sao, đặt phòng khách sạn, khách sạn đồng nai',
            'image' => self::$default_image,
            'url' => self::getCurrentURL(),
            'type' => 'website',
            'locale' => 'vi_VN',
            'author' => 'Aurora Hotel Plaza',
            'robots' => 'index, follow',
            'canonical' => self::getCurrentURL()
        ];
        
        $meta = array_merge($defaults, $data);
        
        $html = '';
        
        // Basic Meta Tags
        $html .= '<meta charset="UTF-8">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">' . "\n";
        $html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
        
        // SEO Meta Tags
        $html .= '<title>' . htmlspecialchars($meta['title']) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
        $html .= '<meta name="keywords" content="' . htmlspecialchars($meta['keywords']) . '">' . "\n";
        $html .= '<meta name="author" content="' . htmlspecialchars($meta['author']) . '">' . "\n";
        $html .= '<meta name="robots" content="' . htmlspecialchars($meta['robots']) . '">' . "\n";
        
        // Canonical URL
        $html .= '<link rel="canonical" href="' . htmlspecialchars($meta['canonical']) . '">' . "\n";
        
        // Open Graph Tags
        $html .= self::generateOpenGraphTags($meta);
        
        // Twitter Card Tags
        $html .= self::generateTwitterCardTags($meta);
        
        // Additional SEO Tags
        $html .= '<meta name="theme-color" content="#1A237E">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
        
        // Favicon
        $html .= '<link rel="icon" type="image/png" href="/assets/img/favicon.png">' . "\n";
        $html .= '<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">' . "\n";
        
        return $html;
    }
    
    /**
     * Generate Open Graph Tags
     * @param array $meta
     * @return string
     */
    private static function generateOpenGraphTags($meta) {
        $html = '';
        
        $html .= '<meta property="og:site_name" content="' . htmlspecialchars(self::$site_name) . '">' . "\n";
        $html .= '<meta property="og:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
        $html .= '<meta property="og:type" content="' . htmlspecialchars($meta['type']) . '">' . "\n";
        $html .= '<meta property="og:url" content="' . htmlspecialchars($meta['url']) . '">' . "\n";
        $html .= '<meta property="og:image" content="' . htmlspecialchars(self::$site_url . $meta['image']) . '">' . "\n";
        $html .= '<meta property="og:image:width" content="1200">' . "\n";
        $html .= '<meta property="og:image:height" content="630">' . "\n";
        $html .= '<meta property="og:locale" content="' . htmlspecialchars($meta['locale']) . '">' . "\n";
        
        return $html;
    }
    
    /**
     * Generate Twitter Card Tags
     * @param array $meta
     * @return string
     */
    private static function generateTwitterCardTags($meta) {
        $html = '';
        
        $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $html .= '<meta name="twitter:site" content="' . htmlspecialchars(self::$twitter_handle) . '">' . "\n";
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
        $html .= '<meta name="twitter:image" content="' . htmlspecialchars(self::$site_url . $meta['image']) . '">' . "\n";
        
        return $html;
    }
    
    /**
     * Generate Structured Data (JSON-LD) for Hotel
     * @return string
     */
    public static function generateHotelStructuredData() {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Hotel',
            'name' => 'Aurora Hotel Plaza',
            'description' => 'Khách sạn 4 sao sang trọng tại trung tâm Biên Hòa, Đồng Nai',
            'image' => self::$site_url . '/assets/img/hotel-exterior.jpg',
            'url' => self::$site_url,
            'telephone' => '+84-251-3836-888',
            'email' => 'info@aurorahotelplaza.com',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '123 Phạm Văn Thuận',
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
                'ratingValue' => '4'
            ],
            'priceRange' => '$$',
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Free WiFi'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Swimming Pool'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Fitness Center'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Restaurant'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Spa'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Conference Room'],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Parking']
            ],
            'checkinTime' => '14:00',
            'checkoutTime' => '12:00',
            'numberOfRooms' => '100',
            'sameAs' => [
                'https://www.facebook.com/aurorahotelplaza',
                'https://www.instagram.com/aurorahotelplaza',
                'https://www.youtube.com/aurorahotelplaza'
            ]
        ];
        
        return '<script type="application/ld+json">' . "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n" . '</script>' . "\n";
    }
    
    /**
     * Generate Structured Data for Room
     * @param array $room
     * @return string
     */
    public static function generateRoomStructuredData($room) {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'HotelRoom',
            'name' => $room['name'],
            'description' => $room['description'],
            'image' => self::$site_url . $room['image'],
            'bed' => [
                '@type' => 'BedDetails',
                'typeOfBed' => $room['bed_type'] ?? 'King'
            ],
            'occupancy' => [
                '@type' => 'QuantitativeValue',
                'value' => $room['max_guests'] ?? 2
            ],
            'amenityFeature' => array_map(function($amenity) {
                return ['@type' => 'LocationFeatureSpecification', 'name' => $amenity];
            }, $room['amenities'] ?? []),
            'offers' => [
                '@type' => 'Offer',
                'price' => $room['price'],
                'priceCurrency' => 'VND',
                'availability' => 'https://schema.org/InStock',
                'url' => self::$site_url . $room['url']
            ]
        ];
        
        return '<script type="application/ld+json">' . "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n" . '</script>' . "\n";
    }
    
    /**
     * Generate Breadcrumb Structured Data
     * @param array $breadcrumbs
     * @return string
     */
    public static function generateBreadcrumbStructuredData($breadcrumbs) {
        $items = [];
        
        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => self::$site_url . $crumb['url']
            ];
        }
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
        
        return '<script type="application/ld+json">' . "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n" . '</script>' . "\n";
    }
    
    /**
     * Generate Review Structured Data
     * @param array $review
     * @return string
     */
    public static function generateReviewStructuredData($review) {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Hotel',
                'name' => 'Aurora Hotel Plaza'
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $review['author']
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $review['rating'],
                'bestRating' => '5'
            ],
            'reviewBody' => $review['content'],
            'datePublished' => $review['date']
        ];
        
        return '<script type="application/ld+json">' . "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n" . '</script>' . "\n";
    }
    
    /**
     * Generate Organization Structured Data
     * @return string
     */
    public static function generateOrganizationStructuredData() {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Aurora Hotel Plaza',
            'url' => self::$site_url,
            'logo' => self::$site_url . '/assets/img/logo.png',
            'description' => 'Khách sạn 4 sao sang trọng tại Biên Hòa',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '123 Phạm Văn Thuận',
                'addressLocality' => 'Biên Hòa',
                'addressRegion' => 'Đồng Nai',
                'postalCode' => '810000',
                'addressCountry' => 'VN'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+84-251-3836-888',
                'contactType' => 'customer service',
                'email' => 'info@aurorahotelplaza.com',
                'availableLanguage' => ['Vietnamese', 'English']
            ],
            'sameAs' => [
                'https://www.facebook.com/aurorahotelplaza',
                'https://www.instagram.com/aurorahotelplaza',
                'https://www.youtube.com/aurorahotelplaza'
            ]
        ];
        
        return '<script type="application/ld+json">' . "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n" . '</script>' . "\n";
    }
    
    /**
     * Get Current URL
     * @return string
     */
    private static function getCurrentURL() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Generate Breadcrumb HTML
     * @param array $breadcrumbs
     * @return string
     */
    public static function generateBreadcrumbHTML($breadcrumbs) {
        $html = '<nav aria-label="Breadcrumb" class="breadcrumb">' . "\n";
        $html .= '<ol class="breadcrumb-list">' . "\n";
        
        foreach ($breadcrumbs as $index => $crumb) {
            $isLast = $index === count($breadcrumbs) - 1;
            
            $html .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '">' . "\n";
            
            if ($isLast) {
                $html .= '<span>' . htmlspecialchars($crumb['name']) . '</span>' . "\n";
            } else {
                $html .= '<a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['name']) . '</a>' . "\n";
            }
            
            $html .= '</li>' . "\n";
        }
        
        $html .= '</ol>' . "\n";
        $html .= '</nav>' . "\n";
        
        return $html;
    }
    
    /**
     * Generate Hreflang Tags for Multi-language
     * @param array $languages
     * @return string
     */
    public static function generateHreflangTags($languages) {
        $html = '';
        
        foreach ($languages as $lang => $url) {
            $html .= '<link rel="alternate" hreflang="' . htmlspecialchars($lang) . '" href="' . htmlspecialchars($url) . '">' . "\n";
        }
        
        return $html;
    }
}
