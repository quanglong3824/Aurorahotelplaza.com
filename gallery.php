<?php
require_once 'config/database.php';
require_once 'helpers/language.php';
initLanguage();

$images_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$current_category = isset($_GET['category']) ? $_GET['category'] : 'all';

$all_images = [];
$category_counts = [];

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT gallery_id, title, image_url as src, category FROM gallery WHERE status = 'active' ORDER BY sort_order ASC, gallery_id ASC");
    $stmt->execute();
    $all_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT category, COUNT(*) as count FROM gallery WHERE status = 'active' GROUP BY category");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $category_counts[$row['category']] = $row['count'];
    }
} catch (Exception $e) {
    $all_images = [];
}

$filtered_images = $current_category === 'all' ? $all_images : array_values(array_filter($all_images, fn($img) => $img['category'] === $current_category));
$total_images = count($filtered_images);
$total_pages = max(1, ceil($total_images / $images_per_page));
$current_page = max(1, min($current_page, $total_pages));
$page_images = array_slice($filtered_images, ($current_page - 1) * $images_per_page, $images_per_page);

$categories = [
    'all' => ['name' => __('gallery_page.all'), 'icon' => 'apps', 'count' => count($all_images)],
    'rooms' => ['name' => __('gallery_page.rooms'), 'icon' => 'hotel', 'count' => $category_counts['rooms'] ?? 0],
    'apartments' => ['name' => __('gallery_page.apartments'), 'icon' => 'apartment', 'count' => $category_counts['apartments'] ?? 0],
    'restaurant' => ['name' => __('gallery_page.restaurant'), 'icon' => 'restaurant', 'count' => $category_counts['restaurant'] ?? 0],
    'facilities' => ['name' => __('gallery_page.facilities'), 'icon' => 'fitness_center', 'count' => $category_counts['facilities'] ?? 0],
    'events' => ['name' => __('gallery_page.events'), 'icon' => 'celebration', 'count' => $category_counts['events'] ?? 0],
    'exterior' => ['name' => __('gallery_page.exterior'), 'icon' => 'location_city', 'count' => $category_counts['exterior'] ?? 0],
];
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('gallery_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
/* ========== GALLERY PAGE - CREATIVE GLASS STYLE ========== */

/* Hero Section */
.gallery-hero {
    position: relative;
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: linear-gradient(135deg, #1A237E 0%, #0f172a 60%, #d4af37 150%);
}

.gallery-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url('assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg');
    background-size: cover;
    background-position: center;
    opacity: 0.15;
}

.gallery-hero::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 150px;
    background: linear-gradient(to top, var(--background-light, #f8fafc), transparent);
    z-index: 2;
}

.dark .gallery-hero::after {
    background: linear-gradient(to top, var(--background-dark, #0f172a), transparent);
}

/* Floating Shapes */
.hero-shape {
    position: absolute;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(212, 175, 55, 0.2);
}

.hero-shape-1 { width: 400px; height: 400px; top: -100px; right: -100px; }
.hero-shape-2 { width: 250px; height: 250px; bottom: 10%; left: -50px; }
.hero-shape-3 { width: 150px; height: 150px; top: 40%; left: 20%; }

/* Hero Content */
.hero-glass-content {
    position: relative;
    z-index: 10;
    text-align: center;
    padding: 2rem;
}

.hero-icon-ring {
    width: 6rem;
    height: 6rem;
    margin: 0 auto 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(212, 175, 55, 0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse-ring 2s ease-in-out infinite;
}

@keyframes pulse-ring {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 30px 10px rgba(212, 175, 55, 0.2); }
}

.hero-icon-ring .material-symbols-outlined {
    font-size: 2.5rem;
    color: #d4af37;
}

.gallery-hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 3.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
    text-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
}

.gallery-hero-subtitle {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.85);
    max-width: 600px;
    margin: 0 auto;
}

/* Category Filter */
.filter-glass-bar {
    position: relative;
    z-index: 20;
    margin-top: -3rem;
    padding: 0 1rem;
}

.filter-glass-container {
    max-width: 1200px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 1.5rem;
    padding: 1.5rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
}

.dark .filter-glass-container {
    background: rgba(30, 41, 59, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.filter-scroll {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    scrollbar-width: thin;
    scrollbar-color: #d4af37 transparent;
}

.filter-scroll::-webkit-scrollbar { height: 4px; }
.filter-scroll::-webkit-scrollbar-track { background: transparent; }
.filter-scroll::-webkit-scrollbar-thumb { background: #d4af37; border-radius: 2px; }

.filter-btn-glass {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 3rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary-light);
    white-space: nowrap;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
}

.dark .filter-btn-glass {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--text-secondary-dark);
}

.filter-btn-glass:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: rgba(212, 175, 55, 0.3);
    color: #d4af37;
    transform: translateY(-2px);
}

.filter-btn-glass.active {
    background: linear-gradient(135deg, #1A237E, #d4af37);
    border-color: transparent;
    color: white;
    box-shadow: 0 8px 25px rgba(26, 35, 126, 0.3);
}

.filter-btn-glass .material-symbols-outlined {
    font-size: 1.125rem;
}

.filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
    padding: 0 0.375rem;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
}

.filter-btn-glass.active .filter-count {
    background: rgba(255, 255, 255, 0.25);
}
</style>
<style>
/* Gallery Grid - Masonry Style */
.gallery-section {
    padding: 4rem 0 6rem;
    background: var(--background-light);
}

.dark .gallery-section {
    background: var(--background-dark);
}

.gallery-masonry {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Image Card */
.gallery-card-glass {
    position: relative;
    border-radius: 1.25rem;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.dark .gallery-card-glass {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(255, 255, 255, 0.1);
}

/* Varying heights for masonry effect */
.gallery-card-glass:nth-child(4n+1) { grid-row: span 2; }
.gallery-card-glass:nth-child(4n+2) { grid-row: span 1; }
.gallery-card-glass:nth-child(4n+3) { grid-row: span 1; }
.gallery-card-glass:nth-child(4n) { grid-row: span 2; }

.gallery-card-glass:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
    z-index: 10;
}

.gallery-card-glass img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.gallery-card-glass:hover img {
    transform: scale(1.1);
}

/* Overlay */
.gallery-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.8) 0%,
        rgba(0, 0, 0, 0.3) 40%,
        transparent 100%
    );
    opacity: 0;
    transition: opacity 0.4s ease;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.5rem;
}

.gallery-card-glass:hover .gallery-overlay {
    opacity: 1;
}

.gallery-overlay-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
    transform: translateY(20px);
    transition: transform 0.4s ease;
}

.gallery-card-glass:hover .gallery-overlay-title {
    transform: translateY(0);
}

.gallery-overlay-category {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: #d4af37;
    font-weight: 600;
    transform: translateY(20px);
    transition: transform 0.4s ease 0.1s;
}

.gallery-card-glass:hover .gallery-overlay-category {
    transform: translateY(0);
}

.gallery-zoom-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 3rem;
    height: 3rem;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s ease;
}

.gallery-card-glass:hover .gallery-zoom-btn {
    opacity: 1;
    transform: scale(1);
}

.gallery-zoom-btn:hover {
    background: #d4af37;
    border-color: #d4af37;
}

/* Empty State */
.gallery-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 1.5rem;
    border: 2px dashed rgba(0, 0, 0, 0.1);
}

.dark .gallery-empty {
    background: rgba(30, 41, 59, 0.8);
    border-color: rgba(255, 255, 255, 0.1);
}

.gallery-empty-icon {
    width: 5rem;
    height: 5rem;
    margin: 0 auto 1.5rem;
    background: rgba(212, 175, 55, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gallery-empty-icon .material-symbols-outlined {
    font-size: 2.5rem;
    color: #d4af37;
}

/* Pagination */
.pagination-glass {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 3rem;
    flex-wrap: wrap;
}

.page-btn-glass {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.75rem;
    height: 2.75rem;
    padding: 0 0.75rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-secondary-light);
    text-decoration: none;
    transition: all 0.3s ease;
}

.dark .page-btn-glass {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--text-secondary-dark);
}

.page-btn-glass:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: #d4af37;
    color: #d4af37;
    transform: translateY(-2px);
}

.page-btn-glass.active {
    background: linear-gradient(135deg, #1A237E, #d4af37);
    border-color: transparent;
    color: white;
    box-shadow: 0 8px 25px rgba(26, 35, 126, 0.3);
}

.page-btn-glass.disabled {
    opacity: 0.5;
    pointer-events: none;
}
</style>
<style>
/* Lightbox */
.lightbox-glass {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(20px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.lightbox-glass.active {
    display: flex;
    opacity: 1;
}

.lightbox-content {
    position: relative;
    max-width: 90vw;
    max-height: 85vh;
    animation: lightbox-zoom 0.4s ease;
}

@keyframes lightbox-zoom {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.lightbox-content img {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 1rem;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
}

.lightbox-info {
    position: absolute;
    bottom: -4rem;
    left: 0;
    right: 0;
    text-align: center;
}

.lightbox-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.25rem;
}

.lightbox-category {
    font-size: 0.875rem;
    color: #d4af37;
    font-weight: 600;
}

.l