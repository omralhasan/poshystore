<?php
/**
 * Barcode Lookup API
 * 
 * 1. Checks local products table for matching barcode
 * 2. If not found, queries Open Food Facts API
 * 3. Returns JSON with product info
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/db_connect.php';

    $barcode = trim(preg_replace('/[^A-Za-z0-9]/', '', $_GET['barcode'] ?? ''));

    if (empty($barcode)) {
        echo json_encode(['status' => 'error', 'message' => 'No barcode provided']);
        exit();
    }

    // 1. Check local database first
    $stmt = $conn->prepare(
        "SELECT id, name_en, name_ar, slug, price_jod, stock_quantity, image_link, barcode, cost, supplier_cost
         FROM products WHERE barcode = ?"
    );
    $stmt->bind_param('s', $barcode);
    $stmt->execute();
    $local = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($local) {
        echo json_encode([
            'found'    => 'local',
            'product'  => [
                'id'             => (int) $local['id'],
                'name_en'        => $local['name_en'],
                'name_ar'        => $local['name_ar'],
                'slug'           => $local['slug'],
                'price_jod'      => (float) ($local['price_jod'] ?? 0),
                'stock_quantity' => (int) ($local['stock_quantity'] ?? 0),
                'image_link'     => $local['image_link'] ?? '',
                'barcode'        => $local['barcode'],
                'cost'           => $local['cost'] !== null ? (float) $local['cost'] : null,
                'supplier_cost'  => $local['supplier_cost'] !== null ? (float) $local['supplier_cost'] : null,
            ],
        ]);
        exit();
    }

    // 2. Look up via Open Food Facts API
    $off_url = "https://world.openfoodfacts.org/api/v2/product/{$barcode}.json";
    $ctx = stream_context_create([
        'http' => [
            'timeout'    => 8,
            'user_agent' => 'PoshyLifestyle/1.0 (admin barcode scanner)',
        ],
    ]);

    $response = @file_get_contents($off_url, false, $ctx);

    if ($response === false) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Could not reach barcode database',
            'barcode' => $barcode,
        ]);
        exit();
    }

    $data = json_decode($response, true);

    if (empty($data) || empty($data['product'])) {
        echo json_encode([
            'found'   => false,
            'barcode' => $barcode,
        ]);
        exit();
    }

    $product = $data['product'];
    $product_name = $product['product_name'] ?? $product['product_name_en'] ?? '';

    echo json_encode([
        'found'   => 'api',
        'barcode' => $barcode,
        'product' => [
            'name_en'       => !empty($product_name) ? $product_name : 'Unknown Product',
            'name_ar'       => $product['product_name_ar'] ?? '',
            'brand'         => $product['brands'] ?? '',
            'image_url'     => $product['image_url'] ?? $product['image_front_url'] ?? '',
            'quantity'      => $product['quantity'] ?? '',
            'categories'    => $product['categories'] ?? '',
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
