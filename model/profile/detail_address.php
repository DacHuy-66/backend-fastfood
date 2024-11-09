<?php

include_once __DIR__ . '/../../config/db.php';

// Kiểm tra xem 'id' có được cung cấp trong yêu cầu GET không
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

// Nếu không có 'id' được cung cấp, trả về phản hồi lỗi
if (!$user_id) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID user là bắt buộc.'
    ]);
    http_response_code(400);
    exit;
}

// Truy vấn để lấy dữ liệu cho user_id đã chỉ định
$sql = "SELECT * FROM detail_address WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$address_result = $stmt->get_result();

// Tạo một mảng để lưu trữ dữ liệu địa chỉ đã lấy
$addresses = array();

// Lặp qua kết quả và thêm mỗi hàng vào mảng addresses
while ($row = $address_result->fetch_assoc()) {
    $addresses[] = $row;
}

// Trả về dữ liệu địa chỉ dưới dạng phản hồi JSON
echo json_encode([
    'ok' => true,
    'success' => true,
    'addresses' => $addresses
]);

// Đóng kết nối
$conn->close();
?>
