-- Migration: Thêm bảng trả góp (installments)
-- Date: 2025-10-23

CREATE TABLE IF NOT EXISTS installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    installment_number INT NOT NULL COMMENT 'Đợt trả thứ mấy (1, 2, 3...)',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền phải trả',
    due_date DATE NOT NULL COMMENT 'Ngày đến hạn',
    paid_amount DECIMAL(15,2) DEFAULT 0 COMMENT 'Số tiền đã trả',
    paid_date DATE NULL COMMENT 'Ngày thanh toán',
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending' COMMENT 'Trạng thái',
    payment_method VARCHAR(50) NULL COMMENT 'Phương thức thanh toán (Tiền mặt, Chuyển khoản, Thẻ)',
    notes TEXT NULL COMMENT 'Ghi chú',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    INDEX idx_contract_id (contract_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm các cột mới vào bảng contracts (bỏ qua nếu đã tồn tại)
ALTER TABLE contracts 
ADD COLUMN payment_type ENUM('full', 'installment') DEFAULT 'full' COMMENT 'Loại thanh toán',
ADD COLUMN number_of_installments INT DEFAULT 1 COMMENT 'Số đợt trả',
ADD COLUMN first_payment DECIMAL(15,2) DEFAULT 0 COMMENT 'Tiền đặt cọc/trả trước';

