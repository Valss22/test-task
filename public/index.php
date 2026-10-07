<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo 'Нужен PHP 8.1 или новее.';
    exit;
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo 'Зависимости не установлены. Выполните composer install в корне проекта.';
    exit;
}

require $autoload;

App\Bootstrap::run();
