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
$api_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : null;

// Check if API key is provided
if (!$api_key) {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'API key không được cấp.'
    ]);
    http_response_code(400);
    exit;
}

// Validate API key
$stmt = $conn->prepare("SELECT id FROM admin WHERE api_key = ?");
$stmt->bind_param("s", $api_key);
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
    
    if (isset($data['role_1'])) {
        $updates[] = "role_1 = ?";
        $types .= "i";
        $values[] = (int)$data['role_1'];
    }
    
    if (isset($data['role_2'])) {
        $updates[] = "role_2 = ?";
        $types .= "i";
        $values[] = (int)$data['role_2'];
    }
    
    if (isset($data['role_3'])) {
        $updates[] = "role_3 = ?";
        $types .= "i";
        $values[] = (int)$data['role_3'];
    }
    
    if (isset($data['role_4'])) {
        $updates[] = "role_4 = ?";
        $types .= "i";
        $values[] = (int)$data['role_4'];
    }
    
    if (isset($data['note'])) {
        $updates[] = "note = ?";
        $types .= "s";
        $values[] = $data['note'];
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
    $types .= "i";
    
    // Prepare and execute the update
    $update_stmt = $conn->prepare($sql);
    
    // Dynamically bind parameters
    $update_stmt->bind_param($types, ...$values);

    if ($update_stmt->execute()) {
        // Fetch updated admin data
        $select_stmt = $conn->prepare("SELECT id, username, email, role_1, role_2, role_3, role_4, note, time FROM admin WHERE id = ?");
        $select_stmt->bind_param("i", $admin_id);
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
                    'role_1' => (bool)$updated_admin['role_1'],
                    'role_2' => (bool)$updated_admin['role_2'],
                    'role_3' => (bool)$updated_admin['role_3'],
                    'role_4' => (bool)$updated_admin['role_4']
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
        'message' => 'API key không hợp lệ!'
    ]);
    http_response_code(401);
}

$stmt->close();
$conn->close();
?>