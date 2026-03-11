<?php
/**
 * Product Options API – Admin
 * AJAX endpoints for managing product variants (size, color, etc.)
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
    case 'get_options':
        getProductOptions($product_id);
        break;
    case 'add_option':
        addOption($product_id);
        break;
    case 'update_option':
        updateOption();
        break;
    case 'delete_option':
        deleteOption();
        break;
    case 'add_value':
        addOptionValue();
        break;
    case 'update_value':
        updateOptionValue();
        break;
    case 'delete_value':
        deleteOptionValue();
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

function getProductOptions($product_id) {
    global $conn;
    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID required']);
        return;
    }
    
    $options = [];
    $stmt = $conn->prepare("SELECT * FROM product_options WHERE product_id = ? ORDER BY sort_order, id");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($opt = $result->fetch_assoc()) {
        // Get values for this option
        $val_stmt = $conn->prepare("SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order, id");
        $val_stmt->bind_param('i', $opt['id']);
        $val_stmt->execute();
        $val_result = $val_stmt->get_result();
        $opt['values'] = [];
        while ($val = $val_result->fetch_assoc()) {
            $opt['values'][] = $val;
        }
        $val_stmt->close();
        $options[] = $opt;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'options' => $options]);
}

function addOption($product_id) {
    global $conn;
    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID required']);
        return;
    }
    
    $name_en = trim($_POST['option_name_en'] ?? '');
    $name_ar = trim($_POST['option_name_ar'] ?? '');
    $type = in_array($_POST['option_type'] ?? '', ['select', 'color']) ? $_POST['option_type'] : 'select';
    $required = intval($_POST['is_required'] ?? 0);
    
    if (empty($name_en)) {
        echo json_encode(['success' => false, 'error' => 'Option name (English) is required']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO product_options (product_id, option_name_en, option_name_ar, option_type, is_required) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isssi', $product_id, $name_en, $name_ar, $type, $required);
    
    if ($stmt->execute()) {
        $option_id = $stmt->insert_id;
        $stmt->close();
        
        // Update has_options flag
        $conn->query("UPDATE products SET has_options = 1 WHERE id = $product_id");
        
        echo json_encode(['success' => true, 'option_id' => $option_id, 'message' => 'Option added']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add option: ' . $stmt->error]);
    }
}

function updateOption() {
    global $conn;
    $option_id = intval($_POST['option_id'] ?? 0);
    if (!$option_id) {
        echo json_encode(['success' => false, 'error' => 'Option ID required']);
        return;
    }
    
    $name_en = trim($_POST['option_name_en'] ?? '');
    $name_ar = trim($_POST['option_name_ar'] ?? '');
    $type = in_array($_POST['option_type'] ?? '', ['size', 'color', 'custom']) ? $_POST['option_type'] : 'custom';
    $required = intval($_POST['is_required'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE product_options SET option_name_en=?, option_name_ar=?, option_type=?, is_required=? WHERE id=?");
    $stmt->bind_param('sssii', $name_en, $name_ar, $type, $required, $option_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Option updated']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    $stmt->close();
}

function deleteOption() {
    global $conn;
    $option_id = intval($_POST['option_id'] ?? 0);
    if (!$option_id) {
        echo json_encode(['success' => false, 'error' => 'Option ID required']);
        return;
    }
    
    // Get product_id before deleting
    $stmt = $conn->prepare("SELECT product_id FROM product_options WHERE id = ?");
    $stmt->bind_param('i', $option_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $product_id = $row['product_id'] ?? 0;
    
    // Delete option (cascades to values)
    $stmt = $conn->prepare("DELETE FROM product_options WHERE id = ?");
    $stmt->bind_param('i', $option_id);
    $stmt->execute();
    $stmt->close();
    
    // Check if product still has options
    if ($product_id) {
        $check = $conn->query("SELECT COUNT(*) as cnt FROM product_options WHERE product_id = $product_id");
        $cnt = $check->fetch_assoc()['cnt'];
        if ($cnt == 0) {
            $conn->query("UPDATE products SET has_options = 0 WHERE id = $product_id");
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Option deleted']);
}

function addOptionValue() {
    global $conn;
    $option_id = intval($_POST['option_id'] ?? 0);
    if (!$option_id) {
        echo json_encode(['success' => false, 'error' => 'Option ID required']);
        return;
    }
    
    $value_en = trim($_POST['value_en'] ?? '');
    $value_ar = trim($_POST['value_ar'] ?? '');
    $color_hex = trim($_POST['color_hex'] ?? '') ?: null;
    $price_adj = floatval($_POST['price_adjustment'] ?? 0);
    $stock = ($_POST['stock_quantity'] ?? '') !== '' ? intval($_POST['stock_quantity']) : null;
    $is_default = intval($_POST['is_default'] ?? 0);
    
    if (empty($value_en)) {
        echo json_encode(['success' => false, 'error' => 'Value (English) is required']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO product_option_values (option_id, value_en, value_ar, color_hex, price_adjustment, stock_quantity, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssdii', $option_id, $value_en, $value_ar, $color_hex, $price_adj, $stock, $is_default);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'value_id' => $stmt->insert_id, 'message' => 'Value added']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add value: ' . $stmt->error]);
    }
    $stmt->close();
}

function updateOptionValue() {
    global $conn;
    $value_id = intval($_POST['value_id'] ?? 0);
    if (!$value_id) {
        echo json_encode(['success' => false, 'error' => 'Value ID required']);
        return;
    }
    
    $value_en = trim($_POST['value_en'] ?? '');
    $value_ar = trim($_POST['value_ar'] ?? '');
    $color_hex = trim($_POST['color_hex'] ?? '') ?: null;
    $price_adj = floatval($_POST['price_adjustment'] ?? 0);
    $stock = ($_POST['stock_quantity'] ?? '') !== '' ? intval($_POST['stock_quantity']) : null;
    $is_default = intval($_POST['is_default'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE product_option_values SET value_en=?, value_ar=?, color_hex=?, price_adjustment=?, stock_quantity=?, is_default=? WHERE id=?");
    $stmt->bind_param('sssdiil', $value_en, $value_ar, $color_hex, $price_adj, $stock, $is_default, $value_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Value updated']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    $stmt->close();
}

function deleteOptionValue() {
    global $conn;
    $value_id = intval($_POST['value_id'] ?? 0);
    if (!$value_id) {
        echo json_encode(['success' => false, 'error' => 'Value ID required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM product_option_values WHERE id = ?");
    $stmt->bind_param('i', $value_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Value deleted']);
}
