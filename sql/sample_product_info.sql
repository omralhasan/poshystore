-- Add sample product details and how to use information for existing products
-- This is a template - admins can customize these for each product

-- Update a sample product (adjust product ID as needed)
UPDATE products 
SET 
    product_details = 'Product Specifications:\n- Brand: Poshy Store\n- Type: Premium Cosmetics\n- Size: Standard\n- Suitable for: All skin types\n- Country of Origin: International\n- Shelf Life: 24 months from manufacturing date\n- Packaging: Sealed and hygienic\n- Certified: Meets international quality standards',
    how_to_use = 'Step-by-step Instructions:\n\n1. Prep Your Skin:\n   - Cleanse your face thoroughly\n   - Pat dry with a clean towel\n   - Ensure skin is completely dry before application\n\n2. Application:\n   - Take a small amount on your fingertips\n   - Apply gently in circular motions\n   - Focus on desired areas\n   - Allow to absorb for 1-2 minutes\n\n3. Best Practices:\n   - Use twice daily for optimal results\n   - Store in a cool, dry place\n   - Keep away from direct sunlight\n   - Close lid tightly after use\n\n4. Safety Tips:\n   - Perform patch test before first use\n   - Discontinue if irritation occurs\n   - Avoid contact with eyes\n   - Keep out of reach of children'
WHERE id = 1;

-- For admins: To update specific products, use this query format:
-- UPDATE products SET 
--   product_details = 'Your detailed product specifications here',
--   how_to_use = 'Your step-by-step usage instructions here'
-- WHERE id = [product_id];
