<?php
// More aggressive mapping of new product folders to database products

require_once '/var/www/html/poshy_store/includes/db_connect.php';

// Get all products
$result = $conn->query("SELECT id, name_en, name_ar FROM products ORDER BY id");
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// New product folders (keep trailing spaces as they appear in filesystem)
$new_folders = [
    "12 15-18 28 The Ordinary. Hyaluronic Acid 2% + B5 ",
    "14 19-24 19 SOMEBYMI GALACTOMYCES BRIGHTENING TRIAL KIT ",
    "16 21-25 41 Celimax RETINAL SHOT TIGHTENING BOOSTER ",
    "18 24-28 10 ANUA HEARTLEAF 70 DAILY LOTION ",
    "18 24-28 12 ANUA HEARTLEAF QUERCETINOL ™ PORE DEEP CLEANSING FOAM ",
    "18 25-29 24 The Ordinary. The Mini Icons Set TRAVEL SIZE Glycolic Acid 7% Exfoliating Toner  Niacinamide 10% + Zinc 1% Hyaluronic Acid 2% + B5 ",
    "19 25-30 22 medicube COLLAGEN NIGHT WRAPPING MASK ",
    "19 25-30 3 EQQUAL BERRY LUSH BLUSH NAD+ PEPTIDE Boosting Serum B",
    "21 30-35 25 The Ordinary. Niacinamide 10% + Zinc 1% ",
    "26 39-45 14 DR.ALTHEA TO BE YOUTHFUL EYE SERUM ",
    "33 42-49 38 PanOxyl™ Acne Creamy Wash for Face & Body 4% BENZOYL PEROXIDE ",
];

$images_dir = '/var/www/html/poshy_store/images';

echo "Attempting comprehensive mapping of new product folders...\n";
echo "====================================\n";

$mapped = 0;
$updated = 0;

foreach ($new_folders as $folder_name) {
    $folder_path = "$images_dir/$folder_name";
    
    // Check if folder has 1.png
    if (!file_exists("$folder_path/1.png")) {
        echo "✗ Skipped (no 1.png): $folder_name\n";
        continue;
    }
    
    // Try to match to product
    $product_id = null;
    $folder_lower = strtolower(trim($folder_name));
    
    // First try direct name matching with fuzzy matching
    foreach ($products as $product) {
        $prod_name_lower = strtolower($product['name_en']);
        
        // Calculate similarity
        similar_text($folder_lower, $prod_name_lower, $percent);
        
        if ($percent > 70) {
            $product_id = $product['id'];
            break;
        }
        
        // Also try substring matching
        if (strpos($prod_name_lower, trim($folder_lower)) !== false ||
            strpos($folder_lower, $prod_name_lower) !== false) {
            $product_id = $product['id'];
            break;
        }
    }
    
    if ($product_id) {
        // Update database
        $image_path = "images/$folder_name/1.png";
        $stmt = $conn->prepare("UPDATE products SET image_link = ? WHERE id = ?");
        $stmt->bind_param("si", $image_path, $product_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "✓ Product $product_id: '$folder_name'\n";
            $mapped++;
            $updated++;
        } else if ($stmt->affected_rows == 0) {
            echo "~ Product $product_id: Already mapped (no update)\n";
            $mapped++;
        }
        $stmt->close();
    } else {
        echo "✗ No match: $folder_name\n";
    }
}

echo "====================================\n";
echo "✅ Mapping complete!\n";
echo "Successfully mapped: $mapped folders\n";
echo "Database updated: $updated products\n";
