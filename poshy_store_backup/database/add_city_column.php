<?php
/**
 * Database Migration: Add city column to orders table
 * This script adds the city field to store city information for delivery routing
 */

require_once __DIR__ . '/../includes/db_connect.php';

echo "=== Adding City Column to Orders Table ===\n\n";

try {
    // Check if city column already exists
    $check_sql = "SHOW COLUMNS FROM orders LIKE 'city'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        echo "✓ City column already exists in orders table.\n";
    } else {
        echo "Adding city column to orders table...\n";
        
        // Add city column after phone column
        $alter_sql = "ALTER TABLE orders ADD COLUMN city VARCHAR(50) NOT NULL AFTER phone";
        
        if ($conn->query($alter_sql)) {
            echo "✓ Successfully added city column to orders table.\n";
        } else {
            throw new Exception("Failed to add city column: " . $conn->error);
        }
    }
    
    // Add index on city for better query performance
    $index_check_sql = "SHOW INDEX FROM orders WHERE Key_name = 'idx_city'";
    $index_result = $conn->query($index_check_sql);
    
    if ($index_result->num_rows > 0) {
        echo "✓ Index on city column already exists.\n";
    } else {
        echo "Adding index on city column...\n";
        
        $index_sql = "ALTER TABLE orders ADD INDEX idx_city (city)";
        
        if ($conn->query($index_sql)) {
            echo "✓ Successfully added index on city column.\n";
        } else {
            echo "⚠ Warning: Could not add index on city column: " . $conn->error . "\n";
        }
    }
    
    echo "\n=== Migration Completed Successfully! ===\n";
    echo "The orders table now includes the city field for delivery routing.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
