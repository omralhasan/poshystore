<?php
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/product_manager.php';
require_once __DIR__ . '/includes/cart_handler.php';
require_once __DIR__ . '/includes/db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$cart_count = 0;
if ($is_logged_in) {
    $cart_info = getCartCount($_SESSION['user_id']);
    $cart_count = $cart_info['count'] ?? 0;
}

// Get search query from URL
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get selected category from URL
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : null;

// Get all categories with their products
$categories_sql = "SELECT c.id, c.name_en, c.name_ar, 
                   (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.stock_quantity > 0) as product_count
                   FROM categories c
                   HAVING product_count > 0
                   ORDER BY c.id";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat;
}

// Get products grouped by category or search results
$products_by_category = [];
$is_search_mode = !empty($search_query);

if ($is_search_mode) {
    // Search mode - show all matching products regardless of category
    $search_filters = ['search' => $search_query, 'in_stock' => true];
    if ($selected_category) {
        $search_filters['category_id'] = $selected_category;
    }
    $products_result = getAllProducts($search_filters, 100);
    
    if (!empty($products_result['products'])) {
        // Group search results by category for display
        foreach ($products_result['products'] as $product) {
            $cat_id = $product['category_id'];
            if (!isset($products_by_category[$cat_id])) {
                // Find category info
                $cat_info = array_filter($categories, fn($c) => $c['id'] == $cat_id);
                $cat_info = reset($cat_info);
                if ($cat_info) {
                    $products_by_category[$cat_id] = [
                        'category' => $cat_info,
                        'products' => []
                    ];
                }
            }
            if (isset($products_by_category[$cat_id])) {
                $products_by_category[$cat_id]['products'][] = $product;
            }
        }
    }
} elseif ($selected_category) {
    // Show only selected category
    $products_result = getAllProducts(['category_id' => $selected_category, 'in_stock' => true], 50);
    if (!empty($products_result['products'])) {
        // Find category info
        $cat_info = array_filter($categories, fn($c) => $c['id'] == $selected_category);
        $cat_info = reset($cat_info);
        if ($cat_info) {
            $products_by_category[$selected_category] = [
                'category' => $cat_info,
                'products' => $products_result['products']
            ];
        }
    }
} else {
    // Show all categories
    foreach ($categories as $category) {
        $products_result = getAllProducts(['category_id' => $category['id'], 'in_stock' => true], 50);
        if (!empty($products_result['products'])) {
            $products_by_category[$category['id']] = [
                'category' => $category,
                'products' => $products_result['products']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poshy Store - Premium Cosmetics</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Arabic Support -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --deep-purple: #2d132c;
            --royal-gold: #c9a86a;
            --creamy-white: #fcf8f2;
            --gold-light: #e4d4b4;
            --purple-dark: #1a0a18;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--creamy-white);
            color: var(--deep-purple);
            overflow-x: hidden;
        }
        
        /* Floating Ramadan Elements */
        .floating-decorations {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }
        
        .floating-icon {
            position: absolute;
            color: var(--royal-gold);
            opacity: 0.15;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon:nth-child(1) { top: 10%; left: 10%; font-size: 3rem; animation-delay: 0s; }
        .floating-icon:nth-child(2) { top: 20%; right: 15%; font-size: 2.5rem; animation-delay: 1s; }
        .floating-icon:nth-child(3) { top: 60%; left: 5%; font-size: 2rem; animation-delay: 2s; }
        .floating-icon:nth-child(4) { top: 70%; right: 10%; font-size: 3.5rem; animation-delay: 1.5s; }
        .floating-icon:nth-child(5) { top: 40%; right: 5%; font-size: 2.2rem; animation-delay: 0.5s; }
        .floating-icon:nth-child(6) { top: 85%; left: 20%; font-size: 2.8rem; animation-delay: 2.5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        /* Navbar */
        .navbar {
            transition: all 0.4s ease;
            padding: 1.2rem 0;
            background-color: transparent;
            position: fixed;
            width: 100%;
            z-index: 1000;
        }
        
        .navbar.scrolled {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            box-shadow: 0 4px 20px rgba(45, 19, 44, 0.3);
            padding: 0.8rem 0;
        }
        
        .navbar-brand {
            font-family: 'Dancing Script', cursive;
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none !important;
            line-height: 1.1;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
        }
        
        .navbar-brand .logo-accent {
            display: none;
        }
        
        .navbar-brand .logo-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.7rem;
            letter-spacing: 5px;
            display: block;
            margin-top: 0.3rem;
            font-weight: 300;
            background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        }
        
        .navbar-brand:hover {
            opacity: 0.85;
            transform: scale(1.02);
        }
        
        /* Icon Navigation Buttons */
        .nav-icon-btn {
            color: var(--royal-gold);
            font-size: 1.4rem;
            padding: 0.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            text-decoration: none;
            background: linear-gradient(135deg, rgba(201, 168, 106, 0.15), rgba(179, 147, 88, 0.1));
            border: 2px solid rgba(201, 168, 106, 0.3);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-icon-btn:hover {
            background: linear-gradient(135deg, var(--royal-gold), #b39358);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(201, 168, 106, 0.4);
        }
        
        .nav-divider-line {
            width: 2px;
            height: 35px;
            background: linear-gradient(to bottom, transparent, var(--royal-gold), transparent);
            opacity: 0.5;
            margin: 0 0.5rem;
        }
        
        .nav-link {
            color: var(--creamy-white) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s;
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--royal-gold) !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--royal-gold);
            transition: all 0.3s;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::after {
            width: 80%;
        }
        
        .nav-icon {
            color: var(--royal-gold);
            font-size: 1.3rem;
            margin-left: 1rem;
            transition: all 0.3s;
            position: relative;
        }
        
        .nav-icon:hover {
            color: var(--gold-light);
            transform: scale(1.1);
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--deep-purple) 0%, #4a1942 50%, var(--purple-dark) 100%);
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            padding-top: 160px;
        }
        
        .hero-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9a86a' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: patternMove 20s linear infinite;
        }
        
        @keyframes patternMove {
            0% { background-position: 0 0; }
            100% { background-position: 60px 60px; }
        }
        
        .hero-content {
            position: relative;
            z-index: 10;
            text-align: center;
            color: var(--creamy-white);
            padding: 3rem 0;
        }
        
        .hero-arabic {
            font-family: 'Tajawal', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--royal-gold);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
            animation: fadeInDown 1s ease;
        }
        
        .hero-english {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 3px 3px 10px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--gold-light);
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeIn 1s ease 0.4s both;
        }
        
        .hero-btn {
            background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
            color: var(--deep-purple);
            border: none;
            padding: 1rem 3rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.4s;
            box-shadow: 0 10px 30px rgba(201, 168, 106, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: fadeInUp 1s ease 0.6s both;
        }
        
        .hero-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(201, 168, 106, 0.6);
            background: linear-gradient(135deg, #b39358 0%, var(--royal-gold) 100%);
        }
        
        .hero-decoration {
            position: absolute;
            font-size: 15rem;
            color: var(--royal-gold);
            opacity: 0.05;
        }
        
        .hero-decoration.star-left {
            top: 20%;
            left: -5%;
            animation: spin 30s linear infinite;
        }
        
        .hero-decoration.star-right {
            bottom: 10%;
            right: -5%;
            animation: spin 25s linear infinite reverse;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Section Titles */
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--deep-purple);
            text-align: center;
            margin: 4rem 0 3rem;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
            margin: 1rem auto 0;
        }
        
        .section-subtitle {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Product Cards */
        .products-section {
            padding: 4rem 0;
            background: linear-gradient(180deg, var(--creamy-white) 0%, #faf6ef 100%);
        }
        
        .product-card {
            background: white;
            border-radius: 20px;
            overflow: visible;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 3px solid transparent;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(45, 19, 44, 0.12);
            position: relative;
        }
        
        /* Ramadan Decorative Corners */
        .product-card::before,
        .product-card::after {
            content: '✦';
            position: absolute;
            color: var(--royal-gold);
            font-size: 1.5rem;
            opacity: 0;
            transition: all 0.5s;
            z-index: 5;
        }
        
        .product-card::before {
            top: -10px;
            left: -10px;
            transform: rotate(-15deg);
        }
        
        .product-card::after {
            bottom: -10px;
            right: -10px;
            transform: rotate(15deg);
        }
        
        .product-card:hover::before,
        .product-card:hover::after {
            opacity: 1;
            animation: sparkle 1.5s infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { transform: rotate(-15deg) scale(1); }
            50% { transform: rotate(-15deg) scale(1.3); }
        }
        
        /* Islamic Pattern Overlay */
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23c9a86a' fill-opacity='0.05'%3E%3Cpath d='M20 20.5V18H0v-2h20v-2H0v-2h20v-2H0V8h20V6H0V4h20V2H0V0h22v20h2V0h2v20h2V0h2v20h2V0h2v20h2V0h2v20h2v2H20v-1.5zM0 20h2v20H0V20zm4 0h2v20H4V20zm4 0h2v20H8V20zm4 0h2v20h-2V20zm4 0h2v20h-2V20zm4 4h20v2H20v-2zm0 4h20v2H20v-2zm0 4h20v2H20v-2zm0 4h20v2H20v-2z'/%3E%3C/g%3E%3C/svg%3E");
            opacity: 0;
            transition: opacity 0.5s;
            border-radius: 20px;
            pointer-events: none;
        }
        
        .product-card:hover::before {
            opacity: 1;
        }
        
        .product-card:hover {
            transform: translateY(-15px) scale(1.02);
            border-color: var(--royal-gold);
            box-shadow: 
                0 20px 60px rgba(201, 168, 106, 0.4),
                0 0 0 8px rgba(201, 168, 106, 0.1),
                inset 0 0 0 2px rgba(201, 168, 106, 0.2);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: all 0.5s;
            filter: brightness(0.95);
            aspect-ratio: 1 / 1;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.15);
            filter: brightness(1.05) saturate(1.2);
        }
        
        .product-image-container {
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #f8f8f8 0%, #fefefe 100%);
            border-radius: 20px 20px 0 0;
            height: 250px;
            min-height: 250px;
            max-height: 250px;
        }
        
        /* Ramadan Lantern Decorations */
        .product-image-container::before,
        .product-image-container::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            background: var(--royal-gold);
            border-radius: 50%;
            opacity: 0.15;
            z-index: 2;
        }
        
        .product-image-container::before {
            top: 15px;
            left: 15px;
            box-shadow: 
                -5px -5px 0 -2px var(--royal-gold),
                5px -5px 0 -2px var(--royal-gold),
                0 -10px 0 -4px var(--royal-gold);
        }
        
        .product-image-container::after {
            bottom: 15px;
            right: 15px;
            animation: lanternGlow 2s ease-in-out infinite;
        }
        
        @keyframes lanternGlow {
            0%, 100% { 
                opacity: 0.15;
                transform: scale(1);
            }
            50% { 
                opacity: 0.3;
                transform: scale(1.1);
            }
        }
        
        /* Crescent Moon Decoration on Image */
        .ramadan-icon-overlay {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 2rem;
            color: var(--royal-gold);
            opacity: 0;
            transform: rotate(-15deg) scale(0.5);
            transition: all 0.5s;
            z-index: 3;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
        }
        
        .product-card:hover .ramadan-icon-overlay {
            opacity: 0.9;
            transform: rotate(0deg) scale(1);
            animation: floatIcon 3s ease-in-out infinite;
        }
        
        @keyframes floatIcon {
            0%, 100% { transform: translateY(0) rotate(0deg) scale(1); }
            50% { transform: translateY(-10px) rotate(5deg) scale(1.05); }
        }
        
        /* Star Decorations in Corners */
        .star-decoration {
            position: absolute;
            color: var(--royal-gold);
            opacity: 0;
            transition: all 0.4s;
            z-index: 4;
            font-size: 1rem;
            filter: drop-shadow(0 0 3px rgba(201, 168, 106, 0.8));
        }
        
        .star-decoration.star-tl {
            top: 10px;
            left: 50px;
            animation-delay: 0s;
        }
        
        .star-decoration.star-tr {
            top: 10px;
            right: 50px;
            animation-delay: 0.2s;
        }
        
        .star-decoration.star-bl {
            bottom: 10px;
            left: 50px;
            animation-delay: 0.4s;
        }
        
        .star-decoration.star-br {
            bottom: 10px;
            right: 50px;
            animation-delay: 0.6s;
        }
        
        .product-card:hover .star-decoration {
            opacity: 1;
            animation: twinkle 1.5s ease-in-out infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { 
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
            50% { 
                opacity: 0.5;
                transform: scale(1.3) rotate(180deg);
            }
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
            color: var(--deep-purple);
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 700;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(201, 168, 106, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .product-body {
            padding: 1.8rem;
            position: relative;
            background: linear-gradient(180deg, white 0%, #fafafa 100%);
        }
        
        /* Decorative Divider */
        .product-body::before {
            content: '✦ ✦ ✦';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
            background: white;
            padding: 0 1rem;
            color: var(--royal-gold);
            font-size: 0.8rem;
            letter-spacing: 0.5rem;
            opacity: 0;
            transition: opacity 0.5s;
        }
        
        .product-card:hover .product-body::before {
            opacity: 1;
        }
        
        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--deep-purple);
            margin-bottom: 0.5rem;
            min-height: 60px;
            max-height: 60px;
            transition: color 0.3s;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
        }
        
        .product-card:hover .product-title {
            color: var(--royal-gold);
        }
        
        .product-title-ar {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
            max-height: 44px;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .price-current {
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--royal-gold);
            text-shadow: 2px 2px 4px rgba(201, 168, 106, 0.2);
            transition: all 0.3s;
            position: relative;
        }
        
        .product-card:hover .price-current {
            transform: scale(1.1);
            text-shadow: 0 0 15px rgba(201, 168, 106, 0.6);
        }
        
        .price-current::before {
            content: '✦';
            position: absolute;
            left: -25px;
            color: var(--royal-gold);
            opacity: 0;
            transition: all 0.4s;
        }
        
        .product-card:hover .price-current::before {
            opacity: 1;
            left: -20px;
        }
        
        .price-original {
            text-decoration: line-through;
            color: #999;
            font-size: 1.2rem;
            margin-left: 0.5rem;
        }
        
        .discount-badge {
            background: #dc3545;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .btn-add-cart {
            flex: 1;
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            color: var(--royal-gold);
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.4s;
            box-shadow: 0 4px 15px rgba(45, 19, 44, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-add-cart::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(201, 168, 106, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-add-cart:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-add-cart:hover {
            background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
            color: var(--deep-purple);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(201, 168, 106, 0.5);
        }
        
        .btn-add-cart i,
        .btn-view i {
            position: relative;
            z-index: 1;
        }
        
        .btn-view {
            background: white;
            color: var(--deep-purple);
            border: 2px solid var(--royal-gold);
            padding: 0.9rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.4s;
            box-shadow: 0 4px 15px rgba(201, 168, 106, 0.2);
        }
        
        .btn-view:hover {
            background: var(--royal-gold);
            color: white;
            border-color: var(--royal-gold);
            transform: translateY(-3px) rotate(5deg);
            box-shadow: 0 8px 25px rgba(201, 168, 106, 0.4);
        }
        
        /* Categories and Search Bar */
        .categories-search-bar {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(201, 168, 106, 0.3);
            position: fixed;
            top: 85px;
            width: 100%;
            z-index: 999;
            box-shadow: 0 4px 20px rgba(45, 19, 44, 0.3);
            padding: 0.75rem 0;
            transition: top 0.4s ease;
        }
        
        .navbar.scrolled + .categories-search-bar {
            top: 65px;
        }
        
        .categories-search-wrapper {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .categories-container {
            flex: 1;
            overflow: hidden;
        }
        
        .categories-scroll {
            display: flex;
            overflow-x: auto;
            gap: 0.5rem;
            padding: 0.25rem 0;
            scrollbar-width: thin;
            scrollbar-color: var(--royal-gold) rgba(201, 168, 106, 0.1);
        }
        
        .categories-scroll::-webkit-scrollbar {
            height: 4px;
        }
        
        .categories-scroll::-webkit-scrollbar-track {
            background: rgba(201, 168, 106, 0.1);
            border-radius: 10px;
        }
        
        .categories-scroll::-webkit-scrollbar-thumb {
            background: var(--royal-gold);
            border-radius: 10px;
        }
        
        .category-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(228, 212, 180, 0.3);
            border: 1.5px solid rgba(201, 168, 106, 0.4);
            border-radius: 25px;
            text-decoration: none;
            color: var(--creamy-white);
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .category-chip i {
            color: var(--royal-gold);
            font-size: 0.875rem;
        }
        
        .category-chip:hover {
            background: rgba(228, 212, 180, 0.5);
            border-color: var(--royal-gold);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(201, 168, 106, 0.3);
            color: var(--gold-light);
        }
        
        .category-chip.active {
            background: linear-gradient(135deg, var(--royal-gold), #b39358);
            border-color: var(--royal-gold);
            color: white;
            box-shadow: 0 4px 12px rgba(201, 168, 106, 0.4);
            font-weight: 600;
        }
        
        .category-chip.active i {
            color: white;
        }
        
        .chip-badge {
            background: rgba(201, 168, 106, 0.3);
            color: var(--creamy-white);
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .category-chip.active .chip-badge {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        /* Inline Search */
        .search-container-inline {
            min-width: 280px;
        }
        
        .search-form-inline {
            position: relative;
            width: 100%;
        }
        
        .search-input-inline {
            width: 100%;
            padding: 0.6rem 0.75rem 0.6rem 2.5rem;
            border: 2px solid rgba(201, 168, 106, 0.4);
            border-radius: 25px;
            font-size: 0.9rem;
            background: rgba(228, 212, 180, 0.3);
            color: var(--creamy-white);
            transition: all 0.3s ease;
        }
        
        .search-input-inline::placeholder {
            color: rgba(252, 248, 242, 0.6);
        }
        
        .search-input-inline:focus {
            outline: none;
            border-color: var(--royal-gold);
            background: rgba(228, 212, 180, 0.5);
            box-shadow: 0 0 15px rgba(201, 168, 106, 0.3);
        }
        
        .search-icon-inline {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--royal-gold);
            font-size: 0.9rem;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            color: var(--creamy-white);
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }
        
        .footer h5 {
            font-family: 'Dancing Script', cursive;
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
        }
        
        .footer h5 span:not([style*="color"]) {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.7rem;
            letter-spacing: 5px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 300;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: var(--gold-light);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--royal-gold);
            padding-left: 5px;
        }
        
        .social-links a {
            color: var(--royal-gold);
            font-size: 1.5rem;
            margin-right: 1rem;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            color: var(--gold-light);
            transform: scale(1.2);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(201, 168, 106, 0.3);
            margin-top: 2rem;
            padding-top: 2rem;
            text-align: center;
            color: var(--gold-light);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-arabic { font-size: 1.8rem; }
            .hero-english { font-size: 2.5rem; }
            .hero-subtitle { font-size: 1rem; }
            .section-title { font-size: 2rem; }
            .navbar-brand { font-size: 1.5rem; }
            .product-image { height: 250px; width: 100%; aspect-ratio: 1 / 1; }
            .product-image-container { height: 250px; min-height: 250px; max-height: 250px; }
            .product-title { font-size: 1.15rem; max-height: 52px; min-height: 52px; }
            
            .nav-icon-btn {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            .categories-search-bar {
                top: 75px;
                padding: 0.5rem 0;
            }
            
            .navbar.scrolled + .categories-search-bar {
                top: 60px;
            }
            
            .categories-search-wrapper {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .search-container-inline {
                width: 100%;
                min-width: auto;
            }
            
            .hero-section {
                padding-top: 140px;
            }
        }
        
        @media (max-width: 576px) {
            .hero-english { font-size: 2rem; }
            .product-image { height: 250px; width: 100%; aspect-ratio: 1 / 1; }
            .product-image-container { height: 250px; min-height: 250px; max-height: 250px; }
            .product-title { font-size: 1rem; max-height: 48px; min-height: 48px; }
            
            .category-chip {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .chip-badge {
                padding: 0.1rem 0.4rem;
                font-size: 0.7rem;
            }
        }
        
        /* Search Bar */
        .search-form {
            position: relative;
            max-width: 500px;
            margin: 2rem auto;
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Header */
        header {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Dancing Script', cursive;
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            line-height: 1.1;
            filter: drop-shadow(0 2px 10px rgba(212, 175, 55, 0.3));
        }
        
        .logo .logo-accent {
            display: none;
        }
        
        .logo span:not(.logo-accent) {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.7rem;
            letter-spacing: 5px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #c9a86a 0%, #e8d5b5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 300;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .cart-btn {
            background: #667eea;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            position: relative;
            transition: background 0.3s;
        }
        
        .cart-btn:hover {
            background: #764ba2;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Hero Section */
        .hero {
            text-align: center;
            padding: 4rem 2rem;
            color: white;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        /* Search Bar */
        .search-container {
            max-width: 600px;
            margin: 2rem auto 0;
        }
        
        .search-form {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .search-input {
            flex: 1;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
        }
        
        .search-btn {
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .clear-search-btn {
            padding: 0.8rem 1.5rem;
            background: #f0f0f0;
            color: #666;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .clear-search-btn:hover {
            background: #e0e0e0;
        }
        
        .search-info {
            text-align: center;
            margin-top: 1rem;
            color: white;
            font-size: 1rem;
        }
        
        .search-query {
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
        }
        
        /* Category Navigation */
        .category-nav {
            max-width: 1200px;
            margin: -1rem auto 2rem;
            padding: 0 2rem;
        }
        
        .category-nav-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .category-nav-title {
            font-weight: 600;
            color: #333;
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .category-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-block;
        }
        
        .category-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .category-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #764ba2;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .category-btn-all {
            background: linear-gradient(135deg, #28a745, #20c997);
            border-color: #28a745;
            color: white;
        }
        
        .category-btn-all:hover {
            background: linear-gradient(135deg, #218838, #1aa179);
            transform: translateY(-2px);
        }
        
        .category-btn-all.active {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.5);
        }
        
        /* Products Section */
        .products-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .section-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        
        .section-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .category-section {
            margin-bottom: 1rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .product-image {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            position: relative;
        }
        
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4444;
            color: white;
            padding: 0.5rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(255, 68, 68, 0.4);
            z-index: 1;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-name-en {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .product-name-ar {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1rem;
            direction: rtl;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 0.5rem;
        }
        
        .price-container {
            margin-bottom: 0.5rem;
        }
        
        .original-price {
            font-size: 1.1rem;
            color: #999;
            text-decoration: line-through;
            margin-right: 0.5rem;
        }
        
        .discounted-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff4444;
        }
        
        .savings-text {
            font-size: 0.85rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .product-stock {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .in-stock {
            background: #d4edda;
            color: #155724;
        }
        
        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
        
        .add-to-cart-btn {
            width: 100%;
            padding: 0.8rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .add-to-cart-btn:hover:not(:disabled) {
            background: #764ba2;
        }
        
        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: white;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        /* Alert Messages */
        .alert {
            position: fixed;
            top: 160px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1001;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        /* Video Hero Section */
        .video-hero {
            position: relative;
            width: 100%;
            height: 70vh;
            min-height: 500px;
            overflow: hidden;
            background: #000;
        }

        .video-hero video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.7) 0%, rgba(118, 75, 162, 0.7) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .video-hero h1 {
            font-size: 4rem;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease-out;
        }

        .video-hero p {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 1.2s ease-out;
        }

        .video-hero-cta {
            display: flex;
            gap: 1.5rem;
            animation: fadeInUp 1.4s ease-out;
        }

        .video-cta-btn {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .video-cta-primary {
            background: white;
            color: #667eea;
        }

        .video-cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255,255,255,0.3);
        }

        .video-cta-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .video-cta-secondary:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        .video-scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 2rem;
            animation: bounce 2s infinite;
            cursor: pointer;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            40% {
                transform: translateX(-50%) translateY(-10px);
            }
            60% {
                transform: translateX(-50%) translateY(-5px);
            }
        }

        @media (max-width: 768px) {
            .video-hero {
                height: 60vh;
                min-height: 400px;
            }

            .video-hero h1 {
                font-size: 2.5rem;
            }

            .video-hero p {
                font-size: 1.2rem;
            }

            .video-hero-cta {
                flex-direction: column;
                gap: 1rem;
            }

            .video-cta-btn {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }
        }
        
        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--royal-gold);
            border-radius: 30px;
            font-size: 1rem;
            background: white;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 15px rgba(201, 168, 106, 0.3);
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--royal-gold);
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Floating Ramadan Decorations -->
    <div class="floating-decorations">
        <i class="fas fa-moon floating-icon"></i>
        <i class="fas fa-star floating-icon"></i>
        <i class="fas fa-mosque floating-icon"></i>
        <i class="fas fa-star-and-crescent floating-icon"></i>
        <i class="fas fa-star floating-icon"></i>
        <i class="fas fa-moon floating-icon"></i>
    </div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center w-100">
                <a href="index.php" class="navbar-brand">
                    Poshy <span class="logo-accent">✦</span>
                    <span class="logo-subtitle">STORE</span>
                </a>
                
                <!-- Icon Navigation -->
                <div class="d-flex align-items-center gap-2">
                    <a href="index.php" class="nav-icon-btn" title="Home">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="pages/shop/shop.php" class="nav-icon-btn" title="Shop">
                        <i class="fas fa-shopping-bag"></i>
                    </a>
                    <?php if ($is_logged_in): ?>
                        <a href="pages/shop/my_orders.php" class="nav-icon-btn" title="My Orders">
                            <i class="fas fa-box"></i>
                        </a>
                    <?php endif; ?>
                    
                    <div class="nav-divider-line"></div>
                    
                    <?php if ($is_logged_in): ?>
                        <a href="pages/shop/cart.php" class="nav-icon position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="pages/auth/logout.php" class="nav-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    <?php else: ?>
                        <a href="pages/auth/signin.php" class="nav-icon">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Categories and Search Bar -->
    <div class="categories-search-bar">
        <div class="container-fluid px-4">
            <div class="categories-search-wrapper">
                <!-- Categories -->
                <div class="categories-container">
                    <?php if (!empty($categories)): ?>
                        <div class="categories-scroll">
                            <a href="index.php" class="category-chip <?= empty($selected_category) ? 'active' : '' ?>">
                                <i class="fas fa-th"></i>
                                <span>All</span>
                            </a>
                            <?php foreach ($categories as $category): ?>
                                <a href="index.php?category=<?= $category['id'] ?>" 
                                   class="category-chip <?= $selected_category == $category['id'] ? 'active' : '' ?>">
                                    <i class="fas fa-tag"></i>
                                    <span><?= htmlspecialchars($category['name_en']) ?></span>
                                    <span class="chip-badge"><?= $category['product_count'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Search -->
                <div class="search-container-inline">
                    <form method="GET" action="index.php" class="search-form-inline">
                        <i class="fas fa-search search-icon-inline"></i>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input-inline" 
                            placeholder="Search products..." 
                            value="<?= htmlspecialchars($search_query) ?>"
                        >
                        <?php if ($selected_category): ?>
                            <input type="hidden" name="category" value="<?= $selected_category ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-pattern"></div>
        <i class="fas fa-star hero-decoration star-left"></i>
        <i class="fas fa-moon hero-decoration star-right"></i>
        
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-english">Welcome to Poshy Store</h1>
                <p class="hero-subtitle">
                    Discover our exclusive Ramadan collection of luxury products. 
                    Embrace the spirit of the holy month with elegance and grace.
                </p>
                <a href="#products" class="btn hero-btn">
                    <i class="fas fa-shopping-bag me-2"></i>Shop Now
                </a>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section" id="products">
        <div class="container-fluid px-4">
            <?php if (!empty($search_query)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-search me-2"></i>
                    Search results for: <strong>"<?= htmlspecialchars($search_query) ?>"</strong>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary ms-3">Clear Search</a>
                </div>
            <?php endif; ?>
            
            <?php if (empty($products_by_category)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open" style="font-size: 4rem; color: var(--royal-gold); opacity: 0.5;"></i>
                    <h3 class="mt-4">No products found</h3>
                    <p class="text-muted">Try searching for something else or browse all categories</p>
                    <a href="index.php" class="btn btn-primary mt-3">View All Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($products_by_category as $cat_data): ?>
                    <h2 class="section-title">
                        <i class="fas fa-star-and-crescent me-2" style="color: var(--royal-gold);"></i>
                        <?= htmlspecialchars($cat_data['category']['name_en']) ?>
                        <span style="font-size: 1.5rem; color: #999;"> / <?= htmlspecialchars($cat_data['category']['name_ar']) ?></span>
                    </h2>
                    
                    <div class="row">
                        <?php foreach ($cat_data['products'] as $product): ?>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="product-card">
                                    <div class="product-image-container">
                                        <!-- Ramadan Decorative Icons -->
                                        <div class="ramadan-icon-overlay">
                                            <i class="fas fa-moon"></i>
                                        </div>
                                        
                                        <!-- Corner Star Decorations -->
                                        <div class="star-decoration star-tl">✦</div>
                                        <div class="star-decoration star-tr">✦</div>
                                        <div class="star-decoration star-bl">✧</div>
                                        <div class="star-decoration star-br">✧</div>
                                        
                                        <?php if ($product['has_discount']): ?>
                                            <span class="product-badge">
                                                <i class="fas fa-gift me-1"></i><?= intval($product['discount_percentage']) ?>% OFF
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php 
                                            $image_src = $product['image_link'];
                                            if (empty($image_src) || $image_src === 'NULL') {
                                                $image_src = 'images/placeholder-cosmetics.svg';
                                            }
                                        ?>
                                        
                                        <img 
                                            src="<?= htmlspecialchars($image_src) ?>" 
                                            alt="<?= htmlspecialchars($product['name_en']) ?>"
                                            class="product-image"
                                            onerror="this.src='images/placeholder-cosmetics.svg'"
                                        >
                                    </div>
                                    
                                    <div class="product-body">
                                        <h3 class="product-title"><?= htmlspecialchars($product['name_en']) ?></h3>
                                        <p class="product-title-ar"><?= htmlspecialchars($product['name_ar']) ?></p>
                                        
                                        <div class="product-price">
                                            <div>
                                                <span class="price-current"><?= number_format($product['price_jod'], 3) ?> JOD</span>
                                                <?php if ($product['has_discount'] && $product['original_price'] > 0): ?>
                                                    <span class="price-original"><?= number_format($product['original_price'], 3) ?> JOD</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="product-actions">
                                            <?php if ($is_logged_in): ?>
                                                <button 
                                                    class="btn btn-add-cart"
                                                    onclick="addToCart(<?= $product['id'] ?>)"
                                                >
                                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                                </button>
                                            <?php else: ?>
                                                <a href="pages/auth/signin.php" class="btn btn-add-cart">
                                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In to Buy
                                                </a>
                                            <?php endif; ?>
                                            <a href="pages/shop/product_detail.php?id=<?= $product['id'] ?>" class="btn btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Poshy<br><span style="font-size: 0.7rem; letter-spacing: 5px; font-weight: 300;">STORE</span></h5>
                    <p class="text-light">Your premium destination for luxury products during the holy month of Ramadan and beyond.</p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home me-2"></i>Home</a></li>
                        <li><a href="pages/shop/shop.php"><i class="fas fa-shopping-bag me-2"></i>Shop</a></li>
                        <?php if ($is_logged_in): ?>
                            <li><a href="pages/shop/my_orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                            <li><a href="pages/shop/cart.php"><i class="fas fa-shopping-cart me-2"></i>Cart</a></li>
                        <?php else: ?>
                            <li><a href="pages/auth/signin.php"><i class="fas fa-sign-in-alt me-2"></i>Sign In</a></li>
                            <li><a href="pages/auth/signup.php"><i class="fas fa-user-plus me-2"></i>Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Contact Us</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope me-2"></i>info@poshystore.com</li>
                        <li><i class="fas fa-phone me-2"></i>+962 6 XXX XXXX</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Amman, Jordan</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Poshy Store. All rights reserved. <i class="fas fa-heart" style="color: var(--royal-gold);"></i> Made with love for Ramadan</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Navbar scroll effect and category nav hide/show
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const categoryNav = document.getElementById('categories');
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Navbar scroll effect
            if (scrollTop > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            // Hide category nav on scroll down, show on scroll up
            if (categoryNav) {
                if (scrollTop > lastScrollTop && scrollTop > 200) {
                    // Scrolling down - hide category nav completely
                    categoryNav.style.display = 'none';
                } else if (scrollTop < lastScrollTop) {
                    // Scrolling up - show category nav
                    categoryNav.style.display = 'block';
                }
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Add to Cart function
        function addToCart(productId) {
            fetch('api/add_to_cart_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Product added to cart successfully!');
                    location.reload(); // Reload to update cart count
                } else {
                    alert('❌ ' + (data.error || 'Failed to add product to cart'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ An error occurred. Please try again.');
            });
        }
    </script>
    <!-- Header -->
    <header>
        <nav>
            <a href="index.php" class="logo">
                Poshy <span class="logo-accent">✦</span>
                <span style="font-size: 0.7rem; letter-spacing: 3px; display: block; margin-top: -0.2rem;">STORE</span>
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="pages/shop/shop.php">Shop</a>
                <?php if ($is_logged_in): ?>
                    <a href="pages/shop/my_orders.php">My Orders</a>
                    <a href="pages/auth/logout.php">Logout</a>
                    <a href="pages/shop/cart.php" class="cart-btn">
                        Cart
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="pages/auth/signin.php">Sign In</a>
                    <a href="pages/auth/signup.php" class="cart-btn">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Video Hero Section -->
    <div class="video-hero">
        <!-- Video Background -->
        <video autoplay muted loop playsinline id="heroVideo">
            <source src="https://cdn.pixabay.com/video/2023/07/24/173048-849766806_large.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        
        <!-- Overlay Content -->
        <div class="video-overlay">
            <h1>✨ Luxury Lifestyle Awaits</h1>
            <p>Discover Premium Products That Define Elegance</p>
            <div class="video-hero-cta">
                <a href="#products" class="video-cta-btn video-cta-primary">Shop Now</a>
                <a href="#categories" class="video-cta-btn video-cta-secondary">Browse Collections</a>
            </div>
            <div class="video-scroll-indicator" onclick="document.getElementById('products').scrollIntoView({behavior: 'smooth'});">⌄</div>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero" id="products">
        <h1>Welcome to Poshy Store</h1>
        <p>Discover luxury products crafted for elegance</p>
        <?php if ($is_logged_in): ?>
            <?php if (isset($_GET['welcome'])): ?>
                <p style="font-size: 1.2rem; opacity: 1; background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; display: inline-block; margin-top: 1rem;">
                    🎉 Welcome, <strong><?= htmlspecialchars($_SESSION['firstname']) ?></strong>! Your account has been created successfully!
                </p>
            <?php else: ?>
                <p style="font-size: 1rem; opacity: 0.9;">Welcome back, <strong><?= htmlspecialchars($_SESSION['firstname']) ?></strong>!</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Search Bar -->
        <div class="search-container">
            <form method="GET" action="index.php" class="search-form">
                <?php if ($selected_category): ?>
                    <input type="hidden" name="category" value="<?= $selected_category ?>">
                <?php endif; ?>
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="🔍 Search for products by name..." 
                    value="<?= htmlspecialchars($search_query) ?>"
                    required
                >
                <button type="submit" class="search-btn">Search</button>
            </form>
            <?php if ($is_search_mode): ?>
                <div class="search-info">
                    Searching for: <span class="search-query"><?= htmlspecialchars($search_query) ?></span>
                    <a href="index.php<?= $selected_category ? '?category='.$selected_category : '' ?>" class="clear-search-btn" style="margin-left: 1rem;">Clear Search</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Navigation -->
    <div class="category-nav" id="categories">
        <div class="category-nav-container">
            <span class="category-nav-title">📂 Categories:</span>
            <a href="index.php" class="category-btn category-btn-all <?= !$selected_category ? 'active' : '' ?>">
                🏠 All Products
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="index.php?category=<?= $category['id'] ?>" 
                   class="category-btn <?= $selected_category == $category['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($category['name_en']) ?> (<?= $category['product_count'] ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Products Section -->
    <div class="products-section">
        <div class="section-header">
            <?php if ($is_search_mode): ?>
                <?php 
                    $total_results = 0;
                    foreach ($products_by_category as $cat_data) {
                        $total_results += count($cat_data['products']);
                    }
                ?>
                <h2>Search Results</h2>
                <p>Found <?= $total_results ?> product<?= $total_results != 1 ? 's' : '' ?> matching "<?= htmlspecialchars($search_query) ?>"</p>
            <?php elseif ($selected_category && !empty($products_by_category)): ?>
                <?php $current_cat = reset($products_by_category)['category']; ?>
                <h2><?= htmlspecialchars($current_cat['name_en']) ?></h2>
                <p><?= htmlspecialchars($current_cat['name_ar']) ?> • <?= count(reset($products_by_category)['products']) ?> products available</p>
            <?php else: ?>
                <h2>Shop by Category</h2>
                <p>Browse our curated collection organized just for you</p>
            <?php endif; ?>
        </div>

        <?php if (empty($products_by_category)): ?>
            <div class="empty-state">
                <?php if ($is_search_mode): ?>
                    <h3>No products found</h3>
                    <p>No products match your search "<?= htmlspecialchars($search_query) ?>". Try different keywords.</p>
                    <a href="index.php<?= $selected_category ? '?category='.$selected_category : '' ?>" class="clear-search-btn" style="margin-top: 1rem;">Clear Search</a>
                <?php else: ?>
                    <h3>No products available at the moment</h3>
                    <p>Check back soon for new arrivals!</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($products_by_category as $cat_id => $cat_data): ?>
                <div class="category-section">
                    <div class="products-grid">
                        <?php foreach ($cat_data['products'] as $product): ?>
                            <div class="product-card" onclick="window.location.href='pages/shop/product_detail.php?id=<?= $product['id'] ?>'">
                                <div class="product-image">
                                    <?php if ($product['has_discount']): ?>
                                        <div class="discount-badge">-<?= number_format($product['discount_percentage'], 0) ?>% OFF</div>
                                    <?php endif; ?>
                                    <?php 
                                    $icons = ['👜', '⌚', '🕶️', '👔', '💼', '👞', '🎩', '💍'];
                                    echo $icons[$product['id'] % count($icons)];
                                    ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-name-en"><?= htmlspecialchars($product['name_en']) ?></div>
                                    <div class="product-name-ar"><?= htmlspecialchars($product['name_ar']) ?></div>
                                    
                                    <?php if ($product['has_discount'] && $product['original_price'] > 0): ?>
                                        <div class="price-container">
                                            <span class="original-price"><?= formatJOD($product['original_price']) ?></span>
                                            <span class="discounted-price"><?= $product['price_formatted'] ?></span>
                                        </div>
                                        <div class="savings-text">
                                            💰 Save <?= formatJOD($product['original_price'] - $product['price_jod']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="product-price"><?= $product['price_formatted'] ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="product-stock">
                                        <?php if ($product['in_stock']): ?>
                                            <span class="stock-badge in-stock">In Stock<?php if (isAdmin()): ?>: <?= $product['stock_quantity'] ?> units<?php endif; ?></span>
                                        <?php else: ?>
                                            <span class="stock-badge out-of-stock">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($is_logged_in): ?>
                                        <button 
                                            class="add-to-cart-btn" 
                                            onclick="event.stopPropagation(); addToCart(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name_en']) ?>')"
                                            <?= !$product['in_stock'] ? 'disabled' : '' ?>
                                        >
                                            <?= $product['in_stock'] ? 'Add to Cart' : 'Out of Stock' ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="add-to-cart-btn" onclick="event.stopPropagation(); window.location.href='pages/auth/signin.php'">
                                            Sign in to Purchase
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <!-- About & Contact Info -->
            <div class="footer-column">
                <h3>📍 Contact Us</h3>
                <div class="contact-info">
                    <p><strong>Poshy Store</strong></p>
                    <p>📧 Email: support@poshystore.com</p>
                    <p>📞 Phone: +962 6 123 4567</p>
                    <p>📱 WhatsApp: +962 79 123 4567</p>
                    <p>🏢 Address: Amman, Jordan</p>
                    <p>⏰ Support Hours: Mon-Fri, 9 AM - 6 PM</p>
                </div>
                <div class="social-links">
                    <a href="#" title="Facebook">📘</a>
                    <a href="#" title="Instagram">📷</a>
                    <a href="#" title="Twitter">🐦</a>
                    <a href="#" title="LinkedIn">💼</a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-column">
                <h3>🔗 Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php">🏠 Home</a></li>
                    <li><a href="pages/shop/shop.php">🛍️ Shop</a></li>
                    <li><a href="pages/shop/cart.php">🛒 Cart</a></li>
                    <li><a href="pages/shop/my_orders.php">📦 My Orders</a></li>
                    <?php if (!$is_logged_in): ?>
                        <li><a href="pages/auth/signin.php">🔐 Sign In</a></li>
                        <li><a href="pages/auth/signup.php">📝 Sign Up</a></li>
                    <?php else: ?>
                        <li><a href="pages/auth/logout.php">🚪 Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Policies -->
            <div class="footer-column">
                <h3>📋 Policies</h3>
                <ul class="footer-links">
                    <li><a href="pages/policies/terms-of-service.php">📜 Terms of Service</a></li>
                    <li><a href="pages/policies/privacy-policy.php">🔒 Privacy Policy</a></li>
                    <li><a href="pages/policies/return-policy.php">↩️ Return Policy</a></li>
                    <li><a href="pages/policies/shipping-policy.php">🚚 Shipping Policy</a></li>
                    <li><a href="pages/policies/cancellation-policy.php">🚫 Cancellation Policy</a></li>
                </ul>
            </div>

            <!-- Recent Categories -->
            <div class="footer-column footer-products">
                <h3>📂 Shop by Category</h3>
                <?php if (!empty($categories)): ?>
                    <div class="footer-product-list">
                        <?php 
                        $category_icons = ['👜', '⌚', '🕶️', '👔', '💼', '👞', '🎩', '💍'];
                        foreach ($categories as $index => $category): 
                        ?>
                            <a href="index.php?category=<?= $category['id'] ?>" class="footer-product">
                                <span class="footer-product-icon">
                                    <?= $category_icons[$index % count($category_icons)] ?>
                                </span>
                                <div class="footer-product-info">
                                    <div class="footer-product-name"><?= htmlspecialchars($category['name_en']) ?></div>
                                    <div class="footer-product-price"><?= $category['product_count'] ?> products</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No categories available</p>
                <?php endif; ?>
            </div>

            <!-- Newsletter -->
            <div class="footer-column">
                <h3>📬 Newsletter</h3>
                <p class="newsletter-text">Subscribe to get special offers and updates!</p>
                <div class="newsletter-form">
                    <input type="email" placeholder="Your email address" class="newsletter-input">
                    <button class="newsletter-btn">Subscribe</button>
                </div>
                <div class="payment-methods">
                    <p><strong>We Accept:</strong></p>
                    <div class="payment-icons">
                        <span title="Visa">💳</span>
                        <span title="Mastercard">💳</span>
                        <span title="PayPal">💰</span>
                        <span title="Apple Pay">📱</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <div style="margin-bottom: 0.8rem;">
                <a href="pages/policies/terms-of-service.php" style="color: #a0aec0; text-decoration: none; margin: 0 0.8rem;">Terms</a>
                <a href="pages/policies/privacy-policy.php" style="color: #a0aec0; text-decoration: none; margin: 0 0.8rem;">Privacy</a>
                <a href="pages/policies/return-policy.php" style="color: #a0aec0; text-decoration: none; margin: 0 0.8rem;">Returns</a>
                <a href="pages/policies/shipping-policy.php" style="color: #a0aec0; text-decoration: none; margin: 0 0.8rem;">Shipping</a>
                <a href="pages/policies/cancellation-policy.php" style="color: #a0aec0; text-decoration: none; margin: 0 0.8rem;">Cancellation</a>
            </div>
            <p>&copy; 2026 Poshy Store. All rights reserved. | Luxury E-Commerce Platform</p>
        </div>
    </footer>

    <!-- Chatbot Widget -->
    <div id="chatbot-container">
        <div id="chatbot-widget" class="chatbot-closed">
            <div class="chatbot-header">
                <span>💬 Poshy Assistant</span>
                <button id="chatbot-close" onclick="toggleChatbot()">×</button>
            </div>
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="bot-message">
                    <p>👋 Hello! Welcome to Poshy Store! How can I help you today?</p>
                </div>
                <div class="bot-message">
                    <p>Choose a question below:</p>
                </div>
            </div>
            <div class="chatbot-questions">
                <button class="question-btn" onclick="askQuestion('hours')">⏰ Store Hours?</button>
                <button class="question-btn" onclick="askQuestion('shipping')">🚚 Shipping Info?</button>
                <button class="question-btn" onclick="askQuestion('returns')">↩️ Return Policy?</button>
                <button class="question-btn" onclick="askQuestion('payment')">💳 Payment Methods?</button>
                <button class="question-btn" onclick="askQuestion('contact')">📞 Contact Us?</button>
                <button class="question-btn" onclick="askQuestion('discount')">🎁 Discounts?</button>
            </div>
        </div>
        <button id="chatbot-button" onclick="toggleChatbot()">
            💬
        </button>
    </div>

    <style>
        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, #2d3748, #1a202c);
            color: white;
            margin-top: 4rem;
            padding: 3rem 2rem 1rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .contact-info {
            line-height: 1.8;
        }

        .contact-info p {
            margin-bottom: 0.5rem;
            color: #e2e8f0;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            font-size: 1.8rem;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .social-links a:hover {
            transform: scale(1.2);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.7rem;
        }

        .footer-links a {
            color: #e2e8f0;
            text-decoration: none;
            transition: color 0.3s;
            display: inline-block;
        }

        .footer-links a:hover {
            color: #667eea;
            transform: translateX(5px);
        }

        .footer-product-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .footer-product {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.6rem;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
        }

        .footer-product:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(5px);
        }

        .footer-product-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }

        .footer-product-info {
            flex: 1;
        }

        .footer-product-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #e2e8f0;
            margin-bottom: 0.2rem;
        }

        .footer-product-price {
            font-size: 0.85rem;
            color: #a0aec0;
            font-weight: 600;
        }

        .newsletter-text {
            color: #e2e8f0;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .newsletter-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .newsletter-input {
            padding: 0.8rem;
            border-radius: 8px;
            border: none;
            outline: none;
            font-size: 0.95rem;
        }

        .newsletter-btn {
            padding: 0.8rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .newsletter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .payment-methods {
            margin-top: 1.5rem;
        }

        .payment-methods p {
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }

        .payment-icons {
            display: flex;
            gap: 0.8rem;
            font-size: 1.8rem;
        }

        .payment-icons span {
            cursor: default;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            text-align: center;
        }

        .footer-bottom p {
            color: #a0aec0;
            font-size: 0.9rem;
        }

        .footer-bottom a {
            transition: color 0.3s;
        }

        .footer-bottom a:hover {
            color: #667eea !important;
        }

        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
            }
        }

        /* Chatbot Styles */
        #chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        #chatbot-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-size: 28px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #chatbot-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }

        #chatbot-widget {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            max-height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.3s;
        }

        #chatbot-widget.chatbot-closed {
            opacity: 0;
            transform: scale(0.8);
            pointer-events: none;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        #chatbot-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            max-height: 300px;
            background: #f8f9fa;
        }

        .bot-message {
            background: white;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            animation: slideIn 0.3s;
        }

        .user-message {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 10px;
            margin-left: 40px;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chatbot-questions {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            background: white;
        }

        .question-btn {
            padding: 8px 12px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            flex: 1 1 calc(50% - 4px);
            min-width: 140px;
        }

        .question-btn:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        @media (max-width: 480px) {
            #chatbot-widget {
                width: calc(100vw - 40px);
                right: 20px;
            }
        }
    </style>

    <script>
        let chatbotOpen = false;

        function toggleChatbot() {
            chatbotOpen = !chatbotOpen;
            const widget = document.getElementById('chatbot-widget');
            const button = document.getElementById('chatbot-button');
            
            if (chatbotOpen) {
                widget.classList.remove('chatbot-closed');
                button.style.display = 'none';
            } else {
                widget.classList.add('chatbot-closed');
                button.style.display = 'flex';
            }
        }

        function askQuestion(type) {
            const messagesDiv = document.getElementById('chatbot-messages');
            
            // User question
            const userMsg = document.createElement('div');
            userMsg.className = 'user-message';
            
            // Bot response
            const botMsg = document.createElement('div');
            botMsg.className = 'bot-message';
            
            const responses = {
                'hours': {
                    question: '⏰ Store Hours?',
                    answer: 'We are open 24/7 online! Our customer support is available Monday-Friday, 9 AM - 6 PM.'
                },
                'shipping': {
                    question: '🚚 Shipping Info?',
                    answer: 'We offer free shipping on orders over $50! Standard delivery takes 3-5 business days. Express shipping is available for $9.99.'
                },
                'returns': {
                    question: '↩️ Return Policy?',
                    answer: 'We have a 30-day return policy. Items must be unused and in original packaging. Refunds are processed within 5-7 business days.'
                },
                'payment': {
                    question: '💳 Payment Methods?',
                    answer: 'We accept all major credit cards (Visa, Mastercard, Amex), PayPal, and Apple Pay. All transactions are secure and encrypted.'
                },
                'contact': {
                    question: '📞 Contact Us?',
                    answer: 'Email: support@poshystore.com<br>Phone: +962 6 123 4567<br>We respond within 24 hours!'
                },
                'discount': {
                    question: '🎁 Discounts?',
                    answer: 'First-time customers get 10% off! Sign up for our newsletter to receive exclusive deals and promotional codes.'
                }
            };
            
            const response = responses[type];
            userMsg.innerHTML = '<p>' + response.question + '</p>';
            botMsg.innerHTML = '<p>' + response.answer + '</p>';
            
            messagesDiv.appendChild(userMsg);
            messagesDiv.appendChild(botMsg);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        // Newsletter subscription
        document.addEventListener('DOMContentLoaded', function() {
            const newsletterBtn = document.querySelector('.newsletter-btn');
            const newsletterInput = document.querySelector('.newsletter-input');
            
            if (newsletterBtn && newsletterInput) {
                newsletterBtn.addEventListener('click', function() {
                    const email = newsletterInput.value.trim();
                    if (email && validateEmail(email)) {
                        showAlert('success', '🎉 Thank you for subscribing to our newsletter!');
                        newsletterInput.value = '';
                    } else {
                        showAlert('error', '❌ Please enter a valid email address');
                    }
                });
                
                newsletterInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        newsletterBtn.click();
                    }
                });
            }
        });

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function addToCart(productId, productName) {
            fetch('api/add_to_cart_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', productName + ' added to cart!');
                    // Update cart count
                    location.reload();
                } else {
                    showAlert('error', data.error || 'Failed to add to cart');
                }
            })
            .catch(error => {
                showAlert('error', 'Network error. Please try again.');
            });
        }

        function showAlert(type, message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    </script>
</body>
</html>
