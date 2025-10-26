<?php
session_start();
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập!']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? '';

if (empty($text)) {
    echo json_encode(['success' => false, 'error' => 'Không có văn bản để phân tích!']);
    exit;
}

// Include AI helper
require_once __DIR__ . '/../includes/ai_helper.php';
require_once __DIR__ . '/../includes/db.php';

// Lấy danh sách coaches để AI biết
$coaches_result = $conn->query("SELECT name FROM coaches ORDER BY name");
$coaches_names = [];
while($coach = $coaches_result->fetch_assoc()) {
    $coaches_names[] = $coach['name'];
}

// Tạo prompt cho AI
$prompt = "Bạn là một trợ lý AI chuyên trích xuất thông tin hợp đồng từ văn bản tiếng Việt.

DANH SÁCH HLV CÓ SẴN: " . implode(', ', $coaches_names) . "

NHIỆM VỤ: Trích xuất thông tin từ văn bản sau và trả về JSON format chính xác.

VĂN BẢN:
\"\"\"
$text
\"\"\"

YÊU CẦU:
1. Trích xuất CHÍNH XÁC các thông tin:
   - Tên học viên (client_name)
   - Số điện thoại (client_phone): format 10 số, bắt đầu bằng 0
   - Ngày bắt đầu (start_date): format YYYY-MM-DD
   - Tổng số buổi (total_sessions): số nguyên
   - Giá gốc (total_price): số tiền (VNĐ), không có dấu phẩy
   - Giảm giá (discount_percentage): phần trăm (0-100)
   - Tên HLV (coach_name): phải khớp với danh sách HLV có sẵn
   - Lịch tập (schedule): mảng các object {day, time}

2. XỬ LÝ THÔNG MINH:
   - Số tiền: \"3 triệu\" = 3000000, \"5.5 triệu\" = 5500000, \"18 triệu\" = 18000000
   - Ngày tháng: \"1/11/2025\" = \"2025-11-01\", \"05/11\" = \"2025-11-05\" (năm hiện tại)
   - Thứ: \"T2\" = Monday, \"T3\" = Tuesday, \"T4\" = Wednesday, \"T5\" = Thursday, \"T6\" = Friday, \"T7\" = Saturday, \"CN\" = Sunday
   - Thứ: \"Thứ 2\" = Monday, \"Thứ 3\" = Tuesday, etc.
   - Giờ: \"7h sáng\" = \"07:00\", \"6h chiều\" = \"18:00\", \"18:00\" = \"18:00\"
   - HLV: Tìm tên gần đúng nhất trong danh sách (ví dụ: \"HLV Tuấn\" hoặc \"Tuấn\" → \"Tuấn\")

3. GIÁ TRỊ MẶC ĐỊNH nếu không tìm thấy:
   - discount_percentage: 0
   - Nếu không có giờ cụ thể: \"07:00\"

4. VALIDATION:
   - Số điện thoại phải 10 số
   - Ngày phải hợp lệ
   - Giá phải > 0
   - Tên HLV phải có trong danh sách

TRẢ VỀ JSON (KHÔNG CÓ TEXT KHÁC, CHỈ JSON):
{
  \"client_name\": \"Nguyễn Văn A\",
  \"client_phone\": \"0912345678\",
  \"start_date\": \"2025-11-01\",
  \"total_sessions\": 12,
  \"total_price\": 3000000,
  \"discount_percentage\": 10,
  \"final_price\": 2700000,
  \"coach_name\": \"Tuấn\",
  \"schedule\": [
    {\"day\": \"Monday\", \"time\": \"07:00\"},
    {\"day\": \"Wednesday\", \"time\": \"07:00\"},
    {\"day\": \"Friday\", \"time\": \"07:00\"}
  ]
}

LƯU Ý: 
- CHỈ trả về JSON, KHÔNG thêm markdown, KHÔNG thêm giải thích
- final_price = total_price * (1 - discount_percentage/100)
- Tên HLV phải CHÍNH XÁC khớp với danh sách";

try {
    // Call AI
    $result = callAI($prompt, [
        'temperature' => 0.1, // Low temperature for accuracy
        'max_tokens' => 1000
    ]);
    
    if (!$result['success']) {
        echo json_encode(['success' => false, 'error' => 'AI Error: ' . $result['error']]);
        exit;
    }
    
    $aiResponse = trim($result['content']);
    
    // Remove markdown code blocks if present
    $aiResponse = preg_replace('/^```json\s*/', '', $aiResponse);
    $aiResponse = preg_replace('/\s*```$/', '', $aiResponse);
    $aiResponse = trim($aiResponse);
    
    // Parse JSON
    $data = json_decode($aiResponse, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false, 
            'error' => 'Không thể parse JSON từ AI: ' . json_last_error_msg(),
            'raw_response' => $aiResponse
        ]);
        exit;
    }
    
    // Validate required fields
    $required = ['client_name', 'client_phone', 'start_date', 'total_sessions', 'total_price', 'coach_name', 'schedule'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => "Thiếu thông tin: $field"]);
            exit;
        }
    }
    
    // Validate phone number
    if (!preg_match('/^0\d{9}$/', $data['client_phone'])) {
        echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ (phải 10 số, bắt đầu bằng 0)']);
        exit;
    }
    
    // Validate date
    $date = DateTime::createFromFormat('Y-m-d', $data['start_date']);
    if (!$date || $date->format('Y-m-d') !== $data['start_date']) {
        echo json_encode(['success' => false, 'error' => 'Ngày không hợp lệ (format: YYYY-MM-DD)']);
        exit;
    }
    
    // Validate coach name exists
    $coach_check = $conn->prepare("SELECT id FROM coaches WHERE name = ?");
    $coach_check->bind_param("s", $data['coach_name']);
    $coach_check->execute();
    $coach_result = $coach_check->get_result();
    
    if ($coach_result->num_rows === 0) {
        // Try fuzzy match
        $coach_check2 = $conn->prepare("SELECT id, name FROM coaches WHERE name LIKE ?");
        $fuzzy_name = "%" . $data['coach_name'] . "%";
        $coach_check2->bind_param("s", $fuzzy_name);
        $coach_check2->execute();
        $coach_result2 = $coach_check2->get_result();
        
        if ($coach_result2->num_rows > 0) {
            $coach_data = $coach_result2->fetch_assoc();
            $data['coach_name'] = $coach_data['name'];
            $data['coach_id'] = $coach_data['id'];
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy HLV: ' . $data['coach_name']]);
            exit;
        }
    } else {
        $coach_data = $coach_result->fetch_assoc();
        $data['coach_id'] = $coach_data['id'];
    }
    
    // Calculate final_price if not set
    if (!isset($data['final_price'])) {
        $discount = $data['discount_percentage'] ?? 0;
        $data['final_price'] = $data['total_price'] * (1 - $discount / 100);
    }
    
    // Set default discount if not set
    if (!isset($data['discount_percentage'])) {
        $data['discount_percentage'] = 0;
    }
    
    // Validate schedule
    if (!is_array($data['schedule']) || count($data['schedule']) === 0) {
        echo json_encode(['success' => false, 'error' => 'Lịch tập không hợp lệ']);
        exit;
    }
    
    // Success
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage()
    ]);
}
?>

