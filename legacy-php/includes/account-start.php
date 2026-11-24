<?php
// Opens the shared account layout. Set before including:
//   $accountActive  one of the $accountLinks keys
//   $accountTitle   page heading;  optional $accountIntro, and $accountAction = [label, path, icon]
// Close it with includes/account-end.php.
$accountUser = current_user();
$accountLinks = [
    'dashboard' => ['account/dashboard.php', 'fa-house', 'Overview'],
    'orders' => ['account/orders.php', 'fa-bag-shopping', 'Orders'],
    'consultations' => ['account/consultations.php', 'fa-calendar-check', 'Consultations'],
    'requests' => ['account/service-requests.php', 'fa-couch', 'Service requests'],
    'wishlist' => ['account/wishlist.php', 'fa-heart', 'Favourites'],
    'profile' => ['account/profile.php', 'fa-user', 'Profile'],
];
$initials = strtoupper(implode('', array_map(fn(string $part): string => mb_substr($part, 0, 1), array_slice(preg_split('/\s+/', trim($accountUser['name'] ?? 'Y')), 0, 2))));
require __DIR__ . '/header.php';
?>
<section class="relative overflow-hidden bg-cream">
    <?= yucca_sprig_svg('pointer-events-none absolute -right-12 -top-12 hidden h-64 w-64 text-olive/10 sm:block') ?>
    <div class="relative mx-auto flex max-w-7xl flex-wrap items-end justify-between gap-5 px-5 py-10">
        <div><p class="eyebrow">Your Yucca account</p><h1 class="mt-2 font-serif text-3xl sm:text-4xl"><?= e($accountTitle) ?></h1><?php if (!empty($accountIntro)): ?><p class="mt-2 max-w-xl text-sm leading-6 text-gray-600"><?= e($accountIntro) ?></p><?php endif; ?></div>
        <?php if (!empty($accountAction)): [$actionLabel, $actionPath, $actionIcon] = $accountAction; ?><a href="<?= e(url($actionPath)) ?>" class="inline-flex items-center gap-2 rounded-full bg-forest px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-olive"><i class="fa-solid <?= e($actionIcon) ?> text-xs" aria-hidden="true"></i><?= e($actionLabel) ?></a><?php endif; ?>
    </div>
</section>
<section class="mx-auto max-w-7xl px-5 py-10">
    <div class="grid gap-8 lg:grid-cols-[250px_1fr]">
        <aside class="min-w-0">
            <div class="hidden items-center gap-3 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-ink/5 lg:flex"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-forest font-semibold text-white"><?= e($initials) ?></span><span class="min-w-0"><span class="block truncate font-semibold"><?= e($accountUser['name'] ?? '') ?></span><span class="block truncate text-xs text-gray-500"><?= e($accountUser['email'] ?? '') ?></span></span></div>
            <nav class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 lg:mx-0 lg:mt-4 lg:grid lg:gap-1 lg:overflow-visible lg:rounded-3xl lg:bg-white lg:p-3 lg:shadow-sm lg:ring-1 lg:ring-ink/5" aria-label="Account">
                <?php foreach ($accountLinks as $key => [$path, $icon, $label]): $isCurrent = $key === $accountActive; ?>
                    <a href="<?= e(url($path)) ?>" class="inline-flex shrink-0 items-center gap-3 rounded-full px-4 py-2.5 text-sm font-medium transition lg:rounded-xl <?= $isCurrent ? 'bg-forest text-white' : 'bg-white text-ink hover:bg-cream lg:bg-transparent' ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>><i class="fa-solid <?= e($icon) ?> w-4 text-center <?= $isCurrent ? '' : 'text-sage' ?>" aria-hidden="true"></i><?= e($label) ?></a>
                <?php endforeach; ?>
                <form action="<?= e(url('auth/logout.php')) ?>" method="post" class="shrink-0 lg:mt-2 lg:border-t lg:border-ink/10 lg:pt-2"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="inline-flex w-full items-center gap-3 rounded-full bg-white px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-cream hover:text-clay lg:rounded-xl lg:bg-transparent"><i class="fa-solid fa-arrow-right-from-bracket w-4 text-center text-sage" aria-hidden="true"></i>Sign out</button></form>
            </nav>
        </aside>
        <div class="min-w-0">
