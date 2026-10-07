<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\NotFound;
use App\Repository\ArticleRepository;
use App\Support\Format;
use App\View;

final class ArticleController
{
    private const RELATED_LIMIT = 3;

    public function __construct(
        private ArticleRepository $articles,
        private View $view,
    ) {
    }

    /** @param array<string, string> $params */
    public function show(array $params): void
    {
        $article = $this->articles->findBySlug($params['slug']);
        if ($article === null) {
            throw new NotFound();
        }

        if ($this->countView($article['id'])) {
            $this->articles->incrementViews($article['id']);
            $article['views']++;
            $article['views_label'] = Format::views($article['views']);
        }

        $this->view->render('article.tpl', [
            'article' => $article,
            'related' => $this->articles->related($article['id'], self::RELATED_LIMIT),
        ]);
    }

    private function countView(int $articleId): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_strict_mode', '1');
            session_start();
        }

        $seen = $_SESSION['viewed_articles'] ?? [];
        if (!is_array($seen) || in_array($articleId, $seen, true)) {
            return false;
        }

        $seen[] = $articleId;
        $_SESSION['viewed_articles'] = $seen;

        return true;
    }
}
