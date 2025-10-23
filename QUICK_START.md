# 🚀 Quick Start Guide

## 📦 Cài đặt nhanh

### 1. Clone repository
```bash
git clone https://github.com/ThanhTamSW/quanly_hopdong.git
cd quanly_hopdong
```

### 2. Cấu hình môi trường

Tạo file `.env` trong thư mục gốc:

```env
# AI API Keys
GEMINI_API_KEY=your_key_here
OPENAI_API_KEY=your_key_here
GROQ_API_KEY=your_key_here
UNSPLASH_ACCESS_KEY=your_key_here
CLIPDROP_API_KEY=your_key_here

# AI Configuration
AI_PROVIDER=groq
IMAGE_PROVIDER=unsplash

# Blog Settings
BLOG_DEFAULT_LENGTH=very-short
BLOG_TOTAL_HASHTAGS=5
```

### 3. Cấu hình database

Import file SQL vào database của bạn (nếu có).

### 4. Khởi động

```bash
# Với WAMP/XAMPP
- Đặt project vào thư mục www/htdocs
- Truy cập: http://localhost/quanly_hopdong
```

---

## 🔑 Lấy API Keys miễn phí

### Groq (Khuyến nghị - Nhanh & Miễn phí)
1. Truy cập: https://console.groq.com/keys
2. Đăng ký/Đăng nhập
3. Tạo API key mới
4. Copy và paste vào `.env`

### Gemini
1. Truy cập: https://makersuite.google.com/app/apikey
2. Tạo API key
3. Copy vào `.env`

### Unsplash (cho ảnh blog)
1. Truy cập: https://unsplash.com/oauth/applications
2. Tạo ứng dụng mới
3. Lấy Access Key
4. Copy vào `.env`

---

## 🎯 Các tính năng chính

- ✅ Quản lý hợp đồng PT
- ✅ Quản lý lịch tập
- ✅ Tính toán hoa hồng
- ✅ Xuất lương theo tháng
- ✅ Trả góp và theo dõi thanh toán
- ✅ AI Blog generator (Groq/Gemini/OpenAI)
- ✅ Responsive design

---

## 📚 Tài liệu

- [Git Workflow](GIT_WORKFLOW.md) - Quy trình làm việc với Git
- [Setup Guide](SETUP.md) - Hướng dẫn setup chi tiết
- [API Error Fix](FIX_API_ERROR.md) - Sửa lỗi API 401

---

## 🆘 Hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra file `.env` đã đúng chưa
2. Xem [FIX_API_ERROR.md](FIX_API_ERROR.md)
3. Chạy `php update_groq_key.php` để update Groq key


