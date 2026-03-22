-- Add image_url column to categories for category banner images
ALTER TABLE categories ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL AFTER name_ar;
