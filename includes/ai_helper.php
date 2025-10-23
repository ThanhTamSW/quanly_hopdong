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
    $author_phone = $options['author_phone'] ?? '0973853417'; // Mặc định Transform
    $author_name = $options['author_name'] ?? 'Transform Fitness';
    
    $length_guide = [
        'very-short' => '200-300 từ',
        'short' => '500-700 từ',
        'medium' => '1000-1500 từ',
        'long' => '2000-3000 từ'
    ];
    
    // Get hashtag requirements
    $required_hashtags = $config['blog']['required_hashtags'] ?? [];
    $total_hashtags = $config['blog']['total_hashtags'] ?? 5;
    
    // Build hashtag instruction
    $hashtag_instruction = '';
    if (!empty($required_hashtags) || $total_hashtags > 0) {
        $hashtag_instruction = "\n- Cuối bài viết thêm CHÍNH XÁC $total_hashtags hashtags liên quan";
        if (!empty($required_hashtags)) {
            $required_list = implode(', ', $required_hashtags);
            $hashtag_instruction .= "\n- PHẢI bao gồm các hashtags: $required_list";
            $remaining = $total_hashtags - count($required_hashtags);
            if ($remaining > 0) {
                $hashtag_instruction .= "\n- Thêm $remaining hashtags khác liên quan đến fitness/gym";
            }
        }
    }
    
    $prompt = "Viết một bài blog SINH ĐỘNG, NĂNG ĐỘNG như đăng trên Facebook về chủ đề: '$topic'

YÊU CẦU FORMAT (QUAN TRỌNG):

1. TIÊU ĐỀ CHÍNH (dòng đầu):
   - VIẾT HOA TOÀN BỘ
   - Có 2 emoji phù hợp ở đầu/cuối
   - Ngắn gọn, hấp dẫn, có từ khóa quan trọng

2. GIỚI THIỆU (2-3 câu):
   - Câu mở đầu thân thiện
   - Giải thích tại sao chủ đề quan trọng
   - Có emoji ✨

3. NỘI DUNG CHÍNH:
   - Chia thành 3-5 phần rõ ràng
   - Mỗi phần có tiêu đề VIẾT HOA + số thứ tự + emoji
   - Dưới mỗi tiêu đề có 2-4 bullet points (dấu ✅)
   - Mỗi bullet có tiêu đề ngắn + emoji + giải thích chi tiết

4. KẾT THÚC:
   - 👉 HÀNH ĐỘNG NGAY HÔM NAY!
   - Câu kêu gọi hành động mạnh mẽ
   - Thông tin liên hệ:
     * 👉 Liên hệ ngay: $author_phone (Zalo/Điện thoại)
     * 📍 Địa chỉ: 337/26 Lê Văn Sỹ, Quận Tân Bình
   - Câu kêu gọi về trẻ em/gia đình

5. HASHTAGS (cuối cùng):$hashtag_instruction

PHONG CÁCH:
- Độ dài: {$length_guide[$length]}
- Ngôn ngữ: Tiếng Việt
- NHIỀU EMOJI: 💪 🏋️ 🔥 ⭐ 💯 👍 ✨ 🎯 💚 ⚡ 🌟 👟 🦵 🧠 🤸‍♀️ 🧘‍♂️ 🛡️ 🦶 ⚙️ 🔄
- Câu ngắn, dễ đọc
- Thân thiện, động viên
- Có thông tin thực tế

⚠️ CỰC KỲ QUAN TRỌNG - BẮT BUỘC PHẢI TUÂN THỦ:
- TUYỆT ĐỐI KHÔNG ĐƯỢC dùng dấu ** (hai dấu sao)
- TUYỆT ĐỐI KHÔNG ĐƯỢC dùng dấu * (một dấu sao)
- TUYỆT ĐỐI KHÔNG ĐƯỢC dùng __ (gạch dưới)
- TUYỆT ĐỐI KHÔNG ĐƯỢC dùng ## hoặc ### (markdown heading)
- CHỈ viết TEXT THUẦN + EMOJI
- Nếu muốn nhấn mạnh → dùng CHỮ IN HOA
- Nếu muốn đánh số → dùng 1. 2. 3. (không dùng *)

VÍ DỤ CHÍNH XÁC:

🏃‍♀️ CHẠY KHÔNG ĐAU: BÍ QUYẾT BẢO VỆ KHỚP GỐI VÀNG! 🛡️

Chạy bộ là một môn thể thao tuyệt vời, nhưng nếu không đúng cách, khớp gối của bạn có thể phải chịu áp lực nặng nề. Hãy biến chạy bộ thành niềm vui, không phải cơn đau! ✨

Đây là 3 nguyên tắc cốt lõi giúp bạn bảo vệ khớp gối một cách tối ưu nhất:

1. CHÚ TRỌNG VÀO KỸ THUẬT VÀ PHỤ KIỆN 👟

✅ Giảm Sải Chân (Cadence): ⚙️ Thay vì bước dài, hãy cố gắng giữ sải chân ngắn hơn và nhanh hơn (tăng tần suất bước). Điều này giúp giảm lực tác động lên đầu gối.

✅ Tiếp đất nhẹ nhàng: 🦶 Hạn chế tiếp đất bằng gót chân quá mạnh. Cố gắng tiếp đất bằng giữa bàn chân (midfoot) và giữ cho bàn chân gần trọng tâm cơ thể nhất có thể.

✅ Giày Chạy Phù Hợp: 👟 Đầu tư vào một đôi giày có đệm tốt, phù hợp với dáng bàn chân và kiểu chạy của bạn để tối ưu hóa khả năng hấp thụ sốc.

2. TĂNG CƯỜNG SỨC MẠNH CƠ BẮP 💪

✅ Tập Chân và Hông: 🦵 Các bài tập tăng cường cơ đùi trước (Quads), đùi sau (Hamstrings) và cơ hông (Glutes) như Squat, Lunge, Deadlift là vô cùng quan trọng.

✅ Cơ Lõi Vững Chắc: 🧠 Cơ bụng và lưng khỏe giúp duy trì tư thế chạy thẳng, ngăn ngừa tình trạng đầu gối bị xoay vào trong khi chạy.

👉 HÀNH ĐỘNG NGAY HÔM NAY!

Chạy bộ là niềm vui của cuộc sống. Hãy chạy thông minh và bảo vệ khớp gối của bạn!

Trang bị kỹ thuật đúng từ nhỏ giúp con bạn tránh được các chấn thương khớp trong tương lai!

👉 Liên hệ ngay: $author_phone (Zalo/Điện thoại)
📍 Địa chỉ: 337/26 Lê Văn Sỹ, Quận Tân Bình

#transformfitness #coaching #gymkid #fitness #workout

HÃY VIẾT BÀI THEO ĐÚNG FORMAT TRÊN:";
    
    // Call AI API based on provider
    if ($provider === 'openai') {
        return callOpenAI($prompt, $config['openai']);
    } else if ($provider === 'gemini') {
        return callGemini($prompt, $config['gemini']);
    } else if ($provider === 'groq') {
        return callGroq($prompt, $config['groq']);
    }
    
    return [
        'success' => false,
        'error' => 'Provider không hợp lệ'
    ];
}

function callGemini($prompt, $config) {
    $api_key = $config['api_key'];
    $model = $config['model'] ?? 'gemini-1.5-flash-latest'; // Model mới nhất
    
    // Gemini API v1beta cho tất cả models
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
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

function callGroq($prompt, $config) {
    $api_key = $config['api_key'];
    $model = $config['model'] ?? 'llama-3.3-70b-versatile'; // Model mới nhất
    
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Bạn là một chuyên gia viết blog về fitness, gym và sức khỏe.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 2000,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Bypass SSL verification for local development (WAMP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $error
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
        'error' => 'Không thể parse response từ Groq'
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
    
    // Bypass SSL verification for local development (WAMP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
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

/**
 * Generate featured image for blog post using AI
 * @param string $topic - Blog topic
 * @param array $options - Image generation options
 * @return array - Success status and image URL or error
 */
function generateFeaturedImage($topic, $options = []) {
    // Load AI config
    $config_file = __DIR__ . '/../config.ai.php';
    if (!file_exists($config_file)) {
        return [
            'success' => false,
            'error' => 'Chưa cấu hình AI API'
        ];
    }
    
    $config = require $config_file;
    $provider = $config['image_provider'] ?? 'none';
    
    if ($provider === 'none') {
        return [
            'success' => false,
            'error' => 'Chưa cấu hình image provider'
        ];
    }
    
    // Get hashtags from options
    $hashtags = $options['hashtags'] ?? [];
    
    // Create image prompt from topic
    $image_prompt = createImagePrompt($topic, $hashtags);
    
    if ($provider === 'dalle') {
        return generateWithDALLE($image_prompt, $config['dalle']);
    } else if ($provider === 'stability') {
        return generateWithStability($image_prompt, $config['stability']);
    } else if ($provider === 'replicate') {
        return generateWithReplicate($image_prompt, $config['replicate']);
    } else if ($provider === 'unsplash') {
        return generateWithUnsplash($topic, $config['unsplash']);
    } else if ($provider === 'clipdrop') {
        return generateWithClipdrop($image_prompt, $config['clipdrop']);
    }
    
    return [
        'success' => false,
        'error' => 'Provider không hợp lệ'
    ];
}

/**
 * Create optimized image prompt from blog topic
 */
function createImagePrompt($topic, $hashtags = []) {
    // Convert Vietnamese topic to English prompt for better AI understanding
    $prompt = "Happy kids/children exercising and having fun, fitness theme: $topic. ";
    $prompt .= "Cute, energetic, playful children doing sports, gym activities. ";
    $prompt .= "Bright colors, joyful atmosphere, motivational, healthy lifestyle. ";
    $prompt .= "High quality, vibrant, colorful, professional photography. ";
    $prompt .= "16:9 aspect ratio, photorealistic, no text overlay. ";
    
    // Add hashtags context to improve relevance
    if (!empty($hashtags)) {
        $hashtag_text = implode(' ', $hashtags);
        $prompt .= "Related to: " . $hashtag_text;
    }
    
    return $prompt;
}

/**
 * Generate image using OpenAI DALL-E
 */
function generateWithDALLE($prompt, $config) {
    $api_key = $config['api_key'];
    $size = $config['size'] ?? '1024x1024';
    
    $url = 'https://api.openai.com/v1/images/generations';
    
    $data = [
        'model' => 'dall-e-3',
        'prompt' => $prompt,
        'n' => 1,
        'size' => $size,
        'quality' => 'standard'
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
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => "DALL-E Error (HTTP $http_code): " . $response
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['data'][0]['url'])) {
        $image_url = $result['data'][0]['url'];
        
        // Download and save image
        $saved_path = downloadAndSaveImage($image_url, 'dalle');
        
        return [
            'success' => true,
            'image_url' => $saved_path,
            'prompt' => $prompt
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Không thể tạo ảnh với DALL-E'
    ];
}

/**
 * Generate image using Stability AI
 */
function generateWithStability($prompt, $config) {
    $api_key = $config['api_key'];
    
    $url = 'https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image';
    
    $data = [
        'text_prompts' => [
            ['text' => $prompt, 'weight' => 1]
        ],
        'cfg_scale' => 7,
        'height' => 1024,
        'width' => 1024,
        'samples' => 1,
        'steps' => 30
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => "Stability AI Error (HTTP $http_code): " . $response
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['artifacts'][0]['base64'])) {
        $base64_image = $result['artifacts'][0]['base64'];
        $saved_path = saveBase64Image($base64_image, 'stability');
        
        return [
            'success' => true,
            'image_url' => $saved_path,
            'prompt' => $prompt
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Không thể tạo ảnh với Stability AI'
    ];
}

/**
 * Generate image using Replicate (Free alternative)
 */
function generateWithReplicate($prompt, $config) {
    $api_key = $config['api_key'];
    
    // Use SDXL model on Replicate
    $url = 'https://api.replicate.com/v1/predictions';
    
    $data = [
        'version' => '39ed52f2a78e934b3ba6e2a89f5b1c712de7dfea535525255b1aa35c5565e08b',
        'input' => [
            'prompt' => $prompt,
            'width' => 1024,
            'height' => 1024
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Token ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 201) {
        return [
            'success' => false,
            'error' => "Replicate Error (HTTP $http_code): " . $response
        ];
    }
    
    $result = json_decode($response, true);
    $prediction_url = $result['urls']['get'];
    
    // Poll for result
    $max_attempts = 30;
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        sleep(2);
        
        $ch = curl_init($prediction_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . $api_key
        ]);
        
        $status_response = curl_exec($ch);
        curl_close($ch);
        
        $status_result = json_decode($status_response, true);
        
        if ($status_result['status'] === 'succeeded') {
            $image_url = $status_result['output'][0];
            $saved_path = downloadAndSaveImage($image_url, 'replicate');
            
            return [
                'success' => true,
                'image_url' => $saved_path,
                'prompt' => $prompt
            ];
        } else if ($status_result['status'] === 'failed') {
            return [
                'success' => false,
                'error' => 'Image generation failed'
            ];
        }
        
        $attempt++;
    }
    
    return [
        'success' => false,
        'error' => 'Timeout waiting for image'
    ];
}

/**
 * Generate image using Unsplash (FREE FOREVER)
 */
function generateWithUnsplash($topic, $config) {
    $access_key = $config['access_key'];
    
    // Tạo search query từ topic (tiếng Việt -> tiếng Anh)
    $search_keywords = extractKeywordsForUnsplash($topic);
    
    // Unsplash API search
    $url = 'https://api.unsplash.com/search/photos?query=' . urlencode($search_keywords) . '&per_page=1&orientation=landscape';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Client-ID ' . $access_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => "Unsplash Error (HTTP $http_code): " . $response
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['results'][0]['urls']['regular'])) {
        $image_url = $result['results'][0]['urls']['regular'];
        
        // Download and save image
        $saved_path = downloadAndSaveImage($image_url, 'unsplash');
        
        // Trigger download (Unsplash requirement)
        if (isset($result['results'][0]['links']['download_location'])) {
            $download_url = $result['results'][0]['links']['download_location'];
            $ch = curl_init($download_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Client-ID ' . $access_key
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            curl_close($ch);
        }
        
        return [
            'success' => true,
            'image_url' => $saved_path,
            'prompt' => $search_keywords
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Không tìm thấy ảnh phù hợp trên Unsplash'
    ];
}

/**
 * Generate image using Clipdrop AI
 */
function generateWithClipdrop($prompt, $config) {
    $api_key = $config['api_key'];
    
    $url = 'https://clipdrop-api.co/text-to-image/v1';
    
    // Prepare POST data
    $data = [
        'prompt' => $prompt
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    // Bypass SSL for WAMP
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $error
        ];
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => "Clipdrop Error (HTTP $http_code): " . substr($response, 0, 200)
        ];
    }
    
    // Clipdrop returns the image binary directly
    if (!empty($response)) {
        // Save the image
        $upload_dir = __DIR__ . '/../uploads/blog_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $filename = 'clipdrop_' . time() . '_' . uniqid() . '.png';
        $filepath = $upload_dir . $filename;
        
        if (file_put_contents($filepath, $response)) {
            return [
                'success' => true,
                'image_url' => 'uploads/blog_images/' . $filename,
                'prompt' => $prompt
            ];
        }
    }
    
    return [
        'success' => false,
        'error' => 'Không thể lưu ảnh từ Clipdrop'
    ];
}

/**
 * Extract keywords for Unsplash search (Vietnamese to English)
 */
function extractKeywordsForUnsplash($topic) {
    // Common Vietnamese to English translations for fitness topics
    $translations = [
        'tập' => 'exercise',
        'tập luyện' => 'training',
        'gym' => 'gym',
        'phòng tập' => 'gym',
        'cơ' => 'muscle',
        'tăng cơ' => 'bodybuilding',
        'giảm cân' => 'weight loss',
        'giảm mỡ' => 'fat loss',
        'cardio' => 'cardio',
        'yoga' => 'yoga',
        'chạy bộ' => 'running',
        'squat' => 'squat',
        'deadlift' => 'deadlift',
        'bench press' => 'bench press',
        'ngực' => 'chest',
        'lưng' => 'back',
        'vai' => 'shoulder',
        'tay' => 'arms',
        'chân' => 'legs',
        'bụng' => 'abs',
        'dinh dưỡng' => 'nutrition',
        'protein' => 'protein',
        'sức khỏe' => 'health',
        'thể hình' => 'bodybuilding',
        'huấn luyện viên' => 'personal trainer',
        'thiết bị' => 'equipment'
    ];
    
    $topic_lower = mb_strtolower($topic);
    $keywords = [];
    
    // Find matching keywords
    foreach ($translations as $vn => $en) {
        if (strpos($topic_lower, $vn) !== false) {
            $keywords[] = $en;
        }
    }
    
    // Default to "gym fitness" if no specific keywords found
    if (empty($keywords)) {
        $keywords = ['gym', 'fitness', 'workout'];
    }
    
    return implode(' ', $keywords);
}

/**
 * Download image from URL and save locally
 */
function downloadAndSaveImage($url, $provider) {
    // Create uploads directory if not exists
    $upload_dir = __DIR__ . '/../uploads/blog_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $filename = $provider . '_' . time() . '_' . uniqid() . '.jpg';
    $filepath = $upload_dir . $filename;
    
    // Download image
    $image_data = file_get_contents($url);
    file_put_contents($filepath, $image_data);
    
    // Return relative path for database
    return 'uploads/blog_images/' . $filename;
}

/**
 * Save base64 image to file
 */
function saveBase64Image($base64_data, $provider) {
    $upload_dir = __DIR__ . '/../uploads/blog_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = $provider . '_' . time() . '_' . uniqid() . '.png';
    $filepath = $upload_dir . $filename;
    
    $image_data = base64_decode($base64_data);
    file_put_contents($filepath, $image_data);
    
    return 'uploads/blog_images/' . $filename;
}

/**
 * Add text overlay to image (create logo/thumbnail)
 * @param string $image_path - Path to base image
 * @param string $text - Text to overlay
 * @param array $options - Styling options
 * @return string - Path to new image with text
 */
function addTextOverlay($image_path, $text, $options = []) {
    // Load config
    $config_file = __DIR__ . '/../config.ai.php';
    $config = file_exists($config_file) ? require $config_file : [];
    $overlay_config = $config['text_overlay'] ?? [];
    
    // Merge options with defaults
    $font_size = $options['font_size'] ?? $overlay_config['font_size'] ?? 60;
    $font_color = $options['font_color'] ?? $overlay_config['font_color'] ?? [255, 255, 255]; // White
    $bg_overlay = $options['bg_overlay'] ?? $overlay_config['bg_overlay'] ?? true;
    $bg_opacity = $options['bg_opacity'] ?? $overlay_config['bg_opacity'] ?? 0.6;
    $position = $options['position'] ?? $overlay_config['position'] ?? 'center'; // top, center, bottom
    $add_branding = $options['add_branding'] ?? $overlay_config['add_branding'] ?? true;
    
    $full_path = __DIR__ . '/../' . $image_path;
    
    if (!file_exists($full_path)) {
        return $image_path; // Return original if file not found
    }
    
    // Detect image type
    $image_info = getimagesize($full_path);
    $mime_type = $image_info['mime'];
    
    // Load image based on type
    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($full_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($full_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($full_path);
            break;
        default:
            return $image_path; // Unsupported format
    }
    
    if (!$image) {
        return $image_path;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // === ADD BRANDING LOGO (TRANSFORM) ===
    if ($add_branding) {
        // Tìm file logo (hỗ trợ PNG, JPG, JPEG)
        $branding_logo = null;
        $logo_paths = [
            __DIR__ . '/../assets/branding/logo.jpg',
            __DIR__ . '/../assets/branding/logo.jpeg',
            __DIR__ . '/../assets/branding/logo.png'
        ];
        
        foreach ($logo_paths as $path) {
            if (file_exists($path)) {
                $branding_logo = $path;
                break;
            }
        }
        
        if ($branding_logo) {
            // Load logo dựa vào extension
            $logo_ext = strtolower(pathinfo($branding_logo, PATHINFO_EXTENSION));
            
            if ($logo_ext === 'png') {
                $logo = imagecreatefrompng($branding_logo);
            } else if ($logo_ext === 'jpg' || $logo_ext === 'jpeg') {
                $logo = imagecreatefromjpeg($branding_logo);
            } else {
                $logo = false;
            }
            
            if ($logo) {
                // Enable alpha blending
                imagealphablending($image, true);
                imagesavealpha($logo, true);
                
                $logo_width = imagesx($logo);
                $logo_height = imagesy($logo);
                
                // Get logo config
                $logo_config = $overlay_config['branding_logo'] ?? [];
                $logo_size = $logo_config['size'] ?? 120; // Max width/height - giảm size để vừa hơn
                $logo_position = $logo_config['position'] ?? 'top-left'; // ĐỔI VỊ TRÍ LOGO LÊN TRÊN BÊN TRÁI
                $logo_margin = $logo_config['margin'] ?? 20; // Giảm margin
                $logo_opacity = $logo_config['opacity'] ?? 0.9; // Độ trong suốt nhẹ
                
                // Resize logo to fit
                $scale = min($logo_size / $logo_width, $logo_size / $logo_height);
                $new_logo_width = (int)($logo_width * $scale);
                $new_logo_height = (int)($logo_height * $scale);
                
                $resized_logo = imagescale($logo, $new_logo_width, $new_logo_height);
                
                // Apply opacity if needed
                if ($logo_opacity < 1.0) {
                    imagefilter($resized_logo, IMG_FILTER_COLORIZE, 0, 0, 0, (1 - $logo_opacity) * 127);
                }
                
                // Calculate position
                switch ($logo_position) {
                    case 'top-left':
                        $logo_x = $logo_margin;
                        $logo_y = $logo_margin;
                        break;
                    case 'top-center':
                        $logo_x = ($width - $new_logo_width) / 2;
                        $logo_y = $logo_margin;
                        break;
                    case 'top-right':
                        $logo_x = $width - $new_logo_width - $logo_margin;
                        $logo_y = $logo_margin;
                        break;
                    case 'bottom-left':
                        $logo_x = $logo_margin;
                        $logo_y = $height - $new_logo_height - $logo_margin;
                        break;
                    case 'bottom-center':
                        $logo_x = ($width - $new_logo_width) / 2;
                        $logo_y = $height - $new_logo_height - $logo_margin;
                        break;
                    case 'bottom-right':
                        $logo_x = $width - $new_logo_width - $logo_margin;
                        $logo_y = $height - $new_logo_height - $logo_margin;
                        break;
                    case 'center':
                    default:
                        $logo_x = ($width - $new_logo_width) / 2;
                        $logo_y = ($height - $new_logo_height) / 2;
                        break;
                }
                
                // Merge logo onto image
                imagecopy($image, $resized_logo, (int)$logo_x, (int)$logo_y, 0, 0, $new_logo_width, $new_logo_height);
                
                imagedestroy($logo);
                imagedestroy($resized_logo);
            }
        }
    }
    
    // ĐÃ XÓA PHẦN TEXT OVERLAY Ở GIỮA HÌNH
    // Bây giờ chỉ có logo ở góc trên bên trái
    
    // Save new image
    $upload_dir = __DIR__ . '/../uploads/blog_images/';
    $filename = 'logo_' . time() . '_' . uniqid() . '.png';
    $new_path = $upload_dir . $filename;
    
    imagepng($image, $new_path, 9); // Max quality
    imagedestroy($image);
    
    return 'uploads/blog_images/' . $filename;
}

/**
 * Word wrap text to fit within width
 */
function wordwrapText($text, $max_width, $font_size, $font_file = null) {
    $words = explode(' ', $text);
    $lines = [];
    $current_line = '';
    
    foreach ($words as $word) {
        $test_line = empty($current_line) ? $word : $current_line . ' ' . $word;
        
        // Calculate width
        if ($font_file && file_exists($font_file)) {
            $bbox = imagettfbbox($font_size, 0, $font_file, $test_line);
            $width = abs($bbox[4] - $bbox[0]);
        } else {
            $width = imagefontwidth(5) * strlen($test_line);
        }
        
        if ($width > $max_width && !empty($current_line)) {
            $lines[] = $current_line;
            $current_line = $word;
        } else {
            $current_line = $test_line;
        }
    }
    
    if (!empty($current_line)) {
        $lines[] = $current_line;
    }
    
    return implode("\n", $lines);
}
?>

