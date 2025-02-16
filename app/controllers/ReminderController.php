<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/BillReminder.php';

class ReminderController {
    private $db;
    private $reminder;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->reminder = new BillReminder($this->db);
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
        $due_day = isset($_POST['due_day']) ? intval($_POST['due_day']) : 0;
        $reminder_days = isset($_POST['reminder_days']) ? intval($_POST['reminder_days']) : 0;

        // Validate input
        $errors = [];

        if(empty($name)) {
            $errors[] = "Bill name is required";
        }

        if($due_day < 1 || $due_day > 31) {
            $errors[] = "Due day must be between 1 and 31";
        }

        if($reminder_days < 1 || $reminder_days > 15) {
            $errors[] = "Reminder days must be between 1 and 15";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Set reminder properties
        $this->reminder->user_id = $_SESSION['user_id'];
        $this->reminder->name = $name;
        $this->reminder->due_day = $due_day;
        $this->reminder->reminder_days = $reminder_days;

        // Try to create the reminder
        if($this->reminder->create()) {
            return [
                'success' => true,
                'message' => 'Bill reminder created successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to create bill reminder']
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
        $due_day = isset($_POST['due_day']) ? intval($_POST['due_day']) : 0;
        $reminder_days = isset($_POST['reminder_days']) ? intval($_POST['reminder_days']) : 0;

        // Validate input
        $errors = [];

        if(empty($name)) {
            $errors[] = "Bill name is required";
        }

        if($due_day < 1 || $due_day > 31) {
            $errors[] = "Due day must be between 1 and 31";
        }

        if($reminder_days < 1 || $reminder_days > 15) {
            $errors[] = "Reminder days must be between 1 and 15";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Set reminder properties
        $this->reminder->id = $id;
        $this->reminder->user_id = $_SESSION['user_id'];
        $this->reminder->name = $name;
        $this->reminder->due_day = $due_day;
        $this->reminder->reminder_days = $reminder_days;

        // Try to update the reminder
        if($this->reminder->update()) {
            return [
                'success' => true,
                'message' => 'Bill reminder updated successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update bill reminder']
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

        $this->reminder->user_id = $_SESSION['user_id'];
        
        // Try to delete the reminder
        if($this->reminder->delete($id)) {
            return [
                'success' => true,
                'message' => 'Bill reminder deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to delete bill reminder']
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

        $this->reminder->user_id = $_SESSION['user_id'];
        $result = $this->reminder->getAll();
        
        $reminders = [];
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $status = $this->reminder->getReminderStatus($row['id']);
            $row['status'] = $status;
            $reminders[] = $row;
        }

        return [
            'success' => true,
            'data' => $reminders
        ];
    }

    public function getUpcoming() {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->reminder->user_id = $_SESSION['user_id'];
        $reminders = $this->reminder->getUpcomingReminders();

        return [
            'success' => true,
            'data' => $reminders
        ];
    }

    public function getDueToday() {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->reminder->user_id = $_SESSION['user_id'];
        $reminders = $this->reminder->checkDueToday();

        return [
            'success' => true,
            'data' => $reminders
        ];
    }

    public function getCalendarEvents($start_date, $end_date) {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        // Validate dates
        if(!strtotime($start_date) || !strtotime($end_date)) {
            return [
                'success' => false,
                'errors' => ['Invalid date format']
            ];
        }

        $this->reminder->user_id = $_SESSION['user_id'];
        $events = $this->reminder->getCalendarEvents($start_date, $end_date);

        return [
            'success' => true,
            'data' => $events
        ];
    }

    public function getReminderStatus($id) {
        // Check if user is logged in
        if(!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'errors' => ['Not logged in']
            ];
        }

        $this->reminder->user_id = $_SESSION['user_id'];
        $status = $this->reminder->getReminderStatus($id);

        if($status) {
            return [
                'success' => true,
                'data' => $status
            ];
        }

        return [
            'success' => false,
            'errors' => ['Reminder not found']
        ];
    }
}
