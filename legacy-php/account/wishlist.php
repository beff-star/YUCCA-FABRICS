<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$stmt = db()->prepare("SELECT p.*,c.name AS category_name FROM wishlists w JOIN wishlist_items wi ON wi.wishlist_id=w.id JOIN products p ON p.id=wi.product_id LEFT JOIN categories c ON c.id=p.category_id WHERE w.user_id=? AND p.is_active=1 ORDER BY wi.created_at DESC");
$stmt->execute([current_user()['id']]);
$products = $stmt->fetchAll();
$pageTitle = 'Saved fabrics | Yucca Fabrics';
$accountActive = 'wishlist';
$accountTitle = 'Your favourites';
$accountIntro = 'Fabrics you’ve saved for later. Tap the heart on any fabric to add it here.';
require __DIR__ . '/../includes/account-start.php';
?>
<?php if ($products): ?>
    <div class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3">
        <?php foreach ($products as $product): ?><div class="relative flex flex-col"><?php require __DIR__ . '/../includes/product-card.php'; ?><button data-remove-wishlist="<?= (int) $product['id'] ?>" class="mt-2 inline-flex items-center justify-center gap-2 rounded-full px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-white hover:text-clay"><i class="fa-solid fa-heart-crack" aria-hidden="true"></i>Remove<span class="sr-only"> <?= e($product['name']) ?> from favourites</span></button></div><?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-ink/5"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-2xl text-clay"><i class="fa-regular fa-heart" aria-hidden="true"></i></span><h2 class="mt-5 font-serif text-2xl">No saved fabrics yet</h2><p class="mt-2 text-sm text-gray-600">Save the fabrics you love and compare them here later.</p><a class="mt-6 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white" href="<?= e(url('shop.php')) ?>">Explore the collection</a></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/account-end.php'; ?>
