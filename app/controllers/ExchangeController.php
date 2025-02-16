<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/ExchangeRate.php';

class ExchangeController {
    private $db;
    private $exchange;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->exchange = new ExchangeRate($this->db);
    }

    public function getRates() {
        // Check if rates need update
        if($this->exchange->needsUpdate()) {
            $this->exchange->updateRates();
        }

        $rates = $this->exchange->getRates();
        $formatted = $this->exchange->getCurrentRatesFormatted();
        $last_update = $this->exchange->getLastUpdateTime();

        return [
            'success' => true,
            'data' => [
                'rates' => $rates,
                'formatted' => $formatted,
                'last_update' => $last_update ? $last_update->format('Y-m-d H:i:s') : null,
                'base_currency' => DEFAULT_CURRENCY
            ]
        ];
    }

    public function updateRates() {
        // Verify CSRF token if it's not an automated update
        if(!isset($_GET['automated'])) {
            verify_csrf_token();
        }

        // Try to update rates
        if($this->exchange->updateRates()) {
            $rates = $this->exchange->getRates();
            $formatted = $this->exchange->getCurrentRatesFormatted();
            $last_update = $this->exchange->getLastUpdateTime();

            return [
                'success' => true,
                'message' => 'Exchange rates updated successfully',
                'data' => [
                    'rates' => $rates,
                    'formatted' => $formatted,
                    'last_update' => $last_update ? $last_update->format('Y-m-d H:i:s') : null,
                    'base_currency' => DEFAULT_CURRENCY
                ]
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update exchange rates']
        ];
    }

    public function convert() {
        // Get parameters
        $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
        $from = isset($_GET['from']) ? trim($_GET['from']) : '';
        $to = isset($_GET['to']) ? trim($_GET['to']) : '';

        // Validate input
        $errors = [];

        if($amount <= 0) {
            $errors[] = "Amount must be greater than zero";
        }

        if(!$this->exchange->validateCurrency($from)) {
            $errors[] = "Invalid source currency";
        }

        if(!$this->exchange->validateCurrency($to)) {
            $errors[] = "Invalid target currency";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Try to convert
        $converted = $this->exchange->convert($amount, $from, $to);
        if($converted !== null) {
            return [
                'success' => true,
                'data' => [
                    'amount' => $amount,
                    'from' => $from,
                    'to' => $to,
                    'result' => $converted,
                    'formatted' => [
                        'from' => $this->exchange->formatAmount($amount, $from),
                        'to' => $this->exchange->formatAmount($converted, $to)
                    ]
                ]
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to convert currency']
        ];
    }

    public function getFormattedAmount() {
        // Get parameters
        $amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
        $currency = isset($_GET['currency']) ? trim($_GET['currency']) : DEFAULT_CURRENCY;

        // Validate input
        if(!$this->exchange->validateCurrency($currency)) {
            return [
                'success' => false,
                'errors' => ['Invalid currency']
            ];
        }

        return [
            'success' => true,
            'data' => [
                'amount' => $amount,
                'currency' => $currency,
                'formatted' => $this->exchange->formatAmount($amount, $currency),
                'symbol' => $this->exchange->getCurrencySymbol($currency)
            ]
        ];
    }

    public function getSupportedCurrencies() {
        return [
            'success' => true,
            'data' => [
                'currencies' => [
                    'TRY' => [
                        'code' => 'TRY',
                        'name' => 'Turkish Lira',
                        'symbol' => '₺'
                    ],
                    'USD' => [
                        'code' => 'USD',
                        'name' => 'US Dollar',
                        'symbol' => '$'
                    ],
                    'EUR' => [
                        'code' => 'EUR',
                        'name' => 'Euro',
                        'symbol' => '€'
                    ],
                    'GBP' => [
                        'code' => 'GBP',
                        'name' => 'British Pound',
                        'symbol' => '£'
                    ]
                ],
                'default' => DEFAULT_CURRENCY
            ]
        ];
    }

    public function validateRate($currency) {
        $rate = $this->exchange->getRate($currency);
        
        return [
            'success' => true,
            'data' => [
                'currency' => $currency,
                'is_valid' => $rate !== null,
                'rate' => $rate
            ]
        ];
    }

    public function getLastUpdateStatus() {
        $last_update = $this->exchange->getLastUpdateTime();
        $needs_update = $this->exchange->needsUpdate();

        return [
            'success' => true,
            'data' => [
                'last_update' => $last_update ? $last_update->format('Y-m-d H:i:s') : null,
                'needs_update' => $needs_update,
                'next_update' => $last_update ? $last_update->modify('+1 hour')->format('Y-m-d H:i:s') : null
            ]
        ];
    }
}
