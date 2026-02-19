<?php
/**
 * Points and Wallet Page
 * Displays user's loyalty points, wallet balance, and transaction history
 * Allows converting points to wallet balance
 */

session_start();
require_once __DIR__ . '/../../includes/language.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/points_wallet_handler.php';

// Check if user is logged in
$user_id = getCurrentUserId();
if (!$user_id) {
    header('Location: /poshy_store/signin.php');
    exit;
}

// Get user info
$user_info = getUserPointsAndWallet($user_id);
$points_history = getPointsHistory($user_id, 20);
$wallet_history = getWalletHistory($user_id, 20);
$settings = getPointsSettings();
$referral_stats = getReferralStats($user_id);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="<?= isRTL() ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('my_rewards') ?> - Poshy Store</title>
    <?php require_once __DIR__ . '/../../includes/ramadan_theme_header.php'; ?>
    <style>
            padding-top: 100px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            color: var(--deep-purple);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-header::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--royal-gold), transparent);
            margin: 1rem auto;
        }
        
        .rewards-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(45, 19, 44, 0.15);
            margin-bottom: 2rem;
            border: 3px solid var(--royal-gold);
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .rewards-card::before {
            content: '✦';
            position: absolute;
            font-size: 10rem;
            color: rgba(201, 168, 106, 0.05);
            top: -30px;
            right: -30px;
            animation: sparkle 3s infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { opacity: 0.5; transform: rotate(0deg); }
            50% { opacity: 0.8; transform: rotate(15deg); }
        }
        
        .rewards-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(45, 19, 44, 0.25);
        }
        
        .card-icon {
            font-size: 3.5rem;
            color: var(--royal-gold);
            margin-bottom: 1rem;
            text-align: center;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .balance-display {
            font-size: 3.5rem;
            font-weight: bold;
            color: #d4af37;
            text-align: center;
            margin: 1.5rem 0;
            font-family: 'Playfair Display', serif;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #d4af37 0%, #f8e5a0 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .balance-label {
            text-align: center;
            color: var(--royal-gold);
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #fff9e6 0%, #fff4d9 100%);
            border: 2px solid var(--royal-gold);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .info-box strong {
            color: var(--deep-purple);
        }
        
        .info-box ul {
            margin: 1rem 0 0 0;
            padding-left: 1.5rem;
        }
        
        .info-box li {
            color: #555;
            margin: 0.5rem 0;
        }
        
        .conversion-section {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin: 2rem 0;
            box-shadow: 0 10px 40px rgba(45, 19, 44, 0.3);
            border: 3px solid var(--royal-gold);
        }
        
        .conversion-section h4 {
            color: var(--royal-gold);
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-label {
            color: var(--creamy-white);
            font-weight: 600;
        }
        
        .form-control {
            border: 2px solid var(--royal-gold);
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
        }
        
        .form-control:focus {
            border-color: var(--gold-light);
            box-shadow: 0 0 0 0.2rem rgba(201, 168, 106, 0.25);
        }
        
        .conversion-preview {
            color: var(--gold-light);
            font-weight: 600;
        }
        
        .btn-convert {
            background: linear-gradient(135deg, var(--royal-gold) 0%, #b39358 100%);
            color: var(--deep-purple);
            border: none;
            padding: 14px 30px;
            font-weight: 700;
            border-radius: 30px;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(201, 168, 106, 0.4);
        }
        
        .btn-convert:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(201, 168, 106, 0.6);
            background: linear-gradient(135deg, #b39358 0%, var(--royal-gold) 100%);
            color: var(--purple-dark);
        }
        
        .nav-tabs {
            border-bottom: 3px solid var(--royal-gold);
        }
        
        .nav-tabs .nav-link {
            color: var(--deep-purple);
            font-weight: 600;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px 10px 0 0;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--royal-gold);
            background: rgba(201, 168, 106, 0.1);
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--deep-purple) 0%, var(--purple-dark) 100%);
            color: var(--royal-gold);
            font-weight: 700;
        }
        
        .transaction-item {
            background: white;
            border-left: 5px solid var(--royal-gold);
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(45, 19, 44, 0.08);
            transition: all 0.3s;
        }
        
        .transaction-item:hover {
            transform: translateX(10px);
            box-shadow: 0 8px 25px rgba(45, 19, 44, 0.15);
        }
        
        .transaction-item.positive {
            border-left-color: #28a745;
        }
        
        .transaction-item.negative {
            border-left-color: #dc3545;
        }
        
        .transaction-type {
            color: var(--deep-purple);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .transaction-amount {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .balance-display {
                font-size: 2.5rem;
            }
            
            .rewards-card {
                padding: 1.5rem;
            }
            
            .conversion-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/ramadan_navbar.php'; ?>
    
    <div class="container my-5">
        <div class="page-header">
            <h1><i class="fas fa-gift"></i> <?= t('my_rewards') ?></h1>
            <p style="color: var(--royal-gold); font-size: 1.1rem;"><?= t('earn_points_unlock_rewards') ?></p>
        </div>
        
        <div class="row">
            <!-- Points Card -->
            <div class="col-md-6">
                <div class="rewards-card">
                    <div class="card-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="balance-label"><?= t('loyalty_points') ?></div>
                    <div class="balance-display">
                        <?php echo number_format($user_info['points']); ?>
                    </div>
                    
                    <div class="info-box">
                        <i class="fas fa-lightbulb"></i>
                        <strong><?= t('how_to_earn_points') ?></strong>
                        <ul class="mb-0 mt-2">
                            <li><?php printf(t('get_x_points_per_jod'), $settings['points_per_jod']); ?></li>
                            <li><?php printf(t('convert_x_points_to_jod'), $settings['points_to_jod_rate']); ?></li>
                            <li><?php printf(t('minimum_conversion'), $settings['minimum_conversion_points']); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Wallet Card -->
            <div class="col-md-6">
                <div class="rewards-card">
                    <div class="card-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="balance-label"><?= t('wallet_balance') ?></div>
                    <div class="balance-display" style="color: var(--royal-gold);">
                        <?php echo $user_info['wallet_balance_formatted']; ?>
                    </div>
                    
                    <div class="info-box">
                        <i class="fas fa-star"></i>
                        <strong><?= t('wallet_benefits') ?></strong>
                        <ul class="mb-0 mt-2">
                            <li><?= t('use_wallet_on_purchase') ?></li>
                            <li><?= t('convert_points_anytime') ?></li>
                            <li><?= t('wallet_never_expires') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Referral Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="rewards-card" style="background: linear-gradient(135deg, #fff9e6 0%, #fff4d9 100%);">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h3 style="color: var(--deep-purple); font-family: 'Playfair Display', serif;">
                                <i class="fas fa-users"></i> <?= t('refer_friends_earn_points') ?>
                            </h3>
                            <p style="color: #555; font-size: 1.1rem; margin: 1rem 0;">
                                <?= t('share_referral_code') ?> <?= t('when_friends_use_code') ?> <strong><?= t('you_get_200_points') ?></strong>
                            </p>
                            <div class="mt-3">
                                <p style="color: var(--royal-gold); font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> <?php printf(t('youve_referred_friends'), $referral_stats['referrals_count']); ?><br>
                                    <i class="fas fa-coins"></i> <?php printf(t('earned_from_referrals'), $referral_stats['total_referral_points']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div style="background: white; padding: 2rem; border-radius: 15px; border: 3px solid var(--royal-gold); text-align: center;">
                                <label style="color: var(--deep-purple); font-weight: 600; display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-gift"></i> <?= t('your_referral_code') ?>
                                </label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="text" 
                                           class="form-control" 
                                           id="referralCodeInput" 
                                           value="<?php echo htmlspecialchars($referral_stats['referral_code']); ?>" 
                                           readonly 
                                           style="font-size: 1.5rem; font-weight: bold; text-align: center; font-family: 'Courier New', monospace; color: var(--deep-purple); border: 2px solid var(--royal-gold);">
                                    <button type="button" 
                                            class="btn btn-convert" 
                                            onclick="copyReferralCode()" 
                                            style="padding: 12px 20px; white-space: nowrap;">
                                        <i class="fas fa-copy"></i> <?= t('copy') ?>
                                    </button>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-share-alt"></i> <?= t('share_with_friends') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Conversion Form -->
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="conversion-section">
                    <h4>
                        <i class="fas fa-exchange-alt"></i> <?= t('convert_points_to_wallet') ?>
                    </h4>
                    
                    <form id="conversionForm">
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="points_to_convert" class="form-label">
                                        <i class="fas fa-coins"></i> <?= t('how_many_points_convert') ?>
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="points_to_convert" 
                                           name="points_to_convert" 
                                           min="<?php echo $settings['minimum_conversion_points']; ?>"
                                           max="<?php echo $user_info['points']; ?>"
                                           step="<?php echo $settings['minimum_conversion_points']; ?>"
                                           placeholder="<?php printf(t('enter_points'), $settings['minimum_conversion_points']); ?>"
                                           required>
                                    <small class="conversion-preview mt-2 d-block">
                                        <i class="fas fa-arrow-right"></i> <?= t('you_will_receive') ?> <strong id="conversion_preview">0.000 <?= t('currency') ?></strong>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-convert w-100">
                                    <i class="fas fa-check-circle"></i> <?= t('convert_now') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div id="conversionMessage" class="mt-3"></div>
                </div>
            </div>
        </div>
        
        <!-- Transaction History Tabs -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-center mb-4" style="color: var(--deep-purple); font-family: 'Playfair Display', serif;">
                    <i class="fas fa-history"></i> <?= t('transaction_history') ?>
                </h3>
                
                <ul class="nav nav-tabs justify-content-center" id="historyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="points-tab" data-bs-toggle="tab" data-bs-target="#points-history" type="button">
                            <i class="fas fa-coins"></i> <?= t('points_history') ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="wallet-tab" data-bs-toggle="tab" data-bs-target="#wallet-history" type="button">
                            <i class="fas fa-wallet"></i> <?= t('wallet_history') ?>
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content py-4" id="historyTabsContent">
                    <!-- Points History -->
                    <div class="tab-pane fade show active" id="points-history">
                        <?php if (empty($points_history)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> <?= t('no_points_transactions') ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($points_history as $trans): ?>
                                <div class="transaction-item <?php echo $trans['points_change'] > 0 ? 'positive' : 'negative'; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="transaction-type">
                                                <?php echo $trans['points_change'] > 0 ? '<i class="fas fa-plus-circle text-success"></i>' : '<i class="fas fa-minus-circle text-danger"></i>'; ?>
                                                <?php echo ucfirst(str_replace('_', ' ', $trans['transaction_type'])); ?>
                                            </div>
                                            <small class="text-muted"><?php echo htmlspecialchars($trans['description']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="transaction-amount <?php echo $trans['points_change'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $trans['points_change'] > 0 ? '+' : ''; ?><?php echo number_format($trans['points_change']); ?> <?= t('pts') ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="far fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($trans['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Wallet History -->
                    <div class="tab-pane fade" id="wallet-history">
                        <?php if (empty($wallet_history)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> <?= t('no_wallet_transactions') ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($wallet_history as $trans): ?>
                                <div class="transaction-item <?php echo $trans['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="transaction-type">
                                                <?php echo $trans['amount'] > 0 ? '<i class="fas fa-plus-circle text-success"></i>' : '<i class="fas fa-minus-circle text-danger"></i>'; ?>
                                                <?php echo ucfirst(str_replace('_', ' ', $trans['transaction_type'])); ?>
                                            </div>
                                            <small class="text-muted"><?php echo htmlspecialchars($trans['description']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="transaction-amount <?php echo $trans['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $trans['amount'] > 0 ? '+' : ''; ?><?php echo $trans['amount_formatted']; ?>
                                            </div>
                                            <small class="text-muted"><?= t('balance') ?>: <strong><?php echo $trans['balance_after_formatted']; ?></strong></small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="far fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($trans['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/../../includes/ramadan_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Conversion preview calculator
        const pointsInput = document.getElementById('points_to_convert');
        const conversionPreview = document.getElementById('conversion_preview');
        const conversionRate = <?php echo $settings['points_to_jod_rate']; ?>;
        
        pointsInput.addEventListener('input', function() {
            const points = parseFloat(this.value) || 0;
            const jod = points / conversionRate;
            conversionPreview.innerHTML = jod.toFixed(3) + ' <?= t("currency") ?>';
        });
        
        // Handle conversion form submission
        document.getElementById('conversionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('conversionMessage');
            
            // Show loading
            messageDiv.innerHTML = '<div class="alert alert-info text-center"><i class="fas fa-spinner fa-spin"></i> <?= t("processing_conversion") ?></div>';
            
            fetch('/poshy_store/api/convert_points.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = `
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle"></i> 
                            <strong>${data.message}</strong><br>
                            <small><?= t('converted_x_points_to_y_jod') ?></small>
                        </div>
                    `.replace('%d', data.points_converted).replace('%s', data.wallet_amount_formatted);
                    
                    // Reload page after 2 seconds to show updated balances
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    messageDiv.innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong><?= t('error') ?>:</strong> ${data.error}
                        </div>
                    `;
                }
            })
            .catch(error => {
                messageDiv.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?= t('network_error_try_again') ?>
                    </div>
                `;
                console.error('Error:', error);
            });
        });
        
        // Copy referral code to clipboard
        function copyReferralCode() {
            const referralInput = document.getElementById('referralCodeInput');
            referralInput.select();
            referralInput.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                
                // Show success feedback
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> <?= t("copied") ?>';
                btn.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
                alert('<?= t("failed_copy_manual") ?>');
            }
        }
    </script>
</body>
</html>
