-- Add discount and delivery fee columns to orders table
-- Allows invoice to display coupon discount and delivery fee breakdown

ALTER TABLE orders
ADD COLUMN discount_amount DECIMAL(10,3) DEFAULT 0.000 COMMENT 'Coupon discount amount applied to order',
ADD COLUMN delivery_fee DECIMAL(10,3) DEFAULT 0.000 COMMENT 'Delivery fee for the order';
