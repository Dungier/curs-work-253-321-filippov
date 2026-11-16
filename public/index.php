<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
define('APP_BASE', rtrim(str_replace('\\', '/', dirname($script)), '/.'));
date_default_timezone_set('Europe/Moscow');
ini_set('session.use_strict_mode', '1');
session_name('list_session');
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_set_cookie_params(['lifetime' => 0, 'path' => APP_BASE . '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
$GLOBALS['settings'] = require APP_ROOT . '/config/settings.php';
require APP_ROOT . '/src/helpers.php';
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = APP_ROOT . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
});
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

if (APP_BASE !== '' && str_starts_with($path, APP_BASE . '/')) {
    $path = substr($path, strlen(APP_BASE));
} elseif ($path === APP_BASE) {
    $path = '/';
}

if ($path === '/index.php') {
    $path = '/';
}

$router = new App\Core\Router();

foreach (require APP_ROOT . '/src/routes.php' as [$method, $pattern, $action]) {
    $router->add($method, $pattern, $action);
}

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $path);
} catch (Throwable $error) {
    $status = in_array($error->getCode(), [401, 403, 404, 409, 419, 422, 429, 502, 503], true) ? $error->getCode() : 500;
    $message = $status === 500 ? 'Не удалось открыть страницу. Попробуйте ещё раз немного позже.' : $error->getMessage();

    if ($status === 500) {
        error_log('List application: ' . get_class($error) . ' in ' . basename($error->getFile()) . ':' . $error->getLine());
    }

    App\Core\View::render('error', ['title' => 'Ошибка ' . $status . ' — Лист', 'message' => $message], $status);
}
