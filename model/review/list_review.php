<?php

include_once __DIR__ . '/../../config/db.php';

try {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
    $rating = isset($_GET['rating']) ? intval($_GET['rating']) : null;
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    if ($page <= 0 || $limit <= 0) {
        throw new Exception('Trang và số lượng phải là số nguyên dương', 400);
    }

    $offset = ($page - 1) * $limit;

    $query = "SELECT r.*, u.username, u.avata, p.name as product_name, p.image_url as product_image_url
              FROM reviews r
              LEFT JOIN users u ON r.user_id = u.id
              LEFT JOIN products p ON r.product_id = p.id 
              WHERE 1=1 ";

    if ($search) {
        $query .= "AND (r.comment LIKE ? OR u.username LIKE ? OR p.name LIKE ?) ";
    }

    if ($product_id) {
        $query .= "AND r.product_id = ? ";
    }

    if ($rating) {
        $query .= "AND r.rating = ? ";
    }

    $query .= "ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);

    if ($search) {
        $search_param = "%$search%";
        if ($product_id && $rating) {
            $stmt->bind_param("sssiiii", $search_param, $search_param, $search_param, $product_id, $rating, $limit, $offset);
        } elseif ($product_id) {
            $stmt->bind_param("sssiii", $search_param, $search_param, $search_param, $product_id, $limit, $offset);
        } elseif ($rating) {
            $stmt->bind_param("sssiii", $search_param, $search_param, $search_param, $rating, $limit, $offset);
        } else {
            $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
        }
    } else {
        if ($product_id && $rating) {
            $stmt->bind_param("iiii", $product_id, $rating, $limit, $offset);
        } elseif ($product_id) {
            $stmt->bind_param("iii", $product_id, $limit, $offset);
        } elseif ($rating) {
            $stmt->bind_param("iii", $rating, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $reviews_arr = [];

    while ($row = $result->fetch_assoc()) {
        $reviews_arr[] = $row;
    }

    $count_query = "SELECT COUNT(*) as total FROM reviews r 
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN products p ON r.product_id = p.id  
                    WHERE 1=1";
    if ($search) {
        $count_query .= " AND (r.comment LIKE ? OR u.username LIKE ? OR p.name LIKE ?)";
    }
    if ($product_id) {
        $count_query .= " AND r.product_id = ?";
    }
    if ($rating) {
        $count_query .= " AND r.rating = ?";
    }

    $count_stmt = $conn->prepare($count_query);
    if ($search) {
        $search_param = "%$search%";
        if ($product_id && $rating) {
            $count_stmt->bind_param("sssii", $search_param, $search_param, $search_param, $product_id, $rating);
        } elseif ($product_id) {
            $count_stmt->bind_param("sssi", $search_param, $search_param, $search_param, $product_id);
        } elseif ($rating) {
            $count_stmt->bind_param("sssi", $search_param, $search_param, $search_param, $rating);
        } else {
            $count_stmt->bind_param("sss", $search_param, $search_param, $search_param);
        }
    } else {
        if ($product_id && $rating) {
            $count_stmt->bind_param("ii", $product_id, $rating);
        } elseif ($product_id) {
            $count_stmt->bind_param("i", $product_id);
        } elseif ($rating) {
            $count_stmt->bind_param("i", $rating);
        }
    }

    $count_stmt->execute();
    $total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];

    $response = [
        'status' => 'success',
        'message' => 'Lấy đánh giá thành công',
        'code' => 200,
        'data' => [
            'reviews' => $reviews_arr,
            'pagination' => [
                'total' => $total_reviews,
                'count' => count($reviews_arr),
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total_reviews / $limit)
            ]
        ]
    ];
    http_response_code(200);
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
