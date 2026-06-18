<?php
/**
 * SEO Helper - Central SEO, Schema, and Analytics Management
 * Provides reusable functions for meta tags, schema markup, and analytics
 */

if (!defined('POSHY_SEO_LOADED')) {
    define('POSHY_SEO_LOADED', true);
}

/**
 * Get the current canonical URL
 */
function getCanonicalURL(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'poshystore.com';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $cleanUri = strtok($uri, '?');
    $cleanUri = rtrim($cleanUri, '/');
    if (empty($cleanUri)) {
        $cleanUri = '/';
    }
    return $scheme . '://' . $host . $cleanUri;
}

/**
 * Render canonical link tag
 */
function renderCanonical(): void
{
    echo '<link rel="canonical" href="' . htmlspecialchars(getCanonicalURL()) . '">' . "\n";
}

/**
 * Render Organization Schema (JSON-LD)
 */
function renderOrganizationSchema(): void
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Poshy Store',
        'url' => 'https://poshystore.com',
        'logo' => 'https://poshystore.com/images/poshy-store-logo.png',
        'description' => 'Premium Korean beauty and skincare products in Jordan. Authentic K-beauty cosmetics, serums, moisturizers, and skincare routines.',
        'email' => 'info@poshystore.com',
        'telephone' => '+962770058416',
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'JO',
        ],
        'sameAs' => [
            'https://www.facebook.com/share/1Am5FrXwQU/',
            'https://www.instagram.com/posh_.lifestyle',
        ],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => '+962770058416',
            'contactType' => 'customer service',
            'availableLanguage' => ['English', 'Arabic'],
        ],
    ];
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n" . '</script>' . "\n";
}

/**
 * Render Product Schema (JSON-LD)
 */
function renderProductSchema(array $product, float $avgRating = 0, int $reviewCount = 0): void
{
    $name = $product['name_en'] ?? '';
    $desc = !empty($product['short_description_en']) ? $product['short_description_en'] : ($product['description'] ?? '');
    $price = $product['price_jod'] ?? 0;
    $image = !empty($product['image_link']) ? 'https://poshystore.com/' . ltrim($product['image_link'], '/') : '';
    $slug = $product['slug'] ?? '';
    $url = 'https://poshystore.com/' . $slug;
    $brandName = $product['brand_en'] ?? '';
    $sku = $product['id'] ?? '';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $name,
        'description' => strip_tags($desc),
        'url' => $url,
        'sku' => (string)$sku,
        'image' => $image,
        'brand' => [
            '@type' => 'Brand',
            'name' => $brandName ?: 'Poshy Store',
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => 'JOD',
            'price' => number_format((float)$price, 3, '.', ''),
            'priceValidUntil' => date('Y-12-31', strtotime('+1 year')),
            'availability' => ((int)($product['stock_quantity'] ?? 0) > 0)
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ],
    ];

    if ($avgRating > 0 && $reviewCount > 0) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($avgRating, 1),
            'reviewCount' => $reviewCount,
        ];
    }

    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n" . '</script>' . "\n";
}

/**
 * Render FAQ Schema (JSON-LD)
 */
function renderFAQSchema(array $faqs): void
{
    if (empty($faqs)) {
        return;
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [],
    ];
    foreach ($faqs as $faq) {
        $schema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $faq['question'] ?? '',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer'] ?? '',
            ],
        ];
    }
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n" . '</script>' . "\n";
}

/**
 * Render Review Schema (JSON-LD)
 */
function renderReviewSchema(array $review, array $product): void
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Review',
        'itemReviewed' => [
            '@type' => 'Product',
            'name' => $product['name_en'] ?? '',
            'url' => 'https://poshystore.com/' . ($product['slug'] ?? ''),
        ],
        'reviewRating' => [
            '@type' => 'Rating',
            'ratingValue' => (int)$review['rating'],
            'bestRating' => '5',
        ],
        'author' => [
            '@type' => 'Person',
            'name' => $review['user_full_name'] ?? 'Verified Customer',
        ],
        'reviewBody' => $review['review_text'] ?? '',
        'datePublished' => $review['created_at'] ?? date('c'),
    ];
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "\n" . '</script>' . "\n";
}

/**
 * Render GA4 + GTM tags
 */
function renderAnalytics(): void
{
    $ga4_id = getenv('GA4_MEASUREMENT_ID') ?: 'G-XXXXXXXXXX';
    $gtm_id = getenv('GTM_CONTAINER_ID') ?: 'GTM-XXXXXXX';
    ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= htmlspecialchars($gtm_id) ?>');</script>
    <!-- End Google Tag Manager -->

    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga4_id) ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= htmlspecialchars($ga4_id) ?>', {
        send_page_view: true,
        cookie_flags: 'SameSite=None;Secure'
    });
    </script>
    <?php
}

/**
 * Render GTM noscript iframe (place right after <body>)
 */
function renderGTMNoScript(): void
{
    $gtm_id = getenv('GTM_CONTAINER_ID') ?: 'GTM-XXXXXXX';
    ?>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= htmlspecialchars($gtm_id) ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php
}

/**
 * Generate meta description for homepage based on language
 */
function getHomeMetaDescription(string $lang): string
{
    if ($lang === 'ar') {
        return 'Poshy Store - متجر كوري متخصص في منتجات العناية بالبشرة والتجميل الأصلية. نقدم أشهر الماركات الكورية مثل Cosrx وSkin1004 وAxis-Y. تسوقي الآن منتجات خالية من القسوة وبأسعار تنافسية في الأردن.';
    }
    return 'Poshy Store - Premium Korean beauty and skincare products in Jordan. Shop authentic K-beauty from Cosrx, Skin1004, Axis-Y, and more. Cruelty-free cosmetics, serums, toners, and complete skincare routines with fast delivery.';
}

/**
 * Get FAQ data for homepage
 */
function getHomepageFAQs(string $lang): array
{
    if ($lang === 'ar') {
        return [
            [
                'question' => 'ما هي منتجات العناية بالبشرة الكورية الأفضل للبشرة الجافة؟',
                'answer' => 'للبشرة الجافة في الأردن، نوصي بمنتجات تحتوي على حمض الهيالورونيك والسيراميد من ماركات مثل Cosrx وSkin1004. روتين مثالي يبدأ بالتونر المرطب، ثم السيروم، وأخيراً المرطب الغني. جميع منتجاتنا أصلية ومضمونة.',
            ],
            [
                'question' => 'هل توفرون الشحن لجميع مدن الأردن؟',
                'answer' => 'نعم، نوفر الشحن لجميع مدن الأردن بما في ذلك عمان وإربد والزرقاء والعقبة والكرك. الشحن مجاني للطلبات فوق 35 دينار أردني، ورسوم التوصيل للطلبات الأقل 2 دينار فقط.',
            ],
            [
                'question' => 'كيف أتأكد من أصالة منتجات العناية بالبشرة؟',
                'answer' => 'جميع منتجاتنا أصلية 100% ومستوردة مباشرة من الموزعين المعتمدين في كوريا. نوفر منتجات من ماركات مثل Cosrx وSkin1004 وAxis-Y وBeauty of Joseon وAnua مع ضمان الجودة والأصالة.',
            ],
            [
                'question' => 'ما هي مدة التوصيل للطلبات؟',
                'answer' => 'مدة التوصيل تتراوح بين 1-3 أيام عمل داخل عمان، و2-5 أيام عمل لباقي مدن الأردن. يتم تأكيد الطلب عبر الاتصال الهاتفي قبل التوصيل لضمان استلامك للطلب في الوقت المناسب.',
            ],
            [
                'question' => 'هل يمكنني إرجاع المنتج إذا لم يعجبني؟',
                'answer' => 'نعم، نوفر سياسة إرجاع لمدة 7 أيام من تاريخ الاستلام بشرط أن يكون المنتج غير مستخدم وفي عبوته الأصلية. يتم استرداد المبلغ بالكامل أو استبدال المنتج حسب رغبتك.',
            ],
            [
                'question' => 'ما هي أفضل منتجات كورية لعلاج حب الشباب والبقع الداكنة؟',
                'answer' => 'نوصي بسيروم فيتامين C من Skin1004 لتفتيح البقع الداكنة، وتونر Cosrx AHA/BHA لتقشير البشرة وتنظيف المسام، وسيروم النياسيناميد من Axis-Y لتوحيد لون البشرة وتقليل التصبغات.',
            ],
            [
                'question' => 'هل لديكم عروض وخصومات للمشتركين الجدد؟',
                'answer' => 'نعم! استخدم كود WELCOME للحصول على خصم على طلبك الأول. تابعونا على إنستغرام posh_.lifestyle للحصول على عروض حصرية وتخفيضات أسبوعية على أحدث المنتجات.',
            ],
        ];
    }

    return [
        [
            'question' => 'What are the best Korean skincare products for dry skin?',
            'answer' => 'For dry skin in Jordan\'s climate, we recommend hyaluronic acid and ceramide-rich products from Cosrx and Skin1004. An ideal routine starts with a hydrating toner, followed by serum, and finishes with a rich moisturizer. All products are 100% authentic.',
        ],
        [
            'question' => 'Do you ship to all cities in Jordan?',
            'answer' => 'Yes, we ship to all Jordanian cities including Amman, Irbid, Zarqa, Aqaba, and Karak. Free shipping is available for orders over 35 JOD, and a flat 2 JOD fee applies to smaller orders.',
        ],
        [
            'question' => 'How can I verify product authenticity?',
            'answer' => 'All our products are 100% authentic, imported directly from authorized Korean distributors. We carry Cosrx, Skin1004, Axis-Y, Beauty of Joseon, and Anua with full quality guarantees.',
        ],
        [
            'question' => 'What are your delivery timeframes?',
            'answer' => 'Delivery takes 1-3 business days within Amman, and 2-5 business days to other Jordanian cities. We confirm every order by phone before delivery to ensure you receive it at a convenient time.',
        ],
        [
            'question' => 'Can I return a product if I\'m not satisfied?',
            'answer' => 'Yes, we offer a 7-day return policy from the delivery date. Products must be unused and in original packaging. We provide full refunds or exchanges based on your preference.',
        ],
        [
            'question' => 'Which Korean products are best for acne and dark spots?',
            'answer' => 'We recommend Skin1004 Vitamin C serum for brightening dark spots, Cosrx AHA/BHA toner for gentle exfoliation, and Axis-Y Niacinamide serum for evening skin tone and reducing hyperpigmentation.',
        ],
        [
            'question' => 'Do you offer discounts for new customers?',
            'answer' => 'Yes! Use code WELCOME for a discount on your first order. Follow us on Instagram @posh_.lifestyle for exclusive deals and weekly sales on the latest Korean beauty products.',
        ],
    ];
}
