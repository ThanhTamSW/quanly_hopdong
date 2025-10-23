<?php
/**
 * 🔍 Script kiểm tra API keys
 */

require_once 'includes/env_loader.php';

echo "🔍 KIỂM TRA API KEYS\n";
echo str_repeat('=', 60) . "\n\n";

// Kiểm tra file .env
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "❌ File .env không tồn tại!\n";
    echo "📝 Vui lòng tạo file .env và thêm API keys của bạn.\n\n";
    echo "📋 Template .env:\n";
    echo str_repeat('-', 60) . "\n";
    echo "GROQ_API_KEY=your_key_here\n";
    echo "GEMINI_API_KEY=your_key_here\n";
    echo "CLIPDROP_API_KEY=your_key_here\n";
    echo "UNSPLASH_ACCESS_KEY=your_key_here\n";
    echo "AI_PROVIDER=groq\n";
    echo "IMAGE_PROVIDER=clipdrop\n";
    echo str_repeat('-', 60) . "\n\n";
    exit(1);
}

// Load env
echo "📋 CURRENT CONFIGURATION:\n";
echo str_repeat('-', 60) . "\n";

// Check AI Provider
$aiProvider = env('AI_PROVIDER', 'not set');
echo "AI Provider: $aiProvider\n";

// Check Image Provider
$imageProvider = env('IMAGE_PROVIDER', 'not set');
echo "Image Provider: $imageProvider\n\n";

// Check API Keys
echo "📋 API KEYS STATUS:\n";
echo str_repeat('-', 60) . "\n";

// Groq
$groqKey = env('GROQ_API_KEY', '');
if (!empty($groqKey) && $groqKey !== 'YOUR_GROQ_API_KEY_HERE') {
    echo "✅ Groq API Key: " . substr($groqKey, 0, 10) . "...\n";
} else {
    echo "❌ Groq API Key: NOT SET\n";
}

// Gemini
$geminiKey = env('GEMINI_API_KEY', '');
if (!empty($geminiKey) && $geminiKey !== 'your-gemini-api-key-here') {
    echo "✅ Gemini API Key: " . substr($geminiKey, 0, 10) . "...\n";
} else {
    echo "⚠️  Gemini API Key: NOT SET\n";
}

// OpenAI
$openaiKey = env('OPENAI_API_KEY', '');
if (!empty($openaiKey) && $openaiKey !== 'your-openai-api-key-here') {
    echo "✅ OpenAI API Key: " . substr($openaiKey, 0, 10) . "...\n";
} else {
    echo "⚠️  OpenAI API Key: NOT SET\n";
}

// Unsplash
$unsplashKey = env('UNSPLASH_ACCESS_KEY', '');
if (!empty($unsplashKey) && $unsplashKey !== 'your-unsplash-access-key-here') {
    echo "✅ Unsplash API Key: " . substr($unsplashKey, 0, 10) . "...\n";
} else {
    echo "❌ Unsplash API Key: NOT SET\n";
}

// Clipdrop
$clipdropKey = env('CLIPDROP_API_KEY', '');
if (!empty($clipdropKey) && $clipdropKey !== 'your-clipdrop-api-key-here') {
    echo "✅ Clipdrop API Key: " . substr($clipdropKey, 0, 10) . "...\n";
} else {
    echo "❌ Clipdrop API Key: NOT SET (ĐANG LỖI)\n";
}

echo "\n" . str_repeat('=', 60) . "\n";

// Recommendations
echo "\n💡 KHUYẾN NGHỊ:\n";
echo str_repeat('-', 60) . "\n";

if ($imageProvider === 'clipdrop' && (empty($clipdropKey) || $clipdropKey === 'your-clipdrop-api-key-here')) {
    echo "🔴 LỖI: Bạn đang dùng Clipdrop nhưng chưa có API key!\n\n";
    echo "🔧 GIẢI PHÁP:\n";
    echo "   1. Chuyển sang Unsplash (MIỄN PHÍ):\n";
    echo "      - Chạy: php fix_image_provider.php\n\n";
    echo "   2. HOẶC cập nhật Clipdrop key:\n";
    echo "      - Chạy: php update_clipdrop_key.php\n";
    echo "      - Làm theo hướng dẫn trong script\n\n";
}

if ($imageProvider === 'unsplash' && !empty($unsplashKey)) {
    echo "✅ GOOD: Đang dùng Unsplash với key hợp lệ!\n";
    echo "   Không cần Clipdrop API key.\n\n";
}

echo "\n";
?>

