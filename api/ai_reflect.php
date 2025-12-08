<?php
// File: api/ai_reflect.php
header('Content-Type: application/json');

// 1. API KEY CỦA BẠN (Mình giữ nguyên key bạn gửi)
$apiKey = 'abc'; 

// 2. Nhận dữ liệu từ Client
$input = json_decode(file_get_contents('php://input'), true);
$journalContent = $input['content'] ?? '';

if (empty($journalContent)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập nội dung nhật ký!']);
    exit;
}

// --- HÀM GỬI CURL (Dùng chung) ---
function sendCurl($url, $method = 'GET', $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix lỗi SSL XAMPP
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) return ['error' => $error];
    return json_decode($result, true);
}

try {
    // 3. [BƯỚC THÔNG MINH] Tự động hỏi Google xem model nào đang hoạt động
    // Gọi API lấy danh sách model
    $listModelsUrl = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
    $modelsData = sendCurl($listModelsUrl, 'GET');

    $selectedModel = ""; 
    
    // Duyệt qua danh sách để tìm model phù hợp
    if (isset($modelsData['models'])) {
        foreach ($modelsData['models'] as $m) {
            // Chỉ lấy model có tên chứa 'gemini' và hỗ trợ generateContent
            if (strpos($m['name'], 'gemini') !== false && 
                isset($m['supportedGenerationMethods']) &&
                in_array("generateContent", $m['supportedGenerationMethods'])) {
                
                // Ưu tiên chọn 1.5-flash hoặc pro nếu thấy
                $selectedModel = $m['name']; 
                
                // Nếu tìm thấy bản 1.5 Flash (nhanh nhất), chọn luôn và dừng tìm kiếm
                if (strpos($m['name'], 'flash') !== false) break;
            }
        }
    }

    // Nếu không tìm thấy cái nào trong list (hoặc list lỗi), dùng fallback cứng
    if (empty($selectedModel)) {
        $selectedModel = "models/gemini-1.5-flash"; // Hy vọng cái này chạy được
    }

    // 4. TẠO NỘI DUNG (Dùng model vừa tìm được: $selectedModel)
    $promptText = "
Vai trò: Bạn là một người bạn tri kỷ (Soulmate) cực kỳ thấu hiểu, ấm áp và tinh tế. Bạn không phải là máy móc, không giáo điều.
Nhiệm vụ: Hãy đọc dòng tâm sự này: \"$journalContent\"

Hãy phản hồi lại thật gần gũi, đời thường, như đang ngồi cạnh vỗ về, thủ thỉ (dùng từ ngữ giản dị, không sáo rỗng).

Trả về kết quả đúng định dạng JSON (không markdown) gồm 3 mục:
1. 'analysis': Một câu 'đọc vị' tâm trạng ngắn gọn, thấu cảm (dưới 25 từ). 
   (Ví dụ style: 'Thương cậu quá, hôm nay chắc là một ngày dài lắm phải không?' hoặc 'Có vẻ cậu đang rối bời, nhưng tớ thấy được sự mạnh mẽ trong đó.')

2. 'advice': Lời nhắn nhủ hoặc gợi ý hành động nhỏ xíu, nhẹ nhàng (dưới 50 từ).
   (Ví dụ style: 'Tối nay hãy khoan nghĩ ngợi, pha một tách trà ấm, nghe bài nhạc lofi và ngủ một giấc thật ngon nhé. Mọi chuyện để mai tính.')

3. 'quote': Một câu trích dẫn/danh ngôn chữa lành phù hợp với hoàn cảnh.

Ngôn ngữ: Tiếng Việt. 
Giọng văn: Nhẹ nhàng, sâu lắng, thân mật (xưng hô Cậu - Tớ hoặc giọng văn không chủ ngữ nhưng đầy quan tâm).
    ";

    // Ghép tên model động vào URL
    // Lưu ý: $selectedModel đã chứa chuỗi 'models/...' nên không cần thêm prefix
    if (strpos($selectedModel, 'models/') === false) {
        $selectedModel = 'models/' . $selectedModel;
    }
    
    $generateUrl = "https://generativelanguage.googleapis.com/v1beta/$selectedModel:generateContent?key=" . $apiKey;
    
    $postData = [
        "contents" => [
            [ "parts" => [ ["text" => $promptText] ] ]
        ]
    ];

    $response = sendCurl($generateUrl, 'POST', $postData);

    // 5. XỬ LÝ KẾT QUẢ TRẢ VỀ
    if (isset($response['error'])) {
        // In lỗi chi tiết kèm tên model đang dùng để debug
        throw new Exception($response['error']['message'] . " (Model used: $selectedModel)");
    }

    $rawText = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    // Làm sạch JSON
    $cleanJson = str_replace(['```json', '```'], '', $rawText);
    $aiData = json_decode($cleanJson, true);

    if (!$aiData) {
        $aiData = [
            'analysis' => 'Vũ trụ đang lắng nghe...',
            'advice' => $rawText ? $rawText : "Vũ trụ đang bận rộn, hãy thử lại sau nhé.",
            'quote' => 'Love yourself.'
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $aiData]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi AI: ' . $e->getMessage()]);
}
?>