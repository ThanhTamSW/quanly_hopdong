# 🤖 Hướng dẫn Setup Blog AI

## Tổng quan
Hệ thống blog tích hợp AI để tự động tạo nội dung chất lượng cao từ chủ đề bạn yêu cầu.

---

## 🚀 Bước 1: Tạo Database

Chạy file setup (đã tự động mở):
```
http://localhost/test/create_blog_table.php
```

Hoặc import SQL thủ công vào phpMyAdmin.

---

## 🔑 Bước 2: Lấy API Key (QUAN TRỌNG)

### Option 1: Google Gemini (KHUYẾN NGHỊ - MIỄN PHÍ)

1. Truy cập: https://makersuite.google.com/app/apikey
2. Đăng nhập Google
3. Click "Create API Key"
4. Copy API key

**Ưu điểm:**
- ✅ Hoàn toàn miễn phí
- ✅ Không cần thẻ tín dụng
- ✅ Chất lượng tốt
- ✅ Giới hạn cao (60 requests/phút)

### Option 2: OpenAI GPT (CÓ PHÍ)

1. Truy cập: https://platform.openai.com/api-keys
2. Đăng ký tài khoản
3. Nạp tiền ($5-$20)
4. Tạo API key

**Ưu điểm:**
- ✅ Chất lượng rất cao
- ✅ Nhiều model lựa chọn (GPT-3.5, GPT-4)

**Nhược điểm:**
- ❌ Mất phí (~$0.002/1000 tokens)

---

## ⚙️ Bước 3: Cấu hình API

1. Copy file mẫu:
```bash
copy config.ai.example.php config.ai.php
```

2. Mở `config.ai.php` và chỉnh sửa:

### Cấu hình Gemini (Miễn phí):
```php
return [
    'ai_provider' => 'gemini',
    
    'gemini' => [
        'api_key' => 'AIzaSy...your-key-here',  // Dán API key của bạn
        'model' => 'gemini-pro',
        'temperature' => 0.7
    ]
];
```

### Cấu hình OpenAI:
```php
return [
    'ai_provider' => 'openai',
    
    'openai' => [
        'api_key' => 'sk-...your-key-here',  // Dán API key của bạn
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 2000,
        'temperature' => 0.7
    ]
];
```

---

## 📝 Bước 4: Sử dụng

### Tạo bài viết mới:

1. Vào **Blog AI** từ menu
2. Nhập chủ đề (càng cụ thể càng tốt):
   ```
   VD: "10 bài tập tăng cơ ngực hiệu quả cho người mới"
   ```
3. Chọn độ dài:
   - **Ngắn:** 500-700 từ (3-5 phút đọc)
   - **Trung bình:** 1000-1500 từ (6-10 phút đọc)
   - **Dài:** 2000-3000 từ (15-20 phút đọc)
4. Chọn phong cách:
   - **Chuyên nghiệp:** Chuẩn mực, học thuật
   - **Thân mật:** Gần gũi, dễ hiểu
   - **Thân thiện:** Vui vẻ, nhiệt tình
5. Click **"Tạo bài viết với AI"**
6. Chờ 10-30 giây
7. Bài viết được tạo tự động!

### Quản lý bài viết:

- **Sửa:** Chỉnh sửa nội dung, tiêu đề
- **Xuất bản:** Hiển thị công khai
- **Nháp:** Lưu để sửa sau
- **Lưu trữ:** Ẩn bài viết cũ

---

## 🎯 Tips để có bài viết tốt

### ✅ Chủ đề tốt:
- "Hướng dẫn sử dụng máy chạy bộ đúng cách cho người mới"
- "Chế độ ăn tăng cơ 3000 calories/ngày cho nam"
- "5 sai lầm phổ biến khi tập Squat và cách khắc phục"

### ❌ Chủ đề tránh:
- "Gym" (quá chung chung)
- "Tập" (không rõ ràng)
- "Fitness" (thiếu focus)

### 💡 Mẹo:
- Thêm số lượng: "10 bài tập...", "5 cách..."
- Thêm đối tượng: "...cho người mới", "...cho nam giới"
- Thêm mục tiêu: "...để tăng cơ", "...giảm mỡ bụng"

---

## 📊 Các tính năng

✅ **AI Content Generation:** Tự động tạo bài viết đầy đủ  
✅ **Multi-Provider:** Hỗ trợ Gemini & OpenAI  
✅ **SEO Friendly:** Tự động tối ưu cấu trúc  
✅ **Markdown Support:** Format đẹp mắt  
✅ **Draft System:** Lưu nháp, sửa sau  
✅ **View Counter:** Đếm lượt xem  
✅ **Search:** Tìm kiếm full-text  
✅ **Categories:** Phân loại bài viết  

---

## 🔧 Troubleshooting

### Lỗi: "Chưa cấu hình AI API"
→ Bạn chưa tạo file `config.ai.php`. Copy từ `config.ai.example.php`

### Lỗi: "API Error (HTTP 401)"
→ API key không đúng. Kiểm tra lại key trong `config.ai.php`

### Lỗi: "cURL timeout"
→ Kết nối internet chậm. Tăng timeout hoặc thử lại

### AI tạo nội dung tiếng Anh
→ Thêm "Viết bằng tiếng Việt" vào chủ đề

### Bài viết quá ngắn
→ Chọn độ dài "Dài" và thêm chi tiết vào chủ đề

---

## 💰 Chi phí ước tính

### Gemini (Miễn phí):
- **Giá:** $0 (Free tier)
- **Giới hạn:** 60 requests/phút
- **Chi phí/bài:** $0

### OpenAI GPT-3.5:
- **Giá:** $0.002/1000 tokens
- **1 bài viết ~1500 từ:** ~$0.004 (100đ)
- **100 bài:** ~$0.40 (10,000đ)

### OpenAI GPT-4:
- **Giá:** $0.03/1000 tokens
- **1 bài viết ~1500 từ:** ~$0.05 (1,250đ)
- **100 bài:** ~$5 (125,000đ)

---

## 📞 Hỗ trợ

Nếu gặp vấn đề, kiểm tra:
1. API key đã đúng chưa
2. File `config.ai.php` đã tồn tại chưa
3. Internet có kết nối không
4. Database đã tạo bảng chưa

**Khuyến nghị:** Dùng **Gemini** - miễn phí và chất lượng tốt!

---

🎉 **Chúc bạn tạo blog thành công!**

