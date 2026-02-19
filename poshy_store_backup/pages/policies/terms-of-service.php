<?php
require_once __DIR__ . '/../../includes/auth_functions.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Poshy Store</title>
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
        <h1>📜 Terms of Service</h1>
        <p class="last-updated">Last Updated: February 10, 2026</p>

        <h2>1. Agreement to Terms</h2>
        <p>By accessing and using Poshy Lifestyle Store ("the Website"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

        <h2>2. Use License</h2>
        <p>Permission is granted to temporarily access the materials (information or software) on Poshy Lifestyle Store's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
        <ul>
            <li>Modify or copy the materials</li>
            <li>Use the materials for any commercial purpose, or for any public display (commercial or non-commercial)</li>
            <li>Attempt to decompile or reverse engineer any software contained on the Website</li>
            <li>Remove any copyright or other proprietary notations from the materials</li>
            <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
        </ul>

        <h2>3. Account Terms</h2>
        <p>When you create an account with us, you must provide accurate, complete, and up-to-date information. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account.</p>
        <p>You are responsible for safeguarding the password that you use to access the service and for any activities or actions under your password.</p>

        <h2>4. Product Information</h2>
        <p>We have made every effort to display as accurately as possible the colors and images of our products that appear at the store. We cannot guarantee that your computer monitor's display of any color will be accurate.</p>
        <p>We reserve the right to limit the quantities of any products or services that we offer. All descriptions of products or product pricing are subject to change at any time without notice.</p>

        <h2>5. Pricing and Payment</h2>
        <ul>
            <li>All prices are listed in Jordanian Dinars (JOD)</li>
            <li>We reserve the right to change prices at any time</li>
            <li>Payment must be received before your order is processed</li>
            <li>We accept various payment methods as displayed at checkout</li>
            <li>All transactions are secure and encrypted</li>
        </ul>

        <h2>6. Order Acceptance</h2>
        <p>We reserve the right to refuse any order you place with us. We may, in our sole discretion, limit or cancel quantities purchased per person, per household, or per order. These restrictions may include orders placed by or under the same customer account, the same credit card, and/or orders that use the same billing and/or shipping address.</p>

        <h2>7. Prohibited Uses</h2>
        <p>You may use the Website only for lawful purposes and in accordance with these Terms. You agree not to use the Website:</p>
        <ul>
            <li>In any way that violates any applicable national or international law or regulation</li>
            <li>To transmit, or procure the sending of, any advertising or promotional material without our prior written consent</li>
            <li>To impersonate or attempt to impersonate the Company, a Company employee, another user, or any other person or entity</li>
            <li>To engage in any other conduct that restricts or inhibits anyone's use or enjoyment of the Website</li>
        </ul>

        <h2>8. Intellectual Property</h2>
        <p>The Website and its entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, and audio) are owned by Poshy Lifestyle Store, its licensors, or other providers of such material and are protected by Jordan and international copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.</p>

        <h2>9. User Content</h2>
        <p>By submitting reviews, comments, or other content to our Website, you grant us a non-exclusive, royalty-free, perpetual, irrevocable, and fully sublicensable right to use, reproduce, modify, adapt, publish, translate, create derivative works from, distribute, and display such content throughout the world in any media.</p>

        <h2>10. Limitation of Liability</h2>
        <p>In no event shall Poshy Lifestyle Store, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the service.</p>

        <h2>11. Disclaimer</h2>
        <p>Your use of the Website is at your sole risk. The service is provided on an "AS IS" and "AS AVAILABLE" basis. The service is provided without warranties of any kind, whether express or implied.</p>

        <h2>12. Governing Law</h2>
        <p>These Terms shall be governed and construed in accordance with the laws of Jordan, without regard to its conflict of law provisions.</p>

        <h2>13. Changes to Terms</h2>
        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days' notice prior to any new terms taking effect.</p>

        <h2>14. Contact Information</h2>
        <p>If you have any questions about these Terms, please contact us:</p>
        <ul>
            <li>📧 Email: legal@poshystore.com</li>
            <li>📞 Phone: +962 6 123 4567</li>
            <li>🏢 Address: Amman, Jordan</li>
        </ul>

        <a href="../../index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>
