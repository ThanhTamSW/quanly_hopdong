# 🔄 HƯỚNG DẪN CHẠY MIGRATION - THÊM TÍNH NĂNG TRẢ GÓP

## ⚠️ QUAN TRỌNG: CHẠY MIGRATION TRƯỚC KHI SỬ DỤNG IMPORT TRẢ GÓP

Tính năng trả góp cần thêm bảng `installments` và các cột mới vào bảng `contracts`.

---

## 🚀 CÁCH 1: Chạy qua phpMyAdmin (KHUYẾN NGHỊ)

### Bước 1: Truy cập phpMyAdmin
- Mở phpMyAdmin
- Chọn database `quanly_hopdong`

### Bước 2: Chạy SQL
- Click tab **SQL**
- Copy toàn bộ nội dung file `migrations/add_installments_table.sql`
- Paste vào ô SQL
- Click **Go** (Thực hiện)

### Bước 3: Kiểm tra
Chạy các câu lệnh sau để kiểm tra:

```sql
-- Kiểm tra bảng installments đã được tạo
SHOW TABLES LIKE 'installments';

-- Kiểm tra các cột mới trong bảng contracts
DESCRIBE contracts;
```

Nếu thấy:
- ✅ Bảng `installments` tồn tại
- ✅ Bảng `contracts` có thêm 3 cột: `payment_type`, `number_of_installments`, `first_payment`

→ **THÀNH CÔNG!**

---

## 🔧 CÁCH 2: Chạy qua Terminal/CMD

```bash
# Truy cập thư mục dự án
cd c:\wamp64\www\test

# Chạy migration
mysql -u root -p quanly_hopdong < migrations/add_installments_table.sql

# Nhập mật khẩu MySQL khi được yêu cầu
```

---

## 📋 CẤU TRÚC BẢNG MỚI

### Bảng `installments` (Trả góp)

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | INT | ID tự động |
| `contract_id` | INT | ID hợp đồng |
| `installment_number` | INT | Đợt thứ mấy (1, 2, 3...) |
| `amount` | DECIMAL | Số tiền phải trả |
| `due_date` | DATE | Ngày đến hạn |
| `paid_amount` | DECIMAL | Số tiền đã trả |
| `paid_date` | DATE | Ngày thanh toán |
| `status` | ENUM | pending/paid/overdue |
| `payment_method` | VARCHAR | Phương thức TT |
| `notes` | TEXT | Ghi chú |

### Cột mới trong `contracts`

| Cột | Kiểu | Mặc định | Mô tả |
|-----|------|----------|-------|
| `payment_type` | ENUM | 'full' | full hoặc installment |
| `number_of_installments` | INT | 1 | Số đợt trả |
| `first_payment` | DECIMAL | 0 | Tiền đặt cọc/trả trước |

---

## ✅ SAU KHI CHẠY MIGRATION

Bạn có thể:

1. **Import hợp đồng trả góp:**
   - Truy cập: `import_contracts.php`
   - Tải template Excel mới (có cột trả góp)
   - Điền thông tin trả góp
   - Import

2. **Ví dụ dữ liệu trả góp trong Excel:**

| Tên | SĐT | Coach | SĐT Coach | Ngày | Buổi | Giá gốc | Giảm | Giá cuối | Loại TT | Số đợt | Đặt cọc |
|-----|-----|-------|-----------|------|------|---------|------|----------|---------|--------|---------|
| Nguyễn A | 0901234567 | ... | ... | 01/01/2025 | 24 | 12,000,000 | 10 | 10,800,000 | installment | 4 | 3,000,000 |

**Kết quả:**
- Đợt 1: 3,000,000 (ngày 01/01/2025) - ✅ Đã trả
- Đợt 2: 2,600,000 (ngày 01/02/2025) - ⏳ Chưa trả
- Đợt 3: 2,600,000 (ngày 01/03/2025) - ⏳ Chưa trả
- Đợt 4: 2,600,000 (ngày 01/04/2025) - ⏳ Chưa trả

---

## 🐛 TROUBLESHOOTING

### Lỗi: "Table 'installments' already exists"
**Giải pháp:** Bảng đã tồn tại, không cần chạy migration nữa.

### Lỗi: "Duplicate column name 'payment_type'"
**Giải pháp:** Cột đã tồn tại, không cần chạy migration nữa.

### Lỗi: "Access denied"
**Giải pháp:** 
- Đảm bảo user MySQL có quyền ALTER TABLE
- Hoặc dùng user `root`

---

## 📝 GHI CHÚ

- Migration này **AN TOÀN** để chạy nhiều lần (có `IF NOT EXISTS`)
- Không ảnh hưởng đến dữ liệu hiện tại
- Các hợp đồng cũ vẫn hoạt động bình thường (mặc định `payment_type='full'`)

---

**Sau khi chạy migration, hệ thống sẽ hỗ trợ đầy đủ tính năng trả góp!** 💰📊

