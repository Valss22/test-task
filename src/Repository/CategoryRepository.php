<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{name: string, slug: string}> */
    public function menu(): array
    {
        $stmt = $this->pdo->query(
            'SELECT name, slug FROM categories ORDER BY position ASC, name ASC'
        );

        return $stmt->fetchAll();
    }

    /** @return array{id: int, name: string, slug: string, description: string}|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, slug, description FROM categories WHERE slug = ?'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'],
        ];
    }
}
