-- Add order_type column to orders table
ALTER TABLE orders 
ADD COLUMN order_type ENUM('customer', 'supplier') DEFAULT 'customer' AFTER status;
ALTER TABLE orders 
ADD INDEX idx_order_type (order_type);
