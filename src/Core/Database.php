<?php
namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $file = settings('database');
            $directory = dirname($file);

            if (!is_dir($directory)) {
                mkdir($directory, 0700, true);
            }

            self::$connection = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            self::$connection->exec('PRAGMA foreign_keys = ON');
            self::$connection->exec('PRAGMA busy_timeout = 5000');
            self::$connection->exec(file_get_contents(APP_ROOT . '/database/schema.sql'));
        }

        return self::$connection;
    }
}
