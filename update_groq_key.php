<?php
/**
 * 🔧 Script cập nhật Groq API Key
 * 
 * Chạy: php update_groq_key.php
 */

$envFile = __DIR__ . '/.env';

// ⚠️ IMPORTANT: Thay 'YOUR_GROQ_API_KEY_HERE' bằng API key thực của bạn
// Get free key: https://console.groq.com/keys
$newGroqKey = 'YOUR_GROQ_API_KEY_HERE';

// Nếu bạn đã có key, uncomment dòng dưới và paste key vào:
// $newGroqKey = 'gsk_...';

echo "🔄 Đang cập nhật Groq API Key...\n\n";

// Kiểm tra xem đã set API key chưa
if ($newGroqKey === 'YOUR_GROQ_API_KEY_HERE') {
    echo "❌ LỖI: Bạn chưa cập nhật API key!\n\n";
    echo "📝 HƯỚNG DẪN:\n";
    echo "   1. Mở file update_groq_key.php\n";
    echo "   2. Tìm dòng: \$newGroqKey = 'YOUR_GROQ_API_KEY_HERE';\n";
    echo "   3. Thay 'YOUR_GROQ_API_KEY_HERE' bằng key của bạn\n";
    echo "   4. Lưu file và chạy lại: php update_groq_key.php\n\n";
    echo "🔗 Lấy key miễn phí tại: https://console.groq.com/keys\n\n";
    exit(1);
}

// Kiểm tra file .env có tồn tại không
if (!file_exists($envFile)) {
    echo "❌ Không tìm thấy file .env\n";
    echo "📝 Đang tạo file .env mới...\n\n";
    
    $envContent = "# ===================================
# 🔑 AI API KEYS
# ===================================

# Gemini API Key (Google AI Studio)
GEMINI_API_KEY=your_gemini_key_here

# OpenAI API Key
OPENAI_API_KEY=your_openai_key_here

# Groq API Key (FREE & FAST)
GROQ_API_KEY=$newGroqKey

# Unsplash API Key
UNSPLASH_ACCESS_KEY=your_unsplash_key_here

# Clipdrop API Key
CLIPDROP_API_KEY=your_clipdrop_key_here

# ===================================
# ⚙️ AI CONFIGURATION
# ===================================

AI_PROVIDER=groq
IMAGE_PROVIDER=unsplash

# ===================================
# 📝 BLOG SETTINGS
# ===================================

BLOG_DEFAULT_LENGTH=very-short
BLOG_TOTAL_HASHTAGS=5
";
    
    file_put_contents($envFile, $envContent);
    echo "✅ Đã tạo file .env với Groq API key mới!\n";
    
} else {
    // Đọc nội dung file .env hiện tại
    $envContent = file_get_contents($envFile);
    
    // Cập nhật Groq API key
    if (preg_match('/GROQ_API_KEY=(.*)/', $envContent)) {
        $envContent = preg_replace('/GROQ_API_KEY=(.*)/', "GROQ_API_KEY=$newGroqKey", $envContent);
        echo "✅ Đã cập nhật Groq API key trong file .env\n";
    } else {
        $envContent .= "\nGROQ_API_KEY=$newGroqKey\n";
        echo "✅ Đã thêm Groq API key vào file .env\n";
    }
    
    // Cập nhật AI provider
    if (preg_match('/AI_PROVIDER=(.*)/', $envContent)) {
        $envContent = preg_replace('/AI_PROVIDER=(.*)/', 'AI_PROVIDER=groq', $envContent);
        echo "✅ Đã chuyển AI provider sang Groq\n";
    } else {
        $envContent .= "\nAI_PROVIDER=groq\n";
        echo "✅ Đã thêm AI_PROVIDER=groq vào file .env\n";
    }
    
    // Lưu lại file
    file_put_contents($envFile, $envContent);
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "🎉 CẬP NHẬT THÀNH CÔNG!\n";
echo str_repeat('=', 50) . "\n\n";

echo "📋 Cấu hình hiện tại:\n";
echo "   • Groq API Key: " . substr($newGroqKey, 0, 10) . "...\n";
echo "   • AI Provider: groq\n";
echo "   • Model: llama-3.3-70b-versatile\n\n";

echo "🚀 BẠN CÓ THỂ:\n";
echo "   1. Truy cập blog_admin.php để tạo bài viết AI\n";
echo "   2. API sẽ tự động dùng Groq (miễn phí, nhanh)\n\n";

echo "✅ HOÀN TẤT!\n";
?>
