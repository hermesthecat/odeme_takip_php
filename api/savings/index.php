<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/controllers/SavingController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Initialize controller
$saving = new SavingController();

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch($method) {
    case 'POST':
        switch($action) {
            case 'progress':
                if($id === null) {
                    $response = [
                        'success' => false,
                        'errors' => ['Saving ID is required']
                    ];
                    break;
                }
                $response = $saving->updateProgress($id);
                break;

            default:
                // Create new saving goal
                $response = $saving->create();
        }
        break;

    case 'PUT':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Saving ID is required']
            ];
            break;
        }
        // Get PUT data
        parse_str(file_get_contents("php://input"), $_POST);
        $response = $saving->update($id);
        break;

    case 'DELETE':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Saving ID is required']
            ];
            break;
        }
        $response = $saving->delete($id);
        break;

    case 'GET':
        switch($action) {
            case 'progress':
                if($id === null) {
                    $response = [
                        'success' => false,
                        'errors' => ['Saving ID is required']
                    ];
                    break;
                }
                $response = $saving->getProgress($id);
                break;

            default:
                // Get all savings
                $response = $saving->getAll();
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
