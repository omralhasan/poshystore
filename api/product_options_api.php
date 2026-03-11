<?php
/**
 * Product Options API – Admin
 * AJAX endpoints for managing product variants (size, color)
 * Each option is ONE type: either Size or Color (not both together).
 * Each value can have its own price (JOD) and its own image.
 */
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

switch ($action) {
    case 'get_options':     getProductOptions($product_id); break;
    case 'add_option':      addOption($product_id); break;
    case 'delete_option':   deleteOption(); break;
    case 'add_value':       addOptionValue(); break;
    case 'update_value':    updateOptionValue(); break;
    case 'delete_value':    deleteOptionValue(); break;
    default: echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

/* ── helpers ─────────────────────────────────────────── */
function uploadOptionImage() {
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) return null;
    $file = $_FILES['image'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) return null;
    $name = 'opt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/options/' . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return '/uploads/options/' . $name;
    }
    return null;
}

/* ── GET options with values ─────────────────────────── */
function getProductOptions($product_id) {
    global $conn;
    if (!$product_id) { echo json_encode(['success'=>false,'error'=>'Product ID required']); return; }

    $options = [];
    $stmt = $conn->prepare("SELECT * FROM product_options WHERE product_id = ? ORDER BY sort_order, id");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($opt = $result->fetch_assoc()) {
        $vs = $conn->prepare("SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order, id");
        $vs->bind_param('i', $opt['id']);
        $vs->execute();
        $vr = $vs->get_result();
        $opt['values'] = [];
        while ($v = $vr->fetch_assoc()) { $opt['values'][] = $v; }
        $vs->close();
        $options[] = $opt;
    }
    $stmt->close();
    echo json_encode(['success'=>true,'options'=>$options]);
}

/* ── ADD option (Size or Color) ──────────────────────── */
function addOption($product_id) {
    global $conn;
    if (!$product_id) { echo json_encode(['success'=>false,'error'=>'Product ID required']); return; }

    $type = in_array($_POST['option_type'] ?? '', ['size','color']) ? $_POST['option_type'] : 'size';

    // Auto-fill name based on type
    $name_en = $type === 'color' ? 'Color' : 'Size';
    $name_ar = $type === 'color' ? 'اللون'  : 'الحجم';

    $stmt = $conn->prepare("INSERT INTO product_options (product_id, option_name_en, option_name_ar, option_type, is_required) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param('isss', $product_id, $name_en, $name_ar, $type);

    if ($stmt->execute()) {
        $oid = $stmt->insert_id;
        $stmt->close();
        $conn->query("UPDATE products SET has_options = 1 WHERE id = $product_id");
        echo json_encode(['success'=>true,'option_id'=>$oid,'message'=>'Option added']);
    } else {
        echo json_encode(['success'=>false,'error'=>'Failed: '.$stmt->error]);
    }
}

/* ── DELETE option ───────────────────────────────────── */
function deleteOption() {
    global $conn;
    $option_id = intval($_POST['option_id'] ?? 0);
    if (!$option_id) { echo json_encode(['success'=>false,'error'=>'Option ID required']); return; }

    $stmt = $conn->prepare("SELECT product_id FROM product_options WHERE id = ?");
    $stmt->bind_param('i', $option_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $pid = $row['product_id'] ?? 0;

    // Delete images on disk
    $imgs = $conn->prepare("SELECT image FROM product_option_values WHERE option_id = ? AND image IS NOT NULL");
    $imgs->bind_param('i', $option_id);
    $imgs->execute();
    $ir = $imgs->get_result();
    while ($img = $ir->fetch_assoc()) {
        $path = __DIR__ . '/..' . $img['image'];
        if (file_exists($path)) @unlink($path);
    }
    $imgs->close();

    $conn->prepare("DELETE FROM product_option_values WHERE option_id = ?")->execute() || true;
    $del = $conn->prepare("DELETE FROM product_options WHERE id = ?");
    $del->bind_param('i', $option_id);
    $del->execute();
    $del->close();

    if ($pid) {
        $chk = $conn->query("SELECT COUNT(*) as cnt FROM product_options WHERE product_id = $pid");
        if ($chk->fetch_assoc()['cnt'] == 0) {
            $conn->query("UPDATE products SET has_options = 0 WHERE id = $pid");
        }
    }
    echo json_encode(['success'=>true,'message'=>'Option deleted']);
}

/* ── ADD value (with price & optional image) ─────────── */
function addOptionValue() {
    global $conn;
    $option_id = intval($_POST['option_id'] ?? 0);
    if (!$option_id) { echo json_encode(['success'=>false,'error'=>'Option ID required']); return; }

    $value_en  = trim($_POST['value_en'] ?? '');
    $value_ar  = trim($_POST['value_ar'] ?? '');
    $color_hex = trim($_POST['color_hex'] ?? '') ?: null;
    $price_jod = ($_POST['price_jod'] ?? '') !== '' ? floatval($_POST['price_jod']) : null;
    $is_default = intval($_POST['is_default'] ?? 0);
    $image = uploadOptionImage();

    if (empty($value_en)) { echo json_encode(['success'=>false,'error'=>'Value (English) is required']); return; }

    $stmt = $conn->prepare("INSERT INTO product_option_values (option_id, value_en, value_ar, color_hex, price_jod, image, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssdsi', $option_id, $value_en, $value_ar, $color_hex, $price_jod, $image, $is_default);

    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'value_id'=>$stmt->insert_id,'message'=>'Value added']);
    } else {
        echo json_encode(['success'=>false,'error'=>'Failed: '.$stmt->error]);
    }
    $stmt->close();
}

/* ── UPDATE value ────────────────────────────────────── */
function updateOptionValue() {
    global $conn;
    $value_id = intval($_POST['value_id'] ?? 0);
    if (!$value_id) { echo json_encode(['success'=>false,'error'=>'Value ID required']); return; }

    $value_en  = trim($_POST['value_en'] ?? '');
    $value_ar  = trim($_POST['value_ar'] ?? '');
    $color_hex = trim($_POST['color_hex'] ?? '') ?: null;
    $price_jod = ($_POST['price_jod'] ?? '') !== '' ? floatval($_POST['price_jod']) : null;
    $is_default = intval($_POST['is_default'] ?? 0);
    $image = uploadOptionImage();

    if ($image) {
        // Delete old image
        $old = $conn->prepare("SELECT image FROM product_option_values WHERE id = ?");
        $old->bind_param('i', $value_id);
        $old->execute();
        $oldRow = $old->get_result()->fetch_assoc();
        $old->close();
        if (!empty($oldRow['image'])) {
            $p = __DIR__ . '/..' . $oldRow['image'];
            if (file_exists($p)) @unlink($p);
        }
        $stmt = $conn->prepare("UPDATE product_option_values SET value_en=?, value_ar=?, color_hex=?, price_jod=?, image=?, is_default=? WHERE id=?");
        $stmt->bind_param('sssdsii', $value_en, $value_ar, $color_hex, $price_jod, $image, $is_default, $value_id);
    } else {
        $stmt = $conn->prepare("UPDATE product_option_values SET value_en=?, value_ar=?, color_hex=?, price_jod=?, is_default=? WHERE id=?");
        $stmt->bind_param('sssdii', $value_en, $value_ar, $color_hex, $price_jod, $is_default, $value_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Value updated']);
    } else {
        echo json_encode(['success'=>false,'error'=>'Update failed']);
    }
    $stmt->close();
}

/* ── DELETE value ────────────────────────────────────── */
function deleteOptionValue() {
    global $conn;
    $value_id = intval($_POST['value_id'] ?? 0);
    if (!$value_id) { echo json_encode(['success'=>false,'error'=>'Value ID required']); return; }

    // Delete image file
    $old = $conn->prepare("SELECT image FROM product_option_values WHERE id = ?");
    $old->bind_param('i', $value_id);
    $old->execute();
    $row = $old->get_result()->fetch_assoc();
    $old->close();
    if (!empty($row['image'])) {
        $p = __DIR__ . '/..' . $row['image'];
        if (file_exists($p)) @unlink($p);
    }

    $stmt = $conn->prepare("DELETE FROM product_option_values WHERE id = ?");
    $stmt->bind_param('i', $value_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true,'message'=>'Value deleted']);
}
