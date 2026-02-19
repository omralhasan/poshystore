-- Add video_review_url column to products table
-- This allows storing video URLs for "See in Action" product demonstration videos
-- Run this migration to enable the See in Action feature on product pages

-- Add the column if it doesn't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS video_review_url VARCHAR(500) DEFAULT NULL 
AFTER how_to_use;

-- Add a comment to the column for documentation
ALTER TABLE products 
MODIFY COLUMN video_review_url VARCHAR(500) DEFAULT NULL 
COMMENT 'URL for product demonstration video shown in "See in Action" tab (YouTube embed, Vimeo, etc.)';

-- Optional: Add index for faster queries if filtering by video availability
CREATE INDEX IF NOT EXISTS idx_video_review ON products(video_review_url(255));

-- Show the updated structure
SHOW COLUMNS FROM products;

-- Example usage:
-- UPDATE products SET video_review_url = 'https://www.youtube.com/embed/YOUR_VIDEO_ID' WHERE id = 1;
