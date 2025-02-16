<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/controllers/IncomeController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Initialize controller
$income = new IncomeController();

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;

switch($method) {
    case 'POST':
        $response = $income->create();
        break;

    case 'PUT':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Income ID is required']
            ];
            break;
        }
        // Get PUT data
        parse_str(file_get_contents("php://input"), $_POST);
        $response = $income->update($id);
        break;

    case 'DELETE':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Income ID is required']
            ];
            break;
        }
        $response = $income->delete($id);
        break;

    case 'GET':
        if($year !== null && $month !== null) {
            // Get monthly incomes
            $response = $income->getMonthlyIncome($year, $month);
        } else {
            // Get all incomes
            $response = $income->getAll();
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
