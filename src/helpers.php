<?php
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function settings(string $name)
{
    return $GLOBALS['settings'][$name] ?? null;
}

function url(string $path = ''): string
{
    return APP_BASE . ($path === '' ? '/' : $path);
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['text' => $message, 'type' => $type];
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
}

function check_csrf(): void
{
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) {
        throw new RuntimeException('Страница устарела. Обновите её и повторите действие.', 419);
    }
}

function post_string(string $name): string
{
    return is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : '';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function genre_name(string $genre): string
{
    return settings('genres')[$genre] ?? $genre;
}

function page_label(int $count): string
{
    return $count . ($count === 1 ? ' страница' : ' страницы');
}

function short_text(string $text, int $length = 150): string
{
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
}

function story_paragraphs(string $text): string
{
    $parts = preg_split('/\n\s*\n/u', trim($text));
    return implode('', array_map(fn(string $part) => '<p>' . nl2br(e($part)) . '</p>', $parts));
}

function readable_date(string $date): string
{
    return date('d.m.Y', strtotime($date . ' UTC'));
}
