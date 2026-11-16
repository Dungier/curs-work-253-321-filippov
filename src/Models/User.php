<?php
namespace App\Models;

use App\Core\Database;

class User
{
    public static function find(int $id): ?array
    {
        $query = Database::connection()->prepare('SELECT id,name,email,created_at FROM users WHERE id=?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public static function byEmail(string $email): ?array
    {
        $query = Database::connection()->prepare('SELECT * FROM users WHERE email=? COLLATE NOCASE');
        $query->execute([$email]);
        return $query->fetch() ?: null;
    }

    public static function create(string $name, string $email, string $password): array
    {
        $db = Database::connection();
        $query = $db->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)');
        $query->execute([$name, mb_strtolower($email), password_hash($password, PASSWORD_DEFAULT)]);
        return self::find((int) $db->lastInsertId());
    }
}
