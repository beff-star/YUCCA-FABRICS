<?php
require_once __DIR__ . '/includes/functions.php';
$stmt = db()->prepare("SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id=bp.category_id WHERE bp.slug=? AND bp.status='published' AND bp.published_at<=NOW() LIMIT 1");
$stmt->execute([trim((string) ($_GET['slug'] ?? ''))]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); $pageTitle = 'Story not found | Yucca Fabrics'; require __DIR__ . '/includes/header.php'; echo '<section class="mx-auto max-w-3xl px-5 py-24 text-center"><h1 class="font-serif text-4xl">Story not found</h1><a class="mt-6 inline-block underline" href="' . e(url('blog.php')) . '">Back to the journal</a></section>'; require __DIR__ . '/includes/footer.php'; exit; }
$articleUrl = absolute_url('article.php?slug=' . rawurlencode($post['slug']));
$related = latest_journal_posts(3, (int) $post['id']);
// Each category points readers to the part of the business that helps them act on the article.
$nextStep = match ($post['category_slug'] ?? '') {
    'fabric-notes' => ['fa-swatchbook', '#16646E', 'Find your fabric', 'Browse bouclé, linen, velvet and more, each shown on real furniture.', 'Shop the collection', 'shop.php'],
    'upholstery' => ['fa-couch', '#1F5B43', 'Give your piece a new life', 'Send us a photo and we’ll advise on fabric, padding and a clear quote.', 'Explore upholstery services', 'services.php'],
    default => ['fa-compass-drafting', '#A9461E', 'Plan your room with us', 'Book a consultation online or in person and leave with clear next steps.', 'Book a consultation', 'consultations.php'],
};
$pageTitle = $post['title'] . ' | Yucca Fabrics';
$description = $post['excerpt'] ?: $post['title'];
$structuredData = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $post['title'], 'datePublished' => $post['published_at'], 'description' => $description, 'image' => absolute_url('uploads/blog/' . rawurlencode(basename((string) $post['image']))), 'publisher' => ['@type' => 'Organization', 'name' => APP_NAME], 'mainEntityOfPage' => $articleUrl];
if (!$post['image']) unset($structuredData['image']);
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream">
    <div class="mx-auto max-w-3xl px-5 pb-10 pt-10 text-center sm:pt-14">
        <a class="inline-flex items-center gap-2 text-sm font-medium text-forest" href="<?= e(url('blog.php')) ?>"><i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>The journal</a>
        <p class="eyebrow mt-8"><?= e($post['category_name'] ?? 'Yucca notes') ?></p>
        <h1 class="mt-3 font-serif text-3xl leading-tight sm:text-5xl"><?= e($post['title']) ?></h1>
        <?php if ($post['excerpt']): ?><p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-gray-600"><?= e($post['excerpt']) ?></p><?php endif; ?>
        <p class="mt-6 inline-flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs text-gray-500"><span><i class="fa-regular fa-calendar mr-1.5" aria-hidden="true"></i><?= e(date('j F Y', strtotime($post['published_at']))) ?></span><span><i class="fa-regular fa-clock mr-1.5" aria-hidden="true"></i><?= read_minutes($post['body']) ?> min read</span></p>
    </div>
</section>
<article class="mx-auto max-w-4xl px-5">
    <?php if ($post['image']): ?><img class="-mt-2 aspect-[16/9] w-full rounded-3xl object-cover shadow-xl" src="<?= e(journal_image($post['image'])) ?>" alt=""><?php endif; ?>
    <div class="journal-body mx-auto mt-10 max-w-2xl"><?= journal_body_html($post['body']) ?></div>
    <div class="mx-auto mt-10 flex max-w-2xl flex-wrap items-center gap-3 border-t border-ink/10 pt-6 text-sm">
        <span class="font-semibold">Share this story</span>
        <a class="social-share" href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode($articleUrl)) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i>Facebook</a>
        <a class="social-share" href="https://wa.me/?text=<?= e(rawurlencode($post['title'] . ' ' . $articleUrl)) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp</a>
    </div>
    <?php [$icon, $colour, $heading, $text, $label, $href] = $nextStep; ?>
    <aside class="relative mx-auto mt-12 flex max-w-2xl flex-col gap-6 overflow-hidden rounded-3xl p-8 text-white sm:flex-row sm:items-center" style="background-color:<?= e($colour) ?>">
        <?= yucca_sprig_svg('pointer-events-none absolute -bottom-10 -right-8 h-48 w-48 text-white/10') ?>
        <span class="relative grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white text-xl" style="color:<?= e($colour) ?>"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span>
        <div class="relative flex-1"><h2 class="font-serif text-2xl"><?= e($heading) ?></h2><p class="mt-1 text-sm leading-6 text-white/90"><?= e($text) ?></p></div>
        <a href="<?= e(url($href)) ?>" class="relative inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold" style="color:<?= e($colour) ?>"><?= e($label) ?> <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a>
    </aside>
</article>
<?php if ($related): ?>
<section class="mx-auto max-w-7xl px-5 py-20">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">Keep reading</p><h2 class="mt-3 font-serif text-3xl sm:text-4xl">More from the journal</h2></div><a class="inline-flex items-center gap-2 text-sm font-semibold text-forest" href="<?= e(url('blog.php')) ?>">All stories <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a></div>
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($related as $post): require __DIR__ . '/includes/journal-card.php'; endforeach; ?></div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
