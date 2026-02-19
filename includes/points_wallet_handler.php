<?php
/**
 * Points and Wallet Management Functions
 * For Poshy Lifestyle E-Commerce
 * 
 * Handles:
 * - Earning points from purchases
 * - Converting points to wallet balance
 * - Tracking all point and wallet transactions
 * - Retrieving user points/wallet history
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/language.php';

/**
 * Get points system settings
 * 
 * @return array Settings as key-value pairs
 */
function getPointsSettings() {
    global $conn;
    
    $sql = "SELECT setting_key, setting_value FROM points_settings";
    $result = $conn->query($sql);
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

/**
 * Get user's current points and wallet balance
 * 
 * @param int $user_id User ID
 * @return array|false User financial info or false on error
 */
function getUserPointsAndWallet($user_id) {
    global $conn;
    
    $sql = "SELECT points, wallet_balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return [
            'points' => (int)$data['points'],
            'wallet_balance' => (float)$data['wallet_balance'],
            'wallet_balance_formatted' => formatJOD($data['wallet_balance'])
        ];
    }
    
    $stmt->close();
    return false;
}

/**
 * Award points to user for a purchase
 * 
 * @param int $user_id User ID
 * @param float $purchase_amount Purchase amount in JOD
 * @param int $order_id Related order ID
 * @return array Result with success status and points earned
 */
function awardPurchasePoints($user_id, $purchase_amount, $order_id) {
    global $conn;
    
    // Get settings
    $settings = getPointsSettings();
    
    if ($settings['enable_points_system'] != '1') {
        return [
            'success' => false,
            'error' => 'Points system is currently disabled'
        ];
    }
    
    // Calculate points to award
    $points_per_jod = (float)$settings['points_per_jod'];
    $points_to_award = floor($purchase_amount * $points_per_jod);
    
    if ($points_to_award <= 0) {
        return [
            'success' => true,
            'points_earned' => 0,
            'message' => 'No points earned for this purchase'
        ];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current points
        $current_info = getUserPointsAndWallet($user_id);
        $points_before = $current_info['points'];
        $points_after = $points_before + $points_to_award;
        
        // Update user points
        $update_sql = "UPDATE users SET points = points + ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $points_to_award, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Record transaction
        $description = "Earned from order #$order_id (Amount: " . formatJOD($purchase_amount) . ")";
        $trans_sql = "INSERT INTO points_transactions 
                      (user_id, points_change, transaction_type, reference_id, description, points_before, points_after) 
                      VALUES (?, ?, 'earned_purchase', ?, ?, ?, ?)";
        $trans_stmt = $conn->prepare($trans_sql);
        $trans_stmt->bind_param('iiisii', $user_id, $points_to_award, $order_id, $description, $points_before, $points_after);
        $trans_stmt->execute();
        $trans_stmt->close();
        
        $conn->commit();
        
        return [
            'success' => true,
            'points_earned' => $points_to_award,
            'points_before' => $points_before,
            'points_after' => $points_after,
            'message' => "You earned $points_to_award points!"
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Failed to award points: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'Failed to award points: ' . $e->getMessage()
        ];
    }
}

/**
 * Convert points to wallet balance
 * 
 * @param int $user_id User ID
 * @param int $points_to_convert Points to convert
 * @return array Result with success status
 */
function convertPointsToWallet($user_id, $points_to_convert) {
    global $conn;
    
    // Validate input
    if (!is_numeric($points_to_convert) || $points_to_convert <= 0) {
        return [
            'success' => false,
            'error' => t('invalid_points_amount')
        ];
    }
    
    $points_to_convert = (int)$points_to_convert;
    
    // Get settings
    $settings = getPointsSettings();
    
    if ($settings['enable_points_system'] != '1') {
        return [
            'success' => false,
            'error' => 'Points system is currently disabled'
        ];
    }
    
    $minimum_conversion = (int)$settings['minimum_conversion_points'];
    $conversion_rate = (float)$settings['points_to_jod_rate']; // Points needed for 1 JOD
    
    // Validate minimum
    if ($points_to_convert < $minimum_conversion) {
        return [
            'success' => false,
            'error' => sprintf(t('minimum_conversion_is'), $minimum_conversion)
        ];
    }
    
    // Get current user data
    $current_info = getUserPointsAndWallet($user_id);
    $current_points = $current_info['points'];
    $current_wallet = $current_info['wallet_balance'];
    
    // Check if user has enough points
    if ($current_points < $points_to_convert) {
        return [
            'success' => false,
            'error' => sprintf(t('insufficient_points_have'), $current_points)
        ];
    }
    
    // Calculate wallet amount to add
    $wallet_amount = $points_to_convert / $conversion_rate;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Deduct points from user
        $new_points = $current_points - $points_to_convert;
        $update_points_sql = "UPDATE users SET points = ? WHERE id = ?";
        $update_points_stmt = $conn->prepare($update_points_sql);
        $update_points_stmt->bind_param('ii', $new_points, $user_id);
        $update_points_stmt->execute();
        $update_points_stmt->close();
        
        // Add to wallet
        $new_wallet = $current_wallet + $wallet_amount;
        $update_wallet_sql = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
        $update_wallet_stmt = $conn->prepare($update_wallet_sql);
        $update_wallet_stmt->bind_param('di', $wallet_amount, $user_id);
        $update_wallet_stmt->execute();
        $update_wallet_stmt->close();
        
        // Record points transaction
        $points_desc = "Converted $points_to_convert points to " . formatJOD($wallet_amount);
        $points_trans_sql = "INSERT INTO points_transactions 
                             (user_id, points_change, transaction_type, description, points_before, points_after) 
                             VALUES (?, ?, 'converted_to_wallet', ?, ?, ?)";
        $points_trans_stmt = $conn->prepare($points_trans_sql);
        $negative_points = -$points_to_convert;
        $points_trans_stmt->bind_param('iisii', $user_id, $negative_points, $points_desc, $current_points, $new_points);
        $points_trans_stmt->execute();
        $points_transaction_id = $points_trans_stmt->insert_id;
        $points_trans_stmt->close();
        
        // Record wallet transaction
        $wallet_desc = "Converted $points_to_convert points to wallet balance";
        $wallet_trans_sql = "INSERT INTO wallet_transactions 
                             (user_id, amount, transaction_type, reference_id, description, balance_before, balance_after) 
                             VALUES (?, ?, 'points_conversion', ?, ?, ?, ?)";
        $wallet_trans_stmt = $conn->prepare($wallet_trans_sql);
        $wallet_trans_stmt->bind_param('idisdd', $user_id, $wallet_amount, $points_transaction_id, $wallet_desc, $current_wallet, $new_wallet);
        $wallet_trans_stmt->execute();
        $wallet_trans_stmt->close();
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => t('points_converted_successfully'),
            'points_converted' => $points_to_convert,
            'wallet_amount_added' => $wallet_amount,
            'wallet_amount_formatted' => formatJOD($wallet_amount),
            'new_points_balance' => $new_points,
            'new_wallet_balance' => $new_wallet,
            'new_wallet_balance_formatted' => formatJOD($new_wallet)
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Failed to convert points: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => t('error_converting_points') . ': ' . $e->getMessage()
        ];
    }
}

/**
 * Get user's points transaction history
 * 
 * @param int $user_id User ID
 * @param int $limit Number of records to fetch
 * @return array Points transactions
 */
function getPointsHistory($user_id, $limit = 50) {
    global $conn;
    
    $sql = "SELECT id, points_change, transaction_type, reference_id, description, 
                   points_before, points_after, created_at
            FROM points_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    $stmt->close();
    return $transactions;
}

/**
 * Get user's wallet transaction history
 * 
 * @param int $user_id User ID
 * @param int $limit Number of records to fetch
 * @return array Wallet transactions
 */
function getWalletHistory($user_id, $limit = 50) {
    global $conn;
    
    $sql = "SELECT id, amount, transaction_type, reference_id, description, 
                   balance_before, balance_after, created_at
            FROM wallet_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $row['amount_formatted'] = formatJOD($row['amount']);
        $row['balance_before_formatted'] = formatJOD($row['balance_before']);
        $row['balance_after_formatted'] = formatJOD($row['balance_after']);
        $transactions[] = $row;
    }
    
    $stmt->close();
    return $transactions;
}

/**
 * Get user's referral code
 * 
 * @param int $user_id User ID
 * @return string|false Referral code or false on error
 */
function getUserReferralCode($user_id) {
    global $conn;
    
    $sql = "SELECT referral_code FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data['referral_code'];
    }
    
    $stmt->close();
    return false;
}

/**
 * Validate a referral code
 * 
 * @param string $referral_code Referral code to validate
 * @param int $exclude_user_id User ID to exclude (can't refer themselves)
 * @return array|false User data if valid, false otherwise
 */
function validateReferralCode($referral_code, $exclude_user_id = null) {
    global $conn;
    
    $sql = "SELECT id, email, CONCAT(firstname, ' ', lastname) as full_name FROM users WHERE referral_code = ?";
    if ($exclude_user_id) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    if ($exclude_user_id) {
        $stmt->bind_param('si', $referral_code, $exclude_user_id);
    } else {
        $stmt->bind_param('s', $referral_code);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
    
    $stmt->close();
    return false;
}

/**
 * Apply referral code - award points to referrer
 * 
 * @param string $referral_code Referral code used
 * @param int $referred_user_id User who used the code (new user)
 * @param int $order_id Order ID for reference
 * @return bool Success status
 */
function applyReferralCode($referral_code, $referred_user_id, $order_id = null) {
    global $conn;
    
    // Check if user already used a referral code
    $check_sql = "SELECT referred_by FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $referred_user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_data = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if ($check_data['referred_by'] !== null) {
        return false; // Already used a referral code
    }
    
    // Validate the referral code
    $referrer = validateReferralCode($referral_code, $referred_user_id);
    if (!$referrer) {
        return false;
    }
    
    $referrer_id = $referrer['id'];
    $referral_points = 200; // Fixed 200 points reward
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update referrer's points
        $update_sql = "UPDATE users 
                      SET points = points + ?, 
                          referrals_count = referrals_count + 1 
                      WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ii', $referral_points, $referrer_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Get new balance for logging
        $balance_sql = "SELECT points FROM users WHERE id = ?";
        $balance_stmt = $conn->prepare($balance_sql);
        $balance_stmt->bind_param('i', $referrer_id);
        $balance_stmt->execute();
        $balance_result = $balance_stmt->get_result();
        $balance_data = $balance_result->fetch_assoc();
        $new_balance = $balance_data['points'];
        $old_balance = $new_balance - $referral_points;
        $balance_stmt->close();
        
        // Log the transaction
        $log_sql = "INSERT INTO points_transactions 
                   (user_id, points_change, transaction_type, reference_id, 
                    description, points_before, points_after, created_at) 
                   VALUES (?, ?, 'bonus', ?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $description = "Referral bonus - Friend used your code";
        $log_stmt->bind_param('iiisii', 
            $referrer_id, 
            $referral_points, 
            $order_id, 
            $description, 
            $old_balance, 
            $new_balance
        );
        $log_stmt->execute();
        $log_stmt->close();
        
        // Mark the referred user
        $mark_sql = "UPDATE users SET referred_by = ? WHERE id = ?";
        $mark_stmt = $conn->prepare($mark_sql);
        $mark_stmt->bind_param('ii', $referrer_id, $referred_user_id);
        $mark_stmt->execute();
        $mark_stmt->close();
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Referral application failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get referral statistics for a user
 * 
 * @param int $user_id User ID
 * @return array Referral stats
 */
function getReferralStats($user_id) {
    global $conn;
    
    $sql = "SELECT referral_code, referrals_count FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        $stmt->close();
        
        // Calculate total points earned from referrals
        $data['total_referral_points'] = $data['referrals_count'] * 200;
        
        return $data;
    }
    
    $stmt->close();
    return [
        'referral_code' => null,
        'referrals_count' => 0,
        'total_referral_points' => 0
    ];
}
