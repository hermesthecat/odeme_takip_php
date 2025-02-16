<?php
require_once __DIR__ . '/../config/config.php';

class BudgetGoal {
    private $conn;
    private $table = 'budget_goals';
    private $categories_table = 'budget_categories';

    public $id;
    public $user_id;
    public $monthly_limit;
    public $created_at;
    public $categories = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $this->conn->beginTransaction();

            // Insert main budget goal
            $query = "INSERT INTO " . $this->table . " 
                     (user_id, monthly_limit) 
                     VALUES 
                     (:user_id, :monthly_limit)";

            $stmt = $this->conn->prepare($query);

            // Sanitize and validate input
            $this->monthly_limit = floatval($this->monthly_limit);
            if($this->monthly_limit <= 0) {
                return false;
            }

            // Bind values
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":monthly_limit", $this->monthly_limit);

            if(!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            $goal_id = $this->conn->lastInsertId();

            // Insert categories if provided
            if(!empty($this->categories)) {
                $query = "INSERT INTO " . $this->categories_table . "
                         (goal_id, name, limit_amount) VALUES 
                         (:goal_id, :name, :limit_amount)";
                
                $stmt = $this->conn->prepare($query);

                foreach($this->categories as $category) {
                    $name = htmlspecialchars(strip_tags($category['name']));
                    $limit = floatval($category['limit']);

                    if($limit <= 0) {
                        $this->conn->rollBack();
                        return false;
                    }

                    $stmt->bindParam(":goal_id", $goal_id);
                    $stmt->bindParam(":name", $name);
                    $stmt->bindParam(":limit_amount", $limit);

                    if(!$stmt->execute()) {
                        $this->conn->rollBack();
                        return false;
                    }
                }
            }

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function read($id) {
        // Get main budget goal
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->monthly_limit = $row['monthly_limit'];
            $this->created_at = $row['created_at'];

            // Get categories
            $query = "SELECT * FROM " . $this->categories_table . " WHERE goal_id = :goal_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":goal_id", $this->id);
            $stmt->execute();

            $this->categories = [];
            while($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->categories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'limit' => $category['limit_amount']
                ];
            }
            
            return true;
        }
        return false;
    }

    public function update() {
        try {
            $this->conn->beginTransaction();

            // Update main budget goal
            $query = "UPDATE " . $this->table . " 
                     SET monthly_limit = :monthly_limit 
                     WHERE id = :id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            // Validate input
            $this->monthly_limit = floatval($this->monthly_limit);
            if($this->monthly_limit <= 0) {
                return false;
            }

            // Bind values
            $stmt->bindParam(":monthly_limit", $this->monthly_limit);
            $stmt->bindParam(":id", $this->id);
            $stmt->bindParam(":user_id", $this->user_id);

            if(!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            // Delete existing categories
            $query = "DELETE FROM " . $this->categories_table . " WHERE goal_id = :goal_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":goal_id", $this->id);
            
            if(!$stmt->execute()) {
                $this->conn->rollBack();
                return false;
            }

            // Insert new categories
            if(!empty($this->categories)) {
                $query = "INSERT INTO " . $this->categories_table . "
                         (goal_id, name, limit_amount) VALUES 
                         (:goal_id, :name, :limit_amount)";
                
                $stmt = $this->conn->prepare($query);

                foreach($this->categories as $category) {
                    $name = htmlspecialchars(strip_tags($category['name']));
                    $limit = floatval($category['limit']);

                    if($limit <= 0) {
                        $this->conn->rollBack();
                        return false;
                    }

                    $stmt->bindParam(":goal_id", $this->id);
                    $stmt->bindParam(":name", $name);
                    $stmt->bindParam(":limit_amount", $limit);

                    if(!$stmt->execute()) {
                        $this->conn->rollBack();
                        return false;
                    }
                }
            }

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function delete($id) {
        // Categories will be deleted automatically due to foreign key constraint
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $this->user_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getCurrentBudget() {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->monthly_limit = $row['monthly_limit'];
            $this->created_at = $row['created_at'];

            // Get categories
            $query = "SELECT * FROM " . $this->categories_table . " WHERE goal_id = :goal_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":goal_id", $this->id);
            $stmt->execute();

            $this->categories = [];
            while($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->categories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'limit' => $category['limit_amount']
                ];
            }
            
            return true;
        }
        return false;
    }

    public function checkLimits($expenses) {
        $alerts = [];
        $total_expense = 0;
        $category_expenses = [];

        // Calculate total and category expenses
        foreach($expenses as $expense) {
            $total_expense += $expense['amount'];
            
            if(!isset($category_expenses[$expense['category']])) {
                $category_expenses[$expense['category']] = 0;
            }
            $category_expenses[$expense['category']] += $expense['amount'];
        }

        // Check overall budget limit
        if($total_expense >= $this->monthly_limit) {
            $alerts[] = [
                'type' => 'overall',
                'message' => 'Monthly budget limit exceeded',
                'current' => $total_expense,
                'limit' => $this->monthly_limit
            ];
        } elseif($total_expense >= ($this->monthly_limit * 0.9)) {
            $alerts[] = [
                'type' => 'overall',
                'message' => 'Approaching monthly budget limit',
                'current' => $total_expense,
                'limit' => $this->monthly_limit
            ];
        }

        // Check category limits
        foreach($this->categories as $category) {
            $category_expense = isset($category_expenses[$category['name']]) 
                              ? $category_expenses[$category['name']] 
                              : 0;

            if($category_expense >= $category['limit']) {
                $alerts[] = [
                    'type' => 'category',
                    'category' => $category['name'],
                    'message' => 'Category budget limit exceeded',
                    'current' => $category_expense,
                    'limit' => $category['limit']
                ];
            } elseif($category_expense >= ($category['limit'] * 0.9)) {
                $alerts[] = [
                    'type' => 'category',
                    'category' => $category['name'],
                    'message' => 'Approaching category budget limit',
                    'current' => $category_expense,
                    'limit' => $category['limit']
                ];
            }
        }

        return $alerts;
    }

    public function getCategoryLimit($category_name) {
        foreach($this->categories as $category) {
            if($category['name'] === $category_name) {
                return $category['limit'];
            }
        }
        return null;
    }
}
