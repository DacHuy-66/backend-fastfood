<?php
function generateRandomId($length = 24) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function convertToWebUrl($url) {
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

function validateNumeric($value, $fieldName) {
    if (isset($value) && !is_numeric($value)) {
        throw new Exception("Invalid {$fieldName}. Must be a numeric value.");
    }
}

function validateStatus($status) {
    if (isset($status) && !in_array((int)$status, [0, 1])) {
        throw new Exception('Invalid status. Must be 0 (inactive) or 1 (active).');
    }
}