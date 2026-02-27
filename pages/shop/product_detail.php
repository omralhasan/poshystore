<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/product_manager.php';
require_once __DIR__ . '/../../includes/cart_handler.php';
require_once __DIR__ . '/../../includes/auto_translate.php';

// Base URL for absolute paths – uses central config constant
$base_url = BASE_PATH;

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . $base_url . '/index.php');
    exit;
}

$product_id = (int)$_GET['id'];
$is_logged_in = isset($_SESSION['user_id']);

// Get product details
$product_result = getProductById($product_id);
if (!$product_result['success']) {
    header('Location: ' . $base_url . '/index.php');
    exit;
}

$product = $product_result['product'];

// Auto-translate English content to Arabic when language is Arabic
// and Arabic fields are missing. Results are cached back to the DB.
if ($current_lang === 'ar') {
    ensureArabicContent($conn, $product);
}

// Get product tags
$product_tags = getProductTags($product_id);

// Get product reviews (last 5)
$reviews_result = getProductReviews($product_id, 5);
$reviews = $reviews_result['reviews'] ?? [];
$average_rating = $reviews_result['average_rating'] ?? 0;
$review_count = $reviews_result['count'] ?? 0;

// Check if user has already reviewed this product
$user_review = null;
if ($is_logged_in) {
    $user_review_result = getUserProductReview($product_id, $_SESSION['user_id']);
    if ($user_review_result['has_review']) {
        $user_review = $user_review_result['review'];
    }
}

// Get cart count
$cart_count = 0;
$product_in_cart_quantity = 0;
if ($is_logged_in) {
    $cart_info = getCartCount($_SESSION['user_id']);
    $cart_count = $cart_info['count'] ?? 0;
    
    // Check if this specific product is in cart
    $cart_check_sql = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($cart_check_sql);
    $stmt->bind_param('ii', $_SESSION['user_id'], $product_id);
    $stmt->execute();
    $cart_check_result = $stmt->get_result();
    if ($cart_check_row = $cart_check_result->fetch_assoc()) {
        $product_in_cart_quantity = (int)$cart_check_row['quantity'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_lang === 'ar' ? $product['name_ar'] : $product['name_en']) ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        /* Product Detail Page Enhancements */
        .back-link {
            color: var(--gold-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: var(--royal-gold);
            transform: translateX(-5px);
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Product Section */
        .product-container {
            background: var(--cream-color);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(72, 54, 112, 0.15);
            margin-bottom: 2rem;
        }
        
        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            padding: 3rem;
        }
        
        .product-image-large {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10rem;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(72, 54, 112, 0.3);
        }
        
        .carousel-slide {
            width: 100%;
            height: 100%;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 10rem;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }
        
        .carousel-slide.active {
            display: flex;
            opacity: 1;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .carousel-indicators {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        
        .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .indicator.active {
            background: white;
        }
        
        /* Thumbnail Gallery Strip */
        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            overflow-x: auto;
            padding: 5px 0;
            scrollbar-width: thin;
            scrollbar-color: var(--gold-color) transparent;
        }
        
        .thumbnail-gallery::-webkit-scrollbar {
            height: 4px;
        }
        
        .thumbnail-gallery::-webkit-scrollbar-thumb {
            background: var(--gold-color);
            border-radius: 2px;
        }
        
        .thumb-item {
            flex: 0 0 70px;
            width: 70px;
            height: 70px;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .thumb-item:hover {
            border-color: var(--gold-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 168, 106, 0.3);
        }
        
        .thumb-item.active {
            border-color: var(--purple-color);
            box-shadow: 0 4px 15px rgba(45, 19, 44, 0.3);
        }
        
        .thumb-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .thumb-item .thumb-emoji {
            font-size: 2rem;
        }
        
        /* Image Lightbox */
        .image-lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.92);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            cursor: zoom-out;
        }
        
        .image-lightbox.active {
            display: flex;
        }
        
        .lightbox-img {
            max-width: 90vw;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 15px 60px rgba(0, 0, 0, 0.5);
            animation: lightboxIn 0.3s ease;
        }
        
        @keyframes lightboxIn {
            from { transform: scale(0.85); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 25px;
            color: white;
            font-size: 2.5rem;
            cursor: pointer;
            z-index: 10001;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            transition: background 0.3s;
        }
        
        .lightbox-close:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 2rem;
            cursor: pointer;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            transition: background 0.3s;
        }
        
        .lightbox-nav:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .lightbox-prev { left: 20px; }
        .lightbox-next { right: 20px; }
        
        .lightbox-counter {
            position: absolute;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,0.7);
            font-size: 0.95rem;
        }
        
        /* Zoom hint on main image */
        .product-image-large {
            cursor: zoom-in;
        }
        
        .zoom-hint {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            z-index: 5;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        
        .product-image-large:hover .zoom-hint {
            opacity: 1;
        }
        
        .product-details {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .product-title-en {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .product-title-ar {
            font-size: 1.8rem;
            color: #666;
            direction: rtl;
        }
        
        .rating-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stars {
            color: #ffc107;
            font-size: 1.5rem;
        }
        
        .rating-text {
            color: #666;
            font-size: 1rem;
        }
        
        .product-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--gold-color);
        }
        
        .price-section {
            margin: 1.5rem 0;
        }
        
        .discount-badge-detail {
            display: inline-block;
            background: #ff4444;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .original-price-detail {
            font-size: 1.8rem;
            color: #999;
            text-decoration: line-through;
            margin-right: 1rem;
        }
        
        .discounted-price-detail {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff4444;
        }
        
        .savings-amount {
            font-size: 1.2rem;
            color: var(--gold-color);
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .product-stock {
            font-size: 1.1rem;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
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
        
        /* Product Info Tabs */
        .product-tabs {
            margin: 2rem 0;
        }
        
        .nav-tabs-product {
            border-bottom: 3px solid var(--gold-color);
            display: flex;
            gap: 0;
            margin-bottom: 0;
            padding: 0;
            list-style: none;
        }
        
        .nav-tabs-product .nav-item {
            margin: 0;
        }
        
        .nav-tabs-product .tab-link,
        .nav-tabs-product .nav-link {
            background: rgba(201, 168, 106, 0.1);
            border: none;
            color: var(--purple-color);
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 10px 10px 0 0;
            margin-right: 5px;
            border: 2px solid transparent;
            border-bottom: none;
        }
        
        .nav-tabs-product .tab-link:hover,
        .nav-tabs-product .nav-link:hover {
            background: rgba(201, 168, 106, 0.2);
            color: var(--royal-gold);
        }
        
        .nav-tabs-product .tab-link.active,
        .nav-tabs-product .nav-link.active {
            background: white;
            color: var(--gold-color);
            border-color: var(--gold-color);
            border-bottom: 2px solid white;
            position: relative;
            z-index: 1;
            margin-bottom: -2px;
        }
        
        .tab-content-product {
            background: white;
            padding: 2rem;
            border: 2px solid var(--gold-color);
            border-radius: 0 10px 10px 10px;
            min-height: 200px;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active,
        .tab-pane.show {
            display: block;
        }
        
        .tab-pane h4 {
            color: var(--purple-color);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .tab-pane p, .tab-pane ul {
            color: #555;
            line-height: 1.8;
            font-size: 1.05rem;
        }
        
        .tab-pane ul {
            padding-left: 1.5rem;
        }
        
        .tab-pane ul li {
            margin-bottom: 0.75rem;
        }
        
        .product-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            padding: 1.5rem;
            background: rgba(201, 168, 106, 0.08);
            border-radius: 10px;
            border-left: 4px solid var(--gold-color);
        }
        
        /* Reviews Section */
        .reviews-section {
            background: var(--cream-color);
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 8px 30px rgba(72, 54, 112, 0.15);
        }
        
        .reviews-header {
            font-size: 2rem;
            font-weight: bold;
            color: var(--deep-purple);
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--gold-color);
            padding-bottom: 1rem;
        }
        
        /* Review Form */
        .review-form {
            background: rgba(201, 168, 106, 0.08);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 2px solid rgba(201, 168, 106, 0.2);
        }
        
        .review-form h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--purple-color);
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--purple-color);
        }
        
        .rating-input {
            display: flex;
            gap: 0.5rem;
        }
        
        .rating-input input[type="radio"] {
            display: none;
        }
        
        .rating-input label {
            font-size: 2rem;
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }
        
        .rating-input input[type="radio"]:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }
        
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        
        textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(201, 168, 106, 0.4);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s;
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--royal-gold);
            box-shadow: 0 0 0 3px rgba(201, 168, 106, 0.15);
        }
        
        /* Review List */
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .review-item {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            border-left: 4px solid var(--gold-color);
            box-shadow: 0 2px 10px rgba(72, 54, 112, 0.08);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .review-author {
            font-weight: bold;
            color: var(--purple-color);
            font-size: 1.1rem;
        }
        
        .review-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-stars {
            color: #ffc107;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .review-text {
            color: #555;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .no-reviews {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Alert Messages */
        .alert {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
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
            background: linear-gradient(135deg, rgba(201, 168, 106, 0.15), rgba(201, 168, 106, 0.05));
            color: #155724;
            border-left: 4px solid var(--gold-color);
            border: 2px solid var(--gold-color);
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.05));
            color: #721c24;
            border-left: 4px solid #dc3545;
            border: 2px solid #dc3545;
        }
        
        /* Cart Popup Modal */
        .cart-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(45, 19, 44, 0.85);
            z-index: 9999;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }
        
        .cart-modal-overlay.active {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .cart-modal {
            background: var(--cream-color);
            border-radius: 20px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.3s ease-out;
        }
        
        .cart-modal-header {
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            padding: 1.5rem 2rem;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-modal-title {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .cart-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .cart-modal-body {
            padding: 2rem;
        }
        
        .added-product-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(72, 54, 112, 0.1);
        }
        
        .added-product-content {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .added-product-image {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            flex-shrink: 0;
        }
        
        .added-product-info {
            flex-grow: 1;
        }
        
        .added-product-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--purple-color);
            margin-bottom: 0.5rem;
        }
        
        .added-product-price {
            color: var(--gold-color);
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .quantity-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            color: #666;
        }
        
        /* Modal Quantity Controls */
        .modal-quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, rgba(201, 168, 106, 0.1), rgba(201, 168, 106, 0.05));
            border-radius: 12px;
            border: 2px solid var(--gold-color);
        }
        
        .modal-quantity-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #000;
            background: transparent;
            color: #000;
            border-radius: 50%;
            font-size: 1.3rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .modal-quantity-btn.decrease {
            border-color: #000;
            color: #000;
        }
        
        .modal-quantity-btn.decrease:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .modal-quantity-btn.increase {
            border-color: #000;
            color: #000;
        }
        
        .modal-quantity-btn.increase:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .modal-quantity-btn:hover {
            transform: scale(1.15);
        }
        
        .modal-quantity-btn:active {
            transform: scale(0.95);
        }
        
        .modal-quantity-display {
            flex-grow: 1;
            text-align: center;
        }
        
        .modal-quantity-label {
            font-size: 0.85rem;
            color: #666;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .modal-quantity-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--purple-color);
            display: block;
        }
        
        .cart-count-badge {
            background: linear-gradient(135deg, var(--gold-color), var(--royal-gold));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            margin-top: 1rem;
        }
        
        .recommended-section {
            margin-top: 2rem;
        }
        
        .recommended-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--purple-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .recommended-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }
        
        .recommended-item {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(72, 54, 112, 0.1);
            cursor: pointer;
        }
        
        .recommended-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(72, 54, 112, 0.2);
        }
        
        .recommended-item-image {
            width: 100%;
            height: 120px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 0.75rem;
        }
        
        .recommended-item-name {
            font-weight: 600;
            color: var(--purple-color);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            min-height: 2.5em;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .recommended-item-price {
            color: var(--gold-color);
            font-weight: bold;
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .recommended-original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }
        
        .recommended-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .recommended-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .recommended-btn-add {
            background: linear-gradient(135deg, var(--gold-color), var(--royal-gold));
            color: white;
        }
        
        .recommended-btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 168, 106, 0.4);
        }
        
        .recommended-btn-view {
            background: white;
            color: var(--purple-color);
            border: 2px solid var(--purple-color);
        }
        
        .recommended-btn-view:hover {
            background: var(--purple-color);
            color: white;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .modal-action-btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: block;
        }
        
        .modal-btn-cart {
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
            color: white;
        }
        
        .modal-btn-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(72, 54, 112, 0.3);
            color: white;
        }
        
        .modal-btn-continue {
            background: white;
            color: var(--gold-color);
            border: 2px solid var(--gold-color);
        }
        
        .modal-btn-continue:hover {
            background: var(--gold-color);
            color: white;
        }
        
        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: white;
            border: 2px solid var(--gold-color);
            border-radius: 12px;
            padding: 0.5rem 1rem;
            width: 100%;
        }
        
        .quantity-btn {
            width: 45px;
            height: 45px;
            border: 2px solid #000;
            background: transparent;
            color: #000;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .quantity-btn:hover {
            transform: scale(1.1);
            background: rgba(0, 0, 0, 0.1);
        }
        
        .quantity-btn:active {
            transform: scale(0.95);
        }
        
        .quantity-btn.decrease {
            border-color: #000;
            color: #000;
        }
        
        .quantity-btn.decrease:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .quantity-btn.increase {
            border-color: #000;
            color: #000;
        }
        
        .quantity-btn.increase:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .quantity-value {
            flex-grow: 1;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--purple-color);
            min-width: 50px;
        }
        
        .quantity-label {
            font-size: 0.9rem;
            color: #666;
            margin-right: auto;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .product-main {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem;
            }
            
            .product-image-large {
                height: 300px;
                font-size: 6rem;
            }
            
            .thumbnail-gallery {
                gap: 8px;
            }
            
            .thumb-item {
                flex: 0 0 55px;
                width: 55px;
                height: 55px;
            }
            
            .lightbox-nav {
                width: 40px;
                height: 40px;
                font-size: 1.4rem;
            }
            
            .lightbox-prev { left: 10px; }
            .lightbox-next { right: 10px; }
            
            .zoom-hint { display: none; }
            
            .product-title-en {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .cart-modal {
                max-width: 100%;
                margin: 0.5rem;
            }
            
            .cart-modal-body {
                padding: 1rem;
            }
            
            .added-product-content {
                flex-direction: column;
                text-align: center;
            }
            
            .recommended-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .modal-actions {
                flex-direction: column;
            }
        }

        /* OPTION 3: MODERN CARD STYLE - PHONE VIEW ONLY */
        @media (max-width: 640px) {
            /* Hide non-essential elements */
            .breadcrumb, 
            .breadcrumb-item,
            .thumbnail-gallery,
            .carousel-indicators,
            .zoom-hint,
            .product-tabs,
            .reviews-section,
            .recommended-section,
            .product-description {
                display: none !important;
            }

            /* Container styling with modern card approach */
            body {
                background: linear-gradient(135deg, #f8f6f3, #fff5f0);
            }

            .product-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(72, 54, 112, 0.12);
                margin: 16px 12px 0 12px;
                border: 1px solid rgba(201, 168, 106, 0.2);
            }

            .product-main {
                display: block;
                gap: 0;
                padding: 0;
                margin: 0;
            }

            .container {
                padding: 0;
                max-width: 100%;
            }

            .py-5 {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }

            .card-ramadan {
                background: transparent;
                border: none;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }

            /* Product Image - Card style, rounded top */
            .product-image-large {
                height: 300px;
                width: 100%;
                margin: 0;
                padding: 0;
                border-radius: 16px 16px 0 0;
                font-size: 5rem;
                border: none;
            }

            /* Product Details Section */
            .col-md-6:last-child {
                padding: 18px 18px !important;
            }

            /* Product Title - Elegant */
            .product-title-en {
                font-size: 1.3rem;
                font-weight: 700;
                color: #2d1b2c;
                margin: 0 0 2px 0;
                line-height: 1.2;
                letter-spacing: -0.3px;
            }

            .product-title-ar {
                font-size: 1.2rem;
                color: #2d1b2c;
                margin: 0 0 12px 0;
                font-weight: 600;
            }

            /* Rating - Badge style */
            .rating-section {
                margin: 12px 0;
                font-size: 0.9rem;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: linear-gradient(135deg, #fff5e6, #fffbf0);
                padding: 8px 12px;
                border-radius: 20px;
                border: 1px solid rgba(201, 168, 106, 0.3);
            }

            .stars {
                font-size: 1.1rem;
                margin: 0;
                color: #ffc107;
            }

            .rating-text {
                font-size: 0.8rem;
                color: #666;
                font-weight: 500;
            }

            /* Hide short description and brand */
            p[style*="italic"],
            .product-details > div[style*="brand"] {
                display: none !important;
            }

            /* Price Section - Highlight Card */
            .price-section {
                background: linear-gradient(135deg, rgba(45, 19, 44, 0.05), rgba(201, 168, 106, 0.1));
                padding: 14px 14px;
                margin: 12px 0;
                border-radius: 12px;
                border: 1.5px solid rgba(45, 19, 44, 0.15);
                position: relative;
            }

            .discount-badge-detail {
                background: linear-gradient(135deg, #ff5555, #ff3333);
                color: white;
                padding: 5px 12px;
                border-radius: 8px;
                font-size: 0.75rem;
                font-weight: 700;
                margin-bottom: 8px;
                display: inline-block;
                box-shadow: 0 4px 12px rgba(255, 51, 51, 0.3);
            }

            .product-price {
                font-size: 1.5rem;
                color: #2d1b2c;
                font-weight: 700;
                margin-bottom: 4px;
            }

            .original-price-detail {
                font-size: 0.85rem;
                color: #999;
                text-decoration: line-through;
                margin-right: 8px;
            }

            .discounted-price-detail {
                font-size: 1.5rem;
                font-weight: 700;
                color: #2d1b2c;
            }

            .savings-amount {
                font-size: 0.95rem;
                color: #ff3333;
                font-weight: 700;
                margin-top: 6px;
            }

            /* Stock Status - Styled Badge */
            .product-stock {
                font-size: 0.85rem;
                margin: 12px 0 0 0;
            }

            .stock-badge {
                padding: 6px 12px;
                border-radius: 8px;
                font-size: 0.8rem;
                font-weight: 600;
                display: inline-block;
            }

            .in-stock {
                background: linear-gradient(135deg, #d4edda, #c3e6cb);
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            /* Show badges for features */
            .product-details > div[style*="tag"] {
                display: block !important;
                margin: 12px 0 !important;
            }

            .product-details > div[style*="tag"] a {
                display: inline-block;
                background: linear-gradient(135deg, #f0e6f6, #e8d5f5);
                color: #2d1b2c;
                padding: 5px 10px;
                border-radius: 6px;
                font-size: 0.75rem;
                margin-right: 6px;
                margin-bottom: 6px;
                border: 1px solid #d4b5e8;
                text-decoration: none;
                font-weight: 600;
            }

            /* Quantity Controls - Card style */
            .quantity-controls {
                width: 100%;
                padding: 12px;
                border: 2px solid var(--gold-color);
                border-radius: 10px;
                gap: 10px;
                background: linear-gradient(135deg, rgba(201, 168, 106, 0.08), rgba(201, 168, 106, 0.04));
                margin: 14px 0;
            }

            .quantity-btn {
                width: 42px;
                height: 42px;
                border: 2px solid var(--purple-color);
                background: white;
                color: var(--purple-color);
                border-radius: 8px;
                font-size: 1.2rem;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .quantity-btn:active {
                background: rgba(201, 168, 106, 0.15);
                transform: scale(0.95);
            }

            .quantity-value {
                flex-grow: 1;
                text-align: center;
                font-size: 1.3rem;
                font-weight: 700;
                color: var(--purple-color);
            }

            .quantity-label {
                font-size: 0.75rem;
                color: #999;
                margin-right: 0;
            }

            /* Action Buttons - Elegant */
            .d-flex.gap-2 {
                display: flex !important;
                flex-direction: column;
                gap: 8px !important;
                margin-top: 14px;
                padding: 0 0 14px 0;
            }

            .btn-ramadan,
            .btn-ramadan-secondary,
            a.btn-ramadan,
            a.btn-ramadan-secondary {
                width: 100%;
                padding: 13px 16px !important;
                font-size: 0.98rem !important;
                font-weight: 700;
                border-radius: 10px;
                border: none;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: block;
                text-align: center;
                letter-spacing: 0.5px;
            }

            .btn-ramadan,
            a.btn-ramadan {
                background: linear-gradient(135deg, var(--purple-color), var(--purple-dark)) !important;
                color: white !important;
                box-shadow: 0 6px 20px rgba(45, 19, 44, 0.25);
            }

            .btn-ramadan:active,
            a.btn-ramadan:active {
                transform: translateY(2px);
                box-shadow: 0 3px 10px rgba(45, 19, 44, 0.15);
            }

            .btn-ramadan-secondary,
            a.btn-ramadan-secondary {
                background: white;
                color: var(--gold-color);
                border: 2px solid var(--gold-color) !important;
                box-shadow: 0 4px 12px rgba(201, 168, 106, 0.15);
            }

            .btn-ramadan-secondary:active,
            a.btn-ramadan-secondary:active {
                background: linear-gradient(135deg, #fff9f0, #fffbf5);
                box-shadow: 0 2px 6px rgba(201, 168, 106, 0.1);
            }

            /* Cart Modal on Mobile */
            .cart-modal {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0;
                border-radius: 20px 20px 0 0;
                max-height: 85vh;
            }

            .cart-modal-header {
                background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));
                padding: 14px 16px;
                border-radius: 20px 20px 0 0;
            }

            .cart-modal-body {
                padding: 14px 16px;
            }

            .added-product-content {
                flex-direction: column;
                text-align: center;
            }

            .added-product-image {
                width: 90px;
                height: 90px;
                font-size: 2.8rem;
                margin: 0 auto 12px;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(72, 54, 112, 0.2);
            }

            /* Lightbox optimized */
            .image-lightbox {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.96);
                z-index: 10000;
                align-items: center;
                justify-content: center;
            }

            .lightbox-img {
                max-width: 95vw;
                max-height: 80vh;
                object-fit: contain;
                border-radius: 12px;
            }

            .lightbox-close {
                top: 12px;
                right: 12px;
                width: 40px;
                height: 40px;
                font-size: 2rem;
                background: rgba(255, 255, 255, 0.15);
            }

            .lightbox-nav {
                display: none;
            }

            /* Page container cleanup */
            .page-container {
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #f8f6f3, #fff5f0);
            }

            .mb-3, .mb-4 {
                margin-bottom: 8px !important;
            }
        }

        /* Sticky Mini Video Player */
        .video-mini-player {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 320px;
            z-index: 9999;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(20px) scale(0.9);
            pointer-events: none;
            background: #000;
        }
        .video-mini-player.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: all;
        }
        .video-mini-player video {
            width: 100%;
            display: block;
        }
        .video-mini-player .mini-close {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: background 0.2s;
        }
        .video-mini-player .mini-close:hover {
            background: rgba(200,0,0,0.8);
        }
        .video-mini-player .mini-back {
            position: absolute;
            bottom: 8px;
            left: 8px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            padding: 4px 10px;
            z-index: 10;
            transition: background 0.2s;
        }
        .video-mini-player .mini-back:hover {
            background: var(--purple-color);
        }

        /* RTL adjustment */
        [dir="rtl"] .video-mini-player {
            right: auto;
            left: 20px;
        }
        [dir="rtl"] .video-mini-player .mini-close {
            right: auto;
            left: 8px;
        }
        [dir="rtl"] .video-mini-player .mini-back {
            left: auto;
            right: 8px;
        }

        @media (max-width: 576px) {
            .video-mini-player {
                width: 220px;
                bottom: 12px;
                right: 12px;
            }
            [dir="rtl"] .video-mini-player {
                right: auto;
                left: 12px;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
    <div class="container py-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="background: transparent; padding: 0; margin: 0;">
                <li class="breadcrumb-item">
                    <a href="<?= $base_url ?>/" style="color: var(--gold-color); text-decoration: none;">
                        <i class="fas fa-home me-1"></i><?= $current_lang === 'ar' ? 'الرئيسية' : 'Home' ?>
                    </a>
                </li>
                <?php if (!empty($product['category_en'])): ?>
                <li class="breadcrumb-item">
                    <a href="<?= $base_url ?>/index.php#products" style="color: var(--gold-color); text-decoration: none;">
                        <?= $current_lang === 'ar' ? htmlspecialchars($product['category_ar']) : htmlspecialchars($product['category_en']) ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (!empty($product['subcategory_en'])): ?>
                <li class="breadcrumb-item">
                    <a href="<?= $base_url ?>/index.php?subcategory=<?= $product['subcategory_id'] ?>#products" style="color: var(--gold-color); text-decoration: none;">
                        <?= $current_lang === 'ar' ? htmlspecialchars($product['subcategory_ar']) : htmlspecialchars($product['subcategory_en']) ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" style="color: var(--deep-purple);">
                    <?= htmlspecialchars(mb_strimwidth($product['name_en'], 0, 40, '...')) ?>
                </li>
            </ol>
        </nav>
        
        <!-- Product Details -->
        <div class="card-ramadan p-4">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="product-image-large" id="productCarousel">
                        <?php 
                        require_once __DIR__ . '/../../includes/product_image_helper.php';
                        
                        $images_dir = __DIR__ . '/../../images';
                        $gallery_images = get_product_gallery_images(
                            trim($product['name_en']),
                            $product['image_link'] ?? '',
                            $images_dir,
                            $base_url . '/'
                        );
                        
                        if (!empty($gallery_images)) {
                            foreach ($gallery_images as $index => $image_path) {
                                $activeClass = $index === 0 ? ' active' : '';
                                echo "<div class='carousel-slide$activeClass' style='background: #f5f5f5; display: flex; align-items: center; justify-content: center;'>";
                                echo "<img src='" . htmlspecialchars($image_path) . "' alt='" . htmlspecialchars($product['name_en']) . "' style='max-width: 100%; max-height: 100%; object-fit: contain; padding: 20px;' loading='lazy' onerror=\"this.onerror=null; this.src='" . $base_url . "/images/placeholder-cosmetics.svg';\">";
                                echo "</div>";
                            }
                        } else {
                            // Last fallback: emoji icons
                            $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎'];
                            $gradients = [
                                'linear-gradient(135deg, var(--purple-color), var(--purple-dark))',
                                'linear-gradient(135deg, var(--gold-color), var(--gold-light))',
                                'linear-gradient(135deg, #f093fb, #f5576c)',
                                'linear-gradient(135deg, #4facfe, #00f2fe)',
                                'linear-gradient(135deg, #43e97b, #38f9d7)'
                            ];
                            for ($i = 0; $i < 5; $i++) {
                                $activeClass = $i === 0 ? ' active' : '';
                                $icon = $icons[($product['id'] + $i) % count($icons)];
                                $gradient = $gradients[$i % count($gradients)];
                                echo "<div class='carousel-slide$activeClass' style='background: $gradient'>$icon</div>";
                            }
                            $gallery_images = range(1, 5);
                        }
                        
                        $total_slides = count($gallery_images);
                        ?>
                        <div class="carousel-indicators">
                            <?php for ($i = 0; $i < $total_slides; $i++): ?>
                                <div class="indicator<?= $i === 0 ? ' active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></div>
                            <?php endfor; ?>
                        </div>
                        <div class="zoom-hint"><i class="fas fa-search-plus"></i> <?= $current_lang === 'ar' ? 'انقر للتكبير' : 'Click to zoom' ?></div>
                    </div>
                    
                    <!-- Thumbnail Gallery -->
                    <div class="thumbnail-gallery">
                        <?php 
                        if (!empty($gallery_images) && !is_array($gallery_images[0] ?? null) && !is_numeric($gallery_images[0] ?? null)):
                            foreach ($gallery_images as $index => $image_path): 
                        ?>
                            <div class="thumb-item<?= $index === 0 ? ' active' : '' ?>" onclick="goToSlide(<?= $index ?>)">
                                <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($product['name_en']) ?> - <?= $index + 1 ?>" loading="lazy" onerror="this.onerror=null; this.src='<?= $base_url ?>/images/placeholder-cosmetics.svg';">
                            </div>
                        <?php 
                            endforeach;
                        else:
                            // Emoji fallback thumbnails
                            $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎'];
                            for ($i = 0; $i < $total_slides; $i++):
                                $icon = $icons[($product['id'] + $i) % count($icons)];
                        ?>
                            <div class="thumb-item<?= $i === 0 ? ' active' : '' ?>" onclick="goToSlide(<?= $i ?>)">
                                <span class="thumb-emoji"><?= $icon ?></span>
                            </div>
                        <?php 
                            endfor;
                        endif;
                        ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h1 style="color: var(--purple-color); font-family: <?= $current_lang === 'ar' ? "'Tajawal', sans-serif" : "'Playfair Display', serif" ?>; font-size: 2rem; margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($current_lang === 'ar' ? $product['name_ar'] : $product['name_en']) ?>
                    </h1>
                    
                    <?php if (!empty($product['short_description_en']) || !empty($product['short_description_ar'])): ?>
                    <p style="color: #666; font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem; font-style: italic;">
                        <?php if ($current_lang === 'ar' && !empty($product['short_description_ar'])): ?>
                            <?= htmlspecialchars($product['short_description_ar']) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($product['short_description_en'] ?? '') ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($review_count > 0): ?>
                    <div class="rating-section">
                        <div class="stars">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $average_rating ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <span class="rating-text"><?= $average_rating ?> <?= t('out_of_5') ?> (<?= $review_count ?> <?= t('reviews_count') ?>)</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <?php if ($product['has_discount'] && $product['original_price'] > 0): ?>
                            <div class="mb-2">
                                <span class="badge" style="background: var(--gold-color); color: var(--purple-dark); padding: 0.5rem 1rem; font-size: 1rem;">
                                    <i class="fas fa-tag me-1"></i>-<?= number_format($product['discount_percentage'], 0) ?>% OFF
                                </span>
                            </div>
                            <div class="mb-2">
                                <span style="text-decoration: line-through; color: #999; font-size: 1.2rem; margin-right: 1rem;">
                                    <?= formatJOD($product['original_price']) ?>
                                </span>
                                <span style="color: var(--purple-color); font-size: 1.8rem; font-weight: bold;">
                                    <?= $product['price_formatted'] ?>
                                </span>
                            </div>
                            <div style="color: var(--gold-color); font-weight: 600;">
                                💰 <?= t('you_save') ?> <?= formatJOD($product['original_price'] - $product['price_jod']) ?>!
                            </div>
                        <?php else: ?>
                            <div style="color: var(--purple-color); font-size: 1.8rem; font-weight: bold;">
                                <?= $product['price_formatted'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <?php if ($product['in_stock']): ?>
                            <span class="badge" style="background: #d4edda; color: #155724; padding: 0.6rem 1rem; font-size: 0.95rem;">
                                <i class="fas fa-check-circle me-1"></i><?= t('in_stock') ?><?php if (isAdmin()): ?>: <?= $product['stock_quantity'] ?> <?= t('units_available') ?><?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge" style="background: #f8d7da; color: #721c24; padding: 0.6rem 1rem; font-size: 0.95rem;">
                                <i class="fas fa-times-circle me-1"></i><?= t('out_of_stock') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($product['brand_en'])): ?>
                    <div class="mb-3">
                        <span style="color: #555; font-size: 0.95rem;">
                            <i class="fas fa-building me-1" style="color: var(--gold-color);"></i>
                            <strong><?= $current_lang === 'ar' ? 'العلامة التجارية' : 'Brand' ?>:</strong>
                            <?= htmlspecialchars($current_lang === 'ar' ? ($product['brand_ar'] ?: $product['brand_en']) : $product['brand_en']) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product_tags)): ?>
                    <div class="mb-3">
                        <?php foreach ($product_tags as $ptag): ?>
                            <a href="<?= $base_url ?>/index.php?tag=<?= urlencode($ptag['slug']) ?>" 
                               style="display: inline-block; background: linear-gradient(135deg, #f0e6f6, #e8d5f5); color: var(--purple-color); 
                                      padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; margin: 0.15rem; 
                                      text-decoration: none; transition: all 0.2s; border: 1px solid #d4b5e8;">
                                <i class="fas fa-tag me-1" style="font-size: 0.75rem;"></i><?= htmlspecialchars($current_lang === 'ar' ? ($ptag['name_ar'] ?: $ptag['name_en']) : $ptag['name_en']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2">
                        <?php if ($is_logged_in): ?>
                            <?php if ($product_in_cart_quantity > 0): ?>
                                <!-- Quantity Controls (shown when product is in cart) -->
                                <div class="quantity-controls flex-grow-1" id="quantityControls">
                                    <button class="quantity-btn decrease" onclick="updateQuantity(<?= $product['id'] ?>, 'decrease')" title="<?= t('decrease_quantity') ?>">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <div style="display: flex; flex-direction: column; align-items: center; flex-grow: 1;">
                                        <span class="quantity-label"><?= t('in_cart_label') ?></span>
                                        <span class="quantity-value" id="cartQuantity"><?= $product_in_cart_quantity ?></span>
                                    </div>
                                    <button class="quantity-btn increase" onclick="updateQuantity(<?= $product['id'] ?>, 'increase')" title="<?= t('increase_quantity') ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Add to Cart Button (shown when product not in cart) -->
                                <button 
                                    id="addToCartBtn"
                                    class="btn-ramadan flex-grow-1" 
                                    onclick="addToCart(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name_en']) ?>')"
                                    <?= !$product['in_stock'] ? 'disabled' : '' ?>
                                    style="<?= !$product['in_stock'] ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>"
                                >
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    <?= $product['in_stock'] ? t('add_to_cart') : t('out_of_stock') ?>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?= $base_url ?>/pages/auth/signin.php" class="btn-ramadan flex-grow-1" style="text-decoration: none; display: block; text-align: center;">
                                <i class="fas fa-sign-in-alt me-2"></i><?= t('sign_in_purchase') ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?= $base_url ?>/" class="btn-ramadan-secondary" style="text-decoration: none; padding: 0.75rem 1.5rem;">
                            <i class="fas fa-shopping-bag me-2"></i><?= t('shop_button') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Information Tabs -->
        <div class="card-ramadan p-0 mt-4 product-tabs">
            <ul class="nav-tabs-product nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-link active" id="see-in-action-tab-btn" data-bs-toggle="tab" data-bs-target="#see-in-action-tab" type="button" role="tab" aria-controls="see-in-action-tab" aria-selected="true">
                        <i class="fas fa-play-circle me-2"></i><?= t('see_in_action') ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-link" id="details-tab-btn" data-bs-toggle="tab" data-bs-target="#details-tab" type="button" role="tab" aria-controls="details-tab" aria-selected="false">
                        <i class="fas fa-info-circle me-2"></i><?= t('details') ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-link" id="description-tab-btn" data-bs-toggle="tab" data-bs-target="#description-tab" type="button" role="tab" aria-controls="description-tab" aria-selected="false">
                        <i class="fas fa-align-left me-2"></i><?= t('description') ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-link" id="howto-tab-btn" data-bs-toggle="tab" data-bs-target="#howto-tab" type="button" role="tab" aria-controls="howto-tab" aria-selected="false">
                        <i class="fas fa-book-open me-2"></i><?= t('how_to_use') ?>
                    </button>
                </li>
            </ul>
            
            <div class="tab-content tab-content-product" id="productTabContent">
                <!-- See in Action Tab -->
                <div id="see-in-action-tab" class="tab-pane fade show active" role="tabpanel" aria-labelledby="see-in-action-tab-btn">
                    <h4><i class="fas fa-play-circle me-2" style="color: var(--gold-color);"></i><?= t('see_in_action') ?></h4>
                    <div class="video-container" id="videoContainer" style="margin-top: 1.5rem;">
                        <?php if (!empty($product['video_review_url'])): ?>
                            <?php
                            $video_src = $product['video_review_url'];
                            $is_local = (strpos($video_src, 'uploads/') === 0 || strpos($video_src, '/uploads/') === 0);
                            ?>
                            <?php if ($is_local): ?>
                                <div class="video-wrapper" style="border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <video id="productVideo" controls playsinline preload="metadata"
                                        style="width: 100%; max-height: 500px; display: block; background: #000;"
                                        poster="">
                                        <source src="<?= $base_url . '/' . htmlspecialchars($video_src) ?>" type="video/mp4">
                                        <?= $current_lang === 'ar' ? 'متصفحك لا يدعم تشغيل الفيديو' : 'Your browser does not support the video tag.' ?>
                                    </video>
                                </div>
                            <?php else: ?>
                                <div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <iframe 
                                        src="<?= htmlspecialchars($video_src) ?>" 
                                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                                        allowfullscreen
                                        loading="lazy"
                                    ></iframe>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-video-placeholder" style="text-align: center; padding: 3rem 2rem; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; border: 2px dashed #dee2e6;">
                                <i class="fas fa-play-circle" style="font-size: 4rem; color: #adb5bd; margin-bottom: 1rem;"></i>
                                <p style="color: #6c757d; font-size: 1.1rem; margin: 0;">
                                    <?= $current_lang === 'ar' ? 'سيتم إضافة الفيديو قريباً' : 'Video will be added soon' ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Details Tab -->
                <div id="details-tab" class="tab-pane fade" role="tabpanel" aria-labelledby="details-tab-btn">
                    <h4><i class="fas fa-cube me-2" style="color: var(--gold-color);"></i><?= t('product_details') ?></h4>
                    <?php
                        // Pick correct language for product details
                        if ($current_lang === 'ar' && !empty($product['product_details_ar'])) {
                            $details_content = $product['product_details_ar'];
                        } elseif (!empty($product['product_details'])) {
                            $details_content = $product['product_details'];
                        } else {
                            $details_content = '';
                        }
                    ?>
                    <?php if (!empty($details_content)): ?>
                        <div style="line-height: 1.8;">
                            <?= nl2br(htmlspecialchars($details_content)) ?>
                        </div>
                    <?php else: ?>
                        <div style="line-height: 1.8;">
                            <ul>
                                <li><strong><?= t('product_name') ?>:</strong> <?= htmlspecialchars($current_lang === 'ar' ? $product['name_ar'] : $product['name_en']) ?></li>
                                <li><strong><?= t('brand') ?>:</strong> <?= htmlspecialchars($current_lang === 'ar' ? ($product['brand_ar'] ?? 'Poshy Store') : ($product['brand_en'] ?? 'Poshy Store')) ?></li>
                                <li><strong><?= t('category') ?>:</strong> <?= htmlspecialchars($current_lang === 'ar' ? ($product['category_name_ar'] ?? 'مستحضرات التجميل') : ($product['category_name_en'] ?? 'Cosmetics')) ?></li>
                                <li><strong><?= t('price') ?>:</strong> <?= $product['price_formatted'] ?? number_format($product['price_jod'], 3) . ' JOD' ?></li>
                                <li><strong><?= t('stock_status') ?>:</strong> <?= $product['in_stock'] ? t('in_stock') : t('out_of_stock') ?></li>
                                <?php if ($product['in_stock'] && isAdmin()): ?>
                                <li><strong><?= t('available_units') ?>:</strong> <?= $product['stock_quantity'] ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Description Tab -->
                <div id="description-tab" class="tab-pane fade" role="tabpanel" aria-labelledby="description-tab-btn">
                    <h4><i class="fas fa-file-alt me-2" style="color: var(--gold-color);"></i><?= t('product_description') ?></h4>
                    <?php
                        // Pick correct language for description
                        if ($current_lang === 'ar' && !empty($product['description_ar'])) {
                            $desc_content = $product['description_ar'];
                        } elseif (!empty($product['description'])) {
                            $desc_content = $product['description'];
                        } else {
                            $desc_content = '';
                        }
                    ?>
                    <?php if (!empty($desc_content)): ?>
                        <div style="line-height: 1.8;">
                            <?= nl2br(htmlspecialchars($desc_content)) ?>
                        </div>
                    <?php else: ?>
                        <p><?= t('default_description') ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- How to Use Tab -->
                <div id="howto-tab" class="tab-pane fade" role="tabpanel" aria-labelledby="howto-tab-btn">
                    <h4><i class="fas fa-hand-sparkles me-2" style="color: var(--gold-color);"></i><?= t('how_to_use') ?></h4>
                    <?php 
                        // Determine which how_to_use to display based on language
                        $how_to_use_content = '';
                        if ($current_lang === 'ar' && !empty($product['how_to_use_ar'])) {
                            $how_to_use_content = $product['how_to_use_ar'];
                        } elseif (!empty($product['how_to_use_en'])) {
                            $how_to_use_content = $product['how_to_use_en'];
                        } elseif (!empty($product['how_to_use'])) {
                            // Fallback to old how_to_use column if bilingual columns are empty
                            $how_to_use_content = $product['how_to_use'];
                        }
                    ?>
                    <?php if (!empty($how_to_use_content)): ?>
                        <div style="line-height: 1.8;">
                            <?= nl2br(htmlspecialchars($how_to_use_content)) ?>
                        </div>
                    <?php else: ?>
                        <div style="line-height: 1.8;">
                            <ol>
                                <li><?= t('howto_step1') ?></li>
                                <li><?= t('howto_step2') ?></li>
                                <li><?= t('howto_step3') ?></li>
                                <li><?= t('howto_step4') ?></li>
                                <li><?= t('howto_step5') ?></li>
                            </ol>
                            <p style="margin-top: 1rem; color: #666; font-style: italic;">
                                <i class="fas fa-info-circle me-1"></i><?= t('refer_packaging') ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="card-ramadan p-4 mt-4">
            <h3 class="section-title-ramadan mb-4">
                <i class="fas fa-star me-2"></i><?= t('customer_reviews') ?>
            </h3>
            
            <!-- Review Form (only if logged in) -->
            <?php if ($is_logged_in): ?>
            <div class="review-form">
                <h3><?= $user_review ? t('update_review') : t('write_review') ?></h3>
                <form id="reviewForm">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    
                    <div class="form-group">
                        <label><?= t('rating_label') ?></label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" 
                                    <?= $user_review && $user_review['rating'] == $i ? 'checked' : '' ?>
                                    <?= !$user_review && $i == 5 ? 'checked' : '' ?>>
                                <label for="star<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_text"><?= t('your_feedback') ?></label>
                        <textarea 
                            id="review_text" 
                            name="review_text" 
                            placeholder="<?= t('share_experience') ?>"
                            required
                        ><?= $user_review ? htmlspecialchars($user_review['review_text']) : '' ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-ramadan w-100">
                        <i class="fas fa-paper-plane me-2"></i><?= $user_review ? t('update_review') : t('submit_review_btn') ?>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="review-form">
                <p style="text-align: center; font-size: 1.1rem; color: #666;">
                    <a href="<?= $base_url ?>/pages/auth/signin.php" style="color: var(--gold-color); text-decoration: none; font-weight: bold;"><?= t('login') ?></a> <?= t('sign_in_to_review') ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <div class="no-reviews"><?= t('no_reviews_yet') ?></div>
                <?php else: ?>
                    <h3 style="margin-bottom: 1.5rem; color: #333; font-size: 1.3rem;"><?= t('recent_feedback') ?></h3>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-author"><?= htmlspecialchars($review['user_full_name']) ?></span>
                                <span class="review-date"><?= date('F j, Y', strtotime($review['created_at'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <div class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Carousel functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.indicator');
        const thumbs = document.querySelectorAll('.thumb-item');
        const totalSlides = slides.length;
        
        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(indicator => indicator.classList.remove('active'));
            thumbs.forEach(thumb => thumb.classList.remove('active'));
            
            currentSlide = (index + totalSlides) % totalSlides;
            slides[currentSlide].classList.add('active');
            indicators[currentSlide].classList.add('active');
            if (thumbs[currentSlide]) {
                thumbs[currentSlide].classList.add('active');
                // Scroll only the thumbnail strip, not the page
                const thumbContainer = thumbs[currentSlide].parentElement;
                if (thumbContainer) {
                    const thumbLeft = thumbs[currentSlide].offsetLeft;
                    const thumbWidth = thumbs[currentSlide].offsetWidth;
                    const containerWidth = thumbContainer.offsetWidth;
                    thumbContainer.scrollTo({
                        left: thumbLeft - (containerWidth / 2) + (thumbWidth / 2),
                        behavior: 'smooth'
                    });
                }
            }
        }
        
        function nextSlide() {
            showSlide(currentSlide + 1);
        }
        
        function goToSlide(index) {
            showSlide(index);
        }
        
        // Auto-advance carousel every 3 seconds (pause on lightbox or when not visible)
        let carouselTimer = setInterval(nextSlide, 3000);
        
        // Pause auto-advance when carousel is not visible (user scrolled past it)
        const carouselEl = document.getElementById('productCarousel');
        if (carouselEl && typeof IntersectionObserver !== 'undefined') {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        if (!carouselTimer) carouselTimer = setInterval(nextSlide, 3000);
                    } else {
                        if (carouselTimer) { clearInterval(carouselTimer); carouselTimer = null; }
                    }
                });
            }, { threshold: 0.1 });
            observer.observe(carouselEl);
        }
        
        // Click main image to open lightbox
        document.getElementById('productCarousel').addEventListener('click', function(e) {
            if (e.target.closest('.carousel-indicators') || e.target.closest('.indicator')) return;
            const activeSlide = document.querySelector('.carousel-slide.active');
            const img = activeSlide ? activeSlide.querySelector('img') : null;
            if (img) {
                openLightbox(img.src);
            }
        });
        
        // Lightbox functions
        let lightboxImages = [];
        let lightboxIndex = 0;
        
        (function() {
            slides.forEach(function(slide) {
                const img = slide.querySelector('img');
                if (img) lightboxImages.push(img.src);
            });
        })();
        
        function openLightbox(src) {
            const lb = document.getElementById('imageLightbox');
            const lbImg = document.getElementById('lightboxImg');
            lightboxIndex = lightboxImages.indexOf(src);
            if (lightboxIndex === -1) lightboxIndex = 0;
            lbImg.src = src;
            lbImg.alt = document.querySelector('.carousel-slide.active img')?.alt || '';
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';
            clearInterval(carouselTimer);
            updateLightboxCounter();
        }
        
        function closeLightbox(e) {
            if (e.target.closest('.lightbox-nav')) return;
            document.getElementById('imageLightbox').classList.remove('active');
            document.body.style.overflow = '';
            carouselTimer = setInterval(nextSlide, 3000);
        }
        
        function lightboxNav(e, direction) {
            e.stopPropagation();
            if (lightboxImages.length === 0) return;
            lightboxIndex = (lightboxIndex + direction + lightboxImages.length) % lightboxImages.length;
            document.getElementById('lightboxImg').src = lightboxImages[lightboxIndex];
            goToSlide(lightboxIndex);
            updateLightboxCounter();
        }
        
        function updateLightboxCounter() {
            const counter = document.getElementById('lightboxCounter');
            if (lightboxImages.length > 1) {
                counter.textContent = (lightboxIndex + 1) + ' / ' + lightboxImages.length;
            } else {
                counter.textContent = '';
            }
        }
        
        // Keyboard navigation for lightbox
        document.addEventListener('keydown', function(e) {
            const lb = document.getElementById('imageLightbox');
            if (!lb.classList.contains('active')) return;
            if (e.key === 'Escape') { closeLightbox(e); }
            else if (e.key === 'ArrowLeft') { lightboxNav(e, -1); }
            else if (e.key === 'ArrowRight') { lightboxNav(e, 1); }
        });
        
        // Tabs are now handled by Bootstrap 5 automatically with data-bs-toggle="tab"
        // No custom openTab function needed
        
        // Base URL for API calls (works from both direct and clean URL access)
        const BASE_URL = '<?= $base_url ?>';
        
        // Add to Cart
        function addToCart(productId, productName) {
            const btn = document.getElementById('addToCartBtn');
            
            // Disable button and show loading state
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            }
            
            fetch(BASE_URL + '/api/add_to_cart_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server error (HTTP ' + response.status + ')');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Replace Add to Cart button with quantity controls
                    replaceButtonWithQuantityControls(productId, 1);
                    
                    // Show cart modal
                    showCartModal(productId);
                } else {
                    // Show specific error message
                    showAlert('error', data.error || 'Failed to add to cart');
                    
                    // Reset button
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i><?= t("add_to_cart") ?>';
                    }
                }
            })
            .catch(error => {
                console.error('Add to cart error:', error);
                showAlert('error', error.message || 'Network error. Please try again.');
                
                // Reset button
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i><?= t("add_to_cart") ?>';
                }
            });
        }
        
        // Replace Add to Cart button with Quantity Controls
        function replaceButtonWithQuantityControls(productId, quantity) {
            const addToCartBtn = document.getElementById('addToCartBtn');
            if (addToCartBtn) {
                const quantityControls = document.createElement('div');
                quantityControls.className = 'quantity-controls flex-grow-1';
                quantityControls.id = 'quantityControls';
                quantityControls.innerHTML = `
                    <button class="quantity-btn decrease" onclick="updateQuantity(${productId}, 'decrease')" title="<?= t('decrease_quantity') ?>">
                        <i class="fas fa-minus"></i>
                    </button>
                    <div style="display: flex; flex-direction: column; align-items: center; flex-grow: 1;">
                        <span class="quantity-label"><?= t('in_cart_label') ?></span>
                        <span class="quantity-value" id="cartQuantity">${quantity}</span>
                    </div>
                    <button class="quantity-btn increase" onclick="updateQuantity(${productId}, 'increase')" title="<?= t('increase_quantity') ?>">
                        <i class="fas fa-plus"></i>
                    </button>
                `;
                addToCartBtn.parentNode.replaceChild(quantityControls, addToCartBtn);
            }
        }
        
        // Replace Quantity Controls with Add to Cart button
        function replaceQuantityControlsWithButton(productId, productName, inStock) {
            const quantityControls = document.getElementById('quantityControls');
            if (quantityControls) {
                const addToCartBtn = document.createElement('button');
                addToCartBtn.id = 'addToCartBtn';
                addToCartBtn.className = 'btn-ramadan flex-grow-1';
                addToCartBtn.onclick = function() { addToCart(productId, productName); };
                if (!inStock) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.style.opacity = '0.5';
                    addToCartBtn.style.cursor = 'not-allowed';
                }
                addToCartBtn.innerHTML = `
                    <i class="fas fa-shopping-cart me-2"></i>
                    ${inStock ? '<?= t("add_to_cart") ?>' : '<?= t("out_of_stock") ?>'}
                `;
                quantityControls.parentNode.replaceChild(addToCartBtn, quantityControls);
            }
        }
        
        // Update Cart Quantity
        function updateQuantity(productId, action) {
            fetch(BASE_URL + '/api/update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'removed') {
                        // Product removed from cart, show Add to Cart button
                        replaceQuantityControlsWithButton(productId, '', true);
                        showAlert('success', '<?= t("product_removed") ?>');
                    } else {
                        // Update quantity display
                        const quantityElement = document.getElementById('cartQuantity');
                        if (quantityElement) {
                            quantityElement.textContent = data.new_quantity;
                        }
                        
                        const message = data.action === 'increased' ? '<?= t("quantity_increased") ?>' : '<?= t("quantity_decreased") ?>';
                        showAlert('success', message);
                    }
                    
                    // Update cart count in navbar
                    updateNavbarCartCount();
                } else {
                    showAlert('error', data.error || '<?= t("failed_update_qty") ?>');
                }
            })
            .catch(error => {
                showAlert('error', '<?= t("network_error") ?>');
            });
        }
        
        // Update navbar cart count
        function updateNavbarCartCount() {
            // This will refresh the cart count badge in the navbar
            const cartBadges = document.querySelectorAll('.cart-count, .badge');
            // In a real implementation, you might fetch the new count via AJAX
            // For now, we'll just reload after a short delay to show the alert
            setTimeout(() => {
                // Optional: You can add an AJAX call here to update just the cart count
                // without reloading the entire page
            }, 500);
        }
        
        // Submit Review
        document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(BASE_URL + '/api/submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.error || '<?= t("failed_submit_review") ?>');
                }
            })
            .catch(error => {
                showAlert('error', '<?= t("network_error") ?>');
            });
        });
        
        // Show Alert
        function showAlert(type, message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
        // Cart Popup Modal Functions
        function showCartModal(productId) {
            // Fetch cart popup data
            fetch(`${BASE_URL}/api/get_cart_popup_data.php?product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateCartModal(data);
                        document.getElementById('cartModal').classList.add('active');
                        document.body.style.overflow = 'hidden';
                    } else {
                        showAlert('error', data.error || '<?= t("failed_add_to_cart") ?>');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', '<?= t("network_error") ?>');
                });
        }
        
        function populateCartModal(data) {
            const product = data.added_product;
            
            // Store product ID for quantity updates
            window.currentModalProductId = product.id;
            
            // Update added product section
            const addedImgEl = document.getElementById('addedProductImage');
            if (product.image_path) {
                addedImgEl.innerHTML = `<img src="${product.image_path}" alt="${product.name_en}" style="width:100%;height:100%;object-fit:contain;" onerror="this.onerror=null;this.parentElement.textContent='📦';">`;
            } else {
                addedImgEl.textContent = '📦';
            }
            document.getElementById('addedProductName').textContent = product.name_en;
            if (product.name_ar) {
                document.getElementById('addedProductName').innerHTML += ` <small style="color: #888;">(${product.name_ar})</small>`;
            }
            document.getElementById('addedProductPrice').textContent = product.price;
            document.getElementById('modalQuantityValue').textContent = product.quantity;
            
            // Update cart count
            document.getElementById('cartCountBadge').innerHTML = `<i class="fas fa-shopping-cart"></i> Total Items in Cart: ${data.cart_count}`;
            
            // Update recommended products
            const recommendedGrid = document.getElementById('recommendedGrid');
            recommendedGrid.innerHTML = '';
            
            if (data.recommended_products && data.recommended_products.length > 0) {
                data.recommended_products.forEach(rec => {
                    const recItem = document.createElement('div');
                    recItem.className = 'recommended-item';
                    
                    const priceHtml = rec.has_discount 
                        ? `<span class="recommended-original-price">${rec.price_formatted}</span><span class="recommended-item-price">${rec.discounted_price_formatted}</span>`
                        : `<div class="recommended-item-price">${rec.price_formatted}</div>`;
                    
                    const recImgSrc = rec.image_path ? rec.image_path : '/images/placeholder-cosmetics.svg';
                    recItem.innerHTML = `
                        <div class="recommended-item-image"><img src="${recImgSrc}" alt="${rec.name_en}" style="width:100%;height:100%;object-fit:contain;" onerror="this.onerror=null;this.parentElement.textContent='✨';"></div>
                        <div class="recommended-item-name">${rec.name_en}</div>
                        ${priceHtml}
                        <div class="recommended-item-actions">
                            <button class="recommended-btn recommended-btn-add" onclick="addRecommendedToCart(${rec.id}, '${rec.name_en.replace(/'/g, "\\'")}')">
                                <i class="fas fa-cart-plus"></i> <?= t('add_button') ?>
                            </button>
                            <a href="<?= BASE_PATH ?>/${rec.slug}" class="recommended-btn recommended-btn-view">
                                <i class="fas fa-eye"></i> <?= t('view_button') ?>
                            </a>
                        </div>
                    `;
                    
                    recommendedGrid.appendChild(recItem);
                });
            } else {
                recommendedGrid.innerHTML = '<div style="text-align: center; color: #666; padding: 2rem;"><?= t("no_recommendations") ?></div>';
            }
        }
        
        function closeCartModal() {
            document.getElementById('cartModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Update quantity in modal
        function updateModalQuantity(action) {
            const productId = window.currentModalProductId;
            if (!productId) return;
            
            fetch(BASE_URL + '/api/update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'removed') {
                        // Product removed, close modal and update page
                        closeCartModal();
                        showAlert('success', '<?= t("product_removed") ?>');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        // Update quantity display in modal
                        document.getElementById('modalQuantityValue').textContent = data.new_quantity;
                        
                        // Update main page quantity if it exists
                        const mainQuantity = document.getElementById('cartQuantity');
                        if (mainQuantity) {
                            mainQuantity.textContent = data.new_quantity;
                        }
                        
                        // Refresh cart data to update count
                        fetch(`${BASE_URL}/api/get_cart_popup_data.php?product_id=${productId}`)
                            .then(resp => resp.json())
                            .then(cartData => {
                                if (cartData.success) {
                                    document.getElementById('cartCountBadge').innerHTML = 
                                        `<i class="fas fa-shopping-cart"></i> <?= t('total_items_cart') ?>: ${cartData.cart_count}`;
                                }
                            });
                        
                        const message = action === 'increase' ? '<?= t("quantity_increased") ?>' : '<?= t("quantity_decreased") ?>';
                        showAlert('success', message);
                    }
                } else {
                    showAlert('error', data.error || '<?= t("failed_update_qty") ?>');
                }
            })
            .catch(error => {
                showAlert('error', '<?= t("network_error") ?>');
            });
        }
        
        function addRecommendedToCart(productId, productName) {
            fetch(BASE_URL + '/api/add_to_cart_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', productName + ' <?= t("added_to_cart_success") ?>');
                    // Refresh modal data
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('error', data.error || '<?= t("failed_add_to_cart") ?>');
                }
            })
            .catch(error => {
                showAlert('error', '<?= t("network_error") ?>');
            });
        }
    </script>
    
    <!-- Cart Popup Modal -->
    <div id="cartModal" class="cart-modal-overlay" onclick="if(event.target === this) closeCartModal()">
        <div class="cart-modal">
            <div class="cart-modal-header">
                <h3 class="cart-modal-title">
                    <i class="fas fa-check-circle"></i> <?= t('added_to_cart_modal') ?>
                </h3>
                <button class="cart-modal-close" onclick="closeCartModal()">&times;</button>
            </div>
            
            <div class="cart-modal-body">
                <!-- Added Product Section -->
                <div class="added-product-section">
                    <div class="added-product-content">
                        <div class="added-product-image" id="addedProductImage">📦</div>
                        <div class="added-product-info">
                            <div class="added-product-name" id="addedProductName"></div>
                            <div class="added-product-price" id="addedProductPrice"></div>
                            <div class="modal-quantity-controls">
                                <button class="modal-quantity-btn decrease" onclick="updateModalQuantity('decrease')" title="<?= t('decrease_quantity') ?>">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <div class="modal-quantity-display">
                                    <span class="modal-quantity-label"><?= t('quantity_in_cart') ?></span>
                                    <span class="modal-quantity-value" id="modalQuantityValue">1</span>
                                </div>
                                <button class="modal-quantity-btn increase" onclick="updateModalQuantity('increase')" title="<?= t('increase_quantity') ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="cart-count-badge" id="cartCountBadge">
                        <i class="fas fa-shopping-cart"></i> <?= t('total_items_cart') ?>: 0
                    </div>
                </div>
                
                <!-- Recommended Products Section -->
                <div class="recommended-section">
                    <h4 class="recommended-title">✨ <?= t('you_may_like') ?></h4>
                    <div class="recommended-grid" id="recommendedGrid">
                        <!-- Recommended products will be inserted here -->
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="modal-actions">
                    <a href="<?= $base_url ?>/pages/shop/cart.php" class="modal-action-btn modal-btn-cart">
                        <i class="fas fa-shopping-cart"></i> <?= t('go_to_cart') ?>
                    </a>
                    <button class="modal-action-btn modal-btn-continue" onclick="closeCartModal()">
                        <i class="fas fa-shopping-bag"></i> <?= t('continue_shopping_btn') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    
    <!-- Image Lightbox -->
    <div class="image-lightbox" id="imageLightbox" onclick="closeLightbox(event)">
        <div class="lightbox-close" onclick="closeLightbox(event)"><i class="fas fa-times"></i></div>
        <div class="lightbox-nav lightbox-prev" onclick="lightboxNav(event, -1)"><i class="fas fa-chevron-left"></i></div>
        <img class="lightbox-img" id="lightboxImg" src="" alt="">
        <div class="lightbox-nav lightbox-next" onclick="lightboxNav(event, 1)"><i class="fas fa-chevron-right"></i></div>
        <div class="lightbox-counter" id="lightboxCounter"></div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>

    <?php if (!empty($product['video_review_url']) && (strpos($product['video_review_url'], 'uploads/') === 0 || strpos($product['video_review_url'], '/uploads/') === 0)): ?>
    <!-- Sticky Mini Video Player -->
    <div class="video-mini-player" id="miniPlayer">
        <button class="mini-close" onclick="closeMiniPlayer()" title="Close">&times;</button>
        <button class="mini-back" onclick="scrollToVideo()"><?= $current_lang === 'ar' ? 'العودة للفيديو' : 'Back to video' ?></button>
        <video id="miniVideo" playsinline>
            <source src="<?= $base_url . '/' . htmlspecialchars($product['video_review_url']) ?>" type="video/mp4">
        </video>
    </div>
    <script>
    (function() {
        const mainVideo = document.getElementById('productVideo');
        const miniPlayer = document.getElementById('miniPlayer');
        const miniVideo = document.getElementById('miniVideo');
        const videoContainer = document.getElementById('videoContainer');
        if (!mainVideo || !miniPlayer || !miniVideo || !videoContainer) return;

        let miniClosed = false;
        let wasPlaying = false;

        function checkScroll() {
            if (miniClosed) return;
            const rect = videoContainer.getBoundingClientRect();
            const isOutOfView = rect.bottom < 0;
            
            if (isOutOfView && !mainVideo.paused) {
                // Video scrolled out of view while playing → show mini player
                if (!miniPlayer.classList.contains('visible')) {
                    miniVideo.currentTime = mainVideo.currentTime;
                    mainVideo.pause();
                    miniVideo.play();
                    miniPlayer.classList.add('visible');
                }
            } else if (!isOutOfView && miniPlayer.classList.contains('visible')) {
                // Scrolled back to video → restore
                mainVideo.currentTime = miniVideo.currentTime;
                miniVideo.pause();
                mainVideo.play();
                miniPlayer.classList.remove('visible');
            }
        }

        window.addEventListener('scroll', checkScroll, { passive: true });

        // Sync time when mini video ends
        miniVideo.addEventListener('ended', function() {
            mainVideo.currentTime = miniVideo.currentTime;
            miniPlayer.classList.remove('visible');
        });

        // Close mini player
        window.closeMiniPlayer = function() {
            miniVideo.pause();
            miniPlayer.classList.remove('visible');
            miniClosed = true;
            mainVideo.pause();
        };

        // Scroll back to the main video
        window.scrollToVideo = function() {
            miniVideo.pause();
            mainVideo.currentTime = miniVideo.currentTime;
            miniPlayer.classList.remove('visible');
            videoContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => mainVideo.play(), 500);
        };

        // Reset miniClosed when user manually plays the main video again
        mainVideo.addEventListener('play', function() {
            miniClosed = false;
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
