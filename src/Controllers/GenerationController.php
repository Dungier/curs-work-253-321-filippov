<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Generation;
use App\Services\TextGenerator;
use App\Services\PdfExporter;
use RuntimeException;

class GenerationController
{
    public function create(): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $values = ['premise' => post_string('premise'), 'genre' => post_string('genre'), 'page_count' => (int) post_string('page_count')];
        $errors = [];

        if (mb_strlen($values['premise']) < 20 || mb_strlen($values['premise']) > 2000) {
            $errors[] = 'Опишите замысел: от 20 до 2000 символов.';
        }

        if (!array_key_exists($values['genre'], settings('genres'))) {
            $errors[] = 'Выберите жанр из списка.';
        }

        if (!in_array($values['page_count'], settings('page_counts'), true)) {
            $errors[] = 'Выберите одну, две или три страницы.';
        }

        if (settings('api_key') === '') {
            $errors[] = 'Генерация временно недоступна. Попробуйте позже.';
        }

        if ($errors) {
            View::render('stories/new', ['title' => 'Новая история — Лист', 'errors' => $errors, 'values' => $values], 422);
            return;
        }

        $id = Generation::create($user['id'], $values['premise'], $values['genre'], $values['page_count']);
        redirect('/generations/' . $id);
    }

    public function show(string $id): void
    {
        $user = Auth::requireUser();
        $generation = Generation::owned((int) $id, $user['id']);

        if ($generation === null) {
            throw new RuntimeException('История не найдена.', 404);
        }

        if ($generation['status'] === 'done' && $generation['story_id']) {
            redirect('/stories/' . $generation['story_id']);
        }

        View::render('stories/generating', ['title' => 'Создание истории — Лист', 'generation' => $generation]);
    }

    public function step(string $id): void
    {
        $user = Auth::user();

        if ($user === null) {
            json_response(['error' => 'Войдите в аккаунт и продолжите создание истории.'], 401);
        }

        $callId = null;

        try {
            check_csrf();
            $generation = Generation::owned((int) $id, $user['id']);

            if ($generation === null) {
                throw new RuntimeException('История не найдена.', 404);
            }

            if ($generation['status'] === 'done') {
                json_response(['done' => true, 'url' => url('/stories/' . $generation['story_id']), 'current' => $generation['page_count'], 'total' => $generation['page_count']]);
            }

            if (!Generation::claim((int) $id)) {
                json_response(['error' => 'Эта история уже создаётся в другой вкладке. Подождите немного и обновите страницу.'], 409);
            }

            if (settings('api_key') === '') {
                throw new RuntimeException('Генерация временно недоступна. Попробуйте позже.', 503);
            }

            $callId = Generation::startCall($user['id'], (int) $id);
            session_write_close();
            $result = (new TextGenerator())->page($generation);
            (new PdfExporter())->build(['title' => $generation['title'] ?: $result['title'], 'author' => $user['name'], 'genre' => $generation['genre'], 'page_count' => 1, 'pages' => [$result['page']]]);
            $storyId = Generation::savePage($generation, $result);
            Generation::endCall($callId, 'success');
            json_response(['done' => $storyId !== null, 'url' => $storyId ? url('/stories/' . $storyId) : null, 'current' => count($generation['pages']) + 1, 'total' => (int) $generation['page_count']]);
        } catch (\Throwable $error) {
            $status = in_array($error->getCode(), [401, 404, 409, 419, 422, 429, 502, 503], true) ? $error->getCode() : 500;
            $message = $status === 500 ? 'Не удалось сохранить историю. Попробуйте продолжить позже.' : $error->getMessage();

            if (isset($generation) && $generation && $status !== 419) {
                Generation::fail((int) $id, $message);
            }

            if ($callId !== null) {
                Generation::endCall($callId, 'error');
            }

            if ($status === 500) {
                error_log('Generation failure: ' . get_class($error));
            }

            json_response(['error' => $message], $status);
        }
    }

    public function delete(string $id): void
    {
        $user = Auth::requireUser();
        check_csrf();
        $generation = Generation::owned((int) $id, $user['id']);

        if ($generation === null) {
            throw new RuntimeException('История не найдена.', 404);
        }

        if ((int) $generation['locked_until'] > time()) {
            throw new RuntimeException('Дождитесь завершения текущей страницы, затем удалите черновик.', 409);
        }

        Generation::delete((int) $id);
        flash('Черновик удалён.');
        redirect('/my-stories');
    }
}
