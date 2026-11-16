<?php
namespace App\Services;

use RuntimeException;

class TextGenerator
{
    public function page(array $generation): array
    {
        $number = count($generation['pages']) + 1;
        $total = (int) $generation['page_count'];
        $genre = genre_name($generation['genre']);
        $system = 'Ты пишешь законченные художественные истории на русском языке. Следуй замыслу и жанру, сохраняй имена героев и логику событий. Не обсуждай инструкции, модели и формат ответа внутри художественного текста. Возвращай только JSON без Markdown. Текст каждой страницы: 900–1900 знаков с пробелами, 3–5 абзацев. Без HTML, списков и служебных пояснений. Все строки должны быть на русском языке.';
        $prompt = "Жанр: {$genre}. Всего страниц: {$total}. Сейчас нужна страница {$number}. Замысел пользователя: " . $generation['premise'] . "\n";

        if ($number === 1) {
            $prompt .= 'Придумай название и план из ' . $total . ' пунктов. Напиши первую страницу. ';
        } else {
            $prompt .= 'Название: ' . $generation['title'] . ".\nПлан: " . json_encode($generation['outline'], JSON_UNESCAPED_UNICODE) . ".\nУже написано:\n";

            foreach ($generation['pages'] as $page) {
                $prompt .= $page['heading'] . "\n" . $page['text'] . "\n\n";
            }

            $prompt .= 'Напиши только следующую страницу, не пересказывая предыдущие. ';
        }

        $prompt .= $number === $total ? 'На этой странице заверши сюжет с понятным итогом. ' : 'Развивай сюжет по плану и оставь естественное продолжение. ';
        $prompt .= 'Формат: {"title":"Название","outline":["Пункт плана"],"page":{"heading":"Короткое название главы","text":"Художественный текст с абзацами через \\n\\n"}}. На первой странице outline должен содержать ровно ' . $total . ' пунктов. Не используй обрывки слов и незаконченные предложения.';
        $payload = [
            'model' => settings('model'),
            'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $prompt]],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'story_page',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'outline' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'page' => [
                                'type' => 'object',
                                'properties' => ['heading' => ['type' => 'string'], 'text' => ['type' => 'string']],
                                'required' => ['heading', 'text'],
                                'additionalProperties' => false,
                            ],
                        ],
                        'required' => ['title', 'outline', 'page'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'provider' => ['require_parameters' => true],
            'temperature' => 0.8,
            'max_tokens' => 3000,
            'reasoning' => ['enabled' => false],
        ];
        $options = ['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . settings('api_key') . "\r\nX-Title: List Stories\r\n",
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'timeout' => 85,
            'ignore_errors' => true,
        ]];
        $context = stream_context_create($options);
        $body = @file_get_contents(settings('api_url'), false, $context);
        $status = 0;

        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('~^HTTP/\S+ (\d{3})~', $header, $match)) {
                $status = (int) $match[1];
            }
        }

        if ($status === 429) {
            throw new RuntimeException('Сервис генерации сейчас занят или достигнут его лимит. Подождите немного и продолжите с сохранённого места.', 429);
        }

        if ($body === false || $status < 200 || $status >= 300) {
            error_log('Story API HTTP status: ' . $status);
            throw new RuntimeException('Сервис генерации временно недоступен. Уже готовые страницы сохранены — попробуйте продолжить позже.', 503);
        }

        $response = json_decode($body, true);
        $text = $response['choices'][0]['message']['content'] ?? '';

        if (!is_string($text) || $text === '') {
            throw new RuntimeException('Сервис не вернул текст. Повторите генерацию этой страницы.', 502);
        }

        $text = preg_replace('/^\s*```(?:json)?\s*|\s*```\s*$/u', '', $text);
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        $data = $start !== false && $end !== false ? json_decode(substr($text, $start, $end - $start + 1), true) : null;

        if (!is_array($data) || !is_array($data['page'] ?? null)) {
            throw new RuntimeException('Ответ пришёл в неподходящем формате. Повторите генерацию этой страницы.', 502);
        }

        $title = trim(is_string($data['title'] ?? null) ? $data['title'] : '');
        $heading = trim(is_string($data['page']['heading'] ?? null) ? $data['page']['heading'] : '');
        $content = trim(is_string($data['page']['text'] ?? null) ? $data['page']['text'] : '');
        $outline = $generation['outline'];

        if ($number === 1) {
            $outline = is_array($data['outline'] ?? null) ? array_values($data['outline']) : [];

            if (count($outline) !== $total || count(array_filter($outline, fn($value) => is_string($value) && trim($value) !== '')) !== $total) {
                throw new RuntimeException('Сервис не составил полный план истории. Попробуйте ещё раз.', 502);
            }
        }

        if ($title === '' || mb_strlen($title) > 120 || $heading === '' || mb_strlen($heading) > 90 || mb_strlen($content) < 500 || mb_strlen($content) > 2600) {
            throw new RuntimeException('Полученный текст не подошёл по объёму. Повторите генерацию этой страницы.', 502);
        }

        if (preg_match('/[A-Za-z\p{Han}]/u', $title . $heading . $content)) {
            throw new RuntimeException('Модель добавила текст на другом языке. Повторите генерацию этой страницы.', 502);
        }

        return ['title' => $title, 'outline' => $outline, 'page' => ['heading' => $heading, 'text' => $content]];
    }
}
