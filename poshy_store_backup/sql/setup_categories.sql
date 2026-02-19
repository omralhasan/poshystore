-- Create product categories and assign products to them

-- Insert categories
INSERT INTO categories (name_en, name_ar) VALUES
('Accessories', 'إكسسوارات'),
('Bags & Luggage', 'حقائب وأمتعة'),
('Jewelry', 'مجوهرات'),
('Watches', 'ساعات'),
('Clothing', 'ملابس'),
('Footwear', 'أحذية'),
('Electronics', 'إلكترونيات'),
('Home & Lifestyle', 'المنزل ونمط الحياة')
ON DUPLICATE KEY UPDATE name_en = VALUES(name_en);

-- Update products to assign categories based on their names
UPDATE products SET category_id = 2 WHERE name_en LIKE '%Bag%' OR name_en LIKE '%Luggage%';
UPDATE products SET category_id = 3 WHERE name_en LIKE '%Ring%' OR name_en LIKE '%Diamond%' OR name_en LIKE '%Gold%';
UPDATE products SET category_id = 4 WHERE name_en LIKE '%Watch%';
UPDATE products SET category_id = 1 WHERE name_en LIKE '%Scarf%' OR name_en LIKE '%Sunglasses%' OR name_en LIKE '%Belt%';
UPDATE products SET category_id = 5 WHERE name_en LIKE '%Sweater%' OR name_en LIKE '%Jacket%' OR name_en LIKE '%Coat%' OR name_en LIKE '%Shirt%';
UPDATE products SET category_id = 6 WHERE name_en LIKE '%Shoe%' OR name_en LIKE '%Boots%';
UPDATE products SET category_id = 2 WHERE name_en LIKE '%Handbag%';

-- For any remaining NULL categories, assign to Home & Lifestyle
UPDATE products SET category_id = 8 WHERE category_id IS NULL;

-- Verify the assignments
SELECT c.name_en as Category, COUNT(p.id) as ProductCount
FROM categories c
LEFT JOIN products p ON c.id = p.category_id
GROUP BY c.id, c.name_en
ORDER BY c.id;

SELECT 'Categories setup complete!' as Status;
