<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME . ' | A Softer Place to Land';
$description = $description ?? 'Discover beautiful fabrics, upholstery craftsmanship and thoughtful interior design at Yucca Fabrics.';
$canonicalPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
if (BASE_URL !== '' && str_starts_with($canonicalPath, BASE_URL)) $canonicalPath = substr($canonicalPath, strlen(BASE_URL)) ?: '/';
// Path relative to the site root (e.g. "account/consultations.php"), so account pages don't light up public menu items.
$scriptPath = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
if (BASE_URL !== '' && str_starts_with($scriptPath, BASE_URL)) $scriptPath = substr($scriptPath, strlen(BASE_URL));
$currentScript = ltrim($scriptPath, '/');
$user = current_user();
$isCustomer = $user && ($user['role'] ?? '') === 'customer';
$accountUrl = url($user ? (($user['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'account/dashboard.php') : 'auth/login.php');
$phone = setting('business_phone', '+254 700 000 000');
$serviceLinks = ['services.php' => 'Upholstery services', 'consultations.php' => 'Interior design consultations', 'gallery.php#fabric-lookbook' => 'Fabric lookbook'];
$navLinks = ['index.php' => 'Home', 'shop.php' => 'Shop', 'services' => 'Services', 'gallery.php' => 'Gallery', 'blog.php' => 'Journal', 'about.php' => 'About', 'contact.php' => 'Contact'];
$servicesActive = in_array($currentScript, ['services.php', 'consultations.php'], true);
$isActive = fn(string $script): bool => $script === $currentScript || ($script === 'blog.php' && $currentScript === 'article.php') || ($script === 'shop.php' && $currentScript === 'product-details.php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="#3F5320">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php if(APP_SITE_URL!==''): ?><link rel="canonical" href="<?= e(APP_SITE_URL . BASE_URL . $canonicalPath) ?>"><?php endif; ?>
    <link rel="icon" type="image/jpeg" href="<?= e(url('assets/images/yucca-fabrics-logo.jpeg')) ?>">
    <?php if(isset($structuredData)): ?><script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= e(url('assets/js/tailwind-config.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <script>window.YUCCA={base:<?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP) ?>,csrf:<?= json_encode(csrf_token()) ?>};</script>
</head>
<body id="top" class="bg-sand text-ink antialiased">
<a href="#main" class="skip-link">Skip to content</a>
<div class="bg-night text-[11px] tracking-wide text-cream/80">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-2">
        <p class="truncate"><i class="fa-solid fa-location-dot mr-1.5 text-wheat" aria-hidden="true"></i><?= e(setting('business_address', 'Nairobi, Kenya')) ?><span class="hidden sm:inline"> · Fabrics, upholstery &amp; interiors</span></p>
        <div class="flex shrink-0 items-center gap-4">
            <a class="hover:text-wheat" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="fa-solid fa-phone mr-1.5 text-wheat" aria-hidden="true"></i><?= e($phone) ?></a>
            <a class="hidden hover:text-wheat md:inline" href="mailto:<?= e(setting('business_email', 'hello@yuccafabrics.co.ke')) ?>"><i class="fa-regular fa-envelope mr-1.5 text-wheat" aria-hidden="true"></i><?= e(setting('business_email', 'hello@yuccafabrics.co.ke')) ?></a>
        </div>
    </div>
</div>
<header id="site-header" class="site-header sticky top-0 z-40 border-b border-ink/10 bg-sand/95 backdrop-blur supports-[backdrop-filter]:bg-sand/85">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-5 py-3 lg:gap-8">
        <a href="<?= e(url('index.php')) ?>" aria-label="Yucca Fabrics home" class="flex shrink-0 items-center gap-3">
            <img src="<?= e(url('assets/images/yucca-fabrics-logo.jpeg')) ?>" alt="" width="56" height="56" class="site-logo h-14 w-14 object-contain mix-blend-multiply">
            <span class="leading-none"><span class="block font-serif text-lg font-semibold tracking-[.18em] text-forest">YUCCA</span><span class="mt-1 block text-[10px] font-medium tracking-[.42em] text-clay">FABRICS</span></span>
        </a>
        <nav class="ml-auto hidden items-center gap-6 text-sm font-medium lg:flex" aria-label="Main">
            <?php foreach ($navLinks as $href => $label): ?>
                <?php if ($href === 'services'): ?>
                    <div class="dropdown relative" data-dropdown>
                        <button type="button" class="nav-link inline-flex items-center gap-1.5 <?= $servicesActive ? 'is-active' : '' ?>" aria-expanded="false" aria-controls="services-menu">Services <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i></button>
                        <div id="services-menu" class="dropdown-panel absolute left-1/2 top-full z-50 w-64 -translate-x-1/2 pt-3">
                            <div class="overflow-hidden rounded-lg border border-ink/10 bg-white py-2 shadow-xl shadow-ink/10">
                                <?php foreach ($serviceLinks as $serviceHref => $serviceLabel): ?><a class="block px-4 py-2.5 text-sm text-ink transition hover:bg-cream hover:text-forest" href="<?= e(url($serviceHref)) ?>"><?= e($serviceLabel) ?></a><?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="nav-link <?= $isActive($href) ? 'is-active' : '' ?>" href="<?= e(url($href)) ?>"<?= $isActive($href) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="ml-auto flex items-center gap-1 lg:ml-0">
            <button type="button" class="icon-button" data-search-toggle aria-expanded="false" aria-controls="site-search" aria-label="Search fabrics"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
            <?php if ($isCustomer): ?><a class="icon-button hidden sm:inline-flex" href="<?= e(url('account/wishlist.php')) ?>" aria-label="Saved favourites"><i class="fa-regular fa-heart" aria-hidden="true"></i></a><?php endif; ?>
            <?php if ($user): ?>
                <div class="dropdown relative hidden sm:block" data-dropdown>
                    <button type="button" class="icon-button" aria-expanded="false" aria-controls="account-menu" aria-label="Account menu"><i class="fa-regular fa-user" aria-hidden="true"></i></button>
                    <div id="account-menu" class="dropdown-panel dropdown-panel-end absolute right-0 top-full z-50 w-60 pt-3">
                        <div class="overflow-hidden rounded-lg border border-ink/10 bg-white py-2 text-sm shadow-xl shadow-ink/10">
                            <p class="truncate border-b border-ink/10 px-4 pb-3 pt-1 text-xs text-gray-500">Signed in as <strong class="text-ink"><?= e($user['name'] ?? '') ?></strong></p>
                            <a class="mt-1 block px-4 py-2.5 transition hover:bg-cream hover:text-forest" href="<?= e($accountUrl) ?>"><?= ($user['role'] ?? '') === 'admin' ? 'Admin dashboard' : 'My account' ?></a>
                            <?php if ($isCustomer): ?><a class="block px-4 py-2.5 transition hover:bg-cream hover:text-forest" href="<?= e(url('account/orders.php')) ?>">My orders</a><a class="block px-4 py-2.5 transition hover:bg-cream hover:text-forest" href="<?= e(url('account/wishlist.php')) ?>">Saved favourites</a><?php endif; ?>
                            <form action="<?= e(url('auth/logout.php')) ?>" method="post" class="mt-1 border-t border-ink/10 pt-1"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="block w-full px-4 py-2.5 text-left transition hover:bg-cream hover:text-clay">Sign out</button></form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a class="icon-button hidden sm:inline-flex" href="<?= e($accountUrl) ?>" aria-label="Sign in"><i class="fa-regular fa-user" aria-hidden="true"></i></a>
            <?php endif; ?>
            <a class="icon-button relative" href="<?= e(url('cart.php')) ?>" aria-label="Shopping bag"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i><span id="cart-count" class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-clay px-1 text-[10px] font-semibold text-white"><?= cart_count() ?></span></a>
            <a href="<?= e(url('consultations.php')) ?>" class="ml-3 hidden rounded-full bg-forest px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-olive xl:inline-block">Book a consultation</a>
            <button id="menu-toggle" type="button" class="icon-button lg:hidden" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        </div>
    </div>
    <div id="site-search" class="hidden border-t border-ink/10 bg-sand">
        <form action="<?= e(url('shop.php')) ?>" method="get" role="search" class="mx-auto flex max-w-3xl items-center gap-3 px-5 py-4">
            <label class="sr-only" for="site-search-input">Search fabrics</label>
            <input id="site-search-input" name="q" type="search" placeholder="Search fabrics by name, colour or code…" class="form-control" autocomplete="off">
            <button class="shrink-0 rounded-full bg-forest px-5 py-3 text-sm font-semibold text-white transition hover:bg-olive">Search</button>
        </form>
    </div>
    <div id="mobile-nav" class="hidden max-h-[calc(100dvh-5rem)] overflow-y-auto border-t border-ink/10 bg-sand lg:hidden">
        <nav class="mx-auto grid max-w-7xl gap-1 px-5 py-4 text-base" aria-label="Mobile">
            <?php foreach ($navLinks as $href => $label): ?>
                <?php if ($href === 'services'): ?>
                    <p class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-[.2em] text-sage">Services</p>
                    <?php foreach ($serviceLinks as $serviceHref => $serviceLabel): ?><a class="mobile-link pl-6" href="<?= e(url($serviceHref)) ?>"><?= e($serviceLabel) ?></a><?php endforeach; ?>
                <?php else: ?>
                    <a class="mobile-link <?= $isActive($href) ? 'is-active' : '' ?>" href="<?= e(url($href)) ?>"<?= $isActive($href) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="mt-3 grid gap-1 border-t border-ink/10 pt-3">
                <a class="mobile-link" href="<?= e($accountUrl) ?>"><i class="fa-regular fa-user mr-3 w-4 text-sage" aria-hidden="true"></i><?= $user ? 'My account' : 'Sign in / Register' ?></a>
                <?php if ($isCustomer): ?><a class="mobile-link" href="<?= e(url('account/wishlist.php')) ?>"><i class="fa-regular fa-heart mr-3 w-4 text-sage" aria-hidden="true"></i>Saved favourites</a><?php endif; ?>
                <?php if ($user): ?><form action="<?= e(url('auth/logout.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="mobile-link w-full text-left"><i class="fa-solid fa-arrow-right-from-bracket mr-3 w-4 text-sage" aria-hidden="true"></i>Sign out</button></form><?php endif; ?>
            </div>
            <a href="<?= e(url('consultations.php')) ?>" class="mt-4 rounded-full bg-forest px-5 py-3.5 text-center text-sm font-semibold text-white">Book a consultation</a>
        </nav>
    </div>
</header>
<main id="main" tabindex="-1">
<?php if ($flashNotice = flash('error')): ?><div class="mx-auto max-w-7xl px-5 pt-6"><?= form_alert($flashNotice, 'info') ?></div><?php endif; ?>
