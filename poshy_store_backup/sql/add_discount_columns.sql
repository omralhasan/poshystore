-- Add discount tracking columns to products table
-- This allows displaying original price, discount %, and discounted price on home page

ALTER TABLE products 
ADD COLUMN original_price DECIMAL(10,3) DEFAULT NULL COMMENT 'Original price before discount',
ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Discount percentage (0-100)',
ADD COLUMN has_discount TINYINT(1) DEFAULT 0 COMMENT 'Flag: 1 if product has active discount';

-- Update existing products to set original_price = current price_jod where no discount exists
UPDATE products 
SET original_price = price_jod, 
    has_discount = 0, 
    discount_percentage = 0.00 
WHERE original_price IS NULL;

-- Add index for better query performance when filtering by discount
CREATE INDEX idx_has_discount ON products(has_discount);

-- Verify changes
SELECT 'Discount columns added successfully!' AS status;
DESCRIBE products;
