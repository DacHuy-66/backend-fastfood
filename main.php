<?php
$request_uri = $_SERVER['REQUEST_URI'];
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
// url ./WebDoAn/main.php/profile/...
elseif (preg_match("/\/profile\/(\w+)$/", $request_uri, $matches)) {
    $id_user = $matches[1]; // Lấy giá trị ID từ kết quả khớp
    include './model/profile/fix_profile.php';
    
}

// delete user
// X-Api-Key:....
// http://localhost/WebDoAn/main.php/delete
// elseif (strpos($request_uri, '/delete') !== false) {
//     include './model/profile/delete_user.php';
// } 
elseif (preg_match("/\/delete\$/", $request_uri)) {
    include './model/profile/delete_user.php';
} 
// xem thông tin profile 
// X-Api-Key:....
// url ./WebDoAn/main.php/profile/...
elseif (preg_match("/\/profile\$/", $request_uri)) {
    include './model/profile/profile_user.php';
} 

//đường dẫn đổi mật khẩu
// {
//     "current_password": "...",
//     "new_password": "..."
// }
// url: http://localhost/WebDoAn/main.php/profile/change/...
elseif (preg_match("/\/profile\/change\/(\w+)$/", $request_uri, $matches)) {
    $id_user = $matches[1]; // Lấy giá trị ID từ kết quả khớp
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
// url ./WebDoAn/main.php/address
elseif (preg_match("/\/address\$/", $request_uri)) {
    include './model/profile/address_user.php';
} 
// detail address
// url ./WebDoAn/main.php/address/...
 elseif (preg_match("/\/address\/(\w+)$/", $request_uri, $matches)) {
    $user_id = $matches[1]; // Extract the user ID from the URI
    $_GET['id'] = $user_id; // Set the 'id' parameter for detail_address.php
    include './model/profile/detail_address.php';
}

// create address
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
// URL: ./WebDoAn/main.php/address/delete/{id}
elseif (preg_match("/\/address\/delete\/(\d+)$/", $request_uri, $matches)) {
    $address_id = $matches[1]; // Extract the address ID from the URI
    $_GET['id'] = $address_id; // Set the 'id' parameter for delete_address.php
    include './model/profile/delete_address.php';
}


// update address 
// url: http://localhost/WebDoAn/main.php/address/update/{user_id}
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

// Get product details: GET /products/{id}
// url: http://localhost/WebDoAn/main.php/products/123
elseif (preg_match("/\/products\/(\w+)$/", $request_uri, $matches)) {
    include './model/product/detail_product.php';
}
// hiển thị tất cả sản phẩm: GET /products
// url: http://localhost/WebDoAn/main.php/products
elseif (preg_match("/\/products$/", $request_uri)) {
    include './model/product/list_product.php';
}

else {
        echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'URL not found'
        ]);
    http_response_code(response_code: 404);
}

?>
