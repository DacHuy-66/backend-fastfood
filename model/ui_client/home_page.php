<?php
include_once __DIR__ . '/../../config/db.php';
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Get company info
function getCompanyInfo($conn) {
    $result = $conn->query("SELECT * FROM company_info LIMIT 1");
    return $result->fetch_assoc();
}

// Get contact info
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

// Get social media links
function getSocialMedia($conn) {
    $result = $conn->query("SELECT * FROM social_media");
    $socialMedia = [];
    while($row = $result->fetch_assoc()) {
        $socialMedia[] = $row;
    }
    return $socialMedia;
}

// Get footer links
function getFooterLinks($conn) {
    $result = $conn->query("SELECT * FROM footer_links");
    $footerLinks = [];
    while($row = $result->fetch_assoc()) {
        $footerLinks[] = $row;
    }
    return $footerLinks;
}

// Get newsletter section
function getNewsletter($conn) {
    $result = $conn->query("SELECT * FROM newsletter_section LIMIT 1");
    return $result->fetch_assoc();
}

// Combine all data
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
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}