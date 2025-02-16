<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Income.php';
require_once __DIR__ . '/../models/ExchangeRate.php';

class IncomeController {
    private $db;
    private $income;
    private $exchange;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->income = new Income($this->db);
        $this->exchange = new ExchangeRate($this->db);
    }

    public function create() {
        // Verify CSRF token
        verify_csrf_token();

        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        // Get POST data
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $currency = isset($_POST['currency']) ? trim($_POST['currency']) : DEFAULT_CURRENCY;
        $first_income_date = isset($_POST['first_income_date']) ? trim($_POST['first_income_date']) : '';
        $frequency = isset($_POST['frequency']) ? intval($_POST['frequency']) : 0;

        // Validate input
        $errors = [];

        if(empty($name)) {
            $errors[] = "Income name is required";
        }

        if($amount <= 0) {
            $errors[] = "Amount must be greater than zero";
        }

        if(!$this->exchange->validateCurrency($currency)) {
            $errors[] = "Invalid currency";
        }

        if(empty($first_income_date)) {
            $errors[] = "First income date is required";
        } elseif(!strtotime($first_income_date)) {
            $errors[] = "Invalid date format";
        }

        if($frequency < 0 || $frequency > 12) {
            $errors[] = "Invalid frequency";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Set income properties
        $this->income->user_id = $_SESSION['user_id'];
        $this->income->name = $name;
        $this->income->amount = $amount;
        $this->income->currency = $currency;
        $this->income->first_income_date = $first_income_date;
        $this->income->frequency = $frequency;

        // Try to create the income
        if($this->income->create()) {
            return [
                'success' => true,
                'message' => 'Income added successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to add income']
        ];
    }

    public function update($id) {
        // Verify CSRF token
        verify_csrf_token();

        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        // Get POST data
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $currency = isset($_POST['currency']) ? trim($_POST['currency']) : DEFAULT_CURRENCY;
        $first_income_date = isset($_POST['first_income_date']) ? trim($_POST['first_income_date']) : '';
        $frequency = isset($_POST['frequency']) ? intval($_POST['frequency']) : 0;

        // Validate input
        $errors = [];

        if(empty($name)) {
            $errors[] = "Income name is required";
        }

        if($amount <= 0) {
            $errors[] = "Amount must be greater than zero";
        }

        if(!$this->exchange->validateCurrency($currency)) {
            $errors[] = "Invalid currency";
        }

        if(empty($first_income_date)) {
            $errors[] = "First income date is required";
        } elseif(!strtotime($first_income_date)) {
            $errors[] = "Invalid date format";
        }

        if($frequency < 0 || $frequency > 12) {
            $errors[] = "Invalid frequency";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Set income properties
        $this->income->id = $id;
        $this->income->user_id = $_SESSION['user_id'];
        $this->income->name = $name;
        $this->income->amount = $amount;
        $this->income->currency = $currency;
        $this->income->first_income_date = $first_income_date;
        $this->income->frequency = $frequency;

        // Try to update the income
        if($this->income->update()) {
            return [
                'success' => true,
                'message' => 'Income updated successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update income']
        ];
    }

    public function delete($id) {
        // Verify CSRF token
        verify_csrf_token();

        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->income->user_id = $_SESSION['user_id'];
        
        // Try to delete the income
        if($this->income->delete($id)) {
            return [
                'success' => true,
                'message' => 'Income deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to delete income']
        ];
    }

    public function getAll() {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->income->user_id = $_SESSION['user_id'];
        $result = $this->income->getAll();
        
        $incomes = [];
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $next_date = $this->income->getNextIncomeDate($row['id']);
            $row['next_income_date'] = $next_date;
            $row['amount_formatted'] = $this->exchange->formatAmount($row['amount'], $row['currency']);
            $incomes[] = $row;
        }

        return [
            'success' => true,
            'data' => $incomes
        ];
    }

    public function getMonthlyIncome($year, $month) {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->income->user_id = $_SESSION['user_id'];
        
        $total = $this->income->calculateTotalIncome($year, $month);
        $incomes = [];
        
        $result = $this->income->getMonthlyIncomes($year, $month);
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row['amount_formatted'] = $this->exchange->formatAmount($row['amount'], $row['currency']);
            $incomes[] = $row;
        }

        return [
            'success' => true,
            'data' => [
                'total' => $total,
                'total_formatted' => $this->exchange->formatAmount($total, DEFAULT_CURRENCY),
                'incomes' => $incomes
            ]
        ];
    }
}
