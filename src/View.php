<?php

declare(strict_types=1);

namespace App;

use Smarty\Smarty;

final class View
{
    private Smarty $smarty;

    /** @var array<string, mixed> */
    private array $shared;

    /** @param array{site_name: string, site_tagline: string} $config */
    public function __construct(array $config)
    {
        $root = dirname(__DIR__);
        $compileDir = $root . '/storage/smarty/compile';
        $cacheDir = $root . '/storage/smarty/cache';
        $this->ensureDirectory($compileDir);
        $this->ensureDirectory($cacheDir);

        $smarty = new Smarty();
        $smarty->setTemplateDir($root . '/templates');
        $smarty->setCompileDir($compileDir);
        $smarty->setCacheDir($cacheDir);
        $smarty->setEscapeHtml(true);
        $smarty->setCaching(Smarty::CACHING_OFF);
        $this->smarty = $smarty;

        $this->shared = [
            'siteName' => $config['site_name'],
            'siteTagline' => $config['site_tagline'],
            'year' => date('Y'),
            'nav' => [],
            'currentPath' => '/',
        ];
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        $this->smarty->assign($this->shared);
        $this->smarty->assign($data);
        $this->smarty->display($template);
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException('Не удалось создать каталог ' . $path);
        }
    }
}
