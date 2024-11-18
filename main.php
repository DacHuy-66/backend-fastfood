<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, X-Authorization,Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');

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
    $id_user = $matches[1];
    include './model/profile/fix_profile.php';
}

// delete user
// X-Api-Key:....
// http://localhost/WebDoAn/main.php/delete

elseif (preg_match("/\/delete\$/", $request_uri)) {
    include './model/profile/delete_user_client.php';
}
// xem thông tin profile 
// X-Api-Key:....
// url http://localhost/WebDoAn/main.php/profile
elseif (preg_match("/\/profile\$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/profile/profile_user.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use POST request.'
        ]);
    }
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

//  create account
//  {
//      "username": "example_user",
//      "email": "user@example.com", 
//      "password": "secure_password",
//  }
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


// forgot password
// url: http://localhost/WebDoAn/main.php/forgotpassword
elseif (preg_match("/\/forgotpassword\$/", $request_uri)) {
    include './model/login/forgotPass_user.php';
}

// reset password
// url: http://localhost/WebDoAn/main.php/resetpassword
elseif (preg_match("/\/resetpassword\$/", $request_uri)) {
    include './model/login/resetPassword_user.php';
}

// address routes

// show address
// url http://localhost/WebDoAn/main.php/address
elseif (preg_match("/\/address\$/", $request_uri)) {
    include './model/profile/address_user.php';
}
// detail address
// url http://localhost/WebDoAn/main.php/address/user_id
elseif (preg_match("/\/address\/(\w+)$/", $request_uri, $matches)) {
    $user_id = $matches[1];
    $_GET['id'] = $user_id;
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
    $address_id = $matches[1];
    $_GET['id'] = $address_id;
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
        $address_id = $matches[1];
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
//     "name": "qqqqq",
//     "description": "qqqqqq",
//     "price": 10000,
//     "type": "water",
//     "quantity": 100,
//     "status": true,
//     "lock": false,
//     "discount": "10",
//     "image_url": "qqqqqqq"
//   }
// url: http://localhost/WebDoAn/main.php/product


elseif (preg_match("/\/product$/", $request_uri) || preg_match("/\/product\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/product/create_product.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // phân tích chuỗi truy vấn để xử lý tất cả các tham số
        $query_string = parse_url($request_uri, PHP_URL_QUERY);
        parse_str($query_string ?? '', $query_params);

        // kiểm tra và làm sạch tất cả các tham số
        $page = isset($query_params['page']) ? filter_var($query_params['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($query_params['limit']) ? filter_var($query_params['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 40, 'min_range' => 1, 'max_range' => 100]
        ]) : 40;

        // làm sạch và xử lý tham số tìm kiếm
        $search = isset($query_params['q']) ? trim(urldecode($query_params['q'])) : '';

        // làm sạch tham số type
        $type = isset($query_params['type']) ? trim($query_params['type']) : '';

        // gán các tham số đã làm sạch trở lại $_GET
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;
        $_GET['q'] = $search;
        $_GET['type'] = $type;

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
// url: http://localhost/WebDoAn/main.php/product/123
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
// X-Api-Key: ""
// {
//     "product_id": "2kashfkshfkjhsadfkh",
//     "rating": 5,
//     "comment": "Great product!",
//     "image_1": "http://example.com/image1.jpg",
//     "image_2": "http://example.com/image2.jpg",
//     "image_3": "http://example.com/image3.jpg"
// }
// show review
// url: http://localhost/WebDoAn/main.php/review
// http://localhost/WebDoAn/main.php/review?page=1&limit=10&rating=5&search=Great
elseif (preg_match("/\/review$/", $request_uri) || preg_match("/\/review\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/review/create_review.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // kiểm tra và làm sạch các tham số phân trang
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]
        ]) : 10;

        // Lọc theo rating nếu có tham số rating
        $rating = isset($_GET['rating']) ? filter_var($_GET['rating'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5]
        ]) : null;

        // Lọc theo từ khóa tìm kiếm nếu có tham số search
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        // Đảm bảo các biến này có sẵn trong list_product.php
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;
        $_GET['rating'] = $rating;
        $_GET['search'] = $search;

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
// create discount
// {
//     "code": "SUwwMsadas20",
//     "name": "Summer",
//     "discount_percent": 10,
//     "quantity": 100,
//     "minimum_price": 10,
//     "valid_from": "2024-06-21",
//     "valid_to": "2024-06-22"
// }
// URL: http://localhost/WebDoAn/main.php/discount

elseif (preg_match("/\/discount$/", $request_uri) || preg_match("/\/discount\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Lấy tham số tìm kiếm từ query string
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]
        ]) : 10;

        // Lọc theo từ khóa tìm kiếm nếu có tham số search
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';

        // Đảm bảo các biến này có sẵn trong list_discount.php
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;
        $_GET['q'] = $search;

        include './model/discount/list_discount.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/discount/create_discount.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET or POST request.'
        ]);
        http_response_code(405);
    }
}

// delete discount
// fix discount
// {
//     "code": "alal2al",
//     "name": "Summs22sser",
//     "discount_percent": 10,
//     "quantity": 10,
//     "minimum_price": 20,
//     "valid_from": "2024-06-21",
//     "valid_to": "2024-06-29",
//     "status": 0
// }
// URL: http://localhost/WebDoAn/main.php/discount/{id}
elseif (preg_match("/\/discount\/(\w+)$/", $request_uri, $matches)) {
    $discount_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/discount/fix_discount.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        include './model/discount/delete_discount.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT or DELETE request.'
        ]);
        http_response_code(405);
    }
}

// discount_user

// list discount user
// url: http://localhost/WebDoAn/main.php/discount/user/{user_id}
elseif (preg_match("/\/discount\/user\/(\w+)$/", $request_uri, $matches)) {
    $user_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/discount/list_discount_user.php';
    }
}

// delete discount user
// URL: http://localhost/WebDoAn/main.php/discount/user/delete/{id}
elseif (preg_match("/\/discount\/user\/delete\/(\w+)$/", $request_uri, $matches)) {
    $product_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
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
// fix discount user
// {
//     "email": "thuan33@gmail.com",
//     "name": "Giảmsdasd è",
//     "description": "Khuyến mãi ",
//     "minimum_price": 20000,
//     "code": "22ádas22220",
//     "discount_percent": 20,
//     "valid_from": "2024-03-21",
//     "valid_to": "2024-04-21",
//     "status": 1
// }
// URL: http://localhost/WebDoAn/main.php/discount/user/fix/{id}
elseif (preg_match("/\/discount\/user\/fix\/(\w+)$/", $request_uri, $matches)) {
    $discount_user_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/discount/fix_discount_user.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT request.'
        ]);
        http_response_code(405);
    }
}

// create discount user
// {
//     "email": "thuan33@gmail.com",
//     "name": "Giảm mùa hè",
//     "minimum_price": 100000,
//     "discount_percent": 15,
//     "valid_from": "2024-03-20",
//     "valid_to": "2024-04-20"
// }
// URL: http://localhost/WebDoAn/main.php/discount_user/create
elseif (preg_match("/\/discount_user\/create$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/discount/create_discount_user.php';
    }
}

// list all discount user
// url: http://localhost/WebDoAn/main.php/discount_user/all
elseif (preg_match("/\/discount_user\/all$/", $request_uri) || preg_match("/\/discount_user\/all\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/discount/ListAll_discount_user.php';
    }
}

// discount_history
// URL: http://localhost/WebDoAn/main.php/discount_history
elseif (preg_match("/\/discount_history$/", $request_uri) || preg_match("/\/discount_history\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/discount/list_discount_history.php';
    }
}

// promotion route

// list_promotion
// URL: http://localhost/WebDoAn/main.php/promotion
elseif (preg_match("/\/promotion(\?.*)?$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/promotion/list_promotion.php';
    }
}

// delete promotion
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
elseif (preg_match("/\/promotion\/(\w+)$/", $request_uri, $matches)) {
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

    // create promotion
    // {
    //     "title": "...",
    //     "description": "...",
    //     "discount_percent": 0.2,
    //     "start_date": "...",
    //     "end_date": "...",
    //     "min_order_value": 10,
    //     "max_discount": 100
    // }
    // URL: http://localhost/WebDoAn/main.php/promotion
} elseif (preg_match("/\/promotion$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/promotion/create_promotion.php';
    }
}

// user route

// list_user
// URL: http://localhost/WebDoAn/main.php/user
elseif (preg_match("/\/user(\?.*)?$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/user/list_user.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// delete user
// fix user
// {
//     "username": "aaaaaa",
//     "email": "aaaa",
//     "password": "aaaaa",
//     "role": 1,
//     "avata": "aaaaaaa"
// }
// URL: http://localhost/WebDoAn/main.php/user/123
elseif (preg_match("/\/user\/(\w+)$/", $request_uri, $matches)) {
    $user_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        include './model/user/delete_user.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/user/fix_user.php';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/user/detail_user.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use DELETE request.'
        ]);
        http_response_code(405);
    }
}

// cart route

// list cart
// URL: http://localhost/WebDoAn/main.php/cart
elseif (preg_match("/\/cart(\?.*)?$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/cart/list_cart.php';
    }
}

// create cart
// URL: http://localhost/WebDoAn/main.php/cart/create
// {
//     "api_key": "144a13d3af38855ce0fbaa60a5945e415fe8c01802ca61cc0a9bf8bf4257aa0f",
//     "product_id": "672b7e2f4e006",
//     "quantity": 10
// }
elseif (preg_match("/\/cart\/create$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/cart/create_cart.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use POST request.'
        ]);
        http_response_code(405);
    }
}
// delete cart
// URL: http://localhost/WebDoAn/main.php/cart/delete/id
// {
//     "delete_type": "reduce",
//     "quantity": 2
// }
elseif (preg_match("/\/cart\/delete\/(\w+)$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        include './model/cart/delete_cart.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use DELETE request.'
        ]);
        http_response_code(405);
    }
}

// order route

// list order
// URL: http://localhost/WebDoAn/main.php/order
elseif (preg_match("/\/order(\?.*)?$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/order/list_order.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// detail order
// URL: http://localhost/WebDoAn/main.php/order_detail/order_id
elseif (preg_match("/\/order_detail\/(\w+)$/", $request_uri, $matches)) {
    $order_id = $matches[1];
    $_GET['id'] = $order_id;
    include './model/order/detail_order.php';
}

//create order
// {
//     "user_id": "672f0ed7ad5ce",
//     "address_id": "54",
//     "products": [
//         {
//             "product_id": "P020",
//             "quantity": 2
//         },
//         {
//             "product_id": "P021",
//             "quantity": 1
//         }
//     ],
//     "payment_method": "COD",
//     "note": "Giao hàng giờ hành chính",     // tùy chọn
//     "discount_code": "SALE10"               // tùy chọn
// }
// URL: http://localhost/WebDoAn/main.php/order/create
elseif (preg_match("/\/order\/create$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/order/create_order.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use POST request.'
        ]);
        http_response_code(405);
    }
}

// fix order
// {
//     "status": "Đã giao hàng"
// }
// URL: http://localhost/WebDoAn/main.php/order/fix/order_id    
elseif (preg_match("/\/order\/fix\/(\w+)$/", $request_uri, $matches)) {
    $order_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/order/fix_order.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT request.'
        ]);
        http_response_code(405);
    }
}

// history order
// URL: http://localhost/WebDoAn/main.php/history_order
elseif (preg_match("/\/history_order$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/order/history_order.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// history order admin
// URL: http://localhost/WebDoAn/main.php/history_order_admin
elseif (preg_match("/\/history_order_admin(\?.*)?$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/order/history_order_admin.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// detail order client
// URL: http://localhost/WebDoAn/main.php/order_detail/order_id
elseif (preg_match("/\/order_detail\/(\w+)$/", $request_uri, $matches)) {
    $order_id = $matches[1];
    include './model/order/detail_order.php';
}

// message route
// list message
// X-Api-Key: 
// URL: http://localhost/WebDoAn/main.php/message
elseif (preg_match("/\/message$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/message/list_message.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// create message user
// X-Api-Key: 
// {
//     "content": "...",
// }
// URL: http://localhost/WebDoAn/main.php/message_user
elseif (preg_match("/\/message_user$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/message/create_message_user.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use POST request.'
        ]);
        http_response_code(405);
    }
}

// create message admin
// {
//     "content": "...",
// }
// URL: http://localhost/WebDoAn/main.php/message_admin/user_id
elseif (preg_match("/\/message_admin\/(\w+)$/", $request_uri, $matches)) {
    $user_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include './model/message/create_message_admin.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use POST request.'
        ]);
        http_response_code(405);
    }
}

// list message admin
// URL: http://localhost/WebDoAn/main.php/message_admin
elseif (preg_match("/\/message_admin$/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/message/list_message_admin.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// detail message user
// URL: http://localhost/WebDoAn/main.php/detail_message_user/user_id
elseif (preg_match("/\/detail_message_user\/(\w+)$/", $request_uri, $matches)) {
    $user_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/message/detail_message_user.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// fix message 
// URL: http://localhost/WebDoAn/main.php/message/fix/{message_id}
// Method: PUT
// Body: { "status": 0 } // 0: đã đọc, 1: chưa đọc
elseif (preg_match("/\/message\/fix\/(\w+)$/", $request_uri, $matches)) {
    $message_id = $matches[1];
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        include './model/message/fix_message.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use PUT request.'
        ]);
        http_response_code(405);
    }
}

// dashboard route
// url: /main.php/dashboard
elseif (strpos($request_uri, '/dashboard') !== false) {
    include './model/dashboard/dashboard.php';
}

// weekly orders
// url: /main.php/weekly_orders
elseif (strpos($request_uri, '/weekly_orders') !== false) {
    include './model/dashboard/weekly_orders.php';
}

// weekly revenue
// url: /main.php/weekly_revenue
elseif (strpos($request_uri, '/weekly_revenue') !== false) {
    include './model/dashboard/weekly_revenue.php';
}
// top selling products
// url: /main.php/top_selling_products
elseif (strpos($request_uri, '/top_selling_products') !== false) {
    include './model/dashboard/top_selling_products.php';
}


// statistical route
// order statistics
// url: http://localhost/WebDoAn/main.php/order_statistics
elseif (preg_match("/\/order_statistics$/", $request_uri) || preg_match("/\/order_statistics\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Lấy tham số tìm kiếm từ query string
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]
        ]) : 10;

        // Lọc theo từ khóa tìm kiếm nếu có tham số search
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';

        // Đảm bảo các biến này có sẵn trong order_statistics.php
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;
        $_GET['q'] = $search;

        include './model/statistical/order_statistics.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// product statistics
// url: http://localhost/WebDoAn/main.php/product_statistics
elseif (preg_match("/\/product_statistics$/", $request_uri) || preg_match("/\/product_statistics\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Lấy tham số tìm kiếm từ query string
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]
        ]) : 10;

        // Lọc theo từ khóa tìm kiếm nếu có tham số search
        $search = isset($_GET['q']) ? trim($_GET['q']) : '';

        // Đảm bảo các biến này có sẵn trong product_statistics.php
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;
        $_GET['q'] = $search;

        include './model/statistical/product_statistics.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }   
}

// user statistics
// url: http://localhost/WebDoAn/main.php/user_statistics
elseif (preg_match("/\/user_statistics$/", $request_uri) || preg_match("/\/user_statistics\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        include './model/statistical/user_statistics.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'URL not found'
    ]);
    http_response_code(404);
}
