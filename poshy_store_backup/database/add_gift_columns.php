<?php
/**
 * Database Migration: Add gift columns to orders table
 * This script adds gift-related fields to store gift information
 */

require_once __DIR__ . '/../includes/db_connect.php';

echo "=== Adding Gift Columns to Orders Table ===\n\n";

try {
    // Check if is_gift column already exists
    $check_sql = "SHOW COLUMNS FROM orders LIKE 'is_gift'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        echo "✓ Gift columns already exist in orders table.\n";
    } else {
        echo "Adding gift columns to orders table...\n";
        
        // Add is_gift column
        $alter_sql1 = "ALTER TABLE orders ADD COLUMN is_gift TINYINT(1) DEFAULT 0 AFTER notes";
        if ($conn->query($alter_sql1)) {
            echo "✓ Successfully added is_gift column.\n";
        } else {
            throw new Exception("Failed to add is_gift column: " . $conn->error);
        }
        
        // Add gift_recipient_name column
        $alter_sql2 = "ALTER TABLE orders ADD COLUMN gift_recipient_name VARCHAR(255) DEFAULT NULL AFTER is_gift";
        if ($conn->query($alter_sql2)) {
            echo "✓ Successfully added gift_recipient_name column.\n";
        } else {
            throw new Exception("Failed to add gift_recipient_name column: " . $conn->error);
        }
        
        // Add gift_message column
        $alter_sql3 = "ALTER TABLE orders ADD COLUMN gift_message TEXT DEFAULT NULL AFTER gift_recipient_name";
        if ($conn->query($alter_sql3)) {
            echo "✓ Successfully added gift_message column.\n";
        } else {
            throw new Exception("Failed to add gift_message column: " . $conn->error);
        }
    }
    
    echo "\n=== Migration Completed Successfully! ===\n";
    echo "The orders table now includes gift functionality:\n";
    echo "  - is_gift: Whether the order is a gift (0 or 1)\n";
    echo "  - gift_recipient_name: Name of the gift recipient\n";
    echo "  - gift_message: Optional gift message\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
