<?php
require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->checkRememberToken(); // Check for remember token first
        $this->checkSessionValidity(); // Then validate session
    }
    
    public function login($username, $password, $remember = false) {
        $username = $this->db->escape($username);
        $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
        $result = $this->db->query($sql);
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Clear any existing session data
                $_SESSION = array();
                
                // Set new session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['login_time'] = time(); // Track login time
                $_SESSION['session_id'] = session_id(); // Track session ID
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Log the login activity
                $this->logActivity($user['id'], 'login');
                
                // Set remember me token if requested
                if ($remember) {
                    $this->setRememberToken($user['id']);
                }
                
                return true;
            }
        }
        return false;
    }
    
    public function setRememberToken($user_id) {
        // Generate random token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days')); // Token valid for 30 days
        
        // Store in database
        $user_id = (int)$user_id;
        $token = $this->db->escape($token);
        $expiry = $this->db->escape($expiry);
        
        $sql = "UPDATE users SET remember_token = '$token', token_expiry = '$expiry' WHERE id = $user_id";
        $this->db->query($sql);
        
        // Set cookies (30 days) - HttpOnly for security
        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
        setcookie('remember_user', $user_id, time() + (86400 * 30), '/', '', false, true);
        
        return true;
    }
    
    public function checkRememberToken() {
        // If already logged in, no need to check
        if ($this->isLoggedIn()) {
            return;
        }
        
        // Check if remember cookies exist
        if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
            $token = $this->db->escape($_COOKIE['remember_token']);
            $user_id = (int)$_COOKIE['remember_user'];
            
            // Verify token in database
            $sql = "SELECT * FROM users WHERE id = $user_id AND remember_token = '$token' AND token_expiry > NOW()";
            $result = $this->db->query($sql);
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Auto login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['login_time'] = time();
                $_SESSION['session_id'] = session_id();
                
                // Log auto-login activity
                $this->logActivity($user['id'], 'auto_login');
                
                // Redirect will happen in the page that called this
            } else {
                // Invalid token, clear cookies
                $this->clearRememberToken();
            }
        }
    }
    
    public function clearRememberToken() {
        if (isset($_COOKIE['remember_user'])) {
            $user_id = (int)$_COOKIE['remember_user'];
            // Clear token from database
            $sql = "UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = $user_id";
            $this->db->query($sql);
        }
        
        // Clear cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
    }
    
    public function register($data) {
    $username = $this->db->escape($data['username']);
    $email = $this->db->escape($data['email']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $full_name = $this->db->escape($data['full_name']);
    $address = $this->db->escape($data['address'] ?? '');
    $contact = $this->db->escape($data['contact_number'] ?? '');
    $user_type = $this->db->escape($data['user_type'] ?? 'resident');
    
    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = $this->db->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        return "Username or email already exists!";
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format!";
    }
    
    // Validate password strength
    if (strlen($data['password']) < 8) {
        return "Password must be at least 8 characters long!";
    }
    
    // Set default status based on user type
    $status = 'active';
    
    $sql = "INSERT INTO users (username, email, password, full_name, address, contact_number, user_type, status, created_at) 
            VALUES ('$username', '$email', '$password', '$full_name', '$address', '$contact', '$user_type', '$status', NOW())";
    
    if ($this->db->query($sql)) {
        // Use lastInsertId() instead of insert_id()
        $user_id = $this->db->lastInsertId();
        
        // Log the registration
        $this->logActivity($user_id, 'register');
        
        return true;
    }
    
    return "Registration failed. Please try again.";
}
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
    return (isset($_SESSION['user_type']) && 
        ($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'official'));
}
    
    public function isUser() {
        return (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'resident');
    }
    
    public function validateUserType($required_type) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if ($required_type == 'admin' && !$this->isAdmin()) {
            $this->logout();
            return false;
        }
        
        if ($required_type == 'user' && $this->isAdmin()) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function logout() {
        // Clear remember token first
        $this->clearRememberToken();
        
        // Log the logout activity
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'logout');
        }
        
        // Clear all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        
        $id = $_SESSION['user_id'];
        $sql = "SELECT * FROM users WHERE id = $id";
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../index.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../index.php');
            exit();
        }
        if (!$this->isAdmin()) {
            $this->logout();
            header('Location: ../index.php?error=unauthorized');
            exit();
        }
    }
    
    public function requireUser() {
        if (!$this->isLoggedIn()) {
            header('Location: ../index.php');
            exit();
        }
        if ($this->isAdmin()) {
            $this->logout();
            header('Location: ../index.php?error=invalid_access');
            exit();
        }
    }
    
    /**
     * Check if session is still valid
     */
    private function checkSessionValidity() {
        if (!$this->isLoggedIn()) {
            return;
        }
        
        // Check if session is too old (8 hours max)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
            $this->logout();
            return;
        }
        
        // Verify user still exists and is active
        $id = $_SESSION['user_id'];
        $sql = "SELECT status FROM users WHERE id = $id";
        $result = $this->db->query($sql);
        
        if ($result->num_rows == 0) {
            $this->logout();
            return;
        }
        
        $user = $result->fetch_assoc();
        if ($user['status'] != 'active') {
            $this->logout();
            return;
        }
    }
    
    /**
     * Get session fingerprint for security
     */
    private function getSessionFingerprint() {
        return md5(
            $_SERVER['HTTP_USER_AGENT'] . 
            $_SERVER['REMOTE_ADDR'] . 
            session_id()
        );
    }
    
    /**
     * Log user activities
     */
    private function logActivity($user_id, $action) {
        // Check if table exists
        $check_table = $this->db->query("SHOW TABLES LIKE 'user_logs'");
        if ($check_table->num_rows == 0) {
            // Create table if it doesn't exist
            $this->db->query("
                CREATE TABLE IF NOT EXISTS user_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO user_logs (user_id, action, ip_address, user_agent) 
                VALUES ($user_id, '$action', '$ip', '$user_agent')";
        
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            // Silently fail - don't break the app
        }
    }
    
    /**
     * Check if user has permission to access current page
     */
    public function canAccess($required_type) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if ($required_type == 'admin' && !$this->isAdmin()) {
            return false;
        }
        
        if ($required_type == 'user' && $this->isAdmin()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * AJAX-friendly response for unauthorized access
     */
    public function requireAccess($required_type) {
        if (!$this->canAccess($required_type)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // AJAX request
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized access', 'redirect' => '../index.php']);
                exit();
            } else {
                // Normal request
                $this->logout();
                header('Location: ../index.php?error=unauthorized');
                exit();
            }
        }
    }
}
?>