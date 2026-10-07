{extends file="layout.tpl"}

{block name="title"}{$category.name} — {$siteName}{/block}

{block name="meta"}
    <meta name="description" content="{$category.description}">
{/block}

{block name="content"}
    <div class="container">
        <header class="page-head">
            <p class="eyebrow">Категория</p>
            <h1>{$category.name}</h1>
            <p class="lead">{$category.description}</p>
            <p class="page-head__meta">{$totalLabel}</p>
        </header>

        <nav class="sort" aria-label="Сортировка">
            {foreach $sorts as $sort}
                <a class="sort__link{if $sort.active} is-active{/if}" href="{$sort.url}"{if $sort.active} aria-current="true"{/if}>{$sort.label}</a>
            {/foreach}
        </nav>

        {if $articles}
            <div class="cards">
                {foreach $articles as $article}
                    {include file="partials/article-card.tpl" article=$article}
                {/foreach}
            </div>
        {else}
            <p class="empty">В этой категории пока нет статей.</p>
        {/if}

        {if $pagination.pages > 1}
            <nav class="pagination" aria-label="Страницы">
                {if $pagination.hasPrev}
                    <a class="pagination__link" href="{$pagination.prevUrl}">Назад</a>
                {else}
                    <span class="pagination__link is-disabled">Назад</span>
                {/if}

                {foreach $pagination.links as $link}
                    {if $link.current}
                        <span class="pagination__link is-current" aria-current="page">{$link.num}</span>
                    {else}
                        <a class="pagination__link" href="{$link.url}">{$link.num}</a>
                    {/if}
                {/foreach}

                {if $pagination.hasNext}
                    <a class="pagination__link" href="{$pagination.nextUrl}">Дальше</a>
                {else}
                    <span class="pagination__link is-disabled">Дальше</span>
                {/if}
            </nav>
        {/if}
    </div>
{/block}
