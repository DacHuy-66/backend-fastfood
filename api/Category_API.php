<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Kiểm tra URL path
$current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/WebDoAn/api/Category_API.php';

if ($current_url !== $base_path) {
    $response = [
        'code' => 400,
        'status_code' => 'FAILED',
        'message' => 'Invalid API endpoint'
    ];
    http_response_code(400);
    echo json_encode($response);
    exit;
}

include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../model/Category.php';

// Khởi tạo đối tượng Category
$category = new Category($conn);

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single category
                $category_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$category_id) {
                    throw new Exception('Invalid category ID format', 400);
                }

                $result = $category->show($category_id);
                if ($result->num_rows > 0) {
                    $category_data = $result->fetch_assoc();
                    
                    // Get products if requested
                    if (isset($_GET['include_products']) && $_GET['include_products'] === 'true') {
                        $category->id = $category_id;
                        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                        
                        $products_result = $category->getCategoryProducts($page, $limit);
                        $products = [];
                        while ($row = $products_result->fetch_assoc()) {
                            $products[] = $row;
                        }
                        
                        $total_products = $category->getCategoryProductCount();
                        $category_data['products'] = [
                            'items' => $products,
                            'pagination' => [
                                'total' => $total_products,
                                'count' => count($products),
                                'per_page' => $limit,
                                'current_page' => $page,
                                'total_pages' => ceil($total_products / $limit)
                            ]
                        ];
                    }
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Category retrieved successfully',
                        'code' => 200,
                        'data' => $category_data
                    ];
                } else {
                    throw new Exception('Category not found', 404);
                }
            } else {
                // List categories with search and pagination
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                
                if ($page <= 0 || $limit <= 0) {
                    throw new Exception('Page and limit must be positive integers', 400);
                }

                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $result = $category->search($search, $page, $limit);
                    $total_categories = $category->getSearchCount($search);
                } else {
                    $result = $category->read($page, $limit);
                    $total_categories = $category->getTotalCount();
                }

                $categories_arr = [];
                while ($row = $result->fetch_assoc()) {
                    $categories_arr[] = $row;
                }

                $response = [
                    'status' => 'success',
                    'message' => 'Categories retrieved successfully',
                    'code' => 200,
                    'data' => [
                        'categories' => $categories_arr,
                        'pagination' => [
                            'total' => $total_categories,
                            'count' => count($categories_arr),
                            'per_page' => $limit,
                            'current_page' => $page,
                            'total_pages' => ceil($total_categories / $limit)
                        ]
                    ]
                ];
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            
            if (isset($data->id)) {
                // Update existing category
                if (!isset($data->Name) || empty(trim($data->Name))) {
                    throw new Exception('Category name is required', 400);
                }

                $category->id = $data->id;
                $category->Name = $data->Name;
                $category->Description = $data->Description ?? '';

                if ($category->update()) {
                    $updated = $category->show($category->id)->fetch_assoc();
                    $response = [
                        'status' => 'success',
                        'message' => 'Category updated successfully',
                        'code' => 200,
                        'data' => $updated
                    ];
                } else {
                    throw new Exception('Failed to update category', 500);
                }
            } else {
                // Create new category
                if (!isset($data->Name) || empty(trim($data->Name))) {
                    throw new Exception('Category name is required', 400);
                }

                $category->Name = $data->Name;
                $category->Description = $data->Description ?? '';

                if ($category->create()) {
                    $new_category_id = $conn->insert_id;
                    $created = $category->show($new_category_id)->fetch_assoc();
                    $response = [
                        'status' => 'success',
                        'message' => 'Category created successfully',
                        'code' => 201,
                        'data' => $created
                    ];
                    http_response_code(201);
                } else {
                    throw new Exception('Failed to create category', 500);
                }
            }
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->id)) {
                throw new Exception('Category ID is required', 400);
            }

            $category->id = $data->id;
            $result = $category->show($data->id);
            
            if ($result->num_rows === 0) {
                throw new Exception('Category not found', 404);
            }

            $category_details = $result->fetch_assoc();
            
            if ($category->delete()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Category deleted successfully',
                    'code' => 200,
                    'data' => [
                        'id' => $category_details['id'],
                        'name' => $category_details['Name']
                    ]
                ];
            } else {
                throw new Exception('Cannot delete category with associated products', 400);
            }
            break;

        default:
            throw new Exception('Method Not Allowed', 405);
    }
} catch (Exception $e) {
    $response = [
        'code' => $e->getCode() ?: 400,
        'status_code' => 'FAILED',
        'message' => $e->getMessage()
    ];
    http_response_code($e->getCode() ?: 400);
}

echo json_encode($response);
?>