<?php
/**
 * Admin Product Information Editor
 * Allows admins to edit product details, description, and how to use fields
 */

require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_connect.php';

// Check admin access
if (!isAdmin()) {
    header('Location: ../../index.php');
    exit;
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $description = trim($_POST['description']);
    $product_details = trim($_POST['product_details']);
    $how_to_use = trim($_POST['how_to_use']);
    $video_review_url = trim($_POST['video_review_url']);
    
    $update_sql = "UPDATE products SET 
                   description = ?, 
                   product_details = ?, 
                   how_to_use = ?,
                   video_review_url = ?
                   WHERE id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('ssssi', $description, $product_details, $how_to_use, $video_review_url, $product_id);
    
    if ($stmt->execute()) {
        $message = "Product information updated successfully!";
    } else {
        $error = "Failed to update product information.";
    }
    $stmt->close();
}

// Get selected product or first product
$selected_product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Get all products for selection
$products_sql = "SELECT id, name_en FROM products ORDER BY name_en";
$products_result = $conn->query($products_sql);
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
    if (!$selected_product_id) {
        $selected_product_id = $row['id'];
    }
}

// Get selected product data
$product = null;
if ($selected_product_id) {
    $product_sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($product_sql);
    $stmt->bind_param('i', $selected_product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product Information - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 2rem;
        }
        
        .editor-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--purple-color);
            margin-bottom: 0.5rem;
        }
        
        textarea {
            min-height: 200px;
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
        }
        
        .btn-save {
            background: linear-gradient(135deg, var(--gold-color), #b39358);
            color: white;
            border: none;
            padding: 12px 30px;
            font-weight: 700;
            border-radius: 8px;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(201, 168, 106, 0.4);
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-edit"></i> Edit Product Information</h2>
            <a href="admin_panel.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Admin Panel
            </a>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <div class="mb-4">
            <label class="form-label">Select Product:</label>
            <select class="form-select" onchange="window.location.href='?id=' + this.value">
                <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $selected_product_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name_en']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($product): ?>
        <form method="POST">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-align-left"></i> Product Description
                    <small class="text-muted">(Main product description shown in tab)</small>
                </label>
                <textarea name="description" class="form-control" placeholder="Enter product description..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-info-circle"></i> Product Details
                    <small class="text-muted">(Technical specifications, features, etc.)</small>
                </label>
                <textarea name="product_details" class="form-control" placeholder="Enter product details...&#10;Example:&#10;- Brand: Poshy Store&#10;- Type: Premium Product&#10;- Size: Standard&#10;- Suitable for: All types"><?= htmlspecialchars($product['product_details'] ?? '') ?></textarea>
                <small class="text-muted">Use line breaks to separate items</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-book-open"></i> How to Use
                    <small class="text-muted">(Step-by-step usage instructions)</small>
                </label>
                <textarea name="how_to_use" class="form-control" placeholder="Enter usage instructions...&#10;Example:&#10;1. Step one&#10;2. Step two&#10;3. Step three"><?= htmlspecialchars($product['how_to_use'] ?? '') ?></textarea>
                <small class="text-muted">Use numbered lists for clear instructions</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-play-circle"></i> See in Action Video URL
                    <small class="text-muted">(YouTube embed URL or video link)</small>
                </label>
                <input type="url" name="video_review_url" class="form-control" placeholder="https://www.youtube.com/embed/VIDEO_ID" value="<?= htmlspecialchars($product['video_review_url'] ?? '') ?>">
                <small class="text-muted">
                    <strong>Examples:</strong><br>
                    • YouTube: https://www.youtube.com/embed/VIDEO_ID<br>
                    • Vimeo: https://player.vimeo.com/video/VIDEO_ID<br>
                    This video appears in the first tab "See in Action" on product page
                </small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="update_product" class="btn-save">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="<?= BASE_PATH ?>/<?= htmlspecialchars($product['slug']) ?>" class="btn btn-info" target="_blank">
                    <i class="fas fa-eye"></i> Preview Product Page
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
