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
//url: http://localhost/WebDoAn/model/ui_client/main_ui.php/homepage
if (preg_match("/\/homepage\$/", $request_uri)) {
    include 'home_page.php';
} 

// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/homepage/header
elseif (preg_match("/\/homepage\/header\$/", $request_uri)) {
    include 'head_page.php';
} 

//url: http://localhost/WebDoAn/model/ui_client/main_ui.php/homepage/navbad
elseif (preg_match("/\/homepage\/navbad\$/", $request_uri)) {
    include 'nav_page.php';
} 

//url: http://localhost/WebDoAn/model/ui_client/main_ui.php/homepage/body
elseif (preg_match("/\/homepage\/body\$/", $request_uri)) {
    include 'body_page.php';
} 


//url: http://localhost/WebDoAn/model/ui_client/main_ui.php/abouts
elseif (preg_match("/\/abouts\$/", $request_uri)) {
    include 'head_review.php';
} 


// fix trademark
// {
//     "title": "",
//     "image": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/trademark
elseif (preg_match("/\/trademark\$/", $request_uri)) {
    include 'trademark/fix_trademark.php';
}

// fix home header
// {
//     "site_name": "",
//     "logo_url": "",
//     "site_slogan": "",
//     "opening_hours": "",
//     "search_placeholder": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/home/header
elseif (preg_match("/\/home\/header\$/", $request_uri)) {
    include 'Home/fix_home_header.php';
}

// fix home body
// {
//     "step_number": "",
//     "title": "",
//     "description": "",
//     "icon": "",
//     "order_number": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/home/body
elseif (preg_match("/\/home\/body\/(\w+)$/", $request_uri)) {
    include 'Home/fix_home_body.php';
}

// fix footer

// fix company info
// {
//     "name": "",
//     "description": "",
//     "copyright_text": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/footer/company
elseif (preg_match("/\/footer\/company\$/", $request_uri)) {
    include 'footer/fix_company_info.php';
}

// fix social media
// {
//     "platform": "",
//     "icon": "",
//     "url": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/footer/social/1
elseif (preg_match("/\/footer\/social\/(\w+)$/", $request_uri)) {
    include 'footer/fix_social_media.php';
}

// fix contact info
// {
//     "title": "",
//     "icon": "",
//     "content": "",
//     "type": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/footer/contact/1
elseif (preg_match("/\/footer\/contact\/(\w+)$/", $request_uri)) {
    include 'footer/fix_contact_info.php';
}

// fix about head
// {
//     "head_review": {
//       "id": 1,
//       "name": "Về Fastfood",
//       "description": "Thưởng thức hương vị nhanh chóng, ngon miệng"
//     },
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/about/head/1
elseif (preg_match("/\/about\/head\/(\w+)$/", $request_uri)) {
    include 'abouts/fix_about_head.php';
}

// fix about 

//fix body review main
// {
//     "name": "",
//     "description": "",
//     "icon": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/about/body_main/1
elseif (preg_match("/\/about\/body_main\/(\w+)$/", $request_uri)) {
    include 'abouts/fix_body_review_main.php';
}

// delete body review extra
//fix body review extra
// create body review extra
// {
//     "name": "",
//     "description": "",
//     "icon": ""
// }
// url: http://localhost/WebDoAn/model/ui_client/main_ui.php/about/body/extra/5
elseif (preg_match("/\/about\/body\/extra\/(\w+)$/", $request_uri)) {
    include 'abouts/fix_body_review_extra.php';
}



else {
    echo json_encode([
    'ok' => false,
    'success' => false,
    'message' => 'URL not found'
    ]);
    http_response_code(404);
}