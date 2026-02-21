<?php
/**
 * Podcast Management - Admin Panel
 * Allows admins to create, edit, and manage podcasts
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

$upload_dir = __DIR__ . '/../../uploads/podcasts/';
$thumb_dir  = $upload_dir . 'thumbnails/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
if (!is_dir($thumb_dir))  mkdir($thumb_dir, 0755, true);

// Ensure DB tables exist
$conn->query("CREATE TABLE IF NOT EXISTS podcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    url_path VARCHAR(255) NOT NULL,
    main_photo VARCHAR(500),
    status ENUM('draft','published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_url_path (url_path),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS podcast_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    podcast_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (podcast_id) REFERENCES podcasts(id) ON DELETE CASCADE,
    INDEX idx_podcast_id (podcast_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function uploadPodcastImage($file, $prefix = 'podcast') {
    global $upload_dir;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) return ['success' => false, 'error' => 'Invalid image type'];
    if ($file['size'] > 10 * 1024 * 1024)   return ['success' => false, 'error' => 'File too large (max 10MB)'];
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = $prefix . '_' . uniqid() . '.' . $ext;
    $dest = $upload_dir . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => true, 'path' => 'uploads/podcasts/' . $name];
    }
    return ['success' => false, 'error' => 'Upload failed'];
}

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create_podcast') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $meta_title  = trim($_POST['meta_title'] ?? '');
        $meta_desc   = trim($_POST['meta_description'] ?? '');
        $url_path    = trim($_POST['url_path'] ?? '');
        $status      = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if (empty($title)) { echo json_encode(['success'=>false,'error'=>'Title is required']); exit(); }

        if (empty($url_path)) {
            $url_path = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
            $url_path = trim($url_path, '-');
        }
        $url_path = strtolower(preg_replace('/[^a-z0-9\-]/', '', $url_path));

        $check = $conn->prepare("SELECT id FROM podcasts WHERE url_path = ?");
        $check->bind_param('s', $url_path);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success'=>false,'error'=>'URL path already exists']);
            $check->close(); exit();
        }
        $check->close();

        $main_photo = '';
        if (!empty($_FILES['main_photo']['name'])) {
            $upload = uploadPodcastImage($_FILES['main_photo'], 'main');
            if ($upload['success']) { $main_photo = $upload['path']; }
            else { echo json_encode($upload); exit(); }
        }

        $sql = "INSERT INTO podcasts (title, description, meta_title, meta_description, url_path, main_photo, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssss', $title, $description, $meta_title, $meta_desc, $url_path, $main_photo, $status);

        if ($stmt->execute()) {
            $podcast_id = $stmt->insert_id;
            $stmt->close();

            if (!empty($_FILES['gallery_images']['name'][0])) {
                $files = $_FILES['gallery_images'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file_data = [
                        'name' => $files['name'][$i], 'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $upload = uploadPodcastImage($file_data, 'gallery');
                    if ($upload['success']) {
                        $img_stmt = $conn->prepare("INSERT INTO podcast_images (podcast_id, image_path, sort_order) VALUES (?, ?, ?)");
                        $img_stmt->bind_param('isi', $podcast_id, $upload['path'], $i);
                        $img_stmt->execute();
                        $img_stmt->close();
                    }
                }
            }
            echo json_encode(['success'=>true, 'message'=>'Podcast created successfully', 'id'=>$podcast_id]);
        } else {
            echo json_encode(['success'=>false, 'error'=>'Failed: '.$stmt->error]);
            $stmt->close();
        }
        exit();
    }

    if ($action === 'update_podcast') {
        $id          = intval($_POST['podcast_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $meta_title  = trim($_POST['meta_title'] ?? '');
        $meta_desc   = trim($_POST['meta_description'] ?? '');
        $url_path    = trim($_POST['url_path'] ?? '');
        $status      = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if ($id <= 0 || empty($title)) { echo json_encode(['success'=>false,'error'=>'Invalid data']); exit(); }

        $url_path = strtolower(preg_replace('/[^a-z0-9\-]/', '', $url_path));

        $check = $conn->prepare("SELECT id FROM podcasts WHERE url_path = ? AND id != ?");
        $check->bind_param('si', $url_path, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success'=>false,'error'=>'URL path already used']);
            $check->close(); exit();
        }
        $check->close();

        $photo_sql = '';
        $params    = [$title, $description, $meta_title, $meta_desc, $url_path, $status];
        $types     = 'ssssss';

        if (!empty($_FILES['main_photo']['name'])) {
            $upload = uploadPodcastImage($_FILES['main_photo'], 'main');
            if ($upload['success']) {
                $photo_sql = ', main_photo = ?';
                $params[]  = $upload['path'];
                $types    .= 's';
            }
        }

        $params[] = $id;
        $types   .= 'i';

        $sql = "UPDATE podcasts SET title=?, description=?, meta_title=?, meta_description=?, url_path=?, status=?{$photo_sql} WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $stmt->close();
            if (!empty($_FILES['gallery_images']['name'][0])) {
                $max_order = $conn->query("SELECT COALESCE(MAX(sort_order),0)+1 as next FROM podcast_images WHERE podcast_id=$id")->fetch_assoc()['next'];
                $files = $_FILES['gallery_images'];
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $file_data = [
                        'name' => $files['name'][$i], 'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $upload = uploadPodcastImage($file_data, 'gallery');
                    if ($upload['success']) {
                        $order = $max_order + $i;
                        $img_stmt = $conn->prepare("INSERT INTO podcast_images (podcast_id, image_path, sort_order) VALUES (?, ?, ?)");
                        $img_stmt->bind_param('isi', $id, $upload['path'], $order);
                        $img_stmt->execute();
                        $img_stmt->close();
                    }
                }
            }
            echo json_encode(['success'=>true, 'message'=>'Podcast updated successfully']);
        } else {
            echo json_encode(['success'=>false, 'error'=>'Update failed: '.$stmt->error]);
            $stmt->close();
        }
        exit();
    }

    if ($action === 'delete_podcast') {
        $id = intval($_POST['podcast_id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid ID']); exit(); }

        $imgs = $conn->query("SELECT image_path FROM podcast_images WHERE podcast_id=$id");
        while ($img = $imgs->fetch_assoc()) {
            $path = __DIR__ . '/../../' . $img['image_path'];
            if (file_exists($path)) unlink($path);
        }
        $main = $conn->query("SELECT main_photo FROM podcasts WHERE id=$id")->fetch_assoc();
        if ($main && $main['main_photo']) {
            $path = __DIR__ . '/../../' . $main['main_photo'];
            if (file_exists($path)) unlink($path);
        }

        $stmt = $conn->prepare("DELETE FROM podcasts WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true, 'message'=>'Podcast deleted']);
        } else {
            echo json_encode(['success'=>false, 'error'=>'Delete failed']);
        }
        $stmt->close();
        exit();
    }

    if ($action === 'delete_gallery_image') {
        $img_id = intval($_POST['image_id'] ?? 0);
        $img = $conn->query("SELECT image_path FROM podcast_images WHERE id=$img_id")->fetch_assoc();
        if ($img) {
            $path = __DIR__ . '/../../' . $img['image_path'];
            if (file_exists($path)) unlink($path);
            $conn->query("DELETE FROM podcast_images WHERE id=$img_id");
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false,'error'=>'Image not found']);
        }
        exit();
    }

    if ($action === 'toggle_status') {
        $id = intval($_POST['podcast_id'] ?? 0);
        $conn->query("UPDATE podcasts SET status = IF(status='published','draft','published') WHERE id=$id");
        echo json_encode(['success'=>true]);
        exit();
    }

    echo json_encode(['success'=>false,'error'=>'Invalid action']);
    exit();
}

// Get podcasts
$podcasts = [];
$result = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM podcast_images WHERE podcast_id=p.id) as image_count FROM podcasts p ORDER BY p.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) $podcasts[] = $row;
}

$edit_podcast = null;
$edit_images  = [];
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM podcasts WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_podcast = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($edit_podcast) {
        $img_result = $conn->query("SELECT * FROM podcast_images WHERE podcast_id=$edit_id ORDER BY sort_order ASC");
        while ($img = $img_result->fetch_assoc()) $edit_images[] = $img;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podcast Management - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a1d2e; --secondary-dark: #242838; --accent-blue: #4f9eff;
            --accent-teal: #00d4aa; --accent-purple: #a855f7; --text-light: #fff;
            --text-gray: #9ca3af; --text-dark: #1f2937; --success: #10b981;
            --warning: #f59e0b; --danger: #ef4444; --bg-light: #f9fafb;
            --border-color: #e5e7eb; --shadow-md: 0 4px 6px rgba(0,0,0,.1);
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
        .logout-btn:hover { transform: translateY(-2px); }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; overflow-x: hidden; }
        .page-header { background: #fff; padding: 1.75rem 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { font-size: 1.875rem; font-weight: 700; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .form-card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); margin-bottom: 2rem; }
        .form-card h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; color: var(--primary-dark); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: .5rem; font-size: .875rem; }
        .form-group label .required { color: var(--danger); }
        .form-group input[type="text"], .form-group textarea, .form-group select { width: 100%; padding: .75rem 1rem; border: 2px solid var(--border-color); border-radius: 10px; font-size: .925rem; font-family: inherit; transition: border-color .3s; background: var(--bg-light); }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 3px rgba(79,158,255,.15); }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .form-group .help-text { font-size: .8rem; color: var(--text-gray); margin-top: .35rem; }
        .image-upload-area { border: 2px dashed var(--border-color); border-radius: 12px; padding: 2rem; text-align: center; cursor: pointer; transition: all .3s; position: relative; background: var(--bg-light); }
        .image-upload-area:hover { border-color: var(--accent-blue); }
        .image-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .image-upload-area i { font-size: 2.5rem; color: var(--accent-blue); margin-bottom: .75rem; }
        .image-upload-area p { color: var(--text-gray); font-size: .9rem; }
        .image-preview { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        .image-preview-item { width: 120px; height: 120px; border-radius: 10px; overflow: hidden; position: relative; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .image-preview-item .remove-btn { position: absolute; top: 4px; right: 4px; background: var(--danger); color: #fff; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: .75rem; opacity: 0; transition: opacity .3s; }
        .image-preview-item:hover .remove-btn { opacity: 1; }
        .btn { padding: .75rem 1.5rem; border: none; border-radius: 10px; font-weight: 600; font-size: .925rem; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem; transition: all .3s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-blue), #3b82f6); color: #fff; box-shadow: 0 4px 12px rgba(79,158,255,.3); }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-success { background: linear-gradient(135deg, var(--success), #059669); color: #fff; }
        .btn-danger { background: linear-gradient(135deg, var(--danger), #dc2626); color: #fff; }
        .btn-secondary { background: var(--bg-light); color: var(--text-dark); border: 2px solid var(--border-color); }
        .btn-sm { padding: .5rem 1rem; font-size: .825rem; }
        .btn-actions { display: flex; gap: .75rem; margin-top: 1.5rem; }
        .podcast-table { width: 100%; border-collapse: collapse; }
        .podcast-table th { background: var(--bg-light); padding: 1rem; text-align: left; font-weight: 600; font-size: .85rem; color: var(--text-gray); text-transform: uppercase; border-bottom: 2px solid var(--border-color); }
        .podcast-table td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .podcast-table tr:hover { background: rgba(79,158,255,.02); }
        .podcast-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; }
        .podcast-thumb-placeholder { width: 60px; height: 60px; border-radius: 8px; background: var(--bg-light); display: flex; align-items: center; justify-content: center; color: var(--text-gray); font-size: 1.5rem; }
        .status-badge { padding: .35rem .75rem; border-radius: 20px; font-size: .75rem; font-weight: 600; display: inline-block; }
        .status-published { background: rgba(16,185,129,.1); color: var(--success); }
        .status-draft { background: rgba(245,158,11,.1); color: var(--warning); }
        .table-actions { display: flex; gap: .5rem; }
        .toast { position: fixed; top: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 12px; color: #fff; font-weight: 600; z-index: 9999; transform: translateX(120%); transition: transform .4s; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
        .toast.show { transform: translateX(0); }
        .toast-success { background: linear-gradient(135deg, var(--success), #059669); }
        .toast-error { background: linear-gradient(135deg, var(--danger), #dc2626); }
        .loading-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 9998; align-items: center; justify-content: center; }
        .loading-overlay.active { display: flex; }
        .spinner { width: 50px; height: 50px; border: 4px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state i { font-size: 4rem; color: var(--text-gray); margin-bottom: 1rem; }
        @media (max-width: 1024px) { .sidebar { width: 70px; } .sidebar .logo, .sidebar .admin-badge, .sidebar .nav-item span, .sidebar .logout-btn span { display: none; } .sidebar-header { padding: 1rem .5rem; text-align: center; } .nav-item { justify-content: center; padding: .75rem; } .main-content { margin-left: 70px; } .form-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 1rem; } }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo"><i class="fas fa-shopping-bag"></i> POSHY</div>
        <div class="admin-badge"><i class="fas fa-shield-alt"></i> ADMIN PANEL</div>
    </div>
    <div class="sidebar-nav">
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-box"></i><span>Orders Management</span></a>
        <a href="admin_panel.php" class="nav-item"><i class="fas fa-tag"></i><span>Products & Pricing</span></a>
        <a href="add_product.php" class="nav-item"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="manage_coupons.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="manage_podcasts.php" class="nav-item active"><i class="fas fa-podcast"></i><span>Podcast Management</span></a>
        <a href="daily_reports.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="../../index.php" class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer"><a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-podcast"></i> Podcast Management</h1>
        <a href="admin_panel.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="form-card" id="podcastForm">
        <h2>
            <i class="fas fa-<?php echo $edit_podcast ? 'edit' : 'plus-circle'; ?>"></i>
            <?php echo $edit_podcast ? 'Edit Podcast' : 'Add New Podcast'; ?>
        </h2>
        <form id="podcastFormEl" enctype="multipart/form-data">
            <?php if ($edit_podcast): ?>
                <input type="hidden" name="podcast_id" value="<?php echo $edit_podcast['id']; ?>">
            <?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" name="title" id="podcastTitle" required value="<?php echo htmlspecialchars($edit_podcast['title'] ?? ''); ?>" placeholder="Enter podcast title">
                </div>
                <div class="form-group">
                    <label>URL Path</label>
                    <input type="text" name="url_path" id="urlPath" value="<?php echo htmlspecialchars($edit_podcast['url_path'] ?? ''); ?>" placeholder="auto-generated-from-title">
                    <div class="help-text">Leave empty to auto-generate from title.</div>
                </div>
                <div class="form-group">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" value="<?php echo htmlspecialchars($edit_podcast['meta_title'] ?? ''); ?>" placeholder="SEO meta title">
                    <div class="help-text">Max 60 characters recommended.</div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" <?php echo ($edit_podcast['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($edit_podcast['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Meta Description</label>
                    <textarea name="meta_description" rows="2" placeholder="SEO meta description (max 160 chars)"><?php echo htmlspecialchars($edit_podcast['meta_description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Description</label>
                    <textarea name="description" rows="5" placeholder="Full podcast description..."><?php echo htmlspecialchars($edit_podcast['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Main Photo</label>
                    <div class="image-upload-area" id="mainPhotoArea">
                        <input type="file" name="main_photo" accept="image/*" id="mainPhotoInput">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click or drag to upload main photo</p>
                    </div>
                    <div class="image-preview" id="mainPhotoPreview">
                        <?php if (!empty($edit_podcast['main_photo'])): ?>
                            <div class="image-preview-item"><img src="../../<?php echo htmlspecialchars($edit_podcast['main_photo']); ?>" alt="Main"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Gallery Images</label>
                    <div class="image-upload-area" id="galleryArea">
                        <input type="file" name="gallery_images[]" accept="image/*" multiple id="galleryInput">
                        <i class="fas fa-images"></i>
                        <p>Click or drag to upload gallery images</p>
                    </div>
                    <div class="image-preview" id="galleryPreview">
                        <?php foreach ($edit_images as $img): ?>
                            <div class="image-preview-item" data-image-id="<?php echo $img['id']; ?>">
                                <img src="../../<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery">
                                <button type="button" class="remove-btn" onclick="deleteGalleryImage(<?php echo $img['id']; ?>, this)"><i class="fas fa-times"></i></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="btn-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_podcast ? 'Update Podcast' : 'Create Podcast'; ?></button>
                <?php if ($edit_podcast): ?><a href="manage_podcasts.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="form-card">
        <h2><i class="fas fa-list"></i> All Podcasts (<?php echo count($podcasts); ?>)</h2>
        <?php if (empty($podcasts)): ?>
            <div class="empty-state"><i class="fas fa-podcast"></i><h3>No Podcasts Yet</h3><p>Create your first podcast using the form above.</p></div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="podcast-table">
                    <thead><tr><th>Image</th><th>Title</th><th>URL Path</th><th>Images</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($podcasts as $p): ?>
                        <tr>
                            <td><?php if ($p['main_photo']): ?><img src="../../<?php echo htmlspecialchars($p['main_photo']); ?>" class="podcast-thumb" alt=""><?php else: ?><div class="podcast-thumb-placeholder"><i class="fas fa-podcast"></i></div><?php endif; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                            <td><code>/podcast/<?php echo htmlspecialchars($p['url_path']); ?></code></td>
                            <td><?php echo $p['image_count']; ?> photos</td>
                            <td><span class="status-badge status-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
                            <td><div class="table-actions">
                                <a href="manage_podcasts.php?edit=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                                <button class="btn btn-sm <?php echo $p['status']==='published'?'btn-secondary':'btn-success'; ?>" onclick="toggleStatus(<?php echo $p['id']; ?>)"><i class="fas fa-<?php echo $p['status']==='published'?'eye-slash':'eye'; ?>"></i></button>
                                <a href="../../podcast.php?slug=<?php echo htmlspecialchars($p['url_path']); ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i></a>
                                <button class="btn btn-danger btn-sm" onclick="deletePodcast(<?php echo $p['id']; ?>,'<?php echo addslashes($p['title']); ?>')"><i class="fas fa-trash"></i></button>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="toast" id="toast"></div>
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>

<script>
function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 3500);
}
function showLoading(on) {
    document.getElementById('loadingOverlay').classList.toggle('active', on);
}

document.getElementById('podcastTitle').addEventListener('input', function() {
    const u = document.getElementById('urlPath');
    if (!u.dataset.manual) {
        u.value = this.value.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    }
});
document.getElementById('urlPath').addEventListener('input', function() { this.dataset.manual = '1'; });

document.getElementById('mainPhotoInput').addEventListener('change', function() {
    const p = document.getElementById('mainPhotoPreview');
    p.innerHTML = '';
    if (this.files[0]) {
        const d = document.createElement('div'); d.className = 'image-preview-item';
        const i = document.createElement('img'); i.src = URL.createObjectURL(this.files[0]);
        d.appendChild(i); p.appendChild(d);
    }
});

document.getElementById('galleryInput').addEventListener('change', function() {
    const p = document.getElementById('galleryPreview');
    p.querySelectorAll('.local-preview').forEach(e => e.remove());
    Array.from(this.files).forEach(f => {
        const d = document.createElement('div'); d.className = 'image-preview-item local-preview';
        const i = document.createElement('img'); i.src = URL.createObjectURL(f);
        d.appendChild(i); p.appendChild(d);
    });
});

document.getElementById('podcastFormEl').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('ajax', '1');
    fd.append('action', fd.get('podcast_id') ? 'update_podcast' : 'create_podcast');
    showLoading(true);
    fetch('manage_podcasts.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            showLoading(false);
            if (data.success) { showToast(data.message); setTimeout(() => window.location.href = 'manage_podcasts.php', 1200); }
            else { showToast(data.error || 'Something went wrong', 'error'); }
        })
        .catch(() => { showLoading(false); showToast('Network error', 'error'); });
});

function toggleStatus(id) {
    const fd = new FormData();
    fd.append('ajax', '1'); fd.append('action', 'toggle_status'); fd.append('podcast_id', id);
    fetch('manage_podcasts.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.success) location.reload(); else showToast(d.error || 'Failed', 'error'); });
}

function deletePodcast(id, title) {
    if (!confirm('Delete "' + title + '"? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('ajax', '1'); fd.append('action', 'delete_podcast'); fd.append('podcast_id', id);
    showLoading(true);
    fetch('manage_podcasts.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        showLoading(false);
        if (d.success) { showToast('Podcast deleted'); setTimeout(() => location.reload(), 1000); }
        else showToast(d.error || 'Failed', 'error');
    });
}

function deleteGalleryImage(imgId, btn) {
    if (!confirm('Remove this image?')) return;
    const fd = new FormData();
    fd.append('ajax', '1'); fd.append('action', 'delete_gallery_image'); fd.append('image_id', imgId);
    fetch('manage_podcasts.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.success) btn.closest('.image-preview-item').remove();
        else showToast('Failed to delete image', 'error');
    });
}

<?php if ($edit_podcast): ?>
document.getElementById('urlPath').dataset.manual = '1';
<?php endif; ?>
</script>
</body>
</html>
