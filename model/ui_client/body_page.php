<?php
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

function getOrderProcessSteps($conn) {
    $result = $conn->query("SELECT * FROM order_process ORDER BY order_number");
    $steps = [];
    while($row = $result->fetch_assoc()) {
        $steps[] = $row;
    }
    return $steps;
}


// Kết hợp tất cả dữ liệu quá trình đặt hàng
try {
    $response = [
        'steps' => getOrderProcessSteps($conn)
    ];
    
    // thêm tham chiếu bước tiếp theo
    foreach ($response['steps'] as $index => &$step) {
        if (isset($response['steps'][$index + 1])) {
            $step['next_step'] = $response['steps'][$index + 1]['step_number'];
        } else {
            $step['next_step'] = null;
        }
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}