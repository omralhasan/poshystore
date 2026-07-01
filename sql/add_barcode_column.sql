-- Add barcode column to products table for barcode scanner support
ALTER TABLE products
ADD COLUMN barcode VARCHAR(100) DEFAULT NULL COMMENT 'Barcode/UPC for scanner lookup'
AFTER slug;

-- Ensure column accepts NULL (in case it exists with NOT NULL)
ALTER TABLE products
MODIFY barcode VARCHAR(100) DEFAULT NULL;

-- Index for fast barcode lookup
ALTER TABLE products
ADD INDEX idx_barcode (barcode);
