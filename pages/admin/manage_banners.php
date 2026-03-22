<?php
/**
 * Manage Homepage Banners – Admin Panel
 * Upload, manage, and arrange banner images for hero slider and between-section positions
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

$upload_dir = __DIR__ . '/../../uploads/banners/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Check if banner_type column exists (migration might not have run yet)
$col_check = $conn->query("SHOW COLUMNS FROM homepage_banners LIKE 'banner_type'");
$has_banner_type = ($col_check && $col_check->num_rows > 0);

// ─── AJAX handlers ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // UPLOAD BANNER
    if ($action === 'upload_banner') {
        $title       = trim($_POST['title'] ?? '');
        $title_ar    = trim($_POST['title_ar'] ?? '');
        $link_url    = trim($_POST['link_url'] ?? '');
        $position    = intval($_POST['position'] ?? 0);
        $banner_type = ($_POST['banner_type'] ?? 'section');
        $subtitle    = trim($_POST['subtitle'] ?? '');
        $subtitle_ar = trim($_POST['subtitle_ar'] ?? '');
        $cta_text    = trim($_POST['cta_text'] ?? '');
        $cta_text_ar = trim($_POST['cta_text_ar'] ?? '');
        $sort_order  = intval($_POST['sort_order'] ?? 0);

        // Validate banner_type
        if (!in_array($banner_type, ['hero', 'section'])) {
            $banner_type = 'section';
        }

        if (empty($_FILES['banner_image']) || $_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Please select a valid image file']);
            exit();
        }

        $file = $_FILES['banner_image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)]);
            exit();
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large. Max 10MB.']);
            exit();
        }

        $filename = 'banner_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $dest = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
            exit();
        }

        $image_path = 'uploads/banners/' . $filename;

        if ($has_banner_type) {
            $stmt = $conn->prepare("INSERT INTO homepage_banners (title, title_ar, banner_type, subtitle, subtitle_ar, cta_text, cta_text_ar, sort_order, image_path, position, link_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('sssssssisis', $title, $title_ar, $banner_type, $subtitle, $subtitle_ar, $cta_text, $cta_text_ar, $sort_order, $image_path, $position, $link_url);
        } else {
            $stmt = $conn->prepare("INSERT INTO homepage_banners (title, title_ar, image_path, position, link_url, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('sssis', $title, $title_ar, $image_path, $position, $link_url);
        }

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            echo json_encode([
                'success'     => true,
                'id'          => $new_id,
                'title'       => $title,
                'title_ar'    => $title_ar,
                'image_path'  => $image_path,
                'position'    => $position,
                'link_url'    => $link_url,
                'banner_type' => $banner_type,
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
            $stmt->close();
        }
        exit();
    }

    // TOGGLE ACTIVE
    if ($action === 'toggle_banner') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE homepage_banners SET is_active = NOT is_active WHERE id = $id");
            $row = $conn->query("SELECT is_active FROM homepage_banners WHERE id = $id")->fetch_assoc();
            echo json_encode(['success' => true, 'is_active' => (bool)$row['is_active']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
        exit();
    }

    // UPDATE POSITION / SORT ORDER
    if ($action === 'update_position') {
        $id         = intval($_POST['id'] ?? 0);
        $position   = intval($_POST['position'] ?? 0);
        $sort_order = intval($_POST['sort_order'] ?? 0);
        if ($id > 0) {
            if ($has_banner_type) {
                $stmt = $conn->prepare("UPDATE homepage_banners SET position = ?, sort_order = ? WHERE id = ?");
                $stmt->bind_param('iii', $position, $sort_order, $id);
            } else {
                $stmt = $conn->prepare("UPDATE homepage_banners SET position = ? WHERE id = ?");
                $stmt->bind_param('ii', $position, $id);
            }
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
        exit();
    }

    // DELETE BANNER
    if ($action === 'delete_banner') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit();
        }
        // Get file path first
        $row = $conn->query("SELECT image_path FROM homepage_banners WHERE id = $id")->fetch_assoc();
        if ($row) {
            $file_path = __DIR__ . '/../../' . $row['image_path'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
        $stmt = $conn->prepare("DELETE FROM homepage_banners WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Delete failed']);
        }
        $stmt->close();
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit();
}

// ─── Load banners ────────────────────────────────────────────────────────
$banners = [];
$hero_banners = [];
$section_banners = [];

if ($has_banner_type) {
    $result = $conn->query("SELECT * FROM homepage_banners ORDER BY banner_type DESC, sort_order ASC, position ASC, id ASC");
} else {
    $result = $conn->query("SELECT * FROM homepage_banners ORDER BY position ASC, id ASC");
}
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $banners[] = $row;
        if ($has_banner_type && ($row['banner_type'] ?? 'section') === 'hero') {
            $hero_banners[] = $row;
        } else {
            $section_banners[] = $row;
        }
    }
}

// Load categories for position reference
$categories = [];
$cat_result = $conn->query("SELECT id, name_en FROM categories ORDER BY id ASC");
if ($cat_result) {
    while ($c = $cat_result->fetch_assoc()) {
        $categories[] = $c;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Banners – Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a1d2e; --secondary-dark: #242838; --accent-blue: #4f9eff;
            --accent-teal: #00d4aa; --accent-purple: #a855f7; --text-light: #fff;
            --text-gray: #9ca3af; --text-dark: #1f2937; --success: #10b981;
            --warning: #f59e0b; --danger: #ef4444; --bg-light: #f9fafb;
            --border-color: #e5e7eb; --shadow-md: 0 4px 6px rgba(0,0,0,.1);
            --accent-gold: #C5A059; --accent-rose: #E8C4B8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); min-height: 100vh; display: flex; color: var(--text-dark); }

        .sidebar { width: 280px; background: linear-gradient(180deg, var(--primary-dark), var(--secondary-dark)); color: var(--text-light); display: flex; flex-direction: column; position: fixed; left: 0; top: 0; bottom: 0; overflow-y: auto; z-index: 1000; box-shadow: 0 20px 40px rgba(0,0,0,.2); }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.1); }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .admin-badge { display: inline-block; background: linear-gradient(135deg, var(--accent-purple), var(--accent-blue)); color: #fff; font-size: .7rem; padding: .25rem .75rem; border-radius: 20px; margin-top: .5rem; font-weight: 600; }
        .sidebar-nav { flex: 1; padding: 1.5rem 0; }
        .nav-item { padding: .75rem 1.5rem; display: flex; align-items: center; gap: 1rem; color: var(--text-gray); cursor: pointer; transition: all .3s; border-left: 3px solid transparent; text-decoration: none; }
        .nav-item:hover { background: rgba(255,255,255,.05); color: var(--text-light); }
        .nav-item.active { background: rgba(79,158,255,.1); color: var(--accent-blue); border-left-color: var(--accent-blue); }
        .nav-item i { font-size: 1.1rem; width: 24px; text-align: center; }
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid rgba(255,255,255,.1); }
        .logout-btn { width: 100%; background: linear-gradient(135deg, var(--danger), #dc2626); color: #fff; border: none; padding: .875rem; border-radius: 10px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: .5rem; transition: all .3s; text-decoration: none; }

        .main-content { flex: 1; margin-left: 280px; padding: 2rem; overflow-x: hidden; }
        .page-header { background: #fff; padding: 1.75rem 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .page-header h1 { font-size: 1.875rem; font-weight: 700; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        .form-card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); margin-bottom: 2rem; }
        .form-card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; color: var(--primary-dark); padding-bottom: .75rem; border-bottom: 2px solid var(--bg-light); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: .4rem; font-size: .875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: .65rem .9rem; border: 2px solid var(--border-color); border-radius: 10px; font-size: .9rem; font-family: inherit; transition: border-color .3s; background: var(--bg-light); }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 3px rgba(79,158,255,.15); }
        .form-group .help-text { font-size: .78rem; color: var(--text-gray); margin-top: .3rem; }

        /* Banner type toggle */
        .type-toggle { display: flex; gap: 0; border: 2px solid var(--border-color); border-radius: 12px; overflow: hidden; margin-bottom: 1rem; }
        .type-toggle label { flex: 1; padding: .75rem 1rem; text-align: center; cursor: pointer; font-weight: 600; font-size: .85rem; transition: all .3s; background: var(--bg-light); color: var(--text-gray); display: flex; align-items: center; justify-content: center; gap: .4rem; }
        .type-toggle label:first-child { border-right: 1px solid var(--border-color); }
        .type-toggle input { display: none; }
        .type-toggle input:checked + label { background: linear-gradient(135deg, var(--accent-gold), #d4a847); color: #fff; }
        .type-toggle .type-hero-label { }
        .type-toggle .type-section-label { }

        .file-upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all .3s;
            background: var(--bg-light);
            position: relative;
        }
        .file-upload-zone:hover, .file-upload-zone.dragover {
            border-color: var(--accent-blue);
            background: rgba(79,158,255,.05);
        }
        .file-upload-zone i { font-size: 2rem; color: var(--accent-blue); margin-bottom: .5rem; }
        .file-upload-zone p { color: var(--text-gray); font-size: .85rem; }
        .file-upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .file-preview { margin-top: 1rem; max-height: 120px; border-radius: 8px; display: none; }

        .btn { padding: .65rem 1.25rem; border: none; border-radius: 10px; font-weight: 600; font-size: .9rem; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem; transition: all .3s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-blue), #3b82f6); color: #fff; box-shadow: 0 4px 12px rgba(79,158,255,.3); }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-danger { background: linear-gradient(135deg, var(--danger), #dc2626); color: #fff; }
        .btn-sm { padding: .35rem .75rem; font-size: .78rem; border-radius: 8px; }
        .btn-secondary { background: var(--bg-light); color: var(--text-dark); border: 2px solid var(--border-color); }
        .btn-success { background: linear-gradient(135deg, var(--success), #059669); color: #fff; }
        .btn-warning { background: linear-gradient(135deg, var(--warning), #d97706); color: #fff; }
        .btn-gold { background: linear-gradient(135deg, var(--accent-gold), #d4a847); color: #fff; }

        /* Tab system */
        .tab-nav { display: flex; gap: 0; margin-bottom: 1.5rem; border: 2px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        .tab-btn { flex: 1; padding: .85rem 1rem; text-align: center; cursor: pointer; font-weight: 600; font-size: .9rem; transition: all .3s; background: #fff; color: var(--text-gray); border: none; display: flex; align-items: center; justify-content: center; gap: .5rem; }
        .tab-btn:not(:last-child) { border-right: 1px solid var(--border-color); }
        .tab-btn.active { background: var(--primary-dark); color: #fff; }
        .tab-btn .tab-count { background: rgba(0,0,0,.1); padding: 0.1rem .5rem; border-radius: 10px; font-size: .75rem; }
        .tab-btn.active .tab-count { background: rgba(255,255,255,.2); }

        .banners-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .banner-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all .3s;
            border: 2px solid transparent;
        }
        .banner-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,.15); }
        .banner-card.inactive { opacity: .6; border-color: var(--danger); }
        .banner-card-img { width: 100%; aspect-ratio: 21/9; object-fit: cover; display: block; background: #eee; }
        .banner-card.hero-type .banner-card-img { aspect-ratio: 21/9; }
        .banner-card-body { padding: 1.25rem; }
        .banner-card-title { font-weight: 700; font-size: 1rem; margin-bottom: .25rem; }
        .banner-card-meta { font-size: .8rem; color: var(--text-gray); margin-bottom: .75rem; }
        .banner-card-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        .banner-type-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .15rem .6rem; border-radius: 6px; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .badge-hero { background: linear-gradient(135deg, var(--accent-gold), #d4a847); color: #fff; }
        .badge-section { background: rgba(79,158,255,.1); color: var(--accent-blue); }

        /* Hero fields toggle */
        .hero-fields { display: none; }
        .hero-fields.show { display: block; }
        .section-fields { display: none; }
        .section-fields.show { display: block; }

        /* Size recommendation */
        .size-rec { background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 10px; padding: .75rem 1rem; font-size: .82rem; color: #92400e; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
        .size-rec i { color: #f59e0b; }

        .toast { position: fixed; top: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 12px; color: #fff; font-weight: 600; z-index: 9999; transform: translateX(120%); transition: transform .4s; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
        .toast.show { transform: translateX(0); }
        .toast-success { background: linear-gradient(135deg, var(--success), #059669); }
        .toast-error   { background: linear-gradient(135deg, var(--danger), #dc2626); }

        @media (max-width: 1024px) {
            .sidebar { width: 70px; }
            .sidebar .logo, .sidebar .admin-badge, .sidebar .nav-item span, .sidebar .logout-btn span { display: none; }
            .sidebar-header { padding: 1rem .5rem; text-align: center; }
            .nav-item { justify-content: center; padding: .75rem; }
            .main-content { margin-left: 70px; }
            .form-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 1rem; } .banners-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo"><i class="fas fa-shopping-bag"></i> POSHY</div>
        <div class="admin-badge"><i class="fas fa-shield-alt"></i> ADMIN PANEL</div>
    </div>
    <div class="sidebar-nav">
        <a href="admin_panel.php"       class="nav-item"><i class="fas fa-box"></i><span>Orders Management</span></a>
        <a href="manage_products.php"   class="nav-item"><i class="fas fa-tag"></i><span>Products</span></a>
        <a href="add_product.php"       class="nav-item"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="manage_coupons.php"    class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="manage_categories.php" class="nav-item"><i class="fas fa-layer-group"></i><span>Categories</span></a>
        <a href="manage_brands.php"     class="nav-item"><i class="fas fa-copyright"></i><span>Brands</span></a>
        <a href="manage_banners.php"    class="nav-item active"><i class="fas fa-images"></i><span>Homepage Banners</span></a>
        <a href="daily_reports.php"     class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="../../index.php"       class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-images"></i> Homepage Banners</h1>
        <a href="admin_panel.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <!-- Upload Form -->
    <div class="form-card">
        <h2><i class="fas fa-cloud-upload-alt"></i> Upload New Banner</h2>

        <?php if ($has_banner_type): ?>
        <!-- Banner Type Toggle -->
        <div class="type-toggle">
            <input type="radio" name="banner_type_radio" id="typeHero" value="hero" checked>
            <label for="typeHero" class="type-hero-label" onclick="setBannerType('hero')">
                <i class="fas fa-film"></i> Hero Slider
            </label>
            <input type="radio" name="banner_type_radio" id="typeSection" value="section">
            <label for="typeSection" class="type-section-label" onclick="setBannerType('section')">
                <i class="fas fa-layer-group"></i> Section Banner
            </label>
        </div>
        <?php endif; ?>

        <!-- Size recommendation -->
        <div class="size-rec" id="sizeRec">
            <i class="fas fa-info-circle"></i>
            <span id="sizeRecText">Recommended: <strong>1600×500px</strong> for hero slider. Max 10MB. Use high-quality imagery for a premium look.</span>
        </div>

        <form id="uploadBannerForm" enctype="multipart/form-data">
            <input type="hidden" name="banner_type" id="bannerTypeField" value="hero">
            <div class="form-row">
                <div>
                    <div class="form-group">
                        <label>Banner Title (English)</label>
                        <input type="text" name="title" id="bannerTitle" placeholder="e.g. The New Glow Collection">
                        <div class="help-text hero-fields show">For hero: overlaid on image. Leave empty for image-only banners.</div>
                    </div>
                    <div class="form-group">
                        <label>Banner Title (Arabic)</label>
                        <input type="text" name="title_ar" id="bannerTitleAr" placeholder="مثال: مجموعة البشرة الجديدة" dir="rtl">
                    </div>

                    <?php if ($has_banner_type): ?>
                    <!-- Hero-specific fields -->
                    <div class="hero-fields show" id="heroFields">
                        <div class="form-group">
                            <label>Subtitle / Tag line (English)</label>
                            <input type="text" name="subtitle" placeholder="e.g. Luxury Beauty Edit">
                            <div class="help-text">Small text above the title. Leave empty to hide.</div>
                        </div>
                        <div class="form-group">
                            <label>Subtitle (Arabic)</label>
                            <input type="text" name="subtitle_ar" placeholder="مثال: عناية فاخرة" dir="rtl">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>CTA Button Text (English)</label>
                                <input type="text" name="cta_text" placeholder="e.g. Shop Now">
                            </div>
                            <div class="form-group">
                                <label>CTA Button Text (Arabic)</label>
                                <input type="text" name="cta_text_ar" placeholder="تسوقي الآن" dir="rtl">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" value="0" min="0">
                            <div class="help-text">Lower number = appears first in slider</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Link URL (optional)</label>
                        <input type="text" name="link_url" id="bannerLink" placeholder="e.g. index.php?category=1">
                        <div class="help-text">Where to go when banner is clicked.</div>
                    </div>

                    <?php if ($has_banner_type): ?>
                    <!-- Section-specific fields -->
                    <div class="section-fields" id="sectionFields">
                        <div class="form-group">
                            <label>Position (between which sections)</label>
                            <select name="position" id="bannerPosition">
                                <?php foreach ($categories as $idx => $cat): ?>
                                    <option value="<?= $idx ?>"><?= "After " . htmlspecialchars($cat['name_en']) . " section" ?></option>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                    <option value="0">Position 1</option>
                                    <option value="1">Position 2</option>
                                    <option value="2">Position 3</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label>Position (between which sections)</label>
                        <select name="position" id="bannerPosition">
                            <?php foreach ($categories as $idx => $cat): ?>
                                <option value="<?= $idx ?>"><?= "After " . htmlspecialchars($cat['name_en']) . " section" ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                                <option value="0">Position 1</option>
                                <option value="1">Position 2</option>
                                <option value="2">Position 3</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="form-group">
                        <label>Banner Image <span style="color:red">*</span></label>
                        <div class="file-upload-zone" id="uploadZone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click or drag to upload image</p>
                            <p style="font-size: .72rem; color: #aaa;" id="sizeHint">Recommended: 1600×500px • Max 10MB</p>
                            <input type="file" name="banner_image" id="bannerFile" accept="image/*" required>
                            <img class="file-preview" id="filePreview" alt="Preview">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1rem;">
                        <i class="fas fa-upload"></i> Upload Banner
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Banners List -->
    <div class="form-card">
        <?php if ($has_banner_type): ?>
        <!-- Tab navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('hero', this)">
                <i class="fas fa-film"></i> Hero Slider
                <span class="tab-count"><?= count($hero_banners) ?></span>
            </button>
            <button class="tab-btn" onclick="showTab('section', this)">
                <i class="fas fa-layer-group"></i> Section Banners
                <span class="tab-count"><?= count($section_banners) ?></span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Hero banners tab -->
        <div id="tab-hero" class="tab-content">
            <h2 style="margin-bottom: 1rem;"><i class="fas fa-film"></i> Hero Slider Banners (<?= count($hero_banners) ?>)</h2>
            <?php if (empty($hero_banners)): ?>
                <p style="color: var(--text-gray); text-align: center; padding: 2rem;">
                    <i class="fas fa-image" style="font-size: 2rem; display: block; margin-bottom: .5rem; color: var(--accent-gold);"></i>
                    No hero banners yet. Upload one above with "Hero Slider" type to display in the top banner slider.
                </p>
            <?php else: ?>
                <div class="banners-grid" id="heroBannersGrid">
                    <?php foreach ($hero_banners as $banner): ?>
                    <div class="banner-card hero-type <?= $banner['is_active'] ? '' : 'inactive' ?>" id="bannerCard-<?= $banner['id'] ?>">
                        <img src="../../<?= htmlspecialchars($banner['image_path']) ?>" alt="<?= htmlspecialchars($banner['title'] ?? 'Banner') ?>" class="banner-card-img" loading="lazy">
                        <div class="banner-card-body">
                            <div style="display: flex; align-items: center; gap: .5rem; margin-bottom: .25rem;">
                                <span class="banner-type-badge badge-hero"><i class="fas fa-film"></i> Hero</span>
                                <span class="banner-card-title"><?= htmlspecialchars($banner['title'] ?: 'Image-only Banner') ?></span>
                            </div>
                            <div class="banner-card-meta">
                                Sort: <?= $banner['sort_order'] ?? 0 ?> •
                                <?= $banner['is_active'] ? '<span style="color:var(--success);">Active</span>' : '<span style="color:var(--danger);">Inactive</span>' ?>
                                <?php if ($banner['link_url']): ?>
                                    • <a href="<?= htmlspecialchars($banner['link_url']) ?>" style="color:var(--accent-blue);">🔗 Link</a>
                                <?php endif; ?>
                            </div>
                            <div class="banner-card-actions">
                                <button class="btn btn-sm <?= $banner['is_active'] ? 'btn-warning' : 'btn-success' ?>" onclick="toggleBanner(<?= $banner['id'] ?>, this)">
                                    <i class="fas <?= $banner['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                    <?= $banner['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteBanner(<?= $banner['id'] ?>, this)">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section banners tab -->
        <div id="tab-section" class="tab-content" style="display: none;">
            <h2 style="margin-bottom: 1rem;"><i class="fas fa-layer-group"></i> Section Banners (<?= count($section_banners) ?>)</h2>
            <?php if (empty($section_banners)): ?>
                <p style="color: var(--text-gray); text-align: center; padding: 2rem;">No section banners uploaded yet.</p>
            <?php else: ?>
                <div class="banners-grid" id="sectionBannersGrid">
                    <?php foreach ($section_banners as $banner): ?>
                    <div class="banner-card <?= $banner['is_active'] ? '' : 'inactive' ?>" id="bannerCard-<?= $banner['id'] ?>">
                        <img src="../../<?= htmlspecialchars($banner['image_path']) ?>" alt="<?= htmlspecialchars($banner['title'] ?? 'Banner') ?>" class="banner-card-img" loading="lazy">
                        <div class="banner-card-body">
                            <div style="display: flex; align-items: center; gap: .5rem; margin-bottom: .25rem;">
                                <span class="banner-type-badge badge-section"><i class="fas fa-layer-group"></i> Section</span>
                                <span class="banner-card-title"><?= htmlspecialchars($banner['title'] ?: 'Untitled Banner') ?></span>
                            </div>
                            <div class="banner-card-meta">
                                Position: <?= $banner['position'] ?? 0 ?> •
                                <?= $banner['is_active'] ? '<span style="color:var(--success);">Active</span>' : '<span style="color:var(--danger);">Inactive</span>' ?>
                                <?php if ($banner['link_url']): ?>
                                    • <a href="<?= htmlspecialchars($banner['link_url']) ?>" style="color:var(--accent-blue);">🔗 Link</a>
                                <?php endif; ?>
                            </div>
                            <div class="banner-card-actions">
                                <button class="btn btn-sm <?= $banner['is_active'] ? 'btn-warning' : 'btn-success' ?>" onclick="toggleBanner(<?= $banner['id'] ?>, this)">
                                    <i class="fas <?= $banner['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                    <?= $banner['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteBanner(<?= $banner['id'] ?>, this)">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 4000);
}

// Banner type toggle
let selectedType = 'hero';
function setBannerType(type) {
    selectedType = type;
    document.getElementById('bannerTypeField').value = type;
    const heroFields = document.getElementById('heroFields');
    const sectionFields = document.getElementById('sectionFields');
    const sizeRecText = document.getElementById('sizeRecText');
    const sizeHint = document.getElementById('sizeHint');

    if (type === 'hero') {
        if (heroFields) heroFields.classList.add('show');
        if (sectionFields) sectionFields.classList.remove('show');
        if (sizeRecText) sizeRecText.innerHTML = 'Recommended: <strong>1600×500px</strong> for hero slider. Max 10MB. Use high-quality imagery for a premium look.';
        if (sizeHint) sizeHint.textContent = 'Recommended: 1600×500px • Max 10MB';
    } else {
        if (heroFields) heroFields.classList.remove('show');
        if (sectionFields) sectionFields.classList.add('show');
        if (sizeRecText) sizeRecText.innerHTML = 'Recommended: <strong>1400×400px</strong> for section banners. Max 10MB.';
        if (sizeHint) sizeHint.textContent = 'Recommended: 1400×400px • Max 10MB';
    }
}

// Tab system
function showTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

// File preview
document.getElementById('bannerFile').addEventListener('change', function() {
    const preview = document.getElementById('filePreview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// Drag & drop
const zone = document.getElementById('uploadZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        document.getElementById('bannerFile').files = e.dataTransfer.files;
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('filePreview').src = ev.target.result;
            document.getElementById('filePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

// Upload form
document.getElementById('uploadBannerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'upload_banner');

    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    fetch('manage_banners.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Banner uploaded successfully!');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.error || 'Upload failed', 'error');
            }
        })
        .catch(() => showToast('Network error', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload"></i> Upload Banner';
        });
});

function toggleBanner(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle_banner');
    fd.append('id', id);
    btn.disabled = true;

    fetch('manage_banners.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('bannerCard-' + id);
                if (data.is_active) {
                    card.classList.remove('inactive');
                    btn.className = 'btn btn-sm btn-warning';
                    btn.innerHTML = '<i class="fas fa-eye-slash"></i> Deactivate';
                } else {
                    card.classList.add('inactive');
                    btn.className = 'btn btn-sm btn-success';
                    btn.innerHTML = '<i class="fas fa-eye"></i> Activate';
                }
                showToast(data.is_active ? 'Banner activated' : 'Banner deactivated');
            } else {
                showToast(data.error, 'error');
            }
        })
        .finally(() => btn.disabled = false);
}

function deleteBanner(id, btn) {
    if (!confirm('Delete this banner permanently?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_banner');
    fd.append('id', id);
    btn.disabled = true;

    fetch('manage_banners.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('bannerCard-' + id)?.remove();
                showToast('Banner deleted');
            } else {
                showToast(data.error, 'error');
                btn.disabled = false;
            }
        });
}
</script>
</body>
</html>
