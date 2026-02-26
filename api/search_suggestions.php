<?php
/**
 * Search Suggestions API
 * Returns product name suggestions for autocomplete.
 * 
 * GET /api/search_suggestions.php?q=text[&lang=en|ar][&limit=8]
 * Response: JSON array of { id, name_en, name_ar, slug, price, image }
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$q     = trim($_GET['q'] ?? '');
$lang  = in_array($_GET['lang'] ?? 'en', ['en', 'ar']) ? $_GET['lang'] : 'en';
$limit = max(1, min(12, (int)($_GET['limit'] ?? 8)));

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$search = '%' . $q . '%';

// Also search brands and categories so user can type "cosrx" and get matches
$stmt = $conn->prepare("
    SELECT p.id, p.name_en, p.name_ar, p.slug, p.price_jod, p.image_link,
           b.name_en AS brand_en, b.name_ar AS brand_ar,
           s.name_en AS subcategory_en, s.name_ar AS subcategory_ar
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    WHERE p.stock_quantity > 0
      AND (
          p.name_en    LIKE ? OR
          p.name_ar    LIKE ? OR
          b.name_en    LIKE ? OR
          b.name_ar    LIKE ? OR
          s.name_en    LIKE ? OR
          s.name_ar    LIKE ?
      )
    ORDER BY
        (p.name_en LIKE ? OR p.name_ar LIKE ?) DESC,
        p.id DESC
    LIMIT ?
");

if (!$stmt) {
    echo json_encode(['error' => 'DB error']);
    exit;
}

$stmt->bind_param(
    'ssssssssi',
    $search, $search, $search, $search, $search, $search,
    $search, $search,
    $limit
);
$stmt->execute();
$result = $stmt->get_result();

// Include image helper to resolve image paths properly
require_once __DIR__ . '/../includes/product_image_helper.php';

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $name = $lang === 'ar' && !empty($row['name_ar']) ? $row['name_ar'] : $row['name_en'];
    $sub  = $lang === 'ar' && !empty($row['subcategory_ar'])
              ? $row['subcategory_ar'] : ($row['subcategory_en'] ?? '');

    // Get proper image path using image helper
    $image_path = get_product_thumbnail(
        $row['name_en'],
        $row['image_link'] ?? '',
        __DIR__ . '/..'
    );

    $suggestions[] = [
        'id'     => (int)$row['id'],
        'name'   => $name,
        'name_en' => $row['name_en'],
        'name_ar' => $row['name_ar'] ?? '',
        'slug'   => $row['slug'] ?? '',
        'price'  => number_format((float)$row['price_jod'], 3) . ' JOD',
        'image'  => $image_path,
        'brand'  => $lang === 'ar' && !empty($row['brand_ar']) ? $row['brand_ar'] : ($row['brand_en'] ?? ''),
        'category' => $sub,
    ];
}
$stmt->close();

echo json_encode($suggestions);
