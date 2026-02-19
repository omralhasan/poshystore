<?php
/**
 * Product Import Script - Poshy Lifestyle
 * 
 * Imports products from Item.pdf parsing data
 * Run: php import_products.php
 */

require_once __DIR__ . '/includes/db_connect.php';

// Product data from Item.pdf
$products_data = [
    ["no" => 1, "name" => "EQQUAL BERRY BAKUCHIOL Plumping Serum", "cost" => 19, "price_range" => "25-30"],
    ["no" => 2, "name" => "EQQUAL BERRY GLOW FILTER VITAMIN illuminating Serum", "cost" => 19, "price_range" => "25-30"],
    ["no" => 3, "name" => "EQQUAL BERRY LUSH BLUSH NAD+ PEPTIDE Boosting Serum", "cost" => 19, "price_range" => "25-30"],
    ["no" => 4, "name" => "Beauty of Joseon Relief Sun: Rice + Probiotics SPF50+ PA++++", "cost" => 15, "price_range" => "20-22"],
    ["no" => 5, "name" => "Beauty of Joseon Relief Sun Aqua-fresh Rice + B5 SPF50+ PA++", "cost" => 15, "price_range" => "20-22"],
    ["no" => 6, "name" => "Axis-y Dark Spot Correcting Glow Serum", "cost" => 14, "price_range" => "19-22"],
    ["no" => 7, "name" => "Axis-y Vegan Collagen Eye serum", "cost" => 14, "price_range" => "18-21"],
    ["no" => 8, "name" => "Axis-y package", "cost" => 23, "price_range" => "35-39"],
    ["no" => 9, "name" => "Anua AZELAIC ACID 10+ HYALURON", "cost" => 18, "price_range" => "24-28"],
    ["no" => 10, "name" => "ANUA HEARTLEAF 70 DAILY LOTION", "cost" => 20, "price_range" => "25-30"],
    ["no" => 11, "name" => "ANUA HEARTLEAF PORE CONTROL CLEANSING OIL MILD", "cost" => 18, "price_range" => "24-28"],
    ["no" => 12, "name" => "ANUA HEARTLEAF QUERCETINOL PORE DEEP CLEANSING FOAM", "cost" => 14, "price_range" => "20-24"],
    ["no" => 13, "name" => "MADAGASCAR CENTELLA DOUBLE CLEANSING DUO", "cost" => 26, "price_range" => "39-45"],
    ["no" => 14, "name" => "DR.ALTHEA TO BE YOUTHFUL EYE SERUM", "cost" => 13, "price_range" => "18-21"],
    ["no" => 15, "name" => "DR. ALTHEA 147 BARRIER CREAM FOR NORMAL TO DRY SKIN TYPES", "cost" => 19, "price_range" => "25-30"],
    ["no" => 16, "name" => "DR. ALTHEA 345 relief CREAM FOR ALL SKIN TYPES", "cost" => 19, "price_range" => "25-30"],
    ["no" => 17, "name" => "DR. ALTHEA 345 relief CREAM FOR ALL SKIN TYPES duo pack", "cost" => 33, "price_range" => "45-50"],
    ["no" => 18, "name" => "SEOUL 1988 EYE CREAM: RETINAL LIPOSOME 4% + FERMENTED BEAN", "cost" => 14, "price_range" => "19-24"],
    ["no" => 19, "name" => "SOMEBYMI GALACTOMYCES BRIGHTENING TRIAL KIT", "cost" => 15, "price_range" => "22-25"],
    ["no" => 20, "name" => "SOMEBYMI RETINOL INTENSE ADVANCED TRIPLE ACTION EYE CREAM", "cost" => 14, "price_range" => "18-22"],
    ["no" => 21, "name" => "medicube RED ACNE BODY PEELING SHOT AHA+BHA+PHA+LHA 32%", "cost" => 19, "price_range" => "25-30"],
    ["no" => 22, "name" => "medicube COLLAGEN NIGHT WRAPPING MASK", "cost" => 18, "price_range" => "23-28"],
    ["no" => 23, "name" => "The Ordinary. The Lip & Lash Set", "cost" => 18, "price_range" => "25-29"],
    ["no" => 24, "name" => "The Ordinary. The Mini Icons Set", "cost" => 21, "price_range" => "30-35"],
    ["no" => 25, "name" => "The Ordinary. Niacinamide 10% + Zinc 1%", "cost" => 11, "price_range" => "15-18"],
    ["no" => 26, "name" => "The Ordinary. Caffeine Solution 5% + EGCG", "cost" => 12, "price_range" => "15-18"],
    ["no" => 27, "name" => "The Ordinary. Vitamin C Suspension 23% + HA Spheres 2%", "cost" => 12, "price_range" => "15-18"],
    ["no" => 28, "name" => "The Ordinary. Hyaluronic Acid 2% + B5", "cost" => 12, "price_range" => "15-18"],
    ["no" => 29, "name" => "The Ordinary. Multi-Peptide Serum for Hair Density", "cost" => 17, "price_range" => "22-25"],
    ["no" => 30, "name" => "The Ordinary. VITAMIN C Ascorbyl Glucoside Solution 12%", "cost" => 17, "price_range" => "22-25"],
    ["no" => 31, "name" => "The Ordinary. Alpha Arbutin 2% + HA", "cost" => 12, "price_range" => "16-19"],
    ["no" => 32, "name" => "The Ordinary. Retinol 0.5% in Squalane", "cost" => 12, "price_range" => "15-18"],
    ["no" => 33, "name" => "The Ordinary. Salicylic Acid 2%", "cost" => 11, "price_range" => "15-18"],
    ["no" => 34, "name" => "The Ordinary. Retinol 0.2%", "cost" => 12, "price_range" => "15-18"],
    ["no" => 35, "name" => "The Ordinary. AHA 30% + BHA 2% Peeling Solution", "cost" => 12, "price_range" => "15-18"],
    ["no" => 36, "name" => "The Ordinary. Glycolic Acid 7% Exfoliating Toner", "cost" => 16, "price_range" => "20-23"],
    ["no" => 37, "name" => "PAULA'S CHOICE SKIN PERFECTING 2% BHA Liquid Exfoliant SALICYLIC ACID", "cost" => 33, "price_range" => "42-49"],
    ["no" => 38, "name" => "PanOxyl Acne Creamy Wash for Face & Body 4% BENZOYL PEROXIDE", "cost" => 14, "price_range" => "18-20"],
    ["no" => 39, "name" => "Crest 3D WHITESTRIPS ENAMEL SAFE DENTAL WHITENING KIT PROFESSIONAL WHITE", "cost" => 35, "price_range" => "55-60"],
    ["no" => 40, "name" => "COSRX Advanced Snail 96 Mucin Power Essence", "cost" => 16, "price_range" => "21-25"],
    ["no" => 41, "name" => "Celimax RETINAL SHOT TIGHTENING BOOSTER", "cost" => 12, "price_range" => "18-23"],
    ["no" => 42, "name" => "Celimax pore dark spot brightening care sunscreen spf50+", "cost" => 14, "price_range" => "18-23"],
];

echo "Starting product import...\n";
echo "Total products to import: " . count($products_data) . "\n\n";

$success_count = 0;
$error_count = 0;

foreach ($products_data as $product) {
    // Parse price range
    $price_parts = explode('-', $product['price_range']);
    $price_min = floatval($price_parts[0]);
    $price_max = isset($price_parts[1]) ? floatval($price_parts[1]) : $price_min;
    
    // Calculate default display price (minimum public price)
    $display_price = $price_min;
    
    // Category ID (default to 1 for cosmetics)
    $category_id = 1;
    
    // Default stock
    $stock = 50;
    
    // Insert product
    $sql = "INSERT INTO products 
            (name_en, name_ar, description, price_jod, stock_quantity, supplier_cost, public_price_min, public_price_max, category_id, image_link) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $name_ar = $product['name']; // Use same name for Arabic (can be translated later)
        $description = "High-quality skincare and cosmetic product";
        $image_link = "images/placeholder-cosmetics.svg";
        
        $stmt->bind_param(
            'sssdidddis',
            $product['name'],
            $name_ar,
            $description,
            $display_price,
            $stock,
            $product['cost'],
            $price_min,
            $price_max,
            $category_id,
            $image_link
        );
        
        if ($stmt->execute()) {
            $success_count++;
            echo "✓ Imported: {$product['name']} (Cost: {$product['cost']} JOD, Price: {$price_min}-{$price_max} JOD)\n";
        } else {
            $error_count++;
            echo "✗ Error importing {$product['name']}: " . $stmt->error . "\n";
        }
        
        $stmt->close();
    } else {
        $error_count++;
        echo "✗ Error preparing statement for {$product['name']}: " . $conn->error . "\n";
    }
}

echo "\n======================\n";
echo "Import completed!\n";
echo "Successful: $success_count\n";
echo "Errors: $error_count\n";
echo "======================\n";

$conn->close();
