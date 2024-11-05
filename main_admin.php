<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Add headers for debugging
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

// admin routes

// login
// X-Api-Key: ...
// {
//     "email": "....",
//     "password": "...."
// }
// url http://localhost/WebDoAn/main_admin.php/admin/login
if (strpos($request_uri, '/admin/login') !== false) {
    include './model/admin/Login_admin.php';
}

// Create admin
// X-Api-Key:....
// {
//     "username": "admin12",
//     "email": "a3uuu2u2u2@gmail.com",
//     "password": "admin12@gmail.com",
//     "order": 0,
//     "mess": 0,
//     "statistics": 0,
//     "user": 0,
//     "product": 0,
//     "discount": 1,
//     "layout": 1,
//     "decentralization": 0,
//     "note": "sdasdas"
// }
// url http://localhost/WebDoAn/main_admin.php/Decentralization/update
elseif (strpos($request_uri, '/Decentralization/create') !== false) {
    include './model/admin/create_admin.php';
}



// Fix admin
// {
//     "username": "admin12",
//     "email": "a3uuu2u2u2@gmail.com",
//     "password": "admin12@gmail.com",
//     "order": 0,
//     "mess": 0,
//     "statistics": 0,
//     "user": 0,
//     "product": 0,
//     "discount": 1,
//     "layout": 1,
//     "decentralization": 0,
//     "note": "sdasdas"
// }
// url http://localhost/WebDoAn/main_admin.php/Decentralization/create
elseif (preg_match("/\/Decentralization\/update\/(\w+)\$/", $request_uri, $matches)) {
    $admin_id = $matches[1];
    include './model/admin/fix_admin.php';
}

// Delete admin
// url http://localhost/WebDoAn/main_admin.php/Decentralization/delete
elseif (preg_match("/\/Decentralization\/delete\/(\w+)\$/", $request_uri, $matches)) {
    $id_admin = $matches[1];
    include './model/admin/delete_admin.php';
}

// list admin
// url http://localhost/WebDoAn/main_admin.php/admin

elseif (preg_match("/\/admin$/", $request_uri) || preg_match("/\/admin\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Validate and sanitize pagination parameters
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 1, 'min_range' => 1]
        ]) : 1;

        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
            'options' => ['default' => 10, 'min_range' => 1, 'max_range' => 100]
        ]) : 10;

        // Pass pagination parameters to list_admin.php
        $_GET['page'] = $page;
        $_GET['limit'] = $limit;

        include './model/admin/list_admin.php';
    } else {
        echo json_encode([
            'ok' => false,
            'success' => false,
            'message' => 'Method not allowed. Use GET request.'
        ]);
        http_response_code(405);
    }
}

// role admin
// X-Api-Key:....
// url http://localhost/WebDoAn/main_admin.php/admin/role
elseif (strpos($request_uri, '/admin/role') !== false) {
    include './model/admin/role_admin.php';
} 


else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'URL not found'
    ]);
    http_response_code(404);
}

?>