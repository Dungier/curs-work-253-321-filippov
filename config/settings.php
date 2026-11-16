<?php
$secrets = is_file(__DIR__ . '/secrets.php') ? require __DIR__ . '/secrets.php' : [];

return [
    'api_key' => getenv('OPENROUTER_API_KEY') ?: ($secrets['api_key'] ?? ''),
    'api_url' => getenv('STORY_API_URL') ?: 'https://openrouter.ai/api/v1/chat/completions',
    'model' => 'openrouter/free',
    'database' => getenv('STORY_DATABASE') ?: dirname(__DIR__) . '/storage/stories.sqlite',
    'daily_limit' => 45,
    'user_daily_limit' => 15,
    'genres' => ['fairy' => 'Сказка', 'fantasy' => 'Фэнтези', 'adventure' => 'Приключения', 'scifi' => 'Фантастика', 'detective' => 'Детектив'],
    'page_counts' => [1, 2, 3],
];
