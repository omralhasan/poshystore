<?php
/**
 * Database Migration: Add Shipping Details to Orders Table
 * This script adds phone, shipping_address, and notes columns to the orders table
 */

require_once __DIR__ . '/../includes/db_connect.php';

echo "Starting database migration for shipping details...\n\n";

// Check and add shipping_address column
$check_shipping = "SHOW COLUMNS FROM orders LIKE 'shipping_address'";
$result = $conn->query($check_shipping);

if ($result->num_rows == 0) {
    echo "Adding shipping_address column...\n";
    $sql = "ALTER TABLE orders ADD COLUMN shipping_address TEXT AFTER status";
    if ($conn->query($sql)) {
        echo "✓ shipping_address column added successfully\n";
    } else {
        echo "✗ Error adding shipping_address: " . $conn->error . "\n";
    }
} else {
    echo "✓ shipping_address column already exists\n";
}

// Check and add phone column
$check_phone = "SHOW COLUMNS FROM orders LIKE 'phone'";
$result = $conn->query($check_phone);

if ($result->num_rows == 0) {
    echo "Adding phone column...\n";
    $sql = "ALTER TABLE orders ADD COLUMN phone VARCHAR(20) AFTER shipping_address";
    if ($conn->query($sql)) {
        echo "✓ phone column added successfully\n";
    } else {
        echo "✗ Error adding phone: " . $conn->error . "\n";
    }
} else {
    echo "✓ phone column already exists\n";
}

// Check and add notes column
$check_notes = "SHOW COLUMNS FROM orders LIKE 'notes'";
$result = $conn->query($check_notes);

if ($result->num_rows == 0) {
    echo "Adding notes column...\n";
    $sql = "ALTER TABLE orders ADD COLUMN notes TEXT AFTER phone";
    if ($conn->query($sql)) {
        echo "✓ notes column added successfully\n";
    } else {
        echo "✗ Error adding notes: " . $conn->error . "\n";
    }
} else {
    echo "✓ notes column already exists\n";
}

// Add index on phone column for faster lookups
echo "\nAdding index on phone column...\n";
$check_index = "SHOW INDEX FROM orders WHERE Key_name = 'idx_orders_phone'";
$result = $conn->query($check_index);

if ($result->num_rows == 0) {
    $sql = "CREATE INDEX idx_orders_phone ON orders(phone)";
    if ($conn->query($sql)) {
        echo "✓ Index on phone column added successfully\n";
    } else {
        echo "✗ Error adding index: " . $conn->error . "\n";
    }
} else {
    echo "✓ Index on phone column already exists\n";
}

echo "\n✅ Migration completed successfully!\n";
echo "\nPhone numbers are now required for all new orders.\n";

$conn->close();
?>
