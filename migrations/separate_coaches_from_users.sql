-- =====================================================
-- MIGRATION: Tách data Coach ra khỏi bảng Users
-- Date: 2025-10-26
-- =====================================================

-- BƯỚC 1: Cập nhật cấu trúc bảng coaches
-- Thêm các cột từ users vào coaches
ALTER TABLE coaches 
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) UNIQUE AFTER phone,
ADD COLUMN IF NOT EXISTS password VARCHAR(255) AFTER phone_number,
ADD COLUMN IF NOT EXISTS start_work_date DATE AFTER password,
ADD COLUMN IF NOT EXISTS coach_type ENUM('official', 'freelance') DEFAULT 'official' AFTER start_work_date,
ADD COLUMN IF NOT EXISTS base_salary DECIMAL(15,2) DEFAULT 0 AFTER coach_type,
ADD COLUMN IF NOT EXISTS sales_target DECIMAL(15,2) DEFAULT 0 AFTER base_salary,
ADD COLUMN IF NOT EXISTS lunch_allowance DECIMAL(15,2) DEFAULT 0 AFTER sales_target,
ADD COLUMN IF NOT EXISTS monthly_bonus DECIMAL(15,2) DEFAULT 0 AFTER lunch_allowance,
ADD COLUMN IF NOT EXISTS monthly_penalty DECIMAL(15,2) DEFAULT 0 AFTER monthly_bonus,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER monthly_penalty;

-- BƯỚC 2: Copy data từ users sang coaches
-- Chỉ copy nếu chưa tồn tại (dựa vào phone_number)
INSERT INTO coaches (
    name, 
    email, 
    phone, 
    phone_number, 
    password, 
    start_work_date, 
    coach_type, 
    base_salary, 
    sales_target, 
    lunch_allowance, 
    monthly_bonus, 
    monthly_penalty,
    created_at
)
SELECT 
    full_name,
    CONCAT(REPLACE(phone_number, ' ', ''), '@coach.local') as email,
    phone_number as phone,
    phone_number,
    password,
    start_work_date,
    coach_type,
    base_salary,
    sales_target,
    lunch_allowance,
    monthly_bonus,
    monthly_penalty,
    created_at
FROM users 
WHERE role = 'coach'
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    start_work_date = VALUES(start_work_date),
    coach_type = VALUES(coach_type),
    base_salary = VALUES(base_salary),
    sales_target = VALUES(sales_target),
    lunch_allowance = VALUES(lunch_allowance),
    monthly_bonus = VALUES(monthly_bonus),
    monthly_penalty = VALUES(monthly_penalty);

-- BƯỚC 3: Tạo bảng mapping giữa user_id cũ và coach_id mới
CREATE TABLE IF NOT EXISTS user_coach_mapping (
    user_id INT NOT NULL,
    coach_id INT NOT NULL,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES coaches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert mapping
INSERT INTO user_coach_mapping (user_id, coach_id)
SELECT u.id, c.id
FROM users u
JOIN coaches c ON u.phone_number = c.phone_number
WHERE u.role = 'coach'
ON DUPLICATE KEY UPDATE coach_id = VALUES(coach_id);

-- BƯỚC 4: Thêm cột coach_id mới vào contracts (giữ lại coach_id cũ tạm thời)
ALTER TABLE contracts 
ADD COLUMN IF NOT EXISTS new_coach_id INT AFTER coach_id,
ADD INDEX idx_new_coach_id (new_coach_id);

-- Cập nhật new_coach_id từ mapping
UPDATE contracts c
JOIN user_coach_mapping m ON c.coach_id = m.user_id
SET c.new_coach_id = m.coach_id;

-- BƯỚC 5: Rename columns (sau khi verify data)
-- CHỈ CHẠY SAU KHI VERIFY DATA ĐÚNG!
-- ALTER TABLE contracts DROP FOREIGN KEY contracts_ibfk_2;
-- ALTER TABLE contracts DROP COLUMN coach_id;
-- ALTER TABLE contracts CHANGE COLUMN new_coach_id coach_id INT NOT NULL;
-- ALTER TABLE contracts ADD FOREIGN KEY (coach_id) REFERENCES coaches(id);

-- BƯỚC 6: Xóa coach khỏi users (TÙY CHỌN - CHỈ SAU KHI VERIFY)
-- DELETE FROM users WHERE role = 'coach';

-- =====================================================
-- GHI CHÚ:
-- - Chạy từng bước và verify data sau mỗi bước
-- - Backup database trước khi chạy
-- - Bước 5 và 6 chỉ chạy sau khi đã cập nhật code
-- =====================================================

