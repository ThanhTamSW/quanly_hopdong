# 🚀 HƯỚNG DẪN DEPLOY LÊN HOSTING

## ⚠️ VẤN ĐỀ: API Error (HTTP 401) trên Hosting

Khi deploy lên hosting, bạn gặp lỗi `Invalid API Key` vì **file `.env` KHÔNG được upload** (do nằm trong `.gitignore`).

---

## 🔧 GIẢI PHÁP NHANH (3 PHÚT)

### Cách 1: Sử dụng script tự động ✨ (KHUYẾN NGHỊ)

1. **Deploy code lên hosting như bình thường**
   ```bash
   git push origin main
   # Hoặc upload qua FTP/cPanel File Manager
   ```

2. **Truy cập script setup:**
   ```
   https://your-domain.com/setup_hosting.php
   ```

3. **Script sẽ tự động:**
   - Tạo file `.env` với API keys đúng
   - Kiểm tra cấu hình
   - Hiển thị trạng thái

4. **XÓA file `setup_hosting.php` sau khi setup xong** (bảo mật)

---

### Cách 2: Tạo file `.env` thủ công

1. **Truy cập hosting qua FTP hoặc cPanel File Manager**

2. **Tạo file mới tên `.env` trong thư mục gốc**

3. **Copy nội dung sau vào file `.env`:**

```env
# AI API Keys
GROQ_API_KEY=your_groq_api_key_here
GEMINI_API_KEY=your_gemini_api_key_here
OPENAI_API_KEY=your_openai_api_key_here
CLIPDROP_API_KEY=your_clipdrop_api_key_here
UNSPLASH_ACCESS_KEY=your_unsplash_access_key_here

# AI Configuration
AI_PROVIDER=groq
IMAGE_PROVIDER=clipdrop

# Blog Settings
BLOG_DEFAULT_LENGTH=very-short
BLOG_TOTAL_HASHTAGS=5
```

**📝 LƯU Ý:** Thay thế `your_xxx_api_key_here` bằng API keys thật của bạn.
- Groq: https://console.groq.com/keys
- Clipdrop: https://clipdrop.co/apis
- Unsplash: https://unsplash.com/oauth/applications

4. **Lưu file**

5. **Kiểm tra quyền file:** Đảm bảo file `.env` có quyền đọc (chmod 644)

---

## 📋 KIỂM TRA SAU KHI SETUP

1. **Kiểm tra API keys:**
   ```
   https://your-domain.com/check_api_keys.php
   ```

2. **Thử tạo bài viết AI:**
   ```
   https://your-domain.com/blog_admin.php
   ```

---

## 🔄 QUY TRÌNH DEPLOY SAU NÀY

Mỗi lần deploy mới:

### Option A: Backup & Restore `.env`

```bash
# TRƯỚC KHI DEPLOY:
# 1. Backup file .env từ hosting (qua FTP/cPanel)
# 2. Lưu file .env vào máy tính

# SAU KHI DEPLOY:
# 3. Upload file .env đã backup lên hosting
```

### Option B: Chạy lại `setup_hosting.php`

```bash
# SAU KHI DEPLOY:
# 1. Truy cập https://your-domain.com/setup_hosting.php
# 2. Script sẽ tự động tạo lại file .env
# 3. Xóa setup_hosting.php sau khi xong
```

---

## 🔒 BẢO MẬT

### ⚠️ QUAN TRỌNG:

1. **KHÔNG commit file `.env` lên Git** (đã có trong `.gitignore`)
2. **XÓA file `setup_hosting.php`** sau khi setup xong
3. **Không share API keys** công khai
4. **Backup file `.env`** ở nơi an toàn

### File cần XÓA sau khi setup:
- ❌ `setup_hosting.php` (sau khi dùng xong)
- ❌ `check_api_keys.php` (nếu không cần debug)
- ❌ `fix_image_provider.php` (nếu không cần đổi provider)
- ❌ `update_clipdrop_key.php` (nếu không cần đổi key)
- ❌ `update_groq_key.php` (nếu không cần đổi key)
- ❌ `update_env_provider.php` (nếu không cần đổi provider)

---

## 🐛 TROUBLESHOOTING

### Lỗi: "File .env không tồn tại"
**Giải pháp:** Tạo file `.env` theo hướng dẫn ở trên

### Lỗi: "Invalid API Key" sau khi tạo `.env`
**Giải pháp:** 
1. Kiểm tra file `.env` có tồn tại không
2. Kiểm tra quyền file (chmod 644)
3. Chạy `check_api_keys.php` để debug

### Lỗi: "Permission denied" khi tạo `.env`
**Giải pháp:**
1. Tạo file `.env` thủ công qua cPanel File Manager
2. Hoặc liên hệ hosting support để cấp quyền ghi

### Lỗi: "API Error (HTTP 403)" với Clipdrop
**Giải pháp:**
1. Chạy `fix_image_provider.php` để chuyển sang Unsplash
2. Hoặc cập nhật Clipdrop key mới

---

## 📞 HỖ TRỢ

Nếu vẫn gặp vấn đề:
1. Chạy `check_api_keys.php` và gửi kết quả
2. Kiểm tra error log của hosting
3. Đảm bảo PHP version >= 8.1

---

## ✅ CHECKLIST DEPLOY

- [ ] Code đã được push lên Git/upload lên hosting
- [ ] File `.env` đã được tạo trên hosting
- [ ] API keys đã được cấu hình đúng
- [ ] Đã test tạo bài viết AI
- [ ] Đã xóa `setup_hosting.php` (bảo mật)
- [ ] Đã backup file `.env` để dùng lại

---

**Hoàn tất! Website của bạn đã sẵn sàng tạo bài viết AI trên hosting!** 🎉

