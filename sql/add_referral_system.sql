-- Add referral system columns to users table
ALTER TABLE users 
ADD COLUMN referral_code VARCHAR(10) UNIQUE DEFAULT NULL AFTER wallet_balance,
ADD COLUMN referred_by INT DEFAULT NULL AFTER referral_code,
ADD COLUMN referrals_count INT DEFAULT 0 AFTER referred_by,
ADD INDEX idx_referral_code (referral_code),
ADD INDEX idx_referred_by (referred_by);

-- Generate unique referral codes for existing users
UPDATE users 
SET referral_code = CONCAT(
    UPPER(SUBSTRING(MD5(CONCAT(id, email, RAND())), 1, 6))
)
WHERE referral_code IS NULL;
