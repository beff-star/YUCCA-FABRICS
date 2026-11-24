<?php
require_once __DIR__ . '/includes/functions.php';
$last = $_SESSION['last_order'] ?? null;
if (!$last) redirect('account/orders.php');
$stmt = db()->prepare('SELECT * FROM orders WHERE id=? AND order_number=?');
$stmt->execute([(int)$last['id'],(string)$last['number']]);
$order=$stmt->fetch();
if (!$order || (current_user() && (int)$order['user_id'] !== (int)current_user()['id'])) { unset($_SESSION['last_order']); http_response_code(404); $pageTitle = 'Order not found | Yucca Fabrics'; require __DIR__ . '/includes/header.php'; echo '<section class="mx-auto max-w-3xl px-5 py-24 text-center"><h1 class="font-serif text-4xl">We couldn’t find that order</h1><a class="mt-6 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white" href="' . e(url('account/orders.php')) . '">View my orders</a></section>'; require __DIR__ . '/includes/footer.php'; exit; }
$items=db()->prepare('SELECT product_name,quantity,unit_price,line_total FROM order_items WHERE order_id=?');$items->execute([$order['id']]);
$pageTitle='Order received | Yucca Fabrics';
$nextSteps = [
    ['fa-envelope-open-text', 'We confirm', 'We check your order and stock, then confirm the details with you.'],
    ['fa-scissors', 'We prepare', 'Your fabric is measured, cut and packed with care.'],
    ['fa-truck-fast', 'We deliver', 'We contact you to arrange delivery to your door.'],
];
require __DIR__.'/includes/header.php';
?>
<section class="relative overflow-hidden bg-cream">
    <?= yucca_sprig_svg('pointer-events-none absolute -right-12 -top-12 hidden h-64 w-64 text-olive/10 sm:block') ?>
    <div class="relative mx-auto max-w-3xl px-5 py-12 text-center">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-forest text-2xl text-white shadow-lg" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
        <p class="eyebrow mt-6">Thank you</p>
        <h1 class="mt-2 font-serif text-3xl sm:text-4xl">Your order is in good hands.</h1>
        <p class="mt-3 text-sm text-gray-600">Order <strong class="text-ink"><?= e($order['order_number']) ?></strong>. We’ll use your contact details to share updates.</p>
        <div class="flex justify-center"><?= checkout_steps(3) ?></div>
    </div>
</section>
<section class="mx-auto max-w-3xl px-5 py-10">
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 sm:p-8">
        <h2 class="font-serif text-2xl">Order details</h2>
        <ul class="mt-4 divide-y divide-ink/10"><?php foreach($items->fetchAll() as $item): ?><li class="flex justify-between gap-5 py-3 text-sm"><span><?= e($item['product_name']) ?> <span class="text-gray-500">× <?= (int)$item['quantity'] ?></span></span><span><?= e(money($item['line_total'])) ?></span></li><?php endforeach; ?></ul>
        <div class="mt-2 flex justify-between border-t border-ink/10 pt-4 text-lg font-semibold"><span>Total</span><span><?= e(money($order['total'])) ?></span></div>
        <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600"><span>Payment: <?= e(payment_method_label($order['payment_method'])) ?></span><?= status_badge($order['payment_status']) ?></p>
        <?php if($order['payment_method']==='bank_transfer'): ?><div class="mt-6 rounded-2xl bg-cream p-5 text-sm"><h3 class="flex items-center gap-2 font-semibold"><i class="fa-solid fa-building-columns text-sage" aria-hidden="true"></i>Bank transfer details</h3><p class="mt-2 whitespace-pre-line text-gray-600"><?= e(setting('bank_details','Please contact us for current bank transfer details.')) ?></p><p class="mt-2 text-xs text-gray-500">Use your order number as the payment reference. The transfer will be verified before your order is marked paid.</p><?php if(current_user()): ?><form data-ajax-form action="<?= e(url('api/payment-reference.php')) ?>" method="post" class="mt-4 flex flex-wrap gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><label class="sr-only" for="payment-reference">Bank transfer reference</label><input id="payment-reference" name="reference" class="form-control max-w-sm" placeholder="Payment reference" maxlength="80" required><button class="rounded-full bg-forest px-5 py-2 text-sm font-semibold text-white">Submit reference</button></form><?php endif; ?></div><?php elseif($order['payment_method']==='mpesa'&&current_user()): ?><form data-ajax-form action="<?= e(url('payment/mpesa.php')) ?>" method="post" class="mt-6 grid gap-3 rounded-2xl bg-cream p-5 sm:grid-cols-[1fr_auto]"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><label class="text-sm font-medium">M-Pesa phone number<input class="form-control mt-1" name="phone" type="tel" placeholder="07XX XXX XXX" required></label><button class="self-end rounded-full bg-forest px-5 py-3 text-sm font-semibold text-white">Request STK push</button></form><?php endif; ?>
    </div>
    <h2 class="mt-12 text-center font-serif text-2xl">What happens next</h2>
    <ol class="mt-6 grid gap-4 sm:grid-cols-3"><?php foreach ($nextSteps as $index => [$icon, $title, $text]): ?><li class="rounded-3xl bg-white p-6 text-center shadow-sm ring-1 ring-ink/5"><span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-cream text-lg text-forest"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><h3 class="mt-4 font-semibold"><?= $index + 1 ?>. <?= e($title) ?></h3><p class="mt-2 text-sm leading-6 text-gray-600"><?= e($text) ?></p></li><?php endforeach; ?></ol>
    <div class="mt-10 flex flex-wrap justify-center gap-3"><a class="rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive" href="<?= e(url(current_user()?'account/orders.php':'shop.php')) ?>"><?= current_user()?'View my orders':'Continue shopping' ?></a><?php if (current_user()): ?><a class="rounded-full border border-forest/30 px-6 py-3 text-sm font-semibold text-forest transition hover:bg-cream" href="<?= e(url('shop.php')) ?>">Continue shopping</a><?php endif; ?></div>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
