<?php
function generateRandomId($length = 24)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function convertToWebUrl($url)
{
    // Check if URL is null or empty
    if (empty($url)) {
        return null;
    }

    // If URL already starts with http:// or https://, return as is
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }

    // Convert local path to web URL
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    return $baseUrl . '/' . ltrim($url, '/');
}

function validateNumeric($value, $fieldName)
{
    if (isset($value) && !is_numeric($value)) {
        throw new Exception("Invalid {$fieldName}. Must be a numeric value.");
    }
}

function validateStatus($status)
{
    if (isset($status) && !in_array((int)$status, [0, 1])) {
        throw new Exception('Invalid status. Must be 0 (inactive) or 1 (active).');
    }
}

// function setCorsHeaders($allowed_methods = 'GET') {
//     header('Access-Control-Allow-Origin: *');
//     header('Content-Type: application/json');
//     header('Access-Control-Allow-Methods: ' . $allowed_methods);
//     header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
// }

// function setDefaultCorsHeaders() {
//     header('Access-Control-Allow-Origin: *');
//     header('Content-Type: application/json');
//     header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
//     header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
// }

function setDefaultCorsHeaders()
{
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
}
