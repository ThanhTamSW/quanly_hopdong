# 🔀 Git Workflow với --no-ff

## 📋 Quy trình chuẩn

### 1. Tạo branch mới cho mỗi tính năng/fix

```bash
# Tạo branch từ main
git checkout main
git pull origin main
git checkout -b feature/ten-tinh-nang
# hoặc
git checkout -b fix/ten-loi
```

### 2. Làm việc trên branch

```bash
# Thêm và commit các thay đổi
git add .
git commit -m "feat: Mô tả thay đổi"
```

### 3. Merge về main với --no-ff

```bash
# Quay về main
git checkout main

# Merge với --no-ff (tạo merge commit)
git merge --no-ff feature/ten-tinh-nang -m "✨ Merge: feature/ten-tinh-nang - Mô tả ngắn gọn

- Chi tiết thay đổi 1
- Chi tiết thay đổi 2
- Chi tiết thay đổi 3"

# Hoặc dùng alias
git merge-noff feature/ten-tinh-nang -m "..."
```

### 4. Push lên GitHub

```bash
# Push branch (tùy chọn)
git push origin feature/ten-tinh-nang

# Push main
git push origin main
```

### 5. Dọn dẹp branch đã merge

```bash
# Xóa branch local
git branch -d feature/ten-tinh-nang

# Xóa branch remote
git push origin --delete feature/ten-tinh-nang
```

---

## 🎯 Lợi ích của --no-ff

1. **Lịch sử rõ ràng**: Dễ thấy các tính năng/fix độc lập
2. **Dễ revert**: Có thể revert cả nhóm commit cùng lúc
3. **Dễ review**: Thấy được scope của mỗi thay đổi
4. **Professional**: Cấu trúc Git graph đẹp và chuyên nghiệp

---

## 🔧 Git Aliases đã thiết lập

```bash
# Merge với --no-ff
git merge-noff <branch-name> -m "message"
```

---

## 📝 Quy ước đặt tên branch

- `feature/` - Tính năng mới
- `fix/` - Sửa lỗi
- `hotfix/` - Sửa lỗi khẩn cấp
- `refactor/` - Tái cấu trúc code
- `docs/` - Cập nhật tài liệu

---

## 🎨 Quy ước commit message

```
<emoji> <type>: <subject>

<body>
```

**Emoji:**
- ✨ - Feature mới
- 🐛 - Bug fix
- 📝 - Documentation
- ♻️ - Refactoring
- 🚀 - Performance
- 🎨 - UI/Style

**Type:**
- `feat` - Tính năng mới
- `fix` - Sửa lỗi
- `docs` - Tài liệu
- `refactor` - Tái cấu trúc
- `perf` - Tối ưu hiệu suất
- `style` - Format code

---

## 📊 Ví dụ Git Graph

```
*   9b2caaf (main) 🐛 Merge: fix/price-decimal-precision
|\
| * 5e96f94 fix: Thay doi intval thanh floatval
| * ab43d60 fix: Sua loi gia tien hien thi 0d
|/
*   a40869e ✨ Merge: feature/auto-schedule-date
|\
| * 187a584 feat: Tu dong dung ngay bat dau HD
|/
*   aafeea3 🐛 Merge: fix/update-contract-bind-param
```

Đẹp và dễ đọc! 🎉

