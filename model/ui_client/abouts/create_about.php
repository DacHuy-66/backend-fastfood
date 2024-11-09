<?php
include_once __DIR__ . '/../../../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

$data = json_decode(file_get_contents("php://input"));

// Thêm mới head_review
if (isset($data->head_review)) {
    $head = $data->head_review;
    $query = "INSERT INTO head_review (name, description, color) VALUES (?, ?, ?)";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", 
        $head->name,
        $head->description,
        $head->color
    );
    $stmt->execute();
}

// Thêm mới body_review
if (isset($data->body_review)) {
    $body = $data->body_review;
    $query = "INSERT INTO body_review (name, description, icon) VALUES (?, ?, ?)";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss",
        $body->name,
        $body->description,
        $body->icon
    );
    $stmt->execute();
}

try {
    $response = [
        'message' => 'Thêm mới thành công',
        'ok' => true
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Đã xảy ra lỗi: ' . $e->getMessage(),
        'ok' => false
    ]);
}
?>
