<?php
require_once __DIR__ . '/../config/config.php';

class BillReminder {
    private $conn;
    private $table = 'bill_reminders';

    public $id;
    public $user_id;
    public $name;
    public $due_day;
    public $reminder_days;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 (user_id, name, due_day, reminder_days) 
                 VALUES 
                 (:user_id, :name, :due_day, :reminder_days)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->due_day = intval($this->due_day);
        $this->reminder_days = intval($this->reminder_days);

        // Validate inputs
        if($this->due_day < 1 || $this->due_day > 31) {
            return false;
        }
        if($this->reminder_days < 1 || $this->reminder_days > 15) {
            return false;
        }

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":due_day", $this->due_day);
        $stmt->bindParam(":reminder_days", $this->reminder_days);

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
            $this->due_day = $row['due_day'];
            $this->reminder_days = $row['reminder_days'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET name = :name, 
                     due_day = :due_day, 
                     reminder_days = :reminder_days 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->due_day = intval($this->due_day);
        $this->reminder_days = intval($this->reminder_days);

        // Validate inputs
        if($this->due_day < 1 || $this->due_day > 31) {
            return false;
        }
        if($this->reminder_days < 1 || $this->reminder_days > 15) {
            return false;
        }

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":due_day", $this->due_day);
        $stmt->bindParam(":reminder_days", $this->reminder_days);
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
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY due_day ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function getUpcomingReminders() {
        $current_day = intval(date('d'));
        $reminders = [];
        $next_month_reminders = [];

        $stmt = $this->getAll();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $due_day = intval($row['due_day']);
            $reminder_days = intval($row['reminder_days']);
            
            // Calculate days until due
            $days_until_due = $due_day - $current_day;
            if($days_until_due < 0) {
                // Due date is in next month
                $days_until_due = $due_day + (date('t') - $current_day);
                $next_month = true;
            } else {
                $next_month = false;
            }

            // Check if we should show reminder
            if($days_until_due <= $reminder_days) {
                $reminder = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'due_day' => $due_day,
                    'days_until_due' => $days_until_due,
                    'is_next_month' => $next_month,
                    'due_date' => $next_month ? 
                                 date('Y-m-d', strtotime('first day of next month + ' . ($due_day - 1) . ' days')) :
                                 date('Y-m-d', strtotime(date('Y-m-') . $due_day)),
                    'status' => $days_until_due < 0 ? 'overdue' : 
                               ($days_until_due == 0 ? 'due_today' : 'upcoming')
                ];

                if($next_month) {
                    $next_month_reminders[] = $reminder;
                } else {
                    $reminders[] = $reminder;
                }
            }
        }

        // Combine current month and next month reminders
        return array_merge($reminders, $next_month_reminders);
    }

    public function checkDueToday() {
        $current_day = intval(date('d'));
        $reminders = [];

        $stmt = $this->getAll();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(intval($row['due_day']) === $current_day) {
                $reminders[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'due_day' => $row['due_day']
                ];
            }
        }

        return $reminders;
    }

    public function getReminderStatus($reminder_id) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $reminder_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $current_day = intval(date('d'));
            $due_day = intval($row['due_day']);
            $reminder_days = intval($row['reminder_days']);
            
            // Calculate days until due
            $days_until_due = $due_day - $current_day;
            if($days_until_due < 0) {
                // Due date is in next month
                $days_until_due = $due_day + (date('t') - $current_day);
                $due_date = date('Y-m-d', strtotime('first day of next month + ' . ($due_day - 1) . ' days'));
            } else {
                $due_date = date('Y-m-d', strtotime(date('Y-m-') . $due_day));
            }

            return [
                'name' => $row['name'],
                'due_day' => $due_day,
                'reminder_days' => $reminder_days,
                'days_until_due' => $days_until_due,
                'due_date' => $due_date,
                'status' => $days_until_due < 0 ? 'overdue' : 
                           ($days_until_due == 0 ? 'due_today' : 
                           ($days_until_due <= $reminder_days ? 'upcoming' : 'normal'))
            ];
        }

        return null;
    }

    public function getCalendarEvents($start_date, $end_date) {
        $events = [];
        $stmt = $this->getAll();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $current = clone $start;

            while($current <= $end) {
                if(intval($current->format('d')) === intval($row['due_day'])) {
                    $events[] = [
                        'title' => $row['name'],
                        'start' => $current->format('Y-m-d'),
                        'type' => 'bill_reminder',
                        'reminder_id' => $row['id']
                    ];
                }
                $current->modify('+1 day');
            }
        }

        return $events;
    }
}
