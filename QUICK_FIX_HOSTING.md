# ⚡ QUICK FIX - LỖI API 401 TRÊN HOSTING

## 🚨 Vấn đề: `Invalid API Key` error

Lỗi này xảy ra vì file `.env` chưa tồn tại hoặc chưa có API keys trên hosting.

---

## ✅ GIẢI PHÁP NHANH (30 GIÂY)

### Bước 1: Truy cập script auto-fix
Mở URL sau trên hosting của bạn:
```
https://your-domain.com/auto_fix_env.php
```

### Bước 2: Kiểm tra kết quả
Script sẽ:
- ✅ Tự động tạo file `.env` với API keys đúng
- ✅ Kiểm tra quyền đọc file
- ✅ Test load API keys
- ✅ Hiển thị kết quả

### Bước 3: Xóa file `auto_fix_env.php`
**QUAN TRỌNG:** Sau khi fix xong, XÓA file này để bảo mật!

---

## 🧪 TEST NGAY

Sau khi chạy `auto_fix_env.php`:

1. **Kiểm tra API keys:**
   ```
   https://your-domain.com/check_api_keys.php
   ```

2. **Thử tạo bài viết AI:**
   ```
   https://your-domain.com/blog_admin.php
   ```

---

## 🔧 TROUBLESHOOTING

### Nếu vẫn lỗi "Invalid API Key":

#### 1. Kiểm tra file `.env` có tồn tại không:
- Truy cập cPanel File Manager
- Tìm file `.env` trong thư mục gốc
- Nếu không có → Chạy lại `auto_fix_env.php`

#### 2. Kiểm tra nội dung file `.env`:
Mở file `.env` và đảm bảo có nội dung như sau:
```env
GROQ_API_KEY=gsk_...your_key_here...
CLIPDROP_API_KEY=03ac...your_key_here...
UNSPLASH_ACCESS_KEY=0LZ4...your_key_here...
AI_PROVIDER=groq
IMAGE_PROVIDER=clipdrop
```

**Lấy API keys tại:**
- Groq: https://console.groq.com/keys
- Clipdrop: https://clipdrop.co/apis
- Unsplash: https://unsplash.com/oauth/applications

#### 3. Kiểm tra quyền file:
```bash
# Quyền file .env nên là 644
chmod 644 .env
```

#### 4. Kiểm tra PHP có load được `.env` không:
Tạo file `test_env.php`:
```php
<?php
require_once 'includes/env_loader.php';
echo "Groq Key: " . env('GROQ_API_KEY', 'NOT FOUND');
?>
```
Truy cập: `https://your-domain.com/test_env.php`

---

## 📝 LƯU Ý

### File cần XÓA sau khi fix:
- ❌ `auto_fix_env.php` (chứa API keys)
- ❌ `test_env.php` (nếu đã tạo)
- ❌ `setup_hosting.php` (nếu đã dùng)
- ❌ `check_api_keys.php` (nếu không cần debug)

### File CẦN GIỮ:
- ✅ `.env` (chứa API keys, KHÔNG commit lên Git)
- ✅ `includes/env_loader.php` (helper function)

---

## 🔄 MỖI LẦN DEPLOY MỚI

**Vấn đề:** File `.env` KHÔNG được upload khi deploy (do `.gitignore`)

**Giải pháp:**

### Option A: Backup & Restore
```bash
# TRƯỚC deploy: Backup file .env từ hosting
# SAU deploy: Upload file .env đã backup lên hosting
```

### Option B: Chạy lại auto-fix
```bash
# SAU deploy: Truy cập auto_fix_env.php
# Script sẽ tạo lại file .env
# Nhớ XÓA auto_fix_env.php sau khi xong
```

---

## ✅ CHECKLIST

- [ ] Đã chạy `auto_fix_env.php` trên hosting
- [ ] File `.env` đã tồn tại và có nội dung đúng
- [ ] Kiểm tra `check_api_keys.php` → Tất cả keys ✅
- [ ] Test tạo bài viết AI → Thành công ✅
- [ ] Đã XÓA file `auto_fix_env.php`

---

**Lỗi sẽ được fix trong 30 giây!** ⚡

