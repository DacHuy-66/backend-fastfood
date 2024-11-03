<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once __DIR__ . '/../../config/db.php';

try {
    // Extract review ID from URL
    $url_parts = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $review_id = end($url_parts);

    if (!is_numeric($review_id)) {
        throw new Exception('Invalid review ID', 400);
    }

    // Get PUT data
    $data = json_decode(file_get_contents("php://input"));

    // Validate rating if provided
    if (isset($data->rating)) {
        if (!is_numeric($data->rating) || $data->rating < 1 || $data->rating > 5) {
            throw new Exception('Invalid rating. Must be between 1 and 5', 400);
        }
    }

    // Check if review exists
    $check_query = "SELECT * FROM reviews WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $review_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Review not found', 404);
    }

    // Build update query dynamically based on provided fields
    $update_fields = array();
    $types = "";
    $values = array();

    if (isset($data->rating)) {
        $update_fields[] = "rating = ?";
        $types .= "i";
        $values[] = $data->rating;
    }

    if (isset($data->comment)) {
        $update_fields[] = "comment = ?";
        $types .= "s";
        $values[] = $data->comment;
    }

    if (isset($data->image_1)) {
        $update_fields[] = "image_1 = ?";
        $types .= "s";
        $values[] = $data->image_1;
    }

    if (isset($data->image_2)) {
        $update_fields[] = "image_2 = ?";
        $types .= "s";
        $values[] = $data->image_2;
    }

    if (isset($data->image_3)) {
        $update_fields[] = "image_3 = ?";
        $types .= "s";
        $values[] = $data->image_3;
    }

    if (empty($update_fields)) {
        throw new Exception('No fields to update', 400);
    }

    // Add review_id to values array and types
    $values[] = $review_id;
    $types .= "i";

    // Prepare and execute update query
    $query = "UPDATE reviews SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $stmt = $conn->prepare($query);

    // Dynamically bind parameters
    $bind_params = array($types);
    foreach ($values as $key => $value) {
        $bind_params[] = &$values[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);

    if ($stmt->execute()) {
        // Fetch the updated review
        $select_query = "SELECT r.*, u.username 
                        FROM reviews r
                        LEFT JOIN users u ON r.user_id = u.id 
                        WHERE r.id = ?";
        $select_stmt = $conn->prepare($select_query);
        $select_stmt->bind_param("i", $review_id);
        $select_stmt->execute();
        $updated = $select_stmt->get_result()->fetch_assoc();

        $response = [
            'status' => 'success',
            'message' => 'Review updated successfully',
            'code' => 200,
            'data' => $updated
        ];
        http_response_code(200);
    } else {
        throw new Exception('Failed to update review', 500);
    }

} catch (Exception $e) {
    $response = [
        'code' => $e->getCode() ?: 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

$conn->close();
echo json_encode($response);
?>