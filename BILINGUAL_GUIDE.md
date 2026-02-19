# Quick Reference: Bilingual System

## 🚀 Files Created

1. `/var/www/html/poshy_store/includes/language.php` - Language system
2. `/var/www/html/poshy_store/includes/language_switcher.php` - Switcher component
3. Updated: `/var/www/html/poshy_store/index.php` - Homepage with bilingual support

## 📖 Usage

### In PHP Files:

```php
// 1. Include language system at top
require_once 'includes/language.php';

// 2. Set HTML attributes
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">

// 3. Add language switcher
<?php include 'includes/language_switcher.php'; ?>

// 4. Use translations
<h1><?= t('welcome') ?></h1>
<button><?= t('add_to_cart') ?></button>
```

### Available Translation Keys:

**Navigation:**
- home, shop, products, categories, cart
- my_account, rewards, my_orders
- login, logout, register

**Products:**
- add_to_cart, buy_now, in_stock, out_of_stock
- price, quantity, description, details, reviews
- original_price, save, discount

**Cart & Checkout:**
- shopping_cart, cart_empty, continue_shopping
- checkout, subtotal, total, remove, update
- apply_coupon, coupon_code
- shipping_address, phone_number, city, address
- place_order, order_summary, use_wallet

**Orders:**
- order_number, order_date, order_status
- order_total, order_details, view_order
- pending, processing, shipped, delivered, cancelled

**Rewards:**
- points, wallet, convert_points
- referral_code, share_code, earn_rewards

**Common:**
- search, filter, sort_by, save_changes
- cancel, confirm, close, loading
- success, error, warning

## 🌐 How Language Switching Works

1. User clicks language button (🇯🇴 or 🇬🇧)
2. URL adds `?lang=ar` or `?lang=en`
3. System saves preference in `$_SESSION['language']`
4. Page reloads with new language
5. All `t()` calls return text in selected language
6. RTL/LTR automatically applied

## ➕ Add More Translations

Edit: `/var/www/html/poshy_store/includes/language.php`

```php
$translations = [
    'ar' => [
        'new_key' => 'النص بالعربية',
    ],
    'en' => [
        'new_key' => 'Text in English',
    ]
];
```

## 🎨 RTL Support

Automatic RTL when Arabic is selected:
- `dir="rtl"` on HTML element
- Text alignment flipped
- Margins/padding reversed
- Custom RTL CSS rules applied

## 🧪 Testing

1. Go to homepage: http://localhost/poshy_store/
2. Look for 🌐 button in navbar
3. Click to switch between العربية ⇄ English
4. Check:
   - Text changes language
   - Layout flips for Arabic (RTL)
   - Button text updates
   - Product names show in correct language

## 📄 Pages to Update

Currently updated:
- ✅ index.php (Homepage)

Need manual update:
- ⏳ cart.php
- ⏳ checkout_page.php
- ⏳ product_detail.php
- ⏳ my_orders.php
- ⏳ points_wallet.php

Use the same pattern shown above to add translations to other pages.
