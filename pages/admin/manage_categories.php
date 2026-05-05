<?php
/**
 * Manage Categories & Subcategories – Admin Panel
 * Add, view, and delete categories/subcategories
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

$is_ajax_request =
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        isset($_POST['ajax']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    );

if (!isAdmin()) {
    if ($is_ajax_request) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Session expired or unauthorized. Please refresh and log in again.'
        ]);
        exit();
    }
    header('Location: ../../index.php');
    exit();
}

if ($is_ajax_request) {
    // Convert notices/warnings during AJAX into JSON-safe errors.
    set_error_handler(function ($severity, $message, $file, $line) {
        if (error_reporting() & $severity) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
    });

    set_exception_handler(function ($e) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server Error: ' . $e->getMessage()
        ]);
        exit();
    });

    ob_start();
    header('Content-Type: application/json; charset=utf-8');
}

$cat_upload_dir = __DIR__ . '/../../uploads/categories/';
$cat_upload_web_path = 'uploads/categories/';
$cat_upload_fallback_dir = __DIR__ . '/../../uploads/category_images/';
$cat_upload_fallback_web_path = 'uploads/category_images/';
$cat_upload_dir_error = null;

$prepare_upload_dir = function (string $dir): bool {
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }
    @chmod($dir, 0775);
    return is_writable($dir);
};

if (!$prepare_upload_dir($cat_upload_dir)) {
    $cat_upload_dir = $cat_upload_fallback_dir;
    $cat_upload_web_path = $cat_upload_fallback_web_path;
    if (!$prepare_upload_dir($cat_upload_dir)) {
        $cat_upload_dir_error = 'Upload directories are not writable.';
    }
}

// Check if image_url column exists
$img_col_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'image_url'");
$has_image_col = ($img_col_check && $img_col_check->num_rows > 0);

// ─── AJAX handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $action = $_POST['action'] ?? '';

    // ADD CATEGORY
    if ($action === 'add_category') {
        $name_en = trim($_POST['name_en'] ?? '');
        $name_ar = trim($_POST['name_ar'] ?? '');
        if (!$name_en) { echo json_encode(['success' => false, 'error' => 'English name is required']); exit(); }
        $sort_order = 0;
        $sort_res = $conn->query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort FROM categories");
        if ($sort_res) {
            $sort_row = $sort_res->fetch_assoc();
            $sort_order = (int)($sort_row['next_sort'] ?? 0);
            $sort_res->free();
        }
        $stmt = $conn->prepare("INSERT INTO categories (name_en, name_ar, sort_order) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $name_en, $name_ar, $sort_order);
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'id' => $new_id, 'name_en' => $name_en, 'name_ar' => $name_ar, 'sort_order' => $sort_order]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
            $stmt->close();
        }
        exit();
    }

    // ADD SUBCATEGORY
    if ($action === 'add_subcategory') {
        $name_en     = trim($_POST['name_en'] ?? '');
        $name_ar     = trim($_POST['name_ar'] ?? '');
        $icon        = trim($_POST['icon'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        if (!$name_en)     { echo json_encode(['success' => false, 'error' => 'English name is required']); exit(); }
        if (!$category_id) { echo json_encode(['success' => false, 'error' => 'Parent category is required']); exit(); }
        $stmt = $conn->prepare("INSERT INTO subcategories (name_en, name_ar, icon, category_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $name_en, $name_ar, $icon, $category_id);
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'id' => $new_id, 'name_en' => $name_en, 'name_ar' => $name_ar, 'icon' => $icon, 'category_id' => $category_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
            $stmt->close();
        }
        exit();
    }

    // UPDATE CATEGORY SORT ORDER
    if ($action === 'update_category_sort') {
        $id = intval($_POST['id'] ?? 0);
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit(); }
        if ($sort_order < 0) { $sort_order = 0; }

        $stmt = $conn->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
        $stmt->bind_param('ii', $sort_order, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'sort_order' => $sort_order]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Update failed']);
        }
        $stmt->close();
        exit();
    }

    // DELETE CATEGORY
    if ($action === 'delete_category') {
        $id = intval($_POST['id'] ?? 0);
        $force_delete = intval($_POST['force'] ?? 0) === 1;
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit(); }

        // Preserve subcategories: move them to a fallback category before deleting this category.
        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM subcategories WHERE category_id = ?");
        $check->bind_param('i', $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();

        $linked_subcategories = (int)($row['cnt'] ?? 0);
        if ($linked_subcategories > 0 && !$force_delete) {
            echo json_encode([
                'success' => false,
                'requires_force' => true,
                'linked_subcategories' => $linked_subcategories,
                'error' => "This category has {$linked_subcategories} subcategory(s)."
            ]);
            exit();
        }

        $moved_subcategories = 0;
        if ($linked_subcategories > 0) {
            $fallback_category_id = 0;

            // Prefer a reusable uncategorized bucket (other than the one being deleted).
            $find_uncat = $conn->prepare("SELECT id FROM categories WHERE id != ? AND LOWER(TRIM(name_en)) = 'uncategorized' ORDER BY id ASC LIMIT 1");
            if ($find_uncat) {
                $find_uncat->bind_param('i', $id);
                $find_uncat->execute();
                $uncat_row = $find_uncat->get_result()->fetch_assoc();
                $find_uncat->close();
                $fallback_category_id = (int)($uncat_row['id'] ?? 0);
            }

            // Otherwise use any existing category except the one being deleted.
            if ($fallback_category_id <= 0) {
                $find_any = $conn->prepare("SELECT id FROM categories WHERE id != ? ORDER BY id ASC LIMIT 1");
                if ($find_any) {
                    $find_any->bind_param('i', $id);
                    $find_any->execute();
                    $any_row = $find_any->get_result()->fetch_assoc();
                    $find_any->close();
                    $fallback_category_id = (int)($any_row['id'] ?? 0);
                }
            }

            // If no other category exists, create one so subcategories are never removed.
            if ($fallback_category_id <= 0) {
                $insert_fallback = $conn->prepare("INSERT INTO categories (name_en, name_ar) VALUES (?, ?)");
                if (!$insert_fallback) {
                    echo json_encode(['success' => false, 'error' => 'Failed to prepare fallback category query']);
                    exit();
                }

                $fallback_name_en = 'Uncategorized';
                $fallback_name_ar = 'Uncategorized';
                $insert_fallback->bind_param('ss', $fallback_name_en, $fallback_name_ar);
                $created = $insert_fallback->execute();

                if (!$created) {
                    $fallback_name_en = 'Uncategorized ' . date('YmdHis');
                    $fallback_name_ar = $fallback_name_en;
                    $insert_fallback->bind_param('ss', $fallback_name_en, $fallback_name_ar);
                    $created = $insert_fallback->execute();
                }

                if ($created) {
                    $fallback_category_id = (int)$insert_fallback->insert_id;
                }
                $insert_fallback->close();

                if ($fallback_category_id <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Could not create fallback category to preserve subcategories']);
                    exit();
                }
            }

            $move_subs = $conn->prepare("UPDATE subcategories SET category_id = ? WHERE category_id = ?");
            if (!$move_subs) {
                echo json_encode(['success' => false, 'error' => 'Failed to prepare subcategory move query']);
                exit();
            }
            $move_subs->bind_param('ii', $fallback_category_id, $id);
            if (!$move_subs->execute()) {
                $err = $move_subs->error;
                $move_subs->close();
                echo json_encode(['success' => false, 'error' => 'Failed to move subcategories: ' . $err]);
                exit();
            }
            $moved_subcategories = (int)$move_subs->affected_rows;
            $move_subs->close();
        }

        // Delete category
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) { echo json_encode(['success' => true, 'moved_subcategories' => $moved_subcategories]); }
        else { echo json_encode(['success' => false, 'error' => 'Delete failed']); }
        $stmt->close();
        exit();
    }

    // DELETE SUBCATEGORY
    if ($action === 'delete_subcategory') {
        $sub_id = intval($_POST['id'] ?? 0);
        $force_delete = intval($_POST['force'] ?? 0) === 1;
        if (!$sub_id) { echo json_encode(['success' => false, 'error' => 'Invalid subcategory ID']); exit(); }

        $sub_stmt = $conn->prepare("SELECT category_id FROM subcategories WHERE id = ?");
        if (!$sub_stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare subcategory lookup']);
            exit();
        }
        $sub_stmt->bind_param('i', $sub_id);
        $sub_stmt->execute();
        $sub_row = $sub_stmt->get_result()->fetch_assoc();
        $sub_stmt->close();

        if (!$sub_row) {
            echo json_encode(['success' => false, 'error' => 'Subcategory not found']);
            exit();
        }

        $category_id = (int)($sub_row['category_id'] ?? 0);
        if ($category_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid parent category']);
            exit();
        }

        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM products WHERE subcategory_id = ?");
        if (!$check) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare product check']);
            exit();
        }
        $check->bind_param('i', $sub_id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();

        $linked_products = (int)($row['cnt'] ?? 0);
        if ($linked_products > 0 && !$force_delete) {
            echo json_encode([
                'success' => false,
                'requires_force' => true,
                'linked_products' => $linked_products,
                'error' => "This subcategory has {$linked_products} product(s)."
            ]);
            exit();
        }

        $moved_products = 0;
        if ($linked_products > 0) {
            $fallback_sub_id = 0;

            $find_uncat = $conn->prepare("SELECT id FROM subcategories WHERE category_id = ? AND id != ? AND LOWER(TRIM(name_en)) = 'uncategorized' ORDER BY id ASC LIMIT 1");
            if ($find_uncat) {
                $find_uncat->bind_param('ii', $category_id, $sub_id);
                $find_uncat->execute();
                $uncat_row = $find_uncat->get_result()->fetch_assoc();
                $find_uncat->close();
                $fallback_sub_id = (int)($uncat_row['id'] ?? 0);
            }

            if ($fallback_sub_id <= 0) {
                $insert_fallback = $conn->prepare("INSERT INTO subcategories (name_en, name_ar, icon, category_id) VALUES (?, ?, ?, ?)");
                if (!$insert_fallback) {
                    echo json_encode(['success' => false, 'error' => 'Failed to prepare fallback subcategory query']);
                    exit();
                }

                $fallback_name_en = 'Uncategorized';
                $fallback_name_ar = 'Uncategorized';
                $fallback_icon = 'fas fa-tag';
                $insert_fallback->bind_param('sssi', $fallback_name_en, $fallback_name_ar, $fallback_icon, $category_id);
                $created = $insert_fallback->execute();

                if (!$created) {
                    $fallback_name_en = 'Uncategorized ' . date('YmdHis');
                    $fallback_name_ar = $fallback_name_en;
                    $insert_fallback->bind_param('sssi', $fallback_name_en, $fallback_name_ar, $fallback_icon, $category_id);
                    $created = $insert_fallback->execute();
                }

                if ($created) {
                    $fallback_sub_id = (int)$insert_fallback->insert_id;
                }
                $insert_fallback->close();

                if ($fallback_sub_id <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Could not create fallback subcategory']);
                    exit();
                }
            }

            $move = $conn->prepare("UPDATE products SET subcategory_id = ? WHERE subcategory_id = ?");
            if (!$move) {
                echo json_encode(['success' => false, 'error' => 'Failed to prepare product move query']);
                exit();
            }
            $move->bind_param('ii', $fallback_sub_id, $sub_id);
            if (!$move->execute()) {
                $err = $move->error;
                $move->close();
                echo json_encode(['success' => false, 'error' => 'Failed to move products: ' . $err]);
                exit();
            }
            $moved_products = (int)$move->affected_rows;
            $move->close();
        }

        $stmt = $conn->prepare("DELETE FROM subcategories WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare delete query']);
            exit();
        }
        $stmt->bind_param('i', $sub_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'moved_products' => $moved_products]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Delete failed']);
        }
        $stmt->close();
        exit();
    }

    // UPLOAD CATEGORY IMAGE
    if ($action === 'upload_category_image') {
        $cat_id = intval($_POST['category_id'] ?? 0);
        if (!$cat_id) { echo json_encode(['success' => false, 'error' => 'Invalid category ID']); exit(); }
        if (!$has_image_col) { echo json_encode(['success' => false, 'error' => 'Please run the migration first: /run_category_image_migration.php']); exit(); }
        if ($cat_upload_dir_error) {
            echo json_encode([
                'success' => false,
                'error' => $cat_upload_dir_error . ' Please grant web server write access to: ' . $cat_upload_dir,
            ]);
            exit();
        }
        if (!is_writable($cat_upload_dir)) {
            echo json_encode([
                'success' => false,
                'error' => 'Upload directory is not writable. Please grant web server write access to: ' . $cat_upload_dir,
            ]);
            exit();
        }
        
        if (empty($_FILES['category_image']) || $_FILES['category_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Please select a valid image']);
            exit();
        }
        
        $file = $_FILES['category_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)]);
            exit();
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB.']);
            exit();
        }
        
        $filename = 'cat_' . $cat_id . '_' . time() . '.' . $ext;
        $dest = $cat_upload_dir . $filename;
        
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            $last_error = error_get_last();
            $details = !empty($last_error['message']) ? $last_error['message'] : 'Server write failed.';
            echo json_encode(['success' => false, 'error' => 'Upload failed: ' . $details]);
            exit();
        }
        
        $image_path = $cat_upload_web_path . $filename;
        $stmt = $conn->prepare("UPDATE categories SET image_url = ? WHERE id = ?");
        $stmt->bind_param('si', $image_path, $cat_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'image_url' => $image_path]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
        }
        $stmt->close();
        exit();
    }

    // UPLOAD SUBCATEGORY IMAGE
    if ($action === 'upload_subcategory_image') {
        $sub_id = intval($_POST['subcategory_id'] ?? 0);
        if (!$sub_id) { echo json_encode(['success' => false, 'error' => 'Invalid subcategory ID']); exit(); }
        if ($cat_upload_dir_error) {
            echo json_encode([
                'success' => false,
                'error' => $cat_upload_dir_error . ' Please grant web server write access to: ' . $cat_upload_dir,
            ]);
            exit();
        }
        if (!is_writable($cat_upload_dir)) {
            echo json_encode([
                'success' => false,
                'error' => 'Upload directory is not writable. Please grant web server write access to: ' . $cat_upload_dir,
            ]);
            exit();
        }
        
        // Check if image_url column exists
        $sub_img_check = $conn->query("SHOW COLUMNS FROM subcategories LIKE 'image_url'");
        $has_sub_image_col = ($sub_img_check && $sub_img_check->num_rows > 0);
        if (!$has_sub_image_col) { echo json_encode(['success' => false, 'error' => 'Please run the migration first: /run_subcategory_image_migration.php']); exit(); }
        
        if (empty($_FILES['subcategory_image']) || $_FILES['subcategory_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Please select a valid image']);
            exit();
        }
        
        $file = $_FILES['subcategory_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)]);
            exit();
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB.']);
            exit();
        }
        
        $filename = 'subcat_' . $sub_id . '_' . time() . '.' . $ext;
        $dest = $cat_upload_dir . $filename;
        
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            $last_error = error_get_last();
            $details = !empty($last_error['message']) ? $last_error['message'] : 'Server write failed.';
            echo json_encode(['success' => false, 'error' => 'Upload failed: ' . $details]);
            exit();
        }
        
        $image_path = $cat_upload_web_path . $filename;
        $stmt = $conn->prepare("UPDATE subcategories SET image_url = ? WHERE id = ?");
        $stmt->bind_param('si', $image_path, $sub_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'image_url' => $image_path]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
        }
        $stmt->close();
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit();
}

// ─── Load categories & subcategories ─────────────────────────────────────────
$categories = [];
$img_select = $has_image_col ? ', c.image_url AS cimg' : '';

// Check if subcategories have image_url column
$sub_img_check = $conn->query("SHOW COLUMNS FROM subcategories LIKE 'image_url'");
$has_sub_image_col = ($sub_img_check && $sub_img_check->num_rows > 0);
$sub_img_select = $has_sub_image_col ? ', s.image_url AS simg' : '';

$result = $conn->query("SELECT c.id AS cid, c.name_en AS cen, c.name_ar AS car, c.sort_order AS csort $img_select,
    s.id AS sid, s.name_en AS sen, s.name_ar AS sar, s.icon AS sicon, s.sort_order AS ssort $sub_img_select,
    (SELECT COUNT(*) FROM products WHERE subcategory_id = s.id) AS product_count
    FROM categories c
    LEFT JOIN subcategories s ON s.category_id = c.id
    ORDER BY c.sort_order ASC, c.id ASC, s.sort_order ASC, s.id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cid = $row['cid'];
        if (!isset($categories[$cid])) {
            $categories[$cid] = [
                'id' => $cid,
                'name_en' => $row['cen'],
                'name_ar' => $row['car'],
                'sort_order' => (int)($row['csort'] ?? 0),
                'image_url' => $row['cimg'] ?? '',
                'subcategories' => []
            ];
        }
        if ($row['sid']) {
            $categories[$cid]['subcategories'][] = [
                'id'            => $row['sid'],
                'name_en'       => $row['sen'],
                'name_ar'       => $row['sar'],
                'icon'          => $row['sicon'] ?? '',
                'sort_order'    => (int)($row['ssort'] ?? 0),
                'image_url'     => $row['simg'] ?? '',
                'product_count' => intval($row['product_count']),
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories – Admin Panel</title>
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

        .main-content { flex: 1; margin-left: 280px; padding: 2rem; overflow-x: hidden; }
        .page-header { background: #fff; padding: 1.75rem 2rem; border-radius: 16px; margin-bottom: 2rem; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .page-header h1 { font-size: 1.875rem; font-weight: 700; background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        .cards-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .form-card { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); }
        .form-card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; color: var(--primary-dark); padding-bottom: .75rem; border-bottom: 2px solid var(--bg-light); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: .4rem; font-size: .875rem; }
        .form-group input, .form-group select { width: 100%; padding: .65rem .9rem; border: 2px solid var(--border-color); border-radius: 10px; font-size: .9rem; font-family: inherit; transition: border-color .3s; background: var(--bg-light); }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 3px rgba(79,158,255,.15); }
        .form-group .help-text { font-size: .78rem; color: var(--text-gray); margin-top: .3rem; }
        .btn { padding: .65rem 1.25rem; border: none; border-radius: 10px; font-weight: 600; font-size: .9rem; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem; transition: all .3s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-blue), #3b82f6); color: #fff; box-shadow: 0 4px 12px rgba(79,158,255,.3); }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-danger { background: linear-gradient(135deg, var(--danger), #dc2626); color: #fff; }
        .btn-sm { padding: .35rem .75rem; font-size: .78rem; border-radius: 8px; }
        .btn-secondary { background: var(--bg-light); color: var(--text-dark); border: 2px solid var(--border-color); }

        /* Category list */
        .categories-section { background: #fff; border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-md); }
        .categories-section h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; color: var(--primary-dark); padding-bottom: .75rem; border-bottom: 2px solid var(--bg-light); }
        .category-block { border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 1.25rem; overflow: hidden; }
        .category-header { display: flex; justify-content: space-between; align-items: center; padding: .9rem 1.25rem; background: linear-gradient(135deg, rgba(79,158,255,.06), rgba(0,212,170,.04)); border-bottom: 1px solid var(--border-color); }
        .category-header .cat-title { font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: .5rem; }
        .category-header .cat-ar { color: var(--text-gray); font-size: .85rem; display: block; }
        .subcategory-list { padding: .75rem 1.25rem; }
        .subcategory-item { display: flex; justify-content: space-between; align-items: center; padding: .5rem .75rem; border-radius: 8px; margin-bottom: .35rem; background: var(--bg-light); }
        .subcategory-item:last-child { margin-bottom: 0; }
        .subcategory-item .sub-info { display: flex; align-items: center; gap: .6rem; }
        .subcategory-item .sub-icon { color: var(--accent-blue); width: 20px; text-align: center; }
        .subcategory-item .sub-name { font-weight: 600; font-size: .9rem; }
        .subcategory-item .sub-ar { color: var(--text-gray); font-size: .8rem; }
        .subcategory-item .prod-badge { background: rgba(79,158,255,.12); color: var(--accent-blue); font-size: .72rem; padding: .2rem .55rem; border-radius: 20px; font-weight: 600; }
        .sort-control { display: flex; flex-direction: column; gap: .25rem; align-items: flex-start; }
        .sort-control label { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--text-gray); }
        .sort-input { width: 90px; padding: .35rem .5rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: .85rem; background: #fff; }
        .empty-sub { color: var(--text-gray); font-style: italic; font-size: .88rem; padding: .5rem 0; text-align: center; }

        .toast { position: fixed; top: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 12px; color: #fff; font-weight: 600; z-index: 9999; transform: translateX(120%); transition: transform .4s; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
        .toast.show { transform: translateX(0); }
        .toast-success { background: linear-gradient(135deg, var(--success), #059669); }
        .toast-error   { background: linear-gradient(135deg, var(--danger), #dc2626); }

        @media (max-width: 1024px) {
            .cards-row { grid-template-columns: 1fr; }
            .sidebar { width: 70px; }
            .sidebar .logo, .sidebar .admin-badge, .sidebar .nav-item span, .sidebar .logout-btn span { display: none; }
            .sidebar-header { padding: 1rem .5rem; text-align: center; }
            .nav-item { justify-content: center; padding: .75rem; }
            .main-content { margin-left: 70px; }
        }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 1rem; } }
    </style>
    <?php require_once __DIR__ . '/../../includes/home_theme_header.php'; ?>
    <?php require_once __DIR__ . '/../../includes/meta_pixel.php'; ?>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo"><i class="fas fa-shopping-bag"></i> POSHY</div>
        <div class="admin-badge"><i class="fas fa-shield-alt"></i> ADMIN PANEL</div>
    </div>
    <div class="sidebar-nav">
        <a href="/pages/admin/admin_panel.php"        class="nav-item"><i class="fas fa-box"></i><span>Orders Management</span></a>
        <a href="/pages/admin/manage_products.php"    class="nav-item"><i class="fas fa-tag"></i><span>Products</span></a>
        <a href="add_product.php"        class="nav-item"><i class="fas fa-plus-circle"></i><span>Add New Product</span></a>
        <a href="/pages/admin/manage_coupons.php"     class="nav-item"><i class="fas fa-ticket-alt"></i><span>Coupon Management</span></a>
        <a href="/pages/admin/manage_categories.php"  class="nav-item active"><i class="fas fa-layer-group"></i><span>Categories</span></a>
        <a href="/pages/admin/manage_brands.php"      class="nav-item"><i class="fas fa-copyright"></i><span>Brands</span></a>
        <a href="/pages/admin/manage_banners.php"     class="nav-item"><i class="fas fa-images"></i><span>Homepage Banners</span></a>
        <a href="/pages/admin/daily_reports.php"      class="nav-item"><i class="fas fa-chart-line"></i><span>Daily Reports</span></a>
        <a href="/index.php"        class="nav-item"><i class="fas fa-store"></i><span>Visit Store</span></a>
    </div>
    <div class="sidebar-footer">
        <a href="/pages/auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-layer-group"></i> Manage Categories</h1>
        <a href="/pages/admin/admin_panel.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <!-- Add Category + Add Subcategory forms -->
    <div class="cards-row">

        <!-- Add Category -->
        <div class="form-card">
            <h2><i class="fas fa-folder-plus"></i> Add New Category</h2>
            <form id="addCategoryForm">
                <div class="form-group">
                    <label>Category Name (English) <span style="color:red">*</span></label>
                    <input type="text" name="name_en" id="catNameEn" placeholder="e.g. Skincare" required>
                </div>
                <div class="form-group">
                    <label>Category Name (Arabic)</label>
                    <input type="text" name="name_ar" id="catNameAr" placeholder="مثال: العناية بالبشرة" dir="rtl">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </form>
        </div>

        <!-- Add Subcategory -->
        <div class="form-card">
            <h2><i class="fas fa-tag"></i> Add New Subcategory</h2>
            <form id="addSubcategoryForm">
                <div class="form-group">
                    <label>Parent Category <span style="color:red">*</span></label>
                    <select name="category_id" id="subCategoryParent" required>
                        <option value="">-- Select Parent Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subcategory Name (English) <span style="color:red">*</span></label>
                    <input type="text" name="name_en" id="subNameEn" placeholder="e.g. Moisturizers" required>
                </div>
                <div class="form-group">
                    <label>Subcategory Name (Arabic)</label>
                    <input type="text" name="name_ar" id="subNameAr" placeholder="مثال: المرطبات" dir="rtl">
                </div>
                <div class="form-group">
                    <label>Icon (FontAwesome class)</label>
                    <input type="text" name="icon" id="subIcon" placeholder="e.g. fas fa-tint">
                    <div class="help-text">Optional. Use any FontAwesome 6 class, e.g. <code>fas fa-leaf</code>.</div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center">
                    <i class="fas fa-plus"></i> Add Subcategory
                </button>
            </form>
        </div>
    </div>

    <!-- Categories List -->
    <div class="categories-section" id="categoriesSection">
        <h2><i class="fas fa-list"></i> Current Categories & Subcategories</h2>

        <?php if (empty($categories)): ?>
            <p style="color: var(--text-gray); text-align:center; padding: 2rem;">No categories found. Add one above.</p>
        <?php else: ?>
            <?php foreach ($categories as $cat): ?>
            <div class="category-block" id="catBlock-<?= $cat['id'] ?>">
                <div class="category-header">
                    <div style="display:flex; align-items:center; gap:1.5rem;">
                        <!-- Category Image -->
                        <div style="position:relative; width:120px; height:120px; border-radius:15px; overflow:hidden; background:#f3f4f6; flex-shrink:0; cursor:pointer;" onclick="document.getElementById('catImgInput-<?= $cat['id'] ?>').click()" title="Click to upload category image">
                            <?php if (!empty($cat['image_url'])): ?>
                                <img src="../../<?= htmlspecialchars($cat['image_url']) ?>" style="width:100%;height:100%;object-fit:cover;" id="catImgPreview-<?= $cat['id'] ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:2rem;" id="catImgPreview-<?= $cat['id'] ?>">
                                    <i class="fas fa-camera"></i>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="catImgInput-<?= $cat['id'] ?>" accept="image/*" style="display:none" onchange="uploadCategoryImage(<?= $cat['id'] ?>, this)">
                            <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.5);color:#fff;text-align:center;font-size:.7rem;padding:4px;font-weight:600;">📷 UPLOAD</div>
                        </div>
                        <div>
                            <div class="cat-title">
                                <i class="fas fa-folder" style="color: var(--warning);"></i>
                                <?= htmlspecialchars($cat['name_en']) ?>
                                <span style="color:var(--text-gray);font-size:.8rem;">(ID: <?= $cat['id'] ?>)</span>
                            </div>
                            <?php if ($cat['name_ar']): ?>
                                <span class="cat-ar"><?= htmlspecialchars($cat['name_ar']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:.75rem;">
                        <div class="sort-control">
                            <label>Home order</label>
                            <input type="number" min="0" step="1" class="sort-input" value="<?= (int)($cat['sort_order'] ?? 0) ?>" data-prev="<?= (int)($cat['sort_order'] ?? 0) ?>" onchange="updateCategorySort(<?= $cat['id'] ?>, this)">
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?= $cat['id'] ?>, this)" title="Delete category (subcategories will be preserved)">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </div>
                </div>
                <div class="subcategory-list" id="subList-<?= $cat['id'] ?>">
                    <?php if (empty($cat['subcategories'])): ?>
                        <div class="empty-sub" id="emptySub-<?= $cat['id'] ?>">No subcategories yet.</div>
                    <?php else: ?>
                        <?php foreach ($cat['subcategories'] as $sub): ?>
                        <?php $sub_product_count = (int)($sub['product_count'] ?? 0); ?>
                        <div class="subcategory-item" id="subItem-<?= $sub['id'] ?>">
                            <div style="display:flex;align-items:center;gap:1rem;flex:1;">
                                <!-- Subcategory Image Upload -->
                                <div style="position:relative;width:80px;height:80px;border-radius:12px;overflow:hidden;background:#f3f4f6;flex-shrink:0;cursor:pointer;" onclick="document.getElementById('subImgInput-<?= $sub['id'] ?>').click()" title="Click to upload subcategory image">
                                    <?php if (!empty($sub['image_url'])): ?>
                                        <img src="../../<?= htmlspecialchars($sub['image_url']) ?>" style="width:100%;height:100%;object-fit:cover;" id="subImgPreview-<?= $sub['id'] ?>">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:1.5rem;" id="subImgPreview-<?= $sub['id'] ?>">
                                            <i class="<?= htmlspecialchars($sub['icon'] ?: 'fas fa-tag') ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="subImgInput-<?= $sub['id'] ?>" accept="image/*" style="display:none" onchange="uploadSubcategoryImage(<?= $sub['id'] ?>, this)">
                                    <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.5);color:#fff;text-align:center;font-size:.6rem;padding:2px;font-weight:600;">📷</div>
                                </div>
                                
                                <div class="sub-info" style="flex:1;">
                                    <?php if ($sub['icon']): ?>
                                        <i class="<?= htmlspecialchars($sub['icon']) ?> sub-icon"></i>
                                    <?php else: ?>
                                        <i class="fas fa-tag sub-icon"></i>
                                    <?php endif; ?>
                                    <div>
                                        <div class="sub-name"><?= htmlspecialchars($sub['name_en']) ?></div>
                                        <?php if ($sub['name_ar']): ?>
                                            <div class="sub-ar"><?= htmlspecialchars($sub['name_ar']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="prod-badge"><?= $sub['product_count'] ?> product<?= $sub['product_count'] != 1 ? 's' : '' ?></span>
                                </div>
                            </div>
                            <button class="btn btn-danger btn-sm" onclick="deleteSubcategory(<?= $sub['id'] ?>, <?= $cat['id'] ?>, this, <?= $sub_product_count ?>)" title="<?= $sub_product_count > 0 ? 'Force delete: move products to Uncategorized' : 'Delete subcategory' ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
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

async function postFormData(fd) {
    fd.append('ajax', '1');
    const r = await fetch(window.location.pathname, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const raw = await r.text();
    const sanitized = raw.replace(/^\uFEFF/, '').trim();
    try {
        return JSON.parse(sanitized);
    } catch (e) {
        const textOnly = sanitized.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(textOnly ? textOnly.slice(0, 200) : `HTTP ${r.status}`);
    }
}

function post(data) {
    const fd = new FormData();
    for (const k in data) fd.append(k, data[k]);
    return postFormData(fd);
}

// ─── Add Category ─────────────────────────────────────────────────────────────
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const name_en = document.getElementById('catNameEn').value.trim();
    const name_ar = document.getElementById('catNameAr').value.trim();
    if (!name_en) { showToast('English name is required', 'error'); return; }

    post({ action: 'add_category', name_en, name_ar }).then(data => {
        if (data.success) {
            showToast('Category added!');
            this.reset();
            setTimeout(() => { window.location.reload(); }, 400);
        } else {
            showToast(data.error || 'Failed', 'error');
        }
    }).catch(err => {
        showToast('Network error: ' + err.message, 'error');
    });
});

// ─── Add Subcategory ──────────────────────────────────────────────────────────
document.getElementById('addSubcategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const category_id = document.getElementById('subCategoryParent').value;
    const name_en     = document.getElementById('subNameEn').value.trim();
    const name_ar     = document.getElementById('subNameAr').value.trim();
    const icon        = document.getElementById('subIcon').value.trim();
    if (!name_en)     { showToast('English name is required', 'error'); return; }
    if (!category_id) { showToast('Select a parent category', 'error'); return; }

    post({ action: 'add_subcategory', name_en, name_ar, icon, category_id }).then(data => {
        if (data.success) {
            showToast('Subcategory added!');
            this.reset();
            setTimeout(() => { window.location.reload(); }, 400);
        } else {
            showToast(data.error || 'Failed', 'error');
        }
    }).catch(err => {
        showToast('Network error: ' + err.message, 'error');
    });
});

// ─── Update Category Sort ───────────────────────────────────────────────────
function updateCategorySort(catId, input) {
    const prev = input.dataset.prev ?? input.value;
    const value = parseInt(input.value, 10);
    if (Number.isNaN(value) || value < 0) {
        showToast('Invalid sort order', 'error');
        input.value = prev;
        return;
    }

    input.disabled = true;
    post({ action: 'update_category_sort', id: catId, sort_order: value }).then(data => {
        if (data.success) {
            input.dataset.prev = String(data.sort_order ?? value);
            showToast('Category order updated');
        } else {
            input.value = prev;
            showToast(data.error || 'Failed', 'error');
        }
    }).catch(err => {
        input.value = prev;
        showToast('Network error: ' + err.message, 'error');
    }).finally(() => {
        input.disabled = false;
    });
}

// ─── Delete Category ──────────────────────────────────────────────────────────
function deleteCategory(id, btn, forceDelete = false) {
    if (!forceDelete && !confirm('Delete this category? Subcategories will be kept and moved to another category.')) return;
    btn.disabled = true;

    const payload = { action: 'delete_category', id };
    if (forceDelete) payload.force = 1;

    post(payload).then(data => {
        if (data.success) {
            document.getElementById('catBlock-' + id)?.remove();
            const moved = Number(data.moved_subcategories || 0);
            if (moved > 0) {
                showToast('Category deleted. ' + moved + ' subcategory(s) were preserved.');
            } else {
                showToast('Category deleted.');
            }
        } else if (data.requires_force && !forceDelete) {
            btn.disabled = false;
            const linked = Number(data.linked_subcategories || 0);
            const shouldForce = confirm('This category has ' + linked + ' subcategory(s). Delete category and keep those subcategories by moving them?');
            if (shouldForce) {
                deleteCategory(id, btn, true);
            }
        } else {
            showToast(data.error || 'Failed', 'error');
            btn.disabled = false;
        }
    }).catch(err => {
        btn.disabled = false;
        showToast('Network error: ' + err.message, 'error');
    });
}

// ─── Delete Subcategory ───────────────────────────────────────────────────────
function deleteSubcategory(subId, catId, btn, productCount = 0, forceDelete = false) {
    const count = Number(productCount || 0);
    if (!forceDelete) {
        const message = count > 0
            ? `This subcategory has ${count} product(s). Move them to "Uncategorized" and delete?`
            : 'Delete this subcategory?';
        if (!confirm(message)) return;
    }
    if (btn) btn.disabled = true;

    const payload = { action: 'delete_subcategory', id: subId };
    if (forceDelete || count > 0) {
        payload.force = 1;
    }

    post(payload).then(data => {
        if (data.success) {
            document.getElementById('subItem-' + subId)?.remove();
            const list = document.getElementById('subList-' + catId);
            if (list && list.querySelectorAll('.subcategory-item').length === 0) {
                const empty = document.createElement('div');
                empty.className = 'empty-sub';
                empty.id = 'emptySub-' + catId;
                empty.textContent = 'No subcategories yet.';
                list.appendChild(empty);
            }
            const moved = Number(data.moved_products || 0);
            if (moved > 0) {
                showToast('Subcategory deleted. ' + moved + ' product(s) moved to Uncategorized.');
            } else {
                showToast('Subcategory deleted.');
            }
        } else if (data.requires_force && !forceDelete) {
            if (btn) btn.disabled = false;
            const linked = Number(data.linked_products || 0);
            const shouldForce = confirm('This subcategory has ' + linked + ' product(s). Move them to "Uncategorized" and delete?');
            if (shouldForce) {
                deleteSubcategory(subId, catId, btn, linked, true);
            }
        } else {
            showToast(data.error || 'Failed', 'error');
            if (btn) btn.disabled = false;
        }
    }).catch(err => {
        if (btn) btn.disabled = false;
        showToast('Network error: ' + err.message, 'error');
    });
}

// ─── DOM helpers ──────────────────────────────────────────────────────────────
function appendCategoryBlock(id, name_en, name_ar) {
    const section = document.getElementById('categoriesSection');
    const noCategories = section.querySelector('p');
    if (noCategories) noCategories.remove();

    const block = document.createElement('div');
    block.className = 'category-block';
    block.id = 'catBlock-' + id;
    block.innerHTML = `
        <div class="category-header">
            <div>
                <div class="cat-title">
                    <i class="fas fa-folder" style="color: var(--warning);"></i>
                    ${escHtml(name_en)}
                    <span style="color:var(--text-gray);font-size:.8rem;">(ID: ${id})</span>
                </div>
                ${name_ar ? '<span class="cat-ar">' + escHtml(name_ar) + '</span>' : ''}
            </div>
            <div style="display:flex; align-items:center; gap:.75rem;">
                <div class="sort-control">
                    <label>Home order</label>
                    <input type="number" min="0" step="1" class="sort-input" value="0" data-prev="0" onchange="updateCategorySort(${id}, this)">
                </div>
                <button class="btn btn-danger btn-sm" onclick="deleteCategory(${id}, this)" title="Delete category">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </div>
        </div>
        <div class="subcategory-list" id="subList-${id}">
            <div class="empty-sub" id="emptySub-${id}">No subcategories yet.</div>
        </div>`;
    section.appendChild(block);
}

function appendSubcategoryItem(subId, catId, name_en, name_ar, icon) {
    const list = document.getElementById('subList-' + catId);
    if (!list) return;
    // Remove "empty" placeholder
    const empty = document.getElementById('emptySub-' + catId);
    if (empty) empty.remove();

    const item = document.createElement('div');
    item.className = 'subcategory-item';
    item.id = 'subItem-' + subId;
    const iconHtml = icon ? `<i class="${escHtml(icon)} sub-icon"></i>` : '<i class="fas fa-tag sub-icon"></i>';
    item.innerHTML = `
        <div class="sub-info">
            ${iconHtml}
            <div>
                <div class="sub-name">${escHtml(name_en)}</div>
                ${name_ar ? '<div class="sub-ar">' + escHtml(name_ar) + '</div>' : ''}
            </div>
            <span class="prod-badge">0 products</span>
        </div>
        <button class="btn btn-danger btn-sm" onclick="deleteSubcategory(${subId}, ${catId}, this, 0)" title="Delete subcategory">
            <i class="fas fa-trash-alt"></i>
        </button>`;
    list.appendChild(item);
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── Upload Category Image ────────────────────────────────────────────────────
function uploadCategoryImage(catId, input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) { showToast('File too large. Max 5MB.', 'error'); return; }
    
    const fd = new FormData();
    fd.append('action', 'upload_category_image');
    fd.append('category_id', catId);
    fd.append('category_image', file);
    
    showToast('Uploading image...');
    
    postFormData(fd)
        .then(data => {
            if (data.success) {
                showToast('Category image updated!');
                // Update preview
                const preview = document.getElementById('catImgPreview-' + catId);
                if (preview) {
                    if (preview.tagName === 'IMG') {
                        preview.src = '../../' + data.image_url;
                    } else {
                        preview.outerHTML = '<img src="../../' + data.image_url + '" style="width:100%;height:100%;object-fit:cover;" id="catImgPreview-' + catId + '">';
                    }
                }
            } else {
                showToast(data.error || 'Upload failed', 'error');
            }
        })
        .catch(err => showToast('Upload failed: ' + err.message, 'error'));
}

// ─── Upload Subcategory Image ─────────────────────────────────────────────────
function uploadSubcategoryImage(subId, input) {
    const file = input.files[0];
    if (!file) return;

    const fd = new FormData();
    fd.append('action', 'upload_subcategory_image');
    fd.append('subcategory_id', subId);
    fd.append('subcategory_image', file);

    postFormData(fd)
        .then(data => {
            if (data.success) {
                showToast('Subcategory image updated!');
                // Update preview
                const preview = document.getElementById('subImgPreview-' + subId);
                if (preview) {
                    if (preview.tagName === 'IMG') {
                        preview.src = '../../' + data.image_url;
                    } else {
                        preview.outerHTML = '<img src="../../' + data.image_url + '" style="width:100%;height:100%;object-fit:cover;" id="subImgPreview-' + subId + '">';
                    }
                }
            } else {
                showToast(data.error || 'Upload failed', 'error');
            }
        })
        .catch(err => showToast('Upload failed: ' + err.message, 'error'));
}
</script>
</body>
</html>
