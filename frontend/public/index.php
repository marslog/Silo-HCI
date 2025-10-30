<?php
/**
 * Silo HCI - Main Entry Point
 */

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load config
$config = require __DIR__ . '/../src/Config/config.php';

// Start session
session_start();

// Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Check if logged in for protected routes
$public_routes = ['/login', '/logout', '/session/sync'];
if (!in_array($uri, $public_routes) && $uri !== '/' && strpos($uri, '/api/') !== 0) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login?error=Please log in first');
        exit;
    }
}

// Handle logout
if ($uri === '/logout' && $method === 'POST') {
    session_destroy();
    header('Location: /login?message=Logged out successfully');
    exit;
}

// PHP session sync from frontend fetch
if ($uri === '/session/sync' && $method === 'POST') {
    require __DIR__ . '/session_sync.php';
    exit;
}

// Simple routing
switch ($uri) {
    // Auth routes
    case '/login':
        require __DIR__ . '/login.php';
        break;
    
    case '/':
    case '/dashboard':
        require __DIR__ . '/pages/dashboard.php';
        break;
        
    case '/nodes':
        require __DIR__ . '/pages/nodes.php';
        break;
    
    case '/vms':
    case '/vm/list':
        require __DIR__ . '/pages/vms.php';
        break;
    
    case '/containers':
    case '/lxc/list':
        require __DIR__ . '/pages/containers.php';
        break;
    
    case '/storage':
        require __DIR__ . '/pages/storage.php';
        break;
    
    case '/network':
        require __DIR__ . '/pages/network.php';
        break;
    
    case '/backup':
        require __DIR__ . '/pages/backup.php';
        break;
    
    case '/monitoring':
        require __DIR__ . '/pages/monitoring.php';
        break;
    
    // System routes
    case '/system/generate':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            header('Location: /dashboard?error=Admin access required');
            exit;
        }
        require __DIR__ . '/pages/system/generate.php';
        break;
    
    case '/system/license':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            header('Location: /dashboard?error=Admin access required');
            exit;
        }
        require __DIR__ . '/pages/system/license.php';
        break;
    
    case '/system/account':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            header('Location: /dashboard?error=Admin access required');
            exit;
        }
        require __DIR__ . '/pages/system/account.php';
        break;
    
    // Security routes
    case '/security/2fa':
        require __DIR__ . '/pages/security/2fa.php';
        break;
    
    case '/settings':
        require __DIR__ . '/pages/settings.php';
        break;
        
    default:
        http_response_code(404);
        require __DIR__ . '/pages/404.php';
        break;
}
