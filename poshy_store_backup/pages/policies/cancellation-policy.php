<?php
require_once __DIR__ . '/../../includes/auth_functions.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancellation Policy - Poshy Store</title>    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>    <style>
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
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }

        th, td {
            padding: 0.8rem;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
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
        <h1>🚫 Order Cancellation Policy</h1>
        <p class="last-updated">Last Updated: February 10, 2026</p>

        <div class="highlight-box">
            <strong>Quick Cancellation:</strong> You can cancel your order free of charge before it ships. Once shipped, standard return policies apply.
        </div>

        <h2>1. When Can I Cancel My Order?</h2>
        <p>At Poshy Lifestyle Store, we understand that circumstances change. You may cancel your order under the following conditions:</p>

        <table>
            <thead>
                <tr>
                    <th>Order Status</th>
                    <th>Can Cancel?</th>
                    <th>Refund Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Order Placed (Processing)</td>
                    <td>✅ Yes</td>
                    <td>100% Full Refund</td>
                </tr>
                <tr>
                    <td>Preparing for Shipment</td>
                    <td>✅ Yes (within 2 hours)</td>
                    <td>100% Full Refund</td>
                </tr>
                <tr>
                    <td>Shipped</td>
                    <td>❌ No - Use Return Policy</td>
                    <td>Refund minus shipping</td>
                </tr>
                <tr>
                    <td>Out for Delivery</td>
                    <td>❌ No - Use Return Policy</td>
                    <td>Refund minus shipping</td>
                </tr>
                <tr>
                    <td>Delivered</td>
                    <td>❌ No - Use Return Policy</td>
                    <td>Refund minus shipping</td>
                </tr>
            </tbody>
        </table>

        <h2>2. How to Cancel Your Order</h2>
        <p>There are three easy ways to cancel your order:</p>

        <h3>Method 1: Online Cancellation (Recommended)</h3>
        <ol style="margin-left: 2rem; margin-bottom: 1.5rem;">
            <li>Log in to your account</li>
            <li>Go to "My Orders"</li>
            <li>Find the order you want to cancel</li>
            <li>Click "Cancel Order" button</li>
            <li>Confirm your cancellation</li>
            <li>You'll receive an email confirmation immediately</li>
        </ol>

        <h3>Method 2: Email</h3>
        <p>Send an email to support@poshystore.com with:</p>
        <ul>
            <li>Your order number</li>
            <li>Email address used for the order</li>
            <li>Reason for cancellation (optional)</li>
        </ul>

        <h3>Method 3: Phone</h3>
        <p>Call our customer service at +962 6 123 4567</p>
        <ul>
            <li>Monday-Friday: 9 AM - 6 PM (Jordan Time)</li>
            <li>Have your order number ready</li>
        </ul>

        <div class="warning-box">
            <strong>⚠️ Important:</strong> Cancellation requests must be made within 24 hours of placing the order for guaranteed processing. Orders placed during weekends or holidays may have extended processing times.
        </div>

        <h2>3. Cancellation Time Frames</h2>
        <ul>
            <li><strong>Within 2 hours of order placement:</strong> Instant cancellation with full refund</li>
            <li><strong>2-24 hours after order placement:</strong> Cancellation possible if order hasn't shipped (95% success rate)</li>
            <li><strong>After 24 hours:</strong> Harder to cancel as order may be in shipping process</li>
            <li><strong>After shipping:</strong> Cannot cancel - use return policy instead</li>
        </ul>

        <h2>4. Refund Processing</h2>
        <p>When your cancellation is approved, here's what happens:</p>
        <ul>
            <li><strong>Cancellation Confirmation:</strong> Immediate email notification</li>
            <li><strong>Refund Initiation:</strong> Within 24 hours of cancellation</li>
            <li><strong>Payment Method Processing:</strong> 5-7 business days</li>
            <li><strong>Full Amount Refunded:</strong> 100% of order total including shipping costs</li>
        </ul>

        <div class="highlight-box">
            <strong>💰 Refund Method:</strong> All refunds are processed to the original payment method used for purchase. We do not provide cash refunds.
        </div>

        <h2>5. Partial Order Cancellations</h2>
        <p>If you ordered multiple items and want to cancel only some of them:</p>
        <ul>
            <li>Full cancellation is available if the order hasn't been processed</li>
            <li>Partial cancellations may not be possible once order is being prepared</li>
            <li>Contact customer service immediately for assistance</li>
            <li>Alternatively, receive the full order and return unwanted items</li>
        </ul>

        <h2>6. Non-Cancellable Orders</h2>
        <p>The following orders cannot be cancelled once placed:</p>
        <ul>
            <li>Custom or personalized items</li>
            <li>Final sale or clearance items (marked at checkout)</li>
            <li>Digital products or gift cards (once code is generated)</li>
            <li>Orders already shipped or delivered</li>
        </ul>

        <h2>7. Failed Cancellation Requests</h2>
        <p>If your order has already shipped before we receive your cancellation request:</p>
        <ul>
            <li>You'll be notified that the cancellation couldn't be processed</li>
            <li>You can refuse delivery when the courier arrives</li>
            <li>Or accept delivery and initiate a return within 30 days</li>
            <li>Standard return shipping fees may apply</li>
        </ul>

        <h2>8. Automatic Cancellations</h2>
        <p>We may cancel your order in the following situations:</p>
        <ul>
            <li>Payment authorization fails or is declined</li>
            <li>Product is out of stock or discontinued</li>
            <li>Shipping address cannot be verified</li>
            <li>Suspected fraudulent activity</li>
            <li>Unable to contact customer for verification</li>
        </ul>
        <p>You will be notified immediately if we cancel your order, and a full refund will be issued within 5-7 business days.</p>

        <h2>9. Order Modifications</h2>
        <p>Instead of cancelling, you may be able to modify your order:</p>
        <ul>
            <li><strong>Change shipping address:</strong> Possible within 6 hours of order placement</li>
            <li><strong>Change items:</strong> Must cancel and place a new order</li>
            <li><strong>Change quantity:</strong> Must cancel and place a new order</li>
            <li><strong>Add items:</strong> Must place a separate order</li>
        </ul>

        <div class="highlight-box">
            <strong>💡 Pro Tip:</strong> For the fastest cancellation, use the online cancellation feature in "My Orders" section. It's instant and available 24/7!
        </div>

        <h2>10. Questions or Issues?</h2>
        <p>If you have any questions about cancelling your order or need assistance:</p>
        <ul>
            <li>📧 Email: support@poshystore.com</li>
            <li>📞 Phone: +962 6 123 4567</li>
            <li>📱 WhatsApp: +962 79 123 4567</li>
            <li>⏰ Support Hours: Monday-Friday, 9 AM - 6 PM (Jordan Time)</li>
        </ul>

        <p>Our customer service team is here to help make the process as smooth as possible!</p>

        <a href="../../index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>
