<?php
namespace App\Core;

class View
{
    public static function render(string $template, array $variables = [], int $status = 200): void
    {
        http_response_code($status);
        extract($variables, EXTR_SKIP);
        $user = Auth::user();
        $notice = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $title = $title ?? 'Лист';
        ob_start();
        require APP_ROOT . '/views/' . $template . '.php';
        $content = ob_get_clean();
        require APP_ROOT . '/views/layout.php';
    }
}
