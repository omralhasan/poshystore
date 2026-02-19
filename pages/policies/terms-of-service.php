<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/language.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
        .policy-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .policy-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 5px 15px rgba(45, 19, 44, 0.1);
            border: 2px solid transparent;
        }
        
        .policy-card h1 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            margin-bottom: 1rem;
            font-size: 2.2rem;
        }
        
        .policy-card .last-updated {
            color: var(--royal-gold);
            font-size: 0.9rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gold-light);
        }
        
        .policy-card h2 {
            color: var(--deep-purple);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-family: 'Playfair Display', serif;
        }
        
        .policy-card h3 {
            color: var(--royal-gold);
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .policy-card p, .policy-card li {
            color: #555;
            line-height: 1.8;
            margin-bottom: 1rem;
        }
        
        .policy-card ul, .policy-card ol {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .highlight-box {
            background: rgba(201, 168, 106, 0.1);
            border-left: 4px solid var(--royal-gold);
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0 10px 10px 0;
        }
        
        .warning-box {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0 10px 10px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 0.8rem;
            text-align: left;
            border: 1px solid var(--gold-light);
        }
        
        th {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            color: var(--gold-light);
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background: rgba(201, 168, 106, 0.05);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
            color: var(--deep-purple);
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(201, 168, 106, 0.3);
        }
        
        .back-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(201, 168, 106, 0.5);
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
    <div class="policy-container">
    <div class="policy-card">
        <h1><i class="fas fa-file-contract me-2" style="color: var(--royal-gold);"></i>Terms of Service</h1>
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

        <a href="../../index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
    </div>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
