<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Создавайте короткие истории по своему замыслу, сохраняйте их и скачивайте в PDF.">
<title><?= e($title) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/app.css?v=1')) ?>">
<link rel="icon" href="<?= e(url('/assets/leaf.svg')) ?>" type="image/svg+xml">
<script src="<?= e(url('/assets/app.js?v=1')) ?>" defer></script>
</head>
<body>
<a class="skip" href="#main">К содержимому</a>
<header class="site-header"><div class="header-inner">
<a class="brand" href="<?= e(url()) ?>"><img src="<?= e(url('/assets/leaf.svg')) ?>" alt="" width="34" height="34"><span>Лист<span class="brand-caption">мастерская историй</span></span></a>
<nav class="main-nav" aria-label="Основная навигация"><a href="<?= e(url('/stories/new')) ?>">Создать историю</a><a href="<?= e(url('/library')) ?>">Библиотека</a><?php if ($user): ?><a href="<?= e(url('/my-stories')) ?>">Мои истории</a><?php endif; ?></nav>
<div class="account-nav"><?php if ($user): ?><span class="user-name"><?= e($user['name']) ?></span><form action="<?= e(url('/logout')) ?>" method="post"><?= csrf_field() ?><button class="text-button" type="submit">Выйти</button></form><?php else: ?><a href="<?= e(url('/login')) ?>">Войти</a><a class="button small" href="<?= e(url('/register')) ?>">Регистрация</a><?php endif; ?></div>
</div></header>
<main id="main" class="container">
<?php if ($notice): ?><div class="notice <?= e($notice['type']) ?>" role="status"><?= e($notice['text']) ?></div><?php endif; ?>
<?= $content ?>
</main>
<footer class="site-footer"><div><strong>Лист</strong><span>Короткие истории из ваших идей</span></div><p>Курсовой проект · Филиппов Фёдор Геннадьевич · 253-321</p></footer>
</body></html>
