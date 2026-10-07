{extends file="layout.tpl"}

{block name="content"}
    <div class="container">
        <header class="hero">
            <p class="eyebrow">Журнал</p>
            <h1>Короткие тексты, к которым возвращаются</h1>
            <p class="hero__text">{$siteTagline}</p>
        </header>

        {if $sections}
            {foreach $sections as $section}
                <section class="category-block" aria-labelledby="category-{$section.id}">
                    <div class="category-block__top">
                        <div>
                            <h2 id="category-{$section.id}">{$section.name}</h2>
                            <p class="category-block__description">{$section.description}</p>
                        </div>
                        <a class="button" href="/category/{$section.slug}">Все статьи</a>
                    </div>
                    <div class="cards cards--three">
                        {foreach $section.articles as $article}
                            {include file="partials/article-card.tpl" article=$article}
                        {/foreach}
                    </div>
                </section>
            {/foreach}
        {else}
            <section class="empty">
                <h2>В журнале пока нет публикаций</h2>
                <p>Когда в категориях появятся статьи, они соберутся на этой странице.</p>
            </section>
        {/if}
    </div>
{/block}
