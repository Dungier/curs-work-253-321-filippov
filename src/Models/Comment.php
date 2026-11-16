<?php
namespace App\Models;

use App\Core\Database;

class Comment
{
    public static function forStory(int $storyId): array
    {
        $query = Database::connection()->prepare('SELECT c.*,u.name AS author FROM comments c JOIN users u ON u.id=c.user_id WHERE c.story_id=? ORDER BY c.id');
        $query->execute([$storyId]);
        return $query->fetchAll();
    }

    public static function owned(int $id, int $userId): ?array
    {
        $query = Database::connection()->prepare('SELECT * FROM comments WHERE id=? AND user_id=?');
        $query->execute([$id, $userId]);
        return $query->fetch() ?: null;
    }

    public static function create(int $userId, int $storyId, string $text): int
    {
        $db = Database::connection();
        $query = $db->prepare('INSERT INTO comments(user_id,story_id,text) VALUES(?,?,?)');
        $query->execute([$userId, $storyId, $text]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, string $text): void
    {
        $query = Database::connection()->prepare('UPDATE comments SET text=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $query->execute([$text, $id]);
    }

    public static function delete(int $id): void
    {
        $query = Database::connection()->prepare('DELETE FROM comments WHERE id=?');
        $query->execute([$id]);
    }
}
