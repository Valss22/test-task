<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

final class Format
{
    public static function date(DateTimeImmutable $date): string
    {
        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
        ];

        $month = $months[(int) $date->format('n')];

        return $date->format('j') . ' ' . $month . ' ' . $date->format('Y');
    }

    public static function views(int $count): string
    {
        return self::number($count) . ' ' . self::plural($count, 'просмотр', 'просмотра', 'просмотров');
    }

    public static function articles(int $count): string
    {
        return self::number($count) . ' ' . self::plural($count, 'статья', 'статьи', 'статей');
    }

    private static function number(int $count): string
    {
        return number_format($count, 0, '', ' ');
    }

    private static function plural(int $count, string $one, string $few, string $many): string
    {
        $n = abs($count) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $many;
        }
        if ($n1 > 1 && $n1 < 5) {
            return $few;
        }
        if ($n1 === 1) {
            return $one;
        }

        return $many;
    }
}
