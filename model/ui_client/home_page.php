<?php
include_once __DIR__ . '/../../config/db.php';

// lấy thông tin công ty
function getCompanyInfo($conn) {
    $result = $conn->query("SELECT * FROM company_info LIMIT 1");
    return $result->fetch_assoc();
}

// lấy thông tin liên hệ
function getContactInfo($conn) {
    $result = $conn->query("SELECT * FROM contact_info");
    $contacts = [];
    while($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }
    
    return [
        'title' => 'Liên hệ với chúng tôi',
        'items' => $contacts
    ];
}

// lấy liên kết mạng xã hội
function getSocialMedia($conn) {
    $result = $conn->query("SELECT * FROM social_media");
    $socialMedia = [];
    while($row = $result->fetch_assoc()) {
        $socialMedia[] = $row;
    }
    return $socialMedia;
}

// lấy liên kết footer
function getFooterLinks($conn) {
    $result = $conn->query("SELECT * FROM footer_links");
    $footerLinks = [];
    while($row = $result->fetch_assoc()) {
        $footerLinks[] = $row;
    }
    return $footerLinks;
}

// lấy phần newsletter
function getNewsletter($conn) {
    $result = $conn->query("SELECT * FROM newsletter_section LIMIT 1");
    return $result->fetch_assoc();
}

// kết hợp tất cả dữ liệu
try {
    $response = [
        'companyInfo' => getCompanyInfo($conn),
        'contactSection' => getContactInfo($conn),
        'socialMedia' => getSocialMedia($conn),
        'footerLinks' => getFooterLinks($conn),
        'newsletter' => getNewsletter($conn),
        'ok' => true
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Đã xảy ra lỗi: ' . $e->getMessage()
    ]);
}