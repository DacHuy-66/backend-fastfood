<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Thêm header để debug
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}
$request_uri = $_SERVER['REQUEST_URI'];

// user routes
// {
//     "email": "....",
//     "password": "...."
// }
// url ./WebDoAn/main.php/apikey
if (strpos($request_uri, '/apikey') !== false) {
    include './model/login/LoginApiKey.php';
}
// Sửa user 
// X-Api-Key:....
// {
//     "username": "...",
//     "phone": "...",
// }
// url http://localhost/WebDoAn/main.php/profile/...
elseif (preg_match("/\/profile\/(\w+)$/", $request_uri, $matches)) {
    $id_user = $matches[1]; // Lấy giá trị ID từ kết quả khớp
    include './model/profile/fix_profile.php';
}

// delete user
// X-Api-Key:....
// http://localhost/WebDoAn/main.php/delete

elseif (preg_match("/\/delete\$/", $request_uri)) {
    include './model/profile/delete_user.php';
}
// xem thông tin profile 
// X-Api-Key:....
// url http://localhost/WebDoAn/main.php/profile/...
elseif (preg_match("/\/profile\$/", $request_uri)) {
    include './model/profile/profile_user.php';
}

//đường dẫn đổi mật khẩu
// {
//     "current_password": "...",
//     "new_password": "..."
// }
// url: http://localhost/WebDoAn/main.php/change/password/...
elseif (preg_match("/\/change\/password\$/", $request_uri)) {
    include './model/profile/changePass_user.php';
}

// create account
// {
//     "username": "example_user",
//     "email": "user@example.com", 
//     "password": "secure_password",
//     "phone": "111111",  // optional
//     "address": "HN"     // optional
// }
// url: http://localhost/WebDoAn/main.php/register
elseif (strpos($request_uri, '/register') !== false) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/register/create_account.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use POST request.'
        ]);
        http_response_code(405);
    }
}

// address routes

// show address
// url http://localhost/WebDoAn/main.php/address
elseif (preg_match("/\/address\$/", $request_uri)) {
    include './model/profile/address_user.php';
}
// detail address
// url http://localhost/WebDoAn/main.php/address/...
elseif (preg_match("/\/address\/(\w+)$/", $request_uri, $matches)) {
    $user_id = $matches[1]; // Extract the user ID from the URI
    $_GET['id'] = $user_id; // Set the 'id' parameter for detail_address.php
    include './model/profile/detail_address.php';
}

// create address
// {
//     "address": "",
//     "phone": "",
//     "note": ""
// }
// url: http://localhost/WebDoAn/main.php/address/create/{user_id}
elseif (preg_match('/\/address\/create\/([^\/]+)$/', $request_uri, $matches)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $matches[1]; // Extract user_id from URL
        include './model/profile/create_address.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use POST request.'
        ]);
        http_response_code(405);
    }
}
// delete address
// URL: http://localhost/WebDoAn/main.php/address/delete/{id}
elseif (preg_match("/\/address\/delete\/(\d+)$/", $request_uri, $matches)) {
    $address_id = $matches[1]; // Extract the address ID from the URI
    $_GET['id'] = $address_id; // Set the 'id' parameter for delete_address.php
    include './model/profile/delete_address.php';
}


// update address 
// {
//     "address": "",
//     "phone": "",
//     "note": ""
// }
// url: http://localhost/WebDoAn/main.php/address/update/{id}
elseif (preg_match('/\/address\/update\/([^\/]+)$/', $request_uri, $matches)) {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $address_id = $matches[1]; // Extract address_id from URL
        include './model/profile/fix_address.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT request.'
        ]);
        http_response_code(405);
    }
}


// Product routes

// Create product: POST /products
// {
//     "name": "",
//     "description": " ",
//     "price": 10000,
//     "quantity": 100,
//     "status": true,
//     "discount": "0.2",
//     "image_url": ""
//   }
// url: http://localhost/WebDoAn/main.php/products


elseif (preg_match("/\/product$/", $request_uri) || preg_match("/\/product\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/product/create_product.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Validate and sanitize pagination parameters
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]
        ]) : 10;

        // Ensure these variables are available in list_product.php
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;

        include './model/product/list_product.php';
    }
}

// Update product: PUT /products/{id}
// {
//     "name": " ",
//     "price": ,
//     "quantity": 200,
//     "status": false,
//     "discount": "",
//     "description": " ",
//     "image_url": " "
//   }
// Delete product: DELETE /products/{id}
// Get product details: GET /products/{id}
// url: http://localhost/WebDoAn/main.php/products/123
elseif (preg_match("/\/product\/(\w+)$/", $request_uri, $matches)) {
    $product_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/product/fix_product.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        include './model/product/delete_product.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT, or DELETE request.'
        ]);
        http_response_code(405);
    }
}

// Get product details: GET /detail/{id}?page=1&limit=3
// url: http://localhost/WebDoAn/main.php/detail/2kashfkshfkjhsadfkh?page=1&limit=3
elseif (preg_match("/\/detail\/([^\/\?]+)(?:\?.*)?$/", $request_uri, $matches)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Validate and sanitize pagination parameters
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 3, 'min_range' => 1, 'max_range' => 100]
        ]) : 3;

        // Pass product_id and pagination parameters to detail_product.php
        $_GET['product_id'] = $matches[1];
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;

        include './model/product/detail_product.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// url: http://localhost/WebDoAn/main.php/products/top?limit=10
elseif (preg_match("/\/products\/top$/", $request_uri) || preg_match("/\/products\/top\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/product/top_product.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}


// review route

// create review
// {
//     "user_id": "6725f9f019f57",
//     "product_id": "2kashfkshfkjhsadfkh",
//     "rating": 5,
//     "comment": "Great product!",
//     "image_1": "http://example.com/image1.jpg",
//     "image_2": "http://example.com/image2.jpg",
//     "image_3": "http://example.com/image3.jpg"
// }
// show review
// url: http://localhost/WebDoAn/main.php/review
elseif (preg_match("/\/review$/", $request_uri) || preg_match("/\/review\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/review/create_review.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Validate and sanitize pagination parameters
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]
        ]) : 10;

        // Ensure these variables are available in list_product.php
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;

        include './model/review/list_review.php';
    }
}
// delete review
// detail address
// URL: http://localhost/WebDoAn/main.php/review/{id}
// update review
elseif (preg_match("/\/review\/(\w+)$/", $request_uri, $matches)) {
    $product_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/review/fix_review.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/product/detail_review.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        include './model/review/delete_review.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT, or DELETE request.'
        ]);
        http_response_code(405);
    }
}

// discount route 
// list_discount
// URL: http://localhost/WebDoAn/main.php/discount

elseif (preg_match("/\/discount\$/", $request_uri)) {
    include './model/discount/list_discount.php';
}

// discount_user
//{
//     "name": "Summer",
//     "code": "SUM20",
//     "description": "20% off",
//     "minimum_price": 10,
//     "type": "percent",
//     "discount_percent": 0.2,
//     "valid_from": "2024-06-22",
//     "valid_to": "2024-06-22"
// }
elseif (preg_match("/\/discount\/user\/(\w+)$/", $request_uri, $matches)) {
    $product_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/discount/fix_discount_user.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/discount/list_discount_user.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        include './model/discount/delete_discount_user.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT, or DELETE request.'
        ]);
        http_response_code(405);
    }
}

// promotion route

// list_promotion
// URL: http://localhost/WebDoAn/main.php/promotion
elseif (preg_match("/\/promotion\$/", $request_uri)) {
    include './model/promotion/list_promotion.php';

// fix_promotion
// {
//     "title": "...",
//     "description": "...",
//     "discount_percent": 0.2,
//     "start_date": "...",
//     "end_date": "...",
//     "min_order_value": 10,
//     "max_discount": 100
// }
// URL: http://localhost/WebDoAn/main.php/promotion/123
} elseif (preg_match("/\/promotion\/(\w+)$/", $request_uri, $matches)) {
    $promotion_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/promotion/fix_promotion.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        include './model/promotion/delete_promotion.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT, or DELETE request.'
        ]);
        http_response_code(405);
    }
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'URL not found'
    ]);
    http_response_code(response_code: 404);
}
