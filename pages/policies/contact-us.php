<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$message_sent = false;
$message_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_subject = trim($_POST['contact_subject'] ?? '');
    $contact_message = trim($_POST['contact_message'] ?? '');

    if (empty($contact_name) || empty($contact_email) || empty($contact_message)) {
        $message_error = $current_lang === 'ar' ? 'يرجى ملء الحقول المطلوبة' : 'Please fill in required fields';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $message_error = $current_lang === 'ar' ? 'بريد إلكتروني غير صالح' : 'Invalid email address';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param('sssss', $contact_name, $contact_email, $contact_phone, $contact_subject, $contact_message);
            if ($stmt->execute()) {
                $message_sent = true;
                // Notify admin by email
                $admin_email = 'info@poshystore.com';
                $email_subject = "New Contact Form Message: $contact_subject";
                $email_body = "Name: $contact_name\nEmail: $contact_email\nPhone: $contact_phone\n\nMessage:\n$contact_message";
                @mail($admin_email, $email_subject, $email_body, "From: $contact_email");
            } else {
                $message_error = $current_lang === 'ar' ? 'فشل إرسال الرسالة. حاول مرة أخرى.' : 'Failed to send message. Please try again.';
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            $message_error = $current_lang === 'ar' ? 'حدث خطأ. حاول مرة أخرى.' : 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $current_lang === 'ar' ? 'تواصل مع Poshy Store - فريق خدمة العملاء لدينا جاهز لمساعدتك. واتساب، بريد إلكتروني، أو نموذج الاتصال.' : 'Contact Poshy Store - Our customer service team is ready to help. WhatsApp, email, or contact form.' ?>">
    <title><?= $current_lang === 'ar' ? 'اتصل بنا' : 'Contact Us' ?> | Poshy Store</title>
    <link rel="alternate" hreflang="en" href="https://poshystore.com/pages/policies/contact-us.php">
    <link rel="alternate" hreflang="ar" href="https://poshystore.com/ar/pages/policies/contact-us.php">
    <link rel="alternate" hreflang="x-default" href="https://poshystore.com/pages/policies/contact-us.php">
    <?php require_once __DIR__ . '/../../includes/home_theme_header.php'; ?>
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #4a1942 0%, #89216B 50%, #da5c8a 100%);
            padding: 4rem 2rem;
            text-align: center;
            color: white;
        }
        .contact-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
        }
        .contact-hero p {
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
            opacity: 0.9;
        }
        .contact-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2.5rem;
        }
        @media (max-width: 768px) {
            .contact-container { grid-template-columns: 1fr; }
            .contact-hero h1 { font-size: 1.8rem; }
        }
        .contact-info-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .contact-info-card h2 {
            color: var(--accent-dark);
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }
        .contact-method {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .contact-method i {
            font-size: 1.3rem;
            color: var(--accent);
            width: 24px;
            text-align: center;
            margin-top: 3px;
        }
        .contact-method h3 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }
        .contact-method p, .contact-method a {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.6;
            text-decoration: none;
        }
        .contact-method a:hover { color: var(--accent); }
        .contact-form-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .contact-form-card h2 {
            color: var(--accent-dark);
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--text-primary);
        }
        .form-group label .required { color: #e74c3c; }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
            background: var(--bg-light);
            color: var(--text-primary);
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(218, 92, 138, 0.1);
        }
        textarea.form-control { min-height: 140px; resize: vertical; }
        .btn-submit {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: white;
            border: none;
            padding: 0.85rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(218, 92, 138, 0.3); }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php renderGTMNoScript(); ?>
    <?php require_once __DIR__ . '/../../includes/home_navbar.php'; ?>

    <section class="contact-hero">
        <h1><?= $current_lang === 'ar' ? 'تواصل معنا' : 'Get in Touch' ?></h1>
        <p><?= $current_lang === 'ar' ? 'فريقنا جاهز لمساعدتك. تواصل معنا بأي طريقة تناسبك' : 'Our team is ready to help. Reach out any way that works for you' ?></p>
    </section>

    <div class="contact-container">
        <div class="contact-info-card">
            <h2><?= $current_lang === 'ar' ? 'معلومات الاتصال' : 'Contact Information' ?></h2>

            <div class="contact-method">
                <i class="fab fa-whatsapp"></i>
                <div>
                    <h3>WhatsApp</h3>
                    <a href="https://wa.me/962770058416" target="_blank" rel="noopener">+962 7 7005 8416</a>
                </div>
            </div>

            <div class="contact-method">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <h3><?= $current_lang === 'ar' ? 'الهاتف' : 'Phone' ?></h3>
                    <a href="tel:+962770058416">+962 7 7005 8416</a>
                </div>
            </div>

            <div class="contact-method">
                <i class="fas fa-envelope"></i>
                <div>
                    <h3>Email</h3>
                    <a href="mailto:info@poshystore.com">info@poshystore.com</a>
                </div>
            </div>

            <div class="contact-method">
                <i class="fab fa-instagram"></i>
                <div>
                    <h3>Instagram</h3>
                    <a href="https://www.instagram.com/posh_.lifestyle" target="_blank" rel="noopener">@posh_.lifestyle</a>
                </div>
            </div>

            <div class="contact-method">
                <i class="fab fa-facebook"></i>
                <div>
                    <h3>Facebook</h3>
                    <a href="https://www.facebook.com/share/1Am5FrXwQU/" target="_blank" rel="noopener">Poshy Store</a>
                </div>
            </div>

            <div class="contact-method">
                <i class="fas fa-clock"></i>
                <div>
                    <h3><?= $current_lang === 'ar' ? 'ساعات العمل' : 'Business Hours' ?></h3>
                    <p><?= $current_lang === 'ar' ? 'السبت - الخميس: 9 صباحاً - 9 مساءً<br>الجمعة: 12 ظهراً - 6 مساءً' : 'Sat - Thu: 9:00 AM - 9:00 PM<br>Friday: 12:00 PM - 6:00 PM' ?></p>
                </div>
            </div>
        </div>

        <div class="contact-form-card">
            <h2><?= $current_lang === 'ar' ? 'أرسل لنا رسالة' : 'Send Us a Message' ?></h2>

            <?php if ($message_sent): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $current_lang === 'ar' ? 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.' : 'Your message has been sent successfully! We will get back to you soon.' ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message_error)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($message_error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="contact-us.php">
                <div class="form-group">
                    <label><?= $current_lang === 'ar' ? 'الاسم الكامل' : 'Full Name' ?> <span class="required">*</span></label>
                    <input type="text" name="contact_name" class="form-control" required maxlength="100" value="<?= htmlspecialchars($_POST['contact_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="contact_email" class="form-control" required maxlength="200" value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label><?= $current_lang === 'ar' ? 'رقم الهاتف (اختياري)' : 'Phone Number (optional)' ?></label>
                    <input type="tel" name="contact_phone" class="form-control" maxlength="20" value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label><?= $current_lang === 'ar' ? 'الموضوع' : 'Subject' ?></label>
                    <input type="text" name="contact_subject" class="form-control" maxlength="200" value="<?= htmlspecialchars($_POST['contact_subject'] ?? '') ?>" placeholder="<?= $current_lang === 'ar' ? 'استفسار، شكوى، اقتراح...' : 'Inquiry, complaint, suggestion...' ?>">
                </div>

                <div class="form-group">
                    <label><?= $current_lang === 'ar' ? 'الرسالة' : 'Message' ?> <span class="required">*</span></label>
                    <textarea name="contact_message" class="form-control" required maxlength="5000" placeholder="<?= $current_lang === 'ar' ? 'اكتب رسالتك هنا...' : 'Write your message here...' ?>"><?= htmlspecialchars($_POST['contact_message'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="submit_contact" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> <?= $current_lang === 'ar' ? 'إرسال الرسالة' : 'Send Message' ?>
                </button>
            </form>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../includes/home_footer.php'; ?>
</body>
</html>
