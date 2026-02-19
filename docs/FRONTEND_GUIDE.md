# 🛍️ Poshy Lifestyle E-Commerce - Frontend Guide

## ✨ What's Been Created

A complete, modern e-commerce frontend to test your backend system! 

### 📄 Pages Available

1. **[index.php](http://localhost/poshy_store/index.php)** - Homepage with featured products
2. **[signin.php](http://localhost/poshy_store/signin.php)** - User login
3. **[signup.php](http://localhost/poshy_store/signup.php)** - User registration  
4. **[cart.php](http://localhost/poshy_store/cart.php)** - Shopping cart
5. **[checkout_page.php](http://localhost/poshy_store/checkout_page.php)** - Checkout form
6. **[my_orders.php](http://localhost/poshy_store/my_orders.php)** - Order history
7. **[order_success.php](http://localhost/poshy_store/order_success.php)** - Order confirmation

## 🚀 Quick Start

### 1. Open the Store
Visit: `http://localhost/poshy_store/index.php`

### 2. Create an Account or Sign In
- **Sign Up:** Create a new customer account
- **Or Sign In:** Use your existing account
  - Email: `admin@poshylifestyle.com`
  - Password: (your password)

### 3. Shop!
- Browse products on the homepage
- Click "Add to Cart" on any product
- View your cart (cart icon in header)
- Adjust quantities or remove items
- Proceed to checkout
- Fill in shipping details
- Confirm your order!

### 4. Track Orders
- Click "My Orders" in the header
- View all your past orders
- Check order status

## 🎨 Features

### ✅ Frontend Features
- **Responsive Design** - Works on all devices
- **Beautiful Gradients** - Purple/blue theme
- **Arabic Support** - Displays product names in English & Arabic
- **Real-time Cart** - Shows item count in header
- **Stock Validation** - Prevents ordering out-of-stock items
- **AJAX Add to Cart** - Smooth, no page reload
- **Session Management** - Secure user authentication

### ✅ Backend Integration
All pages connect to your backend:
- `auth_functions.php` - Login/logout/session
- `product_manager.php` - Product listings
- `cart_handler.php` - Cart operations
- `checkout.php` - Order processing

## 🧪 Testing Workflow

### Complete Purchase Flow:
```
1. Visit index.php
2. Sign in (or create account)
3. Browse products
4. Add items to cart (instant feedback)
5. Click cart icon (see items)
6. Update quantities or remove items
7. Proceed to checkout
8. Fill shipping details
9. Confirm order
10. See success page
11. View in "My Orders"
```

### Test Different Scenarios:
- ✅ Add multiple products
- ✅ Update quantities in cart
- ✅ Remove items from cart
- ✅ Try to order out-of-stock items (blocked)
- ✅ Complete checkout
- ✅ View order history
- ✅ Logout and login again

## 📊 Current Database

**Users:** 2 accounts
**Products:** 9 luxury items (all in stock!)
- Handbags, watches, scarves, shoes, sunglasses, etc.
- Prices from 85.000 JOD to 1,250.000 JOD

## 🎯 Key Files Created

```
index.php              - Homepage/product listing
cart.php               - Shopping cart page
checkout_page.php      - Checkout form
order_success.php      - Order confirmation
my_orders.php          - Order history
add_to_cart_api.php    - AJAX endpoint for cart
logout.php             - Logout handler
```

## 💡 Tips

1. **Always sign in first** - Shopping requires authentication
2. **Check cart icon** - Shows your item count
3. **Stock is validated** - Can't order more than available
4. **Orders are real** - They're saved in the database!
5. **Responsive** - Try on mobile/tablet

## 🔧 Customization

Want to modify the look?
- Colors: Change the gradient in `<style>` sections
- Layout: Adjust CSS grid settings
- Products: Add more via phpMyAdmin or SQL

## 📝 Quick Test Commands

```bash
# Check database
mysql -u poshy_user -p'Poshy2026' poshy_lifestyle -e "SELECT * FROM products;"

# View orders
mysql -u poshy_user -p'Poshy2026' poshy_lifestyle -e "SELECT * FROM orders;"

# Check cart items
mysql -u poshy_user -p'Poshy2026' poshy_lifestyle -e "SELECT * FROM cart;"
```

## 🎉 You're All Set!

Your complete e-commerce platform is ready to test. Enjoy shopping! 🛍️

---

**Need help?** Check `BACKEND_DOCUMENTATION.md` for API details.
