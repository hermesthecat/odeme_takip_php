<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/controllers/ExpenseController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Initialize controller
$expense = new ExpenseController();

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$category = isset($_GET['category']) ? trim($_GET['category']) : null;

switch($method) {
    case 'POST':
        $response = $expense->create();
        break;

    case 'PUT':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Expense ID is required']
            ];
            break;
        }
        // Get PUT data
        parse_str(file_get_contents("php://input"), $_POST);
        $response = $expense->update($id);
        break;

    case 'DELETE':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Expense ID is required']
            ];
            break;
        }
        $response = $expense->delete($id);
        break;

    case 'GET':
        if($year !== null && $month !== null) {
            // Get monthly expenses
            $response = $expense->getMonthlyExpenses($year, $month);
        } elseif($category !== null) {
            // Get expenses by category
            $response = $expense->getByCategory($category);
        } else {
            // Get all expenses
            $response = $expense->getAll();
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
