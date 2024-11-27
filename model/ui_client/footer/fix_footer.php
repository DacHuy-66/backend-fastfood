<?php
include_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $success = true;
    $messages = [];

    // Xử lý company_info
    if (isset($data['company_info'])) {
        // Lấy dữ liệu hiện tại
        $sql_select = "SELECT name, logo, description, copyright_text FROM company_info WHERE id = 1";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->execute();
        $current_data = $stmt_select->get_result()->fetch_assoc();
        $stmt_select->close();

        // Cập nhật với dữ liệu mới hoặc giữ nguyên dữ liệu cũ
        $company_info = $data['company_info'];
        $name = isset($company_info['name']) ? trim($company_info['name']) : $current_data['name'];
        $logo = isset($company_info['logo']) ? trim($company_info['logo']) : $current_data['logo'];
        $description = isset($company_info['description']) ? trim($company_info['description']) : $current_data['description'];
        $copyright_text = isset($company_info['copyright_text']) ? trim($company_info['copyright_text']) : $current_data['copyright_text'];

        $sql_update_company = "UPDATE company_info SET name = ?, logo = ?, description = ?, copyright_text = ? WHERE id = 1";
        $stmt_company = $conn->prepare($sql_update_company);
        $stmt_company->bind_param("ssss", $name, $logo, $description, $copyright_text);
        
        if (!$stmt_company->execute()) {
            $success = false;
            $messages[] = "Lỗi khi cập nhật company_info: " . $stmt_company->error;
        }
        $stmt_company->close();
    }

    // Xử lý social_media
    if (isset($data['social_media']) && is_array($data['social_media'])) {
        foreach ($data['social_media'] as $media) {
            if (is_array($media) && isset($media['id'])) {
                // Lấy dữ liệu hiện tại
                $sql_select = "SELECT platform, icon, url FROM social_media WHERE id = ?";
                $stmt_select = $conn->prepare($sql_select);
                $stmt_select->bind_param("i", $media['id']);
                $stmt_select->execute();
                $current_data = $stmt_select->get_result()->fetch_assoc();
                $stmt_select->close();

                // Cập nhật với dữ liệu mới hoặc giữ nguyên dữ liệu cũ
                $platform = isset($media['platform']) ? trim($media['platform']) : $current_data['platform'];
                $icon = isset($media['icon']) ? trim($media['icon']) : $current_data['icon'];
                $url = isset($media['url']) ? trim($media['url']) : $current_data['url'];

                $sql_update_social = "UPDATE social_media SET platform = ?, icon = ?, url = ? WHERE id = ?";
                $stmt_social = $conn->prepare($sql_update_social);
                $stmt_social->bind_param("sssi", $platform, $icon, $url, $media['id']);
                
                if (!$stmt_social->execute()) {
                    $success = false;
                    $messages[] = "Lỗi khi cập nhật social_media ID {$media['id']}: " . $stmt_social->error;
                }
                $stmt_social->close();
            }
        }
    }

    // Xử lý contact_info
    if (isset($data['contact_info']) && is_array($data['contact_info'])) {
        foreach ($data['contact_info'] as $contact) {
            if (is_array($contact) && isset($contact['id'])) {
                // Lấy dữ liệu hiện tại
                $sql_select = "SELECT title, icon, content, type FROM contact_info WHERE id = ?";
                $stmt_select = $conn->prepare($sql_select);
                $stmt_select->bind_param("i", $contact['id']);
                $stmt_select->execute();
                $current_data = $stmt_select->get_result()->fetch_assoc();
                $stmt_select->close();

                // Cập nhật với dữ liệu mới hoặc giữ nguyên dữ liệu cũ
                $title = isset($contact['title']) ? trim($contact['title']) : $current_data['title'];
                $icon = isset($contact['icon']) ? trim($contact['icon']) : $current_data['icon'];
                $content = isset($contact['content']) ? trim($contact['content']) : $current_data['content'];
                $type = isset($contact['type']) ? trim($contact['type']) : $current_data['type'];

                $sql_update_contact = "UPDATE contact_info SET title = ?, icon = ?, content = ?, type = ? WHERE id = ?";
                $stmt_contact = $conn->prepare($sql_update_contact);
                $stmt_contact->bind_param("ssssi", $title, $icon, $content, $type, $contact['id']);
                
                if (!$stmt_contact->execute()) {
                    $success = false;
                    $messages[] = "Lỗi khi cập nhật contact_info ID {$contact['id']}: " . $stmt_contact->error;
                }
                $stmt_contact->close();
            }
        }
    }

    echo json_encode([
        'ok' => $success,
        'success' => $success,
        'message' => $success ? 'Cập nhật thành công' : implode('; ', $messages)
    ]);
} else {
    echo json_encode([
        'ok' => false,
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}

$conn->close();
?>
