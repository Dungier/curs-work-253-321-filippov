<?php
namespace App\Core;

use App\Models\User;

class Auth
{
    public static function user(): ?array
    {
        return isset($_SESSION['user_id']) ? User::find((int) $_SESSION['user_id']) : null;
    }

    public static function requireUser(): array
    {
        $user = self::user();

        if ($user === null) {
            flash('Войдите, чтобы продолжить.');
            redirect('/login');
        }

        return $user;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        unset($_SESSION['csrf']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
