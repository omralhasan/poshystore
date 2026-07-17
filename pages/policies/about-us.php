<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/language.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $current_lang === 'ar' ? 'تعرف على قصة Poshy Store - متجرك الموثوق لمنتجات العناية بالبشرة والتجميل الكورية الأصلية في الأردن. شغف بالجمال، التزام بالجودة.' : 'Discover the story of Poshy Store - your trusted source for authentic Korean beauty and skincare products in Jordan. Passion for beauty, commitment to quality.' ?>">
    <title><?= $current_lang === 'ar' ? 'عن بوشي ستور' : 'About Us' ?> | Poshy Store</title>
    <link rel="alternate" hreflang="en" href="https://poshystore.com/pages/policies/about-us.php">
    <link rel="alternate" hreflang="ar" href="https://poshystore.com/ar/pages/policies/about-us.php">
    <link rel="alternate" hreflang="x-default" href="https://poshystore.com/pages/policies/about-us.php">
    <?php require_once __DIR__ . '/../../includes/home_theme_header.php'; ?>
    <style>
        .about-hero {
            background: linear-gradient(135deg, #4a1942 0%, #89216B 50%, #da5c8a 100%);
            padding: 4rem 2rem;
            text-align: center;
            color: white;
        }
        .about-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
        }
        .about-hero p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }
        .about-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        .about-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
        }
        .about-card h2 {
            color: var(--accent-dark);
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }
        .about-card p {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1rem;
        }
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .value-item {
            background: var(--surface-alt);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--border-light);
        }
        .value-item i {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 0.75rem;
        }
        .value-item h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        .value-item p {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
        }
        @media (max-width: 768px) {
            .about-hero h1 { font-size: 1.8rem; }
            .about-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php renderGTMNoScript(); ?>
    <?php require_once __DIR__ . '/../../includes/home_navbar.php'; ?>

    <section class="about-hero">
        <h1><?= $current_lang === 'ar' ? 'قصة بوشي ستور' : 'Our Story' ?></h1>
        <p><?= $current_lang === 'ar' ? 'شغف بالجمال الكوري الأصيل، التزام بالجودة والثقة' : 'Passion for authentic Korean beauty, commitment to quality and trust' ?></p>
    </section>

    <div class="about-container">
        <div class="about-card">
            <h2><?= $current_lang === 'ar' ? 'من نحن' : 'Who We Are' ?></h2>
            <p><?= $current_lang === 'ar' ? 'بوشي ستور هو متجر أردني متخصص في تقديم أفضل منتجات العناية بالبشرة والتجميل الكورية الأصلية. نحن هنا لنساعدك على اكتشاف روتين العناية المثالي لبشرتك من خلال تشكيلتنا المنتقاة بعناية من أشهر الماركات الكورية مثل Cosrx وSkin1004 وAxis-Y وBeauty of Joseon وAnua.' : 'Poshy Store is a Jordanian e-commerce store specialized in bringing the best authentic Korean beauty and skincare products. We are here to help you discover the perfect skincare routine through our carefully curated selection of renowned Korean brands including Cosrx, Skin1004, Axis-Y, Beauty of Joseon, and Anua.' ?></p>
        </div>

        <div class="about-card">
            <h2><?= $current_lang === 'ar' ? 'رسالتنا' : 'Our Mission' ?></h2>
            <p><?= $current_lang === 'ar' ? 'نؤمن بأن البشرة الجميلة تبدأ بالعناية الصحيحة. مهمتنا هي جعل منتجات العناية بالبشرة الكورية الأصلية في متناول الجميع في الأردن، مع ضمان الجودة والأصالة في كل منتج نقدمه. نختار كل منتج بعناية ليلبي احتياجات بشرتك المختلفة، سواء كانت جافة، دهنية، مختلطة، أو حساسة.' : 'We believe beautiful skin starts with the right care. Our mission is to make authentic Korean skincare products accessible to everyone in Jordan, with guaranteed quality and authenticity in every product we offer. We carefully select each product to meet your different skin needs, whether dry, oily, combination, or sensitive.' ?></p>
        </div>

        <div class="about-card">
            <h2><?= $current_lang === 'ar' ? 'قيمنا' : 'Our Values' ?></h2>
            <div class="values-grid">
                <div class="value-item">
                    <i class="fas fa-shield-alt"></i>
                    <h3><?= $current_lang === 'ar' ? 'الأصالة' : 'Authenticity' ?></h3>
                    <p><?= $current_lang === 'ar' ? 'جميع منتجاتنا أصلية 100% ومستوردة من موزعين معتمدين في كوريا' : 'All products are 100% authentic, imported from authorized Korean distributors' ?></p>
                </div>
                <div class="value-item">
                    <i class="fas fa-heart"></i>
                    <h3><?= $current_lang === 'ar' ? 'العناية' : 'Care' ?></h3>
                    <p><?= $current_lang === 'ar' ? 'نختار منتجات تلائم احتياجات بشرتك العربية ونقدم نصائح مخصصة' : 'We select products suitable for your skin needs and provide personalized advice' ?></p>
                </div>
                <div class="value-item">
                    <i class="fas fa-truck"></i>
                    <h3><?= $current_lang === 'ar' ? 'الموثوقية' : 'Reliability' ?></h3>
                    <p><?= $current_lang === 'ar' ? 'توصيل سريع لجميع مدن الأردن مع خدمة عملاء متميزة' : 'Fast delivery across all Jordanian cities with excellent customer service' ?></p>
                </div>
                <div class="value-item">
                    <i class="fas fa-star"></i>
                    <h3><?= $current_lang === 'ar' ? 'الجودة' : 'Quality' ?></h3>
                    <p><?= $current_lang === 'ar' ? 'نضمن أعلى معايير الجودة في كل منتج نقدمه لعملائنا' : 'We guarantee the highest quality standards in every product we offer' ?></p>
                </div>
            </div>
        </div>

        <div class="about-card">
            <h2><?= $current_lang === 'ar' ? 'لماذا تختار بوشي ستور؟' : 'Why Choose Poshy Store?' ?></h2>
            <p><?= $current_lang === 'ar' ? 'نحن لسنا مجرد متجر إلكتروني، نحن شريكك في رحلة العناية ببشرتك. مع بوشي ستور، تحصلين على منتجات أصلية بأسعار تنافسية، مع خدمة عملاء ودودة تتحدث العربية والإنجليزية، وتوصيل سريع لجميع محافظات الأردن. نضيف باستمرار منتجات جديدة من أفضل الماركات الكورية لنضمن لك تشكيلة متجددة تلبي كل احتياجاتك.' : 'We are more than just an online store; we are your partner in your skincare journey. With Poshy Store, you get authentic products at competitive prices, friendly customer service in both Arabic and English, and fast delivery to all Jordanian governorates. We continuously add new products from the best Korean brands to ensure a refreshed selection meeting all your needs.' ?></p>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../includes/home_footer.php'; ?>
</body>
</html>
