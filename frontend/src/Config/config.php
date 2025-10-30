<?php
/**
 * Silo HCI Configuration
 */

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'Silo HCI',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
    
    'api' => [
        'host' => $_ENV['API_HOST'] ?? 'localhost',
        'port' => $_ENV['API_PORT'] ?? 5000,
        'prefix' => $_ENV['API_PREFIX'] ?? '/api/v1',
        'timeout' => 30,
    ],
    
    'session' => [
        'lifetime' => $_ENV['SESSION_LIFETIME'] ?? 7200,
        'secure' => ($_ENV['SESSION_SECURE'] ?? 'false') === 'true',
    ],
    
    'cache' => [
        'enabled' => ($_ENV['CACHE_ENABLED'] ?? 'true') === 'true',
        'ttl' => $_ENV['CACHE_TTL'] ?? 60,
    ],
];
