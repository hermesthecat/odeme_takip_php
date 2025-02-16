<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Saving.php';
require_once __DIR__ . '/../models/ExchangeRate.php';

class SavingController {
    private $db;
    private $saving;
    private $exchange;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->saving = new Saving($this->db);
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
        $target_amount = isset($_POST['target_amount']) ? floatval($_POST['target_amount']) : 0;
        $current_amount = isset($_POST['current_amount']) ? floatval($_POST['current_amount']) : 0;
        $currency = isset($_POST['currency']) ? trim($_POST['currency']) : DEFAULT_CURRENCY;
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $target_date = isset($_POST['target_date']) ? trim($_POST['target_date']) : '';

        // Validate input
        $errors = [];

        if(empty($name)) {
            $errors[] = "Goal name is required";
        }

        if($target_amount <= 0) {
            $errors[] = "Target amount must be greater than zero";
        }

        if($current_amount < 0 || $current_amount > $target_amount) {
            $errors[] = "Current amount must be between 0 and target amount";
        }

        if(!$this->exchange->validateCurrency($currency)) {
            $errors[] = "Invalid currency";
        }

        if(empty($start_date)) {
            $errors[] = "Start date is required";
        } elseif(!strtotime($start_date)) {
            $errors[] = "Invalid start date format";
        }

        if(empty($target_date)) {
            $errors[] = "Target date is required";
        } elseif(!strtotime($target_date)) {
            $errors[] = "Invalid target date format";
        }

        if(strtotime($target_date) <= strtotime($start_date)) {
            $errors[] = "Target date must be after start date";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Set saving properties
        $this->saving->user_id = $_SESSION['user_id'];
        $this->saving->name = $name;
        $this->saving->target_amount = $target_amount;
        $this->saving->current_amount = $current_amount;
        $this->saving->currency = $currency;
        $this->saving->start_date = $start_date;
        $this->saving->target_date = $target_date;

        // Try to create the saving goal
        if($this->saving->create()) {
            return [
                'success' => true,
                'message' => 'Saving goal created successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to create saving goal']
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
        $current_amount = isset($_POST['current_amount']) ? floatval($_POST['current_amount']) : null;
        $target_date = isset($_POST['target_date']) ? trim($_POST['target_date']) : '';

        // Validate input
        $errors = [];

        // First get the current saving goal
        $this->saving->user_id = $_SESSION['user_id'];
        if(!$this->saving->read($id)) {
            return [
                'success' => false,
                'errors' => ['Saving goal not found']
            ];
        }

        if($current_amount !== null) {
            if($current_amount < 0 || $current_amount > $this->saving->target_amount) {
                $errors[] = "Current amount must be between 0 and target amount";
            }
            $this->saving->current_amount = $current_amount;
        }

        if(!empty($target_date)) {
            if(!strtotime($target_date)) {
                $errors[] = "Invalid target date format";
            } elseif(strtotime($target_date) <= strtotime($this->saving->start_date)) {
                $errors[] = "Target date must be after start date";
            }
            $this->saving->target_date = $target_date;
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Try to update the saving goal
        if($this->saving->update()) {
            return [
                'success' => true,
                'message' => 'Saving goal updated successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update saving goal']
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

        $this->saving->user_id = $_SESSION['user_id'];
        
        // Try to delete the saving goal
        if($this->saving->delete($id)) {
            return [
                'success' => true,
                'message' => 'Saving goal deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to delete saving goal']
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

        $this->saving->user_id = $_SESSION['user_id'];
        $result = $this->saving->getAll();
        
        $savings = [];
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $progress = $this->saving->calculateProgress($row['id']);
            $row['progress'] = $progress;
            $row['target_amount_formatted'] = $this->exchange->formatAmount($row['target_amount'], $row['currency']);
            $row['current_amount_formatted'] = $this->exchange->formatAmount($row['current_amount'], $row['currency']);
            $row['remaining_amount_formatted'] = $this->exchange->formatAmount($progress['remaining_amount'], $row['currency']);
            $savings[] = $row;
        }

        $total_savings = $this->saving->getTotalSavings();

        return [
            'success' => true,
            'data' => [
                'savings' => $savings,
                'total_savings' => $total_savings,
                'total_savings_formatted' => $this->exchange->formatAmount($total_savings, DEFAULT_CURRENCY)
            ]
        ];
    }

    public function updateProgress($id) {
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
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

        // Validate input
        if($amount <= 0) {
            return [
                'success' => false,
                'errors' => ['Amount must be greater than zero']
            ];
        }

        $this->saving->user_id = $_SESSION['user_id'];
        
        // Try to update the progress
        if($this->saving->updateProgress($id, $amount)) {
            $progress = $this->saving->calculateProgress($id);
            
            return [
                'success' => true,
                'message' => 'Progress updated successfully',
                'data' => $progress
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update progress']
        ];
    }

    public function getProgress($id) {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->saving->user_id = $_SESSION['user_id'];
        $progress = $this->saving->calculateProgress($id);

        if($progress) {
            return [
                'success' => true,
                'data' => $progress
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to get progress']
        ];
    }
}
