-- Add Arabic description and product_details columns
-- Run this on the production server:
--   mysql -u poshy_user -p poshy_db < add_bilingual_description.sql

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS description_ar    TEXT         DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS product_details_ar TEXT        DEFAULT NULL AFTER product_details;
