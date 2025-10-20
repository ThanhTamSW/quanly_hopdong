# 🏋️ Hệ thống Quản lý Hợp đồng & Lịch tập Gym

Hệ thống quản lý toàn diện cho phòng gym với các tính năng quản lý hợp đồng, lịch tập và tính lương tự động.

## ✨ Tính năng chính

### 🔐 Quản lý Người dùng
- Đăng nhập/đăng xuất an toàn
- Đăng ký Coach mới
- Phân quyền người dùng (Coach/Client/Admin)
- Quản lý thông tin cá nhân

### 📋 Quản lý Hợp đồng
- Tạo hợp đồng mới với thông tin đầy đủ
- Danh sách hợp đồng với tìm kiếm và lọc
- Chỉnh sửa và xóa hợp đồng
- Tính giá tự động với giảm giá
- Gói sản phẩm có sẵn và tùy chỉnh

### 📅 Quản lý Lịch tập
- Tạo lịch tập hàng loạt theo tuần
- Thêm buổi tập lẻ
- Cập nhật trạng thái buổi tập (hoàn thành/hủy)
- Sửa đổi và xóa buổi tập
- Lịch tổng quan và lịch cá nhân

### 💰 Tính lương Coach
- Lương cơ bản + phụ cấp
- Hoa hồng bán hàng (4% nếu đạt 80% target)
- Hoa hồng buổi tập (26%)
- Xuất bảng lương Excel chi tiết

### 📊 Báo cáo & Thống kê
- Doanh thu tổng và theo tháng
- Thống kê theo Coach
- Xuất báo cáo Excel
- Import/Export dữ liệu

### 🌐 Giao diện Công khai
- Lịch tập cho học viên (không cần đăng nhập)
- Link chia sẻ lịch tập
- Giao diện responsive

## 🛠️ Yêu cầu hệ thống

- **PHP**: 7.4 trở lên
- **MySQL**: 5.7 trở lên hoặc MariaDB 10.2+
- **Web Server**: Apache hoặc Nginx
- **Composer**: Để cài đặt dependencies

## 📦 Cài đặt

### 1. Clone dự án
```bash
git clone <repository-url>
cd gym-management-system
```

### 2. Cài đặt dependencies
```bash
composer install
```

### 3. Cấu hình database
1. Tạo database MySQL:
```sql
CREATE DATABASE quanly_hopdong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import schema:
```bash
mysql -u root -p quanly_hopdong < database_schema.sql
```

### 4. Cấu hình kết nối database
Chỉnh sửa file `includes/db.php`:
```php
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "quanly_hopdong";
```

### 5. Cấu hình web server

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 🚀 Sử dụng

### 1. Truy cập hệ thống
- URL: `http://your-domain.com`
- Đăng nhập với tài khoản Coach

### 2. Tạo tài khoản Coach đầu tiên
- Truy cập `/register_coach.php`
- Điền thông tin và đăng ký

### 3. Quản lý hợp đồng
- Thêm hợp đồng mới: `/add_contract.php`
- Xem danh sách: `/index.php`
- Quản lý lịch tập: `/view_sessions.php`

### 4. Xem lịch tập
- Lịch tổng quan: `/coach_schedule.php`
- Lịch cá nhân: `/public_schedule_view.php?contract_id=X`

## 📁 Cấu trúc thư mục

```
├── actions/                 # Xử lý logic backend
│   ├── add_single_session.php
│   ├── delete_contract.php
│   ├── export_advanced.php
│   ├── export_salary.php
│   ├── handle_import.php
│   ├── save_contract.php
│   └── update_session_status.php
├── includes/                # File chung
│   ├── db.php              # Kết nối database
│   ├── header.php          # Header chung
│   └── footer.php          # Footer chung
├── vendor/                  # Dependencies (Composer)
├── add_contract.php         # Thêm hợp đồng
├── coach_schedule.php       # Lịch tổng quan
├── edit_contract.php        # Sửa hợp đồng
├── index.php               # Trang chủ
├── login.php               # Đăng nhập
├── public_schedule_view.php # Lịch công khai
├── register_coach.php      # Đăng ký Coach
├── view_sessions.php       # Chi tiết lịch tập
├── database_schema.sql     # Schema database
├── composer.json           # Dependencies
└── README.md              # Hướng dẫn này
```

## 🔧 Cấu hình nâng cao

### 1. Cấu hình gói sản phẩm
Chỉnh sửa trong `add_contract.php`:
```php
$packages = [
    "TF8 - 8 buổi" => ['sessions' => 8, 'price' => 8 * 250000],
    "TF12 - 12 buổi" => ['sessions' => 12, 'price' => 12 * 250000],
    // Thêm gói mới...
];
```

### 2. Cấu hình tính lương
Chỉnh sửa trong `actions/update_session_status.php`:
```php
define('SESSION_COMM_RATE_PERCENT', 26); // 26% hoa hồng buổi tập
```

### 3. Cấu hình hoa hồng bán hàng
Chỉnh sửa trong `actions/export_advanced.php`:
```php
$commission_sale_rate = 4; // 4% hoa hồng bán hàng
```

## 🔒 Bảo mật

- Mật khẩu được hash bằng `password_hash()`
- Sử dụng prepared statements chống SQL injection
- Session management an toàn
- XSS protection với `htmlspecialchars()`

## 📊 Database Schema

### Bảng chính:
- `users`: Thông tin người dùng
- `contracts`: Hợp đồng
- `training_sessions`: Buổi tập
- `payroll_log`: Log lương

### Views hữu ích:
- `coach_revenue_stats`: Thống kê doanh thu Coach
- `current_week_schedule`: Lịch tuần hiện tại

### Stored Procedures:
- `CalculateCoachSalary()`: Tính lương Coach

## 🐛 Troubleshooting

### Lỗi kết nối database
```bash
# Kiểm tra MySQL service
sudo systemctl status mysql

# Kiểm tra kết nối
mysql -u root -p -e "SHOW DATABASES;"
```

### Lỗi Composer
```bash
# Cập nhật Composer
composer self-update

# Cài đặt lại dependencies
rm -rf vendor/
composer install
```

### Lỗi quyền file
```bash
# Cấp quyền cho thư mục
chmod -R 755 /path/to/project
chown -R www-data:www-data /path/to/project
```

## 🤝 Đóng góp

1. Fork dự án
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Tạo Pull Request

## 📝 License

Dự án này được phân phối dưới MIT License. Xem file `LICENSE` để biết thêm chi tiết.

## 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng tạo issue trên GitHub hoặc liên hệ:
- Email: support@example.com
- Phone: +84 xxx xxx xxx

## 🎯 Roadmap

- [ ] API REST cho mobile app
- [ ] Thông báo push
- [ ] Tích hợp thanh toán online
- [ ] Dashboard analytics
- [ ] Multi-language support

---

**Phiên bản**: 1.0.0  
**Cập nhật cuối**: 2024-12-19  
**Tác giả**: Development Team
