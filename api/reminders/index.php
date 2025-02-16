<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/controllers/ReminderController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Initialize controller
$reminder = new ReminderController();

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;

switch($method) {
    case 'POST':
        // Create new reminder
        $response = $reminder->create();
        break;

    case 'PUT':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Reminder ID is required']
            ];
            break;
        }
        // Get PUT data
        parse_str(file_get_contents("php://input"), $_POST);
        $response = $reminder->update($id);
        break;

    case 'DELETE':
        if($id === null) {
            $response = [
                'success' => false,
                'errors' => ['Reminder ID is required']
            ];
            break;
        }
        $response = $reminder->delete($id);
        break;

    case 'GET':
        switch($action) {
            case 'upcoming':
                // Get upcoming reminders
                $response = $reminder->getUpcoming();
                break;

            case 'due-today':
                // Get reminders due today
                $response = $reminder->getDueToday();
                break;

            case 'status':
                if($id === null) {
                    $response = [
                        'success' => false,
                        'errors' => ['Reminder ID is required']
                    ];
                    break;
                }
                // Get reminder status
                $response = $reminder->getReminderStatus($id);
                break;

            case 'calendar':
                if($start_date === null || $end_date === null) {
                    $response = [
                        'success' => false,
                        'errors' => ['Start date and end date are required']
                    ];
                    break;
                }
                // Get calendar events
                $response = $reminder->getCalendarEvents($start_date, $end_date);
                break;

            default:
                // Get all reminders
                $response = $reminder->getAll();
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
