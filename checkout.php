<?php
require_once __DIR__ . '/includes/functions.php';
$items = cart_items();
if (!$items) { flash('error', 'Your bag is empty.'); redirect('shop.php'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address_line'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $county = trim((string) ($_POST['county'] ?? ''));
    $postal = trim((string) ($_POST['postal_code'] ?? ''));
    $instructions = trim((string) ($_POST['delivery_instructions'] ?? ''));
    $method = (string) ($_POST['payment_method'] ?? '');
    if ($name === '' || mb_strlen($name)>150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>190 || $phone === '' || strlen($phone)>40 || $address === '' || mb_strlen($address)>255 || $city === '' || mb_strlen($city)>100 || !in_array($method, ['cod','mpesa','bank_transfer'], true)) $errors[] = 'Please complete all required checkout details.';
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $freshItems = [];
            $subtotal = 0.0;
            foreach ($items as $item) {
                $lock = $pdo->prepare('SELECT id,name,sku,price,sale_price,stock,unit FROM products WHERE id=? AND is_active=1 FOR UPDATE');
                $lock->execute([$item['id']]);
                $product = $lock->fetch();
                if (!$product || (int) $product['stock'] < (int) $item['quantity']) {
                    throw new RuntimeException('A fabric in your bag no longer has enough stock. Please review your bag.');
                }
                $unitPrice = $product['sale_price'] !== null ? (float) $product['sale_price'] : (float) $product['price'];
                $lineTotal = $unitPrice * (int) $item['quantity'];
                $subtotal += $lineTotal;
                $freshItems[] = $product + ['quantity' => (int) $item['quantity'], 'unit_price' => $unitPrice, 'line_total' => $lineTotal];
            }
            $userId = current_user()['id'] ?? null;
            $coupon = !empty($_SESSION['coupon']) ? coupon_for_checkout((string)$_SESSION['coupon'], $subtotal, $userId ? (int)$userId : null, true) : null;
            if (!empty($_SESSION['coupon']) && !$coupon) throw new RuntimeException('Your coupon is no longer available for this order. Remove it from your bag and try again.');
            $discount = $coupon['discount_amount'] ?? 0.0;
            $delivery = (float) DELIVERY_FEE;
            $tax = round(($subtotal - $discount) * TAX_RATE, 2);
            $total = $subtotal - $discount + $delivery + $tax;
            $orderNumber = 'YF' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
            $order = $pdo->prepare("INSERT INTO orders (user_id,order_number,customer_name,email,phone,address_line,city,county,postal_code,delivery_instructions,subtotal,discount,tax,delivery_fee,total,payment_method,payment_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
            $order->execute([$userId,$orderNumber,$name,$email,$phone,$address,$city,$county ?: null,$postal ?: null,$instructions ?: null,$subtotal,$discount,$tax,$delivery,$total,$method]);
            $orderId = (int) $pdo->lastInsertId();
            $line = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,sku,unit_price,quantity,line_total) VALUES (?,?,?,?,?,?,?)');
            $stock = $pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
            foreach ($freshItems as $item) {
                $line->execute([$orderId,$item['id'],$item['name'],$item['sku'],$item['unit_price'],$item['quantity'],$item['line_total']]);
                $stock->execute([$item['quantity'],$item['id'],$item['quantity']]);
                if ($stock->rowCount() !== 1) throw new RuntimeException('Stock changed while placing your order. Please try again.');
            }
            $paymentStatus = 'pending';
            $pdo->prepare('INSERT INTO payments (order_id,method,amount,status) VALUES (?,?,?,?)')->execute([$orderId,$method,$total,$paymentStatus]);
            if ($coupon) $pdo->prepare('INSERT INTO coupon_usage (coupon_id,order_id,user_id) VALUES (?,?,?)')->execute([$coupon['id'],$orderId,$userId]);
            if ($userId) {
                $pdo->prepare('DELETE ci FROM cart_items ci JOIN cart c ON c.id=ci.cart_id WHERE c.user_id=?')->execute([$userId]);
            }
            $pdo->commit();
            $_SESSION['cart'] = [];
            unset($_SESSION['coupon']);
            $_SESSION['last_order'] = ['number' => $orderNumber, 'id' => $orderId];
            send_business_email($email, 'Yucca Fabrics order ' . $orderNumber, "Hello {$name},\n\nThank you for your order. We have received order {$orderNumber} for " . money($total) . ". We will contact you with delivery and payment updates.\n\nYucca Fabrics");
            redirect('order-success.php');
        } catch (PDOException $exception) {
            // PDOException extends RuntimeException: log database errors instead of showing them to customers.
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Checkout failed: ' . $exception->getMessage());
            $errors[] = 'We couldn’t place your order just now. Please try again, or contact us and we’ll help.';
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }
}
$pageTitle = 'Checkout | Yucca Fabrics';
$paymentOptions = [
    'mpesa' => ['fa-mobile-screen-button', 'M-Pesa', 'We’ll send a secure payment request to your phone after you place your order.'],
    'bank_transfer' => ['fa-building-columns', 'Bank transfer', 'Bank details and reference instructions appear after you place your order.'],
    'cod' => ['fa-money-bill-wave', 'Cash on delivery', 'Pay when your order arrives.'],
];
$selectedPayment = $_POST['payment_method'] ?? 'mpesa';
require __DIR__ . '/includes/header.php';
?>
<section class="bg-cream">
    <div class="mx-auto max-w-7xl px-5 py-10"><p class="eyebrow">Almost yours</p><h1 class="mt-2 font-serif text-3xl sm:text-4xl">Delivery &amp; payment</h1><?= checkout_steps(2) ?></div>
</section>
<section class="mx-auto max-w-7xl px-5 py-10">
<?php foreach ($errors as $error): ?><div class="mb-5"><?= form_alert($error) ?></div><?php endforeach; ?>
<form method="post" class="grid gap-8 lg:grid-cols-[1fr_400px]">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="grid content-start gap-6">
        <fieldset class="grid gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 sm:grid-cols-2 sm:p-8">
            <legend class="contents"><span class="flex items-center gap-3 font-serif text-2xl sm:col-span-2"><span class="grid h-8 w-8 place-items-center rounded-full bg-forest font-sans text-sm text-white">1</span>Your details</span></legend>
            <label class="text-sm font-medium sm:col-span-2">Full name<input class="form-control mt-1" name="name" required autocomplete="name" value="<?= e($_POST['name'] ?? current_user()['name'] ?? '') ?>"></label>
            <label class="text-sm font-medium">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? current_user()['email'] ?? '') ?>"></label>
            <label class="text-sm font-medium">Phone<input class="form-control mt-1" type="tel" name="phone" required autocomplete="tel" value="<?= e($_POST['phone'] ?? current_user()['phone'] ?? '') ?>"></label>
        </fieldset>
        <fieldset class="grid gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 sm:grid-cols-2 sm:p-8">
            <legend class="contents"><span class="flex items-center gap-3 font-serif text-2xl sm:col-span-2"><span class="grid h-8 w-8 place-items-center rounded-full bg-forest font-sans text-sm text-white">2</span>Delivery address</span></legend>
            <label class="text-sm font-medium sm:col-span-2">Address<input class="form-control mt-1" name="address_line" required autocomplete="street-address" placeholder="Building, street, estate" value="<?= e($_POST['address_line'] ?? '') ?>"></label>
            <label class="text-sm font-medium">Town / City<input class="form-control mt-1" name="city" required autocomplete="address-level2" value="<?= e($_POST['city'] ?? '') ?>"></label>
            <label class="text-sm font-medium">County (optional)<input class="form-control mt-1" name="county" autocomplete="address-level1" value="<?= e($_POST['county'] ?? '') ?>"></label>
            <label class="text-sm font-medium">Postal code (optional)<input class="form-control mt-1" name="postal_code" autocomplete="postal-code" value="<?= e($_POST['postal_code'] ?? '') ?>"></label>
            <label class="text-sm font-medium sm:col-span-2">Delivery instructions (optional)<textarea class="form-control mt-1" name="delivery_instructions" rows="2" placeholder="Gate code, landmark or best time to deliver"><?= e($_POST['delivery_instructions'] ?? '') ?></textarea></label>
        </fieldset>
        <fieldset class="grid gap-3 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 sm:p-8">
            <legend class="contents"><span class="mb-1 flex items-center gap-3 font-serif text-2xl"><span class="grid h-8 w-8 place-items-center rounded-full bg-forest font-sans text-sm text-white">3</span>Payment method</span></legend>
            <?php foreach ($paymentOptions as $value => [$icon, $label, $hint]): ?>
                <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-ink/15 p-4 transition hover:border-forest/50 has-[:checked]:border-forest has-[:checked]:bg-cream has-[:checked]:ring-1 has-[:checked]:ring-forest">
                    <input type="radio" name="payment_method" value="<?= e($value) ?>" class="h-4 w-4 accent-[#3F5320]" <?= $selectedPayment === $value ? 'checked' : '' ?> required>
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-lg text-forest ring-1 ring-ink/10"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span>
                    <span class="text-sm"><strong class="block"><?= e($label) ?></strong><span class="text-xs text-gray-600"><?= e($hint) ?></span></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
    </div>
    <aside class="h-fit rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 lg:sticky lg:top-28">
        <h2 class="font-serif text-2xl">Order summary</h2>
        <?php $subtotal=array_sum(array_column($items,'line_total'));$summaryCoupon=!empty($_SESSION['coupon'])?coupon_for_checkout((string)$_SESSION['coupon'],$subtotal,current_user()?(int)current_user()['id']:null):null;$summaryDiscount=$summaryCoupon['discount_amount']??0.0;$tax=round(($subtotal-$summaryDiscount)*TAX_RATE,2);$total=$subtotal-$summaryDiscount+$tax+DELIVERY_FEE; ?>
        <ul class="mt-5 grid gap-4">
            <?php foreach ($items as $item): ?><li class="flex items-center gap-3"><span class="relative shrink-0"><img src="<?= e(product_image($item['image'])) ?>" alt="" class="h-14 w-14 rounded-xl object-cover"><span class="absolute -right-2 -top-2 grid h-5 min-w-5 place-items-center rounded-full bg-ink px-1 text-[10px] font-semibold text-white"><?= (int) $item['quantity'] ?></span></span><span class="min-w-0 flex-1 truncate text-sm font-medium"><?= e($item['name']) ?></span><span class="text-sm"><?= e(money($item['line_total'])) ?></span></li><?php endforeach; ?>
        </ul>
        <dl class="mt-5 grid gap-3 border-t border-ink/10 pt-5 text-sm">
            <div class="flex justify-between"><dt class="text-gray-600">Items (<?= cart_count() ?>)</dt><dd><?= e(money($subtotal)) ?></dd></div>
            <?php if ($summaryDiscount > 0): ?><div class="flex justify-between"><dt class="text-gray-600">Coupon</dt><dd>−<?= e(money($summaryDiscount)) ?></dd></div><?php elseif (!empty($_SESSION['coupon'])): ?><p class="text-xs text-[#7A2E22]">Your coupon is no longer available and must be removed before ordering.</p><?php endif; ?>
            <div class="flex justify-between"><dt class="text-gray-600">Tax (<?= e((string) round(TAX_RATE * 100)) ?>%)</dt><dd><?= e(money($tax)) ?></dd></div>
            <div class="flex justify-between"><dt class="text-gray-600">Delivery</dt><dd><?= e(money(DELIVERY_FEE)) ?></dd></div>
            <div class="flex justify-between border-t border-ink/10 pt-4 text-lg font-semibold"><dt>Total</dt><dd><?= e(money($total)) ?></dd></div>
        </dl>
        <button class="mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-forest px-6 py-3.5 text-sm font-semibold text-white shadow-md transition hover:bg-olive"><i class="fa-solid fa-lock text-xs" aria-hidden="true"></i>Place order</button>
        <p class="mt-4 text-xs leading-5 text-gray-500">Your final total is calculated securely on our server. We never store card details.</p>
        <a href="<?= e(url('cart.php')) ?>" class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-forest"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to bag</a>
    </aside>
</form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
