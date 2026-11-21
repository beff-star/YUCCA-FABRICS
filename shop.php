<?php
require_once __DIR__ . '/includes/functions.php';
$categories = db()->query('SELECT id,name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
$search = trim((string) ($_GET['q'] ?? ''));
$category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$colour = trim((string) ($_GET['colour'] ?? ''));
$material = trim((string) ($_GET['material'] ?? ''));
$availability = (string) ($_GET['availability'] ?? '');
$minimumRating = filter_input(INPUT_GET, 'rating', FILTER_VALIDATE_INT) ?: 0;
$sort = (string) ($_GET['sort'] ?? 'newest');
$sorts = ['newest' => 'p.created_at DESC', 'name' => 'p.name ASC', 'popularity' => 'popularity DESC', 'rating' => 'ratings.average_rating DESC'];
$where = ['p.is_active=1'];
$params = [];
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)'; array_push($params, "%{$search}%", "%{$search}%", "%{$search}%"); }
if ($category > 0) { $where[] = 'p.category_id=?'; $params[] = $category; }
if ($colour !== '') { $where[] = 'p.colour=?'; $params[] = $colour; }
if ($material !== '') { $where[] = 'p.material=?'; $params[] = $material; }
if ($availability === 'in_stock') $where[] = 'p.stock>0';
if ($availability === 'out_of_stock') $where[] = 'p.stock=0';
if ($minimumRating >= 1 && $minimumRating <= 5) { $where[] = 'ratings.average_rating>=?'; $params[] = $minimumRating; }
$whereSql = implode(' AND ', $where);
$ratingJoin = " LEFT JOIN (SELECT product_id,AVG(rating) AS average_rating FROM product_reviews WHERE status='approved' GROUP BY product_id) ratings ON ratings.product_id=p.id";
$countStmt = db()->prepare("SELECT COUNT(*) FROM products p{$ratingJoin} WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 12;
$pages = max(1, (int) ceil($total / $perPage));
$page = max(1, min($pages, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$stmt = db()->prepare("SELECT p.*,c.name AS category_name,ratings.average_rating,(SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.product_id=p.id) AS popularity FROM products p LEFT JOIN categories c ON c.id=p.category_id{$ratingJoin} WHERE {$whereSql} ORDER BY " . ($sorts[$sort] ?? $sorts['newest']) . " LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage));
$stmt->execute($params);
$products = $stmt->fetchAll();
$colours=db()->query("SELECT DISTINCT colour FROM products WHERE is_active=1 AND colour IS NOT NULL AND colour<>'' ORDER BY colour")->fetchAll(PDO::FETCH_COLUMN);
$materials=db()->query("SELECT DISTINCT material FROM products WHERE is_active=1 AND material IS NOT NULL AND material<>'' ORDER BY material")->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'Shop fabrics | Yucca Fabrics';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream px-5 py-14 text-center"><p class="eyebrow">The Yucca collection</p><h1 class="mt-3 font-serif text-5xl">Fabrics to live with</h1><p class="mx-auto mt-4 max-w-xl text-sm leading-6 text-gray-600">Touchably beautiful textiles, chosen for real homes and the everyday moments inside them.</p></section>
<section class="mx-auto max-w-7xl px-5 py-12"><form class="mb-9 grid gap-3 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-ink/5 sm:grid-cols-2 lg:grid-cols-4"><label class="relative text-sm">Search<input id="search" class="form-control mt-1" type="search" name="q" value="<?= e($search) ?>" placeholder="Fabric, colour, SKU…" autocomplete="off"><div id="search-suggestions" class="absolute left-0 right-0 top-full z-30 hidden bg-white shadow-lg" role="listbox"></div></label><label class="text-sm">Category<select class="form-control mt-1" name="category"><option value="0">All categories</option><?php foreach ($categories as $item): ?><option value="<?= (int) $item['id'] ?>" <?= $category === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></label><label class="text-sm">Colour<select class="form-control mt-1" name="colour"><option value="">All colours</option><?php foreach($colours as $option): ?><option <?= $colour===$option?'selected':'' ?>><?= e($option) ?></option><?php endforeach; ?></select></label><label class="text-sm">Material<select class="form-control mt-1" name="material"><option value="">All materials</option><?php foreach($materials as $option): ?><option <?= $material===$option?'selected':'' ?>><?= e($option) ?></option><?php endforeach; ?></select></label><label class="text-sm">Availability<select class="form-control mt-1" name="availability"><option value="">Any</option><option value="in_stock" <?= $availability==='in_stock'?'selected':'' ?>>In stock</option><option value="out_of_stock" <?= $availability==='out_of_stock'?'selected':'' ?>>Out of stock</option></select></label><label class="text-sm">Sort<select class="form-control mt-1" name="sort"><option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option><option value="popularity" <?= $sort==='popularity'?'selected':'' ?>>Popularity</option><option value="rating" <?= $sort==='rating'?'selected':'' ?>>Rating</option><option value="name" <?= $sort==='name'?'selected':'' ?>>Name</option></select></label><label class="text-sm">Minimum rating<select class="form-control mt-1" name="rating"><option value="0">Any rating</option><?php for($star=5;$star>=1;$star--): ?><option value="<?= $star ?>" <?= $minimumRating===$star?'selected':'' ?>><?= $star ?>+ stars</option><?php endfor; ?></select></label><button class="self-end rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white">Find fabrics</button></form>
<p class="mb-5 text-xs text-gray-500"><?= $total ?> <?= $total === 1 ? 'piece' : 'pieces' ?> in the collection</p>
<?php if ($products): ?><div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4"><?php foreach ($products as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?></div><?php else: ?><div class="rounded-3xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-ink/5"><h2 class="font-serif text-2xl">Nothing in the collection just yet</h2><p class="mt-3 text-sm text-gray-600">Try another search or get in touch and we’ll help you find it.</p><a class="mt-5 inline-block underline" href="<?= e(url('shop.php')) ?>">Clear filters</a></div><?php endif; ?>
<?php if ($pages > 1): ?><nav class="mt-10 flex justify-center gap-2" aria-label="Pagination"><?php for ($i=1;$i<=$pages;$i++): ?><a class="rounded-full px-4 py-2 text-sm <?= $i===$page?'bg-forest text-white':'bg-white' ?>" href="?<?= e(http_build_query(array_merge($_GET,['page'=>$i]))) ?>"<?= $i===$page?' aria-current="page"':'' ?>><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
