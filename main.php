<?php
$request_uri = $_SERVER['REQUEST_URI'];
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// login
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
//     "address": "..."
// }
// url ./WebDoAn/main.php/profile/...
elseif (preg_match("/\/profile\/(\w+)$/", $request_uri, $matches)) {
    $id_user = $matches[1]; // Lấy giá trị ID từ kết quả khớp
    include './model/profile/fix_profile.php';
    
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
// product
else {
        echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'URL not found'
        ]);
    http_response_code(response_code: 404);
}

?>
