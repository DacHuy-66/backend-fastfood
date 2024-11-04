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
//     "username": "...",
//     "phone": "...",
// }
// url http://localhost/WebDoAn/main_admin.php/admin/create
elseif (strpos($request_uri, '/admin/create') !== false) {
    include './model/admin/fix_admin.php';
} 

// Delete admin
// url http://localhost/WebDoAn/main_admin.php/admin/delete/3
elseif (preg_match("/\/admin\/delete\/(\d+)\$/", $request_uri, $matches)) {
    $id_admin = $matches[1];
    include './model/admin/delete_admin.php';
}

// list admin
// url http://localhost/WebDoAn/main_admin.php/admin

elseif (preg_match("/\/admin$/", $request_uri) || preg_match("/\/admin\?/", $request_uri)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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

else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'URL not found'
    ]);
    http_response_code(404);
}

?>