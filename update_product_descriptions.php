<?php
/**
 * Update Product Descriptions - Poshy Lifestyle
 * 
 * Adds detailed descriptions for all products
 * Run: php update_product_descriptions.php
 */

require_once __DIR__ . '/includes/db_connect.php';

// Product descriptions based on actual product information
$product_descriptions = [
    1 => "EQQUAL BERRY BAKUCHIOL Plumping Serum is a powerful anti-aging serum featuring bakuchiol, a natural retinol alternative. This gentle yet effective formula helps reduce fine lines, improve skin elasticity, and promote a plumper, more youthful complexion without irritation.",
    
    2 => "EQQUAL BERRY GLOW FILTER VITAMIN Illuminating Serum brightens your complexion with a potent blend of vitamins. This lightweight serum diminishes dark spots, evens skin tone, and delivers a radiant, luminous glow for healthier-looking skin.",
    
    3 => "EQQUAL BERRY LUSH BLUSH NAD+ PEPTIDE Boosting Serum combines NAD+ and peptides to boost cellular energy and collagen production. Perfect for addressing signs of aging, this innovative serum firms, smooths, and revitalizes tired skin.",
    
    4 => "Beauty of Joseon Relief Sun: Rice + Probiotics SPF50+ PA++++ is a lightweight, non-greasy sunscreen enriched with rice extract and probiotics. Provides superior sun protection while soothing and hydrating sensitive skin. Leaves no white cast.",
    
    5 => "Beauty of Joseon Relief Sun Aqua-fresh Rice + B5 SPF50+ PA++ offers powerful UV protection in a refreshing, aqua-light formula. Infused with rice extract and vitamin B5, it hydrates and calms skin while defending against sun damage.",
    
    6 => "Axis-y Dark Spot Correcting Glow Serum targets hyperpigmentation and uneven skin tone with plant-based ingredients. This gentle yet effective serum brightens dark spots, evens complexion, and promotes a natural, healthy glow.",
    
    7 => "Axis-y Vegan Collagen Eye Serum is specifically formulated for the delicate eye area. Packed with vegan collagen and nourishing ingredients, it reduces puffiness, minimizes fine lines, and brightens dark circles for refreshed, youthful eyes.",
    
    8 => "Axis-y Package is a curated skincare set featuring Axis-y's best-selling products. Perfect for achieving comprehensive skincare results with a complete routine that addresses multiple skin concerns.",
    
    9 => "Anua AZELAIC ACID 10% + HYALURON is a powerful treatment serum combining azelaic acid and hyaluronic acid. Effectively reduces acne, fades blemishes, minimizes redness, and hydrates skin for a clearer, smoother complexion.",
    
    10 => "ANUA HEARTLEAF 70% DAILY LOTION is a soothing, lightweight moisturizer with 70% heartleaf extract. Perfect for sensitive and acne-prone skin, it calms irritation, controls sebum, and provides lasting hydration without feeling heavy.",
    
    11 => "ANUA HEARTLEAF PORE CONTROL CLEANSING OIL MILD gently dissolves makeup, sunscreen, and impurities while maintaining skin balance. Enriched with heartleaf extract, it cleanses thoroughly without stripping moisture or clogging pores.",
    
    12 => "ANUA HEARTLEAF QUERCETINOL PORE DEEP CLEANSING FOAM creates a rich lather to deeply cleanse pores and remove impurities. With heartleaf extract and quercetin, it purifies skin while maintaining its natural moisture barrier.",
    
    13 => "MADAGASCAR CENTELLA DOUBLE CLEANSING DUO combines oil and foam cleansers for the perfect two-step cleansing routine. Infused with Madagascar centella, it removes all traces of makeup and impurities while soothing and protecting skin.",
    
    14 => "DR.ALTHEA TO BE YOUTHFUL EYE SERUM is an intensive anti-aging treatment for the eye area. Formulated with powerful peptides and botanical extracts, it targets wrinkles, firms skin, and reduces signs of fatigue for brighter, younger-looking eyes.",
    
    15 => "DR. ALTHEA 147 BARRIER CREAM FOR NORMAL TO DRY SKIN TYPES strengthens skin's protective barrier with 7 types of hyaluronic acid and ceramides. Provides deep hydration and long-lasting moisture for comfortable, healthy skin.",
    
    16 => "DR. ALTHEA 345 relief CREAM FOR ALL SKIN TYPES is a soothing moisturizer that calms irritated skin and strengthens the skin barrier. Suitable for all skin types, it provides balanced hydration and protection against environmental stressors.",
    
    17 => "DR. ALTHEA 345 relief CREAM duo pack includes two jars of the bestselling 345 Relief Cream. Perfect value set for continuous use, providing long-term skin barrier support and daily moisture for healthy, resilient skin.",
    
    18 => "SEOUL 1988 EYE CREAM: RETINAL LIPOSOME 4% + FERMENTED BEAN features an innovative retinal formula that targets fine lines and wrinkles around the eyes. Enhanced with fermented bean extract, it firms, brightens, and rejuvenates delicate eye area skin.",
    
    19 => "SOMEBYMI GALACTOMYCES BRIGHTENING TRIAL KIT is a complete skincare set featuring galactomyces ferment filtrate. This brightening lineup evens skin tone, improves texture, and delivers a luminous glow. Perfect starter set to try the full routine.",
    
    20 => "SOMEBYMI RETINOL INTENSE ADVANCED TRIPLE ACTION EYE CREAM targets multiple eye area concerns with retinol, peptides, and nourishing ingredients. Reduces wrinkles, firms skin, and brightens dark circles for visibly younger-looking eyes.",
    
    21 => "medicube RED ACNE BODY PEELING SHOT AHA+BHA+PHA+LHA 32% is an intensive exfoliating treatment for body acne. This powerful formula with four types of acids clears bacne, smooths rough skin, and prevents breakouts for clearer body skin.",
    
    22 => "medicube COLLAGEN NIGHT WRAPPING MASK is an overnight treatment that works while you sleep. Packed with collagen and nourishing ingredients, it deeply hydrates, plumps skin, and reduces fine lines for a refreshed, youthful morning glow.",
    
    23 => "The Ordinary The Lip & Lash Set includes specialized treatments for lips and lashes. Nourishes and strengthens both areas with targeted formulas that promote healthier, fuller-looking lashes and smoother, more hydrated lips.",
    
    24 => "The Ordinary The Mini Icons Set is a travel-sized collection of bestsellers including Glycolic Acid Toner, Niacinamide serum, and Hyaluronic Acid. Perfect introduction to The Ordinary's effective, science-backed skincare essentials.",
    
    25 => "The Ordinary Niacinamide 10% + Zinc 1% is a high-strength formula that reduces the appearance of blemishes and congestion. Controls sebum production, minimizes pores, and improves overall skin texture for clearer, more balanced skin.",
    
    26 => "The Ordinary Caffeine Solution 5% + EGCG targets puffiness and dark circles around the eyes. This lightweight serum reduces visible signs of fatigue with caffeine and green tea extract for refreshed, awakened eyes.",
    
    27 => "The Ordinary Vitamin C Suspension 23% + HA Spheres 2% is a potent antioxidant formula that brightens skin and reduces signs of aging. High concentration of pure vitamin C fights free radicals and promotes even, radiant skin tone.",
    
    28 => "The Ordinary Hyaluronic Acid 2% + B5 provides multi-depth hydration with three types of hyaluronic acid. Enhanced with vitamin B5, it plumps skin, reduces fine lines, and maintains optimal moisture levels throughout the day.",
    
    29 => "The Ordinary Multi-Peptide Serum for Hair Density supports thicker, fuller, healthier-looking hair. This concentrated formula with peptides and natural extracts strengthens hair, improves scalp health, and promotes hair density.",
    
    30 => "The Ordinary VITAMIN C Ascorbyl Glucoside Solution 12% is a stable, water-soluble vitamin C derivative that brightens skin tone. Gentle enough for sensitive skin, it fights oxidative stress and promotes even, radiant complexion.",
    
    31 => "The Ordinary Alpha Arbutin 2% + HA targets hyperpigmentation and dark spots with a concentrated dose of alpha arbutin. Fades discoloration, evens skin tone, and promotes brighter, more uniform complexion with hyaluronic acid hydration.",
    
    32 => "The Ordinary Retinol 0.5% in Squalane is a moderate-strength retinol treatment for experienced users. Reduces signs of aging, improves skin texture, and promotes cellular turnover while squalane provides nourishing moisture.",
    
    33 => "The Ordinary Salicylic Acid 2% is a beta hydroxy acid (BHA) that exfoliates inside pores to reduce acne and blackheads. Ideal for oily and blemish-prone skin, it clears congestion and prevents future breakouts.",
    
    34 => "The Ordinary Retinol 0.2% in Squalane is a gentle introduction to retinol for beginners. Helps reduce fine lines, even skin tone, and improve texture while squalane keeps skin soft and hydrated without irritation.",
    
    35 => "The Ordinary AHA 30% + BHA 2% Peeling Solution is a powerful 10-minute exfoliating facial. Combines glycolic, lactic, tartaric, citric acids with salicylic acid to resurface skin, unclog pores, and reveal smoother, brighter complexion.",
    
    36 => "The Ordinary Glycolic Acid 7% Exfoliating Toner is an alpha hydroxy acid solution that gently exfoliates dead skin cells. Improves skin radiance, texture, and tone while preparing skin for better absorption of other treatments.",
    
    37 => "PAULA'S CHOICE SKIN PERFECTING 2% BHA Liquid Exfoliant SALICYLIC ACID is a cult-favorite leave-on exfoliant. Unclogs pores, reduces blackheads, smooths wrinkles, and evens skin tone for clearer, more radiant skin.",
    
    38 => "PanOxyl Acne Creamy Wash for Face & Body 4% BENZOYL PEROXIDE is a dermatologist-recommended acne treatment. Maximum strength formula kills acne-causing bacteria, clears breakouts, and prevents new blemishes on face and body.",
    
    39 => "Crest 3D WHITESTRIPS ENAMEL SAFE DENTAL WHITENING KIT PROFESSIONAL WHITE delivers professional-level teeth whitening at home. Enamel-safe strips remove years of stains for a noticeably whiter, brighter smile in just days.",
    
    40 => "COSRX Advanced Snail 96 Mucin Power Essence is a bestselling Korean beauty essential with 96.3% snail secretion filtrate. Repairs damaged skin, provides deep hydration, and improves elasticity for healthier, more resilient skin.",
    
    41 => "Celimax RETINAL SHOT TIGHTENING BOOSTER is a powerful anti-aging serum with stabilized retinal. Firms sagging skin, reduces wrinkles, improves elasticity, and promotes collagen production for visibly tighter, younger-looking skin.",
    
    42 => "Celimax pore dark spot brightening care sunscreen spf50+ offers broad-spectrum sun protection while treating skin concerns. Brightens dark spots, minimizes pores, and protects against UV damage in one multi-functional formula."
];

echo "Starting product description updates...\n";
echo "Total products to update: " . count($product_descriptions) . "\n\n";

$success_count = 0;
$error_count = 0;

foreach ($product_descriptions as $product_id => $description) {
    $sql = "UPDATE products SET description = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param('si', $description, $product_id);
        
        if ($stmt->execute()) {
            $success_count++;
            
            // Get product name for display
            $name_sql = "SELECT name_en FROM products WHERE id = ?";
            $name_stmt = $conn->prepare($name_sql);
            $name_stmt->bind_param('i', $product_id);
            $name_stmt->execute();
            $result = $name_stmt->get_result();
            $product = $result->fetch_assoc();
            $name_stmt->close();
            
            echo "✓ Updated: {$product['name_en']}\n";
        } else {
            $error_count++;
            echo "✗ Error updating product ID $product_id: " . $stmt->error . "\n";
        }
        
        $stmt->close();
    } else {
        $error_count++;
        echo "✗ Error preparing statement for product ID $product_id: " . $conn->error . "\n";
    }
}

echo "\n======================\n";
echo "Update completed!\n";
echo "Successful: $success_count\n";
echo "Errors: $error_count\n";
echo "======================\n";

$conn->close();
