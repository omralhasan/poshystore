<?php
require_once __DIR__ . '/../../includes/auth_functions.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Policy - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
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
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
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
        
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .last-updated {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }
        
        h2 {
            color: #667eea;
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        h3 {
            color: #764ba2;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        p, li {
            color: #555;
            line-height: 1.8;
            margin-bottom: 1rem;
        }
        
        ul {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .highlight-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 5px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: transform 0.3s;
        }
        
        .back-link:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../../index.php" class="logo">Poshy Lifestyle</a>
            <div class="nav-links">
                <a href="../../index.php">Home</a>
                <a href="../shop/shop.php">Shop</a>
                <?php if ($is_logged_in): ?>
                    <a href="../shop/my_orders.php">My Orders</a>
                    <a href="../shop/cart.php">Cart</a>
                    <a href="../auth/logout.php">Logout</a>
                <?php else: ?>
                    <a href="../auth/signin.php">Sign In</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>↩️ Return Policy</h1>
        <p class="last-updated">Last Updated: February 10, 2026</p>

        <div class="highlight-box">
            <strong>30-Day Return Guarantee:</strong> We want you to be completely satisfied with your purchase. If you're not happy with your order, you can return it within 30 days of delivery for a full refund or exchange.
        </div>

        <h2>1. Return Eligibility</h2>
        <p>To be eligible for a return, your item must meet the following conditions:</p>
        <ul>
            <li>Item must be returned within 30 days of delivery date</li>
            <li>Item must be unused and in the same condition that you received it</li>
            <li>Item must be in the original packaging with all tags attached</li>
            <li>Proof of purchase or receipt must be provided</li>
            <li>Item must not be a final sale or clearance item</li>
        </ul>

        <h2>2. Non-Returnable Items</h2>
        <p>Certain items cannot be returned for hygiene and safety reasons:</p>
        <ul>
            <li>Intimate or sanitary goods</li>
            <li>Personalized or custom-made items</li>
            <li>Gift cards</li>
            <li>Final sale or clearance items (marked as such at time of purchase)</li>
            <li>Items marked as non-returnable at the time of purchase</li>
        </ul>

        <h2>3. How to Initiate a Return</h2>
        <p>To start a return, please follow these simple steps:</p>
        <ol style="margin-left: 2rem; margin-bottom: 1.5rem;">
            <li>Log in to your account and go to "My Orders"</li>
            <li>Select the order containing the item(s) you wish to return</li>
            <li>Click on "Request Return" and select the item(s)</li>
            <li>Choose your reason for return and submit the request</li>
            <li>You will receive a return authorization email with instructions</li>
        </ol>

        <div class="highlight-box">
            <strong>📞 Need Help?</strong> Contact our customer service team at support@poshystore.com or call +962 6 123 4567
        </div>

        <h2>4. Return Shipping</h2>
        <h3>Standard Returns</h3>
        <ul>
            <li>For returns due to change of mind, customer is responsible for return shipping costs</li>
            <li>We recommend using a tracked shipping service for your return</li>
            <li>Shipping costs are non-refundable</li>
        </ul>

        <h3>Defective or Wrong Items</h3>
        <ul>
            <li>If you received a defective or incorrect item, we will cover all return shipping costs</li>
            <li>A prepaid return shipping label will be provided</li>
            <li>Original shipping costs will also be refunded</li>
        </ul>

        <h2>5. Refund Process</h2>
        <p>Once we receive your returned item, our team will inspect it and process your refund:</p>
        <ul>
            <li><strong>Inspection:</strong> 1-2 business days after we receive the item</li>
            <li><strong>Refund Processing:</strong> 2-3 business days after inspection approval</li>
            <li><strong>Bank Processing:</strong> 5-7 business days for the refund to appear in your account</li>
        </ul>
        <p><strong>Total estimated time:</strong> 8-12 business days from when we receive your return.</p>

        <div class="highlight-box">
            <strong>💰 Refund Method:</strong> Refunds will be issued to the original payment method used for the purchase.
        </div>

        <h2>6. Exchanges</h2>
        <p>If you need to exchange an item for a different size, color, or style:</p>
        <ul>
            <li>Follow the same return process and indicate you want an exchange</li>
            <li>Once we receive and approve your return, we'll ship the replacement item</li>
            <li>Exchanges are subject to product availability</li>
            <li>If the replacement item is not available, we'll issue a full refund</li>
        </ul>

        <h2>7. Partial Refunds</h2>
        <p>In some cases, only partial refunds may be granted:</p>
        <ul>
            <li>Items with obvious signs of use</li>
            <li>Items not in original condition or damaged (not due to our error)</li>
            <li>Items returned more than 30 days after delivery</li>
            <li>Items missing original packaging or tags</li>
        </ul>

        <h2>8. Sale Items</h2>
        <p>Sale items are returnable unless marked as "Final Sale" at the time of purchase. Only regular-priced items may be refunded. Sale items may only be exchanged or receive store credit.</p>

        <h2>9. Late or Missing Refunds</h2>
        <p>If you haven't received a refund after the estimated time:</p>
        <ol style="margin-left: 2rem; margin-bottom: 1.5rem;">
            <li>Check your bank account again</li>
            <li>Contact your credit card company (processing may take time)</li>
            <li>Contact your bank (processing delays may occur)</li>
            <li>If you've done all of this and still haven't received your refund, contact us at support@poshystore.com</li>
        </ol>

        <h2>10. Damaged or Defective Items</h2>
        <p>If you receive a damaged or defective item:</p>
        <ul>
            <li>Contact us immediately with photos of the damage</li>
            <li>We will arrange for pickup or provide a prepaid shipping label</li>
            <li>You will receive a full refund including original shipping costs</li>
            <li>Option for immediate replacement shipment if item is in stock</li>
        </ul>

        <h2>11. Contact Information</h2>
        <p>For any questions about returns or refunds:</p>
        <ul>
            <li>📧 Email: returns@poshystore.com</li>
            <li>📞 Phone: +962 6 123 4567</li>
            <li>📱 WhatsApp: +962 79 123 4567</li>
            <li>⏰ Support Hours: Monday-Friday, 9 AM - 6 PM (Jordan Time)</li>
        </ul>

        <a href="../../index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>
