<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, array $action): void
    {
        $this->routes[] = [$method, $pattern, $action];
    }

    public function dispatch(string $method, string $path): void
    {
        $pathExists = false;

        foreach ($this->routes as [$routeMethod, $pattern, $action]) {
            if (preg_match($pattern, $path, $matches)) {
                $pathExists = true;

                if ($method === $routeMethod || ($method === 'HEAD' && $routeMethod === 'GET')) {
                    array_shift($matches);
                    [$class, $function] = $action;
                    (new $class())->$function(...$matches);
                    return;
                }
            }
        }

        View::render('error', ['title' => $pathExists ? 'Метод не поддерживается' : 'Страница не найдена', 'message' => $pathExists ? 'Для этого адреса нужен другой способ отправки запроса.' : 'Возможно, история удалена или доступна только её автору.'], $pathExists ? 405 : 404);
    }
}
