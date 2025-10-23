<?php
// Cấu hình AI API
// API Keys được load từ file .env (bảo mật)

// Load environment variables
require_once __DIR__ . '/includes/env_loader.php';

return [
    // === TEXT GENERATION (Tạo nội dung) ===
    // Chọn AI provider: 'groq' (miễn phí, nhanh nhất), 'gemini', hoặc 'openai'
    'ai_provider' => env('AI_PROVIDER', 'groq'),
    
    // OpenAI API Configuration
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => 2000,
        'temperature' => 0.7
    ],
    
    // Google Gemini API Configuration
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-pro'),
        'temperature' => 0.7
    ],
    
    // === IMAGE GENERATION (Tạo hình ảnh) ===
    'image_provider' => env('IMAGE_PROVIDER', 'clipdrop'),
    
    // Unsplash - MIỄN PHÍ (Stock Photos)
    'unsplash' => [
        'access_key' => env('UNSPLASH_ACCESS_KEY', ''),
    ],
    
    // DALL-E (OpenAI)
    'dalle' => [
        'api_key' => env('DALLE_API_KEY', ''),
        'size' => '1792x1024',
    ],
    
    // Stability AI
    'stability' => [
        'api_key' => env('STABILITY_API_KEY', ''),
    ],
    
    // Replicate (SDXL)
    'replicate' => [
        'api_key' => env('REPLICATE_API_KEY', ''),
    ],
    
    // Clipdrop AI
    'clipdrop' => [
        'api_key' => env('CLIPDROP_API_KEY', ''),
    ],
    
    // Groq API - MIỄN PHÍ
    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'temperature' => 0.7
    ],
    
    // Blog settings
    'blog' => [
        'default_length' => 'very-short', // very-short, short, medium, long
        'language' => 'vi', // Vietnamese
        'tone' => 'professional', // professional, casual, friendly
        'auto_generate_image' => true, // Tự động tạo ảnh khi tạo bài viết
        'required_hashtags' => ['#transformfitness', '#coaching', '#gymkid'], // Hashtags bắt buộc
        'total_hashtags' => 5 // Tổng số hashtags
    ],
    
    // === TEXT OVERLAY (Chèn text lên ảnh) ===
    'text_overlay' => [
        'font_size' => 60, // Cỡ chữ (px)
        'font_color' => [255, 255, 255], // Màu chữ RGB (trắng)
        'bg_overlay' => true, // Thêm overlay đen mờ phía sau text
        'bg_opacity' => 0.6, // Độ mờ của overlay (0-1)
        'position' => 'center', // Vị trí text: 'top', 'center', 'bottom'
        'add_branding' => true, // Tự động thêm logo TRANSFORM
        
        // === BRANDING LOGO (Logo TRANSFORM) ===
        'branding_logo' => [
            'size' => 150, // Kích thước logo (px)
            'position' => 'top-center', // Vị trí: top-left, top-center, top-right, bottom-left, bottom-center, bottom-right, center
            'margin' => 30, // Khoảng cách từ viền (px)
            'opacity' => 1.0 // Độ trong suốt (0-1)
        ]
    ]
];
?>