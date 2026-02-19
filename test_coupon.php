<?php
// Simple test for coupon API
session_start();

// Simulate being logged in
$_SESSION['user_id'] = 1; // You may need to adjust this

?>
<!DOCTYPE html>
<html>
<head>
    <title>Coupon Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test Coupon API</h1>
    
    <div>
        <label>Coupon Code:</label>
        <input type="text" id="testCode" value="POSH">
        <br><br>
        <label>Cart Total:</label>
        <input type="number" id="testTotal" value="100">
        <br><br>
        <button onclick="testCoupon()">Test Coupon</button>
    </div>
    
    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>
    
    <script>
        function testCoupon() {
            const code = document.getElementById('testCode').value;
            const total = document.getElementById('testTotal').value;
            
            fetch('/poshy_store/api/apply_coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=apply_coupon&code=${encodeURIComponent(code)}&cart_total=${total}`
            })
            .then(response => response.text())
            .then(text => {
                document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed:', data);
                } catch (e) {
                    console.error('Not valid JSON:', text);
                }
            })
            .catch(error => {
                document.getElementById('result').innerHTML = '<pre>Error: ' + error + '</pre>';
            });
        }
    </script>
</body>
</html>
