<?php

declare(strict_types=1);

use App\Database;

if (PHP_VERSION_ID < 80100) {
    fwrite(STDERR, "Нужен PHP 8.1 или новее.\n");
    exit(1);
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Сначала выполните composer install.\n");
    exit(1);
}

require $autoload;

$config = require dirname(__DIR__) . '/config/config.php';
$fixtures = require dirname(__DIR__) . '/database/fixtures.php';

try {
    $pdo = Database::connect($config['db']);
    seed($pdo, $fixtures, dirname(__DIR__) . '/public/assets/images');
} catch (Throwable $e) {
    fwrite(STDERR, 'Сидинг не выполнен: ' . $e->getMessage() . "\n");
    exit(1);
}

/**
 * @param array{categories: list<array<string, mixed>>, articles: list<array<string, mixed>>} $fixtures
 */
function seed(PDO $pdo, array $fixtures, string $imageDir): void
{
    echo "Очищаю таблицы и заполняю журнал заново...\n";

    if (!is_dir($imageDir) && !mkdir($imageDir, 0775, true) && !is_dir($imageDir)) {
        throw new RuntimeException('Не удалось создать каталог изображений.');
    }

    foreach (glob($imageDir . '/*.svg') ?: [] as $oldCover) {
        unlink($oldCover);
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE article_categories');
    $pdo->exec('TRUNCATE TABLE articles');
    $pdo->exec('TRUNCATE TABLE categories');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $pdo->beginTransaction();

    try {
        $categoryIds = insertCategories($pdo, $fixtures['categories']);
        $articleCount = insertArticles($pdo, $fixtures['articles'], $categoryIds, $fixtures['categories'], $imageDir);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    echo 'Готово: ' . count($categoryIds) . ' категорий, ' . $articleCount . " статей.\n";
}

/**
 * @param list<array<string, mixed>> $categories
 * @return array<string, int>
 */
function insertCategories(PDO $pdo, array $categories): array
{
    $stmt = $pdo->prepare(
        'INSERT INTO categories (name, slug, description, position) VALUES (?, ?, ?, ?)'
    );
    $ids = [];

    foreach ($categories as $category) {
        $slug = (string) $category['slug'];
        assertSlug($slug, 'категории');
        if (isset($ids[$slug])) {
            throw new InvalidArgumentException('Повторяется категория: ' . $slug);
        }

        $stmt->execute([
            $category['name'],
            $slug,
            $category['description'],
            (int) $category['position'],
        ]);
        $ids[$slug] = (int) $pdo->lastInsertId();
    }

    return $ids;
}

/**
 * @param list<array<string, mixed>> $articles
 * @param array<string, int> $categoryIds
 * @param list<array<string, mixed>> $categories
 */
function insertArticles(PDO $pdo, array $articles, array $categoryIds, array $categories, string $imageDir): int
{
    $palette = [];
    foreach ($categories as $category) {
        $palette[(string) $category['slug']] = [
            (string) $category['color'],
            (string) $category['accent'],
        ];
    }

    $articleStmt = $pdo->prepare(
        'INSERT INTO articles (title, slug, description, content, image, views, published_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $linkStmt = $pdo->prepare(
        'INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)'
    );
    $seen = [];

    foreach ($articles as $article) {
        $slug = (string) $article['slug'];
        assertSlug($slug, 'статьи');
        if (isset($seen[$slug])) {
            throw new InvalidArgumentException('Повторяется статья: ' . $slug);
        }
        $seen[$slug] = true;

        $publishedAt = (string) $article['published_at'];
        assertDate($publishedAt, $slug);

        $views = $article['views'];
        if (!is_int($views) || $views < 0) {
            throw new InvalidArgumentException('Некорректные просмотры у статьи ' . $slug);
        }

        $links = $article['categories'];
        if (!is_array($links) || $links === []) {
            throw new InvalidArgumentException('У статьи ' . $slug . ' не указана категория.');
        }

        $primary = (string) $links[0];
        if (!isset($palette[$primary])) {
            throw new InvalidArgumentException('Неизвестная категория ' . $primary . ' у статьи ' . $slug);
        }

        [$background, $foreground] = $palette[$primary];
        $image = writeCover($imageDir, $slug, $background, $foreground);

        $articleStmt->execute([
            $article['title'],
            $slug,
            $article['description'],
            trim((string) $article['content']),
            $image,
            $views,
            $publishedAt,
        ]);
        $articleId = (int) $pdo->lastInsertId();

        foreach ($links as $categorySlug) {
            $categorySlug = (string) $categorySlug;
            if (!isset($categoryIds[$categorySlug])) {
                throw new InvalidArgumentException('Неизвестная категория ' . $categorySlug . ' у статьи ' . $slug);
            }
            $linkStmt->execute([$articleId, $categoryIds[$categorySlug]]);
        }
    }

    return count($seen);
}

function assertSlug(string $slug, string $owner): void
{
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        throw new InvalidArgumentException('Некорректный адрес ' . $owner . ': ' . $slug);
    }
}

function assertDate(string $value, string $slug): void
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
    if ($date === false || $date->format('Y-m-d H:i:s') !== $value) {
        throw new InvalidArgumentException('Некорректная дата у статьи ' . $slug);
    }
}

function writeCover(string $directory, string $slug, string $background, string $foreground): string
{
    $n = hexdec(substr(md5($slug), 0, 8));
    $cx = 760 + ($n % 300);
    $cy = 160 + (intdiv($n, 256) % 260);
    $radius = 120 + (intdiv($n, 65536) % 150);
    $bar = 160 + (intdiv($n, 16) % 460);
    $dot = 40 + (intdiv($n, 4096) % 80);
    $background = htmlspecialchars($background, ENT_QUOTES);
    $foreground = htmlspecialchars($foreground, ENT_QUOTES);

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 750" role="img">
  <rect width="1200" height="750" fill="{$background}"/>
  <circle cx="{$cx}" cy="{$cy}" r="{$radius}" fill="{$foreground}"/>
  <circle cx="160" cy="620" r="{$dot}" fill="{$foreground}" fill-opacity="0.45"/>
  <rect x="72" y="72" width="1056" height="606" fill="none" stroke="{$foreground}" stroke-opacity="0.45" stroke-width="3"/>
  <rect x="72" y="600" width="{$bar}" height="14" fill="{$foreground}"/>
</svg>
SVG;

    if (file_put_contents($directory . '/' . $slug . '.svg', $svg) === false) {
        throw new RuntimeException('Не удалось записать обложку ' . $slug);
    }

    return '/assets/images/' . $slug . '.svg';
}
