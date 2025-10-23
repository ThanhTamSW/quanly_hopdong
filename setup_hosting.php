<?php
/**
 * Script tu dong setup API keys tren hosting
 * Chay file nay NGAY SAU KHI deploy len hosting
 */

$envFile = __DIR__ . '/.env';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Hosting - Transform Fitness</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 { color: #667eea; margin-top: 0; }
        h2 { color: #764ba2; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #764ba2;
        }
        ul { line-height: 1.8; }
        .step {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🚀 SETUP HOSTING - TRANSFORM FITNESS</h1>";

// Kiem tra .env
if (file_exists($envFile)) {
    echo "<p class='success'>✅ File .env đã tồn tại!</p>";
    
    // Doc va hien thi cau hinh hien tai
    require_once 'includes/env_loader.php';
    
    echo "<h2>📋 Cấu hình hiện tại:</h2>";
    echo "<div class='code'>";
    echo "AI Provider: " . env('AI_PROVIDER', 'not set') . "<br>";
    echo "Image Provider: " . env('IMAGE_PROVIDER', 'not set') . "<br><br>";
    
    $groqKey = env('GROQ_API_KEY', '');
    $clipdropKey = env('CLIPDROP_API_KEY', '');
    $unsplashKey = env('UNSPLASH_ACCESS_KEY', '');
    
    echo "Groq API Key: " . (!empty($groqKey) && $groqKey !== 'YOUR_GROQ_API_KEY_HERE' ? '✅ Đã set' : '❌ Chưa set') . "<br>";
    echo "Clipdrop API Key: " . (!empty($clipdropKey) && $clipdropKey !== 'YOUR_CLIPDROP_API_KEY_HERE' ? '✅ Đã set' : '❌ Chưa set') . "<br>";
    echo "Unsplash API Key: " . (!empty($unsplashKey) && $unsplashKey !== 'YOUR_UNSPLASH_ACCESS_KEY_HERE' ? '✅ Đã set' : '❌ Chưa set') . "<br>";
    echo "</div>";
    
    // Kiem tra xem co key hop le khong
    if (empty($groqKey) || $groqKey === 'YOUR_GROQ_API_KEY_HERE') {
        echo "<p class='error'>❌ Groq API Key chưa được cấu hình!</p>";
        echo "<p class='warning'>⚠️ Bạn cần cập nhật API keys thủ công.</p>";
    }
    
} else {
    echo "<p class='error'>❌ File .env CHƯA tồn tại!</p>";
    echo "<p>Đang tạo file .env mới...</p>";
    
    // Tao file .env moi
    // LUU Y: Thay the cac API keys placeholder bang keys that cua ban
    $envContent = "# AI API Keys
GROQ_API_KEY=your_groq_api_key_here
GEMINI_API_KEY=
OPENAI_API_KEY=
CLIPDROP_API_KEY=your_clipdrop_api_key_here
UNSPLASH_ACCESS_KEY=your_unsplash_access_key_here

# AI Configuration
AI_PROVIDER=groq
IMAGE_PROVIDER=clipdrop

# Blog Settings
BLOG_DEFAULT_LENGTH=very-short
BLOG_TOTAL_HASHTAGS=5
";
    
    if (file_put_contents($envFile, $envContent)) {
        echo "<p class='success'>✅ Đã tạo file .env thành công!</p>";
        echo "<p class='warning'>⚠️ LƯU Ý: File .env đã được tạo với API keys PLACEHOLDER!</p>";
        echo "<p>Bạn CẦN cập nhật API keys thật vào file .env:</p>";
        echo "<ul>";
        echo "<li>Mở file .env bằng cPanel File Manager hoặc FTP</li>";
        echo "<li>Thay thế <code>your_xxx_api_key_here</code> bằng API keys thật</li>";
        echo "<li>Lưu file và refresh lại trang này để kiểm tra</li>";
        echo "</ul>";
        echo "<p><strong>Lấy API keys tại:</strong></p>";
        echo "<ul>";
        echo "<li>Groq: <a href='https://console.groq.com/keys' target='_blank'>https://console.groq.com/keys</a></li>";
        echo "<li>Clipdrop: <a href='https://clipdrop.co/apis' target='_blank'>https://clipdrop.co/apis</a></li>";
        echo "<li>Unsplash: <a href='https://unsplash.com/oauth/applications' target='_blank'>https://unsplash.com/oauth/applications</a></li>";
        echo "</ul>";
        echo "<div class='code'>" . nl2br(htmlspecialchars($envContent)) . "</div>";
    } else {
        echo "<p class='error'>❌ Không thể tạo file .env!</p>";
        echo "<p class='warning'>Nguyên nhân: Không có quyền ghi file trên hosting.</p>";
        
        echo "<h2>🔧 Giải pháp thủ công:</h2>";
        echo "<div class='step'>";
        echo "<strong>Bước 1:</strong> Tạo file mới tên <code>.env</code> trong thư mục gốc của website<br>";
        echo "<strong>Bước 2:</strong> Copy nội dung sau vào file .env:<br>";
        echo "<div class='code'>" . nl2br(htmlspecialchars($envContent)) . "</div>";
        echo "<strong>Bước 3:</strong> Lưu file và refresh lại trang này";
        echo "</div>";
    }
}

echo "<h2>🔒 BẢO MẬT:</h2>";
echo "<div class='step'>";
echo "<p class='warning'>⚠️ QUAN TRỌNG: Sau khi setup xong, hãy XÓA file setup_hosting.php này để bảo mật!</p>";
echo "<p>File này chứa thông tin nhạy cảm và không nên để công khai trên hosting.</p>";
echo "</div>";

echo "<h2>✅ KIỂM TRA:</h2>";
echo "<ul>";
echo "<li>Truy cập <a href='blog_admin.php'>blog_admin.php</a> để kiểm tra tạo bài viết AI</li>";
echo "<li>Chạy <a href='check_api_keys.php'>check_api_keys.php</a> để kiểm tra API keys</li>";
echo "</ul>";

echo "<h2>📝 GHI CHÚ:</h2>";
echo "<div class='code'>";
echo "- File .env KHÔNG được commit lên Git (đã có trong .gitignore)<br>";
echo "- Mỗi lần deploy mới, bạn cần tạo lại file .env trên hosting<br>";
echo "- HOẶC backup file .env từ hosting và restore sau mỗi lần deploy<br>";
echo "</div>";

echo "<a href='index.php' class='btn'>🏠 Về trang chủ</a>";
echo "<a href='blog_admin.php' class='btn'>📝 Tạo bài viết AI</a>";

echo "</div></body></html>";
?>

