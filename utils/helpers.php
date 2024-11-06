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
    // kiểm tra URL có null hoặc rỗng không
    if (empty($url)) {
        return null;
    }

    // nếu URL đã bắt đầu với http:// hoặc https://, trả về như vậy
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }

    // chuyển đổi đường dẫn local thành URL web
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

function setDefaultCorsHeaders()
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // thêm header để debug
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header('Content-Type: application/json');

    // xử lý yêu cầu preflight
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header("HTTP/1.1 200 OK");
        exit();
    }
}
