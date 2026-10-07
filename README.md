# Черновик

Блог на чистом PHP 8.1+, Smarty и MySQL. Без фреймворков.

На главной — категории, в которых есть статьи, и три последние публикации в каждой. На странице категории — список, сортировка и пагинация. На странице статьи — полный текст и три похожих материала. Похожими считаются статьи с общими категориями: чем больше совпадений, тем выше в блоке.

## Запуск

Нужны Docker и Docker Compose.

```bash
docker compose up -d --build
docker compose exec app php bin/seed.php
```

Сайт откроется на [http://localhost:8080](http://localhost:8080).

Повторный запуск `php bin/seed.php` очищает статьи и категории и заполняет их заново. Счётчики просмотров тоже сбрасываются. Обложки генерируются в `public/assets/images`.

Остановить окружение:

```bash
docker compose down
```

MySQL доступен на порту 3306: база `blog`, пользователь `blog`, пароль `blog`.

## Стили

Исходники лежат в `scss/`. Собранный файл — `public/assets/css/main.css`.

```bash
npm install
npm run build:css
```

## Без Docker

Нужны PHP 8.1+ с `pdo_mysql` и `mbstring`, Composer и MySQL 8.

```bash
composer install
mysql -u blog -p blog < database/schema.sql
php bin/seed.php
php -S localhost:8080 -t public public/router.php
```

Параметры подключения читаются из переменных `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`. Если их нет, используются `127.0.0.1`, `3306`, `blog`, `blog`, `blog`.

## Структура

```text
bin/seed.php                 сидинг категорий и статей
config/config.php            имя сайта и параметры базы
database/schema.sql          таблицы
database/fixtures.php        данные для сидинга
public/index.php             входная точка
src/Bootstrap.php            сборка приложения
src/Router.php               маршруты
src/View.php                 Smarty
src/Repository               запросы к MySQL
src/Controller               страницы
templates                    шаблоны
scss                         стили
```

Маршруты:

- `/` — главная
- `/category/{slug}` — категория, параметры `sort` (`date` или `views`), `order` (`asc` или `desc`), `page`
- `/article/{slug}` — статья
