-- Add short description columns to products table
-- This allows storing brief product descriptions in both Arabic and English
-- These descriptions appear directly below the product name on product pages

-- Add the columns if they don't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS short_description_ar VARCHAR(255) DEFAULT NULL AFTER name_ar;

ALTER TABLE products 
ADD COLUMN IF NOT EXISTS short_description_en VARCHAR(255) DEFAULT NULL AFTER name_en;

-- Add comments to the columns for documentation
ALTER TABLE products 
MODIFY COLUMN short_description_ar VARCHAR(255) DEFAULT NULL 
COMMENT 'Short description in Arabic displayed below product name';

ALTER TABLE products 
MODIFY COLUMN short_description_en VARCHAR(255) DEFAULT NULL 
COMMENT 'Short description in English displayed below product name';

-- Show the updated structure
SHOW COLUMNS FROM products;

-- Example usage:
-- UPDATE products SET 
--   short_description_en = 'Hydrates and plumps the skin for a healthy look.',
--   short_description_ar = 'يساعد على ترطيب البشرة ومنحها مظهر ممتلئ وصحي.'
-- WHERE id = 1;
