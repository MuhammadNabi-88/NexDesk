<?php
// Default settings. Override anything in config.local.php (same structure) or with environment variables.
return [
    'app_name' => 'Nexdesk',
    'timezone' => 'Europe/Berlin',
    // Demo mode seeds sample data on first start and shows one-click demo logins. Turn it off in production.
    'demo'     => filter_var(getenv('NEXDESK_DEMO') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'db'       => [
        'driver'      => getenv('DB_DRIVER') ?: 'sqlite',   // 'sqlite' (zero setup) or 'mysql'
        'sqlite_path' => getenv('DB_SQLITE_PATH') ?: __DIR__ . '/storage/nexdesk.sqlite',
        'host'        => getenv('DB_HOST') ?: '127.0.0.1',
        'port'        => getenv('DB_PORT') ?: '3306',
        'name'        => getenv('DB_NAME') ?: 'nexdesk',
        'user'        => getenv('DB_USER') ?: 'root',
        'pass'        => getenv('DB_PASS') ?: '',
    ],
];
