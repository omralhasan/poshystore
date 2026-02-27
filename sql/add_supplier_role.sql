-- Add supplier role support
-- If role column is VARCHAR, it already supports 'supplier' 
-- If role column is ENUM, we need to expand it
-- This safely handles both cases

-- Make sure role column can hold 'supplier' value
ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'customer';

SELECT 'Supplier role migration complete!' as Status;
