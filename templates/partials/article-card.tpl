<article class="card">
    <a class="card__media" href="/article/{$article.slug}">
        <img src="{$article.image}" alt="{$article.title}" width="1200" height="800" loading="lazy" decoding="async">
    </a>
    <div class="card__body">
        {if $article.categories}
            <ul class="tags">
                {foreach $article.categories as $category}
                    <li><a href="/category/{$category.slug}">{$category.name}</a></li>
                {/foreach}
            </ul>
        {/if}
        <h3 class="card__title"><a href="/article/{$article.slug}">{$article.title}</a></h3>
        <p class="card__text">{$article.description}</p>
        <p class="card__meta">
            <time datetime="{$article.published_iso}">{$article.published_label}</time>
            <span aria-hidden="true">·</span>
            <span>{$article.views_label}</span>
        </p>
    </div>
</article>
