<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Images Download Guide</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .instructions h2 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .instructions ol {
            margin-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .product-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-number {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .product-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
            min-height: 40px;
        }
        
        .product-category {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }
        
        .product-filename {
            background: #f5f5f5;
            padding: 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85em;
            margin: 10px 0;
            word-break: break-all;
        }
        
        .download-btn {
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .download-btn:hover {
            transform: scale(1.05);
        }
        
        .progress {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }
        
        .progress h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Product Images Download Guide</h1>
        <p class="subtitle">Download and save images for 42 skincare products</p>
        
        <div class="instructions">
            <h2>📋 Instructions</h2>
            <ol>
                <li><strong>Click "Search Images"</strong> button for each product</li>
                <li>Google Image Search will open in a new tab</li>
                <li><strong>Right-click on the first product image</strong> and select "Save image as..."</li>
                <li><strong>Save the image</strong> to: <code>/var/www/html/poshy_store/images/products/</code></li>
                <li><strong>Use the exact filename shown</strong> in each card (e.g., <code>eqqual_berry_bakuchiol_plumping_serum.jpg</code>)</li>
                <li>Repeat for all 42 products</li>
            </ol>
            <p style="margin-top: 15px;"><strong>Tip:</strong> You can download multiple images at once by opening several search tabs, then saving each image with the correct filename.</p>
        </div>
        
        <div class="progress">
            <h3>Download Progress</h3>
            <p>Track your progress as you download images</p>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill">0 / 42</div>
            </div>
        </div>
        
        <div class="product-grid" id="productGrid">
            <!-- Products will be loaded here -->
        </div>
    </div>
    
    <script>
        // Product data from CSV
        const products = <?php
        $csv_file = __DIR__ . '/Store_Ready_42_Products_ShortDesc.csv';
        $products = [];
        
        if (file_exists($csv_file)) {
            $csv_data = array_map(function($line) {
                return str_getcsv($line);
            }, file($csv_file));
            
            // Remove header
            array_shift($csv_data);
            
            foreach ($csv_data as $index => $row) {
                if (count($row) >= 5) {
                    $product_name = trim($row[1]);
                    $image_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($product_name));
                    $image_filename = substr($image_filename, 0, 50) . '.jpg';
                    
                    $products[] = [
                        'number' => $index + 1,
                        'name' => trim($row[1]),
                        'category' => trim($row[0]),
                        'search_link' => trim($row[4]),
                        'filename' => $image_filename
                    ];
                }
            }
        }
        
        echo json_encode($products, JSON_UNESCAPED_UNICODE);
        ?>;
        
        let downloadedCount = 0;
        
        function renderProducts() {
            const grid = document.getElementById('productGrid');
            
            products.forEach(product => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <div class="product-number">${product.number}</div>
                    <div class="product-category">${product.category}</div>
                    <div class="product-name">${product.name}</div>
                    <div class="product-filename">📁 ${product.filename}</div>
                    <a href="${product.search_link}" target="_blank" class="download-btn" onclick="markDownloaded(this)">
                        🔍 Search Images
                    </a>
                `;
                grid.appendChild(card);
            });
        }
        
        function markDownloaded(button) {
            setTimeout(() => {
                if (!button.classList.contains('downloaded')) {
                    button.classList.add('downloaded');
                    button.style.background = '#4caf50';
                    button.innerHTML = '✓ Opened';
                    downloadedCount++;
                    updateProgress();
                }
            }, 500);
        }
        
        function updateProgress() {
            const progressFill = document.getElementById('progressFill');
            const percentage = (downloadedCount / products.length) * 100;
            progressFill.style.width = percentage + '%';
            progressFill.textContent = downloadedCount + ' / ' + products.length;
        }
        
        // Initialize
        renderProducts();
    </script>
</body>
</html>
