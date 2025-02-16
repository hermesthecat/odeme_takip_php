<?php
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

switch($method) {
    case 'POST':
        switch($action) {
            case 'register':
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
                    'errors' => ['Invalid action']
                ];
        }
        break;

    case 'GET':
        switch($action) {
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
                    'errors' => ['Invalid action']
                ];
        }
        break;

    default:
        $response = [
            'success' => false,
            'errors' => ['Invalid request method']
        ];
}

// Send response
http_response_code($response['success'] ? 200 : 400);
echo json_encode($response);
