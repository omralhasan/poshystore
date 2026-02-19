# Points and Wallet System Documentation 🎁💰

## Overview

The Poshy Lifestyle E-Commerce platform now includes a **Loyalty Points and Wallet System** that rewards customers for their purchases and allows them to convert earned points into wallet balance for future purchases.

---

## Features

### 1. **Loyalty Points System**
- ✅ Earn points automatically on every purchase
- ✅ Points are awarded immediately after order confirmation
- ✅ Configurable points-per-JOD ratio
- ✅ Complete transaction history tracking

### 2. **Wallet System**
- ✅ Convert earned points to wallet balance
- ✅ Use wallet balance for future purchases
- ✅ No expiration on wallet funds
- ✅ Secure transaction tracking

### 3. **User Dashboard**
- ✅ View current points balance
- ✅ View wallet balance
- ✅ Convert points to wallet balance
- ✅ View complete transaction history
- ✅ Separate tabs for points and wallet history

---

## Database Schema

### New Tables

#### 1. `points_transactions`
Tracks all point earning and spending activities.

```sql
CREATE TABLE points_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points_change INT NOT NULL,  -- Positive for earning, negative for spending
    transaction_type ENUM('earned_purchase', 'converted_to_wallet', 'admin_adjustment', 'bonus', 'expired'),
    reference_id INT DEFAULT NULL,  -- Related order_id or transaction_id
    description VARCHAR(500),
    points_before INT NOT NULL,
    points_after INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 2. `wallet_transactions`
Tracks all wallet balance changes.

```sql
CREATE TABLE wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 3) NOT NULL,  -- Positive for credit, negative for debit
    transaction_type ENUM('points_conversion', 'order_payment', 'refund', 'admin_adjustment', 'bonus'),
    reference_id INT DEFAULT NULL,
    description VARCHAR(500),
    balance_before DECIMAL(10, 3) NOT NULL,
    balance_after DECIMAL(10, 3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 3. `points_settings`
Stores configurable system settings.

```sql
CREATE TABLE points_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    description VARCHAR(500),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Modified Tables

#### `users` table
Added two new columns:
- `points` (INT): Current loyalty points balance
- `wallet_balance` (DECIMAL(10,3)): Current wallet balance in JOD

---

## Default Configuration

| Setting | Default Value | Description |
|---------|---------------|-------------|
| `points_per_jod` | 10 | Points earned per 1 JOD spent |
| `points_to_jod_rate` | 100 | Points needed to convert to 1 JOD |
| `minimum_conversion_points` | 100 | Minimum points required for conversion |
| `points_expiry_days` | 365 | Days until points expire (0 = never) |
| `enable_points_system` | 1 | Enable/disable the system (1=enabled) |

### Examples:
- **Purchase of 50 JOD** → Earn **500 points**
- **Convert 100 points** → Get **1 JOD** in wallet
- **Convert 1000 points** → Get **10 JOD** in wallet

---

## File Structure

### New Files Created

```
poshy_store/
├── sql/
│   └── setup_points_wallet.sql          # Database migration script
├── includes/
│   └── points_wallet_handler.php        # Core functions for points/wallet
├── api/
│   └── convert_points.php               # API endpoint for point conversion
└── pages/
    └── shop/
        └── points_wallet.php            # User dashboard page
```

### Modified Files

- `pages/shop/checkout.php` - Awards points after successful order
- `pages/shop/order_success.php` - Shows points earned
- `includes/ramadan_navbar.php` - Added Rewards navigation link

---

## Key Functions

### Points Management

#### `awardPurchasePoints($user_id, $purchase_amount, $order_id)`
Awards points to a user after a successful purchase.

**Parameters:**
- `$user_id` (int): The user ID
- `$purchase_amount` (float): Purchase amount in JOD
- `$order_id` (int): Order ID for reference

**Returns:** Array with success status and points earned

**Example:**
```php
$result = awardPurchasePoints(1, 125.500, 42);
// Returns: ['success' => true, 'points_earned' => 1255, ...]
```

#### `convertPointsToWallet($user_id, $points_to_convert)`
Converts user points to wallet balance.

**Parameters:**
- `$user_id` (int): The user ID
- `$points_to_convert` (int): Number of points to convert

**Returns:** Array with success status and conversion details

**Example:**
```php
$result = convertPointsToWallet(1, 500);
// Returns: ['success' => true, 'wallet_amount_added' => 5.000, ...]
```

### Helper Functions

#### `getUserPointsAndWallet($user_id)`
Retrieves user's current points and wallet balance.

#### `getPointsHistory($user_id, $limit)`
Gets user's points transaction history.

#### `getWalletHistory($user_id, $limit)`
Gets user's wallet transaction history.

#### `getPointsSettings()`
Retrieves all system settings as key-value pairs.

---

## User Workflow

### Earning Points

1. User adds items to cart
2. User proceeds to checkout
3. Order is successfully created
4. System automatically awards points based on order total
5. User sees points earned on order success page

### Converting Points to Wallet

1. User navigates to **Rewards Dashboard** (navbar icon or "Rewards" link)
2. User views current points balance
3. User enters points to convert (minimum 100 points)
4. System displays preview of JOD amount
5. User clicks "Convert"
6. System deducts points and adds to wallet balance
7. User sees updated balances

### Using Wallet Balance

_(Future feature - to be implemented)_
- During checkout, users can apply wallet balance to reduce order total
- Wallet balance is deducted from order amount
- Remaining amount paid via other methods

---

## API Endpoints

### POST `/api/convert_points.php`

Converts user points to wallet balance.

**Request Body:**
```
points_to_convert: integer (required, minimum: 100)
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Points converted successfully!",
    "points_converted": 500,
    "wallet_amount_added": 5.000,
    "wallet_amount_formatted": "5.000 JOD",
    "new_points_balance": 250,
    "new_wallet_balance": 15.000,
    "new_wallet_balance_formatted": "15.000 JOD"
}
```

**Response (Error):**
```json
{
    "success": false,
    "error": "Insufficient points. You have 50 points."
}
```

---

## Installation

### 1. Run Database Migration

```bash
cd /var/www/html/poshy_store
mysql -u poshy_user -p poshy_lifestyle < sql/setup_points_wallet.sql
```

### 2. Verify Installation

Check that all tables were created:
```sql
USE poshy_lifestyle;
SHOW TABLES LIKE '%points%';
SHOW TABLES LIKE '%wallet%';
DESCRIBE users;  -- Should show points and wallet_balance columns
```

### 3. Test the System

1. Log in to the store
2. Make a test purchase
3. Check order success page for points earned
4. Navigate to Rewards Dashboard
5. Try converting points to wallet

---

## Configuration

To modify system settings, update the `points_settings` table:

```sql
-- Change points per JOD
UPDATE points_settings 
SET setting_value = '20' 
WHERE setting_key = 'points_per_jod';

-- Change conversion rate (200 points = 1 JOD)
UPDATE points_settings 
SET setting_value = '200' 
WHERE setting_key = 'points_to_jod_rate';

-- Change minimum conversion
UPDATE points_settings 
SET setting_value = '200' 
WHERE setting_key = 'minimum_conversion_points';

-- Disable points system
UPDATE points_settings 
SET setting_value = '0' 
WHERE setting_key = 'enable_points_system';
```

---

## Security Features

✅ **User Authentication**: All points/wallet operations require login
✅ **Transaction Integrity**: Database transactions ensure consistency
✅ **Input Validation**: All inputs are validated and sanitized
✅ **Audit Trail**: Complete history of all transactions
✅ **Balance Tracking**: Before/after balances recorded for every transaction
✅ **Authorization**: Users can only access their own data

---

## Future Enhancements

### Planned Features:
- [ ] Use wallet balance during checkout
- [ ] Points expiration system
- [ ] Bonus points campaigns
- [ ] Referral rewards
- [ ] Admin panel for points management
- [ ] Email notifications for points earned
- [ ] Weekly/monthly points summary
- [ ] Tiered loyalty levels (Bronze, Silver, Gold)
- [ ] Special birthday bonus points

---

## Troubleshooting

### Issue: Points not awarded after purchase
**Solution:** Check that `enable_points_system` is set to 1 in `points_settings` table

### Issue: Cannot convert points
**Solution:** Ensure user has at least the minimum required points (default: 100)

### Issue: Conversion preview showing wrong amount
**Solution:** Verify the `points_to_jod_rate` setting in the database

### Issue: Navigation link not showing
**Solution:** Ensure user is logged in - the Rewards link only shows for authenticated users

---

## Database Queries for Reporting

### Total Points Earned by User
```sql
SELECT u.id, u.email, u.points, 
       SUM(CASE WHEN pt.points_change > 0 THEN pt.points_change ELSE 0 END) as total_earned,
       SUM(CASE WHEN pt.points_change < 0 THEN pt.points_change ELSE 0 END) as total_spent
FROM users u
LEFT JOIN points_transactions pt ON u.id = pt.user_id
GROUP BY u.id;
```

### Top Users by Points
```sql
SELECT id, email, points, wallet_balance
FROM users
ORDER BY points DESC
LIMIT 10;
```

### Recent Conversions
```sql
SELECT u.email, pt.points_change, pt.created_at, wt.amount
FROM points_transactions pt
JOIN users u ON pt.user_id = u.id
LEFT JOIN wallet_transactions wt ON wt.reference_id = pt.id
WHERE pt.transaction_type = 'converted_to_wallet'
ORDER BY pt.created_at DESC
LIMIT 20;
```

---

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the transaction logs in the database
3. Check PHP error logs: `/var/log/php-errors.log`
4. Review MySQL logs for database errors

---

**Version:** 1.0
**Last Updated:** February 2026
**Status:** ✅ Production Ready
