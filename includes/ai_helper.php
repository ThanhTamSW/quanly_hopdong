<?php
// Helper functions for AI content generation

function generateBlogPost($topic, $options = []) {
    // Load AI config
    $config_file = __DIR__ . '/../config.ai.php';
    if (!file_exists($config_file)) {
        return [
            'success' => false,
            'error' => 'Chưa cấu hình AI API. Vui lòng copy config.ai.example.php thành config.ai.php và điền API key.'
        ];
    }
    
    $config = require $config_file;
    $provider = $config['ai_provider'];
    
    // Build prompt
    $length = $options['length'] ?? $config['blog']['default_length'];
    $tone = $options['tone'] ?? $config['blog']['tone'];
    
    $length_guide = [
        'short' => '500-700 từ',
        'medium' => '1000-1500 từ',
        'long' => '2000-3000 từ'
    ];
    
    $prompt = "Viết một bài blog chuyên nghiệp về chủ đề: '$topic'

Yêu cầu:
- Độ dài: {$length_guide[$length]}
- Phong cách: $tone
- Ngôn ngữ: Tiếng Việt
- Cấu trúc rõ ràng với tiêu đề phụ
- Bao gồm: Giới thiệu, Nội dung chính, Kết luận
- Sử dụng markdown format cho heading (##, ###)
- Thêm ví dụ thực tế
- SEO friendly

Hãy viết bài viết hoàn chỉnh ngay bây giờ:";
    
    // Call AI API based on provider
    if ($provider === 'openai') {
        return callOpenAI($prompt, $config['openai']);
    } else if ($provider === 'gemini') {
        return callGemini($prompt, $config['gemini']);
    }
    
    return [
        'success' => false,
        'error' => 'Provider không hợp lệ'
    ];
}

function callGemini($prompt, $config) {
    $api_key = $config['api_key'];
    $model = $config['model'];
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => $config['temperature'] ?? 0.7,
            'maxOutputTokens' => 2048
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . curl_error($ch)
        ];
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => "API Error (HTTP $http_code): " . $response
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $content = $result['candidates'][0]['content']['parts'][0]['text'];
        return [
            'success' => true,
            'content' => $content,
            'prompt' => $prompt
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Không thể parse response từ Gemini'
    ];
}

function callOpenAI($prompt, $config) {
    $api_key = $config['api_key'];
    $model = $config['model'];
    
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Bạn là một chuyên gia viết blog về fitness, gym và sức khỏe.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => $config['max_tokens'] ?? 2000,
        'temperature' => $config['temperature'] ?? 0.7
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . curl_error($ch)
        ];
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => "API Error (HTTP $http_code): " . $response
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        $content = $result['choices'][0]['message']['content'];
        return [
            'success' => true,
            'content' => $content,
            'prompt' => $prompt
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Không thể parse response từ OpenAI'
    ];
}

function generateSlug($title) {
    // Vietnamese to ASCII conversion
    $vietnamese = [
        'á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ',
        'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ',
        'í', 'ì', 'ỉ', 'ĩ', 'ị',
        'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ',
        'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự',
        'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ',
        'đ'
    ];
    
    $ascii = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];
    
    $slug = mb_strtolower($title);
    $slug = str_replace($vietnamese, $ascii, $slug);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    return $slug;
}

function extractExcerpt($content, $length = 200) {
    // Remove markdown
    $text = preg_replace('/#+\s/', '', $content);
    $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
    $text = strip_tags($text);
    $text = trim($text);
    
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . '...';
}
?>

