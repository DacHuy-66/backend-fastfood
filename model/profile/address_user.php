<?php

header("Access-Control-Allow-Origin: *");  
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include_once __DIR__ . '/../../config/db.php';

// Truy vấn để lấy tất cả dữ liệu từ bảng detail_address
$sql = "SELECT * FROM detail_address";
$stmt = $conn->prepare($sql);
$stmt->execute();
$address_result = $stmt->get_result();

// Tạo mảng để lưu trữ dữ liệu địa chỉ được truy xuất
$addresses = array();

// Lặp qua kết quả và thêm mỗi hàng vào mảng addresses
while ($row = $address_result->fetch_assoc()) {
    $addresses[] = $row;
}

// Trả về dữ liệu địa chỉ dưới dạng JSON    
echo json_encode([
    'ok' => true,
    'success' => true,
    'addresses' => $addresses
]);

// Đóng kết nối
$conn->close();
?>
