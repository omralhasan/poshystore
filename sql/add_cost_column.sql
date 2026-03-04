-- Add cost column to products table (separate from supplier_cost)
-- cost = actual cost of goods, used for profit calculation
-- supplier_cost = price shown to supplier accounts

-- 1. Add cost column to products
ALTER TABLE products
ADD COLUMN cost DECIMAL(10,3) DEFAULT NULL COMMENT 'Actual cost of product for profit calculation'
AFTER supplier_cost;

-- 2. Migrate: set cost = supplier_cost for existing products
UPDATE products SET cost = supplier_cost WHERE cost IS NULL AND supplier_cost IS NOT NULL;

-- 3. Add cost_per_item column to order_items for snapshotting cost at order time
ALTER TABLE order_items
ADD COLUMN cost_per_item DECIMAL(10,3) DEFAULT NULL COMMENT 'Cost per item at time of order'
AFTER price_per_item;

-- 4. Populate cost_per_item for existing order_items from products table
UPDATE order_items oi
JOIN products p ON oi.product_id = p.id
SET oi.cost_per_item = p.cost
WHERE oi.cost_per_item IS NULL;
