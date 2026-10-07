<?php

declare(strict_types=1);

namespace App;

use App\Http\NotFound;

final class Router
{
    /** @var array<string, callable> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[a-z0-9-]+)', $pattern);
        if (!is_string($regex)) {
            throw new \InvalidArgumentException('Некорректный маршрут: ' . $pattern);
        }

        $this->routes['#^' . $regex . '$#'] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        if ($method !== 'GET') {
            throw new NotFound();
        }

        foreach ($this->routes as $regex => $handler) {
            if (preg_match($regex, $path, $matches) !== 1) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn (mixed $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );
            $handler($params);

            return;
        }

        throw new NotFound();
    }
}
