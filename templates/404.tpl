{extends file="layout.tpl"}

{block name="title"}Страница не найдена — {$siteName}{/block}

{block name="content"}
    <div class="container">
        <section class="empty">
            <p class="eyebrow">404</p>
            <h1>Такой страницы нет</h1>
            <p>Возможно, ссылка устарела или в адресе опечатка.</p>
            <a class="button" href="/">На главную</a>
        </section>
    </div>
{/block}
