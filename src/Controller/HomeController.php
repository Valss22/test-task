<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\View;

final class HomeController
{
    private const LATEST_PER_CATEGORY = 3;

    public function __construct(
        private ArticleRepository $articles,
        private View $view,
    ) {
    }

    /** @param array<string, string> $params */
    public function index(array $params): void
    {
        $this->view->render('home.tpl', [
            'sections' => $this->articles->latestPerCategory(self::LATEST_PER_CATEGORY),
        ]);
    }
}
