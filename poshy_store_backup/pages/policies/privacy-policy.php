<?php
require_once __DIR__ . '/../../includes/auth_functions.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Poshy Store</title>
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
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="page-container">
    <div class="container py-5">
        <h1 class="section-title-ramadan text-center mb-4">
            <i class="fas fa-shield-alt me-2"></i>Privacy Policy
        </h1>
        <p class="text-center mb-4" style="color: var(--gold-color); font-size: 0.95rem;">Last Updated: February 10, 2026</p>

        <div class="card-ramadan p-4">
            <div class="alert-ramadan alert-info mb-4">
                <strong><i class="fas fa-info-circle me-2"></i>Your Privacy Matters:</strong> At Poshy Lifestyle Store, we are committed to protecting your personal information and your right to privacy. This policy explains how we collect, use, and safeguard your data.
            </div>

        <h2 style="color: var(--purple-color); font-family: 'Playfair Display', serif; margin-top: 2rem;">1. Information We Collect</h2>
        
        <h3>Personal Information</h3>
        <p>When you create an account or make a purchase, we collect:</p>
        <ul>
            <li>Full name</li>
            <li>Email address</li>
            <li>Phone number</li>
            <li>Billing and shipping addresses</li>
            <li>Payment information (processed securely through third-party providers)</li>
            <li>Order history and preferences</li>
        </ul>

        <h3>Automatically Collected Information</h3>
        <p>When you visit our website, we automatically collect:</p>
        <ul>
            <li>IP address</li>
            <li>Browser type and version</li>
            <li>Device information</li>
            <li>Pages visited and time spent</li>
            <li>Referring website</li>
            <li>Click patterns and navigation</li>
        </ul>

        <h3>Cookies and Tracking Technologies</h3>
        <p>We use cookies and similar technologies to:</p>
        <ul>
            <li>Remember your preferences and settings</li>
            <li>Keep you logged in</li>
            <li>Analyze site traffic and usage patterns</li>
            <li>Personalize your experience</li>
            <li>Serve relevant advertisements</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the information we collect to:</p>
        <ul>
            <li><strong>Process Orders:</strong> Fulfill and deliver your purchases</li>
            <li><strong>Account Management:</strong> Create and maintain your account</li>
            <li><strong>Customer Service:</strong> Respond to inquiries and provide support</li>
            <li><strong>Marketing:</strong> Send promotional emails (with your consent)</li>
            <li><strong>Fraud Prevention:</strong> Detect and prevent fraudulent transactions</li>
            <li><strong>Legal Compliance:</strong> Comply with applicable laws and regulations</li>
            <li><strong>Improvement:</strong> Analyze and improve our website and services</li>
            <li><strong>Personalization:</strong> Customize your shopping experience</li>
        </ul>

        <h2>3. How We Share Your Information</h2>
        <p>We may share your information with:</p>

        <h3>Service Providers</h3>
        <ul>
            <li><strong>Payment Processors:</strong> To process transactions securely</li>
            <li><strong>Shipping Companies:</strong> To deliver your orders</li>
            <li><strong>Email Services:</strong> To send transactional and marketing emails</li>
            <li><strong>Analytics Providers:</strong> To analyze website usage</li>
            <li><strong>Customer Service Tools:</strong> To provide support</li>
        </ul>

        <h3>Legal Requirements</h3>
        <p>We may disclose your information if required by law or in response to:</p>
        <ul>
            <li>Court orders or legal processes</li>
            <li>Government or regulatory requests</li>
            <li>Protection of our rights and property</li>
            <li>Investigation of fraud or security issues</li>
        </ul>

        <h3>Business Transfers</h3>
        <p>If we are involved in a merger, acquisition, or sale of assets, your information may be transferred to the new owner.</p>

        <div class="highlight-box">
            <strong>🔐 We Never:</strong> Sell your personal information to third parties for their marketing purposes.
        </div>

        <h2>4. Data Security</h2>
        <p>We implement industry-standard security measures to protect your information:</p>
        <ul>
            <li><strong>SSL/TLS Encryption:</strong> All data transmitted is encrypted</li>
            <li><strong>Secure Servers:</strong> Data stored on secure, protected servers</li>
            <li><strong>Access Controls:</strong> Limited employee access to personal data</li>
            <li><strong>Regular Audits:</strong> Security assessments and updates</li>
            <li><strong>PCI Compliance:</strong> Payment card data handled according to PCI-DSS standards</li>
            <li><strong>Password Protection:</strong> Your password is encrypted and never stored in plain text</li>
        </ul>

        <p>However, no method of transmission over the Internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security.</p>

        <h2>5. Your Privacy Rights</h2>
        <p>You have the following rights regarding your personal information:</p>

        <h3>Access and Portability</h3>
        <ul>
            <li>Request a copy of your personal data</li>
            <li>Download your information in a portable format</li>
        </ul>

        <h3>Correction and Updates</h3>
        <ul>
            <li>Update your account information at any time</li>
            <li>Correct inaccurate data</li>
        </ul>

        <h3>Deletion</h3>
        <ul>
            <li>Request deletion of your account and personal data</li>
            <li>Note: Some data may be retained for legal or business purposes</li>
        </ul>

        <h3>Marketing Opt-Out</h3>
        <ul>
            <li>Unsubscribe from marketing emails via the link in any email</li>
            <li>Update your communication preferences in your account settings</li>
            <li>Note: You will still receive transactional emails (order confirmations, etc.)</li>
        </ul>

        <h3>Cookie Management</h3>
        <ul>
            <li>Control cookies through your browser settings</li>
            <li>Note: Disabling cookies may affect website functionality</li>
        </ul>

        <h2>6. Data Retention</h2>
        <p>We retain your personal information for as long as necessary to:</p>
        <ul>
            <li>Provide our services to you</li>
            <li>Comply with legal obligations (typically 7 years for financial records)</li>
            <li>Resolve disputes</li>
            <li>Enforce our agreements</li>
        </ul>
        <p>After this period, data is securely deleted or anonymized.</p>

        <h2>7. Children's Privacy</h2>
        <p>Our website is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us, and we will delete it.</p>

        <h2>8. Third-Party Links</h2>
        <p>Our website may contain links to third-party websites. We are not responsible for the privacy practices of these external sites. We encourage you to review their privacy policies before providing any personal information.</p>

        <h2>9. International Data Transfers</h2>
        <p>Your information may be transferred to and processed in countries other than Jordan. We ensure appropriate safeguards are in place to protect your data in accordance with this Privacy Policy and applicable laws.</p>

        <h2>10. Social Media Login</h2>
        <p>If you use social media (Google, Facebook) to log in:</p>
        <ul>
            <li>We receive basic profile information (name, email, profile picture)</li>
            <li>We do not access or store your social media password</li>
            <li>We do not post on your behalf without permission</li>
            <li>You can revoke access through your social media account settings</li>
        </ul>

        <h2>11. Marketing Communications</h2>
        <p>With your consent, we may send you:</p>
        <ul>
            <li>Promotional offers and discounts</li>
            <li>New product announcements</li>
            <li>Newsletters and updates</li>
            <li>Personalized recommendations</li>
        </ul>
        <p>You can opt out at any time by clicking the "Unsubscribe" link in any email or updating your preferences in your account.</p>

        <h2>12. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. When we make changes:</p>
        <ul>
            <li>We will update the "Last Updated" date at the top</li>
            <li>Material changes will be notified via email or website notice</li>
            <li>Continued use after changes constitutes acceptance</li>
        </ul>

        <h2>13. Contact Us</h2>
        <p>If you have questions, concerns, or requests regarding this Privacy Policy or your personal information:</p>
        <ul>
            <li>📧 Email: privacy@poshystore.com</li>
            <li>📞 Phone: +962 6 123 4567</li>
            <li>🏢 Address: Poshy Lifestyle Store, Amman, Jordan</li>
            <li>⏰ Response Time: Within 48 hours</li>
        </ul>

        <div class="highlight-box">
            <strong>Data Protection Officer:</strong> For specific privacy concerns, you can contact our Data Protection Officer at dpo@poshystore.com
        </div>

        <h2>14. Your Consent</h2>
        <p>By using our website and services, you consent to this Privacy Policy. If you do not agree with this policy, please do not use our website.</p>

        <a href="../../index.php" class="back-link">← Back to Home</a>
        </div>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
