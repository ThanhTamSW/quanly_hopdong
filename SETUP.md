# 🚀 Hướng Dẫn Cài Đặt

## 📋 Yêu cầu
- PHP 7.4+
- MySQL 5.7+
- Composer
- WAMP/XAMPP hoặc web server

## ⚙️ Cài Đặt

### 1. Clone Repository
```bash
git clone https://github.com/your-username/your-repo.git
cd your-repo
```

### 2. Cài Đặt Dependencies
```bash
composer install
```

### 3. Cấu Hình Database
Tạo database và import schema:
```bash
mysql -u root -p < database_schema.sql
```

Sửa file `includes/db.php` với thông tin database của bạn.

### 4. Cấu Hình AI API Keys

#### Bước 1: Tạo file `.env`
```bash
cp .env.example .env
```

#### Bước 2: Lấy API Keys MIỄN PHÍ

**Groq API** (Khuyến nghị - Nhanh nhất & Miễn phí)
1. Truy cập: https://console.groq.com/keys
2. Đăng ký/Đăng nhập
3. Tạo API key mới
4. Copy và dán vào `.env`:
   ```
   GROQ_API_KEY=gsk_your_key_here
   ```

**Unsplash** (Hình ảnh miễn phí)
1. Truy cập: https://unsplash.com/oauth/applications
2. Tạo ứng dụng mới
3. Copy "Access Key"
4. Dán vào `.env`:
   ```
   UNSPLASH_ACCESS_KEY=your_key_here
   ```

**Google Gemini** (Tùy chọn)
1. Truy cập: https://makersuite.google.com/app/apikey
2. Tạo API key
3. Dán vào `.env`:
   ```
   GEMINI_API_KEY=your_key_here
   ```

**OpenAI** (Tùy chọn - Có phí)
1. Truy cập: https://platform.openai.com/api-keys
2. Tạo API key (cần thêm phương thức thanh toán)
3. Dán vào `.env`:
   ```
   OPENAI_API_KEY=sk-your_key_here
   ```

#### Bước 3: Cấu hình AI Provider
Mở file `.env` và chọn provider:
```
AI_PROVIDER=groq        # groq, gemini, hoặc openai
IMAGE_PROVIDER=unsplash # unsplash, clipdrop, dalle, hoặc none
```

### 5. Cấu Hình Upload Directory
Tạo thư mục uploads và set quyền:
```bash
mkdir -p uploads/blog_images
chmod 755 uploads
chmod 755 uploads/blog_images
```

### 6. Chạy Ứng Dụng
Mở trình duyệt và truy cập:
```
http://localhost/test/
```

## 🔐 Bảo Mật

**⚠️ QUAN TRỌNG:**
- File `.env` chứa API keys - **KHÔNG BAO GIỜ** commit file này lên GitHub
- File `.env` đã được thêm vào `.gitignore`
- Chỉ commit file `.env.example` (template không có keys thật)
- Không chia sẻ API keys với người khác

## 🎯 Tính Năng

### ✅ Đã Hoàn Thành
- ✅ Quản lý hợp đồng PT
- ✅ Lịch tập & thanh toán
- ✅ Quản lý target doanh thu
- ✅ Trả góp nhiều đợt
- ✅ Blog AI tự động
- ✅ Tạo hình ảnh AI
- ✅ Responsive design

### 🤖 AI Blog
Hệ thống tự động tạo bài viết và hình ảnh:
- **Text**: Groq (miễn phí, 2-3 giây)
- **Image**: Unsplash (miễn phí, stock photos)
- **Logo**: Tự động thêm watermark "TRANSFORM"

## 📝 Cấu Trúc Thư Mục
```
test/
├── actions/              # Backend logic
├── api/                  # API endpoints
├── includes/             # Shared files
│   ├── db.php           # Database connection
│   ├── header.php       # Header & navigation
│   ├── footer.php       # Footer
│   ├── env_loader.php   # Environment loader
│   └── ai_helper.php    # AI functions
├── uploads/             # User uploads (ignored)
├── .env                 # API keys (ignored)
├── .env.example         # Template
├── .gitignore          # Git ignore rules
└── config.ai.php       # AI configuration
```

## 🐛 Troubleshooting

### Lỗi: "API key not found"
- Kiểm tra file `.env` đã tồn tại chưa
- Kiểm tra API keys đã đúng format chưa
- Thử xóa khoảng trắng đầu/cuối keys

### Lỗi: "cURL SSL certificate"
- Thêm vào `php.ini`:
  ```ini
  curl.cainfo = "C:/path/to/cacert.pem"
  ```
- Download cacert: https://curl.se/docs/caextract.html

### Lỗi database connection
- Kiểm tra MySQL service đã chạy chưa
- Kiểm tra username/password trong `includes/db.php`

## 📞 Hỗ Trợ
Gặp vấn đề? Tạo issue trên GitHub hoặc liên hệ admin.

## 📄 License
MIT License - Tự do sử dụng và chỉnh sửa.

