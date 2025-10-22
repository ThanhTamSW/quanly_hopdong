<?php
// Cấu hình AI API
// Copy file này thành config.ai.php và điền API key của bạn

return [
    // Chọn AI provider: 'openai' hoặc 'gemini'
    'ai_provider' => 'gemini', // Gemini miễn phí
    
    // OpenAI API Configuration
    'openai' => [
        'api_key' => 'your-openai-api-key-here',
        'model' => 'gpt-3.5-turbo', // hoặc 'gpt-4'
        'max_tokens' => 2000,
        'temperature' => 0.7
    ],
    
    // Google Gemini API Configuration (Miễn phí)
    'gemini' => [
        'api_key' => 'your-gemini-api-key-here',
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

