<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/controllers/ExchangeController.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Initialize controller
$exchange = new ExchangeController();

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch($method) {
    case 'GET':
        switch($action) {
            case 'rates':
                // Get current exchange rates
                $response = $exchange->getRates();
                break;

            case 'update':
                // Update exchange rates
                $response = $exchange->updateRates();
                break;

            case 'convert':
                // Convert amount between currencies
                $response = $exchange->convert();
                break;

            case 'format':
                // Format amount with currency
                $response = $exchange->getFormattedAmount();
                break;

            case 'currencies':
                // Get supported currencies
                $response = $exchange->getSupportedCurrencies();
                break;

            case 'validate':
                // Validate currency and get rate
                $currency = isset($_GET['currency']) ? trim($_GET['currency']) : '';
                if(empty($currency)) {
                    $response = [
                        'success' => false,
                        'errors' => ['Currency is required']
                    ];
                    break;
                }
                $response = $exchange->validateRate($currency);
                break;

            case 'status':
                // Get exchange rate update status
                $response = $exchange->getLastUpdateStatus();
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
