<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Comment;
use App\Models\Story;
use RuntimeException;

class CommentController
{
    public function create(string $storyId): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $story = Story::find((int) $storyId);

        if ($story === null || !$story['is_public']) {
            throw new RuntimeException('Публикация не найдена.', 404);
        }

        $text = post_string('text');

        if ($text === '' || mb_strlen($text) > 2000) {
            flash('Комментарий должен содержать от 1 до 2000 символов.', 'error');
            redirect('/stories/' . $story['id'] . '#comments');
        }

        $id = Comment::create($user['id'], $story['id'], $text);
        redirect('/stories/' . $story['id'] . '#comment' . $id);
    }

    public function editForm(string $id): void
    {
        $user = Auth::requireUser();
        $comment = Comment::owned((int) $id, $user['id']);

        if ($comment === null || Story::visible($comment['story_id'], $user['id']) === null) {
            throw new RuntimeException('Комментарий не найден.', 404);
        }

        View::render('comments/edit', ['title' => 'Редактирование комментария — Лист', 'comment' => $comment, 'error' => '']);
    }

    public function edit(string $id): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $comment = Comment::owned((int) $id, $user['id']);

        if ($comment === null || Story::visible($comment['story_id'], $user['id']) === null) {
            throw new RuntimeException('Комментарий не найден.', 404);
        }

        $text = post_string('text');

        if ($text === '' || mb_strlen($text) > 2000) {
            $comment['text'] = $text;
            View::render('comments/edit', ['title' => 'Редактирование комментария — Лист', 'comment' => $comment, 'error' => 'Комментарий должен содержать от 1 до 2000 символов.'], 422);
            return;
        }

        Comment::update($comment['id'], $text);
        redirect('/stories/' . $comment['story_id'] . '#comment' . $comment['id']);
    }

    public function delete(string $id): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $comment = Comment::owned((int) $id, $user['id']);

        if ($comment === null || Story::visible($comment['story_id'], $user['id']) === null) {
            throw new RuntimeException('Комментарий не найден.', 404);
        }

        Comment::delete($comment['id']);
        redirect('/stories/' . $comment['story_id'] . '#comments');
    }
}
