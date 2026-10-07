<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\NotFound;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Support\Format;
use App\View;

final class CategoryController
{
    public function __construct(
        private CategoryRepository $categories,
        private ArticleRepository $articles,
        private View $view,
        private int $perPage,
    ) {
    }

    /** @param array<string, string> $params */
    public function show(array $params): void
    {
        $category = $this->categories->findBySlug($params['slug']);
        if ($category === null) {
            throw new NotFound();
        }

        [$sort, $order] = $this->sorting();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->articles->paginateByCategory(
            $category['id'],
            $sort,
            $order,
            $page,
            $this->perPage
        );

        $pages = max(1, (int) ceil($result['total'] / $this->perPage));
        if ($result['total'] > 0 && $page > $pages) {
            throw new NotFound();
        }

        $this->view->render('category.tpl', [
            'category' => $category,
            'articles' => $result['items'],
            'totalLabel' => Format::articles($result['total']),
            'sorts' => $this->sortLinks($category['slug'], $sort, $order),
            'pagination' => $this->pagination($category['slug'], $sort, $order, $page, $pages),
        ]);
    }

    /** @return array{0: string, 1: string} */
    private function sorting(): array
    {
        $sort = (string) ($_GET['sort'] ?? 'date');
        $order = (string) ($_GET['order'] ?? 'desc');

        if (!in_array($sort, ['date', 'views'], true)) {
            $sort = 'date';
        }
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }

        return [$sort, $order];
    }

    /** @return list<array{label: string, url: string, active: bool}> */
    private function sortLinks(string $slug, string $sort, string $order): array
    {
        $options = [
            ['date', 'desc', 'Сначала новые'],
            ['date', 'asc', 'Сначала старые'],
            ['views', 'desc', 'Больше просмотров'],
            ['views', 'asc', 'Меньше просмотров'],
        ];

        $links = [];
        foreach ($options as [$optionSort, $optionOrder, $label]) {
            $links[] = [
                'label' => $label,
                'url' => $this->url($slug, $optionSort, $optionOrder),
                'active' => $optionSort === $sort && $optionOrder === $order,
            ];
        }

        return $links;
    }

    /** @return array<string, mixed> */
    private function pagination(string $slug, string $sort, string $order, int $page, int $pages): array
    {
        $links = [];
        for ($number = 1; $number <= $pages; $number++) {
            $links[] = [
                'num' => $number,
                'url' => $this->url($slug, $sort, $order, $number),
                'current' => $number === $page,
            ];
        }

        return [
            'page' => $page,
            'pages' => $pages,
            'hasPrev' => $page > 1,
            'hasNext' => $page < $pages,
            'prevUrl' => $this->url($slug, $sort, $order, $page - 1),
            'nextUrl' => $this->url($slug, $sort, $order, $page + 1),
            'links' => $links,
        ];
    }

    private function url(string $slug, string $sort, string $order, ?int $page = null): string
    {
        $query = [
            'sort' => $sort,
            'order' => $order,
        ];
        if ($page !== null && $page > 1) {
            $query['page'] = $page;
        }

        return '/category/' . rawurlencode($slug) . '?' . http_build_query($query);
    }
}
