 #!/usr/bin/env python3
"""Fix product_detail.php on VPS to show real images in cart popup instead of emoji."""
import sys

filepath = sys.argv[1] if len(sys.argv) > 1 else "pages/shop/product_detail.php"

with open(filepath) as f:
    content = f.read()

changes = 0

# 1. Fix added product image emoji -> real image
# Find the line: document.getElementById('addedProductImage').textContent = '📦';
old_added = "document.getElementById('addedProductImage').textContent = '\U0001f4e6';"
new_added = """const addedImgEl = document.getElementById('addedProductImage');
            if (product.image_path) {
                addedImgEl.innerHTML = '<img src="' + product.image_path + '" alt="' + product.name_en + '" style="width:100%;height:100%;object-fit:contain;" onerror="this.onerror=null;this.parentElement.textContent=\\'\\ud83d\\udce6\\';">';
            } else {
                addedImgEl.textContent = '\U0001f4e6';
            }"""

if old_added in content:
    content = content.replace(old_added, new_added)
    changes += 1
    print("Fixed: added product image (emoji -> real image)")
else:
    print("WARN: Could not find added product emoji line, trying alternate...")
    # Try with the unicode escape
    for needle in [
        'document.getElementById(\'addedProductImage\').textContent = \'📦\';',
        'document.getElementById("addedProductImage").textContent = \'📦\';',
    ]:
        if needle in content:
            content = content.replace(needle, new_added)
            changes += 1
            print("Fixed: added product image (alternate)")
            break

# 2. Fix recommended product images emoji -> real images
old_rec = '<div class="recommended-item-image">\u2728</div>'
new_rec = '<div class="recommended-item-image"><img src="${rec.image_path || \'/images/placeholder-cosmetics.svg\'}" alt="${rec.name_en}" style="width:100%;height:100%;object-fit:contain;" onerror="this.onerror=null;this.parentElement.textContent=\'\\u2728\';"></div>'

if old_rec in content:
    content = content.replace(old_rec, new_rec)
    changes += 1
    print("Fixed: recommended product images (emoji -> real image)")
else:
    print("WARN: Could not find recommended product emoji line, trying alternate...")
    for needle in [
        '<div class="recommended-item-image">✨</div>',
    ]:
        if needle in content:
            content = content.replace(needle, new_rec)
            changes += 1
            print("Fixed: recommended product images (alternate)")
            break

with open(filepath, "w") as f:
    f.write(content)

print(f"DONE - {changes} changes applied")
