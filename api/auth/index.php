<?php
session_start();
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Initialize controller
$auth = new AuthController();

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Verify CSRF token for POST requests
if ($method === 'POST' && $action !== 'login') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $response = [
            'success' => false,
            'errors' => ['Invalid CSRF token']
        ];
        http_response_code(403);
        echo json_encode($response);
        exit;
    }
}

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'register':
                // Validate required fields
                $required = ['username', 'password', 'confirm_password'];
                $errors = [];

                foreach ($required as $field) {
                    if (!isset($_POST[$field]) || empty($_POST[$field])) {
                        $errors[] = ucfirst($field) . ' is required';
                    }
                }

                if (!empty($errors)) {
                    $response = [
                        'success' => false,
                        'errors' => $errors
                    ];
                    break;
                }

                // Validate password match
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $response = [
                        'success' => false,
                        'errors' => ['Passwords do not match']
                    ];
                    break;
                }

                $response = $auth->register();
                break;

            case 'login':
                $response = $auth->login();
                break;

            case 'update':
                $response = $auth->updateProfile();
                break;

            case 'delete':
                $response = $auth->deleteAccount();
                break;

            default:
                $response = [
                    'success' => false,
                    'errors' => ['Invalid Post Auth Action']
                ];
        }
        break;

    case 'GET':
        switch ($action) {
            case 'status':
                $response = [
                    'success' => true,
                    'data' => [
                        'isLoggedIn' => $auth->isLoggedIn(),
                        'user' => $auth->getCurrentUser()
                    ]
                ];
                break;

            case 'logout':
                $response = $auth->logout();
                break;

            default:
                $response = [
                    'success' => false,
                    'errors' => ['Invalid Get Auth Action']
                ];
        }
        break;

    default:
        $response = [
            'success' => false,
            'errors' => ['Invalid Auth Request Method']
        ];
}

// Send response
http_response_code($response['success'] ? 200 : 400);
echo json_encode($response);
