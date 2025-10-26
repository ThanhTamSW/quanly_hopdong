-- Migration: Cho phép coach_id = NULL trong contracts table
-- Vì đã chuyển sang dùng new_coach_id (tham chiếu coaches table)

-- Bước 1: Cho phép coach_id = NULL
ALTER TABLE contracts 
MODIFY COLUMN coach_id INT NULL;

-- Bước 2: Xác nhận
SELECT 
    COLUMN_NAME,
    IS_NULLABLE,
    COLUMN_TYPE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'quanly_hopdong' 
  AND TABLE_NAME = 'contracts' 
  AND COLUMN_NAME IN ('coach_id', 'new_coach_id');

