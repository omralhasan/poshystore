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
    <title>Shipping Policy - Poshy Store</title>
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
        <h1><i class="fas fa-shipping-fast me-2" style="color: var(--royal-gold);"></i>Shipping Policy</h1>
        <p class="last-updated">Last Updated: February 10, 2026</p>

        <div class="highlight-box">
            <strong>🎉 Free Shipping:</strong> Enjoy free standard shipping on all orders over 50 JOD within Jordan!
        </div>

        <h2>1. Shipping Options & Rates</h2>
        <p>We offer multiple shipping options to meet your needs:</p>

        <table>
            <thead>
                <tr>
                    <th>Shipping Method</th>
                    <th>Delivery Time</th>
                    <th>Cost</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Standard Shipping</td>
                    <td>3-5 business days</td>
                    <td>Free (50+ JOD) / 3 JOD</td>
                </tr>
                <tr>
                    <td>Express Shipping</td>
                    <td>1-2 business days</td>
                    <td>8 JOD</td>
                </tr>
                <tr>
                    <td>Same Day Delivery (Amman)</td>
                    <td>Same day (if ordered before noon)</td>
                    <td>12 JOD</td>
                </tr>
                <tr>
                    <td>International Shipping</td>
                    <td>7-14 business days</td>
                    <td>Calculated at checkout</td>
                </tr>
            </tbody>
        </table>

        <h2>2. Processing Time</h2>
        <p>Orders are processed and prepared for shipment during our business hours:</p>
        <ul>
            <li><strong>Standard Processing:</strong> 1-2 business days</li>
            <li><strong>Orders placed on weekends:</strong> Processed on next business day (Sunday)</li>
            <li><strong>Orders placed on public holidays:</strong> Processed on next business day</li>
            <li><strong>Custom/Personalized items:</strong> 3-5 business days</li>
        </ul>

        <div class="highlight-box">
            <strong>📦 Order Tracking:</strong> You'll receive a tracking number via email and SMS once your order ships. Track your package in real-time!
        </div>

        <h2>3. Delivery Areas</h2>
        
        <h3>Domestic Shipping (Within Jordan)</h3>
        <p>We ship to all cities and areas within Jordan, including:</p>
        <ul>
            <li>Amman and surrounding areas</li>
            <li>Zarqa, Irbid, Aqaba, Karak</li>
            <li>All other governorates</li>
            <li>Remote areas (may have extended delivery time)</li>
        </ul>

        <h3>International Shipping</h3>
        <p>We currently ship to the following regions:</p>
        <ul>
            <li>GCC Countries (Saudi Arabia, UAE, Kuwait, Qatar, Bahrain, Oman)</li>
            <li>Middle East & North Africa</li>
            <li>Europe (selected countries)</li>
            <li>United States and Canada</li>
        </ul>
        <p>International shipping rates and times are calculated at checkout based on destination and weight.</p>

        <h2>4. Order Tracking</h2>
        <p>Stay informed about your order every step of the way:</p>
        <ul>
            <li><strong>Order Confirmation:</strong> Immediate email after placing order</li>
            <li><strong>Processing Update:</strong> Email when order is being prepared</li>
            <li><strong>Shipment Notification:</strong> Email and SMS with tracking number</li>
            <li><strong>Out for Delivery:</strong> SMS notification on delivery day</li>
            <li><strong>Delivery Confirmation:</strong> Confirmation once package is delivered</li>
        </ul>

        <h3>How to Track Your Order</h3>
        <ol style="margin-left: 2rem; margin-bottom: 1.5rem;">
            <li>Log in to your account</li>
            <li>Go to "My Orders"</li>
            <li>Click on the order you want to track</li>
            <li>View real-time tracking information</li>
            <li>Or use the tracking link sent via email</li>
        </ol>

        <h2>5. Delivery Process</h2>
        <p>Our delivery partners will:</p>
        <ul>
            <li>Contact you via phone before delivery</li>
            <li>Deliver to your specified address during business hours (9 AM - 6 PM)</li>
            <li>Require a signature upon delivery for orders above 100 JOD</li>
            <li>Leave package at doorstep if pre-authorized and under 100 JOD</li>
            <li>Make up to 2 delivery attempts if you're not available</li>
        </ul>

        <h2>6. Shipping Restrictions</h2>
        <p>Please note the following restrictions:</p>
        <ul>
            <li>We do not ship to P.O. Boxes for same-day or express delivery</li>
            <li>Some items cannot be shipped internationally due to customs restrictions</li>
            <li>Heavy or oversized items may require special shipping arrangements</li>
            <li>Alcohol and certain cosmetics cannot be shipped internationally</li>
        </ul>

        <h2>7. Delivery Issues</h2>
        
        <h3>Failed Delivery Attempts</h3>
        <p>If delivery cannot be completed:</p>
        <ul>
            <li>First attempt: Courier will leave a notice and schedule re-delivery</li>
            <li>Second attempt: Next business day</li>
            <li>After 2 failed attempts: Package held at local depot for 5 days</li>
            <li>After 5 days: Package returned to Poshy Store</li>
        </ul>

        <h3>Lost or Damaged Packages</h3>
        <p>If your package is lost or arrives damaged:</p>
        <ul>
            <li>Contact us immediately at support@poshystore.com</li>
            <li>Provide photos of damaged items/packaging</li>
            <li>We'll open an investigation with the shipping carrier</li>
            <li>Full refund or replacement provided once confirmed</li>
        </ul>

        <h3>Wrong Address</h3>
        <p>If you provided an incorrect shipping address:</p>
        <ul>
            <li>Contact us within 24 hours of placing order</li>
            <li>Address can be changed if order hasn't shipped yet</li>
            <li>After shipment, carrier may charge address correction fee</li>
            <li>We are not responsible for deliveries made to incorrect addresses provided by customer</li>
        </ul>

        <h2>8. Customs & Import Duties</h2>
        <p>For international orders:</p>
        <ul>
            <li>Customer is responsible for all customs duties and import taxes</li>
            <li>These fees are determined by your country's customs authority</li>
            <li>Customs may delay your shipment for inspection</li>
            <li>We cannot predict or control these fees</li>
            <li>Refusal to pay duties may result in package being returned (no refund)</li>
        </ul>

        <h2>9. Holiday Shipping</h2>
        <p>During peak holiday seasons (Ramadan, Eid, Christmas, etc.):</p>
        <ul>
            <li>Orders may experience delays of 1-3 additional business days</li>
            <li>We recommend ordering 1-2 weeks before needed date</li>
            <li>Express shipping is still available but may take longer than usual</li>
            <li>Holiday shipping deadlines will be posted on our website</li>
        </ul>

        <div class="highlight-box">
            <strong>🎁 Gift Wrapping:</strong> Add gift wrapping at checkout for 2 JOD. Your gift will arrive beautifully wrapped and ready to present!
        </div>

        <h2>10. Order Packaging</h2>
        <p>We take great care in packaging your orders:</p>
        <ul>
            <li>All items are wrapped in protective materials</li>
            <li>Fragile items receive extra cushioning and marked as "Fragile"</li>
            <li>Branded Poshy Lifestyle packaging</li>
            <li>Eco-friendly materials whenever possible</li>
            <li>Invoice and return instructions included</li>
        </ul>

        <h2>11. Contact Shipping Support</h2>
        <p>Have questions about your shipment?</p>
        <ul>
            <li>📧 Email: shipping@poshystore.com</li>
            <li>📞 Phone: +962 6 123 4567</li>
            <li>📱 WhatsApp: +962 79 123 4567</li>
            <li>⏰ Support Hours: Monday-Friday, 9 AM - 6 PM (Jordan Time)</li>
        </ul>

        <p><strong>For urgent delivery issues, please call us directly for fastest resolution.</strong></p>

        <a href="../../index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
    </div>
    </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
</body>
</html>
