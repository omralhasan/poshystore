<?php
/**
 * Run hero banner migration
 * Adds banner_type, subtitle, cta fields to homepage_banners table
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

echo "Running hero banner migration...\n";

// Add banner_type column
$columns_to_add = [
    "banner_type" => "ALTER TABLE homepage_banners ADD COLUMN banner_type ENUM('hero', 'section') NOT NULL DEFAULT 'section' AFTER title_ar",
    "subtitle" => "ALTER TABLE homepage_banners ADD COLUMN subtitle VARCHAR(500) DEFAULT NULL AFTER banner_type",
    "subtitle_ar" => "ALTER TABLE homepage_banners ADD COLUMN subtitle_ar VARCHAR(500) DEFAULT NULL AFTER subtitle",
    "cta_text" => "ALTER TABLE homepage_banners ADD COLUMN cta_text VARCHAR(100) DEFAULT NULL AFTER subtitle_ar", 
    "cta_text_ar" => "ALTER TABLE homepage_banners ADD COLUMN cta_text_ar VARCHAR(100) DEFAULT NULL AFTER cta_text",
    "sort_order" => "ALTER TABLE homepage_banners ADD COLUMN sort_order INT DEFAULT 0 AFTER cta_text_ar",
];

foreach ($columns_to_add as $col => $sql) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM homepage_banners LIKE '$col'");
    if ($check && $check->num_rows > 0) {
        echo "✅ Column '$col' already exists.\n";
    } else {
        if ($conn->query($sql)) {
            echo "✅ Added column '$col'.\n";
        } else {
            echo "❌ Error adding '$col': " . $conn->error . "\n";
        }
    }
}

// Add index (ignore if exists)
$conn->query("ALTER TABLE homepage_banners ADD INDEX idx_banner_type_active (banner_type, is_active, sort_order)");
echo "✅ Index added (or already exists).\n";

echo "\nDone! Hero banner migration complete.\n";
