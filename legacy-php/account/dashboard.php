<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
$counts = db()->prepare("SELECT COUNT(*) AS order_count, COALESCE(SUM(total),0) AS spend FROM orders WHERE user_id=?"); $counts->execute([$user['id']]); $stats = $counts->fetch();
$saved = db()->prepare('SELECT COUNT(*) FROM wishlist_items wi JOIN wishlists w ON w.id=wi.wishlist_id JOIN products p ON p.id=wi.product_id WHERE w.user_id=? AND p.is_active=1'); $saved->execute([$user['id']]);
$consultationCount = db()->prepare('SELECT COUNT(*) FROM consultations WHERE user_id=?'); $consultationCount->execute([$user['id']]);
$recentOrders = db()->prepare('SELECT id,order_number,total,status,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 3'); $recentOrders->execute([$user['id']]); $orders = $recentOrders->fetchAll();
$upcoming = db()->prepare("SELECT preferred_date,preferred_time,status,consultation_type FROM consultations WHERE user_id=? AND preferred_date>=CURDATE() ORDER BY preferred_date,preferred_time LIMIT 3"); $upcoming->execute([$user['id']]); $consultations = $upcoming->fetchAll();
$requests = db()->prepare("SELECT sr.status,sr.created_at,sr.description,s.name AS service_name FROM service_requests sr LEFT JOIN services s ON s.id=sr.service_id WHERE sr.user_id=? ORDER BY sr.created_at DESC LIMIT 3"); $requests->execute([$user['id']]); $requestRows = $requests->fetchAll();
$tiles = [
    ['fa-bag-shopping', '#1F5B43', 'text-white', 'Orders', (string) (int) $stats['order_count'], 'account/orders.php'],
    ['fa-wallet', '#16646E', 'text-white', 'Total spent', money_whole($stats['spend']), 'account/orders.php'],
    ['fa-heart', '#A9461E', 'text-white', 'Saved fabrics', (string) (int) $saved->fetchColumn(), 'account/wishlist.php'],
    ['fa-calendar-check', '#E3A82B', 'text-ink', 'Consultations', (string) (int) $consultationCount->fetchColumn(), 'account/consultations.php'],
];
$pageTitle = 'My account | Yucca Fabrics';
$accountActive = 'dashboard';
$accountTitle = 'Hello, ' . strtok($user['name'], ' ');
$accountIntro = 'Your orders, bookings and saved fabrics, all in one place.';
$accountAction = ['Shop fabrics', 'shop.php', 'fa-swatchbook'];
require __DIR__ . '/../includes/account-start.php';
?>
<div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    <?php foreach ($tiles as [$icon, $colour, $iconText, $label, $value, $path]): ?>
        <a href="<?= e(url($path)) ?>" class="group rounded-3xl bg-white p-5 shadow-sm ring-1 ring-ink/5 transition hover:shadow-lg"><span class="grid h-11 w-11 place-items-center rounded-2xl <?= $iconText ?>" style="background-color:<?= e($colour) ?>"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><p class="mt-4 font-serif text-xl sm:text-3xl"><?= e($value) ?></p><p class="mt-1 text-xs uppercase tracking-[.14em] text-gray-500"><?= e($label) ?></p></a>
    <?php endforeach; ?>
</div>

<div class="mt-8 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5">
    <div class="flex items-center justify-between gap-4"><h2 class="font-serif text-2xl">Recent orders</h2><a class="text-sm font-semibold text-forest" href="<?= e(url('account/orders.php')) ?>">All orders <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a></div>
    <?php if ($orders): ?>
        <ul class="mt-4 divide-y divide-ink/10">
            <?php foreach ($orders as $order): ?><li><a href="<?= e(url('account/order-details.php?id=' . (int) $order['id'])) ?>" class="flex flex-wrap items-center justify-between gap-3 py-4 transition hover:text-forest"><span><span class="block font-semibold"><?= e($order['order_number']) ?></span><span class="text-xs text-gray-500"><?= e(date('j M Y', strtotime($order['created_at']))) ?></span></span><span class="flex items-center gap-4"><?= status_badge($order['status']) ?><span class="font-semibold"><?= e(money($order['total'])) ?></span><i class="fa-solid fa-chevron-right text-xs text-gray-500" aria-hidden="true"></i></span></a></li><?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="mt-4 rounded-2xl bg-cream p-5 text-sm text-gray-600">No orders yet. <a class="font-semibold text-forest underline" href="<?= e(url('shop.php')) ?>">Explore the fabric collection</a>.</p>
    <?php endif; ?>
</div>

<div class="mt-8 grid gap-8 md:grid-cols-2">
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5">
        <div class="flex items-center justify-between gap-4"><h2 class="font-serif text-xl">Upcoming consultations</h2><a class="text-xs font-semibold text-forest" href="<?= e(url('account/consultations.php')) ?>">View all</a></div>
        <?php if ($consultations): foreach ($consultations as $item): ?>
            <div class="mt-4 flex items-start justify-between gap-3 rounded-2xl bg-cream p-4"><div><p class="font-semibold"><?= e($item['consultation_type']) ?></p><p class="mt-1 text-sm text-gray-600"><i class="fa-regular fa-calendar mr-1.5" aria-hidden="true"></i><?= e(date('D j M Y', strtotime($item['preferred_date']))) ?> · <?= e(date('g:i A', strtotime($item['preferred_time']))) ?></p></div><?= status_badge($item['status']) ?></div>
        <?php endforeach; else: ?>
            <p class="mt-4 rounded-2xl bg-cream p-5 text-sm text-gray-600">Nothing booked yet. <a class="font-semibold text-forest underline" href="<?= e(url('consultations.php')) ?>">Book a design conversation</a>.</p>
        <?php endif; ?>
    </div>
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5">
        <div class="flex items-center justify-between gap-4"><h2 class="font-serif text-xl">Service requests</h2><a class="text-xs font-semibold text-forest" href="<?= e(url('account/service-requests.php')) ?>">View all</a></div>
        <?php if ($requestRows): foreach ($requestRows as $item): ?>
            <div class="mt-4 rounded-2xl bg-cream p-4"><div class="flex items-start justify-between gap-3"><p class="font-semibold"><?= e($item['service_name'] ?? 'Project enquiry') ?></p><?= status_badge($item['status']) ?></div><p class="mt-1 line-clamp-2 text-sm text-gray-600"><?= e($item['description']) ?></p><p class="mt-2 text-xs text-gray-500"><?= e(date('j M Y', strtotime($item['created_at']))) ?></p></div>
        <?php endforeach; else: ?>
            <p class="mt-4 rounded-2xl bg-cream p-5 text-sm text-gray-600">No requests yet. <a class="font-semibold text-forest underline" href="<?= e(url('services.php')) ?>">Explore our services</a>.</p>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/account-end.php'; ?>
