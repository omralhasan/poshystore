<?php
/**
 * Remove categories from products table
 * This script will:
 * 1. Drop the category_id column from products table
 * 2. Optionally drop the categories table (set $drop_table = true)
 */

// Database configuration
$host = 'localhost';
$db_user = 'poshy_user';
$db_pass = 'Poshy2026';
$db_name = 'poshy_lifestyle';

try {
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "🔄 Starting category removal...\n\n";
    
    // Step 0: Drop foreign key constraint if it exists
    echo "0️⃣  Checking for foreign key constraints...\n";
    $constraints_result = $conn->query(
        "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
         WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'category_id' AND REFERENCED_TABLE_NAME = 'categories'"
    );
    
    if ($constraints_result && $constraints_result->num_rows > 0) {
        while ($constraint = $constraints_result->fetch_assoc()) {
            $constraint_name = $constraint['CONSTRAINT_NAME'];
            echo "   Found constraint: $constraint_name\n";
            $sql_drop_fk = "ALTER TABLE products DROP FOREIGN KEY " . $constraint_name;
            if ($conn->query($sql_drop_fk)) {
                echo "   ✅ Dropped foreign key\n";
            } else {
                echo "   ❌ Error dropping FK: " . $conn->error . "\n";
            }
        }
    } else {
        echo "   ℹ️  No foreign key constraints found\n";
    }
    echo "\n";
    
    // Step 1: Check if category_id column exists before dropping
    echo "1️⃣  Checking for category_id column...\n";
    $result = $conn->query("DESCRIBE products");
    $has_category = false;
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'category_id') {
            $has_category = true;
            break;
        }
    }
    
    if ($has_category) {
        echo "   Found category_id column, dropping...\n";
        $sql1 = "ALTER TABLE products DROP COLUMN category_id";
        if ($conn->query($sql1)) {
            echo "   ✅ Successfully dropped category_id column\n\n";
        } else {
            echo "   ❌ Error: " . $conn->error . "\n";
        }
    } else {
        echo "   ℹ️  category_id column not found (already removed)\n\n";
    }
    
    // Step 2: Check if categories table exists before dropping
    echo "2️⃣  Checking for categories table...\n";
    $tables_result = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($tables_result->num_rows > 0) {
        echo "   Found categories table, dropping...\n";
        $sql2 = "DROP TABLE categories";
        if ($conn->query($sql2)) {
            echo "   ✅ Successfully dropped categories table\n\n";
        } else {
            echo "   ❌ Error: " . $conn->error . "\n";
        }
    } else {
        echo "   ℹ️  categories table not found (already removed)\n\n";
    }
    
    // Step 3: Verify changes
    echo "3️⃣  Verifying changes...\n";
    $result = $conn->query("DESCRIBE products");
    
    $has_category = false;
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'category_id') {
            $has_category = true;
            break;
        }
    }
    
    if (!$has_category) {
        echo "   ✅ Confirmed: category_id column removed from products\n";
    } else {
        echo "   ⚠️  category_id column still exists\n";
    }
    
    echo "\n✅ Category removal complete!\n";
    echo "\n⚠️  IMPORTANT: You may need to update the following files to remove category references:\n";
    echo "   - index.php (category filtering logic)\n";
    echo "   - review_products.php (category display in admin)\n";
    echo "   - import_new_products.php (category insertion)\n";
    echo "   - add_skincare_categories.php (category management)\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
