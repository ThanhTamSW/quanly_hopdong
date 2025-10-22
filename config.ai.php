<?php
// Cấu hình AI API - ĐIỀN API KEY CỦA BẠN

return [
    // Chọn AI provider: 'gemini' (miễn phí) hoặc 'openai' (có phí)
    'ai_provider' => 'gemini',
    
    // OpenAI API Configuration
    'openai' => [
        'api_key' => 'sk-your-openai-api-key-here', // Lấy tại: https://platform.openai.com/api-keys
        'model' => 'gpt-3.5-turbo', // hoặc 'gpt-4'
        'max_tokens' => 2000,
        'temperature' => 0.7
    ],
    
    // Google Gemini API Configuration (MIỄN PHÍ - KHUYẾN NGHỊ)
    'gemini' => [
        'api_key' => 'AIzaSyDxnF6example-replace-with-your-key', // Lấy tại: https://makersuite.google.com/app/apikey
        'model' => 'gemini-pro',
        'temperature' => 0.7
    ],
    
    // Blog settings
    'blog' => [
        'default_length' => 'medium', // short, medium, long
        'language' => 'vi', // Vietnamese
        'tone' => 'professional' // professional, casual, friendly
    ]
];
?>

