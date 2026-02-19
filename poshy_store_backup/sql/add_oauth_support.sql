-- Add OAuth support to users table for Poshy Lifestyle
-- This allows users to login with Google, Facebook, or Apple
-- Run with: mysql -u poshy_user -p'Poshy2026' poshy_lifestyle < add_oauth_support.sql

USE poshy_lifestyle;

-- Add OAuth columns to users table
ALTER TABLE users 
ADD COLUMN oauth_provider VARCHAR(20) NULL DEFAULT NULL COMMENT 'google, facebook, apple, or NULL for email/password',
ADD COLUMN oauth_id VARCHAR(255) NULL DEFAULT NULL COMMENT 'OAuth provider user ID',
ADD COLUMN profile_picture VARCHAR(500) NULL DEFAULT NULL COMMENT 'User profile picture URL',
ADD INDEX idx_oauth (oauth_provider, oauth_id);

-- Make password field nullable for OAuth users
ALTER TABLE users 
MODIFY COLUMN password VARCHAR(255) NULL DEFAULT NULL;

-- Make phone number nullable (OAuth users might not have it initially)
ALTER TABLE users 
MODIFY COLUMN phonenumber VARCHAR(20) NULL DEFAULT NULL;

-- Add unique constraint for OAuth provider + ID combination
ALTER TABLE users
ADD CONSTRAINT unique_oauth UNIQUE KEY (oauth_provider, oauth_id);

SELECT 'OAuth support added successfully! Users can now login with Google, Facebook, or Apple.' as Status;
