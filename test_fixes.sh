#!/bin/bash
echo "=========================================="
echo "Testing Fixes for Admin & Arabic Issues"
echo "=========================================="
echo ""

# Test 1: Admin page access 
echo "Test 1: Admin Panel Pages (should be 302 for unauthenticated)"
echo "═════════════════════════════════════════"
echo "add_product: $(curl -s -o /dev/null -w '%{http_code}' http://localhost/pages/admin/add_product.php)"
echo "manage_categories: $(curl -s -o /dev/null -w '%{http_code}' http://localhost/pages/admin/manage_categories.php)"
echo "manage_podcasts: $(curl -s -o /dev/null -w '%{http_code}' http://localhost/pages/admin/manage_podcasts.php)"
echo ""

# Test 2: Regular English product URLs (should still work)
echo "Test 2: English Product URLs (catch-all rewrite)"
echo "═════════════════════════════════════════"
SLUG_CODE=$(curl -s -o /dev/null -w '%{http_code}' http://localhost/retinol-serum)
echo "URL: /retinol-serum"
echo "Response: $SLUG_CODE (expect 200 if product exists, or 302/404 if not)"
echo ""

# Test 3: Arabic product URL
echo "Test 3: Arabic Product URLs"
echo "═════════════════════════════════════════"
echo "URL: /منتج/retinol-serum (Arabic 'منتج' + English slug)"
ARABIC_CODE=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost/%D9%85%D9%86%D8%AA%D8%AC/retinol-serum")
echo "Response: $ARABIC_CODE"
if [ "$ARABIC_CODE" = "200" ]; then
  echo "✓ SUCCESS! Arabic product page working"
elif [ "$ARABIC_CODE" = "302" ]; then
  echo "~ PARTIAL: Redirecting (pages/shop or index.php), not 404"
elif [ "$ARABIC_CODE" = "404" ]; then
  echo "✗ FAILED: Still getting 404"
fi
echo ""

# Test 4: Check .htaccess was deployed correctly
echo "Test 4: .htaccess Verification"
echo "═════════════════════════════════════════"
if grep -q "RewriteCond.*REQUEST_URI.*pages" /var/www/html/.htaccess; then
  echo "✓ .htaccess fix applied (skip /pages/ in catch-all)"
else
  echo "✗ .htaccess fix NOT applied"
fi
echo ""

# Test 5: Check product.php was deployed correctly
echo "Test 5: product.php Validation"
echo "═════════════════════════════════════════"
if grep -q "\\\\p{L}" /var/www/html/product.php; then
  echo "✓ product.php updated to accept Unicode (Arabic) characters"
else
  echo "✗ product.php NOT updated"
fi
echo ""

echo "=========================================="
