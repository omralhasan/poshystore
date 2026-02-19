<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Organization Test - Poshy Lifestyle</title>
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
        h1 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        .category-name {
            font-weight: bold;
            color: #333;
        }
        .category-ar {
            color: #666;
            font-size: 0.9rem;
            direction: rtl;
        }
        .product-list {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #555;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            margin-top: 1.5rem;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Category Organization Complete!</h1>
        
        <div class="success">
            <strong>Success!</strong> Your products are now organized into categories on the home page.
        </div>

        <h2>Categories Overview</h2>
        
        <?php
        require_once __DIR__ . '/db_connect.php';
        
        $sql = "SELECT c.id, c.name_en, c.name_ar, 
                GROUP_CONCAT(p.name_en SEPARATOR ', ') as products,
                COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.stock_quantity > 0
                GROUP BY c.id, c.name_en, c.name_ar
                HAVING product_count > 0
                ORDER BY c.id";
        
        $result = $conn->query($sql);
        ?>
        
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Products</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="category-name"><?= htmlspecialchars($row['name_en']) ?></div>
                        <div class="category-ar"><?= htmlspecialchars($row['name_ar']) ?></div>
                    </td>
                    <td>
                        <div class="product-list"><?= htmlspecialchars($row['products']) ?></div>
                    </td>
                    <td><strong><?= $row['product_count'] ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 style="margin-top: 2rem;">What's New on Home Page:</h3>
        <ul style="line-height: 1.8; margin-left: 1.5rem; margin-top: 1rem;">
            <li>✅ Products grouped by category</li>
            <li>✅ Category headers with English and Arabic names</li>
            <li>✅ Product count badge for each category</li>
            <li>✅ Beautiful gradient styling matching your brand</li>
            <li>✅ All existing features preserved (discounts, cart, etc.)</li>
        </ul>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e0e0e0; text-align: center;">
            <a href="index.php" class="btn">🏠 View Home Page</a>
            <a href="admin_panel.php" class="btn" style="margin-left: 1rem; background: #28a745;">⚙️ Admin Panel</a>
        </div>
    </div>
</body>
</html>
