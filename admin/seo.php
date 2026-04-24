<?php
/**
 * SEO Admin Panel - Aurora Hotel Plaza
 * Yoast SEO Style Interface with Google Preview
 */

// Session must be started before any includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';
require_once '../helpers/seo-manager.php';

// Check admin auth
AuthMiddleware::requireAdmin();

// Initialize SEOManager
SEOManager::init();

$db = getDB();
$current_page = 'seo';
$page_title = 'SEO Manager';

// Get SEO pages (with fallback)
try {
    $seo_pages = $db->query("SELECT * FROM seo_pages ORDER BY priority DESC, page_slug")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $seo_pages = [];
}

// Get SEO settings
try {
    $settings = $db->query("SELECT setting_key, setting_value FROM seo_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $settings = [];
}

// Get FAQs
try {
    $faqs = $db->query("SELECT * FROM seo_faqs ORDER BY page_slug, display_order")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $faqs = [];
}

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$messageType = 'success';

if ($action === 'update_page' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $seo_id = (int)$_POST['seo_id'];
    $data = [
        'meta_title_vi' => $_POST['meta_title_vi'],
        'meta_title_en' => $_POST['meta_title_en'],
        'meta_description_vi' => $_POST['meta_description_vi'],
        'meta_description_en' => $_POST['meta_description_en'],
        'meta_keywords_vi' => $_POST['meta_keywords_vi'],
        'meta_keywords_en' => $_POST['meta_keywords_en'],
        'og_image' => $_POST['og_image'] ?? '',
        'priority' => $_POST['priority'] ?? 0.8,
        'changefreq' => $_POST['changefreq'] ?? 'weekly',
        'robotsDirective' => $_POST['robotsDirective'] ?? 'index, follow',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    $stmt = $db->prepare("UPDATE seo_pages SET
        meta_title_vi = ?, meta_title_en = ?,
        meta_description_vi = ?, meta_description_en = ?,
        meta_keywords_vi = ?, meta_keywords_en = ?,
        og_image = ?, priority = ?, changefreq = ?,
        robotsDirective = ?, is_active = ?
        WHERE seo_id = ?");
    $stmt->execute([
        $data['meta_title_vi'], $data['meta_title_en'],
        $data['meta_description_vi'], $data['meta_description_en'],
        $data['meta_keywords_vi'], $data['meta_keywords_en'],
        $data['og_image'], $data['priority'], $data['changefreq'],
        $data['robotsDirective'], $data['is_active'],
        $seo_id
    ]);
    $message = 'SEO updated successfully!';

    // Refresh data
    $seo_pages = $db->query("SELECT * FROM seo_pages ORDER BY priority DESC, page_slug")->fetchAll(PDO::FETCH_ASSOC);
}

if ($action === 'update_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $db->prepare("UPDATE seo_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    $message = 'Settings updated!';
    $settings = $db->query("SELECT setting_key, setting_value FROM seo_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
}

if ($action === 'generate_sitemap') {
    SEOManager::saveSitemap();
    $message = 'Sitemap generated!';
}

if ($action === 'add_faq' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("INSERT INTO seo_faqs (page_slug, question_vi, question_en, answer_vi, answer_en, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $_POST['faq_page_slug'],
        $_POST['faq_question_vi'],
        $_POST['faq_question_en'],
        $_POST['faq_answer_vi'],
        $_POST['faq_answer_en'],
        (int)$_POST['faq_order']
    ]);
    $message = 'FAQ added!';
    $faqs = $db->query("SELECT * FROM seo_faqs ORDER BY page_slug, display_order")->fetchAll(PDO::FETCH_ASSOC);
}

if ($action === 'delete_faq' && isset($_GET['faq_id'])) {
    $stmt = $db->prepare("DELETE FROM seo_faqs WHERE faq_id = ?");
    $stmt->execute([(int)$_GET['faq_id']]);
    $message = 'FAQ deleted!';
    $faqs = $db->query("SELECT * FROM seo_faqs ORDER BY page_slug, display_order")->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate SEO Score function
function calculateSeoScore($page) {
    $score = 0;
    $maxScore = 100;

    // Title check (30 points)
    $titleLen = strlen($page['meta_title_vi'] ?? '');
    if ($titleLen >= 30 && $titleLen <= 60) $score += 30;
    elseif ($titleLen > 0) $score += 15;

    // Description check (30 points)
    $descLen = strlen($page['meta_description_vi'] ?? '');
    if ($descLen >= 120 && $descLen <= 160) $score += 30;
    elseif ($descLen > 80) $score += 15;
    elseif ($descLen > 0) $score += 10;

    // Keywords check (20 points)
    $keywords = $page['meta_keywords_vi'] ?? '';
    if (strlen($keywords) > 20) $score += 20;
    elseif (strlen($keywords) > 0) $score += 10;

    // Active status (10 points)
    if ($page['is_active']) $score += 10;

    // OG Image (10 points)
    if (!empty($page['og_image'])) $score += 10;

    return $score;
}

function getSeoScoreColor($score) {
    if ($score >= 80) return '#10B981'; // Green - Good
    if ($score >= 50) return '#F59E0B'; // Orange - Needs Improvement
    return '#EF4444'; // Red - Bad
}

function getSeoScoreLabel($score) {
    if ($score >= 80) return 'Good';
    if ($score >= 50) return 'Needs Improvement';
    return 'Bad';
}

include 'includes/admin-header.php';
?>

<div class="seo-dashboard">
    <!-- Header -->
    <div class="seo-header">
        <div class="seo-header-left">
            <h1>
                <span class="material-symbols-outlined">search</span>
                SEO Manager
            </h1>
            <p>Optimize your pages for search engines</p>
        </div>
        <div class="seo-header-right">
            <button onclick="generateSitemap()" class="seo-btn-primary">
                <span class="material-symbols-outlined">sync</span>
                Generate Sitemap
            </button>
            <a href="<?= BASE_URL ?>/sitemap.xml" target="_blank" class="seo-btn-secondary">
                <span class="material-symbols-outlined">visibility</span>
                View Sitemap
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="seo-alert seo-alert-<?= $messageType ?>">
        <span class="material-symbols-outlined"><?= $messageType === 'success' ? 'check_circle' : 'error' ?></span>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- SEO Overview Stats -->
    <div class="seo-overview">
        <div class="seo-stat-card">
            <div class="seo-stat-icon green">
                <span class="material-symbols-outlined">check_circle</span>
            </div>
            <div class="seo-stat-content">
                <span class="seo-stat-value"><?= count(array_filter($seo_pages, fn($p) => calculateSeoScore($p) >= 80)) ?></span>
                <span class="seo-stat-label">Good SEO</span>
            </div>
        </div>
        <div class="seo-stat-card">
            <div class="seo-stat-icon orange">
                <span class="material-symbols-outlined">warning</span>
            </div>
            <div class="seo-stat-content">
                <span class="seo-stat-value"><?= count(array_filter($seo_pages, fn($p) => calculateSeoScore($p) >= 50 && calculateSeoScore($p) < 80)) ?></span>
                <span class="seo-stat-label">Needs Improvement</span>
            </div>
        </div>
        <div class="seo-stat-card">
            <div class="seo-stat-icon red">
                <span class="material-symbols-outlined">error</span>
            </div>
            <div class="seo-stat-content">
                <span class="seo-stat-value"><?= count(array_filter($seo_pages, fn($p) => calculateSeoScore($p) < 50)) ?></span>
                <span class="seo-stat-label">Bad SEO</span>
            </div>
        </div>
        <div class="seo-stat-card">
            <div class="seo-stat-icon blue">
                <span class="material-symbols-outlined">article</span>
            </div>
            <div class="seo-stat-content">
                <span class="seo-stat-value"><?= count($seo_pages) ?></span>
                <span class="seo-stat-label">Total Pages</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="seo-tabs">
        <button class="seo-tab active" onclick="showSeoTab('pages')">
            <span class="material-symbols-outlined">article</span>
            Pages SEO
        </button>
        <button class="seo-tab" onclick="showSeoTab('settings')">
            <span class="material-symbols-outlined">tune</span>
            Settings
        </button>
        <button class="seo-tab" onclick="showSeoTab('faq')">
            <span class="material-symbols-outlined">help</span>
            FAQ Schema
        </button>
        <button class="seo-tab" onclick="showSeoTab('tools')">
            <span class="material-symbols-outlined">build</span>
            Tools
        </button>
    </div>

    <!-- Pages SEO Tab -->
    <div id="seo-tab-pages" class="seo-tab-content active">
        <div class="seo-pages-grid">
            <?php foreach ($seo_pages as $page):
                $score = calculateSeoScore($page);
                $scoreColor = getSeoScoreColor($score);
                $scoreLabel = getSeoScoreLabel($score);
            ?>
            <div class="seo-page-card" data-page-id="<?= $page['seo_id'] ?>">
                <!-- SEO Score Badge -->
                <div class="seo-score-badge" style="background: <?= $scoreColor ?>">
                    <div class="seo-score-circle">
                        <svg viewBox="0 0 36 36">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none" stroke="#fff" stroke-width="3" stroke-opacity="0.3"/>
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none" stroke="#fff" stroke-width="3"
                                stroke-dasharray="<?= $score ?>, 100"/>
                        </svg>
                        <span class="seo-score-number"><?= $score ?></span>
                    </div>
                    <span class="seo-score-label"><?= $scoreLabel ?></span>
                </div>

                <!-- Page Info -->
                <div class="seo-page-info">
                    <h3 class="seo-page-title"><?= htmlspecialchars($page['page_slug']) ?></h3>
                    <div class="seo-page-meta">
                        <span class="seo-badge"><?= $page['page_type'] ?></span>
                        <span class="seo-badge <?= $page['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $page['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>

                <!-- Google Preview -->
                <div class="seo-google-preview">
                    <div class="google-preview-title">
                        <?= htmlspecialchars(substr($page['meta_title_vi'] ?? 'No Title', 0, 60)) ?>
                    </div>
                    <div class="google-preview-url">
                        <?= BASE_URL ?>/<?= htmlspecialchars($page['page_slug']) ?>
                    </div>
                    <div class="google-preview-desc">
                        <?= htmlspecialchars(substr($page['meta_description_vi'] ?? 'No description...', 0, 160)) ?>
                    </div>
                </div>

                <!-- Analysis Summary -->
                <div class="seo-analysis-mini">
                    <div class="analysis-item <?= strlen($page['meta_title_vi'] ?? '') >= 30 && strlen($page['meta_title_vi'] ?? '') <= 60 ? 'good' : (strlen($page['meta_title_vi'] ?? '') > 0 ? 'warning' : 'bad') ?>">
                        <span class="material-symbols-outlined">title</span>
                        <span>Title: <?= strlen($page['meta_title_vi'] ?? 0) ?>/60</span>
                    </div>
                    <div class="analysis-item <?= strlen($page['meta_description_vi'] ?? '') >= 120 && strlen($page['meta_description_vi'] ?? '') <= 160 ? 'good' : (strlen($page['meta_description_vi'] ?? '') > 80 ? 'warning' : 'bad') ?>">
                        <span class="material-symbols-outlined">description</span>
                        <span>Desc: <?= strlen($page['meta_description_vi'] ?? 0) ?>/160</span>
                    </div>
                    <div class="analysis-item <?= !empty($page['meta_keywords_vi']) ? 'good' : 'bad' ?>">
                        <span class="material-symbols-outlined">label</span>
                        <span>Keywords: <?= !empty($page['meta_keywords_vi']) ? 'Set' : 'Missing' ?></span>
                    </div>
                </div>

                <!-- Edit Button -->
                <button class="seo-edit-btn" onclick="openSeoEditor(<?= $page['seo_id'] ?>)">
                    <span class="material-symbols-outlined">edit</span>
                    Edit SEO
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Settings Tab -->
    <div id="seo-tab-settings" class="seo-tab-content">
        <div class="seo-settings-panel">
            <h3>Global SEO Settings</h3>
            <form method="POST" action="?action=update_settings">
                <div class="seo-settings-grid">
                    <div class="seo-setting-item">
                        <label>Site Name</label>
                        <input type="text" name="settings[site_name]" value="<?= htmlspecialchars($settings['site_name'] ?? 'Aurora Hotel Plaza') ?>">
                    </div>
                    <div class="seo-setting-item">
                        <label>Tagline (VI)</label>
                        <input type="text" name="settings[site_tagline_vi]" value="<?= htmlspecialchars($settings['site_tagline_vi'] ?? '') ?>">
                    </div>
                    <div class="seo-setting-item">
                        <label>Tagline (EN)</label>
                        <input type="text" name="settings[site_tagline_en]" value="<?= htmlspecialchars($settings['site_tagline_en'] ?? '') ?>">
                    </div>
                    <div class="seo-setting-item">
                        <label>Default OG Image</label>
                        <input type="text" name="settings[default_og_image]" value="<?= htmlspecialchars($settings['default_og_image'] ?? '/assets/img/og-image.jpg') ?>">
                    </div>
                    <div class="seo-setting-item">
                        <label>Google Analytics ID</label>
                        <input type="text" name="settings[google_analytics_id]" value="<?= htmlspecialchars($settings['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                    </div>
                    <div class="seo-setting-item">
                        <label>Facebook Pixel ID</label>
                        <input type="text" name="settings[facebook_pixel_id]" value="<?= htmlspecialchars($settings['facebook_pixel_id'] ?? '') ?>" placeholder="123456789">
                    </div>
                    <div class="seo-setting-item">
                        <label>Google Site Verification</label>
                        <input type="text" name="settings[google_site_verification]" value="<?= htmlspecialchars($settings['google_site_verification'] ?? '') ?>">
                    </div>
                    <div class="seo-setting-item">
                        <label>Schema Star Rating</label>
                        <select name="settings[schema_star_rating]">
                            <option value="3" <?= ($settings['schema_star_rating'] ?? '4') == '3' ? 'selected' : '' ?>>3 Stars</option>
                            <option value="4" <?= ($settings['schema_star_rating'] ?? '4') == '4' ? 'selected' : '' ?>>4 Stars</option>
                            <option value="5" <?= ($settings['schema_star_rating'] ?? '4') == '5' ? 'selected' : '' ?>>5 Stars</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="seo-btn-primary">
                    <span class="material-symbols-outlined">save</span>
                    Save Settings
                </button>
            </form>
        </div>
    </div>

    <!-- FAQ Schema Tab -->
    <div id="seo-tab-faq" class="seo-tab-content">
        <div class="seo-faq-panel">
            <div class="seo-faq-add">
                <h3>Add FAQ for Structured Data</h3>
                <form method="POST" action="?action=add_faq">
                    <div class="seo-form-row">
                        <div class="seo-form-group">
                            <label>Page</label>
                            <select name="faq_page_slug" required>
                                <?php foreach ($seo_pages as $p): ?>
                                <option value="<?= $p['page_slug'] ?>"><?= $p['page_slug'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="seo-form-group">
                            <label>Order</label>
                            <input type="number" name="faq_order" value="1" min="1">
                        </div>
                    </div>
                    <div class="seo-form-row">
                        <div class="seo-form-group">
                            <label>Question (VI)</label>
                            <input type="text" name="faq_question_vi" required placeholder="Aurora Hotel ở đâu?">
                        </div>
                        <div class="seo-form-group">
                            <label>Question (EN)</label>
                            <input type="text" name="faq_question_en" required placeholder="Where is Aurora Hotel located?">
                        </div>
                    </div>
                    <div class="seo-form-row">
                        <div class="seo-form-group">
                            <label>Answer (VI)</label>
                            <textarea name="faq_answer_vi" rows="3" required placeholder="Aurora Hotel Plaza tại Biên Hòa, Đồng Nai..."></textarea>
                        </div>
                        <div class="seo-form-group">
                            <label>Answer (EN)</label>
                            <textarea name="faq_answer_en" rows="3" required placeholder="Aurora Hotel Plaza is located in Bien Hoa..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="seo-btn-primary">
                        <span class="material-symbols-outlined">add</span>
                        Add FAQ
                    </button>
                </form>
            </div>

            <div class="seo-faq-list">
                <h3>FAQ List (<?= count($faqs) ?> items)</h3>
                <?php foreach ($faqs as $faq): ?>
                <div class="seo-faq-item">
                    <div class="seo-faq-header">
                        <span class="seo-faq-page"><?= htmlspecialchars($faq['page_slug']) ?></span>
                        <span class="seo-badge <?= $faq['is_active'] ? 'active' : 'inactive' ?>"><?= $faq['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </div>
                    <div class="seo-faq-content">
                        <strong>Q:</strong> <?= htmlspecialchars($faq['question_vi']) ?>
                        <br><strong>A:</strong> <?= htmlspecialchars(substr($faq['answer_vi'], 0, 100)) ?>...
                    </div>
                    <div class="seo-faq-actions">
                        <a href="?action=delete_faq&faq_id=<?= $faq['faq_id'] ?>" class="seo-btn-danger" onclick="return confirm('Delete this FAQ?')">
                            <span class="material-symbols-outlined">delete</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($faqs)): ?>
                <div class="seo-empty-state">
                    <span class="material-symbols-outlined">help</span>
                    <p>No FAQs yet. Add FAQs for better SEO ranking!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tools Tab -->
    <div id="seo-tab-tools" class="seo-tab-content">
        <div class="seo-tools-grid">
            <div class="seo-tool-card">
                <div class="seo-tool-icon">
                    <span class="material-symbols-outlined">sitemap</span>
                </div>
                <h4>Sitemap Generator</h4>
                <p>Auto-generate XML sitemap from database</p>
                <button onclick="generateSitemap()" class="seo-btn-primary">
                    <span class="material-symbols-outlined">sync</span>
                    Generate
                </button>
            </div>
            <div class="seo-tool-card">
                <div class="seo-tool-icon">
                    <span class="material-symbols-outlined">code</span>
                </div>
                <h4>Rich Results Test</h4>
                <p>Test structured data on Google</p>
                <a href="https://search.google.com/test/rich-results?url=<?= BASE_URL ?>" target="_blank" class="seo-btn-secondary">
                    <span class="material-symbols-outlined">external_link</span>
                    Test
                </a>
            </div>
            <div class="seo-tool-card">
                <div class="seo-tool-icon">
                    <span class="material-symbols-outlined">speed</span>
                </div>
                <h4>PageSpeed Insights</h4>
                <p>Check page loading speed</p>
                <a href="https://pagespeed.web.dev/analysis?url=<?= BASE_URL ?>" target="_blank" class="seo-btn-secondary">
                    <span class="material-symbols-outlined">external_link</span>
                    Analyze
                </a>
            </div>
            <div class="seo-tool-card">
                <div class="seo-tool-icon">
                    <span class="material-symbols-outlined">manage_search</span>
                </div>
                <h4>Google Search Console</h4>
                <p>Monitor search performance</p>
                <a href="https://search.google.com/search-console" target="_blank" class="seo-btn-secondary">
                    <span class="material-symbols-outlined">external_link</span>
                    Open
                </a>
            </div>
        </div>

        <!-- SEO Tips -->
        <div class="seo-tips-section">
            <h3>SEO Best Practices</h3>
            <div class="seo-tips-grid">
                <div class="seo-tip-card">
                    <h5>Title Optimization</h5>
                    <ul>
                        <li>Keep 50-60 characters</li>
                        <li>Include primary keyword</li>
                        <li>Brand name at end</li>
                        <li>Unique for each page</li>
                    </ul>
                </div>
                <div class="seo-tip-card">
                    <h5>Meta Description</h5>
                    <ul>
                        <li>150-160 characters</li>
                        <li>Include call-to-action</li>
                        <li>Match page content</li>
                        <li>Use emotional triggers</li>
                    </ul>
                </div>
                <div class="seo-tip-card">
                    <h5>Keywords Strategy</h5>
                    <ul>
                        <li>Primary: "khách sạn biên hòa"</li>
                        <li>Secondary: "hotel bien hoa"</li>
                        <li>Long-tail: "đặt phòng 4 sao"</li>
                        <li>Local: "aurora hotel plaza"</li>
                    </ul>
                </div>
                <div class="seo-tip-card">
                    <h5>Structured Data</h5>
                    <ul>
                        <li>Hotel Schema on homepage</li>
                        <li>Room Schema on room pages</li>
                        <li>FAQ Schema for Q&A</li>
                        <li>Blog Schema for posts</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SEO Editor Modal -->
<div id="seoEditorModal" class="seo-modal">
    <div class="seo-modal-content">
        <div class="seo-modal-header">
            <h2>SEO Editor</h2>
            <button onclick="closeSeoEditor()" class="seo-modal-close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="seo-modal-body">
            <form id="seoEditForm" method="POST" action="?action=update_page">
                <input type="hidden" name="seo_id" id="edit_seo_id">

                <!-- Google Preview Live -->
                <div class="seo-preview-section">
                    <h4>Google Search Preview</h4>
                    <div class="seo-google-preview-live">
                        <div class="google-preview-title" id="googlePreviewTitle">Title Preview</div>
                        <div class="google-preview-url" id="googlePreviewUrl">URL Preview</div>
                        <div class="google-preview-desc" id="googlePreviewDesc">Description Preview</div>
                    </div>
                </div>

                <!-- SEO Analysis -->
                <div class="seo-analysis-section">
                    <h4>SEO Analysis</h4>
                    <div class="seo-analysis-items" id="seoAnalysisItems">
                        <!-- Dynamic analysis items -->
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="seo-form-section">
                    <div class="seo-lang-tabs">
                        <button class="seo-lang-tab active" onclick="switchLang('vi')">Vietnamese</button>
                        <button class="seo-lang-tab" onclick="switchLang('en')">English</button>
                    </div>

                    <!-- Vietnamese Fields -->
                    <div id="lang-vi" class="seo-lang-content active">
                        <div class="seo-form-group">
                            <label>Title (VI)</label>
                            <input type="text" name="meta_title_vi" id="edit_title_vi" maxlength="70" oninput="updatePreview()">
                            <div class="seo-char-counter">
                                <span id="title_vi_count">0</span>/70 chars
                                <span class="seo-counter-indicator" id="title_vi_indicator"></span>
                            </div>
                        </div>
                        <div class="seo-form-group">
                            <label>Description (VI)</label>
                            <textarea name="meta_description_vi" id="edit_desc_vi" maxlength="160" rows="3" oninput="updatePreview()"></textarea>
                            <div class="seo-char-counter">
                                <span id="desc_vi_count">0</span>/160 chars
                                <span class="seo-counter-indicator" id="desc_vi_indicator"></span>
                            </div>
                        </div>
                        <div class="seo-form-group">
                            <label>Keywords (VI)</label>
                            <input type="text" name="meta_keywords_vi" id="edit_keywords_vi" placeholder="khách sạn biên hòa, aurora hotel, 4 sao">
                            <small>Separate keywords with commas</small>
                        </div>
                    </div>

                    <!-- English Fields -->
                    <div id="lang-en" class="seo-lang-content">
                        <div class="seo-form-group">
                            <label>Title (EN)</label>
                            <input type="text" name="meta_title_en" id="edit_title_en" maxlength="70" oninput="updatePreview()">
                            <div class="seo-char-counter">
                                <span id="title_en_count">0</span>/70 chars
                            </div>
                        </div>
                        <div class="seo-form-group">
                            <label>Description (EN)</label>
                            <textarea name="meta_description_en" id="edit_desc_en" maxlength="160" rows="3" oninput="updatePreview()"></textarea>
                            <div class="seo-char-counter">
                                <span id="desc_en_count">0</span>/160 chars
                            </div>
                        </div>
                        <div class="seo-form-group">
                            <label>Keywords (EN)</label>
                            <input type="text" name="meta_keywords_en" id="edit_keywords_en" placeholder="hotel bien hoa, aurora hotel, 4 star">
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <div class="seo-advanced-section">
                        <h4>Advanced Settings</h4>
                        <div class="seo-form-row">
                            <div class="seo-form-group">
                                <label>OG Image</label>
                                <input type="text" name="og_image" id="edit_og_image" placeholder="/assets/img/og-image.jpg">
                            </div>
                            <div class="seo-form-group">
                                <label>Sitemap Priority</label>
                                <select name="priority" id="edit_priority">
                                    <?php for ($p = 0.0; $p <= 1.0; $p += 0.1): ?>
                                    <option value="<?= $p ?>"><?= $p ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="seo-form-row">
                            <div class="seo-form-group">
                                <label>Change Frequency</label>
                                <select name="changefreq" id="edit_changefreq">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="seo-form-group">
                                <label>Robots Directive</label>
                                <select name="robotsDirective" id="edit_robots">
                                    <option value="index, follow">Index, Follow</option>
                                    <option value="noindex, follow">NoIndex, Follow</option>
                                    <option value="noindex, nofollow">NoIndex, NoFollow</option>
                                </select>
                            </div>
                        </div>
                        <div class="seo-form-group seo-checkbox-group">
                            <label>
                                <input type="checkbox" name="is_active" id="edit_active" checked>
                                <span>Page is Active (Indexed)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="seo-modal-footer">
            <button onclick="closeSeoEditor()" class="seo-btn-secondary">Cancel</button>
            <button onclick="saveSeoPage()" class="seo-btn-primary">
                <span class="material-symbols-outlined">save</span>
                Save SEO
            </button>
        </div>
    </div>
</div>

<style>
/* SEO Dashboard Styles - Yoast Style */
.seo-dashboard {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
}

.seo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.seo-header h1 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.seo-header p {
    color: #64748b;
    margin-top: 4px;
}

.seo-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.seo-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.seo-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.seo-btn-secondary:hover {
    background: #e2e8f0;
}

.seo-btn-danger {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: #fef2f2;
    color: #ef4444;
    border: 1px solid #fecaca;
    border-radius: 6px;
    cursor: pointer;
}

/* SEO Overview Stats */
.seo-overview {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.seo-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.seo-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.seo-stat-icon.green { background: #d1fae5; color: #10B981; }
.seo-stat-icon.orange { background: #fef3c7; color: #F59E0B; }
.seo-stat-icon.red { background: #fee2e2; color: #EF4444; }
.seo-stat-icon.blue { background: #dbeafe; color: #3B82F6; }

.seo-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.seo-stat-label {
    font-size: 13px;
    color: #64748b;
}

/* SEO Tabs */
.seo-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e2e8f0;
}

.seo-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    background: transparent;
    border: none;
    color: #64748b;
    font-weight: 600;
    cursor: pointer;
    border-radius: 8px 8px 0 0;
    transition: all 0.2s;
}

.seo-tab:hover {
    background: #f1f5f9;
    color: #475569;
}

.seo-tab.active {
    background: #10B981;
    color: white;
}

.seo-tab-content {
    display: none;
}

.seo-tab-content.active {
    display: block;
}

/* SEO Pages Grid */
.seo-pages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
}

.seo-page-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.seo-score-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 8px 12px;
    border-radius: 8px;
    color: white;
    font-weight: 700;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.seo-score-circle {
    width: 36px;
    height: 36px;
    position: relative;
}

.seo-score-number {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 11px;
    font-weight: 700;
    color: white;
}

.seo-score-label {
    font-size: 10px;
    opacity: 0.9;
}

.seo-page-info {
    margin-bottom: 16px;
}

.seo-page-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.seo-page-meta {
    display: flex;
    gap: 8px;
}

.seo-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.seo-badge.active {
    background: #d1fae5;
    color: #10B981;
}

.seo-badge.inactive {
    background: #fee2e2;
    color: #EF4444;
}

/* Google Preview */
.seo-google-preview {
    background: #fff;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 12px;
}

.google-preview-title {
    color: #1a0dab;
    font-size: 18px;
    font-weight: 400;
    margin-bottom: 4px;
    line-height: 1.3;
}

.google-preview-url {
    color: #006621;
    font-size: 14px;
    margin-bottom: 4px;
}

.google-preview-desc {
    color: #545454;
    font-size: 13px;
    line-height: 1.4;
}

/* Analysis Mini */
.seo-analysis-mini {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.analysis-item {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.analysis-item.good { background: #d1fae5; color: #10B981; }
.analysis-item.warning { background: #fef3c7; color: #F59E0B; }
.analysis-item.bad { background: #fee2e2; color: #EF4444; }

.seo-edit-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.seo-edit-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}

/* Modal */
.seo-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.seo-modal.open {
    display: flex;
}

.seo-modal-content {
    background: white;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    border-radius: 16px;
    overflow-y: auto;
}

.seo-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.seo-modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.seo-modal-close {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #64748b;
}

.seo-modal-body {
    padding: 20px;
}

.seo-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px;
    border-top: 1px solid #e2e8f0;
}

/* SEO Preview Live */
.seo-preview-section {
    margin-bottom: 24px;
}

.seo-preview-section h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #64748b;
}

.seo-google-preview-live {
    background: #fff;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

/* SEO Analysis */
.seo-analysis-section {
    margin-bottom: 24px;
}

.seo-analysis-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.seo-analysis-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
}

.seo-analysis-item.good { background: #d1fae5; }
.seo-analysis-item.warning { background: #fef3c7; }
.seo-analysis-item.bad { background: #fee2e2; }

/* Form Styles */
.seo-form-section {
    margin-bottom: 24px;
}

.seo-lang-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}

.seo-lang-tab {
    padding: 8px 16px;
    background: #f1f5f9;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    color: #64748b;
}

.seo-lang-tab.active {
    background: #10B981;
    color: white;
}

.seo-lang-content {
    display: none;
}

.seo-lang-content.active {
    display: block;
}

.seo-form-group {
    margin-bottom: 16px;
}

.seo-form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.seo-form-group input,
.seo-form-group textarea,
.seo-form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

.seo-form-group input:focus,
.seo-form-group textarea:focus,
.seo-form-group select:focus {
    outline: none;
    border-color: #10B981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.seo-char-counter {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.seo-counter-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.seo-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.seo-checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.seo-checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

/* Settings Panel */
.seo-settings-panel {
    background: white;
    padding: 24px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.seo-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.seo-setting-item label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.seo-setting-item input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
}

/* FAQ Panel */
.seo-faq-panel {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.seo-faq-add,
.seo-faq-list {
    background: white;
    padding: 24px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.seo-faq-item {
    background: #f8fafc;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
}

.seo-faq-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.seo-faq-page {
    font-weight: 600;
    color: #10B981;
}

.seo-faq-content {
    font-size: 13px;
    color: #475569;
}

.seo-faq-actions {
    margin-top: 8px;
}

.seo-empty-state {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

/* Tools Grid */
.seo-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.seo-tool-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.seo-tool-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    background: #d1fae5;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #10B981;
}

.seo-tool-card h4 {
    margin: 0 0 8px 0;
}

.seo-tool-card p {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 16px;
}

/* Tips */
.seo-tips-section {
    background: white;
    padding: 24px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.seo-tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
}

.seo-tip-card {
    background: #fffbeb;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #fde68a;
}

.seo-tip-card h5 {
    color: #d97706;
    margin: 0 0 12px 0;
}

.seo-tip-card ul {
    margin: 0;
    padding-left: 16px;
    color: #78716c;
    font-size: 13px;
}

.seo-tip-card li {
    margin-bottom: 4px;
}

/* Alert */
.seo-alert {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.seo-alert-success {
    background: #d1fae5;
    color: #10B981;
}

.seo-alert-error {
    background: #fee2e2;
    color: #EF4444;
}

/* Responsive */
@media (max-width: 768px) {
    .seo-overview {
        grid-template-columns: repeat(2, 1fr);
    }

    .seo-pages-grid {
        grid-template-columns: 1fr;
    }

    .seo-faq-panel {
        grid-template-columns: 1fr;
    }

    .seo-form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// SEO Data Store
const seoData = <?= json_encode($seo_pages) ?>;
const baseUrl = '<?= BASE_URL ?>';

// Show SEO Tab
function showSeoTab(tabId) {
    document.querySelectorAll('.seo-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.seo-tab-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById('seo-tab-' + tabId).classList.add('active');
}

// Open SEO Editor
function openSeoEditor(pageId) {
    const page = seoData.find(p => p.seo_id == pageId);
    if (!page) return;

    document.getElementById('edit_seo_id').value = page.seo_id;
    document.getElementById('edit_title_vi').value = page.meta_title_vi || '';
    document.getElementById('edit_title_en').value = page.meta_title_en || '';
    document.getElementById('edit_desc_vi').value = page.meta_description_vi || '';
    document.getElementById('edit_desc_en').value = page.meta_description_en || '';
    document.getElementById('edit_keywords_vi').value = page.meta_keywords_vi || '';
    document.getElementById('edit_keywords_en').value = page.meta_keywords_en || '';
    document.getElementById('edit_og_image').value = page.og_image || '';
    document.getElementById('edit_priority').value = page.priority || 0.8;
    document.getElementById('edit_changefreq').value = page.changefreq || 'weekly';
    document.getElementById('edit_robots').value = page.robotsDirective || 'index, follow';
    document.getElementById('edit_active').checked = page.is_active == 1;

    // Update preview URL
    document.getElementById('googlePreviewUrl').textContent = baseUrl + '/' + page.page_slug;

    // Update counters
    updateCharCounters();
    updatePreview();
    updateAnalysis();

    document.getElementById('seoEditorModal').classList.add('open');
}

// Close SEO Editor
function closeSeoEditor() {
    document.getElementById('seoEditorModal').classList.remove('open');
}

// Switch Language Tab
function switchLang(lang) {
    document.querySelectorAll('.seo-lang-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.seo-lang-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById('lang-' + lang).classList.add('active');
}

// Update Preview
function updatePreview() {
    const title = document.getElementById('edit_title_vi').value;
    const desc = document.getElementById('edit_desc_vi').value;

    document.getElementById('googlePreviewTitle').textContent = title || 'No Title';
    document.getElementById('googlePreviewDesc').textContent = desc || 'No description provided...';

    updateCharCounters();
    updateAnalysis();
}

// Update Character Counters
function updateCharCounters() {
    const titleVi = document.getElementById('edit_title_vi').value.length;
    const titleEn = document.getElementById('edit_title_en').value.length;
    const descVi = document.getElementById('edit_desc_vi').value.length;
    const descEn = document.getElementById('edit_desc_en').value.length;

    document.getElementById('title_vi_count').textContent = titleVi;
    document.getElementById('title_en_count').textContent = titleEn;
    document.getElementById('desc_vi_count').textContent = descVi;
    document.getElementById('desc_en_count').textContent = descEn;

    // Update indicators
    updateIndicator('title_vi_indicator', titleVi, 30, 60);
    updateIndicator('desc_vi_indicator', descVi, 120, 160);
}

function updateIndicator(id, value, min, max) {
    const el = document.getElementById(id);
    if (!el) return;

    if (value >= min && value <= max) {
        el.style.background = '#10B981';
    } else if (value > 0) {
        el.style.background = '#F59E0B';
    } else {
        el.style.background = '#EF4444';
    }
}

// Update SEO Analysis
function updateAnalysis() {
    const title = document.getElementById('edit_title_vi').value;
    const desc = document.getElementById('edit_desc_vi').value;
    const keywords = document.getElementById('edit_keywords_vi').value;

    const analysisItems = document.getElementById('seoAnalysisItems');
    analysisItems.innerHTML = '';

    // Title Analysis
    const titleLen = title.length;
    const titleStatus = titleLen >= 30 && titleLen <= 60 ? 'good' : (titleLen > 0 ? 'warning' : 'bad');
    const titleMsg = titleLen >= 30 && titleLen <= 60
        ? '✓ Title length is optimal (30-60 chars)'
        : titleLen > 60
            ? '⚠ Title too long (>60 chars)'
            : titleLen > 0
                ? '⚠ Title too short (<30 chars)'
                : '✗ No title set';

    analysisItems.innerHTML += `<div class="seo-analysis-item ${titleStatus}">
        <span class="material-symbols-outlined">${titleStatus === 'good' ? 'check_circle' : (titleStatus === 'warning' ? 'warning' : 'error')}</span>
        ${titleMsg}
    </div>`;

    // Description Analysis
    const descLen = desc.length;
    const descStatus = descLen >= 120 && descLen <= 160 ? 'good' : (descLen > 80 ? 'warning' : 'bad');
    const descMsg = descLen >= 120 && descLen <= 160
        ? '✓ Description length is optimal (120-160 chars)'
        : descLen > 160
            ? '⚠ Description too long (>160 chars)'
            : descLen > 80
                ? '⚠ Description could be longer'
                : '✗ Description too short';

    analysisItems.innerHTML += `<div class="seo-analysis-item ${descStatus}">
        <span class="material-symbols-outlined">${descStatus === 'good' ? 'check_circle' : (descStatus === 'warning' ? 'warning' : 'error')}</span>
        ${descMsg}
    </div>`;

    // Keywords Analysis
    const kwStatus = keywords.length > 20 ? 'good' : (keywords.length > 0 ? 'warning' : 'bad');
    const kwMsg = keywords.length > 20
        ? '✓ Keywords are set'
        : keywords.length > 0
            ? '⚠ Add more keywords'
            : '✗ No keywords set';

    analysisItems.innerHTML += `<div class="seo-analysis-item ${kwStatus}">
        <span class="material-symbols-outlined">${kwStatus === 'good' ? 'check_circle' : (kwStatus === 'warning' ? 'warning' : 'error')}</span>
        ${kwMsg}
    </div>`;
}

// Save SEO Page
function saveSeoPage() {
    document.getElementById('seoEditForm').submit();
}

// Generate Sitemap
function generateSitemap() {
    fetch('?action=generate_sitemap')
        .then(r => r.text())
        .then(() => {
            alert('Sitemap generated successfully!');
            location.reload();
        });
}
</script>

<?php include 'includes/admin-footer.php'; ?>