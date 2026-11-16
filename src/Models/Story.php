<?php
namespace App\Models;

use App\Core\Database;

class Story
{
    public static function find(int $id): ?array
    {
        $query = Database::connection()->prepare('SELECT s.*,u.name AS author FROM stories s JOIN users u ON u.id=s.user_id WHERE s.id=?');
        $query->execute([$id]);
        $story = $query->fetch();

        if ($story) {
            $story['pages'] = json_decode($story['pages_json'], true, 512, JSON_THROW_ON_ERROR);
        }

        return $story ?: null;
    }

    public static function visible(int $id, ?int $userId): ?array
    {
        $story = self::find($id);
        return $story && ($story['is_public'] || (int) $story['user_id'] === $userId) ? $story : null;
    }

    public static function owned(int $id, int $userId): ?array
    {
        $story = self::find($id);
        return $story && (int) $story['user_id'] === $userId ? $story : null;
    }

    public static function forUser(int $userId): array
    {
        $query = Database::connection()->prepare('SELECT * FROM stories WHERE user_id=? ORDER BY id DESC');
        $query->execute([$userId]);
        return $query->fetchAll();
    }

    public static function library(string $genre, int $page): array
    {
        $db = Database::connection();
        $where = 's.is_public=1';
        $values = [];

        if (array_key_exists($genre, settings('genres'))) {
            $where .= ' AND s.genre=?';
            $values[] = $genre;
        }

        $count = $db->prepare('SELECT COUNT(*) FROM stories s WHERE ' . $where);
        $count->execute($values);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 6));
        $page = min(max(1, $page), $pages);
        $query = $db->prepare('SELECT s.*,u.name AS author FROM stories s JOIN users u ON u.id=s.user_id WHERE ' . $where . ' ORDER BY s.id DESC LIMIT 6 OFFSET ' . (($page - 1) * 6));
        $query->execute($values);
        return ['items' => $query->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function save(int $id, string $title, array $pages): void
    {
        $query = Database::connection()->prepare('UPDATE stories SET title=?,pages_json=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $query->execute([$title, json_encode($pages, JSON_UNESCAPED_UNICODE), $id]);
    }

    public static function publish(int $id, bool $public): void
    {
        $query = Database::connection()->prepare('UPDATE stories SET is_public=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $query->execute([(int) $public, $id]);
    }

    public static function delete(int $id): void
    {
        $db = Database::connection();
        $query = $db->prepare('UPDATE generations SET story_id=NULL WHERE story_id=?');
        $query->execute([$id]);
        $query = $db->prepare('DELETE FROM stories WHERE id=?');
        $query->execute([$id]);
    }
}
