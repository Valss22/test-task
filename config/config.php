<?php

declare(strict_types=1);

return [
    'site_name' => 'Черновик',
    'site_tagline' => 'Журнал о городах, кухне, книгах и маршрутах, по которым хочется пройти ещё раз.',
    'per_page' => 6,
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'blog',
        'user' => getenv('DB_USER') ?: 'blog',
        'password' => getenv('DB_PASSWORD') ?: 'blog',
    ],
];
