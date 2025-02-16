<?php
require_once __DIR__ . '/../config/config.php';

class Saving {
    private $conn;
    private $table = 'savings';

    public $id;
    public $user_id;
    public $name;
    public $target_amount;
    public $current_amount;
    public $currency;
    public $start_date;
    public $target_date;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 (user_id, name, target_amount, current_amount, currency, start_date, target_date) 
                 VALUES 
                 (:user_id, :name, :target_amount, :current_amount, :currency, :start_date, :target_date)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->target_amount = floatval($this->target_amount);
        $this->current_amount = floatval($this->current_amount);
        $this->currency = htmlspecialchars(strip_tags($this->currency));

        // Validate dates
        $start = new DateTime($this->start_date);
        $target = new DateTime($this->target_date);
        if($target <= $start) {
            return false;
        }

        // Validate amounts
        if($this->current_amount > $this->target_amount) {
            return false;
        }

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":target_amount", $this->target_amount);
        $stmt->bindParam(":current_amount", $this->current_amount);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":start_date", $this->start_date);
        $stmt->bindParam(":target_date", $this->target_date);

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
            $this->target_amount = $row['target_amount'];
            $this->current_amount = $row['current_amount'];
            $this->currency = $row['currency'];
            $this->start_date = $row['start_date'];
            $this->target_date = $row['target_date'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        return false;
    }

    public function update() {
        // Only allow updating current_amount and target_date
        $query = "UPDATE " . $this->table . " 
                 SET current_amount = :current_amount,
                     target_date = :target_date 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Validate amounts
        if($this->current_amount > $this->target_amount) {
            return false;
        }

        // Validate dates
        $start = new DateTime($this->start_date);
        $target = new DateTime($this->target_date);
        if($target <= $start) {
            return false;
        }

        // Bind values
        $stmt->bindParam(":current_amount", $this->current_amount);
        $stmt->bindParam(":target_date", $this->target_date);
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
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY target_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function updateProgress($saving_id, $amount) {
        // First get current saving details
        $query = "SELECT current_amount, target_amount FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $saving_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $new_amount = $row['current_amount'] + $amount;
            
            // Check if new amount exceeds target
            if($new_amount > $row['target_amount']) {
                return false;
            }
            
            // Update current amount
            $query = "UPDATE " . $this->table . " 
                     SET current_amount = :current_amount 
                     WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":current_amount", $new_amount);
            $stmt->bindParam(":id", $saving_id);
            $stmt->bindParam(":user_id", $this->user_id);
            
            return $stmt->execute();
        }
        return false;
    }

    public function calculateProgress($saving_id) {
        $query = "SELECT current_amount, target_amount, start_date, target_date 
                 FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $saving_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $amount_progress = ($row['current_amount'] / $row['target_amount']) * 100;
            
            $start = new DateTime($row['start_date']);
            $target = new DateTime($row['target_date']);
            $now = new DateTime();
            
            $total_days = $start->diff($target)->days;
            $elapsed_days = $start->diff($now)->days;
            $time_progress = ($elapsed_days / $total_days) * 100;
            
            return [
                'amount_progress' => min(100, $amount_progress),
                'time_progress' => min(100, $time_progress),
                'is_on_track' => $amount_progress >= $time_progress,
                'remaining_amount' => $row['target_amount'] - $row['current_amount'],
                'remaining_days' => $target->diff($now)->days
            ];
        }
        return null;
    }

    public function convertToDefaultCurrency($amount, $currency) {
        if($currency === DEFAULT_CURRENCY) {
            return $amount;
        }

        $query = "SELECT rate FROM exchange_rates WHERE currency = :currency";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":currency", $currency);
        $stmt->execute();
        
        if($rate = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $amount / $rate['rate'];
        }
        return null;
    }

    public function getTotalSavings() {
        $query = "SELECT current_amount, currency FROM " . $this->table . " 
                 WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        $total = 0;
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $amount_in_try = $this->convertToDefaultCurrency(
                $row['current_amount'], 
                $row['currency']
            );
            if($amount_in_try !== null) {
                $total += $amount_in_try;
            }
        }
        
        return $total;
    }
}
