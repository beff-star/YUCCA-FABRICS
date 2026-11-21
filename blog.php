<?php
require_once __DIR__ . '/includes/functions.php';
$search = trim((string) ($_GET['q'] ?? ''));
$categories = db()->query('SELECT bc.id, bc.name, bc.slug FROM blog_categories bc WHERE EXISTS (SELECT 1 FROM blog_posts bp WHERE bp.category_id=bc.id AND bp.status=\'published\' AND bp.published_at<=NOW()) ORDER BY bc.name')->fetchAll();
$categorySlug = (string) ($_GET['category'] ?? '');
$activeCategory = array_values(array_filter($categories, fn(array $category): bool => $category['slug'] === $categorySlug))[0] ?? null;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$limit = 9;
$where = "bp.status='published' AND bp.published_at<=NOW()";
$params = [];
if ($search !== '') { $where .= ' AND (bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.body LIKE ?)'; array_push($params, "%{$search}%", "%{$search}%", "%{$search}%"); }
if ($activeCategory) { $where .= ' AND bp.category_id=?'; $params[] = (int) $activeCategory['id']; }
$stmt = db()->prepare("SELECT COUNT(*) FROM blog_posts bp WHERE {$where}"); $stmt->execute($params);
$pages = max(1, (int) ceil((int) $stmt->fetchColumn() / $limit)); $page = min($pages, $page);
$stmt = db()->prepare("SELECT bp.*, bc.name AS category_name FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id=bp.category_id WHERE {$where} ORDER BY bp.published_at DESC LIMIT {$limit} OFFSET " . (($page - 1) * $limit));
$stmt->execute($params); $posts = $stmt->fetchAll();
// The newest story is featured on the unfiltered first page.
$featured = ($page === 1 && $search === '' && !$activeCategory && $posts) ? array_shift($posts) : null;
$listUrl = fn(array $query): string => url('blog.php' . ($query ? '?' . http_build_query($query) : ''));
$pageTitle = 'The journal: notes on fabrics and home | Yucca Fabrics';
$description = 'Ideas and practical advice on choosing fabrics, caring for upholstery and making a home feel like yours.';
require __DIR__ . '/includes/header.php';
?>
<section class="relative overflow-hidden bg-cream px-5 py-16 text-center">
    <?= yucca_sprig_svg('pointer-events-none absolute -left-16 -top-10 hidden h-72 w-72 text-olive/10 sm:block') ?>
    <?= yucca_sprig_svg('pointer-events-none absolute -bottom-16 -right-16 hidden h-72 w-72 text-clay/10 sm:block') ?>
    <div class="relative"><p class="eyebrow">The Yucca journal</p><h1 class="mt-3 font-serif text-4xl sm:text-5xl">Notes on making a home</h1><p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-gray-600">Practical ideas for choosing fabrics, caring for furniture and making a space feel a little more like you.</p>
        <form class="mx-auto mt-8 flex max-w-md gap-2" role="search"><label class="sr-only" for="blog-search">Search the journal</label><input id="blog-search" class="form-control rounded-full px-5" type="search" name="q" value="<?= e($search) ?>" placeholder="Search the journal"><?php if ($activeCategory): ?><input type="hidden" name="category" value="<?= e($activeCategory['slug']) ?>"><?php endif; ?><button class="shrink-0 rounded-full bg-forest px-5 text-sm font-semibold text-white transition hover:bg-olive" aria-label="Search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button></form>
    </div>
</section>
<section class="mx-auto max-w-7xl px-5 py-12">
    <?php if ($categories): ?><nav class="mb-10 flex flex-wrap justify-center gap-2" aria-label="Journal categories"><a class="rounded-full px-4 py-2 text-xs font-semibold transition <?= !$activeCategory ? 'bg-forest text-white' : 'bg-white text-ink hover:bg-cream' ?>" href="<?= e($listUrl(array_filter(['q' => $search]))) ?>"<?= !$activeCategory ? ' aria-current="page"' : '' ?>>All stories</a><?php foreach ($categories as $category): $isActive = $activeCategory && $activeCategory['id'] === $category['id']; ?><a class="rounded-full px-4 py-2 text-xs font-semibold transition <?= $isActive ? 'bg-forest text-white' : 'bg-white text-ink hover:bg-cream' ?>" href="<?= e($listUrl(array_filter(['category' => $category['slug'], 'q' => $search]))) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= e($category['name']) ?></a><?php endforeach; ?></nav><?php endif; ?>
    <?php if ($search !== ''): ?><p class="mb-6 text-sm text-gray-600">Results for “<?= e($search) ?>” · <a class="underline" href="<?= e($listUrl(array_filter(['category' => $activeCategory['slug'] ?? '']))) ?>">Clear search</a></p><?php endif; ?>
    <?php if ($featured): $featuredUrl = url('article.php?slug=' . rawurlencode($featured['slug'])); ?>
        <article class="group mb-10 grid overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-ink/5 lg:grid-cols-2">
            <a href="<?= e($featuredUrl) ?>" class="relative block aspect-[16/10] overflow-hidden lg:aspect-auto lg:min-h-[420px]" tabindex="-1" aria-hidden="true"><img class="absolute inset-0 h-full w-full object-cover motion-safe:transition motion-safe:duration-700 motion-safe:group-hover:scale-105" src="<?= e(journal_image($featured['image'])) ?>" alt=""><span class="absolute left-5 top-5 rounded-full bg-clay px-3 py-1 text-[10px] font-semibold uppercase tracking-[.16em] text-white">Latest story</span></a>
            <div class="flex flex-col justify-center p-8 sm:p-12"><p class="eyebrow"><?= e($featured['category_name'] ?? 'Yucca notes') ?></p><h2 class="mt-3 font-serif text-3xl leading-tight sm:text-4xl"><a class="transition hover:text-forest" href="<?= e($featuredUrl) ?>"><?= e($featured['title']) ?></a></h2><p class="mt-4 text-sm leading-7 text-gray-600"><?= e($featured['excerpt'] ?: strip_tags($featured['body'])) ?></p><p class="mt-5 text-xs text-gray-500"><?= e(date('j F Y', strtotime($featured['published_at']))) ?> · <?= read_minutes($featured['body']) ?> min read</p><a href="<?= e($featuredUrl) ?>" class="mt-7 inline-flex w-fit items-center gap-2 rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive">Read the story <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a></div>
        </article>
    <?php endif; ?>
    <?php if ($posts): ?>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($posts as $post): require __DIR__ . '/includes/journal-card.php'; endforeach; ?></div>
    <?php elseif (!$featured): ?>
        <div class="rounded-3xl bg-white px-6 py-16 text-center"><i class="fa-regular fa-newspaper text-3xl text-sage" aria-hidden="true"></i><h2 class="mt-4 font-serif text-2xl"><?= $search !== '' || $activeCategory ? 'No stories match yet' : 'Good stories are on their way' ?></h2><p class="mt-3 text-sm text-gray-600">We’ll be sharing ideas from the world of fabrics, furniture and home.</p><?php if ($search !== '' || $activeCategory): ?><a class="mt-5 inline-block text-sm font-semibold underline" href="<?= e(url('blog.php')) ?>">See all stories</a><?php endif; ?></div>
    <?php endif; ?>
    <?php if ($pages > 1): ?><nav class="mt-12 flex justify-center gap-2" aria-label="Journal pages"><?php for ($number = 1; $number <= $pages; $number++): ?><a class="grid h-10 w-10 place-items-center rounded-full text-sm font-semibold <?= $number === $page ? 'bg-forest text-white' : 'bg-white hover:bg-cream' ?>" href="<?= e($listUrl(array_filter(['category' => $activeCategory['slug'] ?? '', 'q' => $search, 'page' => $number > 1 ? $number : '']))) ?>"<?= $number === $page ? ' aria-current="page"' : '' ?>><?= $number ?></a><?php endfor; ?></nav><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
