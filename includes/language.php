<?php
/**
 * Language System for Poshy Lifestyle
 * Supports Arabic and English
 */

// Load central config (DB, SITE_URL, error logging, session)
if (!defined('POSHY_CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}

// Normalize language session key (supports legacy `lang` key)
if (!isset($_SESSION['language']) && isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['ar', 'en'], true)) {
    $_SESSION['language'] = $_SESSION['lang'];
}

// Set default language
if (!isset($_SESSION['language']) || !in_array($_SESSION['language'], ['ar', 'en'], true)) {
    $_SESSION['language'] = 'en'; // Default to English
}
$_SESSION['lang'] = $_SESSION['language'];

// Handle language change
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['ar', 'en'])) {
        $_SESSION['language'] = $lang;
        $_SESSION['lang'] = $lang;

        // Redirect to remove lang parameter from URL (only for normal page GET requests)
        $is_get_request = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET');
        $is_ajax_request = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_api_request = (strpos($request_uri, '/api/') !== false);

        if ($is_get_request && !$is_ajax_request && !$is_api_request && !headers_sent()) {
            $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
            if (!empty($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $params);
                unset($params['lang']);
                if (!empty($params)) {
                    $redirect_url .= '?' . http_build_query($params);
                }
            }

            header("Location: $redirect_url");
            exit();
        }
    }
}

// Get current language
$current_lang = $_SESSION['language'];

/**
 * Generate Arabic-aware product URL
 * In Arabic mode: /منتج/product-slug
 * In English mode: /product-slug
 */
function getProductUrl($slug, $lang = null) {
    global $current_lang;
    $lang = $lang ?? $current_lang;
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    
    if ($lang === 'ar') {
        return $base . '/منتج/' . $slug;
    }
    return $base . '/' . $slug;
}

/**
 * Generate Arabic-aware podcast URL
 */
function getPodcastUrl($slug = null, $lang = null) {
    global $current_lang;
    $lang = $lang ?? $current_lang;
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    
    if ($lang === 'ar') {
        return $base . '/بودكاست' . ($slug ? '/' . $slug : '');
    }
    return $base . '/podcast' . ($slug ? '/' . $slug : '');
}

/**
 * Generate Arabic-aware shop URL
 */
function getShopUrl($lang = null) {
    global $current_lang;
    $lang = $lang ?? $current_lang;
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    
    if ($lang === 'ar') {
        return $base . '/متجر';
    }
    return $base . '/';
}

/**
 * Translation arrays
 */
$translations = [
    'ar' => [
        // Navigation
        'home' => 'الرئيسية',
        'shop' => 'المتجر',
        'products' => 'المنتجات',
        'categories' => 'الفئات',
        'cart' => 'السلة',
        'my_account' => 'حسابي',
        'rewards' => 'المكافآت',
        'my_orders' => 'طلباتي',
        'logout' => 'تسجيل الخروج',
        'login' => 'تسجيل الدخول',
        'register' => 'إنشاء حساب',
        
        // Home Page
        'welcome' => 'مرحباً بكم في',
        'tagline' => 'كل ما تحتاجه في مكان واحد مع منتجاتنا الأصلية',
        'ramadan_greeting' => '🌙 رمضان كريم 🌙',
        'shop_now' => 'تسوق الآن',
        'view_all' => 'عرض الكل',
        'featured_products' => 'منتجات مميزة',
        'new_arrivals' => 'وصل حديثاً',
        'best_sellers' => 'الأكثر مبيعاً',
        
        // Product
        'add_to_cart' => 'أضف إلى السلة',
        'buy_now' => 'اشتر الآن',
        'out_of_stock' => 'نفذت الكمية',
        'in_stock' => 'متوفر',
        'price' => 'السعر',
        'quantity' => 'الكمية',
        'description' => 'الوصف',
        'details' => 'التفاصيل',
        'reviews' => 'التقييمات',
        'original_price' => 'السعر الأصلي',
        'save' => 'وفر',
        'discount' => 'خصم',
        'product_details' => 'تفاصيل المنتج',
        'product_description' => 'وصف المنتج',
        'how_to_use' => 'طريقة الاستخدام',
        'see_in_action' => 'شاهد المنتج',
        'customer_reviews' => 'تقييمات العملاء',
        'write_review' => 'اكتب تقييم',
        'update_review' => 'تحديث تقييمك',
        'back_to_products' => 'العودة للمنتجات',
        
        // Cart
        'shopping_cart' => 'سلة التسوق',
        'cart_empty' => 'سلة التسوق فارغة',
        'continue_shopping' => 'متابعة التسوق',
        'checkout' => 'إتمام الطلب',
        'subtotal' => 'المجموع الفرعي',
        'total' => 'الإجمالي',
        'remove' => 'حذف',
        'update' => 'تحديث',
        'review_items_checkout' => 'راجع منتجاتك قبل إتمام الطلب',
        'add_beautiful_cosmetics' => 'أضف بعض منتجات التجميل الرائعة للبدء!',
        'start_shopping' => 'ابدأ التسوق',
        'out_of_stock_remove' => 'نفذت الكمية - يرجى الحذف',
        'apply_coupon' => 'تطبيق الكوبون',
        'coupon_code' => 'رمز الكوبون',
        'have_coupon' => 'لديك كوبون؟',
        'enter_code' => 'أدخل الرمز',
        'apply' => 'تطبيق',
        'order_summary' => 'ملخص الطلب',
        'total_items' => 'إجمالي المنتجات',
        'proceed_to_checkout' => 'متابعة إلى الدفع',
        'clear_cart' => 'إفراغ السلة',
        'item_in_cart' => 'منتج في السلة',
        'items_in_cart' => 'منتجات في السلة',
        'secure_shopping' => 'تسوق آمن - بياناتك محمية',
        
        // Checkout
        'shipping_address' => 'عنوان الشحن',
        'shipping_details' => 'تفاصيل الشحن',
        'phone_number' => 'رقم الهاتف',
        'city' => 'المدينة',
        'address' => 'العنوان',
        'notes' => 'ملاحظات',
        'payment_method' => 'طريقة الدفع',
        'cash_on_delivery' => 'الدفع عند الاستلام',
        'place_order' => 'تأكيد الطلب',
        'use_wallet' => 'استخدام المحفظة',
        'wallet_balance' => 'رصيد المحفظة',
        'complete_your_order' => 'أكمل طلبك',
        'customer_information' => 'معلومات العميل',
        'full_name' => 'الاسم الكامل',
        'email_address' => 'البريد الإلكتروني',
        'shipping_details_tab' => 'تفاصيل الشحن',
        'gift_order_tab' => 'طلب هدية',
        'standard_delivery' => 'التوصيل القياسي',
        'provide_shipping_details' => 'يرجى تقديم تفاصيل الشحن لتوصيل الطلب',
        'call_confirm_delivery' => 'سنتصل بك لتأكيد تفاصيل التوصيل',
        'enter_your_city' => 'أدخل مدينتك (مثال: عمان، إربد)',
        'required_delivery_routing' => 'مطلوب لتوجيه التوصيل',
        'order_notes' => 'ملاحظات الطلب',
        'optional' => 'اختياري',
        'special_delivery_instructions' => 'أي تعليمات خاصة بالتوصيل، الوقت المفضل، أو تعليقات إضافية...',
        'example_delivery_time' => 'مثال: "يرجى التوصيل بعد الساعة 5 مساءً" أو "اتصل قبل التوصيل"',
        'have_referral_code' => 'لديك رمز إحالة؟',
        'optional_earn_rewards' => 'اختياري - اكسب مكافآت!',
        'enter_friend_referral' => 'أدخل رمز إحالة صديقك (مثال: ABC123)',
        'friend_gets_200_points' => 'صديقك يحصل على 200 نقطة عند استخدام رمزه!',
        'sending_gift' => 'إرسال هدية؟',
        'gift_unforgettable' => 'سنجعلها لا تُنسى مع تغليف جميل ورسالتك القلبية!',
        'gift_details' => 'تفاصيل الهدية',
        'recipient_name' => 'اسم المستلم',
        'special_person_receive' => 'الشخص المميز الذي سيتلقى هذه الهدية',
        'gift_message' => 'رسالة الهدية',
        'write_heartfelt_message' => 'اكتب رسالتك القلبية هنا... عبر عن حبك، امتنانك، أو أفضل أمنياتك!',
        'present_message_gift' => 'سنقدم رسالتك بشكل جميل مع الهدية',
        'delivery_address' => 'عنوان التوصيل',
        'contact_delivery' => 'رقم الاتصال للتوصيل',
        'enter_delivery_city' => 'أدخل مدينة التوصيل (مثال: عمان، إربد)',
        'complete_delivery_address' => 'أدخل عنوان التوصيل الكامل (الشارع، المبنى، الطابق)...',
        'full_address_landmarks' => 'يرجى تقديم العنوان الكامل بما في ذلك اسم الشارع، رقم المبنى، الطابق، وأي علامات بارزة',
        'delivery_notes' => 'ملاحظات التوصيل',
        'special_delivery_preferred_time' => 'تعليمات التوصيل الخاصة، الوقت المفضل للتوصيل، أو أي رسالة لفريق التوصيل...',
        'example_deliver_after_6pm' => 'مثال: "التوصيل بعد 6 مساءً" أو "رمز البوابة: 1234"',
        'items_in_cart_singular' => 'منتج في السلة',
        'items_in_cart_plural' => 'منتجات في السلة',
        'subtotal_label' => 'المجموع الفرعي',
        'coupon_label' => 'كوبون',
        'shipping_label' => 'الشحن',
        'free' => 'مجاني',
        'your_wallet_balance' => 'رصيد محفظتك',
        'use_wallet_balance_order' => 'استخدام رصيد المحفظة لهذا الطلب',
        'wallet_covers_full_amount' => 'رصيد محفظتك يغطي المبلغ الكامل للطلب!',
        'wallet_applied_pay_remaining' => 'سيتم تطبيق رصيد محفظتك. ستدفع المتبقي',
        'total_to_pay' => 'الإجمالي المطلوب دفعه',
        'wallet_credit_applied' => 'تم تطبيق رصيد المحفظة',
        'confirm_order' => 'تأكيد الطلب',
        'back_to_cart' => 'العودة للسلة',
        'secure_checkout_protected' => 'دفع آمن - معلوماتك محمية',
        
        // Orders
        'order_number' => 'رقم الطلب',
        'order_date' => 'تاريخ الطلب',
        'order_status' => 'حالة الطلب',
        'order_total' => 'إجمالي الطلب',
        'order_details' => 'تفاصيل الطلب',
        'view_order' => 'عرض الطلب',
        'no_orders' => 'لا توجد طلبات',
        'total_orders' => 'إجمالي الطلبات',
        
        // Status
        'pending' => 'قيد المعالجة',
        'processing' => 'قيد التحضير',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        
        // Rewards
        'points' => 'النقاط',
        'wallet' => 'المحفظة',
        'points_wallet' => 'النقاط والمحفظة',
        'convert_points' => 'تحويل النقاط',
        'points_balance' => 'رصيد النقاط',
        'referral_code' => 'رمز الإحالة',
        'share_code' => 'شارك الرمز',
        'earn_rewards' => 'اكسب المكافآت',
        'my_rewards' => 'مكافآتي',
        'earn_points_unlock_rewards' => 'اكسب النقاط، افتح المكافآت، تسوق بذكاء',
        'loyalty_points' => 'نقاط الولاء',
        'how_to_earn_points' => 'كيفية كسب النقاط:',
        'get_x_points_per_jod' => 'احصل على %d نقطة لكل 1 د.أ تنفقه',
        'convert_x_points_to_jod' => 'حول %d نقطة إلى 1 د.أ رصيد محفظة',
        'minimum_conversion' => 'تحتاج على الأقل %d نقطة للتحويل',
        'wallet_balance' => 'رصيد المحفظة',
        'wallet_benefits' => 'فوائد المحفظة:',
        'use_wallet_on_purchase' => 'استخدم رصيد محفظتك في أي عملية شراء مستقبلية',
        'convert_points_anytime' => 'حول نقاطك إلى اعتمادات المحفظة في أي وقت',
        'wallet_never_expires' => 'رصيد محفظتك لا ينتهي أبدًا',
        'refer_friends_earn_points' => 'أحل الأصدقاء واكسب النقاط!',
        'share_referral_code' => 'شارك رمز الإحالة الفريد الخاص بك مع الأصدقاء.',
        'when_friends_use_code' => 'عندما يستخدمون رمزك عند الدفع،',
        'you_get_200_points' => 'ستحصل على 200 نقطة!',
        'youve_referred_friends' => 'لقد أحلت %d من الأصدقاء',
        'earned_from_referrals' => 'كسبت %d نقطة من الإحالات',
        'your_referral_code' => 'رمز الإحالة الخاص بك',
        'copy' => 'نسخ',
        'copied' => 'تم النسخ!',
        'share_with_friends' => 'شارك هذا الرمز مع أصدقائك!',
        'convert_points_to_wallet' => 'تحويل النقاط إلى رصيد المحفظة',
        'how_many_points_convert' => 'كم عدد النقاط التي تريد تحويلها؟',
        'enter_points' => 'أدخل النقاط (الحد الأدنى %d)',
        'you_will_receive' => 'سوف تتلقى:',
        'convert_now' => 'حول الآن',
        'transaction_history' => 'سجل المعاملات',
        'points_history' => 'سجل النقاط',
        'wallet_history' => 'سجل المحفظة',
        'no_points_transactions' => 'لا توجد معاملات نقاط حتى الآن. ابدأ التسوق لكسب النقاط!',
        'start_shopping_earn_points' => 'ابدأ التسوق لكسب النقاط!',
        'no_wallet_transactions' => 'لا توجد معاملات محفظة حتى الآن. حول النقاط لإضافتها إلى محفظتك!',
        'convert_points_to_add_wallet' => 'حول النقاط لإضافتها إلى محفظتك!',
        'balance' => 'الرصيد',
        'processing_conversion' => 'جاري معالجة التحويل...',
        'converted_x_points_to_y_jod' => 'تم تحويل %d نقطة إلى %s',
        'network_error_try_again' => 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.',
        'failed_copy_manual' => 'فشل نسخ رمز الإحالة. يرجى نسخه يدويًا.',
        'pts' => 'نقطة',
        'you_have_points' => 'لديك',
        'convert_points_save' => 'حول نقاطك إلى رصيد محفظة ووفّر في طلبك القادم',
        'points_converted_successfully' => 'تم تحويل النقاط بنجاح!',
        'invalid_points_amount' => 'كمية نقاط غير صالحة',
        'points_system_disabled' => 'نظام النقاط غير مفعل حاليًا',
        'minimum_conversion_is' => 'الحد الأدنى للتحويل هو %d نقطة',
        'insufficient_points_have' => 'نقاط غير كافية. لديك %d نقطة.',
        'error_converting_points' => 'حدث خطأ أثناء تحويل النقاط',
        
        // Common
        'search' => 'بحث',
        'search_products' => 'ابحث عن منتجات...',
        'search_by_name' => '🔍 ابحث عن المنتجات بالاسم...',
        'searching_for' => 'البحث عن:',
        'search_results_for' => 'نتائج البحث عن:',
        'clear_search' => 'إلغاء البحث',
        'no_products_found' => 'لم يتم العثور على منتجات',
        'try_searching_else' => 'جرب البحث عن شيء آخر أو تصفح جميع الفئات',
        'view_all_products' => 'عرض جميع المنتجات',
        'filter' => 'تصفية',
        'sort_by' => 'ترتيب حسب',
        'save_changes' => 'حفظ التغييرات',
        'cancel' => 'إلغاء',
        'confirm' => 'تأكيد',
        'close' => 'إغلاق',
        'loading' => 'جاري التحميل...',
        'success' => 'نجح',
        'error' => 'خطأ',
        'warning' => 'تحذير',
        'yes' => 'نعم',
        'no' => 'لا',
        'brand' => 'العلامة التجارية',
        'category' => 'الفئة',
        'stock_status' => 'حالة المخزون',
        'available_units' => 'الوحدات المتوفرة',
        'product_name' => 'اسم المنتج',
        
        // Footer
        'about_us' => 'عن بوشي',
        'contact_us' => 'اتصل بنا',
        'privacy_policy' => 'سياسة الخصوصية',
        'terms_conditions' => 'الشروط والأحكام',
        'follow_us' => 'تابعنا',
        'all_rights_reserved' => 'جميع الحقوق محفوظة',
        
        // Messages
        'added_to_cart' => 'تم الإضافة إلى السلة',
        'removed_from_cart' => 'تم الحذف من السلة',
        'order_placed' => 'تم تأكيد طلبك بنجاح',
        'please_login' => 'يرجى تسجيل الدخول',
        'invalid_quantity' => 'كمية غير صالحة',
        
        // Auth Pages
        'sign_in' => 'تسجيل الدخول',
        'sign_up' => 'إنشاء حساب',
        'remember_me' => 'تذكرني',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'phone_number' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'confirm_password' => 'تأكيد كلمة المرور',
        'enter_first_name' => 'أدخل اسمك الأول',
        'enter_last_name' => 'أدخل اسم العائلة',
        'enter_phone_number' => 'أدخل رقم هاتفك',
        'enter_email' => 'أدخل بريدك الإلكتروني',
        'enter_password' => 'أدخل كلمة المرور',
        'confirm_your_password' => 'أكد كلمة المرور',
        'continue_with_google' => 'تابع مع جوجل',
        'continue_with_facebook' => 'تابع مع فيسبوك',
        'or' => 'أو',
        'sign_in_with_email' => 'تسجيل الدخول بالبريد الإلكتروني',
        'already_have_account' => 'لديك حساب بالفعل؟',
        'dont_have_account' => 'ليس لديك حساب؟',
        'back_to_store' => 'العودة إلى المتجر',
        'registration_successful' => 'تم التسجيل بنجاح!',
        'sign_in_now' => 'سجل دخولك الآن',
        'to_start_shopping' => 'لبدء التسوق',
        'email_password_required' => 'البريد الإلكتروني وكلمة المرور مطلوبان',
        'database_connection_failed' => 'فشل الاتصال بقاعدة البيانات',
        'database_error' => 'خطأ في قاعدة البيانات',
        'invalid_email_or_password' => 'بريد إلكتروني أو كلمة مرور غير صحيحة',
        'account_created_with_oauth' => 'تم إنشاء هذا الحساب باستخدام %s. يرجى تسجيل الدخول بـ %s.',
        'social_media' => 'وسائل التواصل الاجتماعي',
        'first_name_required' => 'الاسم الأول مطلوب',
        'last_name_required' => 'اسم العائلة مطلوب',
        'phone_number_required' => 'رقم الهاتف مطلوب',
        'valid_email_required' => 'بريد إلكتروني صالح مطلوب',
        'password_required' => 'كلمة المرور مطلوبة',
        'password_min_length' => 'يجب أن تتكون كلمة المرور من 6 أحرف على الأقل',
        'passwords_do_not_match' => 'كلمتا المرور غير متطابقتين',
        'email_already_registered' => 'البريد الإلكتروني مسجل بالفعل',
        'registration_failed' => 'فشل التسجيل',
        'all' => 'الكل',
        'all_products' => 'جميع المنتجات',
        'item_removed' => 'تم حذف المنتج',
        'quantity_updated' => 'تم تحديث الكمية',
        'please_enter_coupon' => 'الرجاء إدخال رمز الكوبون',
        'cart_is_empty' => 'السلة فارغة',
        'clear_cart_confirm' => 'هل تريد إفراغ جميع المنتجات من السلة؟',
        'failed_update_quantity' => 'فشل تحديث الكمية',
        'network_error' => 'خطأ في الشبكة. حاول مرة أخرى.',
        'cart_cleared' => 'تم إفراغ السلة',
        'maximum_stock_reached' => 'تم الوصول إلى الحد الأقصى للمخزون',
        'discover_premium' => 'اكتشف مجموعتنا المميزة',
        'applying' => 'جاري التطبيق...',
        'failed_remove_coupon' => 'فشل حذف الكوبون',
        'complete' => 'تم',
        'out_of_5' => 'من 5',
        'reviews_count' => 'تقييم',
        'you_save' => 'وفر',
        'units_available' => 'وحدة متاحة',
        'in_cart_label' => 'في السلة',
        'decrease_quantity' => 'تقليل الكمية',
        'increase_quantity' => 'زيادة الكمية',
        'sign_in_purchase' => 'تسجيل الدخول للشراء',
        'shop_button' => 'تسوق',
        'rating_label' => 'التقييم *',
        'your_feedback' => 'ملاحظاتك *',
        'share_experience' => 'شارك تجربتك مع هذا المنتج...',
        'submit_review_btn' => 'إرسال التقييم',
        'sign_in_to_review' => 'لكتابة تقييم',
        'no_reviews_yet' => 'لا تقييمات بعد. كن أول من يقيم هذا المنتج!',
        'recent_feedback' => 'آخر 5 تقييمات',
        'failed_add_to_cart' => 'فشل إضافة إلى السلة',
        'product_removed' => 'تم حذف المنتج من السلة',
        'quantity_increased' => 'تم زيادة الكمية',
        'quantity_decreased' => 'تم تقليل الكمية',
        'failed_update_qty' => 'فشل تحديث الكمية',
        'failed_submit_review' => 'فشل إرسال التقييم',
        'added_to_cart_modal' => 'تم الإضافة إلى السلة',
        'quantity_in_cart' => 'الكمية في السلة',
        'you_may_like' => 'قد يعجبك أيضاً',
        'no_recommendations' => 'لا توجد توصيات متاحة',
        'go_to_cart' => 'اذهب إلى السلة',
        'continue_shopping_btn' => 'متابعة التسوق',
        'total_items_cart' => 'إجمالي المنتجات في السلة',
        'added_to_cart_success' => 'تمت الأضافة إلى السلة!',
        'add_button' => 'إضافة',
        'view_button' => 'عرض',
        'default_description' => 'منتجات تجميل عالية الجودة من مجموعة Poshy Store. تم اختيار هذا المنتج بعناية ليلبي أعلى معايير الجودة والأناقة.',
        'howto_step1' => 'اقرأ جميع التعليمات والتحذيرات قبل الاستخدام',
        'howto_step2' => 'ضعه حسب التعليمات الموجودة على العبوة',
        'howto_step3' => 'للحصول على أفضل النتائج، استخدمه بانتظام كما هو موصى به',
        'howto_step4' => 'خزن في مكان بارد وجاف بعيداً عن أشعة الشمس المباشرة',
        'howto_step5' => 'توقف عن الاستخدام إذا حدث تهيج',
        'refer_packaging' => 'للتعليمات المحددة، يرجى الرجوع إلى عبوة المنتج أو التواصل مع خدمة العملاء.',
        
        // Chatbot
        'chatbot_assistant' => 'مساعد Poshy',
        'chatbot_welcome' => '👋 مرحباً! أهلاً بك في Poshy Store! كيف يمكنني مساعدتك اليوم؟',
        'chatbot_choose' => 'اختر سؤالاً أدناه:',
        'chatbot_hours_q' => '⌚️ ساعات العمل؟',
        'chatbot_hours_a' => 'نحن نعمل 24/7 عبر الإنترنت! خدمة العملاء متاحة من الإثنين إلى الجمعة، 9 صباحاً - 6 مساءً.',
        'chatbot_shipping_q' => '🚚 معلومات الشحن؟',
        'chatbot_shipping_a' => 'نوفر شحناً مجانياً للطلبات التي تزيد عن 50 دينار! التوصيل القياسي يستغرق 3-5 أيام عمل. الشحن السريع متاح بقيمة 9.99 دينار.',
        'chatbot_returns_q' => '↩️ سياسة الإرجاع؟',
        'chatbot_returns_a' => 'لدينا سياسة إرجاع 30 يوماً. يجب أن تكون المنتجات غير مستخدمة وفي التغليف الأصلي. يتم معالجة المبالغ المستردة خلال 5-7 أيام عمل.',
        'chatbot_payment_q' => '💳 طرق الدفع؟',
        'chatbot_payment_a' => 'نقبل جميع بطاقات الائتمان الرئيسية (Visa، Mastercard، Amex)، وPayPal، وApple Pay. جميع المعاملات آمنة ومشفرة.',
        'chatbot_contact_q' => '📞 اتصل بنا؟',
        'chatbot_contact_a' => 'البريد الإلكتروني: support@poshystore.com<br>الهاتف: +962 6 123 4567<br>نرد خلال 24 ساعة!',
        'chatbot_discount_q' => '🎁 خصومات؟',
        'chatbot_discount_a' => 'العملاء الجدد يحصلون على خصم 10%! اشترك في نشرتنا الإخبارية لتلقي عروض حصرية ورموز ترويجية.',
        'currency' => 'د.أ',
    ],
    
    'en' => [
        // Navigation
        'home' => 'Home',
        'shop' => 'Shop',
        'products' => 'Products',
        'categories' => 'Categories',
        'cart' => 'Cart',
        'my_account' => 'My Account',
        'rewards' => 'Rewards',
        'my_orders' => 'My Orders',
        'logout' => 'Logout',
        'login' => 'Login',
        'register' => 'Register',
        
        // Home Page
        'welcome' => 'Welcome to',
        'tagline' => 'All what you need in one place with our authentic products',
        'ramadan_greeting' => '🌙 Ramadan Kareem 🌙',
        'shop_now' => 'Shop Now',
        'view_all' => 'View All',
        'featured_products' => 'Featured Products',
        'new_arrivals' => 'New Arrivals',
        'best_sellers' => 'Best Sellers',
        
        // Product
        'add_to_cart' => 'Add to Cart',
        'buy_now' => 'Buy Now',
        'out_of_stock' => 'Out of Stock',
        'in_stock' => 'In Stock',
        'price' => 'Price',
        'quantity' => 'Quantity',
        'description' => 'Description',
        'details' => 'Details',
        'reviews' => 'Reviews',
        'original_price' => 'Original Price',
        'save' => 'Save',
        'discount' => 'Discount',
        'product_details' => 'Product Details',
        'product_description' => 'Product Description',
        'how_to_use' => 'How to Use',
        'see_in_action' => 'See in Action',
        'customer_reviews' => 'Customer Reviews',
        'write_review' => 'Write a Review',
        'update_review' => 'Update Your Review',
        'back_to_products' => 'Back to Products',
        
        // Cart
        'shopping_cart' => 'Shopping Cart',
        'cart_empty' => 'Your cart is empty',
        'continue_shopping' => 'Continue Shopping',
        'checkout' => 'Checkout',
        'subtotal' => 'Subtotal',
        'total' => 'Total',
        'remove' => 'Remove',
        'update' => 'Update',
        'review_items_checkout' => 'Review your items before checkout',
        'add_beautiful_cosmetics' => 'Add some beautiful cosmetics to get started!',
        'start_shopping' => 'Start Shopping',
        'out_of_stock_remove' => 'Out of stock - please remove',
        'apply_coupon' => 'Apply Coupon',
        'coupon_code' => 'Coupon Code',
        'have_coupon' => 'Have a Coupon?',
        'enter_code' => 'Enter code',
        'apply' => 'Apply',
        'order_summary' => 'Order Summary',
        'total_items' => 'Total Items',
        'proceed_to_checkout' => 'Proceed to Checkout',
        'clear_cart' => 'Clear Cart',
        'item_in_cart' => 'Item in Cart',
        'items_in_cart' => 'Items in Cart',
        'secure_shopping' => 'Secure shopping - Your data is protected',
        
        // Checkout
        'shipping_address' => 'Shipping Address',
        'shipping_details' => 'Shipping Details',
        'phone_number' => 'Phone Number',
        'city' => 'City',
        'address' => 'Address',
        'notes' => 'Notes',
        'payment_method' => 'Payment Method',
        'cash_on_delivery' => 'Cash on Delivery',
        'place_order' => 'Place Order',
        'use_wallet' => 'Use Wallet',
        'wallet_balance' => 'Wallet Balance',
        'complete_your_order' => 'Complete your order',
        'customer_information' => 'Customer Information',
        'full_name' => 'Full Name',
        'email_address' => 'Email Address',
        'shipping_details_tab' => 'Shipping Details',
        'gift_order_tab' => 'Gift Order',
        'standard_delivery' => 'Standard Delivery',
        'provide_shipping_details' => 'Please provide your shipping details for order delivery',
        'call_confirm_delivery' => "We'll call you to confirm delivery details",
        'enter_your_city' => 'Enter your city (e.g., Amman, Irbid)',
        'required_delivery_routing' => 'Required for delivery routing',
        'order_notes' => 'Order Notes',
        'optional' => 'Optional',
        'special_delivery_instructions' => 'Any special delivery instructions, preferred time, or additional comments...',
        'example_delivery_time' => 'Example: "Please deliver after 5 PM" or "Call before delivery"',
        'have_referral_code' => 'Have a Referral Code?',
        'optional_earn_rewards' => 'Optional - Earn rewards!',
        'enter_friend_referral' => "Enter friend's referral code (e.g., ABC123)",
        'friend_gets_200_points' => 'Your friend gets 200 points when you use their code!',
        'sending_gift' => 'Sending a gift?',
        'gift_unforgettable' => "We'll make it unforgettable with beautiful wrapping and your heartfelt message!",
        'gift_details' => 'Gift Details',
        'recipient_name' => 'Recipient Name',
        'special_person_receive' => "The special person who'll receive this gift",
        'gift_message' => 'Gift Message',
        'write_heartfelt_message' => 'Write your heartfelt message here... Express your love, gratitude, or best wishes!',
        'present_message_gift' => "We'll beautifully present your message with the gift",
        'delivery_address' => 'Delivery Address',
        'contact_delivery' => 'Contact number for delivery',
        'enter_delivery_city' => 'Enter delivery city (e.g., Amman, Irbid)',
        'complete_delivery_address' => 'Enter the complete delivery address (street, building, floor)...',
        'full_address_landmarks' => 'Please provide full address including street, building number, floor, and landmarks',
        'delivery_notes' => 'Delivery Notes',
        'special_delivery_preferred_time' => 'Special delivery instructions, preferred delivery time, or any message for our delivery team...',
        'example_deliver_after_6pm' => 'Example: "Deliver after 6 PM" or "Gate code: 1234"',
        'items_in_cart_singular' => 'Item in Cart',
        'items_in_cart_plural' => 'Items in Cart',
        'subtotal_label' => 'Subtotal',
        'coupon_label' => 'Coupon',
        'shipping_label' => 'Shipping',
        'free' => 'FREE',
        'your_wallet_balance' => 'Your Wallet Balance',
        'use_wallet_balance_order' => 'Use wallet balance for this order',
        'wallet_covers_full_amount' => 'Your wallet balance covers the full order amount!',
        'wallet_applied_pay_remaining' => "Your wallet balance will be applied. You'll pay the remaining",
        'total_to_pay' => 'Total to Pay',
        'wallet_credit_applied' => 'Wallet credit applied',
        'confirm_order' => 'Confirm Order',
        'back_to_cart' => 'Back to Cart',
        'secure_checkout_protected' => 'Secure checkout - Your information is protected',
        
        // Orders
        'order_number' => 'Order Number',
        'order_date' => 'Order Date',
        'order_status' => 'Order Status',
        'order_total' => 'Order Total',
        'order_details' => 'Order Details',
        'view_order' => 'View Order',
        'no_orders' => 'No orders yet',
        'total_orders' => 'Total Orders',
        
        // Status
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        
        // Rewards
        'points' => 'Points',
        'wallet' => 'Wallet',
        'points_wallet' => 'Points & Wallet',
        'convert_points' => 'Convert Points',
        'points_balance' => 'Points Balance',
        'referral_code' => 'Referral Code',
        'share_code' => 'Share Code',
        'earn_rewards' => 'Earn Rewards',
        'my_rewards' => 'My Rewards',
        'earn_points_unlock_rewards' => 'Earn points, unlock rewards, shop smarter',
        'loyalty_points' => 'Loyalty Points',
        'how_to_earn_points' => 'How to Earn Points:',
        'get_x_points_per_jod' => 'Get %d points for every 1 JOD you spend',
        'convert_x_points_to_jod' => 'Convert %d points to 1 JOD wallet credit',
        'minimum_conversion' => 'Need at least %d points to convert',
        'wallet_balance' => 'Wallet Balance',
        'wallet_benefits' => 'Wallet Benefits:',
        'use_wallet_on_purchase' => 'Use your wallet balance on any future purchase',
        'convert_points_anytime' => 'Convert your points to wallet credits anytime',
        'wallet_never_expires' => 'Your wallet balance never expires',
        'refer_friends_earn_points' => 'Refer Friends & Earn Points!',
        'share_referral_code' => 'Share your unique referral code with friends.',
        'when_friends_use_code' => 'When they use your code at checkout,',
        'you_get_200_points' => 'you get 200 points!',
        'youve_referred_friends' => "You've referred %d friends",
        'earned_from_referrals' => 'Earned %d points from referrals',
        'your_referral_code' => 'Your Referral Code',
        'copy' => 'Copy',
        'copied' => 'Copied!',
        'share_with_friends' => 'Share this code with your friends!',
        'convert_points_to_wallet' => 'Convert Points to Wallet Balance',
        'how_many_points_convert' => 'How many points do you want to convert?',
        'enter_points' => 'Enter points (minimum %d)',
        'you_will_receive' => 'You will receive:',
        'convert_now' => 'Convert Now',
        'transaction_history' => 'Transaction History',
        'points_history' => 'Points History',
        'wallet_history' => 'Wallet History',
        'no_points_transactions' => 'No points transactions yet. Start shopping to earn points!',
        'start_shopping_earn_points' => 'Start shopping to earn points!',
        'no_wallet_transactions' => 'No wallet transactions yet. Convert points to add to your wallet!',
        'convert_points_to_add_wallet' => 'Convert points to add to your wallet!',
        'balance' => 'Balance',
        'processing_conversion' => 'Processing conversion...',
        'converted_x_points_to_y_jod' => 'Converted %d points to %s',
        'network_error_try_again' => 'Network error. Please try again.',
        'failed_copy_manual' => 'Failed to copy referral code. Please copy it manually.',
        'pts' => 'pts',
        'you_have_points' => 'You have',
        'convert_points_save' => 'Convert your points to wallet balance and save on your next purchase',
        'points_converted_successfully' => 'Points converted successfully!',
        'invalid_points_amount' => 'Invalid points amount',
        'points_system_disabled' => 'Points system is currently disabled',
        'minimum_conversion_is' => 'Minimum conversion is %d points',
        'insufficient_points_have' => 'Insufficient points. You have %d points.',
        'error_converting_points' => 'Error occurred while converting points',
        
        // Common
        'search' => 'Search',
        'search_products' => 'Search products...',
        'search_by_name' => '🔍 Search for products by name...',
        'searching_for' => 'Searching for:',
        'search_results_for' => 'Search results for:',
        'clear_search' => 'Clear Search',
        'no_products_found' => 'No products found',
        'try_searching_else' => 'Try searching for something else or browse all categories',
        'view_all_products' => 'View All Products',
        'filter' => 'Filter',
        'sort_by' => 'Sort By',
        'save_changes' => 'Save Changes',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'close' => 'Close',
        'loading' => 'Loading...',
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'yes' => 'Yes',
        'no' => 'No',
        'brand' => 'Brand',
        'category' => 'Category',
        'stock_status' => 'Stock Status',
        'available_units' => 'Available Units',
        'product_name' => 'Product Name',
        
        // Footer
        'about_us' => 'About Us',
        'contact_us' => 'Contact Us',
        'privacy_policy' => 'Privacy Policy',
        'terms_conditions' => 'Terms & Conditions',
        'follow_us' => 'Follow Us',
        'all_rights_reserved' => 'All Rights Reserved',
        
        // Messages
        'added_to_cart' => 'Added to cart',
        'removed_from_cart' => 'Removed from cart',
        'order_placed' => 'Your order has been placed successfully',
        'please_login' => 'Please login',
        'invalid_quantity' => 'Invalid quantity',
        
        // Auth Pages
        'sign_in' => 'Sign In',
        'sign_up' => 'Sign Up',
        'remember_me' => 'Remember me',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'phone_number' => 'Phone Number',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'enter_first_name' => 'Enter your first name',
        'enter_last_name' => 'Enter your last name',
        'enter_phone_number' => 'Enter your phone number',
        'enter_email' => 'Enter your email address',
        'enter_password' => 'Enter your password',
        'confirm_your_password' => 'Confirm your password',
        'continue_with_google' => 'Continue with Google',
        'continue_with_facebook' => 'Continue with Facebook',
        'or' => 'OR',
        'sign_in_with_email' => 'Sign In with Email',
        'already_have_account' => 'Already have an account?',
        'dont_have_account' => "Don't have an account?",
        'back_to_store' => 'Back to Store',
        'registration_successful' => 'Registration successful!',
        'sign_in_now' => 'Sign in now',
        'to_start_shopping' => 'to start shopping',
        'email_password_required' => 'Email and password are required',
        'database_connection_failed' => 'Database connection failed',
        'database_error' => 'Database error',
        'invalid_email_or_password' => 'Invalid email or password',
        'account_created_with_oauth' => 'This account was created using %s. Please sign in with %s.',
        'social_media' => 'social media',
        'first_name_required' => 'First name is required',
        'last_name_required' => 'Last name is required',
        'phone_number_required' => 'Phone number is required',
        'valid_email_required' => 'Valid email is required',
        'password_required' => 'Password is required',
        'password_min_length' => 'Password must be at least 6 characters',
        'passwords_do_not_match' => 'Passwords do not match',
        'email_already_registered' => 'Email already registered',
        'registration_failed' => 'Registration failed',
        'all' => 'All',
        'all_products' => 'All Products',
        'item_removed' => 'Item removed from cart',
        'quantity_updated' => 'Quantity updated',
        'please_enter_coupon' => 'Please enter a coupon code',
        'cart_is_empty' => 'Cart is empty',
        'clear_cart_confirm' => 'Clear all items from cart?',
        'failed_update_quantity' => 'Failed to update quantity',
        'network_error' => 'Network error. Please try again.',
        'cart_cleared' => 'Cart cleared',
        'maximum_stock_reached' => 'Maximum stock reached',
        'discover_premium' => 'Discover our premium collection',
        'applying' => 'Applying...',
        'failed_remove_coupon' => 'Failed to remove coupon',
        'complete' => 'Complete',
        'out_of_5' => 'out of 5',
        'reviews_count' => 'reviews',
        'you_save' => 'You save',
        'units_available' => 'units available',
        'in_cart_label' => 'In Cart',
        'decrease_quantity' => 'Decrease quantity',
        'increase_quantity' => 'Increase quantity',
        'sign_in_purchase' => 'Sign in to Purchase',
        'shop_button' => 'Shop',
        'rating_label' => 'Rating *',
        'your_feedback' => 'Your Feedback *',
        'share_experience' => 'Share your experience with this product...',
        'submit_review_btn' => 'Submit Review',
        'sign_in_to_review' => 'to write a review',
        'no_reviews_yet' => 'No reviews yet. Be the first to review this product!',
        'recent_feedback' => 'Recent Feedback (Last 5 Reviews)',
        'failed_add_to_cart' => 'Failed to add to cart',
        'product_removed' => 'Product removed from cart',
        'quantity_increased' => 'Quantity increased',
        'quantity_decreased' => 'Quantity decreased',
        'failed_update_qty' => 'Failed to update quantity',
        'failed_submit_review' => 'Failed to submit review',
        'added_to_cart_modal' => 'Added to Cart',
        'quantity_in_cart' => 'Quantity in Cart',
        'you_may_like' => 'You May Also Like',
        'no_recommendations' => 'No recommendations available',
        'go_to_cart' => 'Go to Cart',
        'continue_shopping_btn' => 'Continue Shopping',
        'total_items_cart' => 'Total Items in Cart',
        'added_to_cart_success' => 'added to cart!',
        'add_button' => 'Add',
        'view_button' => 'View',
        'default_description' => 'Premium quality cosmetics from Poshy Store collection. This product is carefully selected to meet the highest standards of quality and elegance.',
        'howto_step1' => 'Read all product instructions and warnings before use',
        'howto_step2' => 'Apply as directed on the packaging',
        'howto_step3' => 'For best results, use consistently as recommended',
        'howto_step4' => 'Store in a cool, dry place away from direct sunlight',
        'howto_step5' => 'Discontinue use if irritation occurs',
        'refer_packaging' => 'For specific instructions, please refer to the product packaging or consult with our customer service.',
        
        // Chatbot
        'chatbot_assistant' => 'Poshy Assistant',
        'chatbot_welcome' => '👋 Hello! Welcome to Poshy Store! How can I help you today?',
        'chatbot_choose' => 'Choose a question below:',
        'chatbot_hours_q' => '⌚️ Store Hours?',
        'chatbot_hours_a' => 'We are open 24/7 online! Our customer support is available Monday-Friday, 9 AM - 6 PM.',
        'chatbot_shipping_q' => '🚚 Shipping Info?',
        'chatbot_shipping_a' => 'We offer free shipping on orders over $50! Standard delivery takes 3-5 business days. Express shipping is available for $9.99.',
        'chatbot_returns_q' => '↩️ Return Policy?',
        'chatbot_returns_a' => 'We have a 30-day return policy. Items must be unused and in original packaging. Refunds are processed within 5-7 business days.',
        'chatbot_payment_q' => '💳 Payment Methods?',
        'chatbot_payment_a' => 'We accept all major credit cards (Visa, Mastercard, Amex), PayPal, and Apple Pay. All transactions are secure and encrypted.',
        'chatbot_contact_q' => '📞 Contact Us?',
        'chatbot_contact_a' => 'Email: support@poshystore.com<br>Phone: +962 6 123 4567<br>We respond within 24 hours!',
        'chatbot_discount_q' => '🎁 Discounts?',
        'chatbot_discount_a' => 'First-time customers get 10% off! Sign up for our newsletter to receive exclusive deals and promotional codes.',
        'currency' => 'JOD',
    ]
];

/**
 * Get translation
 * 
 * @param string $key Translation key
 * @return string Translated text
 */
function t($key) {
    global $translations, $current_lang;
    
    if (isset($translations[$current_lang][$key])) {
        return $translations[$current_lang][$key];
    }
    
    // Fallback to English
    if (isset($translations['en'][$key])) {
        return $translations['en'][$key];
    }
    
    // Return key if translation not found
    return $key;
}

/**
 * Check if current language is RTL
 * 
 * @return bool
 */
function isRTL() {
    global $current_lang;
    return $current_lang === 'ar';
}

/**
 * Get opposite language
 * 
 * @return string
 */
function getOtherLang() {
    global $current_lang;
    return $current_lang === 'ar' ? 'en' : 'ar';
}

/**
 * Get language name
 * 
 * @param string $lang Language code
 * @return string Language name
 */
function getLangName($lang) {
    return $lang === 'ar' ? 'العربية' : 'English';
}
