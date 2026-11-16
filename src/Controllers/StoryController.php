<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Story;
use App\Models\Comment;
use App\Models\Generation;
use App\Services\PdfExporter;
use RuntimeException;

class StoryController
{
    public function createForm(): void
    {
        Auth::requireUser();
        View::render('stories/new', ['title' => 'Новая история — Лист', 'errors' => [], 'values' => []]);
    }

    public function mine(): void
    {
        $user = Auth::requireUser();
        View::render('stories/mine', ['title' => 'Мои истории — Лист', 'stories' => Story::forUser($user['id']), 'unfinished' => Generation::unfinished($user['id'])]);
    }

    public function show(string $id): void
    {
        $user = Auth::user();
        $story = Story::visible((int) $id, $user['id'] ?? null);

        if ($story === null) {
            throw new RuntimeException('История не найдена или доступна только автору.', 404);
        }

        View::render('stories/show', ['title' => $story['title'] . ' — Лист', 'story' => $story, 'comments' => $story['is_public'] ? Comment::forStory($story['id']) : []]);
    }

    public function editForm(string $id): void
    {
        $user = Auth::requireUser();
        $story = Story::owned((int) $id, $user['id']);

        if ($story === null) {
            throw new RuntimeException('История не найдена.', 404);
        }

        View::render('stories/edit', ['title' => 'Редактирование — Лист', 'story' => $story, 'errors' => []]);
    }

    public function edit(string $id): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $story = Story::owned((int) $id, $user['id']);

        if ($story === null) {
            throw new RuntimeException('История не найдена.', 404);
        }

        $title = post_string('title');
        $input = $_POST['pages'] ?? [];
        $pages = [];
        $errors = [];

        if ($title === '' || mb_strlen($title) > 120) {
            $errors[] = 'Укажите название длиной до 120 символов.';
        }

        if (!is_array($input) || count($input) !== (int) $story['page_count']) {
            $errors[] = 'Количество страниц изменять нельзя.';
        } else {
            foreach (array_values($input) as $index => $page) {
                $heading = trim(is_string($page['heading'] ?? null) ? $page['heading'] : '');
                $text = trim(is_string($page['text'] ?? null) ? $page['text'] : '');
                $pages[] = ['heading' => $heading, 'text' => $text];

                if ($heading === '' || mb_strlen($heading) > 90 || mb_strlen($text) < 100 || mb_strlen($text) > 2600) {
                    $errors[] = 'Страница ' . ($index + 1) . ': нужны заголовок до 90 символов и текст от 100 до 2600 символов.';
                }
            }
        }

        if ($errors) {
            $story['title'] = $title;
            $story['pages'] = $pages ?: $story['pages'];
            View::render('stories/edit', ['title' => 'Редактирование — Лист', 'story' => $story, 'errors' => $errors], 422);
            return;
        }

        $candidate = $story;
        $candidate['title'] = $title;
        $candidate['pages'] = $pages;

        try {
            (new PdfExporter())->build($candidate);
        } catch (RuntimeException $error) {
            View::render('stories/edit', ['title' => 'Редактирование — Лист', 'story' => $candidate, 'errors' => [$error->getMessage()]], 422);
            return;
        }

        Story::save($story['id'], $title, $pages);
        flash('Изменения сохранены.');
        redirect('/stories/' . $story['id']);
    }

    public function publish(string $id): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $story = Story::owned((int) $id, $user['id']);

        if ($story === null) {
            throw new RuntimeException('История не найдена.', 404);
        }

        $public = post_string('is_public') === '1';
        Story::publish($story['id'], $public);
        flash($public ? 'История опубликована в библиотеке.' : 'История снова доступна только вам.');
        redirect('/stories/' . $story['id']);
    }

    public function delete(string $id): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $story = Story::owned((int) $id, $user['id']);

        if ($story === null) {
            throw new RuntimeException('История не найдена.', 404);
        }

        Story::delete($story['id']);
        flash('История удалена.');
        redirect('/my-stories');
    }

    public function pdf(string $id): void
    {
        $user = Auth::user();
        $story = Story::visible((int) $id, $user['id'] ?? null);

        if ($story === null) {
            throw new RuntimeException('История не найдена.', 404);
        }

        $bytes = (new PdfExporter())->build($story);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="story-' . $story['id'] . '.pdf"; filename*=UTF-8\'\'' . rawurlencode($story['title'] . '.pdf'));
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
    }
}
