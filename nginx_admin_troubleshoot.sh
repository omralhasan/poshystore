#!/bin/bash

echo "!=======!  Nginx Admin Panel 404 Troubleshooting  !=======!"
echo ""

# Test 1: Check if nginx is actually running and which interface
echo "1. Check which web server is serving which port:"
echo "=================================================="
echo ""
echo "Port 80:"
netstat -tulpn 2>/dev/null | grep ":80 " || echo "Using lsof:"
echo "Zzcckkaa11oommaarr." | sudo -S lsof -i :80 2>/dev/null || echo "  Could not determine"
echo ""
echo "Port 443:"
netstat -tulpn 2>/dev/null | grep ":443 " || echo "Using lsof:"
echo "Zzcckkaa11oommaarr." | sudo -S lsof -i :443 2>/dev/null || echo "  Could not determine"
echo ""

# Test 2: Test admin page via HTTP
echo "2. Test HTTP access to admin page:"
echo "=================================================="
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' http://localhost/pages/admin/add_product.php)
echo "HTTP://localhost/pages/admin/add_product.php: $HTTP_CODE"
HTTP_SERVER=$(curl -s -I http://localhost/pages/admin/add_product.php | grep -i "^Server:" | cut -d' ' -f2-)
echo "Server: $HTTP_SERVER"
echo ""

# Test 3: Test admin page via domain HTTP
echo "3. Test HTTP access via domain:"
echo "=================================================="
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' http://poshystore.com/pages/admin/add_product.php)
echo "HTTP://poshystore.com/pages/admin/add_product.php: $HTTP_CODE"
HTTP_SERVER=$(curl -s -I http://poshystore.com/pages/admin/add_product.php | grep -i "^Server:" | cut -d' ' -f2-)
echo "Server: $HTTP_SERVER"
echo ""

# Test 4: Test via HTTPS
echo "4. Test HTTPS access via domain:"
echo "=================================================="
HTTPS_CODE=$(curl -s -o /dev/null -w '%{http_code}' https://poshystore.com/pages/admin/add_product.php)
echo "HTTPS://poshystore.com/pages/admin/add_product.php (direct): $HTTPS_CODE"
HTTPS_SERVER=$(curl -s -I https://poshystore.com/pages/admin/add_product.php | grep -i "^Server:" | cut -d' ' -f2-)
echo "Server: $HTTPS_SERVER"
echo ""

# Test 5: Check if admin page files exist  
echo "5. Verify admin files exist:"
echo "=================================================="
echo "Zzcckkaa11oommaarr." | sudo -S ls -la /var/www/html/pages/admin/add_product.php 2>&1 | tail -1 || echo "File not found"
echo ""

# Test 6: Check nginx configuration syntax
echo "6. Verify nginx configuration:"
echo "=================================================="
echo "Zzcckkaa11oommaarr." | sudo -S nginx -t 2>&1
echo ""

# Test 7: Check if PHP-FPM is accessible
echo "7. Test PHP-FPM socket:"
echo "=================================================="
echo "Zzcckkaa11oommaarr." | sudo -S ls -la /run/php-fpm/www.sock | awk '{print $1, $9, $3, $4}' || echo "Socket not found"
echo ""

echo "!=========================================================!"
