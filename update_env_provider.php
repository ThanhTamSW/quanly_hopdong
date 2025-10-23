<?php
/**
 * Script để sửa lỗi OpenAI API Invalid Key
 * Chuyển AI provider từ OpenAI sang Groq (miễn phí)
 */

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "❌ File .env không tồn tại!\n";
    echo "Vui lòng copy .env.example thành .env:\n";
    echo "cp .env.example .env\n";
    exit(1);
}

// Đọc file .env
$envContent = file_get_contents($envFile);

// Kiểm tra provider hiện tại
if (preg_match('/^AI_PROVIDER=(.+)$/m', $envContent, $matches)) {
    $currentProvider = trim($matches[1]);
    echo "🔍 Provider hiện tại: $currentProvider\n";
    
    if ($currentProvider === 'openai') {
        // Đổi sang groq
        $envContent = preg_replace('/^AI_PROVIDER=.+$/m', 'AI_PROVIDER=groq', $envContent);
        
        // Lưu lại file
        if (file_put_contents($envFile, $envContent)) {
            echo "✅ Đã chuyển AI provider từ OpenAI → Groq (miễn phí)\n";
            echo "\n📝 Thông tin:\n";
            echo "- Groq API: MIỄN PHÍ, nhanh (2-3 giây)\n";
            echo "- Model: llama-3.3-70b-versatile\n";
            echo "- Không cần API key mới (đã có sẵn)\n";
            echo "\n🎉 HOÀN TẤT! Bây giờ AI blog sẽ hoạt động bình thường.\n";
        } else {
            echo "❌ Lỗi khi ghi file .env\n";
            exit(1);
        }
    } elseif ($currentProvider === 'groq') {
        echo "✅ Đã đang dùng Groq rồi - không cần thay đổi!\n";
        echo "\nNếu vẫn gặp lỗi, kiểm tra:\n";
        echo "1. GROQ_API_KEY trong file .env\n";
        echo "2. GROQ_MODEL=llama-3.3-70b-versatile\n";
    } else {
        echo "⚠️ Provider hiện tại: $currentProvider\n";
        echo "Có muốn chuyển sang Groq không? (y/n)\n";
    }
} else {
    echo "❌ Không tìm thấy AI_PROVIDER trong file .env\n";
    echo "Thêm dòng này vào .env:\n";
    echo "AI_PROVIDER=groq\n";
}

echo "\n💡 Sau khi chạy xong, bạn có thể xóa file này:\n";
echo "rm update_env_provider.php\n";
?>

