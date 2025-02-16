<?php
require_once __DIR__ . '/../config/config.php';

class Income {
    private $conn;
    private $table = 'incomes';

    public $id;
    public $user_id;
    public $name;
    public $amount;
    public $currency;
    public $first_income_date;
    public $frequency;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 (user_id, name, amount, currency, first_income_date, frequency) 
                 VALUES 
                 (:user_id, :name, :amount, :currency, :first_income_date, :frequency)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->amount = floatval($this->amount);
        $this->currency = htmlspecialchars(strip_tags($this->currency));
        $this->frequency = intval($this->frequency);

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":first_income_date", $this->first_income_date);
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
            $this->first_income_date = $row['first_income_date'];
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
                     first_income_date = :first_income_date, 
                     frequency = :frequency 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->amount = floatval($this->amount);
        $this->currency = htmlspecialchars(strip_tags($this->currency));
        $this->frequency = intval($this->frequency);

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":first_income_date", $this->first_income_date);
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
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY first_income_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function getMonthlyIncomes($year, $month) {
        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01"));

        $query = "SELECT * FROM " . $this->table . " 
                 WHERE user_id = :user_id 
                 AND (
                     (frequency = 0 AND first_income_date BETWEEN :start_date AND :end_date)
                     OR (
                         frequency > 0 
                         AND first_income_date <= :end_date 
                         AND (
                             TIMESTAMPDIFF(MONTH, first_income_date, :start_date) % frequency = 0
                             OR TIMESTAMPDIFF(MONTH, first_income_date, :end_date) % frequency = 0
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

    public function calculateTotalIncome($year, $month) {
        $incomes = $this->getMonthlyIncomes($year, $month);
        $total = 0;

        while($row = $incomes->fetch(PDO::FETCH_ASSOC)) {
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

    public function getNextIncomeDate($income_id) {
        $query = "SELECT first_income_date, frequency FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $income_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $first_date = new DateTime($row['first_income_date']);
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
}
