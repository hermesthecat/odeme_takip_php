<?php
require_once __DIR__ . '/../config/config.php';

class Expense {
    private $conn;
    private $table = 'expenses';

    public $id;
    public $user_id;
    public $name;
    public $amount;
    public $currency;
    public $category;
    public $first_payment_date;
    public $frequency;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 (user_id, name, amount, currency, category, first_payment_date, frequency) 
                 VALUES 
                 (:user_id, :name, :amount, :currency, :category, :first_payment_date, :frequency)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->amount = floatval($this->amount);
        $this->currency = htmlspecialchars(strip_tags($this->currency));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->frequency = intval($this->frequency);

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":first_payment_date", $this->first_payment_date);
        $stmt->bindParam(":frequency", $this->frequency);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->amount = $row['amount'];
            $this->currency = $row['currency'];
            $this->category = $row['category'];
            $this->first_payment_date = $row['first_payment_date'];
            $this->frequency = $row['frequency'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET name = :name, 
                     amount = :amount, 
                     currency = :currency, 
                     category = :category,
                     first_payment_date = :first_payment_date, 
                     frequency = :frequency 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->amount = floatval($this->amount);
        $this->currency = htmlspecialchars(strip_tags($this->currency));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->frequency = intval($this->frequency);

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":first_payment_date", $this->first_payment_date);
        $stmt->bindParam(":frequency", $this->frequency);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY first_payment_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function getByCategory($category) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE user_id = :user_id AND category = :category 
                 ORDER BY first_payment_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":category", $category);
        $stmt->execute();

        return $stmt;
    }

    public function getMonthlyExpenses($year, $month) {
        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01"));

        $query = "SELECT * FROM " . $this->table . " 
                 WHERE user_id = :user_id 
                 AND (
                     (frequency = 0 AND first_payment_date BETWEEN :start_date AND :end_date)
                     OR (
                         frequency > 0 
                         AND first_payment_date <= :end_date 
                         AND (
                             TIMESTAMPDIFF(MONTH, first_payment_date, :start_date) % frequency = 0
                             OR TIMESTAMPDIFF(MONTH, first_payment_date, :end_date) % frequency = 0
                         )
                     )
                 )";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();

        return $stmt;
    }

    public function calculateTotalExpense($year, $month) {
        $expenses = $this->getMonthlyExpenses($year, $month);
        $total = 0;

        while($row = $expenses->fetch(PDO::FETCH_ASSOC)) {
            // Convert to default currency (TRY)
            $query = "SELECT rate FROM exchange_rates WHERE currency = :currency";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":currency", $row['currency']);
            $stmt->execute();
            $rate = $stmt->fetch(PDO::FETCH_ASSOC)['rate'];

            $total += $row['amount'] / $rate;
        }

        return $total;
    }

    public function calculateCategoryExpenses($year, $month) {
        $expenses = $this->getMonthlyExpenses($year, $month);
        $categories = [];

        while($row = $expenses->fetch(PDO::FETCH_ASSOC)) {
            // Convert to default currency (TRY)
            $query = "SELECT rate FROM exchange_rates WHERE currency = :currency";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":currency", $row['currency']);
            $stmt->execute();
            $rate = $stmt->fetch(PDO::FETCH_ASSOC)['rate'];

            $amount = $row['amount'] / $rate;
            
            if(!isset($categories[$row['category']])) {
                $categories[$row['category']] = 0;
            }
            $categories[$row['category']] += $amount;
        }

        return $categories;
    }

    public function getNextPaymentDate($expense_id) {
        $query = "SELECT first_payment_date, frequency FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $expense_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $first_date = new DateTime($row['first_payment_date']);
            $frequency = $row['frequency'];
            
            if($frequency == 0) {
                return $first_date->format('Y-m-d');
            }

            $today = new DateTime();
            $next_date = clone $first_date;

            while($next_date <= $today) {
                $next_date->modify("+{$frequency} months");
            }

            return $next_date->format('Y-m-d');
        }

        return null;
    }

    public function checkBudgetLimit($category_id = null) {
        $year = date('Y');
        $month = date('m');
        $total = $this->calculateTotalExpense($year, $month);

        // Check overall monthly limit
        $query = "SELECT monthly_limit FROM budget_goals WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $limit = $row['monthly_limit'];
            if($total >= $limit) {
                return [
                    'exceeded' => true,
                    'type' => 'overall',
                    'current' => $total,
                    'limit' => $limit
                ];
            }
        }

        // Check category limit if specified
        if($category_id) {
            $categories = $this->calculateCategoryExpenses($year, $month);
            
            $query = "SELECT limit_amount FROM budget_categories WHERE id = :category_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->execute();
            
            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $category_limit = $row['limit_amount'];
                $category_total = isset($categories[$category_id]) ? $categories[$category_id] : 0;
                
                if($category_total >= $category_limit) {
                    return [
                        'exceeded' => true,
                        'type' => 'category',
                        'category_id' => $category_id,
                        'current' => $category_total,
                        'limit' => $category_limit
                    ];
                }
            }
        }

        return ['exceeded' => false];
    }
}
