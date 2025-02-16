<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function register() {
        // Verify CSRF token
        verify_csrf_token();

        // Get POST data
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Validate input
        $errors = [];

        if(empty($username)) {
            $errors[] = "Username is required";
        } elseif(strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters";
        }

        if(empty($password)) {
            $errors[] = "Password is required";
        } elseif(strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }

        if($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Try to create the user
        if($this->user->create($username, $password)) {
            // Log the user in
            if($this->user->login($username, $password)) {
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                
                return [
                    'success' => true,
                    'message' => 'Registration successful'
                ];
            }
        }

        return [
            'success' => false,
            'errors' => ['Failed to create account. Username might already exist.']
        ];
    }

    public function login() {
        // Verify CSRF token
        verify_csrf_token();

        // Get POST data
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        // Validate input
        $errors = [];

        if(empty($username)) {
            $errors[] = "Username is required";
        }

        if(empty($password)) {
            $errors[] = "Password is required";
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Try to log in
        if($this->user->login($username, $password)) {
            $_SESSION['user_id'] = $this->user->id;
            $_SESSION['username'] = $this->user->username;

            return [
                'success' => true,
                'message' => 'Login successful'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Invalid username or password']
        ];
    }

    public function logout() {
        // Clear session data
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        
        // Destroy the session
        session_destroy();

        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }

    public function updateProfile() {
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
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Validate input
        $errors = [];

        if(empty($username)) {
            $errors[] = "Username is required";
        } elseif(strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters";
        }

        if(empty($current_password)) {
            $errors[] = "Current password is required";
        }

        if(!empty($new_password)) {
            if(strlen($new_password) < 6) {
                $errors[] = "New password must be at least 6 characters";
            }
            if($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match";
            }
        }

        // If there are errors, return them
        if(!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Try to update the user
        if($this->user->update($_SESSION['user_id'], $username, $current_password, $new_password)) {
            $_SESSION['username'] = $username;
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to update profile. Current password might be incorrect.']
        ];
    }

    public function deleteAccount() {
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
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        // Validate input
        if(empty($password)) {
            return [
                'success' => false,
                'errors' => ['Password is required to delete account']
            ];
        }

        // Try to delete the user
        if($this->user->delete($_SESSION['user_id'], $password)) {
            // Log out the user
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            session_destroy();

            return [
                'success' => true,
                'message' => 'Account deleted successfully'
            ];
        }

        return [
            'success' => false,
            'errors' => ['Failed to delete account. Password might be incorrect.']
        ];
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if(!$this->isLoggedIn()) {
            return null;
        }

        $this->user->read($_SESSION['user_id']);
        return [
            'id' => $this->user->id,
            'username' => $this->user->username
        ];
    }
}
