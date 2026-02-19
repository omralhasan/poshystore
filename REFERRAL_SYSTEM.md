# 🎁 Referral System Documentation

## Overview
The referral system allows users to invite friends and earn 100 loyalty points when their friends make their first purchase using the referral code.

## Features

### ✨ For Referrers (Users who share codes)
- Each user has a unique 6-character referral code (e.g., `24393B`)
- Earn **100 points** when a friend uses your code at checkout
- Track how many friends you've referred
- View total points earned from referrals
- Referral code is displayed on:
  - Rewards/Points Wallet page (`pages/shop/points_wallet.php`)
  - Order Success page (`pages/shop/order_success.php`)

### 👥 For Referred Users (New customers)
- Can enter a referral code during checkout
- Optional field - not required to complete purchase
- Can only use ONE referral code per account (first purchase only)
- Cannot use their own referral code

## Database Structure

### New Columns in `users` Table
```sql
referral_code VARCHAR(10) UNIQUE     -- User's unique referral code
referred_by INT                      -- ID of user who referred them (NULL if not referred)
referrals_count INT DEFAULT 0        -- How many people this user has referred
```

### Transaction Tracking
Referral bonuses are logged in the `points_transactions` table with:
- `transaction_type`: `'referral_bonus'`
- `points_change`: `100` (fixed amount)
- `reference_id`: Order ID that triggered the bonus
- `description`: "Referral bonus - Friend used your code"

## How It Works

### 1. User Shares Referral Code
- User logs in and views their rewards page
- Copies their unique referral code (e.g., `1B6B86`)
- Shares code with friends via WhatsApp, social media, or in person

### 2. Friend Uses Code at Checkout
- Friend adds products to cart
- Goes to checkout page
- Enters referral code in the "Have a Referral Code?" field
- Completes purchase

### 3. Points Are Awarded
- System validates the referral code
- Checks that the referred user hasn't used a code before
- Awards 100 points to the referrer
- Marks the new user as referred (prevents duplicate use)
- Logs the transaction for tracking

## Files Modified

### Backend Files
1. **`includes/points_wallet_handler.php`**
   - `getUserReferralCode($user_id)` - Get user's referral code
   - `validateReferralCode($code, $exclude_user_id)` - Validate code
   - `applyReferralCode($code, $user_id, $order_id)` - Award points
   - `getReferralStats($user_id)` - Get referral statistics

2. **`pages/shop/checkout.php`**
   - Extracts referral code from `$additional_data['referral_code']`
   - Calls `applyReferralCode()` after successful order
   - Returns `referral_applied` status in order result

3. **`pages/shop/checkout_page.php`**
   - Collects referral code from `$_POST['referral_code']`
   - Passes to `processCheckout()` via `$additional_data`

### Frontend Files
1. **`pages/shop/points_wallet.php`** (Rewards Dashboard)
   - Displays user's referral code in a prominent card
   - Shows referral statistics (count, total points earned)
   - Copy-to-clipboard button with visual feedback
   - JavaScript function: `copyReferralCode()`

2. **`pages/shop/checkout_page.php`** (Checkout Form)
   - Referral code input field in shipping details section
   - Optional field with helpful hint text
   - Styled with gradient background to stand out
   - Auto-converts to uppercase for consistency

3. **`pages/shop/order_success.php`** (Order Confirmation)
   - Shows user's referral code after successful order
   - Encourages sharing with friends
   - Copy button for easy sharing
   - JavaScript function: `copyReferralCodeSuccess()`

### Database Migration
- **`sql/add_referral_system.sql`**
  - Adds `referral_code`, `referred_by`, `referrals_count` columns
  - Creates indexes for performance
  - Generates unique codes for existing users

## Business Rules

### Validation Rules
1. ✅ Referral code must exist in database
2. ✅ User cannot refer themselves
3. ✅ User can only be referred ONCE (first purchase only)
4. ✅ Referral code is case-insensitive (converted to uppercase)
5. ✅ Code must be 10 characters or less

### Points Award Rules
1. 🎯 Fixed reward: **100 points** per referral
2. 📊 Points are awarded immediately after order is placed
3. 🔄 Transaction is logged for transparency
4. 📈 Referral count increments for the referrer
5. 🚫 No points if code is invalid or already used

### User Experience Rules
1. 🎨 Referral code field is optional at checkout
2. 🚀 Order proceeds even if referral code is invalid
3. 📱 Responsive design on all devices
4. 📋 One-click copy functionality
5. ✨ Visual feedback when code is copied

## Testing

### Test Scenarios

#### ✅ Scenario 1: Valid Referral Code
1. User A has referral code `24393B`
2. User B enters `24393B` at checkout
3. User B completes purchase
4. **Expected:** User A gets 100 points, User B marked as referred

#### ✅ Scenario 2: Invalid Referral Code
1. User enters non-existent code `XXXXXX`
2. Completes checkout
3. **Expected:** Order succeeds, no points awarded, no error shown

#### ✅ Scenario 3: Already Referred User
1. User B was already referred by User A
2. User B tries to use User C's code
3. **Expected:** Code is ignored, no additional referral recorded

#### ✅ Scenario 4: Self-Referral Attempt
1. User A tries to use their own code `24393B`
2. **Expected:** Code is rejected, no points awarded

### Testing Commands

```bash
# Check referral codes
mysql -u poshy_user -pPoshy2026 poshy_lifestyle \
  -e "SELECT id, email, referral_code, referrals_count FROM users;"

# Check referral transactions
mysql -u poshy_user -pPoshy2026 poshy_lifestyle \
  -e "SELECT * FROM points_transactions WHERE transaction_type='referral_bonus';"

# Check referred users
mysql -u poshy_user -pPoshy2026 poshy_lifestyle \
  -e "SELECT id, email, referred_by FROM users WHERE referred_by IS NOT NULL;"
```

## Usage Examples

### Example 1: Sharing Referral Code
```
User logs in → Goes to Rewards page → Sees code "1B6B86"
→ Clicks "Copy" button → Shares via WhatsApp:
"Hey! Use my code 1B6B86 at Poshy Store checkout and I'll get points! 😊"
```

### Example 2: Using Referral Code
```
New customer shops → Adds to cart → Goes to checkout
→ Fills shipping details → Enters "1B6B86" in referral code field
→ Completes order → Referrer gets 100 points automatically
```

## Future Enhancements

### Potential Features
1. 🎁 Reward both referrer AND referred user (e.g., 100 points each)
2. 📊 Referral dashboard with detailed analytics
3. 🏆 Tiered rewards (refer 10 friends = bonus points)
4. 📧 Email notifications when referral is successful
5. 🔗 Unique referral links with auto-filled codes
6. 💬 Social media share buttons
7. 🎯 Referral campaigns with limited-time bonuses
8. 📈 Leaderboard for top referrers

## Troubleshooting

### Issue: Referral code not working
**Solution:** Check that:
- Code exists in database
- User hasn't already used a referral code
- User isn't trying to use their own code

### Issue: Points not awarded
**Solution:** Check:
- Order was completed successfully
- Referral code was valid at time of order
- Check `points_transactions` table for the transaction

### Issue: Copy button not working
**Solution:**
- Check browser console for JavaScript errors
- Ensure `copyReferralCode()` function is loaded
- Test in different browser (compatibility issue)

## Security Considerations

1. ✅ SQL injection prevention: All queries use prepared statements
2. ✅ Input sanitization: Referral codes are trimmed and validated
3. ✅ Transaction safety: Database transactions ensure data integrity
4. ✅ XSS prevention: All output is escaped with `htmlspecialchars()`
5. ✅ Duplicate prevention: Database constraint prevents duplicate codes

## Summary

The referral system is now fully integrated into your e-commerce store! Users can:
- ✨ Find their unique code on the rewards page
- 📋 Copy it with one click
- 📤 Share with friends
- 🎁 Earn 100 points per successful referral
- 📊 Track their referral statistics

The system is automatic, secure, and designed to encourage viral growth through word-of-mouth marketing.
