<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Filter Test - Poshy Lifestyle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        h1 { color: #667eea; margin-bottom: 1rem; }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #28a745;
        }
        .feature {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .feature h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        .feature ul {
            margin-left: 1.5rem;
            line-height: 2;
        }
        .demo-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .demo-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: transform 0.3s;
            display: block;
        }
        .demo-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .demo-btn-all {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .screenshot {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Category Navigation Implemented!</h1>
        
        <div class="success">
            <strong>Success!</strong> Your home page now has a category navigation bar at the top.
            Click any category to filter products, or click "All Products" to see everything.
        </div>

        <div class="feature">
            <h3>🎯 How It Works:</h3>
            <ul>
                <li><strong>Default View:</strong> Shows all products grouped by category</li>
                <li><strong>Category Filter:</strong> Click a category to see only its products</li>
                <li><strong>All Products:</strong> Click "All Products" to return to full view</li>
                <li><strong>Active Indicator:</strong> Selected category is highlighted</li>
                <li><strong>Product Count:</strong> Each button shows number of products</li>
            </ul>
        </div>

        <div class="feature">
            <h3>✨ Features Added:</h3>
            <ul>
                <li>📂 Category navigation bar below hero section</li>
                <li>🏠 "All Products" button (green) to show everything</li>
                <li>🎨 Color-coded active states (purple gradient)</li>
                <li>📊 Product counts in each category button</li>
                <li>🔄 Dynamic header updates based on selected category</li>
                <li>📱 Responsive design with hover effects</li>
            </ul>
        </div>

        <h3>🧪 Try It Out:</h3>
        <div class="demo-links">
            <a href="index.php" class="demo-btn demo-btn-all">
                🏠 All Products
            </a>
            <?php
            require_once __DIR__ . '/db_connect.php';
            $cats_sql = "SELECT c.id, c.name_en FROM categories c 
                         WHERE (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.stock_quantity > 0) > 0 
                         ORDER BY c.id";
            $cats_result = $conn->query($cats_sql);
            while ($cat = $cats_result->fetch_assoc()):
            ?>
                <a href="index.php?category=<?= $cat['id'] ?>" class="demo-btn">
                    <?= htmlspecialchars($cat['name_en']) ?>
                </a>
            <?php endwhile; ?>
        </div>

        <div class="feature" style="margin-top: 2rem;">
            <h3>🔗 URL Structure:</h3>
            <div class="screenshot">
                • index.php → Shows all products<br>
                • index.php?category=1 → Shows Accessories only<br>
                • index.php?category=2 → Shows Bags & Luggage only<br>
                • index.php?category=3 → Shows Jewelry only<br>
                • And so on...
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e0e0e0; text-align: center;">
            <a href="index.php" class="demo-btn" style="max-width: 300px; margin: 0 auto;">
                🛍️ Go to Home Page
            </a>
        </div>
    </div>
</body>
</html>
