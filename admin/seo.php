<?php
/**
 * SEO Admin Panel - Aurora Hotel Plaza
 * Trang quản lý SEO toàn diện
 */

session_start();
require_once '../config/database.php';
require_once '../helpers/auth-middleware.php';
require_once '../helpers/seo-manager.php';

// Check admin auth
requireAdmin();

$db = getDB();
$current_page = 'seo';
$page_title = 'Quản lý SEO';

// Get SEO settings
$settings = $db->query("SELECT * FROM seo_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);

// Get SEO pages
$seo_pages = $db->query("SELECT * FROM seo_pages ORDER BY priority DESC, page_slug")->fetchAll(PDO::FETCH_ASSOC);

// Get FAQs
$faqs = $db->query("SELECT * FROM seo_faqs ORDER BY page_slug, display_order")->fetchAll(PDO::FETCH_ASSOC);

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$messageType = 'success';

if ($action === 'update_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $db->prepare("UPDATE seo_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    $message = 'Đã cập nhật cấu hình SEO!';
}

if ($action === 'update_page' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $seo_id = (int)$_POST['seo_id'];
    $data = [
        'meta_title_vi' => substr($_POST['meta_title_vi'], 0, 70),
        'meta_title_en' => substr($_POST['meta_title_en'], 0, 70),
        'meta_description_vi' => substr($_POST['meta_description_vi'], 0, 160),
        'meta_description_en' => substr($_POST['meta_description_en'], 0, 160),
        'meta_keywords_vi' => $_POST['meta_keywords_vi'],
        'meta_keywords_en' => $_POST['meta_keywords_en'],
        'og_image' => $_POST['og_image'],
        'priority' => $_POST['priority'],
        'changefreq' => $_POST['changefreq'],
        'robotsDirective' => $_POST['robotsDirective'],
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

    $message = 'Đã cập nhật SEO cho trang!';
}

if ($action === 'generate_sitemap') {
    SEOManager::saveSitemap();
    $message = 'Đã tạo sitemap.xml mới!';
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
    $message = 'Đã thêm FAQ mới!';
}

if ($action === 'delete_faq' && isset($_GET['faq_id'])) {
    $stmt = $db->prepare("DELETE FROM seo_faqs WHERE faq_id = ?");
    $stmt->execute([(int)$_GET['faq_id']]);
    $message = 'Đã xóa FAQ!';
}

if ($action === 'update_faq' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE seo_faqs SET page_slug = ?, question_vi = ?, question_en = ?, answer_vi = ?, answer_en = ?, display_order = ?, is_active = ? WHERE faq_id = ?");
    $stmt->execute([
        $_POST['faq_page_slug'],
        $_POST['faq_question_vi'],
        $_POST['faq_question_en'],
        $_POST['faq_answer_vi'],
        $_POST['faq_answer_en'],
        (int)$_POST['faq_order'],
        isset($_POST['faq_active']) ? 1 : 0,
        (int)$_POST['faq_id']
    ]);
    $message = 'Đã cập nhật FAQ!';
}

// Refresh data after updates
if ($message) {
    $settings = $db->query("SELECT * FROM seo_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);
    $seo_pages = $db->query("SELECT * FROM seo_pages ORDER BY priority DESC, page_slug")->fetchAll(PDO::FETCH_ASSOC);
    $faqs = $db->query("SELECT * FROM seo_faqs ORDER BY page_slug, display_order")->fetchAll(PDO::FETCH_ASSOC);
}

include 'includes/admin-header.php';
?>

<div class="admin-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1 class="page-title">
                <span class="material-symbols-outlined">search</span>
                Quản lý SEO
            </h1>
            <p class="page-subtitle">Tối ưu hóa tìm kiếm cho Aurora Hotel Plaza</p>
        </div>
        <div class="header-right">
            <button onclick="generateSitemap()" class="btn-primary">
                <span class="material-symbols-outlined">sync</span>
                Tạo Sitemap
            </button>
            <a href="<?= BASE_URL ?>/sitemap.xml" target="_blank" class="btn-secondary">
                <span class="material-symbols-outlined">visibility</span>
                Xem Sitemap
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <span class="material-symbols-outlined"><?= $messageType === 'success' ? 'check_circle' : 'error' ?></span>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab active" onclick="showTab('settings')">
                <span class="material-symbols-outlined">tune</span>
                Cấu hình
            </button>
            <button class="tab" onclick="showTab('pages')">
                <span class="material-symbols-outlined">article</span>
                Trang
            </button>
            <button class="tab" onclick="showTab('faq')">
                <span class="material-symbols-outlined">help</span>
                FAQ Schema
            </button>
            <button class="tab" onclick="showTab('tools')">
                <span class="material-symbols-outlined">build</span>
                Công cụ
            </button>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content">

        <!-- Settings Tab -->
        <div id="tab-settings" class="tab-panel active">
            <div class="card">
                <div class="card-header">
                    <h3>Cấu hình SEO toàn trang</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="?action=update_settings">
                        <div class="form-grid">
                            <?php foreach ($settings as $setting): ?>
                            <div class="form-group">
                                <label><?= htmlspecialchars($setting['description'] ?? $setting['setting_key']) ?></label>
                                <input type="text" name="settings[<?= $setting['setting_key'] ?>]"
                                    value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                    class="form-input">
                                <small class="form-hint">Key: <?= $setting['setting_key'] ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Lưu cấu hình
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pages Tab -->
        <div id="tab-pages" class="tab-panel">
            <div class="card">
                <div class="card-header">
                    <h3>SEO cho từng trang</h3>
                    <p class="text-muted">Title tối đa 70 ký tự, Description tối đa 160 ký tự</p>
                </div>
                <div class="card-body">
                    <div class="seo-pages-list">
                        <?php foreach ($seo_pages as $page): ?>
                        <div class="seo-page-item" id="page-<?= $page['seo_id'] ?>">
                            <div class="seo-page-header">
                                <div class="page-info">
                                    <span class="page-slug"><?= htmlspecialchars($page['page_slug']) ?></span>
                                    <span class="page-type badge"><?= $page['page_type'] ?></span>
                                    <span class="page-priority">Priority: <?= $page['priority'] ?></span>
                                    <span class="page-status <?= $page['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $page['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                                <button onclick="toggleSeoPage(<?= $page['seo_id'] ?>)" class="btn-sm">
                                    <span class="material-symbols-outlined">expand_more</span>
                                </button>
                            </div>
                            <div class="seo-page-form" id="form-<?= $page['seo_id'] ?>" style="display:none;">
                                <form method="POST" action="?action=update_page">
                                    <input type="hidden" name="seo_id" value="<?= $page['seo_id'] ?>">
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label>Title (VI) - <?= strlen($page['meta_title_vi']) ?>/70</label>
                                            <input type="text" name="meta_title_vi"
                                                value="<?= htmlspecialchars($page['meta_title_vi']) ?>"
                                                maxlength="70" class="form-input"
                                                oninput="updateCharCount(this, 70)">
                                        </div>
                                        <div class="form-group">
                                            <label>Title (EN) - <?= strlen($page['meta_title_en']) ?>/70</label>
                                            <input type="text" name="meta_title_en"
                                                value="<?= htmlspecialchars($page['meta_title_en']) ?>"
                                                maxlength="70" class="form-input"
                                                oninput="updateCharCount(this, 70)">
                                        </div>
                                    </div>
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label>Description (VI) - <?= strlen($page['meta_description_vi']) ?>/160</label>
                                            <textarea name="meta_description_vi" maxlength="160"
                                                class="form-input" rows="2"
                                                oninput="updateCharCount(this, 160)"><?= htmlspecialchars($page['meta_description_vi']) ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Description (EN) - <?= strlen($page['meta_description_en']) ?>/160</label>
                                            <textarea name="meta_description_en" maxlength="160"
                                                class="form-input" rows="2"
                                                oninput="updateCharCount(this, 160)"><?= htmlspecialchars($page['meta_description_en']) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label>Keywords (VI)</label>
                                            <input type="text" name="meta_keywords_vi"
                                                value="<?= htmlspecialchars($page['meta_keywords_vi']) ?>"
                                                class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Keywords (EN)</label>
                                            <input type="text" name="meta_keywords_en"
                                                value="<?= htmlspecialchars($page['meta_keywords_en']) ?>"
                                                class="form-input">
                                        </div>
                                    </div>
                                    <div class="form-grid-3">
                                        <div class="form-group">
                                            <label>OG Image</label>
                                            <input type="text" name="og_image"
                                                value="<?= htmlspecialchars($page['og_image']) ?>"
                                                class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Priority</label>
                                            <select name="priority" class="form-input">
                                                <?php for ($p = 0.0; $p <= 1.0; $p += 0.1): ?>
                                                <option value="<?= $p ?>" <?= $page['priority'] == $p ? 'selected' : '' ?>>
                                                    <?= $p ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Change Frequency</label>
                                            <select name="changefreq" class="form-input">
                                                <option value="always" <?= $page['changefreq'] == 'always' ? 'selected' : '' ?>>Always</option>
                                                <option value="hourly" <?= $page['changefreq'] == 'hourly' ? 'selected' : '' ?>>Hourly</option>
                                                <option value="daily" <?= $page['changefreq'] == 'daily' ? 'selected' : '' ?>>Daily</option>
                                                <option value="weekly" <?= $page['changefreq'] == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                                <option value="monthly" <?= $page['changefreq'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                                <option value="yearly" <?= $page['changefreq'] == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                                <option value="never" <?= $page['changefreq'] == 'never' ? 'selected' : '' ?>>Never</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-grid-2">
                                        <div class="form-group">
                                            <label>Robots Directive</label>
                                            <select name="robotsDirective" class="form-input">
                                                <option value="index, follow" <?= $page['robotsDirective'] == 'index, follow' ? 'selected' : '' ?>>Index, Follow</option>
                                                <option value="index, nofollow" <?= $page['robotsDirective'] == 'index, nofollow' ? 'selected' : '' ?>>Index, NoFollow</option>
                                                <option value="noindex, follow" <?= $page['robotsDirective'] == 'noindex, follow' ? 'selected' : '' ?>>NoIndex, Follow</option>
                                                <option value="noindex, nofollow" <?= $page['robotsDirective'] == 'noindex, nofollow' ? 'selected' : '' ?>>NoIndex, NoFollow</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="is_active" <?= $page['is_active'] ? 'checked' : '' ?>>
                                                Active
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-primary">
                                        <span class="material-symbols-outlined">save</span>
                                        Lưu thay đổi
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Schema Tab -->
        <div id="tab-faq" class="tab-panel">
            <div class="card">
                <div class="card-header">
                    <h3>FAQ Structured Data</h3>
                    <p class="text-muted">FAQ Schema giúp Google hiểu nội dung và hiển thị trong kết quả tìm kiếm</p>
                </div>
                <div class="card-body">
                    <!-- Add New FAQ -->
                    <div class="faq-add-section">
                        <h4>Thêm FAQ mới</h4>
                        <form method="POST" action="?action=add_faq">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Trang</label>
                                    <select name="faq_page_slug" class="form-input">
                                        <?php foreach ($seo_pages as $p): ?>
                                        <option value="<?= $p['page_slug'] ?>"><?= $p['page_slug'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Thứ tự</label>
                                    <input type="number" name="faq_order" value="1" class="form-input">
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label>Câu hỏi (VI)</label>
                                    <input type="text" name="faq_question_vi" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label>Câu hỏi (EN)</label>
                                    <input type="text" name="faq_question_en" class="form-input" required>
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label>Trả lời (VI)</label>
                                    <textarea name="faq_answer_vi" class="form-input" rows="3" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Trả lời (EN)</label>
                                    <textarea name="faq_answer_en" class="form-input" rows="3" required></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary">
                                <span class="material-symbols-outlined">add</span>
                                Thêm FAQ
                            </button>
                        </form>
                    </div>

                    <!-- FAQ List -->
                    <div class="faq-list-section">
                        <h4>Danh sách FAQ</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Trang</th>
                                    <th>Câu hỏi</th>
                                    <th>Thứ tự</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($faqs as $faq): ?>
                                <tr>
                                    <td><?= htmlspecialchars($faq['page_slug']) ?></td>
                                    <td>
                                        <strong>VI:</strong> <?= htmlspecialchars($faq['question_vi']) ?><br>
                                        <strong>EN:</strong> <?= htmlspecialchars($faq['question_en']) ?>
                                    </td>
                                    <td><?= $faq['display_order'] ?></td>
                                    <td>
                                        <span class="badge <?= $faq['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $faq['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="editFaq(<?= $faq['faq_id'] ?>)" class="btn-sm btn-edit">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <a href="?action=delete_faq&faq_id=<?= $faq['faq_id'] ?>"
                                            class="btn-sm btn-danger" onclick="return confirm('Xóa FAQ này?')">
                                            <span class="material-symbols-outlined">delete</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tools Tab -->
        <div id="tab-tools" class="tab-panel">
            <div class="card">
                <div class="card-header">
                    <h3>Công cụ SEO</h3>
                </div>
                <div class="card-body">
                    <div class="tools-grid">
                        <!-- Sitemap Generator -->
                        <div class="tool-card">
                            <div class="tool-icon">
                                <span class="material-symbols-outlined">sitemap</span>
                            </div>
                            <h4>Sitemap Generator</h4>
                            <p>Tạo sitemap.xml tự động từ database</p>
                            <button onclick="generateSitemap()" class="btn-primary">
                                <span class="material-symbols-outlined">sync</span>
                                Tạo Sitemap
                            </button>
                            <a href="<?= BASE_URL ?>/sitemap.xml" target="_blank" class="btn-secondary">
                                <span class="material-symbols-outlined">visibility</span>
                                Xem Sitemap
                            </a>
                        </div>

                        <!-- Robots.txt -->
                        <div class="tool-card">
                            <div class="tool-icon">
                                <span class="material-symbols-outlined">shield</span>
                            </div>
                            <h4>Robots.txt</h4>
                            <p>Quản lý robots.txt</p>
                            <a href="<?= BASE_URL ?>/robots.txt" target="_blank" class="btn-secondary">
                                <span class="material-symbols-outlined">visibility</span>
                                Xem Robots.txt
                            </a>
                        </div>

                        <!-- SEO Checklist -->
                        <div class="tool-card">
                            <div class="tool-icon">
                                <span class="material-symbols-outlined">checklist</span>
                            </div>
                            <h4>SEO Checklist</h4>
                            <p>Kiểm tra SEO website</p>
                            <button onclick="showSeoChecklist()" class="btn-secondary">
                                <span class="material-symbols-outlined">list</span>
                                Xem Checklist
                            </button>
                        </div>

                        <!-- Structured Data Test -->
                        <div class="tool-card">
                            <div class="tool-icon">
                                <span class="material-symbols-outlined">code</span>
                            </div>
                            <h4>Structured Data Test</h4>
                            <p>Kiểm tra JSON-LD Schema</p>
                            <a href="https://search.google.com/test/rich-results" target="_blank" class="btn-secondary">
                                <span class="material-symbols-outlined">external_link</span>
                                Google Rich Results
                            </a>
                        </div>
                    </div>

                    <!-- SEO Tips -->
                    <div class="seo-tips-section">
                        <h4>Hướng dẫn SEO Aurora Hotel Plaza</h4>
                        <div class="tips-grid">
                            <div class="tip-card">
                                <h5>1. Keywords chiến lược</h5>
                                <ul>
                                    <li><strong>Primary:</strong> "khách sạn biên hòa", "hotel bien hoa"</li>
                                    <li><strong>Secondary:</strong> "khách sạn 4 sao đồng nai", "aurora hotel"</li>
                                    <li><strong>Long-tail:</strong> "đặt phòng khách sạn biên hòa", "tiệc cưới khách sạn"</li>
                                </ul>
                            </div>
                            <div class="tip-card">
                                <h5>2. Meta Tags Rules</h5>
                                <ul>
                                    <li>Title: 50-60 ký tự (tối đa 70)</li>
                                    <li>Description: 150-160 ký tự</li>
                                    <li>Keywords: 5-10 từ khóa liên quan</li>
                                    <li>Mỗi trang cần unique title/description</li>
                                </ul>
                            </div>
                            <div class="tip-card">
                                <h5>3. Structured Data</h5>
                                <ul>
                                    <li><strong>Hotel Schema:</strong> Homepage</li>
                                    <li><strong>Room Schema:</strong> Trang phòng</li>
                                    <li><strong>FAQ Schema:</strong> Trang có FAQ</li>
                                    <li><strong>Blog Schema:</strong> Tin tức</li>
                                </ul>
                            </div>
                            <div class="tip-card">
                                <h5>4. Content SEO</h5>
                                <ul>
                                    <li>Blog thường xuyên (2-4 bài/tháng)</li>
                                    <li>Update sitemap khi có thay đổi</li>
                                    <li>Alt text cho tất cả images</li>
                                    <li>Internal linking giữa các trang</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* SEO Admin Styles */
.seo-pages-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.seo-page-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.seo-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
}

.page-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-slug {
    font-weight: 600;
    color: #1e293b;
}

.page-type {
    background: #d4af37;
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
}

.page-priority {
    color: #64748b;
    font-size: 13px;
}

.page-status {
    font-size: 12px;
    font-weight: 500;
}

.page-status.active {
    color: #16a34a;
}

.page-status.inactive {
    color: #94a3b8;
}

.seo-page-form {
    padding: 16px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.form-hint {
    color: #94a3b8;
    font-size: 11px;
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.tool-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.tool-icon {
    font-size: 40px;
    color: #d4af37;
    margin-bottom: 12px;
}

.tool-card h4 {
    margin: 0 0 8px 0;
    color: #1e293b;
}

.tool-card p {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 16px;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.tip-card {
    background: #fffbeb;
    border: 1px solid #fef08a;
    border-radius: 8px;
    padding: 16px;
}

.tip-card h5 {
    color: #d4af37;
    margin-bottom: 12px;
}

.tip-card ul {
    margin: 0;
    padding-left: 16px;
    color: #64748b;
    font-size: 13px;
}

.tip-card li {
    margin-bottom: 6px;
}

.faq-add-section, .faq-list-section {
    margin-bottom: 24px;
}

.faq-add-section h4, .faq-list-section h4 {
    margin-bottom: 16px;
    color: #1e293b;
}

.btn-edit {
    background: #3b82f6;
    color: #fff;
}

.btn-danger {
    background: #ef4444;
    color: #fff;
}

.btn-sm {
    padding: 6px 12px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
</style>

<script>
function showTab(tabId) {
    // Hide all panels
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    // Deactivate all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    // Show selected panel
    document.getElementById('tab-' + tabId).classList.add('active');
    // Activate selected tab
    event.target.classList.add('active');
}

function toggleSeoPage(id) {
    const form = document.getElementById('form-' + id);
    if (form.style.display === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}

function updateCharCount(input, max) {
    const count = input.value.length;
    const label = input.previousElementSibling;
    if (label) {
        label.textContent = label.textContent.split(' - ')[0] + ' - ' + count + '/' + max;
    }
}

function generateSitemap() {
    fetch('?action=generate_sitemap')
        .then(r => r.text())
        .then(html => {
            alert('Sitemap đã được tạo!');
            location.reload();
        });
}

function editFaq(id) {
    // Show edit modal or inline form
    alert('Edit FAQ #' + id + ' - Coming soon');
}

function showSeoChecklist() {
    const checklist = `
SEO Checklist for Aurora Hotel Plaza:

✅ Meta Tags
- Title tags cho tất cả trang (50-70 chars)
- Meta descriptions (150-160 chars)
- Keywords research

✅ Technical SEO
- Sitemap.xml auto-generated
- Robots.txt configured
- Canonical URLs
- Hreflang tags (vi/en)
- Mobile responsive

✅ Structured Data
- Hotel Schema (homepage)
- Room Schema (room pages)
- FAQ Schema
- Organization Schema

✅ Content SEO
- Blog posts with keywords
- Image alt texts
- Internal linking
- H1, H2, H3 structure

✅ Local SEO
- Google Business Profile
- NAP consistency
- Local keywords
- Review management

✅ Performance
- Page speed optimization
- Image compression
- Browser caching
    `;
    alert(checklist);
}
</script>

<?php include 'includes/admin-footer.php'; ?>