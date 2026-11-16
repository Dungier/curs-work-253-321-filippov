<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function registerForm(): void
    {
        View::render('auth/register', ['title' => 'Регистрация — Лист', 'errors' => [], 'values' => []]);
    }

    public function register(): void
    {
        check_csrf();
        $values = ['name' => post_string('name'), 'email' => post_string('email')];
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
        $errors = [];

        if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 50) {
            $errors[] = 'Имя должно содержать от 2 до 50 символов.';
        }

        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 190) {
            $errors[] = 'Укажите корректную электронную почту.';
        } elseif (User::byEmail($values['email'])) {
            $errors[] = 'Эта электронная почта уже зарегистрирована.';
        }

        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Используйте от 8 до 72 латинских букв, цифр и символов.';
        } elseif ($password !== $confirmation) {
            $errors[] = 'Пароли не совпадают.';
        }

        if ($errors) {
            View::render('auth/register', ['title' => 'Регистрация — Лист', 'errors' => $errors, 'values' => $values], 422);
            return;
        }

        try {
            $user = User::create($values['name'], $values['email'], $password);
        } catch (\PDOException $error) {
            View::render('auth/register', ['title' => 'Регистрация — Лист', 'errors' => ['Не удалось создать аккаунт. Проверьте почту и повторите попытку.'], 'values' => $values], 422);
            return;
        }

        Auth::login($user);
        flash('Аккаунт создан. Можно написать первую историю.');
        redirect('/stories/new');
    }

    public function loginForm(): void
    {
        View::render('auth/login', ['title' => 'Вход — Лист', 'error' => '', 'email' => '']);
    }

    public function login(): void
    {
        check_csrf();
        $email = post_string('email');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $attempts = $_SESSION['login_attempts'] ?? ['start' => time(), 'count' => 0];

        if (time() - $attempts['start'] > 600) {
            $attempts = ['start' => time(), 'count' => 0];
        }

        if ($attempts['count'] >= 10) {
            View::render('auth/login', ['title' => 'Вход — Лист', 'error' => 'Слишком много попыток. Подождите 10 минут.', 'email' => $email], 429);
            return;
        }

        $user = User::byEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $attempts['count']++;
            $_SESSION['login_attempts'] = $attempts;
            View::render('auth/login', ['title' => 'Вход — Лист', 'error' => 'Почта или пароль указаны неверно.', 'email' => $email], 422);
            return;
        }

        unset($_SESSION['login_attempts']);
        Auth::login($user);
        redirect('/my-stories');
    }

    public function logout(): void
    {
        check_csrf();
        Auth::logout();
        redirect('/');
    }
}
