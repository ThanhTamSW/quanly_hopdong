# 🔧 Sửa Lỗi API Error (HTTP 401): Invalid API Key

## ❌ Lỗi
```
API Error (HTTP 401): {"error":{"message":"Invalid API Key"}}
```

## 🔍 Nguyên nhân
OpenAI API key không hợp lệ, hết hạn, hoặc hết quota.

## ✅ Giải pháp

### Cách 1: Chuyển sang Groq (KHUYẾN NGHỊ - MIỄN PHÍ)

1. **Mở file `.env`** trong thư mục gốc
2. **Tìm dòng:**
   ```
   AI_PROVIDER=openai
   ```
3. **Đổi thành:**
   ```
   AI_PROVIDER=groq
   ```
4. **Lưu file** và refresh trang

**Groq là gì?**
- ✅ Hoàn toàn MIỄN PHÍ
- ✅ Rất nhanh (2-3 giây)
- ✅ Model mạnh: Llama 3.3 70B
- ✅ Không cần đăng ký thêm (API key đã có sẵn)

### Cách 2: Lấy OpenAI API Key mới

1. Truy cập: https://platform.openai.com/api-keys
2. Tạo API key mới
3. **Thêm phương thức thanh toán** (OpenAI yêu cầu)
4. Copy API key
5. Mở file `.env`, tìm:
   ```
   OPENAI_API_KEY=your-openai-api-key-here
   ```
6. Thay bằng key mới:
   ```
   OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxx
   ```

### Cách 3: Dùng Gemini (MIỄN PHÍ)

1. Mở file `.env`
2. Đổi:
   ```
   AI_PROVIDER=gemini
   ```
3. Lấy Gemini API key tại: https://makersuite.google.com/app/apikey
4. Thêm vào `.env`:
   ```
   GEMINI_API_KEY=AIzaSy...
   ```

## 🎯 So sánh AI Providers

| Provider | Giá | Tốc độ | Chất lượng | Khuyến nghị |
|----------|-----|--------|------------|-------------|
| **Groq** | 🆓 Miễn phí | ⚡ Rất nhanh (2-3s) | ⭐⭐⭐⭐ | ✅ **Dùng ngay** |
| **Gemini** | 🆓 Miễn phí | 🐢 Trung bình (5-8s) | ⭐⭐⭐⭐⭐ | ✅ Tốt |
| **OpenAI** | 💰 Có phí | ⚡ Nhanh (3-5s) | ⭐⭐⭐⭐⭐ | ⚠️ Cần thanh toán |

## 🧪 Test sau khi fix

1. Truy cập: `http://localhost/test/blog_admin.php`
2. Nhập chủ đề bài viết
3. Click "Tạo bài viết"
4. Nếu thành công → ✅ Đã fix!

## 💡 Lưu ý

- File `.env` **KHÔNG được commit** lên GitHub (đã ignore)
- Mỗi môi trường (dev/production) cần `.env` riêng
- Nếu clone repo mới, copy `.env.example` → `.env` và điền API keys

## 📞 Cần hỗ trợ?

Nếu vẫn gặp lỗi, kiểm tra:
1. File `.env` có tồn tại không?
2. API keys có đúng format không?
3. Groq API key: `GROQ_API_KEY=gsk_...`
4. Internet connection OK?

---

**🎉 Khuyến nghị:** Dùng **Groq** - miễn phí, nhanh, không cần setup gì thêm!

