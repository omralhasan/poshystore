-- ====================================================================
-- Renumber Product IDs: 85→1, 86→2, etc.
-- ====================================================================
-- WARNING: This will change primary keys and all foreign key references
-- Run this on VPS production database with extreme caution
-- ====================================================================

START TRANSACTION;

-- ─── Step 1: Create temporary mapping table ───────────────────────────
DROP TABLE IF EXISTS temp_id_mapping;
CREATE TEMPORARY TABLE temp_id_mapping (
    old_id INT PRIMARY KEY,
    new_id INT NOT NULL
);

-- ─── Step 2: Generate mapping (sorted by old_id) ──────────────────────
SET @row_num = 0;
INSERT INTO temp_id_mapping (old_id, new_id)
SELECT id AS old_id, (@row_num := @row_num + 1) AS new_id
FROM products
ORDER BY id ASC;

-- ─── Step 3: Verify mapping ───────────────────────────────────────────
SELECT 'Mapping Preview (first 10):' AS info;
SELECT * FROM temp_id_mapping LIMIT 10;

SELECT 'Total products to renumber:' AS info, COUNT(*) AS count FROM temp_id_mapping;

-- ─── Step 4: Disable foreign key checks ───────────────────────────────
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Step 5: Update foreign key references ────────────────────────────
-- Update cart
UPDATE cart c
INNER JOIN temp_id_mapping m ON c.product_id = m.old_id
SET c.product_id = m.new_id + 10000;  -- Temp offset to avoid conflicts

-- Update order_items
UPDATE order_items oi
INNER JOIN temp_id_mapping m ON oi.product_id = m.old_id
SET oi.product_id = m.new_id + 10000;

-- Update product_reviews
UPDATE product_reviews pr
INNER JOIN temp_id_mapping m ON pr.product_id = m.old_id
SET pr.product_id = m.new_id + 10000;

-- Update product_tags
UPDATE product_tags pt
INNER JOIN temp_id_mapping m ON pt.product_id = m.old_id
SET pt.product_id = m.new_id + 10000;

-- ─── Step 6: Update products table ────────────────────────────────────
UPDATE products p
INNER JOIN temp_id_mapping m ON p.id = m.old_id
SET p.id = m.new_id + 10000;

-- ─── Step 7: Remove temporary offset ──────────────────────────────────
UPDATE cart SET product_id = product_id - 10000;
UPDATE order_items SET product_id = product_id - 10000;
UPDATE product_reviews SET product_id = product_id - 10000;
UPDATE product_tags SET product_id = product_id - 10000;
UPDATE products SET id = id - 10000;

-- ─── Step 8: Reset auto-increment ─────────────────────────────────────
SET @max_id = (SELECT MAX(id) FROM products);
SET @sql = CONCAT('ALTER TABLE products AUTO_INCREMENT = ', @max_id + 1);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ─── Step 9: Re-enable foreign key checks ─────────────────────────────
SET FOREIGN_KEY_CHECKS = 1;

-- ─── Step 10: Verify results ──────────────────────────────────────────
SELECT 'Products after renumbering (first 10):' AS info;
SELECT id, name_en FROM products ORDER BY id ASC LIMIT 10;

SELECT 'Count verification:' AS info;
SELECT 
    'products' AS tbl, COUNT(*) AS count FROM products
UNION ALL SELECT 'cart', COUNT(*) FROM cart
UNION ALL SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL SELECT 'product_reviews', COUNT(*) FROM product_reviews
UNION ALL SELECT 'product_tags', COUNT(*) FROM product_tags;

-- ─── Step 11: Commit if everything looks good ─────────────────────────
COMMIT;

SELECT '✅ Migration completed successfully!' AS result;
