#!/bin/bash

# OAuth & Login Diagnostics Script

echo "======================================="
echo "Poshy Store - Login & OAuth Diagnostics"
echo "======================================="
echo ""

# Check current configuration
echo "1. Current Configuration:"
echo "   SITE_URL: http://159.223.180.154"
echo "   OAuth Redirect URI: determines where providers send users back"
echo ""

echo "2. Testing Accessibility:"
echo ""

# Test IP address access
echo "   • Testing http://159.223.180.154/signin.php"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://159.223.180.154/signin.php)
echo "     HTTP Status: $STATUS"

# Test domain access
echo "   • Testing https://poshystore.com/signin.php"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://poshystore.com/signin.php 2>/dev/null || echo "Connection failed")
echo "     HTTP Status: $STATUS"

echo ""
echo "3. OAuth Redirect URI Issues:"
echo ""
echo "   Current Situation:"
echo "   - Your .env has: SITE_URL=http://159.223.180.154"
echo "   - Google/Facebook credentials are set up for: https://poshystore.com"
echo "   - MISMATCH = Users get 502 Bad Gateway when trying OAuth login"
echo ""

echo "4. To Fix OAuth Login:"
echo ""
echo "   Option A: Update .env to use poshystore.com (RECOMMENDED)"
echo "   - Edit: /home/omar/poshystore/.env"
echo "   - Change: SITE_URL=http://159.223.180.154"
echo "   - To:     SITE_URL=https://poshystore.com"
echo "   - Note: Requires poshystore.com domain to point to 159.223.180.154"
echo ""

echo "   Option B: Update Google/Facebook OAuth Settings"
echo "   - Google Cloud Console: Add http://159.223.180.154/oauth_callback.php"
echo "   - Facebook App: Update redirect_uris to use IP address"
echo "   - Note: Less recommended, IP addresses can change"
echo ""

echo "5. Testing Login Form:"
curl -s -X POST http://localhost/pages/auth/signin.php \
  -d "signin=1&email=test@test.com&password=test123" \
  --dump-header /tmp/test_headers.txt -o /tmp/test_body.txt

LOCATION=$(grep -i "Location:" /tmp/test_headers.txt | head -1 | cut -d' ' -f2-)
if [[ $LOCATION == *"signin.php?error="* ]]; then
  echo "   ✅ Login form is working (user not found error is expected)"
  echo "   Redirect: $LOCATION"
else
  echo "   ❌ Login form NOT working"
  echo "   Response: $(head -1 /tmp/test_headers.txt)"
fi

echo ""
echo "======================================="
echo "Next Steps:"
echo "1. Choose Option A or B above"
echo "2. Restart PHP-FPM: sudo systemctl restart php-fpm"
echo "3. Test OAuth login again"
echo "======================================="
