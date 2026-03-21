-- Migration: Add image_path to categories and subcategories
-- Required for the new visual category stories and shop headers

ALTER TABLE categories 
ADD COLUMN IF NOT EXISTS image_path VARCHAR(500) DEFAULT NULL;

ALTER TABLE subcategories 
ADD COLUMN IF NOT EXISTS image_path VARCHAR(500) DEFAULT NULL;
