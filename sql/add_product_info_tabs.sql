-- Add product details and how to use columns
ALTER TABLE products 
ADD COLUMN product_details TEXT DEFAULT NULL AFTER description,
ADD COLUMN how_to_use TEXT DEFAULT NULL AFTER product_details;
