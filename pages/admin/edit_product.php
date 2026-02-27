<?php
/**
 * Edit Product – Admin Panel
 * Full edit of all product fields
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/product_image_helper.php';

if (!isAdmin()) {
    header('Location: ../../index.php');
    exit();
}

$product_id = intval($_GET['id'] ?? 0);
if (!$product_id) {
    header('Location: admin_panel.php');
    exit();
}

// ─── AJAX handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // ── Delete product handler ──
    if (($_POST['action'] ?? '') === 'delete_product') {
        $del_id = intval($_POST['product_id'] ?? 0);
        try {
            $chk = $conn->prepare('SELECT COUNT(*) AS cnt FROM order_items WHERE product_id = ?');
            $chk->bind_param('i', $del_id);
            $chk->execute();
            $order_count = $chk->get_result()->fetch_assoc()['cnt'];
            $chk->close();
            if ($order_count > 0) {
                echo json_encode(['success' => false, 'error' => "Cannot delete: this product appears in $order_count order(s)."]);
                exit();
            }
            $conn->query("DELETE FROM product_tags WHERE product_id = $del_id");
            $conn->query("DELETE FROM cart WHERE product_id = $del_id");
            $conn->query("DELETE FROM product_reviews WHERE product_id = $del_id");
            $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
            $stmt->bind_param('i', $del_id);
            if ($stmt->execute()) { $stmt->close(); echo json_encode(['success' => true]); }
            else { $stmt->close(); echo json_encode(['success' => false, 'error' => 'Delete failed']); }
        } catch (mysqli_sql_exception $e) {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
        }
        exit();
    }

    // ── Delete single image handler ──
    if (($_POST['action'] ?? '') === 'delete_image') {
        $pid = intval($_POST['product_id'] ?? 0);
        $img_file = trim($_POST['image_file'] ?? '');
        if (!$pid || !$img_file) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']); exit();
        }
        // Get product name for folder
        $pstmt = $conn->prepare('SELECT name_en, image_link FROM products WHERE id = ?');
        $pstmt->bind_param('i', $pid);
        $pstmt->execute();
        $prow = $pstmt->get_result()->fetch_assoc();
        $pstmt->close();
        if (!$prow) { echo json_encode(['success' => false, 'error' => 'Product not found']); exit(); }

        $img_base = __DIR__ . '/../../images/' . $prow['name_en'] . '/';
        $full_path = __DIR__ . '/../../' . $img_file;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        // Renumber remaining images sequentially
        if (is_dir($img_base)) {
            $remaining = glob($img_base . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
            sort($remaining);
            // Remove and re-add with correct numbers
            $temp_names = [];
            foreach ($remaining as $idx => $f) {
                $tmp = $img_base . 'tmp_rename_' . $idx . '_' . basename($f);
                rename($f, $tmp);
                $temp_names[] = $tmp;
            }
            foreach ($temp_names as $idx => $tmp) {
                $ext = pathinfo($tmp, PATHINFO_EXTENSION);
                $new_name = $img_base . ($idx + 1) . '.' . $ext;
                rename($tmp, $new_name);
            }
            // Update image_link to first image if it exists
            $new_files = glob($img_base . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
            sort($new_files);
            if (!empty($new_files)) {
                $new_link = 'images/' . $prow['name_en'] . '/' . basename($new_files[0]);
                $conn->query("UPDATE products SET image_link = '" . $conn->real_escape_string($new_link) . "' WHERE id = $pid");
            } else {
                $conn->query("UPDATE products SET image_link = '' WHERE id = $pid");
            }
        }
        // Return updated images list
        $updated_images = [];
        if (is_dir($img_base)) {
            $files = glob($img_base . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
            sort($files);
            foreach ($files as $f) {
                $updated_images[] = 'images/' . $prow['name_en'] . '/' . basename($f);
            }
        }
        echo json_encode(['success' => true, 'images' => $updated_images]);
        exit();
    }

    // ── Replace single image handler ──
    if (($_POST['action'] ?? '') === 'replace_image') {
        $pid = intval($_POST['product_id'] ?? 0);
        $img_file = trim($_POST['image_file'] ?? '');
        if (!$pid || !$img_file || empty($_FILES['new_image']['name'])) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']); exit();
        }
        $allowed_img = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['new_image']['type'], $allowed_img)) {
            echo json_encode(['success' => false, 'error' => 'Invalid image type']); exit();
        }
        $full_path = __DIR__ . '/../../' . $img_file;
        // Get the number from the old filename and keep the same path
        $dir = dirname($full_path) . '/';
        $old_base = pathinfo($full_path, PATHINFO_FILENAME);
        $new_ext = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION) ?: 'png';
        // Remove old file (may have different extension)
        foreach (glob($dir . $old_base . '.*') as $old_f) { unlink($old_f); }
        $new_path = $dir . $old_base . '.' . strtolower($new_ext);
        move_uploaded_file($_FILES['new_image']['tmp_name'], $new_path);

        // Get product name to build relative paths
        $pstmt = $conn->prepare('SELECT name_en FROM products WHERE id = ?');
        $pstmt->bind_param('i', $pid);
        $pstmt->execute();
        $prow = $pstmt->get_result()->fetch_assoc();
        $pstmt->close();

        // If replaced image #1, update image_link
        if ($old_base === '1' && $prow) {
            $new_link = 'images/' . $prow['name_en'] . '/1.' . strtolower($new_ext);
            $conn->query("UPDATE products SET image_link = '" . $conn->real_escape_string($new_link) . "' WHERE id = $pid");
        }

        // Return updated images list
        $updated_images = [];
        $img_base = $dir;
        $files = glob($img_base . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
        sort($files);
        foreach ($files as $f) {
            $updated_images[] = 'images/' . $prow['name_en'] . '/' . basename($f);
        }
        echo json_encode(['success' => true, 'images' => $updated_images]);
        exit();
    }

    // ── Add more images handler ──
    if (($_POST['action'] ?? '') === 'add_images') {
        $pid = intval($_POST['product_id'] ?? 0);
        if (!$pid || empty($_FILES['new_images']['name'][0])) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']); exit();
        }
        $pstmt = $conn->prepare('SELECT name_en, image_link FROM products WHERE id = ?');
        $pstmt->bind_param('i', $pid);
        $pstmt->execute();
        $prow = $pstmt->get_result()->fetch_assoc();
        $pstmt->close();
        if (!$prow) { echo json_encode(['success' => false, 'error' => 'Product not found']); exit(); }

        $img_base = __DIR__ . '/../../images/' . $prow['name_en'] . '/';
        if (!is_dir($img_base)) mkdir($img_base, 0755, true);

        // Find highest existing number
        $existing = glob($img_base . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
        $max_num = 0;
        foreach ($existing as $f) {
            $num = intval(pathinfo($f, PATHINFO_FILENAME));
            if ($num > $max_num) $max_num = $num;
        }

        $allowed_img = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $files = $_FILES['new_images'];
        $added = 0;
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if (!in_array($files['type'][$i], $allowed_img)) continue;
            $max_num++;
            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION) ?: 'png';
            $dest = $img_base . $max_num . '.' . strtolower($ext);
            move_uploaded_file($files['tmp_name'][$i], $dest);
            $added++;
            // If this is the first image ever, set image_link
            if (empty($prow['image_link']) && $max_num === 1) {
                $new_link = 'images/' . $prow['name_en'] . '/1.' . strtolower($ext);
                $conn->query("UPDATE products SET image_link = '" . $conn->real_escape_string($new_link) . "' WHERE id = $pid");
            }
        }

        // Return updated images list
        $updated_images = [];
        $files_list = glob($img_base . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
        sort($files_list);
        foreach ($files_list as $f) {
            $updated_images[] = 'images/' . $prow['name_en'] . '/' . basename($f);
        }
        echo json_encode(['success' => true, 'images' => $updated_images, 'added' => $added]);
        exit();
    }

    // ── Set main image handler ──
    if (($_POST['action'] ?? '') === 'set_main_image') {
        $pid = intval($_POST['product_id'] ?? 0);
        $img_file = trim($_POST['image_file'] ?? '');
        if (!$pid || !$img_file) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']); exit();
        }
        $pstmt = $conn->prepare('SELECT name_en FROM products WHERE id = ?');
        $pstmt->bind_param('i', $pid);
        $pstmt->execute();
        $prow = $pstmt->get_result()->fetch_assoc();
        $pstmt->close();
        if (!$prow) { echo json_encode(['success' => false, 'error' => 'Product not found']); exit(); }

        $img_base = __DIR__ . '/../../images/' . $prow['name_en'] . '/';
        $target_path = __DIR__ . '/../../' . $img_file;
        $target_num = intval(pathinfo($target_path, PATHINFO_FILENAME));
        $target_ext = pathinfo($target_path, PATHINFO_EXTENSION);

        if ($target_num === 1) {
            echo json_encode(['success' => true, 'images' => []]);
            exit();
        }

        // Swap: target becomes 1, old 1 takes target's number
        $old_main_files = glob($img_base . '1.*');
        $old_main = !empty($old_main_files) ? $old_main_files[0] : null;
        $old_main_ext = $old_main ? pathinfo($old_main, PATHINFO_EXTENSION) : 'png';

        // Temp rename to avoid collision
        if ($old_main && file_exists($old_main)) {
            rename($old_main, $img_base . 'tmp_swap_old.' . $old_main_ext);
        }
        rename($target_path, $img_base . '1.' . $target_ext);
        if (file_exists($img_base . 'tmp_swap_old.' . $old_main_ext)) {
            rename($img_base . 'tmp_swap_old.' . $old_main_ext, $img_base . $target_num . '.' . $old_main_ext);
        }

        // Update image_link
        $new_link = 'images/' . $prow['name_en'] . '/1.' . $target_ext;
        $conn->query("UPDATE products SET image_link = '" . $conn->real_escape_string($new_link) . "' WHERE id = $pid");

        // Return updated images list
        $updated_images = [];
        $files_list = glob($img_base . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
        sort($files_list);
        foreach ($files_list as $f) {
            $updated_images[] = 'images/' . $prow['name_en'] . '/' . basename($f);
        }
        echo json_encode(['success' => true, 'images' => $updated_images]);
        exit();
    }

    $pid        = intval($_POST['product_id'] ?? 0);
    $name_en    = trim($_POST['name_en'] ?? '');
    $name_ar    = trim($_POST['name_ar'] ?? '');
    $short_en   = trim($_POST['short_description_en'] ?? '');
    $short_ar   = trim($_POST['short_description_ar'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $desc_ar    = trim($_POST['description_ar'] ?? '');
    $details    = trim($_POST['product_details'] ?? '');
    $details_ar = trim($_POST['product_details_ar'] ?? '');
    $how_en     = trim($_POST['how_to_use_en'] ?? '');
    $how_ar     = trim($_POST['how_to_use_ar'] ?? '');
    $video_url  = trim($_POST['video_review_url'] ?? '');
    $price      = floatval($_POST['price_jod'] ?? 0);
    $stock      = intval($_POST['stock_quantity'] ?? 0);
    $subcat_id  = intval($_POST['subcategory_id'] ?? 0) ?: null;
    $brand_id   = intval($_POST['brand_id'] ?? 0) ?: null;
    $tags_raw   = trim($_POST['tags'] ?? '');
    $sup_cost   = ($_POST['supplier_price'] ?? '') !== '' ? floatval($_POST['supplier_price']) : null;
    $orig_price = ($_POST['original_price'] ?? '') !== '' ? floatval($_POST['original_price']) : $price;
    $discount   = floatval($_POST['discount_percentage'] ?? 0);
    $has_disc   = ($discount > 0) ? 1 : 0;

    if (empty($name_en)) { echo json_encode(['success' => false, 'error' => 'English name is required']); exit(); }
    if ($price <= 0)      { echo json_encode(['success' => false, 'error' => 'Price must be greater than 0']); exit(); }

    // Handle video file upload
    $video_url = trim($_POST['video_review_url'] ?? '');
    if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $allowed_vid = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'];
        if (in_array($_FILES['video_file']['type'], $allowed_vid)) {
            $vid_dir = __DIR__ . '/../../uploads/videos/';
            if (!is_dir($vid_dir)) mkdir($vid_dir, 0755, true);
            $ext      = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION) ?: 'mp4';
            $vid_name = uniqid('vid_') . '.' . strtolower($ext);
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $vid_dir . $vid_name)) {
                $video_url = 'uploads/videos/' . $vid_name;
            }
        }
    }

    // Build UPDATE query (images are now managed via separate AJAX actions)
    $sql = "UPDATE products SET
                name_en=?, name_ar=?, short_description_en=?, short_description_ar=?,
                description=?, description_ar=?, product_details=?, product_details_ar=?,
                how_to_use_en=?, how_to_use_ar=?, video_review_url=?,
                price_jod=?, stock_quantity=?, subcategory_id=?, brand_id=?,
                supplier_cost=?,
                original_price=?, discount_percentage=?, has_discount=?
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssssssdiiidddii',
        $name_en, $name_ar, $short_en, $short_ar,
        $desc, $desc_ar, $details, $details_ar,
        $how_en, $how_ar, $video_url,
        $price, $stock, $subcat_id, $brand_id,
        $sup_cost,
        $orig_price, $discount, $has_disc,
        $pid
    );

    if (!$stmt) { echo json_encode(['success' => false, 'error' => 'DB prepare error: ' . $conn->error]); exit(); }

    if ($stmt->execute()) {
        $stmt->close();

        // Handle tags — clear old, insert new
        $conn->query("DELETE FROM product_tags WHERE product_id = $pid");
        if (!empty($tags_raw)) {
            $tag_names = array_unique(array_filter(array_map('trim', explode(',', $tags_raw))));
            foreach ($tag_names as $tag_name) {
                $tag_slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($tag_name)));
                $tag_slug = trim($tag_slug, '-');
                if (empty($tag_slug)) continue;
                $conn->query("INSERT IGNORE INTO tags (name_en, slug) VALUES ('" . $conn->real_escape_string($tag_name) . "', '" . $conn->real_escape_string($tag_slug) . "')");
                $tag_row = $conn->query("SELECT id FROM tags WHERE slug = '" . $conn->real_escape_string($tag_slug) . "'")->fetch_assoc();
                if ($tag_row) {
                    $conn->query("INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES ($pid, {$tag_row['id']})");
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt->error]);
        $stmt->close();
    }
    exit();
}

// ─── Load product ──────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: admin_panel.php');
    exit();
}

// ─── Load categories ───────────────────────────────────────────────────────────
$categories = [];
$cat_result = $conn->query("SELECT c.id AS cid, c.name_en AS cname, s.id AS sid, s.name_en AS sname
    FROM categories c LEFT JOIN subcategories s ON s.category_id = c.id ORDER BY c.sort_order, s.sort_order");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        if (!isset($categories[$row['cid']])) $categories[$row['cid']] = ['name' => $row['cname'], 'subcategories' => []];
        if ($row['sid']) $categories[$row['cid']]['subcategories'][] = ['id' => $row['sid'], 'name' => $row['sname']];
    }
}

// ─── Current images ────────────────────────────────────────────────────────────
$base_dir   = __DIR__ . '/../../';

// Load brands
$brands = [];
$brand_res = $conn->query("SELECT id, name_en FROM brands ORDER BY sort_order, name_en");
if ($brand_res) { while ($r = $brand_res->fetch_assoc()) $brands[] = $r; }

// Load current tags for this product
$product_tags = [];
$tag_res = $conn->query("SELECT t.name_en FROM tags t JOIN product_tags pt ON pt.tag_id = t.id WHERE pt.product_id = $product_id ORDER BY t.name_en");
if ($tag_res) { while ($r = $tag_res->fetch_assoc()) $product_tags[] = $r['name_en']; }
$tags_string = implode(', ', $product_tags);

$img_folder = $base_dir . 'images/' . $product['name_en'] . '/';
$current_images = [];
if (is_dir($img_folder)) {
    $files = glob($img_folder . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);
    sort($files);
    foreach ($files as $f) {
        $current_images[] = 'images/' . $product['name_en'] . '/' . basename($f);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product – Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark:#1a1d2e;--secondary-dark:#242838;--accent-blue:#4f9eff;
            --accent-teal:#00d4aa;--accent-purple:#a855f7;--text-light:#fff;
            --text-gray:#9ca3af;--text-dark:#1f2937;--success:#10b981;
            --warning:#f59e0b;--danger:#ef4444;--bg-light:#f9fafb;
            --border-color:#e5e7eb;--shadow-md:0 4px 6px rgba(0,0,0,.1);
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',sans-serif;background:var(--bg-light);min-height:100vh;display:flex;color:var(--text-dark);}
        .sidebar{width:280px;background:linear-gradient(180deg,var(--primary-dark),var(--secondary-dark));color:var(--text-light);display:flex;flex-direction:column;position:fixed;left:0;top:0;bottom:0;overflow-y:auto;z-index:1000;box-shadow:0 20px 40px rgba(0,0,0,.2);}
        .sidebar-header{padding:2rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.1);}
        .logo{font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,var(--accent-blue),var(--accent-teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .admin-badge{display:inline-block;background:linear-gradient(135deg,var(--accent-purple),var(--accent-blue));color:#fff;font-size:.7rem;padding:.25rem .75rem;border-radius:20px;margin-top:.5rem;font-weight:600;}
        .sidebar-nav{flex:1;padding:1.5rem 0;}
        .nav-item{padding:.75rem 1.5rem;display:flex;align-items:center;gap:1rem;color:var(--text-gray);transition:all .3s;border-left:3px solid transparent;text-decoration:none;}
        .nav-item:hover{background:rgba(255,255,255,.05);color:var(--text-light);}
        .nav-item.active{background:rgba(79,158,255,.1);color:var(--accent-blue);border-left-color:var(--accent-blue);}
        .nav-item i{font-size:1.1rem;width:24px;text-align:center;}
        .sidebar-footer{padding:1.5rem;border-top:1px solid rgba(255,255,255,.1);}
        .logout-btn{width:100%;background:linear-gradient(135deg,var(--danger),#dc2626);color:#fff;border:none;padding:.875rem;border-radius:10px;cursor:pointer;font-weight:600;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;}
        .main-content{flex:1;margin-left:280px;padding:2rem;overflow-x:hidden;}
        .page-header{background:#fff;padding:1.75rem 2rem;border-radius:16px;margin-bottom:2rem;box-shadow:var(--shadow-md);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
        .page-header h1{font-size:1.875rem;font-weight:700;background:linear-gradient(135deg,var(--accent-blue),var(--accent-teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .form-card{background:#fff;border-radius:16px;padding:2rem;box-shadow:var(--shadow-md);margin-bottom:2rem;}
        .form-card h2{font-size:1.1rem;font-weight:700;margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem;color:var(--primary-dark);padding-bottom:.75rem;border-bottom:2px solid var(--bg-light);}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;}
        .form-group{margin-bottom:.5rem;}
        .form-group.full-width{grid-column:1/-1;}
        .form-group label{display:block;font-weight:600;margin-bottom:.5rem;font-size:.875rem;}
        .form-group label .required{color:var(--danger);}
        .form-group input,.form-group textarea,.form-group select{width:100%;padding:.75rem 1rem;border:2px solid var(--border-color);border-radius:10px;font-size:.925rem;font-family:inherit;transition:border-color .3s;background:var(--bg-light);}
        .form-group input:focus,.form-group textarea:focus,.form-group select:focus{outline:none;border-color:var(--accent-blue);box-shadow:0 0 0 3px rgba(79,158,255,.15);}
        .form-group textarea{resize:vertical;min-height:100px;}
        .form-group .help-text{font-size:.8rem;color:var(--text-gray);margin-top:.35rem;}
        .price-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;}
        .btn{padding:.75rem 1.5rem;border:none;border-radius:10px;font-weight:600;font-size:.925rem;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;transition:all .3s;text-decoration:none;}
        .btn-primary{background:linear-gradient(135deg,var(--accent-blue),#3b82f6);color:#fff;box-shadow:0 4px 12px rgba(79,158,255,.3);}
        .btn-primary:hover{transform:translateY(-2px);}
        .btn-success{background:linear-gradient(135deg,var(--success),#059669);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,.3);}
        .btn-success:hover{transform:translateY(-2px);}
        .btn-secondary{background:var(--bg-light);color:var(--text-dark);border:2px solid var(--border-color);}
        .btn-sm{padding:.5rem 1rem;font-size:.825rem;}
        .btn-actions{display:flex;gap:.75rem;margin-top:1.5rem;justify-content:flex-end;}
        .image-upload-area{border:2px dashed var(--border-color);border-radius:12px;padding:2rem;text-align:center;cursor:pointer;transition:all .3s;position:relative;background:var(--bg-light);}
        .image-upload-area:hover{border-color:var(--accent-blue);}
        .image-upload-area input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;}
        .image-upload-area i{font-size:2.5rem;color:var(--accent-blue);margin-bottom:.75rem;}
        .image-upload-area p{color:var(--text-gray);font-size:.9rem;}
        .image-preview{display:flex;flex-wrap:wrap;gap:1rem;margin-top:1rem;}
        .image-preview-item{width:120px;height:120px;border-radius:10px;overflow:hidden;position:relative;box-shadow:0 1px 3px rgba(0,0,0,.1);}
        .image-preview-item img{width:100%;height:100%;object-fit:cover;}
        .image-preview-item .badge{position:absolute;bottom:4px;left:4px;background:var(--accent-blue);color:#fff;font-size:.65rem;padding:2px 6px;border-radius:8px;font-weight:700;}
        .current-images{display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;}
        .current-img-item{position:relative;width:140px;height:140px;border-radius:10px;overflow:visible;border:2px solid var(--border-color);background:#fff;}
        .current-img-item.is-main{border-color:var(--accent-blue);box-shadow:0 0 0 2px rgba(79,158,255,.3);}
        .current-img-item img{width:100%;height:100%;object-fit:cover;border-radius:8px;}
        .current-img-item .img-num{position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,.65);color:#fff;font-size:.65rem;padding:2px 6px;border-radius:4px;}
        .current-img-item .img-main-badge{position:absolute;top:-8px;left:-8px;background:var(--accent-blue);color:#fff;font-size:.6rem;padding:3px 8px;border-radius:10px;font-weight:700;z-index:2;}
        .img-actions{position:absolute;top:4px;right:4px;display:flex;flex-direction:column;gap:3px;z-index:2;}
        .img-action-btn{width:28px;height:28px;border:none;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.7rem;transition:all .2s;opacity:.85;}
        .img-action-btn:hover{opacity:1;transform:scale(1.1);}
        .img-action-btn.delete{background:var(--danger);color:#fff;}
        .img-action-btn.replace{background:var(--warning);color:#fff;}
        .img-action-btn.set-main{background:var(--accent-teal);color:#fff;}
        .add-more-images-area{border:2px dashed var(--border-color);border-radius:12px;padding:1.5rem;text-align:center;cursor:pointer;transition:all .3s;position:relative;background:var(--bg-light);margin-top:1rem;}
        .add-more-images-area:hover{border-color:var(--accent-teal);background:rgba(0,212,170,.05);}
        .add-more-images-area input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;}
        .hidden-replace-input{display:none;}
        .toast{position:fixed;top:2rem;right:2rem;padding:1rem 1.5rem;border-radius:12px;color:#fff;font-weight:600;z-index:9999;transform:translateX(120%);transition:transform .4s;box-shadow:0 10px 30px rgba(0,0,0,.2);}
        .toast.show{transform:translateX(0);}
        .toast-success{background:linear-gradient(135deg,var(--success),#059669);}
        .toast-error{background:linear-gradient(135deg,var(--danger),#dc2626);}
        .loading-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9998;align-items:center;justify-content:center;}
        .loading-overlay.active{display:flex;}
        .spinner{width:50px;height:50px;border:4px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;}
        @keyframes spin{to{transform:rotate(360deg);}}
        @media(max-width:1024px){.sidebar{width:70px;}.sidebar .logo,.sidebar .admin-badge,.sidebar .nav-item span,.sidebar .logout-btn span{display:none;}.nav-item{justify-content:center;padding:.75rem;}.main-content{margin-left:70px;}.form-grid,.price-grid{grid-template-columns:1fr;}}
        @media(max-width:768px){.sidebar{display:none;}.main-content{margin-left:0;padding:1rem;}}
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
        <a href="manage_products.php"   class="nav-item active"><i class="fas fa-tag"></i><span>Products</span></a>
        <a href="add_product.php"       class="nav-item"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="manage_coupons.php"    class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="manage_categories.php" class="nav-item"><i class="fas fa-layer-group"></i><span>Categories</span></a>
        <a href="manage_brands.php" class="nav-item"><i class="fas fa-copyright"></i><span>Brands</span></a>
        <a href="daily_reports.php"     class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="../../index.php"       class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Product #<?= $product_id ?></h1>
        <div style="display:flex;gap:.75rem;">
            <a href="admin_panel.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
            <a href="../../<?= htmlspecialchars($product['slug'] ?? '') ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View Product</a>
            <button type="button" class="btn btn-sm" onclick="deleteProduct()" style="background:linear-gradient(135deg,var(--danger),#dc2626);color:#fff;"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>

    <form id="editProductForm" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?= $product_id ?>">

        <!-- Basic Info -->
        <div class="form-card">
            <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Product Name (English) <span class="required">*</span></label>
                    <input type="text" name="name_en" required value="<?= htmlspecialchars($product['name_en']) ?>">
                </div>
                <div class="form-group">
                    <label>Product Name (Arabic)</label>
                    <input type="text" name="name_ar" dir="rtl" value="<?= htmlspecialchars($product['name_ar'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Short Description (English)</label>
                    <input type="text" name="short_description_en" maxlength="255" value="<?= htmlspecialchars($product['short_description_en'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Short Description (Arabic)</label>
                    <input type="text" name="short_description_ar" dir="rtl" maxlength="255" value="<?= htmlspecialchars($product['short_description_ar'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Category / Subcategory</label>
                    <select name="subcategory_id">
                        <option value="">-- None --</option>
                        <?php foreach ($categories as $cat): ?>
                            <optgroup label="<?= htmlspecialchars($cat['name']) ?>">
                                <?php foreach ($cat['subcategories'] as $sub): ?>
                                    <option value="<?= $sub['id'] ?>" <?= ($product['subcategory_id'] == $sub['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Brand</label>
                    <select name="brand_id">
                        <option value="">-- None --</option>
                        <?php foreach ($brands as $br): ?>
                            <option value="<?= $br['id'] ?>" <?= ($product['brand_id'] ?? 0) == $br['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($br['name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Tags</label>
                    <input type="text" name="tags" placeholder="e.g. skincare, acne, moisturizer" maxlength="500" value="<?= htmlspecialchars($tags_string) ?>">
                    <div class="help-text">Comma-separated tags. Users can search products by these tags.</div>
                </div>
                <div class="form-group full-width">
                    <label><i class="fas fa-video"></i> Upload Video (See in Action)</label>
                    <?php if (!empty($product['video_review_url'])): ?>
                        <div style="margin-bottom:.5rem;font-size:.85rem;color:var(--text-gray);"><i class="fas fa-check-circle" style="color:var(--success);"></i> Current: <?= htmlspecialchars($product['video_review_url']) ?></div>
                    <?php endif; ?>
                    <input type="file" name="video_file" id="videoFileInput" accept="video/mp4,video/webm,video/ogg,video/mov,video/avi" style="padding:.5rem;">
                    <div class="help-text">Upload a new video to replace the current one. Leave empty to keep existing.</div>
                    <video id="videoPreview" style="display:none;max-width:320px;margin-top:.5rem;border-radius:8px;" controls></video>
                </div>
            </div>
        </div>

        <!-- Pricing & Stock -->
        <div class="form-card">
            <h2><i class="fas fa-money-bill-wave"></i> Pricing & Stock</h2>
            <div class="price-grid">
                <div class="form-group">
                    <label>Customer Price (JOD) <span class="required">*</span></label>
                    <input type="number" name="price_jod" step="0.001" min="0" required value="<?= $product['price_jod'] ?>">
                    <div class="help-text">Price shown to regular customers.</div>
                </div>
                <div class="form-group">
                    <label>Supplier Price (JOD)</label>
                    <input type="number" name="supplier_price" step="0.001" min="0" value="<?= $product['supplier_cost'] ?? '' ?>">
                    <div class="help-text">Price shown to supplier accounts.</div>
                </div>
                <div class="form-group">
                    <label>Original Price (JOD)</label>
                    <input type="number" name="original_price" step="0.001" min="0" value="<?= $product['original_price'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Stock Quantity <span class="required">*</span></label>
                    <input type="number" name="stock_quantity" min="0" required value="<?= $product['stock_quantity'] ?>">
                </div>
            </div>
            <div style="margin-top:1rem;">
                <div class="form-group" style="max-width:280px;">
                    <label>Discount Percentage (%)</label>
                    <input type="number" name="discount_percentage" step="0.01" min="0" max="100" value="<?= $product['discount_percentage'] ?? 0 ?>">
                </div>
            </div>
        </div>

        <!-- Descriptions -->
        <div class="form-card">
            <h2><i class="fas fa-align-left"></i> Description & Details</h2>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Description (English)</label>
                    <textarea name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Full Description (Arabic)</label>
                    <textarea name="description_ar" rows="4" dir="rtl"><?= htmlspecialchars($product['description_ar'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Product Details (English)</label>
                    <textarea name="product_details" rows="4"><?= htmlspecialchars($product['product_details'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Product Details (Arabic)</label>
                    <textarea name="product_details_ar" rows="4" dir="rtl"><?= htmlspecialchars($product['product_details_ar'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>How to Use (English)</label>
                    <textarea name="how_to_use_en" rows="3"><?= htmlspecialchars($product['how_to_use_en'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>How to Use (Arabic)</label>
                    <textarea name="how_to_use_ar" rows="3" dir="rtl"><?= htmlspecialchars($product['how_to_use_ar'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="form-card">
            <h2><i class="fas fa-images"></i> Product Images</h2>
            <p style="color:var(--text-gray);margin-bottom:1rem;font-size:.85rem;">
                <i class="fas fa-info-circle"></i>
                Hover over images to <strong>delete</strong> <i class="fas fa-trash" style="color:var(--danger);"></i>,
                <strong>replace</strong> <i class="fas fa-sync" style="color:var(--warning);"></i>,
                or <strong>set as main</strong> <i class="fas fa-star" style="color:var(--accent-teal);"></i>.
            </p>

            <div class="current-images" id="currentImagesContainer">
                <?php if (!empty($current_images)): ?>
                    <?php foreach ($current_images as $i => $img): ?>
                        <div class="current-img-item <?= $i === 0 ? 'is-main' : '' ?>" data-img="<?= htmlspecialchars($img) ?>">
                            <?php if ($i === 0): ?><span class="img-main-badge"><i class="fas fa-star"></i> Main</span><?php endif; ?>
                            <div class="img-actions">
                                <button type="button" class="img-action-btn delete" title="Delete image" onclick="deleteImage('<?= htmlspecialchars($img) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button type="button" class="img-action-btn replace" title="Replace image" onclick="triggerReplace('<?= htmlspecialchars($img) ?>')">
                                    <i class="fas fa-sync"></i>
                                </button>
                                <?php if ($i !== 0): ?>
                                <button type="button" class="img-action-btn set-main" title="Set as main image" onclick="setMainImage('<?= htmlspecialchars($img) ?>')">
                                    <i class="fas fa-star"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <img src="../../<?= htmlspecialchars($img) ?>?t=<?= time() ?>" alt="Image <?= $i+1 ?>">
                            <span class="img-num"><?= $i === 0 ? 'Main' : ($i+1) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:var(--text-gray);font-size:.9rem;"><i class="fas fa-image"></i> No images yet.</p>
                <?php endif; ?>
            </div>

            <!-- Hidden file input for replacing individual images -->
            <input type="file" id="replaceImageInput" class="hidden-replace-input" accept="image/*">

            <!-- Add more images -->
            <div class="add-more-images-area" id="addMoreArea">
                <input type="file" accept="image/*" multiple id="addMoreInput">
                <i class="fas fa-plus-circle" style="font-size:2rem;color:var(--accent-teal);margin-bottom:.5rem;"></i>
                <p style="color:var(--text-gray);font-size:.9rem;"><strong>Add more images</strong> — click or drag to append new photos</p>
            </div>
            <div class="image-preview" id="imagePreview"></div>
        </div>

        <!-- Submit -->
        <div class="btn-actions">
            <a href="admin_panel.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
            <button type="submit" class="btn btn-success" id="submitBtn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<div class="toast" id="toast"></div>
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>

<script>
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 4000);
}
function showLoading(on) {
    document.getElementById('loadingOverlay').classList.toggle('active', on);
}

document.getElementById('videoFileInput').addEventListener('change', function() {
    const prev = document.getElementById('videoPreview');
    if (this.files[0]) { prev.src = URL.createObjectURL(this.files[0]); prev.style.display = 'block'; }
    else { prev.style.display = 'none'; }
});

async function deleteProduct() {
    if (!confirm('Permanently delete this product? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'delete_product');
    fd.append('product_id', '<?= $product_id ?>');
    showLoading(true);
    try {
        const r = await fetch('edit_product.php?id=<?= $product_id ?>', { method: 'POST', body: fd });
        const d = await r.json();
        showLoading(false);
        if (d.success) { showToast('Product deleted!'); setTimeout(() => window.location.href = 'admin_panel.php', 1000); }
        else showToast(d.error || 'Delete failed', 'error');
    } catch(e) { showLoading(false); showToast('Network error', 'error'); }
}

// ─── Image Management Functions ───
let replaceTarget = '';

function renderImages(images) {
    const container = document.getElementById('currentImagesContainer');
    if (!images || images.length === 0) {
        container.innerHTML = '<p style="color:var(--text-gray);font-size:.9rem;"><i class="fas fa-image"></i> No images yet.</p>';
        return;
    }
    container.innerHTML = '';
    images.forEach((img, i) => {
        const div = document.createElement('div');
        div.className = 'current-img-item' + (i === 0 ? ' is-main' : '');
        div.dataset.img = img;
        let html = '';
        if (i === 0) html += '<span class="img-main-badge"><i class="fas fa-star"></i> Main</span>';
        html += '<div class="img-actions">';
        html += `<button type="button" class="img-action-btn delete" title="Delete image" onclick="deleteImage('${img}')"><i class="fas fa-trash"></i></button>`;
        html += `<button type="button" class="img-action-btn replace" title="Replace image" onclick="triggerReplace('${img}')"><i class="fas fa-sync"></i></button>`;
        if (i !== 0) html += `<button type="button" class="img-action-btn set-main" title="Set as main image" onclick="setMainImage('${img}')"><i class="fas fa-star"></i></button>`;
        html += '</div>';
        html += `<img src="../../${img}?t=${Date.now()}" alt="Image ${i+1}">`;
        html += `<span class="img-num">${i === 0 ? 'Main' : (i+1)}</span>`;
        div.innerHTML = html;
        container.appendChild(div);
    });
}

async function deleteImage(imgFile) {
    if (!confirm('Delete this image?')) return;
    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'delete_image');
    fd.append('product_id', '<?= $product_id ?>');
    fd.append('image_file', imgFile);
    showLoading(true);
    try {
        const r = await fetch('edit_product.php?id=<?= $product_id ?>', { method: 'POST', body: fd });
        const d = await r.json();
        showLoading(false);
        if (d.success) {
            showToast('Image deleted');
            renderImages(d.images);
        } else {
            showToast(d.error || 'Failed to delete image', 'error');
        }
    } catch(e) { showLoading(false); showToast('Network error', 'error'); }
}

function triggerReplace(imgFile) {
    replaceTarget = imgFile;
    document.getElementById('replaceImageInput').click();
}

document.getElementById('replaceImageInput').addEventListener('change', async function() {
    if (!this.files[0] || !replaceTarget) return;
    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'replace_image');
    fd.append('product_id', '<?= $product_id ?>');
    fd.append('image_file', replaceTarget);
    fd.append('new_image', this.files[0]);
    showLoading(true);
    try {
        const r = await fetch('edit_product.php?id=<?= $product_id ?>', { method: 'POST', body: fd });
        const d = await r.json();
        showLoading(false);
        if (d.success) {
            showToast('Image replaced');
            renderImages(d.images);
        } else {
            showToast(d.error || 'Failed to replace image', 'error');
        }
    } catch(e) { showLoading(false); showToast('Network error', 'error'); }
    this.value = '';
    replaceTarget = '';
});

async function setMainImage(imgFile) {
    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'set_main_image');
    fd.append('product_id', '<?= $product_id ?>');
    fd.append('image_file', imgFile);
    showLoading(true);
    try {
        const r = await fetch('edit_product.php?id=<?= $product_id ?>', { method: 'POST', body: fd });
        const d = await r.json();
        showLoading(false);
        if (d.success) {
            showToast('Main image updated');
            renderImages(d.images);
        } else {
            showToast(d.error || 'Failed to set main image', 'error');
        }
    } catch(e) { showLoading(false); showToast('Network error', 'error'); }
}

// Add more images
document.getElementById('addMoreInput').addEventListener('change', async function() {
    if (!this.files.length) return;
    const fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'add_images');
    fd.append('product_id', '<?= $product_id ?>');
    for (let i = 0; i < this.files.length; i++) {
        fd.append('new_images[]', this.files[i]);
    }
    showLoading(true);
    try {
        const r = await fetch('edit_product.php?id=<?= $product_id ?>', { method: 'POST', body: fd });
        const d = await r.json();
        showLoading(false);
        if (d.success) {
            showToast(`${d.added} image(s) added`);
            renderImages(d.images);
        } else {
            showToast(d.error || 'Failed to add images', 'error');
        }
    } catch(e) { showLoading(false); showToast('Network error', 'error'); }
    this.value = '';
});

document.getElementById('editProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const nameEn = this.querySelector('[name="name_en"]').value.trim();
    const price  = parseFloat(this.querySelector('[name="price_jod"]').value) || 0;
    if (!nameEn) { showToast('English name is required', 'error'); return; }
    if (price <= 0) { showToast('Price must be greater than 0', 'error'); return; }

    const fd = new FormData(this);
    fd.append('ajax', '1');

    showLoading(true);
    document.getElementById('submitBtn').disabled = true;

    fetch('edit_product.php?id=<?= $product_id ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            showLoading(false);
            document.getElementById('submitBtn').disabled = false;
            if (data.success) {
                showToast(data.message || 'Product updated!');
                setTimeout(() => window.location.href = 'admin_panel.php', 1500);
            } else {
                showToast(data.error || 'Something went wrong', 'error');
            }
        })
        .catch(err => {
            showLoading(false);
            document.getElementById('submitBtn').disabled = false;
            showToast('Network error: ' + err.message, 'error');
        });
});
</script>
</body>
</html>
