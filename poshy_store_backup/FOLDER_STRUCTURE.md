# Poshy Store - Complete Folder Structure

## 📁 **Fully Organized File Structure**

All files have been organized into logical folders for better maintainability and professional organization.

## 🏗️ Directory Layout

```
/var/www/html/poshy_store/
│
├── 📄 index.php                    # Main home page with video hero and products
├── 📄 welcome.php                  # Welcome/landing page
├── 📄 composer.json               # PHP dependencies configuration
├── 📄 composer.lock               # Locked dependency versions
├── 📄 README.md                   # Main project documentation
├── 📄 FOLDER_STRUCTURE.md         # This file - folder organization guide
│
├── 📁 pages/                      # All website pages organized by category
│   │
│   ├── 📁 auth/                   # Authentication & User Management (7 files)
│   │   ├── signin.php             # User login page
│   │   ├── signup.php             # User registration page
│   │   ├── logout.php             # Logout handler
│   │   ├── process_signup.php     # Registration form processor
│   │   ├── oauth_callback.php     # Google OAuth callback handler
│   │   ├── oauth_diagnostic.php   # OAuth debugging tool
│   │   └── oauth_test.php         # OAuth testing page
│   │
│   ├── 📁 shop/                   # Shopping & E-commerce Pages (7 files)
│   │   ├── shop.php               # Main shop/browse page
│   │   ├── product_detail.php     # Individual product details
│   │   ├── cart.php               # Shopping cart page
│   │   ├── checkout.php           # Checkout process
│   │   ├── checkout_page.php      # Checkout form page
│   │   ├── my_orders.php          # User order history
│   │   └── order_success.php      # Order confirmation page
│   │
│   ├── 📁 policies/               # Legal & Policy Pages (5 files)
│   │   ├── terms-of-service.php   # Terms of service
│   │   ├── privacy-policy.php     # Privacy policy (GDPR compliant)
│   │   ├── return-policy.php      # 30-day return policy
│   │   ├── shipping-policy.php    # Shipping information
│   │   └── cancellation-policy.php # Order cancellation policy
│   │
│   └── 📁 admin/                  # Admin Management (1 file)
│       └── admin_panel.php        # Admin dashboard
│
├── 📁 includes/                   # Backend Configuration & Functions (7 files)
│   ├── auth_functions.php         # Authentication utilities
│   ├── db_config.php              # Database configuration
│   ├── db_connect.php             # Database connection handler
│   ├── cart_handler.php           # Shopping cart functions
│   ├── product_manager.php        # Product CRUD operations
│   ├── oauth_config.php           # OAuth settings
│   └── oauth_functions.php        # OAuth helper functions
│
├── 📁 api/                        # API Endpoints (6 files)
│   ├── add_to_cart_api.php        # Add product to cart endpoint
│   ├── cancel_order.php           # Cancel order endpoint
│   ├── submit_review.php          # Submit product review
│   ├── get_stock_status.php       # Check product stock
│   ├── view_logs.php              # View system logs
│   └── view_token_debug.php       # OAuth token debugger
│
├── 📁 sql/                        # Database Setup Scripts (7 files)
│   ├── setup_ecommerce.sql        # Main e-commerce tables
│   ├── setup_categories.sql       # Product categories
│   ├── setup_reviews.sql          # Product reviews
│   ├── add_oauth_support.sql      # OAuth authentication
│   ├── add_discount_columns.sql   # Discount functionality
│   ├── update_password.sql        # Password updates
│   └── (other setup scripts)
│
├── 📁 tests/                      # Testing & Development Files (15+ files)
│   ├── test_backend.php           # Backend functionality test
│   ├── test_add_to_cart.php       # Cart testing
│   ├── test_login_check.php       # Login system test
│   ├── test_oauth_error.php       # OAuth error testing
│   ├── test_email_login.php       # Email authentication test
│   ├── test_session.php           # Session management test
│   ├── check_users.php            # User data verification
│   ├── quick_test.php             # Quick functionality test
│   └── (other test files...)
│
├── 📁 demo/                       # Demo & Debug Files (4 files)
│   ├── category_filter_demo.php   # Category filtering demo
│   ├── category_overview.php      # Category overview demo
│   ├── stock_verification.html    # Stock check interface
│   └── oauth_token_debug.txt      # OAuth debug logs
│
├── 📁 docs/                       # Project Documentation (5 files)
│   ├── BACKEND_DOCUMENTATION.md   # Backend API documentation
│   ├── FRONTEND_GUIDE.md          # Frontend development guide
│   ├── OAUTH_SETUP.md             # OAuth setup instructions
│   ├── SOCIAL_LOGIN_README.md     # Social login configuration
│   └── VISUAL_GUIDE.md            # Visual design guide
│
├── 📁 images/                     # Image Assets
│   └── (upload your product images, banners, logos here)
│
├── 📁 backups/                    # Backup Files
│   └── my_orders.php.backup       # Backup files
│
└── 📁 vendor/                     # Composer Dependencies (auto-generated)
    ├── autoload.php
    ├── google/                    # Google API client
    ├── firebase/                  # Firebase JWT
    ├── guzzlehttp/               # HTTP client
    └── (other packages...)
```

## 📋 File Organization Summary

### **Root Directory** (4 essential files)
- `index.php` - Main home page
- `welcome.php` - Landing page  
- `composer.json` - Dependencies
- `README.md` - Documentation

### **pages/** (20 files)
- **auth/** - 7 authentication pages
- **shop/** - 7 e-commerce pages
- **policies/** - 5 legal pages
- **admin/** - 1 admin dashboard

### **includes/** (7 files)
- Configuration and utility functions

### **api/** (6 files)
- RESTful API endpoints

### **sql/** (7 files)
- Database setup and migration scripts

### **tests/** (15+ files)
- Testing and development tools

### **demo/** (4 files)
- Demo pages and debug files

### **docs/** (5 files)
- Project documentation

### **images/** (empty)
- Ready for image uploads

### **backups/** (1+ files)
- Backup files

### **vendor/** (auto-generated)
- Third-party dependencies

## 🔗 Updated File Paths

All internal links and includes have been updated:

### Navigation Links in Pages
```php
// Old:
<a href="signin.php">Sign In</a>
<a href="shop.php">Shop</a>

// New:
<a href="pages/auth/signin.php">Sign In</a>
<a href="pages/shop/shop.php">Shop</a>
```

### Include Paths
```php
// Old:
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/product_manager.php';

// New:
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/product_manager.php';
```

### API Endpoints
```javascript
// Old:
fetch('add_to_cart_api.php', {...})

// New:
fetch('api/add_to_cart_api.php', {...})
```

## ✅ Benefits of This Structure

1. **📂 Clear Organization**: Files grouped by functionality
2. **🔍 Easy Navigation**: Quickly find what you need
3. **🛠️ Better Maintenance**: Related files stay together
4. **📈 Scalability**: Easy to add new features
5. **👥 Team-Friendly**: Standard industry structure
6. **🔒 Security**: Sensitive configs in /includes/
7. **🧪 Separate Testing**: Test files don't clutter production
8. **📚 Organized Docs**: All documentation in one place

## 📝 Important Notes

- ✅ **All paths updated** in index.php, welcome.php, and policy pages
- ✅ **API endpoints** moved to /api/ folder  
- ✅ **Test files** separated from production code
- ✅ **SQL scripts** organized in /sql/
- ✅ **Documentation** centralized in /docs/
- ⚠️ When adding new files, place them in appropriate folders
- 📸 Upload product images to /images/ folder
- 🔄 Backup files automatically go to /backups/

## 🚀 Quick Access

**Main Pages:**
- Home: `/index.php`
- Welcome: `/welcome.php`

**User Pages:**
- Sign In: `/pages/auth/signin.php`
- Sign Up: `/pages/auth/signup.php`
- Shop: `/pages/shop/shop.php`
- Cart: `/pages/shop/cart.php`
- Orders: `/pages/shop/my_orders.php`

**Admin:**
- Admin Panel: `/pages/admin/admin_panel.php`

**Documentation:**
- All docs: `/docs/`

---

**Last Updated**: February 10, 2026  
**Total Files Organized**: 60+  
**Folders Created**: 10
