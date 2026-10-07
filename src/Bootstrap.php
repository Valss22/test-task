<?php

declare(strict_types=1);

namespace App;

use App\Controller\ArticleController;
use App\Controller\CategoryController;
use App\Controller\HomeController;
use App\Http\NotFound;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Throwable;

final class Bootstrap
{
    public static function run(): void
    {
        $config = require dirname(__DIR__) . '/config/config.php';
        $debug = filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN);

        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        set_exception_handler(static function (Throwable $e) use ($debug): void {
            error_log((string) $e);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<!doctype html><html lang="ru"><meta charset="utf-8"><title>Ошибка</title><body>';
            echo '<p>Не получилось открыть страницу. Попробуйте обновить её чуть позже.</p>';
            if ($debug) {
                echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
            }
            echo '</body></html>';
        });

        try {
            $pdo = Database::connect($config['db']);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            throw new \RuntimeException('Нет соединения с базой данных. Проверьте, что MySQL запущен.', 0, $e);
        }

        $categories = new CategoryRepository($pdo);
        $articles = new ArticleRepository($pdo);
        $view = new View($config);
        $view->share('nav', $categories->menu());
        $view->share('currentPath', self::requestPath());

        $router = new Router();
        $router->get('/', [new HomeController($articles, $view), 'index']);
        $router->get('/category/{slug}', [new CategoryController($categories, $articles, $view, (int) $config['per_page']), 'show']);
        $router->get('/article/{slug}', [new ArticleController($articles, $view), 'show']);

        try {
            $router->dispatch(self::requestMethod(), self::requestPath());
        } catch (NotFound) {
            $view->render('404.tpl', [], 404);
        }
    }

    private static function requestMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        return $method === 'HEAD' ? 'GET' : $method;
    }

    private static function requestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        $path = rawurldecode($path);
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        return $path;
    }
}
