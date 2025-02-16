<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/BudgetGoal.php';
require_once __DIR__ . '/../models/ExchangeRate.php';
require_once __DIR__ . '/../models/Expense.php';

class BudgetController {
    private $db;
    private $budget;
    private $exchange;
    private $expense;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->budget = new BudgetGoal($this->db);
        $this->exchange = new ExchangeRate($this->db);
        $this->expense = new Expense($this->db);
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
        $monthly_limit = isset($_POST['monthly_limit']) ? floatval($_POST['monthly_limit']) : 0;
        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];

        // Validate input
        $errors = [];

        if($monthly_limit <= 0) {
            $errors[] = "Monthly limit must be greater than zero";
        }

        foreach($categories as $category) {
            if(!isset($category['name']) || empty($category['name'])) {
                $errors[] = "Category name is required";
                continue;
            }
            if(!isset($category['limit']) || floatval($category['limit']) <= 0) {
                $errors[] = "Category limit must be greater than zero";
                continue;
            }
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Set budget properties
        $this->budget->user_id = $_SESSION['user_id'];
        $this->budget->monthly_limit = $monthly_limit;
        $this->budget->categories = $categories;

        // Try to create the budget
        if($this->budget->create()) {
            return [
                'success' => true,
                'message' => 'Budget created successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to create budget']
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
        $monthly_limit = isset($_POST['monthly_limit']) ? floatval($_POST['monthly_limit']) : 0;
        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];

        // Validate input
        $errors = [];

        if($monthly_limit <= 0) {
            $errors[] = "Monthly limit must be greater than zero";
        }

        foreach($categories as $category) {
            if(!isset($category['name']) || empty($category['name'])) {
                $errors[] = "Category name is required";
                continue;
            }
            if(!isset($category['limit']) || floatval($category['limit']) <= 0) {
                $errors[] = "Category limit must be greater than zero";
                continue;
            }
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Set budget properties
        $this->budget->id = $id;
        $this->budget->user_id = $_SESSION['user_id'];
        $this->budget->monthly_limit = $monthly_limit;
        $this->budget->categories = $categories;

        // Try to update the budget
        if($this->budget->update()) {
            return [
                'success' => true,
                'message' => 'Budget updated successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update budget']
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

        $this->budget->user_id = $_SESSION['user_id'];
        
        // Try to delete the budget
        if($this->budget->delete($id)) {
            return [
                'success' => true,
                'message' => 'Budget deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to delete budget']
        ];
    }

    public function getCurrentBudget() {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->budget->user_id = $_SESSION['user_id'];
        
        if($this->budget->getCurrentBudget()) {
            // Get current month's expenses
            $this->expense->user_id = $_SESSION['user_id'];
            $month = date('m');
            $year = date('Y');
            
            $total_expense = $this->expense->calculateTotalExpense($year, $month);
            $category_expenses = $this->expense->calculateCategoryExpenses($year, $month);

            // Calculate remaining amounts
            $remaining = $this->budget->monthly_limit - $total_expense;
            $category_remaining = [];
            
            foreach($this->budget->categories as $category) {
                $spent = isset($category_expenses[$category['name']]) ? $category_expenses[$category['name']] : 0;
                $category_remaining[$category['name']] = [
                    'limit' => $category['limit'],
                    'spent' => $spent,
                    'remaining' => $category['limit'] - $spent,
                    'limit_formatted' => $this->exchange->formatAmount($category['limit'], DEFAULT_CURRENCY),
                    'spent_formatted' => $this->exchange->formatAmount($spent, DEFAULT_CURRENCY),
                    'remaining_formatted' => $this->exchange->formatAmount($category['limit'] - $spent, DEFAULT_CURRENCY),
                    'percentage' => $category['limit'] > 0 ? ($spent / $category['limit']) * 100 : 0
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'monthly_limit' => $this->budget->monthly_limit,
                    'monthly_limit_formatted' => $this->exchange->formatAmount($this->budget->monthly_limit, DEFAULT_CURRENCY),
                    'total_expense' => $total_expense,
                    'total_expense_formatted' => $this->exchange->formatAmount($total_expense, DEFAULT_CURRENCY),
                    'remaining' => $remaining,
                    'remaining_formatted' => $this->exchange->formatAmount($remaining, DEFAULT_CURRENCY),
                    'percentage' => $this->budget->monthly_limit > 0 ? ($total_expense / $this->budget->monthly_limit) * 100 : 0,
                    'categories' => $category_remaining
                ]
            ];
        }

        return [
            'success' => false,
            'errors' => ['No budget found']
        ];
    }

    public function checkLimit($amount, $currency, $category = null) {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        // Convert amount to default currency
        $amount_in_default = $this->exchange->convert($amount, $currency, DEFAULT_CURRENCY);
        if($amount_in_default === null) {
            return [
                'success' => false,
                'errors' => ['Invalid currency']
            ];
        }

        $this->budget->user_id = $_SESSION['user_id'];
        
        if($this->budget->getCurrentBudget()) {
            $this->expense->user_id = $_SESSION['user_id'];
            $month = date('m');
            $year = date('Y');
            
            $current_expenses = $this->expense->getMonthlyExpenses($year, $month);
            $expenses_data = [];
            
            while($row = $current_expenses->fetch(PDO::FETCH_ASSOC)) {
                $expenses_data[] = [
                    'amount' => $this->exchange->convert($row['amount'], $row['currency'], DEFAULT_CURRENCY),
                    'category' => $row['category']
                ];
            }

            // Add the new expense
            $expenses_data[] = [
                'amount' => $amount_in_default,
                'category' => $category
            ];

            $limit_check = $this->budget->checkLimits($expenses_data);
            return [
                'success' => true,
                'data' => $limit_check
            ];
        }

        return [
            'success' => false,
            'errors' => ['No budget found']
        ];
    }
}
