<?php
/**
 * 🔧 Script tự động chuyển sang Unsplash
 * Sử dụng khi Clipdrop gặp lỗi hoặc không có key
 */

$envFile = __DIR__ . '/.env';

echo "🔧 ĐANG CHUYỂN IMAGE PROVIDER SANG UNSPLASH...\n\n";

if (!file_exists($envFile)) {
    echo "❌ Không tìm thấy file .env\n";
    echo "📝 Vui lòng tạo file .env trước.\n\n";
    exit(1);
}

// Đọc file hiện tại
$content = file_get_contents($envFile);

// Cập nhật IMAGE_PROVIDER
if (preg_match('/IMAGE_PROVIDER=(.*)/', $content)) {
    $content = preg_replace('/IMAGE_PROVIDER=(.*)/', 'IMAGE_PROVIDER=unsplash', $content);
    echo "✅ Đã chuyển IMAGE_PROVIDER sang 'unsplash'\n";
} else {
    $content .= "\nIMAGE_PROVIDER=unsplash\n";
    echo "✅ Đã thêm IMAGE_PROVIDER=unsplash\n";
}

// Lưu lại
file_put_contents($envFile, $content);
echo "✅ Đã cập nhật file .env\n\n";

echo str_repeat('=', 60) . "\n";
echo "🎉 HOÀN TẤT!\n";
echo str_repeat('=', 60) . "\n\n";

echo "📋 CẤU HÌNH MỚI:\n";
echo "   • Image Provider: Unsplash (MIỄN PHÍ)\n";
echo "   • AI Provider: Groq\n\n";

echo "🚀 BẠN CÓ THỂ:\n";
echo "   1. Truy cập blog_admin.php\n";
echo "   2. Tạo bài viết AI với ảnh\n";
echo "   3. Ảnh sẽ được lấy từ Unsplash (miễn phí, chất lượng cao)\n\n";

echo "✅ LỖI ĐÃ ĐƯỢC SỬA!\n";
?>

