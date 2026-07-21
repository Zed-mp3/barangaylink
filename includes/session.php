<?php
// Session management and security functions

class Session {
    
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
    
    public static function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        session_destroy();
    }
    
    public static function regenerate() {
        session_regenerate_id(true);
    }
    
    public static function setFlash($message, $type = 'success') {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    public static function displayFlash() {
        $flash = self::getFlash();
        if ($flash) {
            $class = $flash['type'] == 'success' ? 'alert-success' : 'alert-danger';
            return "<div class='alert $class'>{$flash['message']}</div>";
        }
        return '';
    }
}

// Initialize session
Session::init();
?>