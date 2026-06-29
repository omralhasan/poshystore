-- Add barcode column to products table for barcode scanner support
ALTER TABLE products
ADD COLUMN barcode VARCHAR(100) DEFAULT NULL COMMENT 'Barcode/UPC for scanner lookup'
AFTER slug;

-- Index for fast barcode lookup
ALTER TABLE products
ADD INDEX idx_barcode (barcode);
