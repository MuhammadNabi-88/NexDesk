<?php
declare(strict_types=1);

// PHP's built-in server: let it serve existing files (css, js) itself.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($requested)) {
        return false;
    }
}

require __DIR__ . '/../app/bootstrap.php';

$routes = [
    ['GET', '#^/$#', [PageController::class, 'home']],
    ['GET', '#^/login$#', [AuthController::class, 'showLogin']],
    ['POST', '#^/login$#', [AuthController::class, 'login']],
    ['POST', '#^/demo-login$#', [AuthController::class, 'demo']],
    ['GET', '#^/register$#', [AuthController::class, 'showRegister']],
    ['POST', '#^/register$#', [AuthController::class, 'register']],
    ['POST', '#^/logout$#', [AuthController::class, 'logout']],
    ['GET', '#^/dashboard$#', [PageController::class, 'dashboard']],
    ['GET', '#^/tickets$#', [TicketController::class, 'index']],
    ['GET', '#^/tickets/new$#', [TicketController::class, 'create']],
    ['POST', '#^/tickets$#', [TicketController::class, 'store']],
    ['GET', '#^/tickets/(\d+)$#', [TicketController::class, 'show']],
    ['POST', '#^/tickets/(\d+)/comment$#', [TicketController::class, 'comment']],
    ['POST', '#^/tickets/(\d+)/update$#', [TicketController::class, 'update']],
    ['GET', '#^/users$#', [PageController::class, 'users']],
    ['POST', '#^/users/(\d+)/role$#', [PageController::class, 'setRole']],
];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
if (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
    $path = substr($path, strlen(BASE_URL));
}
$path = '/' . trim($path, '/');
if (str_starts_with($path, '/index.php')) {
    $path = '/' . ltrim(substr($path, strlen('/index.php')), '/');
}

try {
    foreach ($routes as [$routeMethod, $pattern, $handler]) {
        if ($routeMethod === $method && preg_match($pattern, $path, $matches)) {
            if ($method === 'POST') {
                verify_csrf();
            }
            array_shift($matches);
            call_user_func_array($handler, array_map('intval', $matches));
            exit;
        }
    }
    abort(404, 'We could not find that page.');
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo 'The database is not available. Check config.php or config.local.php.';
}
