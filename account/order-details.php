<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$stmt = db()->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$stmt->execute([(int) ($_GET['id'] ?? 0), current_user()['id']]);
$order = $stmt->fetch();
$items = [];
if ($order) {
    $stmt = db()->prepare('SELECT oi.product_name,oi.sku,oi.quantity,oi.unit_price,oi.line_total,p.slug,p.image FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?');
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
} else {
    http_response_code(404);
}
// Progress shown to the customer; cancelled orders show a notice instead.
$stages = ['pending' => 'Received', 'confirmed' => 'Confirmed', 'processing' => 'Preparing', 'shipped' => 'On its way', 'delivered' => 'Delivered'];
$stageIndex = $order ? array_search($order['status'], array_keys($stages), true) : false;
$pageTitle = $order ? 'Order ' . $order['order_number'] . ' | Yucca Fabrics' : 'Order not found | Yucca Fabrics';
$accountActive = 'orders';
$accountTitle = $order ? 'Order ' . $order['order_number'] : 'Order not found';
$accountIntro = $order ? 'Placed on ' . date('j F Y', strtotime($order['created_at'])) . '.' : '';
$accountAction = ['All orders', 'account/orders.php', 'fa-arrow-left'];
require __DIR__ . '/../includes/account-start.php';
?>
<?php if (!$order): ?>
    <div class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-ink/5"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-2xl text-forest"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span><h2 class="mt-5 font-serif text-2xl">We couldn’t find that order</h2><p class="mt-2 text-sm text-gray-600">It may belong to a different account. Your orders are listed on the orders page.</p></div>
<?php else: ?>
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5">
        <?php if ($stageIndex !== false): ?>
            <ol class="grid grid-cols-5 gap-2 text-center text-[11px] font-medium sm:text-xs" aria-label="Order progress">
                <?php $index = 0; foreach ($stages as $status => $label): $done = $index <= $stageIndex; ?>
                    <li class="flex flex-col items-center gap-2"<?= $index === $stageIndex ? ' aria-current="step"' : '' ?>><span class="grid h-9 w-9 place-items-center rounded-full <?= $done ? 'bg-forest text-white' : 'bg-cream text-gray-500' ?>"><i class="fa-solid <?= $done ? 'fa-check' : 'fa-circle' ?> <?= $done ? 'text-sm' : 'text-[6px]' ?>" aria-hidden="true"></i></span><span class="<?= $done ? 'text-ink' : 'text-gray-500' ?>"><?= e($label) ?></span></li>
                <?php $index++; endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="flex items-center gap-3 rounded-2xl bg-[#F6E1DE] p-4 text-sm text-[#7A2E22]"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>This order was cancelled. Please contact us if you have any questions.</p>
        <?php endif; ?>
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_300px]">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5">
            <h2 class="font-serif text-xl">Items</h2>
            <ul class="mt-2 divide-y divide-ink/10">
                <?php foreach ($items as $item): ?>
                    <li class="flex items-center gap-4 py-4"><img src="<?= e(product_image($item['image'])) ?>" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover"><div class="min-w-0 flex-1"><?php if ($item['slug']): ?><a class="font-semibold hover:text-forest" href="<?= e(url('product-details.php?slug=' . rawurlencode($item['slug']))) ?>"><?= e($item['product_name']) ?></a><?php else: ?><p class="font-semibold"><?= e($item['product_name']) ?></p><?php endif; ?><p class="mt-1 text-xs text-gray-500"><?= e($item['sku']) ?> · <?= (int) $item['quantity'] ?> × <?= e(money($item['unit_price'])) ?></p></div><span class="font-semibold"><?= e(money($item['line_total'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <dl class="mt-2 grid gap-2 border-t border-ink/10 pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-gray-600">Subtotal</dt><dd><?= e(money($order['subtotal'])) ?></dd></div>
                <?php if ((float) $order['discount'] > 0): ?><div class="flex justify-between"><dt class="text-gray-600">Discount</dt><dd>−<?= e(money($order['discount'])) ?></dd></div><?php endif; ?>
                <div class="flex justify-between"><dt class="text-gray-600">Tax</dt><dd><?= e(money($order['tax'])) ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-600">Delivery</dt><dd><?= e(money($order['delivery_fee'])) ?></dd></div>
                <div class="flex justify-between border-t border-ink/10 pt-3 text-base font-semibold"><dt>Total</dt><dd><?= e(money($order['total'])) ?></dd></div>
            </dl>
        </div>
        <aside class="grid content-start gap-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5"><h2 class="font-serif text-xl">Delivery</h2><p class="mt-3 text-sm leading-6 text-gray-600"><strong class="text-ink"><?= e($order['customer_name']) ?></strong><br><?= e($order['address_line']) ?><br><?= e($order['city']) ?><?= $order['county'] ? ', ' . e($order['county']) : '' ?><br><?= e($order['phone']) ?></p><?php if ($order['delivery_instructions']): ?><p class="mt-3 rounded-2xl bg-cream p-3 text-xs leading-5 text-gray-600"><?= e($order['delivery_instructions']) ?></p><?php endif; ?></div>
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5"><h2 class="font-serif text-xl">Payment</h2><p class="mt-3 text-sm text-gray-600"><?= e(payment_method_label($order['payment_method'])) ?></p><div class="mt-3"><?= status_badge($order['payment_status']) ?></div></div>
            <a href="<?= e(url('contact.php')) ?>" class="flex items-center gap-3 rounded-3xl bg-forest p-5 text-sm text-white transition hover:bg-olive"><i class="fa-solid fa-headset text-lg text-wheat" aria-hidden="true"></i><span><strong class="block">Need help with this order?</strong>Contact us and quote <?= e($order['order_number']) ?>.</span></a>
        </aside>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/account-end.php'; ?>
