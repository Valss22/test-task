{extends file="layout.tpl"}

{block name="title"}{$article.title} — {$siteName}{/block}

{block name="meta"}
    <meta name="description" content="{$article.description}">
{/block}

{block name="content"}
    <div class="container">
        <article class="article">
            <header class="article__head">
                {if $article.categories}
                    <ul class="tags">
                        {foreach $article.categories as $category}
                            <li><a href="/category/{$category.slug}">{$category.name}</a></li>
                        {/foreach}
                    </ul>
                {/if}
                <h1>{$article.title}</h1>
                <p class="lead">{$article.description}</p>
                <p class="article__meta">
                    <time datetime="{$article.published_iso}">{$article.published_label}</time>
                    <span aria-hidden="true">·</span>
                    <span>{$article.views_label}</span>
                </p>
            </header>
            <figure class="article__figure">
                <img src="{$article.image}" alt="{$article.title}" width="1200" height="750">
            </figure>
            <div class="prose">
                {foreach $article.paragraphs as $paragraph}
                    <p>{$paragraph}</p>
                {/foreach}
            </div>
        </article>
    </div>

    {if $related}
        <section class="similar" aria-labelledby="similar-title">
            <div class="container">
                <h2 id="similar-title">Похожие статьи</h2>
                <div class="cards cards--three">
                    {foreach $related as $relatedArticle}
                        {include file="partials/article-card.tpl" article=$relatedArticle}
                    {/foreach}
                </div>
            </div>
        </section>
    {/if}
{/block}
