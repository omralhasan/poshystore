<?php
/**
 * Clean-URL product page handler
 * 
 * Accessed via: /poshy_store/product-slug-name
 * Rewritten by .htaccess to: product.php?slug=product-slug-name
 * 
 * This file looks up the product by slug and includes the
 * existing product_detail.php template, keeping all logic in one place.
 */

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/product_manager.php';

// Get slug from rewritten URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug) || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
    header('Location: index.php');
    exit;
}

// Look up product by slug
$product_result = getProductBySlug($slug);

if (!$product_result['success']) {
    // Try legacy numeric ID as fallback
    http_response_code(404);
    header('Location: index.php');
    exit;
}

// Set the product ID in $_GET so the detail page template can find it
$_GET['id'] = $product_result['product']['id'];

// Include the full product detail page
require __DIR__ . '/pages/shop/product_detail.php';
