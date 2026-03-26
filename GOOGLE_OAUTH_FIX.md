# Google OAuth 2.0 Compliance Fix 🔒

## The Problem
When users try to sign in with Google, they get this error:
```
You can't sign in to this app because it doesn't comply with Google's OAuth 2.0 policy for keeping apps secure.
Error 400: invalid_request
```

## Root Causes

### 1. **HTTP instead of HTTPS** ❌
- **Before**: `SITE_URL=http://159.223.180.154`
- **Requirement**: Google OAuth requires **HTTPS** for all production apps
- **Status**: ✅ FIXED

### 2. **Bare IP Address instead of Domain** ❌
- **Before**: Using `159.223.180.154` (bare IP)
- **Requirement**: Google OAuth only accepts proper domain names
- **Status**: ✅ FIXED

### 3. **Redirect URI Mismatch** ❌
- **Before**: Redirect URI was `http://159.223.180.154/oauth_callback.php`
- **Expected**: `https://poshystore.com/oauth_callback.php`
- **Status**: ✅ FIXED

---

## What Was Done

### Updated `.env` file:
```php
# BEFORE
SITE_URL=http://159.223.180.154

# AFTER
SITE_URL=https://poshystore.com
```

### How This Fixes OAuth:
1. OAuth config automatically uses `SITE_URL` to build redirect URIs
2. Redirect URI is now `https://poshystore.com/oauth_callback.php`
3. Matches what's registered in Google Cloud Console ✅

---

## Pre-Deployment Checklist

### ✅ DNS Configuration
Verify that `poshystore.com` points to your VPS:
```bash
nslookup poshystore.com
# Should show: 159.223.180.154
```

### ✅ SSL Certificate
Check that HTTPS is working:
```bash
curl -I https://poshystore.com
# Should return 200 OK (not 300 redirect or 500 error)
```

The nginx config expects the certificate at:
```
/etc/letsencrypt/live/poshystore.com/fullchain.pem
/etc/letsencrypt/live/poshystore.com/privkey.pem
```

If certificate doesn't exist or is expired, renew it:
```bash
sudo certbot renew --force-renewal
```

### ✅ Nginx Configuration
Your nginx config is already set up correctly for `https://poshystore.com`:
- **File**: `/etc/nginx/conf.d/poshystore.conf`
- **Status**: ✅ Uses `https://poshystore.com`
- **Action**: Reload nginx after deploying code changes

```bash
sudo systemctl reload nginx
```

### ✅ Deploy Updated Code
Copy the new `.env` to production:
```bash
echo 'Zzcckkaa11oommaarr.' | sudo -S cp /home/omar/poshystore/.env /var/www/html/.env
sudo systemctl restart php-fpm  # If using PHP-FPM
# OR
sudo systemctl restart apache2  # If using Apache
```

---

## Testing OAuth After Deployment

### 1. Test Redirect URI Construction
Access the login page and check the Google button link:
```bash
curl -s https://poshystore.com/pages/auth/signin.php | grep -o 'https://accounts.google.com[^"]*' | head -1
```

Should contain:
```
redirect_uri=https%3A%2F%2Fposhystore.com%2Foauth_callback.php
```

### 2. Test Full OAuth Flow
1. Go to `https://poshystore.com/pages/auth/signin.php`
2. Click "Sign in with Google"
3. You should be redirected to Google login (not an error)
4. After login, you should be redirected back to the app

### 3. Debug if Still Failing
Check the error log:
```bash
echo 'Zzcckkaa11oommaarr.' | sudo -S tail -50 /var/log/apache2/error.log
# OR for PHP-FPM
echo 'Zzcckkaa11oommaarr.' | sudo -S tail -50 /var/log/php-fpm/www-error.log
```

Check the OAuth debug log:
```bash
tail -50 /home/omar/poshystore/includes/oauth_debug.log
# OR if in production
echo 'Zzcckkaa11oommaarr.' | sudo -S tail -50 /var/www/html/includes/oauth_debug.log
```

---

## Google Cloud Console Verification

### Expected Configuration in Google Cloud Console:

**OAuth 2.0 Credentials** → **Web application**

**Authorized Redirect URIs** should include:
```
https://poshystore.com/oauth_callback.php
```

**Note**: 
- Must be HTTPS (not HTTP)
- Must be exact domain (not IP address)
- Can include multiple redirect URIs if testing locally:
  - `http://localhost/oauth_callback.php` (for local dev)
  - `https://poshystore.com/oauth_callback.php` (for production)

---

## Facebook App Configuration (If Using)

Similarly, in Facebook App Dashboard:

**Settings** → **Basic**
- App Domains: `poshystore.com`

**Settings** → **Advanced**
- Valid OAuth Redirect URIs: `https://poshystore.com/oauth_callback.php`

---

## Common Mistakes to Avoid ⚠️

❌ **Don't use**:
- `http://159.223.180.154` (no HTTPS, bare IP)
- `http://poshystore.com` (no HTTPS)
- `poshystore.com/oauth_callback.php` (missing protocol)
- Different domain in `.env` vs Google Console

✅ **Do use**:
- `https://poshystore.com/oauth_callback.php` (exact match)
- Proper SSL certificate (Let's Encrypt is fine)
- Same domain everywhere (code, Google Console, Facebook App)

---

## Deployment Steps

### 1. Update .env (DONE ✅)
```bash
# Already updated in repo
cat /home/omar/poshystore/.env | grep SITE_URL
```

### 2. Copy to Production
```bash
echo 'Zzcckkaa11oommaarr.' | sudo -S cp /home/omar/poshystore/.env /var/www/html/.env
```

### 3. Verify HTTPS works
```bash
curl -I https://poshystore.com/pages/auth/signin.php
# Should return: HTTP/2 200 or HTTP/1.1 200
```

### 4. Reload Web Server
```bash
echo 'Zzcckkaa11oommaarr.' | sudo -S systemctl reload nginx
# OR
echo 'Zzcckkaa11oommaarr.' | sudo -S systemctl reload apache2
```

### 5. Test OAuth
Go to: `https://poshystore.com/pages/auth/signin.php`

Click "Sign in with Google" → Should work! ✅

---

## Reference Files

- **Config file**: [`includes/oauth_config.php`](includes/oauth_config.php)
  - Uses `$oauth_domain` from `SITE_URL`
  - Builds correct redirect URIs automatically

- **OAuth functions**: [`includes/oauth_functions.php`](includes/oauth_functions.php)
  - `getOAuthURL($provider)` - generates Google login link
  - `exchangeCodeForToken()` - exchanges code for token

- **Callback handler**: [`pages/auth/oauth_callback.php`](pages/auth/oauth_callback.php)
  - Receives `code` from Google
  - Exchanges for access token
  - Creates/updates user
  - Redirects to dashboard

---

## Still Having Issues?

### Check Phase 1: DNS Resolution
```bash
nslookup poshystore.com
dig poshystore.com
```
Should return: `159.223.180.154`

### Check Phase 2: HTTPS Certificate
```bash
echo 'Zzcckkaa11oommaarr.' | sudo -S openssl x509 -in /etc/letsencrypt/live/poshystore.com/fullchain.pem -text -noout | grep -A2 "Subject:"
```
Should show: `CN = poshystore.com`

### Check Phase 3: Redirect URI Construction
```bash
curl -s https://poshystore.com/includes/oauth_config.php | grep "redirect_uri"
```
Should show: `https://poshystore.com/oauth_callback.php`

### Check Phase 4: Google Console Match
Compare what you see in output with Google Cloud Console settings.
They must match exactly.

---

## Success Indicators ✅

After deployment, you'll know it's working when:

1. ✅ Visiting `https://poshystore.com/pages/auth/signin.php` shows Google login button
2. ✅ Clicking Google button redirects to Google Accounts login (no error message)
3. ✅ After signing in, you're redirected back to the app
4. ✅ No "invalid_request" error
5. ✅ User is logged in and profile is created/updated
6. ✅ OAuth debug log shows successful token exchange

---

**Status**: Ready to deploy! 🚀
