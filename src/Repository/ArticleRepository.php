<?php

declare(strict_types=1);

namespace App\Repository;

use App\Support\Format;
use DateTimeImmutable;
use PDO;

final class ArticleRepository
{
    private const SORTS = [
        'date' => 'a.published_at',
        'views' => 'a.views',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Категории, в которых есть статьи, и несколько последних публикаций в каждой.
     * Номер строки внутри категории считает оконная функция, поэтому витрина
     * собирается одним запросом, а не циклом по категориям.
     *
     * @return list<array<string, mixed>>
     */
    public function latestPerCategory(int $limit = 3): array
    {
        $limit = max(1, $limit);
        $sql = <<<SQL
            SELECT
                c.id AS category_id,
                c.name AS category_name,
                c.slug AS category_slug,
                c.description AS category_description,
                a.id,
                a.title,
                a.slug,
                a.description,
                a.image,
                a.views,
                a.published_at
            FROM categories AS c
            INNER JOIN (
                SELECT
                    pairs.category_id,
                    pairs.article_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY pairs.category_id
                        ORDER BY pairs.published_at DESC, pairs.article_id DESC
                    ) AS rn
                FROM (
                    SELECT
                        ac.category_id,
                        a.id AS article_id,
                        a.published_at
                    FROM article_categories AS ac
                    INNER JOIN articles AS a ON a.id = ac.article_id
                ) AS pairs
            ) AS ranked ON ranked.category_id = c.id AND ranked.rn <= {$limit}
            INNER JOIN articles AS a ON a.id = ranked.article_id
            ORDER BY c.position ASC, a.published_at DESC
        SQL;

        $sections = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $row) {
            $categoryId = (int) $row['category_id'];
            if (!isset($sections[$categoryId])) {
                $sections[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $row['category_name'],
                    'slug' => $row['category_slug'],
                    'description' => $row['category_description'],
                    'articles' => [],
                ];
            }
            $sections[$categoryId]['articles'][] = $this->mapArticle($row);
        }

        return $this->hydrateSectionArticles(array_values($sections));
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginateByCategory(int $categoryId, string $sort, string $order, int $page, int $perPage): array
    {
        $column = self::SORTS[$sort] ?? throw new \InvalidArgumentException('Неизвестная сортировка.');
        $direction = match ($order) {
            'asc' => 'ASC',
            'desc' => 'DESC',
            default => throw new \InvalidArgumentException('Неизвестный порядок сортировки.'),
        };

        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $tieBreaker = $sort === 'views' ? 'a.published_at DESC, a.id DESC' : 'a.id DESC';

        $count = $this->pdo->prepare('SELECT COUNT(*) FROM article_categories WHERE category_id = ?');
        $count->execute([$categoryId]);
        $total = (int) $count->fetchColumn();

        // Колонка и направление уже проверены по белому списку.
        // LIMIT и OFFSET подставляются целыми: нативный MySQL PDO не принимает здесь плейсхолдеры.
        $sql = <<<SQL
            SELECT a.id, a.title, a.slug, a.description, a.image, a.views, a.published_at
            FROM articles AS a
            INNER JOIN article_categories AS ac ON ac.article_id = a.id
            WHERE ac.category_id = ?
            ORDER BY {$column} {$direction}, {$tieBreaker}
            LIMIT {$perPage} OFFSET {$offset}
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$categoryId]);

        $articles = [];
        foreach ($stmt->fetchAll() as $row) {
            $articles[] = $this->mapArticle($row);
        }

        return [
            'items' => $this->attachCategories($articles),
            'total' => $total,
        ];
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, slug, description, content, image, views, published_at
             FROM articles
             WHERE slug = ?'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        return $this->attachCategories([$this->mapArticle($row, true)])[0];
    }

    /**
     * Статьи с общими категориями. Чем больше совпадений, тем выше в списке.
     *
     * @return list<array<string, mixed>>
     */
    public function related(int $articleId, int $limit = 3): array
    {
        $limit = max(1, $limit);
        $sql = <<<SQL
            SELECT a.id, a.title, a.slug, a.description, a.image, a.views, a.published_at
            FROM articles AS a
            INNER JOIN article_categories AS ac ON ac.article_id = a.id
            WHERE a.id <> ?
              AND ac.category_id IN (
                  SELECT category_id
                  FROM article_categories
                  WHERE article_id = ?
              )
            GROUP BY a.id
            ORDER BY COUNT(*) DESC, a.published_at DESC
            LIMIT {$limit}
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$articleId, $articleId]);

        $articles = [];
        foreach ($stmt->fetchAll() as $row) {
            $articles[] = $this->mapArticle($row);
        }

        return $this->attachCategories($articles);
    }

    public function incrementViews(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE articles SET views = views + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @return array<string, mixed> */
    private function mapArticle(array $row, bool $withContent = false): array
    {
        $published = new DateTimeImmutable((string) $row['published_at']);
        $views = (int) $row['views'];
        $article = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'image' => $row['image'],
            'views' => $views,
            'views_label' => Format::views($views),
            'published_iso' => $published->format('Y-m-d'),
            'published_label' => Format::date($published),
            'categories' => [],
        ];

        if ($withContent) {
            $article['paragraphs'] = $this->paragraphs((string) $row['content']);
        }

        return $article;
    }

    /** @return list<string> */
    private function paragraphs(string $content): array
    {
        $parts = preg_split("/\R\s*\R/u", trim($content)) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $part): string => trim($part), $parts),
            static fn (string $part): bool => $part !== ''
        ));
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    private function hydrateSectionArticles(array $sections): array
    {
        $unique = [];
        foreach ($sections as $section) {
            foreach ($section['articles'] as $article) {
                $unique[$article['id']] = $article;
            }
        }

        $withCategories = [];
        foreach ($this->attachCategories(array_values($unique)) as $article) {
            $withCategories[$article['id']] = $article;
        }

        foreach ($sections as $sectionIndex => $section) {
            foreach ($section['articles'] as $articleIndex => $article) {
                $sections[$sectionIndex]['articles'][$articleIndex] = $withCategories[$article['id']];
            }
        }

        return $sections;
    }

    /**
     * @param list<array<string, mixed>> $articles
     * @return list<array<string, mixed>>
     */
    private function attachCategories(array $articles): array
    {
        if ($articles === []) {
            return [];
        }

        $ids = array_map(static fn (array $article): int => (int) $article['id'], $articles);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = <<<SQL
            SELECT ac.article_id, c.name, c.slug
            FROM article_categories AS ac
            INNER JOIN categories AS c ON c.id = ac.category_id
            WHERE ac.article_id IN ({$placeholders})
            ORDER BY c.position ASC, c.name ASC
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['article_id']][] = [
                'name' => $row['name'],
                'slug' => $row['slug'],
            ];
        }

        foreach ($articles as $index => $article) {
            $articles[$index]['categories'] = $grouped[(int) $article['id']] ?? [];
        }

        return $articles;
    }
}
