<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Debug Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .test-section {
            background: #f5f5f5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success { color: green; }
        .error { color: red; }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        #result {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Coupon API Debug Test</h1>
    
    <div class="test-section">
        <h3>Test Apply Coupon</h3>
        <p>Coupon Code: <input type="text" id="couponCode" value="WELCOME20" placeholder="Enter coupon code"></p>
        <p>Cart Total: <input type="number" id="cartTotal" value="100" step="0.001"></p>
        <button onclick="testApplyCoupon()">Test Apply Coupon</button>
    </div>
    
    <div id="result"></div>
    
    <script>
        function testApplyCoupon() {
            const code = document.getElementById('couponCode').value;
            const total = document.getElementById('cartTotal').value;
            const resultDiv = document.getElementById('result');
            
            resultDiv.innerHTML = '<p>Testing... Please wait.</p>';
            
            fetch('/poshy_store/api/apply_coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=apply_coupon&code=${encodeURIComponent(code)}&cart_total=${total}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Get raw response text first
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        return { isJson: true, data: data, raw: text };
                    } catch (e) {
                        return { isJson: false, error: e.message, raw: text };
                    }
                });
            })
            .then(result => {
                if (result.isJson) {
                    resultDiv.innerHTML = `
                        <h3 class="success">✓ Valid JSON Response</h3>
                        <pre>${JSON.stringify(result.data, null, 2)}</pre>
                        <h4>Raw Response:</h4>
                        <pre>${result.raw}</pre>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <h3 class="error">✗ Invalid JSON Response</h3>
                        <p><strong>Error:</strong> ${result.error}</p>
                        <h4>Raw Response:</h4>
                        <pre>${result.raw}</pre>
                        <p><strong>This is why you're seeing "Network error"!</strong></p>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <h3 class="error">✗ Network Error</h3>
                    <p>${error.message}</p>
                `;
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
