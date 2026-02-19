# 💰 Wallet Balance Payment Feature

## Overview
Users can now use their wallet balance to pay for orders at checkout. The wallet balance is automatically applied to reduce the order total, and any remaining balance is charged.

## How It Works

### 1. Checkout Page
- **Displays Wallet Balance**: Users see their current wallet balance in a highlighted section
- **Auto-Check for Full Coverage**: If wallet balance covers the full order, the checkbox is automatically checked
- **Real-time Total Update**: JavaScript dynamically updates the final total when checkbox is toggled
- **Payment Breakdown**: Shows how much wallet credit will be applied vs remaining amount to pay

### 2. Order Processing
- **Wallet Deduction**: System deducts the used amount from user's wallet balance
- **Order Amount**: Order is created with the final amount (after wallet deduction)
- **Transaction Logging**: Wallet usage is logged in `wallet_transactions` table
- **Points Award**: Points are still earned based on the **original cart total** (not the discounted amount)

### 3. Order Confirmation
- **Detailed Breakdown**: Success page shows:
  - Original cart total
  - Wallet credit used
  - Final amount paid
- **Transaction History**: Wallet transaction is recorded with reference to order ID

### 4. Order History
- **My Orders Page**: Shows wallet usage for each order
- **Payment Summary**: Displays original total, wallet credit used, and amount paid

## Database Changes

### Wallet Transactions Table
```sql
-- New transaction type: 'order_payment'
-- Negative amount indicates wallet usage
-- reference_id links to order_id
```

## Implementation Details

### Files Modified

#### 1. `pages/shop/checkout_page.php`
**Changes:**
- Added wallet balance fetch from database
- Created wallet balance display section with checkbox
- Added JavaScript for real-time total calculation
- Included `use_wallet` in form submission

**UI Features:**
```php
- Wallet balance card (only shows if balance > 0)
- Checkbox to enable/disable wallet usage
- Smart hint text (full coverage vs partial)
- Dynamic total updates without page reload
```

#### 2. `pages/shop/checkout.php`
**Changes:**
- Added wallet balance processing logic
- Deducts wallet from order total before creating order
- Logs wallet transaction to `wallet_transactions` table
- Returns wallet usage info in order result

**Key Logic:**
```php
$wallet_used = min($current_wallet, $total_amount);
$total_amount = max(0, $total_amount - $wallet_used);
```

#### 3. `pages/shop/order_success.php`
**Changes:**
- Queries `wallet_transactions` to check if wallet was used
- Displays payment breakdown when wallet was used
- Shows original total, wallet credit, and final paid amount

**Display Logic:**
- If wallet used: Shows detailed breakdown
- If no wallet: Shows standard total amount

#### 4. `pages/shop/my_orders.php`
**Changes:**
- Fetches wallet usage for each order
- Adds wallet data to order array
- Displays wallet credit line in order summary

## User Experience Flow

### Scenario 1: Full Wallet Coverage
```
Cart Total: 50.000 JOD
Wallet Balance: 75.000 JOD

✓ Checkbox auto-checked
✓ Message: "Your wallet balance covers the full order amount!"
✓ Final Total: 0.000 JOD
✓ Order placed with 0 payment required
✓ Wallet deducted: 50.000 JOD
✓ New wallet balance: 25.000 JOD
```

### Scenario 2: Partial Wallet Coverage
```
Cart Total: 100.000 JOD
Wallet Balance: 30.000 JOD

□ Checkbox unchecked by default
✓ Message: "Your wallet balance will be applied. You'll pay the remaining: 70.000 JOD"
✓ User can check/uncheck to toggle
✓ Final Total: 70.000 JOD (when checked)
✓ Order placed with 70.000 JOD payment
✓ Wallet deducted: 30.000 JOD
✓ New wallet balance: 0.000 JOD
```

### Scenario 3: No Wallet Balance
```
Cart Total: 50.000 JOD
Wallet Balance: 0.000 JOD

✗ Wallet section not displayed
✓ Final Total: 50.000 JOD
✓ Standard checkout flow
```

## Technical Specifications

### Wallet Transaction Record
```sql
INSERT INTO wallet_transactions (
    user_id,
    amount,                    -- Negative value (e.g., -50.000)
    transaction_type,          -- 'order_payment'
    reference_id,              -- Order ID
    description,               -- "Used wallet balance for Order #123"
    balance_before,            -- Wallet balance before deduction
    balance_after,             -- Wallet balance after deduction
    created_at                 -- Timestamp
)
```

### Order Response Data
```php
[
    'success' => true,
    'order' => [
        'order_id' => 123,
        'original_amount' => 100.000,      // Cart total before wallet
        'wallet_used' => 30.000,           // Amount from wallet
        'total_amount' => 70.000,          // Final amount paid
        'wallet_used_formatted' => '30.000 JOD',
        'total_amount_formatted' => '70.000 JOD',
        'points_earned' => 1000,           // Based on original_amount
        // ... other order data
    ]
]
```

## Benefits

### For Users
1. ✅ **Convenience**: Use accumulated wallet balance easily
2. ✅ **Transparency**: Clear breakdown of payment components
3. ✅ **Flexibility**: Optional - users can choose to save wallet for later
4. ✅ **Auto-suggestion**: Smart hints for best usage
5. ✅ **Instant**: No manual calculations needed

### For Business
1. 📈 **Increased Conversion**: Users more likely to complete purchase
2. 💰 **Reduced Payment Processing**: Less payment gateway fees
3. 🎯 **Customer Retention**: Encourages use of earned rewards
4. 📊 **Clear Tracking**: Full audit trail of wallet transactions
5. 🔄 **Closed Loop**: Wallet balance keeps customers in ecosystem

## Points & Referrals Integration

### Points Earned
- **Based on Original Total**: Points are calculated from cart total BEFORE wallet deduction
- **Example**: 100 JOD order = 1000 points (even if 50 JOD paid from wallet)
- **Rationale**: Rewards customer for full purchase value

### Referral Bonuses
- **Still Applied**: Referral codes work normally with wallet usage
- **No Impact**: Wallet usage doesn't affect referral bonus award

## Edge Cases Handled

### ✅ Insufficient Wallet Balance
- System uses available balance
- User pays the difference
- No errors or failures

### ✅ Exact Wallet Match
- Order total = 50 JOD, Wallet = 50 JOD
- Final amount = 0.000 JOD
- Order processes successfully

### ✅ Empty Cart
- Handled before wallet check
- User redirected to cart page

### ✅ Concurrent Orders
- Database transaction ensures accuracy
- Wallet balance locked during checkout
- No double-spending possible

### ✅ Order Cancellation
- Future enhancement: Refund wallet on cancellation
- Currently: Wallet deduction is permanent (standard order flow)

## Testing Checklist

### Functionality Tests
- [ ] Wallet balance displays correctly at checkout
- [ ] Checkbox toggles total calculation
- [ ] Order processes with wallet usage
- [ ] Wallet balance deducted from user account
- [ ] Transaction logged in wallet_transactions table
- [ ] Success page shows correct breakdown
- [ ] My Orders shows wallet usage
- [ ] Points awarded on full cart amount

### Edge Case Tests
- [ ] Order with wallet > cart total
- [ ] Order with wallet < cart total
- [ ] Order with wallet = cart total
- [ ] Order with 0 wallet balance
- [ ] Multiple orders in sequence
- [ ] Order with referral code + wallet

### UI/UX Tests
- [ ] Mobile responsive display
- [ ] JavaScript updates work
- [ ] Copy is clear and helpful
- [ ] Colors/styling consistent
- [ ] Icons display properly

## Future Enhancements

1. **Wallet Refunds**: Auto-refund to wallet on order cancellation
2. **Partial Wallet Option**: Choose amount to use (not all-or-nothing)
3. **Wallet-Only Orders**: Allow 100% wallet payment checkout
4. **Wallet History Filter**: Filter by order payments in wallet dashboard
5. **Email Notifications**: Notify when wallet is used
6. **Minimum Order Amount**: Set minimum for wallet usage
7. **Wallet Expiry**: Optional expiry date for unused balance

## Security Considerations

1. ✅ **SQL Injection**: Prepared statements throughout
2. ✅ **Race Conditions**: Database transactions prevent double-spend
3. ✅ **Validation**: Balance checked before deduction
4. ✅ **Logging**: Full audit trail
5. ✅ **Session Security**: User authentication required

## Summary

The wallet balance feature is now fully integrated! Users can:
- 💰 See their wallet balance at checkout
- ✓ Choose to apply wallet balance with one click
- 📊 See clear breakdown of payments
- 📜 Track wallet usage in order history

The system handles all edge cases, maintains data integrity, and provides a seamless user experience.
