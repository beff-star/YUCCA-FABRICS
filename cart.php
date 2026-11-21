<?php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][(int) $_POST['remove']]);
    } elseif (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $id => $quantity) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if (!$id || !$quantity || $quantity < 1) continue;
            $check = db()->prepare('SELECT stock FROM products WHERE id=? AND is_active=1');
            $check->execute([$id]);
            $stock = $check->fetchColumn();
            if ($stock !== false) $_SESSION['cart'][$id] = min((int) $stock, $quantity);
        }
    }
    persist_cart();
    redirect('cart.php');
}
$items = cart_items();
$subtotal = array_sum(array_column($items, 'line_total'));
$coupon = !empty($_SESSION['coupon']) ? coupon_for_checkout((string)$_SESSION['coupon'],$subtotal,current_user()?(int)current_user()['id']:null) : null;
$pageTitle = 'Your shopping bag | Yucca Fabrics';
$suggestions = $items ? [] : db()->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND p.stock>0 ORDER BY p.is_featured DESC, p.id LIMIT 4")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream">
    <div class="mx-auto max-w-7xl px-5 py-10"><p class="eyebrow">The things you love</p><h1 class="mt-2 font-serif text-3xl sm:text-4xl">Your shopping bag</h1><?= checkout_steps(1) ?></div>
</section>
<section class="mx-auto max-w-7xl px-5 py-10">
<?php if (!$items): ?>
    <div class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-ink/5"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-2xl text-forest"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span><h2 class="mt-5 font-serif text-2xl">Your bag is waiting for something lovely</h2><p class="mt-2 text-sm text-gray-600">Explore the collection and find a fabric that feels like you.</p><a class="mt-6 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive" href="<?= e(url('shop.php')) ?>">Browse fabrics</a></div>
    <?php if ($suggestions): ?><h2 class="mt-14 font-serif text-2xl sm:text-3xl">You might like</h2><div class="mt-6 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4"><?php foreach ($suggestions as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?></div><?php endif; ?>
<?php else: ?>
    <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-ink/5 sm:p-8">
            <form method="post" data-auto-submit>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <ul class="divide-y divide-ink/10">
                <?php foreach ($items as $item): $productUrl = url('product-details.php?slug=' . rawurlencode($item['slug'])); ?>
                    <li class="grid grid-cols-[88px_1fr] gap-4 py-5 first:pt-0 sm:grid-cols-[112px_1fr_auto]">
                        <a href="<?= e($productUrl) ?>" class="block overflow-hidden rounded-2xl bg-cream"><img class="aspect-square w-full object-cover" src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['name']) ?>"></a>
                        <div class="min-w-0">
                            <a class="font-serif text-lg hover:text-forest" href="<?= e($productUrl) ?>"><?= e($item['name']) ?></a>
                            <p class="mt-1 text-xs text-gray-500"><?= e(money($item['unit_price'])) ?> / <?= e($item['unit']) ?> · <?= (int) $item['stock'] ?> available</p>
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <label class="inline-flex items-center gap-2 text-xs font-medium">Qty (<?= e($item['unit']) ?>)<input class="form-control w-20 py-2 text-center" type="number" min="1" max="<?= (int) $item['stock'] ?>" name="quantities[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>"></label>
                                <button class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-cream hover:text-clay" name="remove" value="<?= (int) $item['id'] ?>"><i class="fa-regular fa-trash-can" aria-hidden="true"></i>Remove<span class="sr-only"> <?= e($item['name']) ?></span></button>
                            </div>
                        </div>
                        <p class="col-start-2 text-sm font-semibold sm:col-start-3 sm:text-right sm:text-base"><?= e(money($item['line_total'])) ?></p>
                    </li>
                <?php endforeach; ?>
                </ul>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-ink/10 pt-5"><a href="<?= e(url('shop.php')) ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-forest"><i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>Continue shopping</a><button class="rounded-full border border-forest/30 px-5 py-2.5 text-sm font-semibold text-forest transition hover:bg-cream" data-update-button>Update bag</button></div>
            </form>
        </div>
        <aside class="h-fit rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 lg:sticky lg:top-28">
            <h2 class="font-serif text-2xl">Summary</h2>
            <form data-ajax-form data-reload="true" action="<?= e(url('api/coupon.php')) ?>" method="post" class="mt-5 flex gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="sr-only" for="coupon-code">Coupon code</label><input id="coupon-code" class="form-control min-w-0" name="code" placeholder="Coupon code" value="<?= e($_SESSION['coupon'] ?? '') ?>"><button class="shrink-0 rounded-full border border-forest px-4 text-sm font-semibold text-forest transition hover:bg-forest hover:text-white">Apply</button></form>
            <?php if (!empty($_SESSION['coupon'])): ?><div class="mt-3 flex items-center justify-between gap-3 rounded-2xl bg-cream px-4 py-2.5 text-xs"><?php if ($coupon): ?><span><i class="fa-solid fa-tag mr-1.5 text-sage" aria-hidden="true"></i><?= e($_SESSION['coupon']) ?> · −<?= e(money($coupon['discount_amount'])) ?></span><?php else: ?><span class="text-[#7A2E22]">This coupon is no longer available.</span><?php endif; ?><form data-ajax-form data-reload="true" action="<?= e(url('api/coupon.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="remove"><button class="font-semibold underline">Remove</button></form></div><?php endif; ?>
            <dl class="mt-6 grid gap-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-600">Subtotal (<?= cart_count() ?> items)</dt><dd><?= e(money($subtotal)) ?></dd></div>
                <?php if ($coupon): ?><div class="flex justify-between"><dt class="text-gray-600">Coupon</dt><dd>−<?= e(money($coupon['discount_amount'])) ?></dd></div><?php endif; ?>
                <div class="flex justify-between"><dt class="text-gray-600">Delivery</dt><dd><?= e(money(DELIVERY_FEE)) ?></dd></div>
            </dl>
            <p class="mt-3 text-xs leading-5 text-gray-500">Tax (<?= e((string) round(TAX_RATE * 100)) ?>%) is added at checkout. Fabric is sold by the metre.</p>
            <a href="<?= e(url('checkout.php')) ?>" class="mt-6 flex items-center justify-center gap-2 rounded-full bg-forest px-6 py-3.5 text-sm font-semibold text-white shadow-md transition hover:bg-olive">Continue to checkout <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a>
            <ul class="mt-6 grid gap-3 border-t border-ink/10 pt-5 text-xs text-gray-600">
    <li class="flex items-center gap-3"><i class="fa-solid fa-mobile-screen-button w-4 text-center text-sage" aria-hidden="true"></i>Pay by M-Pesa, bank transfer or cash on delivery</li>
    <li class="flex items-center gap-3"><i class="fa-solid fa-truck-fast w-4 text-center text-sage" aria-hidden="true"></i>Delivery <?= e(money(DELIVERY_FEE)) ?>, confirmed with your order</li>
    <li class="flex items-center gap-3"><i class="fa-solid fa-headset w-4 text-center text-sage" aria-hidden="true"></i>Questions? <a class="font-semibold text-forest underline" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener">WhatsApp us</a></li>
</ul>
        </aside>
    </div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
