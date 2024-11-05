<?php
// Include database connection
include_once __DIR__ . '/../../config/db.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Retrieve API key from headers
$headers = apache_request_headers();

$url_path = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim($request_uri, '/'));
$admin_id = end($path_parts);
// Check if admin ID is provided
if (empty($id_admin)) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID admin không được cung cấp!'
    ]);
    http_response_code(400);
    exit;
}

// Validate admin ID
$stmt = $conn->prepare("SELECT id FROM admin WHERE id = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
    $admin_id = $admin_data['id'];

    // Admin is authenticated, proceed with updating data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Get the new data from the parsed input
    $updates = [];
    $types = "";
    $values = [];

    // Prepare dynamic update fields
    if (isset($data['username'])) {
        $updates[] = "username = ?";
        $types .= "s";
        $values[] = $data['username'];
    }
    
    if (isset($data['email'])) {
        $updates[] = "email = ?";
        $types .= "s";
        $values[] = $data['email'];
    }
    
    if (isset($data['password'])) {
        $updates[] = "password = ?";
        $types .= "s";
        $values[] = $data['password'];
    }
    
    if (isset($data['order'])) {
        $updates[] = "`order` = ?";
        $types .= "i";
        $values[] = (int)$data['order'];
    }
    
    if (isset($data['mess'])) {
        $updates[] = "mess = ?";
        $types .= "i";
        $values[] = (int)$data['mess'];
    }
    
    if (isset($data['statistics'])) {
        $updates[] = "statistics = ?";
        $types .= "i";
        $values[] = (int)$data['statistics'];
    }
    
    if (isset($data['user'])) {
        $updates[] = "user = ?";
        $types .= "i";
        $values[] = (int)$data['user'];
    }
    
    if (isset($data['note'])) {
        $updates[] = "note = ?";
        $types .= "s";
        $values[] = $data['note'];
    }
    if (isset($data['product'])) {
        $updates[] = "product = ?";
        $types .= "i";
        $values[] = (int)$data['product'];
    }
    if (isset($data['discount'])) {
        $updates[] = "discount = ?";
        $types .= "i";
        $values[] = (int)$data['discount'];
    }
    if (isset($data['layout'])) {
        $updates[] = "layout = ?";
        $types .= "i";
        $values[] = (int)$data['layout'];
    }
    if (isset($data['decentralization'])) {
        $updates[] = "decentralization = ?";
        $types .= "i";
        $values[] = (int)$data['decentralization'];
    }
    if (empty($updates)) {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Không có trường nào để cập nhật được cung cấp!'
        ]);
        http_response_code(400);
        exit;
    }

    // Add time update
    $updates[] = "time = NOW()";
    
    // Create the SQL query
    $sql = "UPDATE admin SET " . implode(", ", $updates) . " WHERE id = ?";
    
    // Add admin ID to values and types
    $values[] = $admin_id;
    $types .= "s";
    
    // Prepare and execute the update
    $update_stmt = $conn->prepare($sql);
    
    // Dynamically bind parameters
    $update_stmt->bind_param($types, ...$values);

    if ($update_stmt->execute()) {
        // Fetch updated admin data
        $select_stmt = $conn->prepare("SELECT id, username, email, 'order', mess, 'statistics', user, product, discount, layout, decentralization, note, time FROM admin WHERE id = ?");
        $select_stmt->bind_param("s", $admin_id);
        $select_stmt->execute();
        $updated_admin = $select_stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'ok' => true,
            'success' => true,
            'message' => 'Cập nhật dữ liệu thành công!',
            'data' => [
                'id' => $updated_admin['id'],
                'username' => $updated_admin['username'],
                'email' => $updated_admin['email'],
                'roles' => [
                    'order' => (bool)$updated_admin['order'],
                    'mess' => (bool)$updated_admin['mess'],
                    'statistics' => (bool)$updated_admin['statistics'],
                    'user' => (bool)$updated_admin['user'],
                    'product' => (bool)$updated_admin['product'],
                    'discount' => (bool)$updated_admin['discount'],
                    'layout' => (bool)$updated_admin['layout'],
                    'decentralization' => (bool)$updated_admin['decentralization']
                ],
                'note' => $updated_admin['note'],
                'time' => $updated_admin['time']
            ]
        ]);
        http_response_code(200);
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Cập nhật không thành công: ' . $update_stmt->error
        ]);
        http_response_code(500);
    }
    $update_stmt->close();
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'ID không hợp lệ!'
    ]);
    http_response_code(401);
}

$stmt->close();
$conn->close();
?>