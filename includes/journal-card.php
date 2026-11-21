<?php
// Included inside journal loops with $post set to a blog_posts row joined with its category name.
/** @var array $post */
$postUrl = url('article.php?slug=' . rawurlencode($post['slug']));
?>
<article class="group flex flex-col overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-ink/5 transition motion-safe:hover:-translate-y-1 hover:shadow-xl">
    <a href="<?= e($postUrl) ?>" class="relative block aspect-[4/3] overflow-hidden" tabindex="-1" aria-hidden="true">
        <img class="h-full w-full object-cover motion-safe:transition motion-safe:duration-700 motion-safe:group-hover:scale-105" src="<?= e(journal_image($post['image'])) ?>" alt="" loading="lazy">
        <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[10px] font-semibold uppercase tracking-[.16em] text-ink"><?= e($post['category_name'] ?? 'Yucca notes') ?></span>
    </a>
    <div class="flex flex-1 flex-col p-6">
        <p class="text-xs text-gray-500"><?= e(date('j M Y', strtotime($post['published_at']))) ?> · <?= read_minutes($post['body']) ?> min read</p>
        <h3 class="mt-2 font-serif text-xl leading-snug"><a class="transition hover:text-forest" href="<?= e($postUrl) ?>"><?= e($post['title']) ?></a></h3>
        <p class="mt-3 line-clamp-2 text-sm leading-6 text-gray-600"><?= e($post['excerpt'] ?: strip_tags($post['body'])) ?></p>
        <a class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-semibold text-forest" href="<?= e($postUrl) ?>">Read the story <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i><span class="sr-only">: <?= e($post['title']) ?></span></a>
    </div>
</article>
