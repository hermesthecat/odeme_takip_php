<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/ExchangeRate.php';
require_once __DIR__ . '/../models/BudgetGoal.php';

class ExpenseController {
    private $db;
    private $expense;
    private $exchange;
    private $budget;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->expense = new Expense($this->db);
        $this->exchange = new ExchangeRate($this->db);
        $this->budget = new BudgetGoal($this->db);
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
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $first_payment_date = isset($_POST['first_payment_date']) ? trim($_POST['first_payment_date']) : '';
        $frequency = isset($_POST['frequency']) ? intval($_POST['frequency']) : 0;

        // Validate input
        $errors = [];

        if(empty($name)) {
            $errors[] = "Expense name is required";
        }

        if($amount <= 0) {
            $errors[] = "Amount must be greater than zero";
        }

        if(!$this->exchange->validateCurrency($currency)) {
            $errors[] = "Invalid currency";
        }

        if(empty($category)) {
            $errors[] = "Category is required";
        }

        if(empty($first_payment_date)) {
            $errors[] = "First payment date is required";
        } elseif(!strtotime($first_payment_date)) {
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

        // Check budget limits
        $this->budget->user_id = $_SESSION['user_id'];
        $this->budget->getCurrentBudget();

        // Convert amount to TRY for budget check
        $amount_in_try = $this->exchange->convert($amount, $currency, DEFAULT_CURRENCY);
        $month = date('m', strtotime($first_payment_date));
        $year = date('Y', strtotime($first_payment_date));

        // Get current month's expenses
        $this->expense->user_id = $_SESSION['user_id'];
        $current_expenses = $this->expense->getMonthlyExpenses($year, $month);
        $expenses_data = [];
        while($row = $current_expenses->fetch(PDO::FETCH_ASSOC)) {
            $expenses_data[] = [
                'amount' => $this->exchange->convert($row['amount'], $row['currency'], DEFAULT_CURRENCY),
                'category' => $row['category']
            ];
        }
        
        // Add new expense to the list
        $expenses_data[] = [
            'amount' => $amount_in_try,
            'category' => $category
        ];

        // Check if this would exceed any limits
        $budget_check = $this->budget->checkLimits($expenses_data);
        if($budget_check['exceeded']) {
            $warning = $budget_check['type'] === 'overall' ?
                      'This expense would exceed your monthly budget limit.' :
                      'This expense would exceed your category budget limit.';

            return [
                'success' => false,
                'warning' => $warning,
                'budget_check' => $budget_check,
                'require_confirmation' => true
            ];
        }

        // Set expense properties
        $this->expense->user_id = $_SESSION['user_id'];
        $this->expense->name = $name;
        $this->expense->amount = $amount;
        $this->expense->currency = $currency;
        $this->expense->category = $category;
        $this->expense->first_payment_date = $first_payment_date;
        $this->expense->frequency = $frequency;

        // Try to create the expense
        if($this->expense->create()) {
            return [
                'success' => true,
                'message' => 'Expense added successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to add expense']
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
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $first_payment_date = isset($_POST['first_payment_date']) ? trim($_POST['first_payment_date']) : '';
        $frequency = isset($_POST['frequency']) ? intval($_POST['frequency']) : 0;

        // Validate input
        $errors = [];

        if(empty($name)) {
            $errors[] = "Expense name is required";
        }

        if($amount <= 0) {
            $errors[] = "Amount must be greater than zero";
        }

        if(!$this->exchange->validateCurrency($currency)) {
            $errors[] = "Invalid currency";
        }

        if(empty($category)) {
            $errors[] = "Category is required";
        }

        if(empty($first_payment_date)) {
            $errors[] = "First payment date is required";
        } elseif(!strtotime($first_payment_date)) {
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

        // Set expense properties
        $this->expense->id = $id;
        $this->expense->user_id = $_SESSION['user_id'];
        $this->expense->name = $name;
        $this->expense->amount = $amount;
        $this->expense->currency = $currency;
        $this->expense->category = $category;
        $this->expense->first_payment_date = $first_payment_date;
        $this->expense->frequency = $frequency;

        // Try to update the expense
        if($this->expense->update()) {
            return [
                'success' => true,
                'message' => 'Expense updated successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update expense']
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

        $this->expense->user_id = $_SESSION['user_id'];
        
        // Try to delete the expense
        if($this->expense->delete($id)) {
            return [
                'success' => true,
                'message' => 'Expense deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to delete expense']
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

        $this->expense->user_id = $_SESSION['user_id'];
        $result = $this->expense->getAll();
        
        $expenses = [];
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $next_date = $this->expense->getNextPaymentDate($row['id']);
            $row['next_payment_date'] = $next_date;
            $row['amount_formatted'] = $this->exchange->formatAmount($row['amount'], $row['currency']);
            $expenses[] = $row;
        }

        return [
            'success' => true,
            'data' => $expenses
        ];
    }

    public function getMonthlyExpenses($year, $month) {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->expense->user_id = $_SESSION['user_id'];
        
        $total = $this->expense->calculateTotalExpense($year, $month);
        $expenses = [];
        $categories = [];
        
        $result = $this->expense->getMonthlyExpenses($year, $month);
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row['amount_formatted'] = $this->exchange->formatAmount($row['amount'], $row['currency']);
            $expenses[] = $row;

            // Group by category
            if(!isset($categories[$row['category']])) {
                $categories[$row['category']] = 0;
            }
            $categories[$row['category']] += $this->exchange->convert(
                $row['amount'],
                $row['currency'],
                DEFAULT_CURRENCY
            );
        }

        // Format category totals
        foreach($categories as $category => $amount) {
            $categories[$category] = [
                'amount' => $amount,
                'formatted' => $this->exchange->formatAmount($amount, DEFAULT_CURRENCY)
            ];
        }

        return [
            'success' => true,
            'data' => [
                'total' => $total,
                'total_formatted' => $this->exchange->formatAmount($total, DEFAULT_CURRENCY),
                'expenses' => $expenses,
                'categories' => $categories
            ]
        ];
    }

    public function getByCategory($category) {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->expense->user_id = $_SESSION['user_id'];
        $result = $this->expense->getByCategory($category);
        
        $expenses = [];
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row['amount_formatted'] = $this->exchange->formatAmount($row['amount'], $row['currency']);
            $expenses[] = $row;
        }

        return [
            'success' => true,
            'data' => $expenses
        ];
    }
}
