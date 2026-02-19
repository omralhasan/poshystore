-- Add shipping details columns to orders table
-- Run this SQL to update the orders table structure

-- Check if columns exist and add them if they don't
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS shipping_address TEXT,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS notes TEXT;

-- Add index on phone for faster lookups
CREATE INDEX IF NOT EXISTS idx_orders_phone ON orders(phone);

-- Update existing orders with NULL values will be handled by application
