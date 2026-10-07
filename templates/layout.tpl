<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{block name="title"}{$siteName}{/block}</title>
    {block name="meta"}
        <meta name="description" content="{$siteTagline}">
    {/block}
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:ital,opsz,wght@0,7..72,560;0,7..72,700;1,7..72,560&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <a class="skip" href="#content">К содержанию</a>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="/">{$siteName}</a>
            <nav class="nav" aria-label="Разделы">
                <a class="nav__link{if $currentPath == '/'} is-active{/if}" href="/">Главная</a>
                {foreach $nav as $item}
                    <a class="nav__link{if $currentPath == "/category/{$item.slug}"} is-active{/if}" href="/category/{$item.slug}">{$item.name}</a>
                {/foreach}
            </nav>
        </div>
    </header>
    <main id="content" class="site-main">
        {block name="content"}{/block}
    </main>
    <footer class="site-footer">
        <div class="container">
            <p>{$siteName} · {$year}</p>
        </div>
    </footer>
</body>
</html>
