# 🌳 Git Workflow - Tạo cấu trúc branch đẹp

## 📌 Quy tắc đặt tên branch

### Theo loại công việc:
- `feature/` - Tính năng mới
- `fix/` - Sửa lỗi
- `enhance/` - Cải tiến
- `refactor/` - Tái cấu trúc code
- `docs/` - Tài liệu
- `test/` - Testing

### Ví dụ:
```bash
feature/blog-ai-generation
feature/payment-installment
fix/api-401-error
enhance/ui-responsive
refactor/database-structure
```

---

## 🔄 Quy trình làm việc

### Bước 1: Tạo branch mới từ main

```bash
git checkout main
git pull origin main
git checkout -b feature/tên-tính-năng
```

### Bước 2: Làm việc và commit

```bash
# Commit nhỏ, rõ ràng
git add .
git commit -m "✨ Add: Mô tả ngắn gọn"
git commit -m "🔧 Fix: Sửa lỗi X"
git commit -m "📝 Docs: Cập nhật tài liệu"
```

### Bước 3: Push branch lên GitHub

```bash
git push -u origin feature/tên-tính-năng
```

### Bước 4: Merge vào main (DÙNG --no-ff)

```bash
git checkout main
git pull origin main
git merge --no-ff feature/tên-tính-năng -m "Merge feature: Tên tính năng

- Chi tiết 1
- Chi tiết 2
- Chi tiết 3"
```

### Bước 5: Push main và xóa branch (optional)

```bash
git push origin main

# Xóa branch local
git branch -d feature/tên-tính-năng

# Xóa branch remote
git push origin --delete feature/tên-tính-năng
```

---

## 🎨 Emoji Commits (Conventional Commits)

| Emoji | Code | Mục đích |
|-------|------|----------|
| ✨ | `:sparkles:` | Tính năng mới |
| 🐛 | `:bug:` | Sửa lỗi |
| 🔧 | `:wrench:` | Cấu hình |
| 📝 | `:memo:` | Tài liệu |
| 🎨 | `:art:` | Cải thiện UI/UX |
| ⚡️ | `:zap:` | Tối ưu hiệu suất |
| 🔥 | `:fire:` | Xóa code/file |
| 🚀 | `:rocket:` | Deploy |
| ♻️ | `:recycle:` | Refactor |
| 🔒 | `:lock:` | Bảo mật |

---

## 📊 Cấu trúc mong muốn

```
main
  │
  ├─── feature/blog-ai-generation (merged)
  │    ├─ ✨ Add AI blog post generator
  │    ├─ 🎨 Add blog UI
  │    └─ 🔧 Add AI config
  │
  ├─── fix/api-401-error (merged)
  │    ├─ 🔧 Add fix documentation
  │    ├─ ✨ Add update script
  │    └─ 🔒 Move API keys to .env
  │
  └─── enhance/ui-responsive (merged)
       ├─ 🎨 Add responsive tables
       ├─ 🎨 Add mobile navigation
       └─ 🎨 Add animations
```

---

## 🛠️ Lệnh hữu ích

### Xem graph trong terminal:
```bash
git log --oneline --graph --all --decorate
```

### Xem graph đẹp hơn:
```bash
git log --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr) %C(bold blue)<%an>%Creset' --abbrev-commit --all
```

### Tạo alias cho lệnh trên:
```bash
git config --global alias.lg "log --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr) %C(bold blue)<%an>%Creset' --abbrev-commit --all"

# Sau đó chỉ cần:
git lg
```

---

## 📱 SmartGit Settings

### Để hiển thị đẹp trong SmartGit:

1. **View → Show Log Window**
2. **Preferences → Commands → Merge**
   - ✅ Check "Always create merge commit (--no-ff)"
3. **Preferences → User Interface → Log**
   - ✅ Show branch labels
   - ✅ Show tags
   - ✅ Compact branch layout

---

## 🚨 Lưu ý quan trọng

### ❌ KHÔNG làm:
- Commit trực tiếp vào `main`
- Force push `main` (trừ khi thật sự cần)
- Merge bằng fast-forward cho feature lớn
- Đặt tên branch không rõ ràng

### ✅ NÊN làm:
- Luôn tạo branch mới cho mỗi task
- Commit message rõ ràng, có emoji
- Merge bằng `--no-ff` cho feature
- Pull trước khi merge
- Xóa branch sau khi merge xong

---

## 📚 Ví dụ thực tế

### Tạo feature mới:
```bash
git checkout main
git pull
git checkout -b feature/revenue-target-display

# ... làm việc ...
git add .
git commit -m "✨ Add revenue target calculation"
git commit -m "🎨 Add target progress bar"
git push -u origin feature/revenue-target-display

# Merge
git checkout main
git merge --no-ff feature/revenue-target-display -m "Merge feature: Revenue Target Display

- Add target calculation logic
- Add visual progress bar
- Add percentage comparison"

git push origin main
git branch -d feature/revenue-target-display
git push origin --delete feature/revenue-target-display
```

---

## 🎯 Kết quả

Sau khi làm theo workflow này, Git graph của bạn sẽ:

✅ Có cấu trúc rõ ràng  
✅ Dễ review code  
✅ Dễ rollback nếu cần  
✅ Dễ track ai làm gì, khi nào  
✅ Đẹp trong SmartGit và GitHub  


