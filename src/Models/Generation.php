<?php
namespace App\Models;

use App\Core\Database;
use RuntimeException;

class Generation
{
    public static function create(int $userId, string $premise, string $genre, int $pages): int
    {
        $db = Database::connection();
        $query = $db->prepare('SELECT COUNT(*) FROM generations WHERE user_id=? AND status IN (\'pending\',\'running\')');
        $query->execute([$userId]);

        if ((int) $query->fetchColumn() >= 3) {
            throw new RuntimeException('Сначала завершите или удалите одну из начатых историй.', 422);
        }

        $query = $db->prepare('INSERT INTO generations(user_id,premise,genre,page_count) VALUES(?,?,?,?)');
        $query->execute([$userId, $premise, $genre, $pages]);
        return (int) $db->lastInsertId();
    }

    public static function owned(int $id, int $userId): ?array
    {
        $query = Database::connection()->prepare('SELECT * FROM generations WHERE id=? AND user_id=? AND status<>\'deleted\'');
        $query->execute([$id, $userId]);
        $row = $query->fetch();

        if ($row) {
            $row['pages'] = json_decode($row['pages_json'], true, 512, JSON_THROW_ON_ERROR);
            $row['outline'] = json_decode($row['outline_json'], true, 512, JSON_THROW_ON_ERROR);
        }

        return $row ?: null;
    }

    public static function unfinished(int $userId): array
    {
        $query = Database::connection()->prepare('SELECT * FROM generations WHERE user_id=? AND status NOT IN (\'done\',\'deleted\') ORDER BY id DESC');
        $query->execute([$userId]);
        return $query->fetchAll();
    }

    public static function claim(int $id): bool
    {
        $query = Database::connection()->prepare('UPDATE generations SET locked_until=?,status=\'running\',error=\'\' WHERE id=? AND status<>\'done\' AND locked_until<?');
        $query->execute([time() + 105, $id, time()]);
        return $query->rowCount() === 1;
    }

    public static function startCall(int $userId, int $generationId): int
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $total = (int) $db->query("SELECT COUNT(*) FROM generation_calls WHERE created_at>=date('now')")->fetchColumn();
            $query = $db->prepare("SELECT COUNT(*) FROM generation_calls WHERE user_id=? AND created_at>=date('now')");
            $query->execute([$userId]);

            if ($total >= settings('daily_limit') || (int) $query->fetchColumn() >= settings('user_daily_limit')) {
                throw new RuntimeException('На сегодня лимит генерации исчерпан. Сохранённые истории и PDF доступны без ограничений.', 429);
            }

            $query = $db->prepare('INSERT INTO generation_calls(user_id,generation_id) VALUES(?,?)');
            $query->execute([$userId, $generationId]);
            $id = (int) $db->lastInsertId();
            $db->commit();
            return $id;
        } catch (\Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    }

    public static function endCall(int $id, string $outcome): void
    {
        $query = Database::connection()->prepare('UPDATE generation_calls SET outcome=? WHERE id=?');
        $query->execute([$outcome, $id]);
    }

    public static function savePage(array $generation, array $result): ?int
    {
        $db = Database::connection();
        $pages = $generation['pages'];
        $pages[] = $result['page'];
        $title = $generation['title'] !== '' ? $generation['title'] : $result['title'];
        $outline = $generation['outline'] ?: $result['outline'];
        $finished = count($pages) === (int) $generation['page_count'];
        $storyId = null;
        $db->beginTransaction();

        try {
            if ($finished) {
                $query = $db->prepare('INSERT INTO stories(user_id,generation_id,title,premise,genre,page_count,pages_json) VALUES(?,?,?,?,?,?,?)');
                $query->execute([$generation['user_id'], $generation['id'], $title, $generation['premise'], $generation['genre'], $generation['page_count'], json_encode($pages, JSON_UNESCAPED_UNICODE)]);
                $storyId = (int) $db->lastInsertId();
            }

            $query = $db->prepare('UPDATE generations SET title=?,outline_json=?,pages_json=?,status=?,locked_until=0,story_id=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $query->execute([$title, json_encode($outline, JSON_UNESCAPED_UNICODE), json_encode($pages, JSON_UNESCAPED_UNICODE), $finished ? 'done' : 'pending', $storyId, $generation['id']]);
            $db->commit();
        } catch (\Throwable $error) {
            $db->rollBack();
            throw $error;
        }

        return $storyId;
    }

    public static function fail(int $id, string $message): void
    {
        $query = Database::connection()->prepare('UPDATE generations SET status=\'failed\',locked_until=0,error=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        $query->execute([$message, $id]);
    }

    public static function delete(int $id): void
    {
        $query = Database::connection()->prepare("UPDATE generations SET status='deleted',premise='',title='',outline_json='[]',pages_json='[]',error='',locked_until=0 WHERE id=?");
        $query->execute([$id]);
    }
}
