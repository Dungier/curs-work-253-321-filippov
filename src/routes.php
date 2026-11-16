<?php
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\StoryController;
use App\Controllers\GenerationController;
use App\Controllers\CommentController;

return [
    ['GET', '~^/$~', [HomeController::class, 'index']],
    ['GET', '~^/library$~', [HomeController::class, 'library']],
    ['GET', '~^/register$~', [AuthController::class, 'registerForm']],
    ['POST', '~^/register$~', [AuthController::class, 'register']],
    ['GET', '~^/login$~', [AuthController::class, 'loginForm']],
    ['POST', '~^/login$~', [AuthController::class, 'login']],
    ['POST', '~^/logout$~', [AuthController::class, 'logout']],
    ['GET', '~^/my-stories$~', [StoryController::class, 'mine']],
    ['GET', '~^/stories/new$~', [StoryController::class, 'createForm']],
    ['POST', '~^/generations$~', [GenerationController::class, 'create']],
    ['GET', '~^/generations/([1-9][0-9]*)$~', [GenerationController::class, 'show']],
    ['POST', '~^/generations/([1-9][0-9]*)/step$~', [GenerationController::class, 'step']],
    ['POST', '~^/generations/([1-9][0-9]*)/delete$~', [GenerationController::class, 'delete']],
    ['GET', '~^/stories/([1-9][0-9]*)$~', [StoryController::class, 'show']],
    ['GET', '~^/stories/([1-9][0-9]*)/edit$~', [StoryController::class, 'editForm']],
    ['POST', '~^/stories/([1-9][0-9]*)/edit$~', [StoryController::class, 'edit']],
    ['POST', '~^/stories/([1-9][0-9]*)/publish$~', [StoryController::class, 'publish']],
    ['POST', '~^/stories/([1-9][0-9]*)/delete$~', [StoryController::class, 'delete']],
    ['GET', '~^/stories/([1-9][0-9]*)/pdf$~', [StoryController::class, 'pdf']],
    ['POST', '~^/stories/([1-9][0-9]*)/comments$~', [CommentController::class, 'create']],
    ['GET', '~^/comments/([1-9][0-9]*)/edit$~', [CommentController::class, 'editForm']],
    ['POST', '~^/comments/([1-9][0-9]*)/edit$~', [CommentController::class, 'edit']],
    ['POST', '~^/comments/([1-9][0-9]*)/delete$~', [CommentController::class, 'delete']],
];
