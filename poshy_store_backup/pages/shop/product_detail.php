<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/product_manager.php';
require_once __DIR__ . '/../../includes/cart_handler.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../../index.php');
    exit;
}

$product_id = (int)$_GET['id'];
$is_logged_in = isset($_SESSION['user_id']);

// Get product details
$product_result = getProductById($product_id);
if (!$product_result['success']) {
    header('Location: ../../index.php');
    exit;
}

$product = $product_result['product'];

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
if ($is_logged_in) {
    $cart_info = getCartCount($_SESSION['user_id']);
    $cart_count = $cart_info['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name_en']) ?> - Poshy Store</title>
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
            
            .product-title-en {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
    <div class="container py-5">
        <a href="../../index.php" class="mb-3 d-inline-block" style="color: var(--gold-color); text-decoration: none; font-weight: 600;">
            <i class="fas fa-arrow-left me-2"></i>Back to Products
        </a>
        
        <!-- Product Details -->
        <div class="card-ramadan p-4">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="product-image-large" id="productCarousel">
                        <?php 
                        $icons = ['💄', '💅', '🌹', '✨', '💫', '🌙', '⭐', '💎'];
                        $gradients = [
                            'linear-gradient(135deg, ' . 'var(--purple-color)' . ', ' . 'var(--purple-dark)' . ')',
                            'linear-gradient(135deg, ' . 'var(--gold-color)' . ', ' . 'var(--gold-light)' . ')',
                            'linear-gradient(135deg, #f093fb, #f5576c)',
                            'linear-gradient(135deg, #4facfe, #00f2fe)',
                            'linear-gradient(135deg, #43e97b, #38f9d7)'
                        ];
                        
                        $productIcons = [
                            $icons[$product['id'] % count($icons)],
                            $icons[($product['id'] + 1) % count($icons)],
                            $icons[($product['id'] + 2) % count($icons)],
                            $icons[($product['id'] + 3) % count($icons)],
                            $icons[($product['id'] + 4) % count($icons)]
                        ];
                        
                        foreach ($productIcons as $index => $icon) {
                            $activeClass = $index === 0 ? ' active' : '';
                            $gradient = $gradients[$index % count($gradients)];
                            echo "<div class='carousel-slide$activeClass' style='background: $gradient'>$icon</div>";
                        }
                        ?>
                        <div class="carousel-indicators">
                            <?php for ($i = 0; $i < count($productIcons); $i++): ?>
                                <div class="indicator<?= $i === 0 ? ' active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h1 style="color: var(--purple-color); font-family: 'Playfair Display', serif; font-size: 2rem; margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($product['name_en']) ?>
                    </h1>
                    <h2 style="color: var(--gold-color); font-family: 'Tajawal', sans-serif; font-size: 1.3rem; margin-bottom: 1.5rem;">
                        <?= htmlspecialchars($product['name_ar']) ?>
                    </h2>
                    
                    <?php if ($review_count > 0): ?>
                    <div class="rating-section">
                        <div class="stars">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $average_rating ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <span class="rating-text"><?= $average_rating ?> out of 5 (<?= $review_count ?> reviews)</span>
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
                                💰 You save <?= formatJOD($product['original_price'] - $product['price_jod']) ?>!
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
                                <i class="fas fa-check-circle me-1"></i>In Stock<?php if (isAdmin()): ?>: <?= $product['stock_quantity'] ?> units available<?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge" style="background: #f8d7da; color: #721c24; padding: 0.6rem 1rem; font-size: 0.95rem;">
                                <i class="fas fa-times-circle me-1"></i>Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-3 mb-4" style="background: rgba(201, 168, 106, 0.1); border-radius: 8px; border-left: 4px solid var(--gold-color);">
                        <strong style="color: var(--purple-color);">Description:</strong><br>
                        <span style="color: #555; line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($product['description'] ?? 'Premium quality cosmetics from Poshy Store collection.')) ?>
                        </span>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($is_logged_in): ?>
                            <button 
                                class="btn-ramadan flex-grow-1" 
                                onclick="addToCart(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name_en']) ?>')"
                                <?= !$product['in_stock'] ? 'disabled' : '' ?>
                                style="<?= !$product['in_stock'] ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>"
                            >
                                <i class="fas fa-shopping-cart me-2"></i>
                                <?= $product['in_stock'] ? 'Add to Cart' : 'Out of Stock' ?>
                            </button>
                        <?php else: ?>
                            <a href="../auth/signin.php" class="btn-ramadan flex-grow-1" style="text-decoration: none; display: block; text-align: center;">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign in to Purchase
                            </a>
                        <?php endif; ?>
                        <a href="../../index.php" class="btn-ramadan-secondary" style="text-decoration: none; padding: 0.75rem 1.5rem;">
                            <i class="fas fa-shopping-bag me-2"></i>Shop
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="card-ramadan p-4 mt-4">
            <h3 class="section-title-ramadan mb-4">
                <i class="fas fa-star me-2"></i>Customer Reviews
            </h3>
            
            <!-- Review Form (only if logged in) -->
            <?php if ($is_logged_in): ?>
            <div class="review-form">
                <h3><?= $user_review ? 'Update Your Review' : 'Write a Review' ?></h3>
                <form id="reviewForm">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    
                    <div class="form-group">
                        <label>Rating *</label>
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
                        <label for="review_text">Your Feedback *</label>
                        <textarea 
                            id="review_text" 
                            name="review_text" 
                            placeholder="Share your experience with this product..."
                            required
                        ><?= $user_review ? htmlspecialchars($user_review['review_text']) : '' ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-ramadan w-100">
                        <i class="fas fa-paper-plane me-2"></i><?= $user_review ? 'Update Review' : 'Submit Review' ?>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="review-form">
                <p style="text-align: center; font-size: 1.1rem; color: #666;">
                    <a href="../auth/signin.php" style="color: var(--gold-color); text-decoration: none; font-weight: bold;">Sign in</a> to write a review
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <div class="no-reviews">No reviews yet. Be the first to review this product!</div>
                <?php else: ?>
                    <h3 style="margin-bottom: 1.5rem; color: #333; font-size: 1.3rem;">Recent Feedback (Last 5 Reviews)</h3>
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
        const totalSlides = slides.length;
        
        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(indicator => indicator.classList.remove('active'));
            
            currentSlide = (index + totalSlides) % totalSlides;
            slides[currentSlide].classList.add('active');
            indicators[currentSlide].classList.add('active');
        }
        
        function nextSlide() {
            showSlide(currentSlide + 1);
        }
        
        function goToSlide(index) {
            showSlide(index);
        }
        
        // Auto-advance carousel every 3 seconds
        setInterval(nextSlide, 3000);
        
        // Add to Cart
        function addToCart(productId, productName) {
            fetch('../../api/add_to_cart_api.php', {
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
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('error', data.error || 'Failed to add to cart');
                }
            })
            .catch(error => {
                showAlert('error', 'Network error. Please try again.');
            });
        }
        
        // Submit Review
        document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../../api/submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.error || 'Failed to submit review');
                }
            })
            .catch(error => {
                showAlert('error', 'Network error. Please try again.');
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
    </script>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
