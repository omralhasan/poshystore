# Poshy Lifestyle E-Commerce Backend Documentation

## 🏗️ Architecture Overview

Your professional PHP e-commerce backend is ready with:
- **Database**: poshy_lifestyle
- **User**: poshy_user  
- **Password**: Poshy_Lifestyle_2026!
- **Currency**: JOD (Jordanian Dinar)

---

## 📁 Core Backend Files

### 1. **db_connect.php** - Database Connection
- Establishes mysqli connection to poshy_lifestyle database
- UTF-8 support for Arabic content
- Helper functions:
  - `prepareAndBind($sql, $types, $params)` - Execute prepared statements
  - `formatJOD($amount)` - Format prices (125.500 JOD)
  - `jodToFils($jod)` / `filsToJOD($fils)` - Convert currency

### 2. **auth_functions.php** - Authentication
Functions connecting to `users` table:
- `registerUser($firstname, $lastname, $email, $password, $role)` - Register new user with hashed password
- `loginUser($email, $password)` - Verify credentials and create session
- `checkSession($redirect)` - Validate user session
- `hasRole($role)` - Check user permissions
- `getCurrentUserId()` - Get logged-in user ID
- `logoutUser()` - Destroy session

### 3. **product_manager.php** - Product Operations
Functions connecting to `products` table:
- `getAllProducts($filters, $limit, $offset)` - Get products with filtering (category, price, search)
- `getProductById($id)` - Get single product details
- `updateStock($id, $qty, $increment)` - Update product inventory (with transaction safety)
- `checkStock($id, $required_qty)` - Validate stock availability
- `searchProducts($search_term, $limit)` - Search products by name/description

### 4. **cart_handler.php** - Shopping Cart
Functions connecting to `cart` and `products` tables:
- `addToCart($product_id, $quantity, $user_id)` - Add item to cart (validates stock)
- `viewCart($user_id)` - Get cart contents with product details and totals
- `removeFromCart($cart_id, $user_id)` - Remove item from cart
- `updateCartQuantity($cart_id, $new_quantity, $user_id)` - Update item quantity
- `clearCart($user_id)` - Empty entire cart
- `getCartCount($user_id)` - Get total items in cart

### 5. **checkout.php** - Order Processing
Functions connecting to `orders`, `cart`, and `products` tables:
- `processCheckout($user_id, $additional_data)` - Create order from cart with transaction safety
  - Validates stock availability
  - Creates order record
  - Decrements product stock
  - Clears cart
- `getOrderDetails($order_id, $user_id)` - Get order information
- `getUserOrders($user_id, $limit, $offset)` - Get user's order history
- `updateOrderStatus($order_id, $new_status)` - Update order status (pending, processing, completed, cancelled)

---

## 🗄️ Database Tables

### `users`
```sql
id, firstname, lastname, email, password (hashed), role
```

### `products`
```sql
id, name_en, name_ar, description, price (DECIMAL 10,3), stock, image_url, category_id
```

### `cart`
```sql
id, user_id, product_id, quantity
```

### `orders`
```sql
id, user_id, total_amount (DECIMAL 10,3), status, created_at
```

---

## 💻 Usage Examples

### Register User
```php
require_once 'auth_functions.php';

$result = registerUser('John', 'Doe', 'john@example.com', 'SecurePass123', 'customer');
if ($result['success']) {
    echo "User registered with ID: " . $result['user_id'];
}
```

### Login User
```php
$result = loginUser('john@example.com', 'SecurePass123');
if ($result['success']) {
    echo "Welcome " . $result['user']['firstname'];
}
```

### Get Products
```php
require_once 'product_manager.php';

$result = getAllProducts(['category_id' => 1, 'in_stock' => true], 20);
foreach ($result['products'] as $product) {
    echo $product['name_en'] . ' - ' . $product['price_formatted'];
}
```

### Add to Cart
```php
require_once 'cart_handler.php';

$result = addToCart(5, 2); // product_id=5, quantity=2
if ($result['success']) {
    echo $result['message'];
}
```

### View Cart
```php
$cart = viewCart();
echo "Total: " . $cart['total_amount_formatted'];
foreach ($cart['cart_items'] as $item) {
    echo $item['name_en'] . ' x' . $item['quantity'];
}
```

### Checkout
```php
require_once 'checkout.php';

$result = processCheckout();
if ($result['success']) {
    echo "Order #" . $result['order']['order_id'];
    echo "Total: " . $result['order']['total_amount_formatted'];
}
```

---

## 🔒 Security Features

✅ **Prepared Statements** - All SQL queries use prepared statements to prevent SQL injection  
✅ **Password Hashing** - Passwords hashed with `password_hash()` (bcrypt)  
✅ **Session Management** - Secure session handling with timeout (24 hours)  
✅ **Transaction Safety** - Stock updates and checkout use database transactions  
✅ **Input Validation** - All inputs validated and sanitized  
✅ **Role-Based Access** - User role checking for admin functions

---

## 💰 JOD Currency Handling

Jordanian Dinar uses 3 decimal places (1 JOD = 1000 fils):
- Prices stored as `DECIMAL(10, 3)`
- Display format: `125.500 JOD`
- Helper functions for conversion

---

## 🚀 API Response Format

All functions return arrays with consistent structure:

**Success:**
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {...}
}
```

**Error:**
```json
{
    "success": false,
    "error": "Error message"
}
```

---

## 📝 Next Steps

1. ✅ Database tables created
2. ✅ Backend logic files ready
3. 🔄 Create frontend pages that use these functions
4. 🔄 Add order_items table for detailed order tracking (optional)
5. 🔄 Implement payment gateway integration

---

## 🧪 Testing

Test the backend:
```bash
php -r "require 'db_connect.php'; echo 'Connection: OK\n';"
```

Check tables:
```bash
mysql -u poshy_user -p'Poshy_Lifestyle_2026!' poshy_lifestyle -e "SHOW TABLES;"
```

---

**All files use prepared statements, handle JOD currency, and return JSON-compatible responses!** 🎉
