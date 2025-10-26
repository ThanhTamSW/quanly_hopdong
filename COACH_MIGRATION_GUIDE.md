# 🔄 Hướng dẫn Tách Coach ra khỏi Users

## 📋 Tổng quan

Hiện tại:
- **users** table: Chứa cả coach và client
- **coaches** table: Tồn tại nhưng chưa dùng đầy đủ

Mục tiêu:
- **users** table: Chỉ chứa client (học viên)
- **coaches** table: Chứa tất cả thông tin coach

---

## ⚠️ QUAN TRỌNG

1. **BACKUP DATABASE** trước khi chạy migration!
2. Migration này sẽ:
   - Copy data coach từ `users` sang `coaches`
   - Tạo bảng `user_coach_mapping` để map ID cũ → ID mới
   - Thêm cột `new_coach_id` vào `contracts` (giữ lại `coach_id` cũ tạm thời)
   - **KHÔNG XÓA** data cũ ngay (để rollback nếu cần)

---

## 🚀 Các bước thực hiện

### Bước 1: Backup Database

```bash
# Backup qua phpMyAdmin hoặc command line
mysqldump -u root -p quanly_hopdong > backup_before_migration.sql
```

### Bước 2: Chạy Migration

```bash
php run_coach_migration.php
```

Script sẽ hỏi xác nhận trước khi chạy. Nhập `y` để tiếp tục.

### Bước 3: Verify Data

Kiểm tra trong database:

```sql
-- Kiểm tra số lượng coaches
SELECT COUNT(*) FROM coaches;

-- Kiểm tra mapping
SELECT * FROM user_coach_mapping;

-- Kiểm tra contracts đã update
SELECT 
    c.id, 
    c.coach_id as old_id, 
    c.new_coach_id as new_id,
    u.full_name as old_coach,
    co.name as new_coach
FROM contracts c
LEFT JOIN users u ON c.coach_id = u.id
LEFT JOIN coaches co ON c.new_coach_id = co.id
LIMIT 10;
```

### Bước 4: Cập nhật Code

Sau khi verify data OK, cập nhật code để query từ bảng `coaches`:

**Trước:**
```php
$coaches = $conn->query("SELECT id, full_name FROM users WHERE role = 'coach'");
```

**Sau:**
```php
$coaches = $conn->query("SELECT id, name as full_name FROM coaches");
```

**Các file cần cập nhật:**
- `add_contract.php` - Dropdown chọn coach
- `edit_contract.php` - Dropdown chọn coach
- `coach_schedule.php` - Hiển thị lịch coach
- `salary_report.php` - Báo cáo lương coach
- `manage_targets.php` - Quản lý target coach
- `index.php` - Hiển thị tên coach trong contracts

### Bước 5: Test Toàn Bộ

Test các chức năng:
- ✅ Thêm hợp đồng mới
- ✅ Sửa hợp đồng
- ✅ Xem lịch dạy
- ✅ Báo cáo lương
- ✅ Quản lý target

### Bước 6: Finalize (Hoàn tất)

**CHỈ CHẠY SAU KHI ĐÃ TEST KỸ!**

```sql
-- 1. Xóa foreign key cũ
ALTER TABLE contracts DROP FOREIGN KEY contracts_ibfk_2;

-- 2. Xóa cột coach_id cũ
ALTER TABLE contracts DROP COLUMN coach_id;

-- 3. Rename new_coach_id thành coach_id
ALTER TABLE contracts CHANGE COLUMN new_coach_id coach_id INT NOT NULL;

-- 4. Thêm foreign key mới
ALTER TABLE contracts ADD FOREIGN KEY (coach_id) REFERENCES coaches(id);

-- 5. Xóa coach khỏi users (TÙY CHỌN)
DELETE FROM users WHERE role = 'coach';

-- 6. Xóa bảng mapping (không cần nữa)
DROP TABLE user_coach_mapping;
```

---

## 🔙 Rollback (Nếu có lỗi)

Nếu có vấn đề, rollback bằng cách:

```sql
-- 1. Xóa cột new_coach_id
ALTER TABLE contracts DROP COLUMN new_coach_id;

-- 2. Xóa bảng mapping
DROP TABLE user_coach_mapping;

-- 3. Xóa data trong coaches (nếu cần)
DELETE FROM coaches WHERE phone_number IN (
    SELECT phone_number FROM users WHERE role = 'coach'
);

-- 4. Restore từ backup
-- mysql -u root -p quanly_hopdong < backup_before_migration.sql
```

---

## 📊 Lợi ích

1. **Tách biệt rõ ràng**: Users (clients) và Coaches
2. **Dễ quản lý**: Thông tin coach tập trung ở 1 bảng
3. **Mở rộng**: Dễ thêm các trường riêng cho coach
4. **Bảo mật**: Tách authentication (users) và business data (coaches)

---

## 🆘 Hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra log lỗi
2. Verify data trong database
3. Rollback nếu cần
4. Restore từ backup

---

**Lưu ý:** Migration này an toàn vì giữ lại data cũ. Chỉ xóa sau khi đã test kỹ!

