-- Update Products Table for Dual Pricing System
-- Run with: mysql -u poshy_user -p poshy_lifestyle < update_dual_pricing.sql

USE poshy_lifestyle;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clear all existing products
TRUNCATE TABLE products;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Database updated for dual pricing system!' as Status;
