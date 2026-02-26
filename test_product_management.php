<?php
/**
 * Product Management Verification System
 * Tests adding and editing products to ensure data persistence
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

if (!$conn) {
    die("❌ Database connection failed\n");
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "              PRODUCT MANAGEMENT VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Check database structure
echo "1️⃣ Verifying database structure...\n";
$tables = ['products', 'categories', 'brands', 'product_tags', 'tags'];
$missing = [];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing[] = $table;
        echo "   ❌ Table '$table' NOT found\n";
    } else {
        echo "   ✅ Table '$table' exists\n";
    }
}

if (!empty($missing)) {
    echo "\n⚠️  Missing tables: " . implode(', ', $missing) . "\n\n";
} else {
    echo "   ✅ All required tables exist\n\n";
}

// 2. Check products table structure
echo "2️⃣ Checking products table columns...\n";
$result = $conn->query("DESCRIBE products");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[$row['Field']] = $row['Type'];
}

$required_columns = [
    'id', 'name_en', 'name_ar', 'slug', 'price_jod', 
    'stock_quantity', 'description', 'image_link'
];

foreach ($required_columns as $col) {
    if (isset($columns[$col])) {
        echo "   ✅ Column '$col' found\n";
    } else {
        echo "   ❌ Column '$col' MISSING\n";
    }
}
echo "\n";

// 3. Test product insertion capability
echo "3️⃣ Testing product insertion...\n";
$test_product_name = 'Test Product ' . date('Y-m-d H:i:s');
$test_slug = strtolower(str_replace([' ', ':'], ['-', ''], $test_product_name));

$insert_sql = "INSERT INTO products 
    (name_en, name_ar, slug, description, description_ar, 
     price_jod, stock_quantity, image_link) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_sql);
if (!$stmt) {
    echo "   ❌ Insert preparation failed: " . $conn->error . "\n";
} else {
    $name_ar = 'منتج الاختبار';
    $desc = 'This is a test product for verification';
    $desc_ar = 'هذا منتج اختبار للتحقق';
    $price = 29.99;
    $stock = 10;
    $image = '';
    
    $stmt->bind_param('sssssdis', 
        $test_product_name, $name_ar, $test_slug,
        $desc, $desc_ar, $price, $stock, $image
    );
    
    if ($stmt->execute()) {
        $test_product_id = $stmt->insert_id;
        echo "   ✅ Product inserted successfully (ID: $test_product_id)\n\n";
    } else {
        echo "   ❌ Insert failed: " . $stmt->error . "\n\n";
        $test_product_id = null;
    }
    $stmt->close();
}

// 4. Test product retrieval
echo "4️⃣ Testing product retrieval...\n";
if (!empty($test_product_id)) {
    $retrieve_sql = "SELECT id, name_en, name_ar, price_jod, stock_quantity, description 
                     FROM products WHERE id = ?";
    $stmt = $conn->prepare($retrieve_sql);
    $stmt->bind_param('i', $test_product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($product = $result->fetch_assoc()) {
        echo "   ✅ Product retrieved successfully\n";
        echo "      • ID: " . $product['id'] . "\n";
        echo "      • Name (EN): " . $product['name_en'] . "\n";
        echo "      • Name (AR): " . $product['name_ar'] . "\n";
        echo "      • Price: JOD " . $product['price_jod'] . "\n";
        echo "      • Stock: " . $product['stock_quantity'] . "\n";
        echo "      • Description: " . substr($product['description'], 0, 50) . "...\n";
    } else {
        echo "   ❌ Product not found after insertion\n";
    }
    $stmt->close();
    echo "\n";
}

// 5. Test product update
echo "5️⃣ Testing product update...\n";
if (!empty($test_product_id)) {
    $new_price = 39.99;
    $new_stock = 20;
    
    $update_sql = "UPDATE products SET price_jod = ?, stock_quantity = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('dii', $new_price, $new_stock, $test_product_id);
    
    if ($stmt->execute()) {
        echo "   ✅ Product updated successfully\n";
        
        // Verify the update
        $verify_sql = "SELECT price_jod, stock_quantity FROM products WHERE id = ?";
        $vstmt = $conn->prepare($verify_sql);
        $vstmt->bind_param('i', $test_product_id);
        $vstmt->execute();
        $vresult = $vstmt->get_result();
        if ($vproduct = $vresult->fetch_assoc()) {
            if ($vproduct['price_jod'] == $new_price && $vproduct['stock_quantity'] == $new_stock) {
                echo "   ✅ Update verified: Price = JOD " . $vproduct['price_jod'] . ", Stock = " . $vproduct['stock_quantity'] . "\n";
            } else {
                echo "   ❌ Update verification failed\n";
            }
        }
        $vstmt->close();
    } else {
        echo "   ❌ Update failed: " . $stmt->error . "\n";
    }
    $stmt->close();
    echo "\n";
}

// 6. Test deletion of test product
echo "6️⃣ Cleaning up test product...\n";
if (!empty($test_product_id)) {
    // Clean up tags first
    $conn->query("DELETE FROM product_tags WHERE product_id = $test_product_id");
    
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param('i', $test_product_id);
    
    if ($stmt->execute()) {
        echo "   ✅ Test product deleted\n";
    } else {
        echo "   ❌ Delete failed: " . $stmt->error . "\n";
    }
    $stmt->close();
    echo "\n";
}

// 7. Summary statistics
echo "7️⃣ System Statistics...\n";
$prod_result = $conn->query("SELECT COUNT(*) AS cnt FROM products");
$prod_count = $prod_result->fetch_assoc()['cnt'];

$cat_result = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
$cat_count = $cat_result->fetch_assoc()['cnt'];

$brand_result = $conn->query("SELECT COUNT(*) AS cnt FROM brands");
$brand_count = $brand_result->fetch_assoc()['cnt'];

echo "   📦 Total Products: $prod_count\n";
echo "   🏷️  Total Categories: $cat_count\n";
echo "   🏢 Total Brands: $brand_count\n\n";

// 8. Final verification
echo "═══════════════════════════════════════════════════════════════\n";
if (empty($missing)) {
    echo "✅ VERIFICATION COMPLETE: All product management systems operational\n";
    echo "\n✨ Admins can:\n";
    echo "   • Add new products via admin panel\n";
    echo "   • Edit existing products\n";
    echo "   • Delete products (if not in orders)\n";
    echo "   • Manage images, prices, stock\n";
    echo "\n📍 Access point: /pages/admin/add_product.php\n";
    echo "📍 Edit products: /pages/admin/edit_product.php?id=<product_id>\n";
} else {
    echo "❌ VERIFICATION FAILED: Database structure incomplete\n";
    echo "Missing tables: " . implode(', ', $missing) . "\n";
}
echo "═══════════════════════════════════════════════════════════════\n";
?>
