<?php
/**
 * Session Management
 */

namespace Silo\Utils;

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key)
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }
    
    public static function destroy()
    {
        self::start();
        session_destroy();
    }
    
    // Authentication methods
    public static function isLoggedIn()
    {
        self::start();
        return isset($_SESSION['user_id']);
    }
    
    public static function getUserId()
    {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUsername()
    {
        self::start();
        return $_SESSION['username'] ?? null;
    }
    
    public static function getUserRole()
    {
        self::start();
        return $_SESSION['user_role'] ?? null;
    }
    
    public static function isAdmin()
    {
        return self::getUserRole() === 'admin';
    }
    
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: /login?error=Please log in first');
            exit;
        }
    }
    
    public static function requireAdmin()
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /dashboard?error=Admin access required');
            exit;
        }
    }
}

