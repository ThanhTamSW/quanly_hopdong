<?php
// Cấu hình AI API
// Copy file này thành config.ai.php và điền API key của bạn

return [
    // === TEXT GENERATION (Tạo nội dung) ===
    // Chọn AI provider: 'gemini' (miễn phí) hoặc 'openai' (có phí)
    'ai_provider' => 'gemini', // Gemini miễn phí
    
    // OpenAI API Configuration
    'openai' => [
        'api_key' => 'sk-your-openai-api-key-here',
        'model' => 'gpt-3.5-turbo', // hoặc 'gpt-4'
        'max_tokens' => 2000,
        'temperature' => 0.7
    ],
    
    // Google Gemini API Configuration (MIỄN PHÍ - KHUYẾN NGHỊ)
    'gemini' => [
        'api_key' => 'AIzaSy...your-gemini-api-key-here',
        'model' => 'gemini-pro',
        'temperature' => 0.7
    ],
    
    // === IMAGE GENERATION (Tạo hình ảnh) ===
    // Chọn: 'dalle', 'stability', 'replicate', hoặc 'none' (không tạo ảnh)
    'image_provider' => 'none', // Đổi thành 'dalle' hoặc 'replicate' để bật
    
    // DALL-E (OpenAI) - Chất lượng cao, ~$0.04/ảnh
    'dalle' => [
        'api_key' => 'sk-your-openai-api-key-here', // Cùng key với OpenAI
        'size' => '1792x1024', // 1024x1024, 1792x1024, 1024x1792
    ],
    
    // Stability AI - Chất lượng tốt, ~$0.002/ảnh
    'stability' => [
        'api_key' => 'sk-your-stability-api-key-here', // https://platform.stability.ai/
    ],
    
    // Replicate (SDXL) - Rẻ nhất, ~$0.0025/ảnh
    'replicate' => [
        'api_key' => 'r8_your-replicate-api-key-here', // https://replicate.com/account/api-tokens
    ],
    
    // Blog settings
    'blog' => [
        'default_length' => 'medium', // short, medium, long
        'language' => 'vi', // Vietnamese
        'tone' => 'professional', // professional, casual, friendly
        'auto_generate_image' => true // Tự động tạo ảnh khi tạo bài viết
    ]
];
?>

