-- Fix decimal precision for price columns
-- Date: 2025-10-25
-- Issue: total_price and final_price were decimal(15,0) causing prices to be 0

-- Change decimal(15,0) to decimal(15,2) to support prices with 2 decimal places
ALTER TABLE contracts MODIFY COLUMN total_price DECIMAL(15,2) NOT NULL DEFAULT 0;
ALTER TABLE contracts MODIFY COLUMN final_price DECIMAL(15,2) NOT NULL DEFAULT 0;

-- Note: paid_amount was already decimal(15,2) so no change needed

