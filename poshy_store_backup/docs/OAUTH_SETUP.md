# 🔐 Social Login Setup Guide for Poshy Lifestyle

Your e-commerce platform now supports **social login** with Google, Facebook, and Apple! Users can sign in using their social accounts in addition to email/password.

## ✅ What's Been Implemented

1. **Database Changes** ✓
   - Added OAuth columns to users table
   - Support for multiple login methods
   - Profile picture storage

2. **OAuth Infrastructure** ✓
   - OAuth configuration system
   - Secure token handling
   - User account linking

3. **Social Login Buttons** ✓
   - Beautiful UI with Google, Facebook, and Apple buttons
   - Seamless integration with existing login flow

## 🚀 How to Configure OAuth Providers

### 1️⃣ Google OAuth Setup

1. **Go to Google Cloud Console**
   - Visit: https://console.cloud.google.com/

2. **Create a New Project**
   - Click "Select a project" → "New Project"
   - Name: "Poshy Lifestyle"
   - Click "Create"

3. **Enable Google+ API**
   - Go to "APIs & Services" → "Library"
   - Search for "Google+ API"
   - Click "Enable"

4. **Create OAuth Credentials**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth client ID"
   - Application type: "Web application"
   - Name: "Poshy Lifestyle Web"
   
   **Authorized redirect URIs:**
   ```
   http://localhost/poshy_store/oauth_callback.php?provider=google
   http://yourdomain.com/poshy_store/oauth_callback.php?provider=google
   ```

5. **Copy Your Credentials**
   - Copy the `Client ID` and `Client Secret`
   - Update in `oauth_config.php`:
   ```php
   'google' => [
       'client_id' => 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',
       'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
       // ... rest stays the same
   ],
   ```

---

### 2️⃣ Facebook OAuth Setup

1. **Go to Facebook Developers**
   - Visit: https://developers.facebook.com/

2. **Create a New App**
   - Click "My Apps" → "Create App"
   - Select "Consumer" as app type
   - App name: "Poshy Lifestyle"
   - Click "Create App"

3. **Add Facebook Login**
   - In your app dashboard, click "Add Product"
   - Find "Facebook Login" and click "Set Up"
   - Select "Web" platform

4. **Configure OAuth Settings**
   - Go to "Facebook Login" → "Settings"
   
   **Valid OAuth Redirect URIs:**
   ```
   http://localhost/poshy_store/oauth_callback.php?provider=facebook
   http://yourdomain.com/poshy_store/oauth_callback.php?provider=facebook
   ```

5. **Copy Your Credentials**
   - Go to "Settings" → "Basic"
   - Copy `App ID` and `App Secret`
   - Update in `oauth_config.php`:
   ```php
   'facebook' => [
       'app_id' => 'YOUR_FACEBOOK_APP_ID',
       'app_secret' => 'YOUR_FACEBOOK_APP_SECRET',
       // ... rest stays the same
   ],
   ```

6. **Make App Public (When Ready)**
   - Go to "Settings" → "Basic"
   - Toggle app from "Development" to "Live"

---

### 3️⃣ Apple Sign In Setup

**Note:** Apple Sign In requires:
- An Apple Developer account ($99/year)
- A registered domain (doesn't work with localhost)
- More complex setup with certificates

1. **Go to Apple Developer**
   - Visit: https://developer.apple.com/account/

2. **Register an App ID**
   - Go to "Certificates, IDs & Profiles"
   - Click "Identifiers" → "+" button
   - Select "App IDs" → Continue
   - Description: "Poshy Lifestyle"
   - Bundle ID: com.poshylifestyle.web
   - Check "Sign in with Apple"

3. **Create a Services ID**
   - Click "Identifiers" → "+" button
   - Select "Services IDs" → Continue
   - Description: "Poshy Lifestyle Web"
   - Identifier: com.poshylifestyle.web.service
   - Check "Sign in with Apple"
   - Click "Configure"
   
   **Return URLs:**
   ```
   https://yourdomain.com/poshy_store/oauth_callback.php?provider=apple
   ```

4. **Create a Private Key**
   - Go to "Keys" → "+" button
   - Key Name: "Poshy Lifestyle Apple Sign In"
   - Check "Sign in with Apple"
   - Click "Configure" → Select your App ID
   - Download the `.p8` file
   - Save as `apple_private_key.p8` in your project root

5. **Update Configuration**
   ```php
   'apple' => [
       'client_id' => 'com.poshylifestyle.web.service',
       'team_id' => 'YOUR_TEAM_ID', // Found in Apple Developer account
       'key_id' => 'YOUR_KEY_ID', // From the key you created
       'private_key_path' => __DIR__ . '/apple_private_key.p8',
       // ... rest stays the same
   ],
   ```

---

## 🧪 Testing (Without Full OAuth Setup)

If you want to **test the UI** without setting up OAuth:

1. The social login buttons are visible and styled
2. Clicking them will redirect to OAuth providers
3. Without proper credentials, users will see OAuth provider errors
4. Regular email/password login still works perfectly

For **local development testing:**
- Google and Facebook can work with `localhost` URLs
- Apple requires a real domain with HTTPS

---

## 📝 Configuration File Location

Edit your OAuth credentials in:
```
/var/www/html/poshy_store/oauth_config.php
```

---

## 🔒 Security Features

✅ **CSRF Protection** - State tokens prevent cross-site attacks  
✅ **Secure Password Hashing** - Passwords never stored in plain text  
✅ **Email Validation** - Prevents duplicate accounts  
✅ **Session Management** - Secure user sessions  
✅ **Profile Pictures** - Automatic profile image sync  

---

## 🎨 User Experience

### New Users with Social Login:
1. Click "Continue with Google/Facebook/Apple"
2. Authorize the app on the OAuth provider
3. Automatically create account
4. Redirect to home page with welcome message

### Existing Users:
1. Click social login button
2. Instant authentication
3. Redirect to home page

### Traditional Login:
- Still available below the social buttons
- Works exactly as before

---

## 📊 Database Changes

New columns in `users` table:
- `oauth_provider` - Which provider (google/facebook/apple/NULL)
- `oauth_id` - Provider's user ID
- `profile_picture` - User's profile image URL
- `password` - Now nullable for OAuth users
- `phonenumber` - Now nullable

---

## 🐛 Troubleshooting

### "Invalid OAuth provider" error
- Check that OAuth URLs are correctly configured
- Verify redirect URIs in provider settings

### "Email already registered" error
- User has an existing email/password account
- Ask them to sign in with email/password
- Future enhancement: Link OAuth accounts to existing accounts

### OAuth provider errors
- Verify your Client ID/App ID is correct
- Check that redirect URIs match exactly
- Ensure OAuth credentials are in `oauth_config.php`

### "Failed to get access token" error
- Check Client Secret/App Secret
- Verify the authorization code hasn't expired
- Check error logs in `/var/log/apache2/error.log`

---

## 🚀 Going to Production

1. **Update Redirect URIs**
   - Change all `localhost` URLs to your domain
   - Use HTTPS in production

2. **Secure Your Config**
   - Move `oauth_config.php` outside web root
   - Set proper file permissions: `chmod 600 oauth_config.php`

3. **Enable HTTPS**
   - Required for Apple Sign In
   - Recommended for all OAuth providers

4. **App Store Compliance**
   - Facebook: Make app public
   - Google: Submit for verification if needed
   - Apple: Complete domain verification

---

## 📁 Files Created

- `oauth_config.php` - OAuth credentials configuration
- `oauth_functions.php` - OAuth handling functions
- `oauth_callback.php` - OAuth redirect handler
- `add_oauth_support.sql` - Database migration
- `OAUTH_SETUP.md` - This guide

---

## 🎉 Quick Start (For Testing)

**Want to test immediately?**

1. **Google only** (easiest to set up):
   - Get Google OAuth credentials
   - Update `oauth_config.php`
   - Test with your Google account

2. **Skip OAuth setup** (test the UI):
   - Social buttons are already visible
   - Email/password login works normally
   - Add OAuth credentials later

---

## 💡 Next Steps

1. Get OAuth credentials from providers
2. Update `oauth_config.php` with your credentials
3. Test social login functionality
4. Update redirect URIs for production domain

Need help? Check the troubleshooting section or review the error logs!
