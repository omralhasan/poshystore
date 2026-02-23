<?php
/**
 * Product Manager for Poshy Lifestyle E-Commerce
 * 
 * Handles all product-related operations
 * Connects to: products table (id, name_en, name_ar, description, price, stock, image_url, category_id)
 */

require_once __DIR__ . '/db_connect.php';

/**
 * Get all products with optional filtering
 * 
 * @param array $filters Optional filters (category_id, min_price, max_price, search, in_stock)
 * @param int $limit Number of products to return
 * @param int $offset Offset for pagination
 * @return array Response with products array
 */
function getAllProducts($filters = [], $limit = 50, $offset = 0) {
    global $conn;
    
    // Base query - joins with subcategories and categories
    $sql = "SELECT p.id, p.name_en, p.name_ar, p.slug, p.short_description_en, p.short_description_ar, p.description, 
                   p.price_jod, p.stock_quantity, p.image_link, p.subcategory_id, p.brand_id,
                   p.original_price, p.discount_percentage, p.has_discount,
                   s.name_en AS subcategory_en, s.name_ar AS subcategory_ar,
                   c.name_en AS category_en, c.name_ar AS category_ar,
                   b.name_en AS brand_en, b.name_ar AS brand_ar
            FROM products p
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN categories c ON s.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Filter by subcategory
    if (isset($filters['subcategory_id']) && !empty($filters['subcategory_id'])) {
        $sql .= " AND p.subcategory_id = ?";
        $types .= 'i';
        $params[] = (int)$filters['subcategory_id'];
    }
    
    // Filter by category (all subcategories under it)
    if (isset($filters['category_id']) && !empty($filters['category_id'])) {
        $sql .= " AND s.category_id = ?";
        $types .= 'i';
        $params[] = (int)$filters['category_id'];
    }
    
    // Apply filters
    if (isset($filters['min_price']) && !empty($filters['min_price'])) {
        $sql .= " AND price_jod >= ?";
        $types .= 'd';
        $params[] = $filters['min_price'];
    }
    
    if (isset($filters['max_price']) && !empty($filters['max_price'])) {
        $sql .= " AND price_jod <= ?";
        $types .= 'd';
        $params[] = $filters['max_price'];
    }
    
    if (isset($filters['search']) && !empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $sql .= " AND (p.name_en LIKE ? OR p.name_ar LIKE ? OR p.id IN (SELECT pt.product_id FROM product_tags pt JOIN tags t ON pt.tag_id = t.id WHERE t.name_en LIKE ? OR t.name_ar LIKE ?))";
        $types .= 'ssss';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    if (isset($filters['in_stock']) && $filters['in_stock'] == true) {
        $sql .= " AND p.stock_quantity > 0";
    }
    
    // Filter by brand
    if (isset($filters['brand_id']) && !empty($filters['brand_id'])) {
        $sql .= " AND p.brand_id = ?";
        $types .= 'i';
        $params[] = (int)$filters['brand_id'];
    }
    
    // Filter by tag (search products that have a specific tag)
    if (isset($filters['tag']) && !empty($filters['tag'])) {
        $tag_search = '%' . $filters['tag'] . '%';
        $sql .= " AND p.id IN (SELECT pt.product_id FROM product_tags pt JOIN tags t ON pt.tag_id = t.id WHERE t.name_en LIKE ? OR t.name_ar LIKE ? OR t.slug LIKE ?)";
        $types .= 'sss';
        $params[] = $tag_search;
        $params[] = $tag_search;
        $params[] = $tag_search;
    }
    
    // Add ordering
    $sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    
    // Prepare and execute
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Get products prepare failed: " . $conn->error);
        return [
            'success' => false,
            'error' => 'Failed to fetch products'
        ];
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['price_formatted'] = formatJOD($row['price_jod']);
        $row['in_stock'] = $row['stock_quantity'] > 0;
        $products[] = $row;
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ];
}

/**
 * Get single product by ID
 * 
 * @param int $id Product ID
 * @return array Response with product data
 */
function getProductById($id) {
    global $conn;
    
    if (!is_numeric($id) || $id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid product ID'
        ];
    }
    
    // Query products table by ID with category info
    $sql = "SELECT p.id, p.name_en, p.name_ar, p.slug, p.short_description_en, p.short_description_ar, 
                   p.description, p.description_ar, p.product_details, p.product_details_ar, p.how_to_use_en, p.how_to_use_ar, p.video_review_url,
                   p.price_jod, p.stock_quantity, p.image_link, p.subcategory_id, p.brand_id,
                   p.original_price, p.discount_percentage, p.has_discount,
                   s.name_en AS subcategory_en, s.name_ar AS subcategory_ar,
                   c.name_en AS category_en, c.name_ar AS category_ar, c.id AS category_id,
                   b.name_en AS brand_en, b.name_ar AS brand_ar, b.logo AS brand_logo
            FROM products p
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN categories c ON s.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Get product prepare failed: " . $conn->error);
        return [
            'success' => false,
            'error' => 'Failed to fetch product'
        ];
    }
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        $product['price_formatted'] = formatJOD($product['price_jod']);
        $product['in_stock'] = ($product['stock_quantity'] > 0);
        
        $stmt->close();
        
        return [
            'success' => true,
            'product' => $product
        ];
    } else {
        $stmt->close();
        return [
            'success' => false,
            'error' => 'Product not found'
        ];
    }
}

/**
 * Get single product by slug (for clean URLs)
 *
 * @param string $slug Product slug
 * @return array Response with product data
 */
function getProductBySlug($slug) {
    global $conn;
    
    if (empty($slug) || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
        return [
            'success' => false,
            'error' => 'Invalid product slug'
        ];
    }
    
    $sql = "SELECT p.id, p.name_en, p.name_ar, p.slug, p.short_description_en, p.short_description_ar, 
                   p.description, p.description_ar, p.product_details, p.product_details_ar,
                   p.how_to_use_en, p.how_to_use_ar, p.video_review_url,
                   p.price_jod, p.stock_quantity, p.image_link, p.subcategory_id, p.brand_id,
                   p.original_price, p.discount_percentage, p.has_discount,
                   s.name_en AS subcategory_en, s.name_ar AS subcategory_ar,
                   c.name_en AS category_en, c.name_ar AS category_ar, c.id AS category_id,
                   b.name_en AS brand_en, b.name_ar AS brand_ar, b.logo AS brand_logo
            FROM products p
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN categories c ON s.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.slug = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Get product by slug prepare failed: " . $conn->error);
        return ['success' => false, 'error' => 'Failed to fetch product'];
    }
    
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        $product['price_formatted'] = formatJOD($product['price_jod']);
        $product['in_stock'] = ($product['stock_quantity'] > 0);
        $stmt->close();
        return ['success' => true, 'product' => $product];
    }
    
    $stmt->close();
    return ['success' => false, 'error' => 'Product not found'];
}

/**
 * Update product stock quantity
 * 
 * @param int $id Product ID
 * @param int $qty New stock quantity (can be negative for decrement)
 * @param bool $increment If true, adds to current stock; if false, sets absolute value
 * @return array Response with success status
 */
function updateStock($id, $qty, $increment = false) {
    global $conn;
    
    if (!is_numeric($id) || $id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid product ID'
        ];
    }
    
    if (!is_numeric($qty)) {
        return [
            'success' => false,
            'error' => 'Invalid quantity'
        ];
    }
    
    // Start transaction for data consistency
    $conn->begin_transaction();
    
    try {
        // Lock row for update
        $check_sql = "SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Product not found');
        }
        
        $current_stock = $result->fetch_assoc()['stock_quantity'];
        $check_stmt->close();
        
        // Calculate new stock
        if ($increment) {
            $new_stock = $current_stock + $qty;
        } else {
            $new_stock = $qty;
        }
        
        // Prevent negative stock
        if ($new_stock < 0) {
            throw new Exception('Insufficient stock');
        }
        
        // Update stock in products table
        $update_sql = "UPDATE products SET stock_quantity = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $new_stock, $id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Stock updated successfully',
            'old_stock' => $current_stock,
            'new_stock' => $new_stock
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Update stock failed: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Check if product has sufficient stock
 * 
 * @param int $id Product ID
 * @param int $required_qty Required quantity
 * @return bool True if sufficient stock available
 */
function checkStock($id, $required_qty) {
    global $conn;
    
    $sql = "SELECT stock_quantity FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $stock = $result->fetch_assoc()['stock_quantity'];
        $stmt->close();
        return $stock >= $required_qty;
    }
    
    $stmt->close();
    return false;
}

/**
 * Get products by category
 * 
 * @param int $category_id Category ID
 * @param int $limit Number of products to return
 * @return array Response with products
 */
function getProductsByCategory($category_id, $limit = 20) {
    return getAllProducts(['category_id' => $category_id, 'in_stock' => true], $limit);
}

/**
 * Get products by subcategory
 */
function getProductsBySubcategory($subcategory_id, $limit = 20) {
    return getAllProducts(['subcategory_id' => $subcategory_id, 'in_stock' => true], $limit);
}

/**
 * Get all categories with their subcategories
 */
function getAllCategories() {
    global $conn;
    $sql = "SELECT c.id AS category_id, c.name_en AS category_en, c.name_ar AS category_ar, c.icon AS category_icon,
                   s.id AS subcategory_id, s.name_en AS subcategory_en, s.name_ar AS subcategory_ar, s.icon AS subcategory_icon,
                   (SELECT COUNT(*) FROM products p WHERE p.subcategory_id = s.id AND p.stock_quantity > 0) AS product_count
            FROM categories c
            LEFT JOIN subcategories s ON s.category_id = c.id
            ORDER BY c.sort_order, s.sort_order";
    $result = $conn->query($sql);
    if (!$result) return [];
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $cid = $row['category_id'];
        if (!isset($categories[$cid])) {
            $categories[$cid] = [
                'id' => $cid,
                'name_en' => $row['category_en'],
                'name_ar' => $row['category_ar'],
                'icon' => $row['category_icon'],
                'subcategories' => []
            ];
        }
        if ($row['subcategory_id']) {
            $categories[$cid]['subcategories'][] = [
                'id' => $row['subcategory_id'],
                'name_en' => $row['subcategory_en'],
                'name_ar' => $row['subcategory_ar'],
                'icon' => $row['subcategory_icon'],
                'product_count' => (int)$row['product_count']
            ];
        }
    }
    return array_values($categories);
}

/**
 * Search products
 * 
 * @param string $search_term Search term
 * @param int $limit Number of results
 * @return array Response with products
 */
function searchProducts($search_term, $limit = 20) {
    return getAllProducts(['search' => $search_term, 'in_stock' => true], $limit);
}

/**
 * Update product price (admin only)
 * 
 * @param int $product_id Product ID
 * @param float $new_price New price in JOD
 * @return array Response with success status
 */
function updateProductPrice($product_id, $new_price) {
    global $conn;
    
    if (!is_numeric($product_id) || $product_id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid product ID'
        ];
    }
    
    if (!is_numeric($new_price) || $new_price < 0) {
        return [
            'success' => false,
            'error' => 'Invalid price'
        ];
    }
    
    // Get old price first
    $check_sql = "SELECT price_jod, name_en FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $check_stmt->close();
        return [
            'success' => false,
            'error' => 'Product not found'
        ];
    }
    
    $product = $result->fetch_assoc();
    $old_price = $product['price_jod'];
    $product_name = $product['name_en'];
    $check_stmt->close();
    
    // Update price
    $update_sql = "UPDATE products SET price_jod = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('di', $new_price, $product_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        return [
            'success' => true,
            'message' => 'Price updated successfully',
            'product_id' => $product_id,
            'product_name' => $product_name,
            'old_price' => $old_price,
            'new_price' => $new_price,
            'old_price_formatted' => formatJOD($old_price),
            'new_price_formatted' => formatJOD($new_price)
        ];
    } else {
        $update_stmt->close();
        return [
            'success' => false,
            'error' => 'Failed to update price'
        ];
    }
}

/**
 * Apply discount percentage to product price
 * 
 * @param int $product_id Product ID
 * @param float $discount_percentage Discount percentage (e.g., 20 for 20%)
 * @return array Response with success status
 */
function applyProductDiscount($product_id, $discount_percentage) {
    global $conn;
    
    if (!is_numeric($product_id) || $product_id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid product ID'
        ];
    }
    
    if (!is_numeric($discount_percentage) || $discount_percentage < 0 || $discount_percentage > 100) {
        return [
            'success' => false,
            'error' => 'Invalid discount percentage. Must be between 0 and 100'
        ];
    }
    
    // Get current price and discount info
    $check_sql = "SELECT price_jod, name_en, original_price, has_discount FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $check_stmt->close();
        return [
            'success' => false,
            'error' => 'Product not found'
        ];
    }
    
    $product = $result->fetch_assoc();
    $current_price = $product['price_jod'];
    $product_name = $product['name_en'];
    $has_discount = $product['has_discount'];
    
    // Determine the base price for discount calculation
    // If product already has discount, use original_price, otherwise use current price_jod
    $base_price = ($has_discount && $product['original_price']) ? $product['original_price'] : $current_price;
    $check_stmt->close();
    
    // Calculate new price with discount
    $discount_multiplier = (100 - $discount_percentage) / 100;
    $new_price = round($base_price * $discount_multiplier, 3);
    
    // Update price, original_price, discount info
    $update_sql = "UPDATE products 
                   SET price_jod = ?, 
                       original_price = ?, 
                       discount_percentage = ?, 
                       has_discount = 1 
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('dddi', $new_price, $base_price, $discount_percentage, $product_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        return [
            'success' => true,
            'message' => 'Discount applied successfully',
            'product_id' => $product_id,
            'product_name' => $product_name,
            'old_price' => $base_price,
            'new_price' => $new_price,
            'discount_percentage' => $discount_percentage,
            'old_price_formatted' => formatJOD($base_price),
            'new_price_formatted' => formatJOD($new_price)
        ];
    } else {
        $update_stmt->close();
        return [
            'success' => false,
            'error' => 'Failed to apply discount'
        ];
    }
}

/**
 * Remove discount from product (restore original price)
 * 
 * @param int $product_id Product ID
 * @return array Response with success status
 */
function removeProductDiscount($product_id) {
    global $conn;
    
    if (!is_numeric($product_id) || $product_id <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid product ID'
        ];
    }
    
    // Get product info
    $check_sql = "SELECT name_en, original_price, has_discount FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $product_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $check_stmt->close();
        return [
            'success' => false,
            'error' => 'Product not found'
        ];
    }
    
    $product = $result->fetch_assoc();
    $check_stmt->close();
    
    if (!$product['has_discount']) {
        return [
            'success' => false,
            'error' => 'Product has no active discount'
        ];
    }
    
    // Restore original price
    $original_price = $product['original_price'];
    $update_sql = "UPDATE products 
                   SET price_jod = original_price, 
                       discount_percentage = 0.00, 
                       has_discount = 0 
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('i', $product_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        return [
            'success' => true,
            'message' => 'Discount removed successfully',
            'product_id' => $product_id,
            'product_name' => $product['name_en'],
            'restored_price' => $original_price,
            'restored_price_formatted' => formatJOD($original_price)
        ];
    } else {
        $update_stmt->close();
        return [
            'success' => false,
            'error' => 'Failed to remove discount'
        ];
    }
}

/**
 * Add a product review
 * 
 * @param int $product_id Product ID
 * @param int $user_id User ID
 * @param int $rating Rating (1-5)
 * @param string $review_text Review text
 * @return array Response with success status
 */
function addProductReview($product_id, $user_id, $rating, $review_text) {
    global $conn;
    
    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }
    
    if (!is_numeric($user_id) || $user_id <= 0) {
        return ['success' => false, 'error' => 'Invalid user ID'];
    }
    
    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
    }
    
    $review_text = trim($review_text);
    if (empty($review_text)) {
        return ['success' => false, 'error' => 'Review text cannot be empty'];
    }
    
    // Check if product exists
    $check_product = "SELECT id FROM products WHERE id = ?";
    $stmt = $conn->prepare($check_product);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'error' => 'Product not found'];
    }
    $stmt->close();
    
    // Check if user already reviewed this product
    $check_review = "SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_review);
    $stmt->bind_param('ii', $product_id, $user_id);
    $stmt->execute();
    $existing_review = $stmt->get_result();
    
    if ($existing_review->num_rows > 0) {
        // Update existing review
        $review_id = $existing_review->fetch_assoc()['id'];
        $stmt->close();
        
        $update_sql = "UPDATE product_reviews SET rating = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('isi', $rating, $review_text, $review_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Review updated successfully'];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'Failed to update review'];
        }
    } else {
        // Add new review
        $stmt->close();
        
        $insert_sql = "INSERT INTO product_reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('iiis', $product_id, $user_id, $rating, $review_text);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Review added successfully'];
        } else {
            $stmt->close();
            return ['success' => false, 'error' => 'Failed to add review'];
        }
    }
}

/**
 * Get reviews for a product
 * 
 * @param int $product_id Product ID
 * @param int $limit Maximum number of reviews to return
 * @return array Response with reviews array
 */
function getProductReviews($product_id, $limit = 50) {
    global $conn;
    
    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }
    
    $sql = "SELECT r.id, r.product_id, r.user_id, r.rating, r.review_text, r.created_at, r.updated_at,
                   u.firstname, u.lastname
            FROM product_reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ?
            ORDER BY r.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    $total_rating = 0;
    
    while ($row = $result->fetch_assoc()) {
        $row['user_full_name'] = $row['firstname'] . ' ' . $row['lastname'];
        $total_rating += $row['rating'];
        $reviews[] = $row;
    }
    
    $stmt->close();
    
    $average_rating = count($reviews) > 0 ? round($total_rating / count($reviews), 1) : 0;
    
    return [
        'success' => true,
        'reviews' => $reviews,
        'count' => count($reviews),
        'average_rating' => $average_rating
    ];
}

/**
 * Check if user has reviewed a product
 * 
 * @param int $product_id Product ID
 * @param int $user_id User ID
 * @return array Response with review data if exists
 */
function getUserProductReview($product_id, $user_id) {
    global $conn;
    
    if (!is_numeric($product_id) || $product_id <= 0) {
        return ['success' => false, 'error' => 'Invalid product ID'];
    }
    
    if (!is_numeric($user_id) || $user_id <= 0) {
        return ['success' => false, 'error' => 'Invalid user ID'];
    }
    
    $sql = "SELECT id, rating, review_text, created_at, updated_at
            FROM product_reviews
            WHERE product_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $review = $result->fetch_assoc();
        $stmt->close();
        return [
            'success' => true,
            'has_review' => true,
            'review' => $review
        ];
    } else {
        $stmt->close();
        return [
            'success' => true,
            'has_review' => false
        ];
    }
}

/**
 * Get all tags for a product
 */
function getProductTags($product_id) {
    global $conn;
    $sql = "SELECT t.id, t.name_en, t.name_ar, t.slug 
            FROM tags t 
            JOIN product_tags pt ON t.id = pt.tag_id 
            WHERE pt.product_id = ?
            ORDER BY t.name_en";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    $stmt->close();
    return $tags;
}

/**
 * Get products by tag slug
 */
function getProductsByTag($tag_slug, $limit = 50) {
    global $conn;
    $sql = "SELECT p.id, p.name_en, p.name_ar, p.slug, p.short_description_en, p.short_description_ar,
                   p.price_jod, p.stock_quantity, p.image_link,
                   p.original_price, p.discount_percentage, p.has_discount,
                   b.name_en AS brand_en, b.name_ar AS brand_ar
            FROM products p
            JOIN product_tags pt ON p.id = pt.product_id
            JOIN tags t ON pt.tag_id = t.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE t.slug = ?
            ORDER BY p.id DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param('si', $tag_slug, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['price_formatted'] = formatJOD($row['price_jod']);
        $products[] = $row;
    }
    $stmt->close();
    return $products;
}
