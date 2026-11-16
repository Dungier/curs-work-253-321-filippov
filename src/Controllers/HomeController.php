<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\Story;

class HomeController
{
    public function index(): void
    {
        $recent = Story::library('', 1)['items'];
        View::render('home', ['title' => 'Лист — истории из ваших идей', 'recent' => array_slice($recent, 0, 3)]);
    }

    public function library(): void
    {
        $genre = is_string($_GET['genre'] ?? null) ? $_GET['genre'] : '';
        $result = Story::library($genre, max(1, (int) ($_GET['page'] ?? 1)));
        View::render('stories/library', ['title' => 'Библиотека — Лист', 'result' => $result, 'genre' => $genre]);
    }
}
