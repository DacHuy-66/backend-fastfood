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
// ui_client route

// home_page
if (preg_match("/\/homepage\$/", $request_uri)) {
    include 'home_page.php';
} 

elseif (preg_match("/\/homepage\/header\$/", $request_uri)) {
    include 'head_page.php';
} 
elseif (preg_match("/\/homepage\/navbad\$/", $request_uri)) {
    include 'nav_page.php';
} 
elseif (preg_match("/\/homepage\/body\$/", $request_uri)) {
    include 'body_page.php';
} 
else {
    echo json_encode([
    'ok' => false,
    'success' => false,
    'message' => 'URL not found'
    ]);
http_response_code(response_code: 404);
}




