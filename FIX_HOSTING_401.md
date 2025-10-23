# ⚡ FIX LỖI API 401 TRÊN HOSTING - 2 PHÚT

## 🚨 Vấn đề
```
API Error (HTTP 401): {"error":{"message":"Invalid API Key"}}
```

## ✅ GIẢI PHÁP NHANH NHẤT: UPLOAD FILE `.env`

### Bước 1: Tạo file `.env` trên máy tính
1. Copy file `env.template` thành `.env`
2. Hoặc tạo file mới tên `.env` và copy nội dung từ `env.template`
3. Thay thế các `your_xxx_api_key_here` bằng API keys thật của bạn

**Lấy API keys tại:**
- Groq: https://console.groq.com/keys
- Clipdrop: https://clipdrop.co/apis
- Unsplash: https://unsplash.com/oauth/applications

### Bước 2: Upload lên hosting
1. Đăng nhập **cPanel**
2. Mở **File Manager**
3. Click **Settings** (góc trên phải) → Tick **"Show Hidden Files (dotfiles)"** → Save
4. Vào thư mục gốc website (public_html hoặc www)
5. Click **Upload** → Chọn file `.env` → Upload
6. **XONG!**

### Bước 3: Kiểm tra
Truy cập: `https://your-domain.com/check_api_keys.php`

Nếu thấy tất cả ✅ → **THÀNH CÔNG!**

---

## 🔧 CÁCH 2: TẠO TRỰC TIẾP TRÊN HOSTING

1. Mở **cPanel File Manager**
2. Settings → Show Hidden Files
3. Click **+ File** → Tên: `.env`
4. Click phải file `.env` → **Edit**
5. Copy nội dung từ `env.template`
6. Thay thế API keys
7. Save

---

## 📋 LƯU Ý

- File `.env` là file ẩn (dotfile) nên cần bật "Show Hidden Files" trong File Manager
- Quyền file `.env` nên là **644**
- File `.env` KHÔNG được commit lên Git (đã có trong `.gitignore`)
- Mỗi lần deploy mới, cần upload lại file `.env`

---

## ✅ CHECKLIST

- [ ] File `.env` đã tồn tại trên hosting
- [ ] API keys đã được điền vào
- [ ] `check_api_keys.php` hiển thị tất cả keys ✅
- [ ] Test tạo bài viết AI thành công

---

**Lỗi sẽ biến mất ngay sau khi upload file `.env`!** 🚀

