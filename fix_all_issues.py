#!/usr/bin/env python3
"""
Comprehensive fix script for Poshy Store VPS.
Applies all fixes directly on the server.
"""
import os
import subprocess
import sys

WEBROOT = "/var/www/html"

def read_file(path):
    with open(path, "r", encoding="utf-8") as f:
        return f.read()

def write_file(path, content):
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)
    print(f"  [OK] {os.path.relpath(path, WEBROOT)}")

def run_sql(query):
    cmd = ['mysql', '-u', 'poshy_user', '-pPoshy2026secure', 'poshy_db', '-e', query]
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"  [SQL ERROR] {result.stderr.strip()}")
    return result.stdout

# ============================================================
# 1. MIRROR ARABIC FROM ENGLISH IN DATABASE
# ============================================================
print("\n=== 1. Mirroring Arabic from English in DB ===")

run_sql("UPDATE products SET name_ar = name_en WHERE name_ar != name_en OR name_ar IS NULL;")
print("  [OK] products.name_ar = products.name_en")

run_sql("UPDATE products SET short_description_ar = short_description_en WHERE short_description_ar IS NULL OR short_description_ar != short_description_en;")
print("  [OK] products.short_description_ar = short_description_en")

run_sql("UPDATE products SET how_to_use_ar = how_to_use_en WHERE how_to_use_en IS NOT NULL AND (how_to_use_ar IS NULL OR how_to_use_ar != how_to_use_en);")
print("  [OK] products.how_to_use_ar = how_to_use_en")

# Categories - set Arabic names to English
run_sql("UPDATE categories SET name_ar = name_en;")
print("  [OK] categories.name_ar = name_en")

run_sql("UPDATE subcategories SET name_ar = name_en;")
print("  [OK] subcategories.name_ar = name_en")

# ============================================================
# 2. FIX POPUP RECOMMENDED PRODUCT LINK (/poshy_store/ → /)
# ============================================================
print("\n=== 2. Fix popup recommended product links ===")

path = os.path.join(WEBROOT, "pages/shop/product_detail.php")
content = read_file(path)

# Fix the recommended product link in the JS template
old_link = '<a href="/poshy_store/${rec.slug}"'
new_link = '<a href="/${rec.slug}"'
if old_link in content:
    content = content.replace(old_link, new_link)
    print("  [OK] Fixed /poshy_store/ in recommended product link")
else:
    # Check if already fixed
    if '/${rec.slug}"' in content and '/poshy_store/' not in content:
        print("  [SKIP] Already fixed")
    else:
        print("  [WARN] Could not find recommended product link to fix")

# ============================================================
# 3. MAKE PRODUCT PHOTOS FULL-FRAME (remove padding, use cover)
# ============================================================
print("\n=== 3. Full-frame product photos ===")

# Fix the inline style on gallery images - remove padding, use object-fit: cover
content = content.replace(
    "style='max-width: 100%; max-height: 100%; object-fit: contain; padding: 20px;'",
    "style='width: 100%; height: 100%; object-fit: cover;'"
)
print("  [OK] Gallery images: cover, no padding")

# Fix the product-image-large height to be taller
content = content.replace(
    """.product-image-large {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, var(--purple-color), var(--purple-dark));""",
    """.product-image-large {
            width: 100%;
            height: 500px;
            background: #f8f8f8;"""
)
print("  [OK] Product image container: 500px, neutral background")

# Fix mobile height too
old_mobile = """.product-image-large {
                height: 250px;"""
new_mobile = """.product-image-large {
                height: 350px;"""
content = content.replace(old_mobile, new_mobile)

# Fix the carousel-slide background inline style 
content = content.replace(
    "style='background: #f5f5f5; display: flex; align-items: center; justify-content: center;'",
    "style='background: #fff;'"
)
print("  [OK] Carousel slide: clean white background")

# Fix thumbnail images too (the small indicators below main image)
content = content.replace(
    "onerror=\"this.onerror=null; this.src='" + "' . $base_url . '" + "/images/placeholder-cosmetics.svg';\"",
    "onerror=\"this.onerror=null; this.src='/images/placeholder-cosmetics.svg';\""
)

write_file(path, content)

# ============================================================
# 4. FIX CART PAGE - USE REAL PRODUCT IMAGES
# ============================================================
print("\n=== 4. Fix cart page product images ===")

cart_path = os.path.join(WEBROOT, "pages/shop/cart.php")
cart_content = read_file(cart_path)

# Add product_image_helper require if not present
if "product_image_helper" not in cart_content:
    cart_content = cart_content.replace(
        "require_once __DIR__ . '/../../includes/cart_handler.php';",
        "require_once __DIR__ . '/../../includes/cart_handler.php';\nrequire_once __DIR__ . '/../../includes/product_image_helper.php';"
    )
    # Also try alternate require pattern
    if "product_image_helper" not in cart_content:
        cart_content = cart_content.replace(
            "require_once __DIR__ . '/../../includes/auth_functions.php';",
            "require_once __DIR__ . '/../../includes/auth_functions.php';\nrequire_once __DIR__ . '/../../includes/product_image_helper.php';"
        )
    print("  [OK] Added product_image_helper require")

# Replace the image rendering section - use get_product_thumbnail instead of raw image_url
old_cart_img = """<?php if (!empty($item['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name_en']) ?>">
                                    <?php else: ?>
                                        <div class="item-image-placeholder">
                                            <?= $icons[$item['product_id'] % count($icons)] ?>
                                        </div>
                                    <?php endif; ?>"""

new_cart_img = """<?php
                                        $cart_img = get_product_thumbnail(
                                            trim($item['name_en']),
                                            $item['image_url'] ?? '',
                                            __DIR__ . '/../..'
                                        );
                                    ?>
                                    <img src="/<?= htmlspecialchars($cart_img) ?>" 
                                         alt="<?= htmlspecialchars($item['name_en']) ?>"
                                         style="width:100%;height:100%;object-fit:cover;"
                                         onerror="this.onerror=null;this.src='/images/placeholder-cosmetics.svg';">"""

if old_cart_img in cart_content:
    cart_content = cart_content.replace(old_cart_img, new_cart_img)
    print("  [OK] Cart page uses real product images now")
else:
    # Try to find the image section with different whitespace
    if "item-image-placeholder" in cart_content and "product_thumbnail" not in cart_content:
        print("  [WARN] Cart image section has different format, manual fix needed")
    elif "product_thumbnail" in cart_content:
        print("  [SKIP] Cart already uses product_thumbnail")
    else:
        print("  [WARN] Could not find cart image section")

write_file(cart_path, cart_content)

# ============================================================
# 5. FIX MY ORDERS PAGE - USE REAL PRODUCT IMAGES
# ============================================================
print("\n=== 5. Fix My Orders page product images ===")

orders_path = os.path.join(WEBROOT, "pages/shop/my_orders.php")
orders_content = read_file(orders_path)

# Add product_image_helper require
if "product_image_helper" not in orders_content:
    orders_content = orders_content.replace(
        "require_once __DIR__ . '/../../includes/auth_functions.php';",
        "require_once __DIR__ . '/../../includes/auth_functions.php';\nrequire_once __DIR__ . '/../../includes/product_image_helper.php';"
    )
    print("  [OK] Added product_image_helper require")

# Replace the emoji-based image carousel with real product images
# The old code generates emoji icons for each order item
old_orders_img = """$photo_gradients = [
                        'linear-gradient(135deg, var(--purple-color), var(--purple-dark))',
                        'linear-gradient(135deg, var(--gold-color), var(--gold-light))',
                        'linear-gradient(135deg, #f093fb, #f5576c)',
                        'linear-gradient(135deg, #4facfe, #00f2fe)',
                        'linear-gradient(135deg, #43e97b, #38f9d7)'
                    ];
                    $icons = ['\U0001f484', '\U0001f485', '\U0001f339', '\u2728', '\U0001f4ab', '\U0001f319', '\u2b50', '\U0001f48e', '\U0001f381', '\U0001f451'];"""

# Find the actual emoji line in the file 
if "$photo_gradients" in orders_content and "$icons" in orders_content:
    # Replace the entire item-icon block with a real image
    # Find and replace the item icon section
    
    # Add a function call to get product image just before the order item div
    orders_content = orders_content.replace(
        """<div class="order-item">
                                            <div class="item-icon" id="item-<?= $item['product_id'] ?>-<?= $order['order_id'] ?>">""",
        """<div class="order-item">
                                            <?php
                                                $order_item_img = get_product_thumbnail(
                                                    trim($item['product_name_en']),
                                                    '',
                                                    __DIR__ . '/../..'
                                                );
                                            ?>
                                            <div class="item-icon" id="item-<?= $item['product_id'] ?>-<?= $order['order_id'] ?>" style="background:#fff;padding:0;overflow:hidden;">"""
    )
    
    # Replace emoji slides with a single real image
    # Find the foreach that generates emoji slides and replace it
    old_slides = """<?php foreach ($product_icons as $idx => $icon): ?>
                                                    <div class="item-image-slide <?= $idx === 0 ? 'active' : '' ?>"
                                                         style="background: <?= $photo_gradients[$idx % count($photo_gradients)] ?>">
                                                        <?= $icon ?>
                                                    </div>
                                                <?php endforeach; ?>"""
    
    new_slides = """<img src="/<?= htmlspecialchars($order_item_img) ?>" 
                                                         alt="<?= htmlspecialchars($item['product_name_en']) ?>"
                                                         style="width:100%;height:100%;object-fit:cover;border-radius:12px;"
                                                         onerror="this.onerror=null;this.src='/images/placeholder-cosmetics.svg';">"""
    
    if old_slides in orders_content:
        orders_content = orders_content.replace(old_slides, new_slides)
        print("  [OK] My Orders uses real product images now")
    else:
        print("  [WARN] Could not find emoji slides block (whitespace may differ)")
        # Try a simpler replacement - just replace at regex level
        import re
        # Replace the foreach block with image
        pattern = r'<\?php foreach \(\$product_icons.*?endforeach; \?>'
        replacement = new_slides
        orders_content_new = re.sub(pattern, replacement, orders_content, flags=re.DOTALL)
        if orders_content_new != orders_content:
            orders_content = orders_content_new
            print("  [OK] My Orders uses real product images now (regex)")
        else:
            print("  [WARN] Regex replacement also failed")
else:
    print("  [SKIP] photo_gradients/icons not found, may already be fixed")

write_file(orders_path, orders_content)

# ============================================================
# 6. ADD SCROLL-TO-TOP BUTTON TO INDEX.PHP
# ============================================================
print("\n=== 6. Add scroll-to-top button ===")

index_path = os.path.join(WEBROOT, "index.php")
index_content = read_file(index_path)

scroll_button_code = """
    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" 
        style="display:none;position:fixed;bottom:30px;right:30px;z-index:9999;
        width:50px;height:50px;border-radius:50%;border:none;
        background:linear-gradient(135deg,#2d132c,#c9a86a);color:#fff;
        font-size:1.2rem;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);
        transition:all 0.3s ease;opacity:0.9;"
        title="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>
    <script>
    (function(){
        const btn = document.getElementById('scrollTopBtn');
        window.addEventListener('scroll', function(){
            btn.style.display = window.scrollY > 300 ? 'flex' : 'none';
            btn.style.alignItems = 'center';
            btn.style.justifyContent = 'center';
        });
    })();
    </script>
"""

if "scrollTopBtn" not in index_content:
    # Add before </body>
    index_content = index_content.replace("</body>", scroll_button_code + "</body>")
    write_file(index_path, index_content)
    print("  [OK] Scroll-to-top button added")
else:
    print("  [SKIP] Scroll-to-top button already exists")

# ============================================================
# 7. CENTER TEXT IN NAV ICONS
# ============================================================
print("\n=== 7. Center text in nav icons ===")

theme_path = os.path.join(WEBROOT, "includes/ramadan_theme_header.php")
theme_content = read_file(theme_path)

old_icon_css = """.nav-icon-ramadan {
        color: var(--royal-gold);
        font-size: 1.3rem;
        margin-left: 1rem;
        transition: all 0.3s;
        position: relative;
    }"""

new_icon_css = """.nav-icon-ramadan {
        color: var(--royal-gold);
        font-size: 1.3rem;
        margin-left: 1rem;
        transition: all 0.3s;
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 40px;
        height: 40px;
    }"""

if old_icon_css in theme_content:
    theme_content = theme_content.replace(old_icon_css, new_icon_css)
    write_file(theme_path, theme_content)
    print("  [OK] Nav icons centered")
else:
    print("  [SKIP] Nav icon CSS not found or already modified")

# ============================================================
# 8. FIX SCROLL-TO-TOP ON PRODUCT DETAIL PAGE TOO
# ============================================================
print("\n=== 8. Add scroll-to-top to product detail page ===")

pd_path = os.path.join(WEBROOT, "pages/shop/product_detail.php")
pd_content = read_file(pd_path)

if "scrollTopBtn" not in pd_content:
    pd_content = pd_content.replace("</body>", scroll_button_code + "</body>")
    write_file(pd_path, pd_content)
    print("  [OK] Scroll-to-top added to product detail")
else:
    print("  [SKIP] Already has scroll-to-top")

# ============================================================
# FINAL: Fix ownership
# ============================================================
print("\n=== Final: Fix ownership ===")
os.system("chown -R www-data:www-data /var/www/html/")
print("  [OK] Ownership fixed")

print("\n=== ALL FIXES APPLIED ===")
