<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/controllers/BudgetController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Initialize controller
$budget = new BudgetController();

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch($method) {
    case 'POST':
        if($action === 'check') {
            // Check budget limit
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $currency = isset($_POST['currency']) ? trim($_POST['currency']) : DEFAULT_CURRENCY;
            $category = isset($_POST['category']) ? trim($_POST['category']) : null;
            
            $response = $budget->checkLimit($amount, $currency, $category);
        } else {
            // Create new budget
            $response = $budget->create();
        }
        break;

    case 'PUT':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Budget ID is required']
            ];
            break;
        }
        // Get PUT data
        parse_str(file_get_contents("php://input"), $_POST);
        $response = $budget->update($id);
        break;

    case 'DELETE':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Budget ID is required']
            ];
            break;
        }
        $response = $budget->delete($id);
        break;

    case 'GET':
        if($action === 'current') {
            // Get current budget status
            $response = $budget->getCurrentBudget();
        } else {
            // Invalid action
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
