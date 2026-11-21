<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$stmt = db()->prepare('SELECT o.id,o.order_number,o.total,o.status,o.payment_status,o.created_at,(SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_id=o.id) AS item_count FROM orders o WHERE o.user_id=? ORDER BY o.created_at DESC');
$stmt->execute([current_user()['id']]);
$orders = $stmt->fetchAll();
$pageTitle = 'My orders | Yucca Fabrics';
$accountActive = 'orders';
$accountTitle = 'Order history';
$accountIntro = 'Track your orders and payments, and see what you’ve bought.';
require __DIR__ . '/../includes/account-start.php';
?>
<?php if (!$orders): ?>
    <div class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-ink/5"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-2xl text-forest"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span><h2 class="mt-5 font-serif text-2xl">No orders yet</h2><p class="mt-2 text-sm text-gray-600">When you place an order, you’ll be able to follow it here.</p><a class="mt-6 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white" href="<?= e(url('shop.php')) ?>">Explore the collection</a></div>
<?php else: ?>
    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-ink/5">
        <div class="hidden grid-cols-[1.4fr_1fr_1fr_1fr_1fr_auto] gap-4 bg-cream px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600 md:grid" aria-hidden="true"><span>Order</span><span>Date</span><span>Total</span><span>Status</span><span>Payment</span><span class="w-16"></span></div>
        <ul class="divide-y divide-ink/10">
            <?php foreach ($orders as $order): ?>
                <li class="grid grid-cols-2 items-center gap-3 px-6 py-5 md:grid-cols-[1.4fr_1fr_1fr_1fr_1fr_auto] md:gap-4">
                    <span class="col-span-2 md:col-span-1"><span class="block font-semibold"><?= e($order['order_number']) ?></span><span class="text-xs text-gray-500"><?= (int) $order['item_count'] ?> item<?= (int) $order['item_count'] === 1 ? '' : 's' ?></span></span>
                    <span class="text-sm text-gray-600"><span class="text-xs text-gray-500 md:hidden">Placed </span><?= e(date('j M Y', strtotime($order['created_at']))) ?></span>
                    <span class="text-right font-semibold md:text-left"><?= e(money($order['total'])) ?></span>
                    <span><?= status_badge($order['status']) ?></span>
                    <span class="text-right md:text-left"><?= status_badge($order['payment_status']) ?></span>
                    <a class="col-span-2 inline-flex w-full items-center justify-center gap-2 rounded-full border border-forest/30 px-4 py-2 text-sm font-semibold text-forest transition hover:bg-forest hover:text-white md:col-span-1 md:w-auto" href="<?= e(url('account/order-details.php?id=' . (int) $order['id'])) ?>">View<span class="sr-only"> order <?= e($order['order_number']) ?></span></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/account-end.php'; ?>
