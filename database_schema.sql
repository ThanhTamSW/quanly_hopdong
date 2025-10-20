-- =============================================
-- HỆ THỐNG QUẢN LÝ HỢP ĐỒNG & LỊCH TẬP GYM
-- Database Schema
-- =============================================

-- Tạo database
CREATE DATABASE IF NOT EXISTS quanly_hopdong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanly_hopdong;

-- =============================================
-- BẢNG NGƯỜI DÙNG
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('coach', 'client', 'admin') NOT NULL DEFAULT 'client',
    start_work_date DATE NULL COMMENT 'Ngày bắt đầu làm việc (chỉ dành cho coach)',
    base_salary DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương cơ bản',
    sales_target DECIMAL(15,2) DEFAULT 0 COMMENT 'Mục tiêu doanh số',
    lunch_allowance DECIMAL(15,2) DEFAULT 0 COMMENT 'Phụ cấp ăn trưa',
    monthly_bonus DECIMAL(15,2) DEFAULT 0 COMMENT 'Thưởng tháng',
    monthly_penalty DECIMAL(15,2) DEFAULT 0 COMMENT 'Phạt tháng',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- BẢNG HỢP ĐỒNG
-- =============================================
CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    coach_id INT NOT NULL,
    start_date DATE NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    total_sessions INT NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    final_price DECIMAL(15,2) NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- BẢNG BUỔI TẬP
-- =============================================
CREATE TABLE training_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    session_datetime DATETIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    action_timestamp TIMESTAMP NULL COMMENT 'Thời gian thực hiện hành động',
    action_by_coach_id INT NULL COMMENT 'Coach thực hiện hành động',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by_coach_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- BẢNG LOG LƯƠNG
-- =============================================
CREATE TABLE payroll_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    contract_id INT NOT NULL,
    coach_id INT NOT NULL,
    completion_timestamp TIMESTAMP NOT NULL,
    commission_earned DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES training_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- INDEXES ĐỂ TỐI ƯU HIỆU SUẤT
-- =============================================
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_phone ON users(phone_number);
CREATE INDEX idx_contracts_coach ON contracts(coach_id);
CREATE INDEX idx_contracts_client ON contracts(client_id);
CREATE INDEX idx_contracts_status ON contracts(status);
CREATE INDEX idx_sessions_contract ON training_sessions(contract_id);
CREATE INDEX idx_sessions_datetime ON training_sessions(session_datetime);
CREATE INDEX idx_sessions_status ON training_sessions(status);
CREATE INDEX idx_payroll_coach ON payroll_log(coach_id);
CREATE INDEX idx_payroll_timestamp ON payroll_log(completion_timestamp);

-- =============================================
-- DỮ LIỆU MẪU (TÙY CHỌN)
-- =============================================

-- Tạo tài khoản admin mặc định
INSERT INTO users (phone_number, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Tạo một số coach mẫu
INSERT INTO users (phone_number, password, full_name, role, start_work_date, base_salary, sales_target, lunch_allowance) VALUES 
('0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', 'coach', '2024-01-01', 5000000, 50000000, 500000),
('0901234568', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', 'coach', '2024-02-01', 4500000, 40000000, 500000);

-- =============================================
-- VIEWS HỮU ÍCH
-- =============================================

-- View thống kê doanh thu theo coach
CREATE VIEW coach_revenue_stats AS
SELECT 
    u.id as coach_id,
    u.full_name as coach_name,
    COUNT(c.id) as total_contracts,
    SUM(c.final_price) as total_revenue,
    AVG(c.final_price) as avg_contract_value,
    COUNT(ts.id) as total_sessions,
    COUNT(CASE WHEN ts.status = 'completed' THEN 1 END) as completed_sessions
FROM users u
LEFT JOIN contracts c ON u.id = c.coach_id
LEFT JOIN training_sessions ts ON c.id = ts.contract_id
WHERE u.role = 'coach'
GROUP BY u.id, u.full_name;

-- View lịch tập tuần hiện tại
CREATE VIEW current_week_schedule AS
SELECT 
    ts.id,
    ts.session_datetime,
    c.id as contract_id,
    client.full_name as client_name,
    coach.full_name as coach_name,
    c.package_name,
    ts.status
FROM training_sessions ts
JOIN contracts c ON ts.contract_id = c.id
JOIN users client ON c.client_id = client.id
JOIN users coach ON c.coach_id = coach.id
WHERE ts.session_datetime BETWEEN 
    DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
    AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
ORDER BY ts.session_datetime;

-- =============================================
-- STORED PROCEDURES
-- =============================================

-- Procedure tính lương cho coach
DELIMITER //
CREATE PROCEDURE CalculateCoachSalary(
    IN p_coach_id INT,
    IN p_month INT,
    IN p_year INT
)
BEGIN
    DECLARE v_base_salary DECIMAL(15,2);
    DECLARE v_lunch_allowance DECIMAL(15,2);
    DECLARE v_sales_target DECIMAL(15,2);
    DECLARE v_total_revenue DECIMAL(15,2);
    DECLARE v_commission_rate DECIMAL(5,2);
    DECLARE v_commission_amount DECIMAL(15,2);
    DECLARE v_session_commission DECIMAL(15,2);
    DECLARE v_total_salary DECIMAL(15,2);
    
    -- Lấy thông tin coach
    SELECT base_salary, lunch_allowance, sales_target 
    INTO v_base_salary, v_lunch_allowance, v_sales_target
    FROM users WHERE id = p_coach_id;
    
    -- Tính doanh thu tháng
    SELECT COALESCE(SUM(final_price), 0) INTO v_total_revenue
    FROM contracts 
    WHERE coach_id = p_coach_id 
    AND MONTH(start_date) = p_month 
    AND YEAR(start_date) = p_year;
    
    -- Tính hoa hồng bán hàng (4% nếu đạt 80% target)
    IF v_sales_target > 0 AND (v_total_revenue / v_sales_target) >= 0.8 THEN
        SET v_commission_rate = 4;
    ELSE
        SET v_commission_rate = 0;
    END IF;
    
    SET v_commission_amount = v_total_revenue * (v_commission_rate / 100);
    
    -- Tính hoa hồng buổi tập (26%)
    SELECT COALESCE(SUM(commission_earned), 0) INTO v_session_commission
    FROM payroll_log 
    WHERE coach_id = p_coach_id 
    AND MONTH(completion_timestamp) = p_month 
    AND YEAR(completion_timestamp) = p_year;
    
    -- Tổng lương
    SET v_total_salary = v_base_salary + v_lunch_allowance + v_commission_amount + v_session_commission;
    
    -- Trả về kết quả
    SELECT 
        p_coach_id as coach_id,
        v_base_salary as base_salary,
        v_lunch_allowance as lunch_allowance,
        v_total_revenue as total_revenue,
        v_commission_rate as commission_rate,
        v_commission_amount as commission_amount,
        v_session_commission as session_commission,
        v_total_salary as total_salary;
END //
DELIMITER ;

-- =============================================
-- TRIGGERS
-- =============================================

-- Trigger cập nhật thời gian khi sửa hợp đồng
DELIMITER //
CREATE TRIGGER update_contract_timestamp 
BEFORE UPDATE ON contracts
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- Trigger cập nhật thời gian khi sửa buổi tập
DELIMITER //
CREATE TRIGGER update_session_timestamp 
BEFORE UPDATE ON training_sessions
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- =============================================
-- QUYỀN TRUY CẬP
-- =============================================

-- Tạo user cho ứng dụng (tùy chọn)
-- CREATE USER 'gym_app'@'localhost' IDENTIFIED BY 'your_secure_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON quanly_hopdong.* TO 'gym_app'@'localhost';
-- FLUSH PRIVILEGES;

-- =============================================
-- KẾT THÚC
-- =============================================
