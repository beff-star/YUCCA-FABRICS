<?php
require_once __DIR__ . '/includes/functions.php';
$services = active_services();
$requestServices = array_values(array_filter($services, fn(array $service): bool => !is_consultation_service($service)));
$consultationPrices = array_filter(array_map(fn(array $service) => is_consultation_service($service) ? $service['starting_price'] : null, $services), fn($price) => $price !== null);
$fabricCount = (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn();
$fabricFrom = db()->query('SELECT MIN(COALESCE(sale_price,price)) FROM products WHERE is_active=1')->fetchColumn();
$phone = setting('business_phone', '+254 700 000 000');
$steps = [
    ['fa-camera', '#1F5B43', 'text-white', 'Share your piece', 'Send a photo and a few details with the form below, or visit us in ' . setting('business_address', 'Nairobi') . '.'],
    ['fa-swatchbook', '#E3A82B', 'text-ink', 'Choose your fabric', 'Browse the collection online or compare samples with us until one feels right.'],
    ['fa-handshake', '#16646E', 'text-white', 'Agree the quote', 'We confirm the work, the price and the timing with you before anything begins.'],
    ['fa-couch', '#A9461E', 'text-white', 'Enjoy it again', 'Your piece is finished with care and ready to take its place at home.'],
];
$pageTitle = 'Upholstery & interior services | Yucca Fabrics';
$description = 'Furniture reupholstery, cushion renewal, fabric and colour consultations and room styling from Yucca Fabrics.';
require __DIR__ . '/includes/header.php';
?>
<section class="relative overflow-hidden bg-cream">
    <?= yucca_sprig_svg('pointer-events-none absolute -left-20 -top-12 hidden h-80 w-80 text-olive/10 sm:block') ?>
    <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 lg:grid-cols-2 lg:py-24">
        <div>
            <p class="eyebrow">Upholstery &amp; interior services</p>
            <h1 class="mt-4 font-serif text-4xl leading-[1.08] sm:text-5xl lg:text-6xl">Good things,<br><span class="text-clay">made useful again.</span></h1>
            <p class="mt-6 max-w-xl text-base leading-8 text-gray-600">From one tired cushion to a full room refresh, we pair skilled upholstery with fabrics chosen for real life. Bring us the piece you love, and we’ll help it feel new again.</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="#request-form" class="rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-olive">Start a project</a><a href="#services" class="rounded-full border border-forest/30 bg-white/60 px-6 py-3 text-sm font-semibold text-forest transition hover:bg-white">Explore services</a></div>
            <ul class="mt-10 flex flex-wrap gap-3 text-xs font-medium text-ink">
                <li class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm"><i class="fa-solid fa-couch text-[#1F5B43]" aria-hidden="true"></i>Reupholstery &amp; cushion renewal</li>
                <?php if ($fabricFrom !== null): ?><li class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm"><i class="fa-solid fa-swatchbook text-[#A9461E]" aria-hidden="true"></i><?= $fabricCount ?> fabrics from <?= e(money_whole($fabricFrom)) ?>/m</li><?php endif; ?>
                <?php if ($consultationPrices): ?><li class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm"><i class="fa-solid fa-compass-drafting text-[#16646E]" aria-hidden="true"></i>Consultations from <?= e(money_whole(min(array_map('floatval', $consultationPrices)))) ?></li><?php endif; ?>
            </ul>
        </div>
        <div class="relative mx-auto w-full max-w-lg">
            <span class="absolute -right-5 -top-6 h-36 w-36 rounded-full bg-[#E3A82B]" aria-hidden="true"></span>
            <span class="absolute -bottom-8 -left-6 h-44 w-44 rounded-full bg-[#16646E]" aria-hidden="true"></span>
            <div class="relative grid h-[420px] grid-cols-5 grid-rows-6 gap-3 sm:h-[520px]">
                <img src="<?= e(service_image('reupholstery-tufted-corduroy.jpg')) ?>" alt="Green corduroy upholstery with tufted buttons" class="col-span-3 row-span-6 h-full w-full rounded-3xl object-cover shadow-xl">
                <img src="<?= e(service_image('cushion-renewal-mustard-cushion.jpg')) ?>" alt="Mustard cushion on an upholstered armchair" class="col-span-2 row-span-3 h-full w-full rounded-3xl object-cover shadow-xl">
                <img src="<?= e(service_image('green-sofa-cushions.jpg')) ?>" alt="Green sofa with patterned cushions" class="col-span-2 row-span-3 h-full w-full rounded-3xl object-cover shadow-xl">
            </div>
            <div class="absolute -bottom-6 right-4 flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-xl sm:right-auto sm:left-1/3"><span class="grid h-10 w-10 place-items-center rounded-full bg-[#A9461E] text-white"><i class="fa-solid fa-scissors" aria-hidden="true"></i></span><span class="text-xs leading-5 text-gray-600"><strong class="block text-sm text-ink">Made to measure</strong>for the pieces you love</span></div>
        </div>
    </div>
</section>

<section id="services" class="py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="mx-auto max-w-2xl text-center"><p class="eyebrow">What we do</p><h2 class="mt-3 font-serif text-4xl sm:text-5xl">A considered hand with every detail</h2><p class="mt-4 text-sm leading-7 text-gray-600">Some projects start with a favourite chair. Others start with a room that doesn’t quite feel finished. Choose a service to get started, or simply tell us what you have in mind.</p></div>
        <?php if ($services): ?>
            <div class="mt-14 grid gap-8 md:grid-cols-2"><?php foreach ($services as $service): require __DIR__ . '/includes/service-card.php'; endforeach; ?></div>
        <?php else: ?>
            <p class="mt-10 text-center text-sm text-gray-600">Our services are being updated. <a class="underline" href="<?= e(url('contact.php')) ?>">Contact us</a> and we’ll be glad to help.</p>
        <?php endif; ?>
    </div>
</section>

<section class="relative overflow-hidden bg-night py-20 text-white">
    <?= yucca_sprig_svg('pointer-events-none absolute -bottom-16 -right-16 h-96 w-96 text-white/5') ?>
    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-[.9fr_1.1fr]">
        <img src="<?= e(service_image('upholstery-workshop-chair.jpg')) ?>" alt="A curved upholstered chair in a workshop" loading="lazy" class="mx-auto aspect-[4/5] w-full max-w-md rounded-3xl object-cover shadow-2xl">
        <div>
            <p class="eyebrow">How it works</p>
            <h2 class="mt-3 font-serif text-4xl sm:text-5xl">From first photo to finished piece</h2>
            <p class="mt-4 max-w-xl text-sm leading-7 text-cream/70">No guesswork and no surprises: you’ll know what we’re doing, what it costs and when it will be ready.</p>
            <ol class="mt-10 grid gap-5 sm:grid-cols-2">
                <?php foreach ($steps as $index => [$icon, $colour, $iconText, $title, $text]): ?>
                    <li class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10"><div class="flex items-center gap-4"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-lg <?= $iconText ?>" style="background-color:<?= e($colour) ?>"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><span class="font-serif text-sm text-wheat">Step <?= $index + 1 ?></span></div><h3 class="mt-4 text-lg font-semibold"><?= e($title) ?></h3><p class="mt-2 text-sm leading-6 text-cream/70"><?= e($text) ?></p></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</section>

<section class="py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">Start with the fabric</p><h2 class="mt-3 font-serif text-4xl"><?= count(fabric_collection()) ?> textures, one for every room</h2></div><a href="<?= e(url('shop.php')) ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-forest">Browse the collection <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a></div>
        <div class="mt-8 grid grid-cols-3 gap-3 sm:grid-cols-6 sm:gap-4">
            <?php foreach (array_slice(fabric_collection(), 6, 6) as $fabric): ?><a href="<?= e(url('shop.php?q=' . rawurlencode($fabric['name']))) ?>" class="group block"><span class="block overflow-hidden rounded-2xl"><img src="<?= e(fabric_image($fabric['image'])) ?>" alt="<?= e($fabric['name'] . ' ' . $fabric['type']) ?>" loading="lazy" class="aspect-square w-full object-cover motion-safe:transition motion-safe:duration-500 motion-safe:group-hover:scale-110"></span><span class="mt-2 block text-sm font-semibold"><?= e($fabric['name']) ?></span><span class="block text-[10px] uppercase tracking-widest text-gray-500"><?= e($fabric['type']) ?></span></a><?php endforeach; ?>
        </div>
    </div>
</section>

<section id="request-form" class="bg-cream py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="overflow-hidden rounded-3xl bg-white shadow-xl shadow-ink/10 lg:grid lg:grid-cols-[.8fr_1.2fr]">
            <aside class="relative overflow-hidden bg-forest p-8 text-white sm:p-10">
                <?= yucca_sprig_svg('pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 text-white/10') ?>
                <div class="relative">
                    <p class="eyebrow">Tell us a little</p>
                    <h2 class="mt-3 font-serif text-4xl">Start a project</h2>
                    <p class="mt-4 text-sm leading-7 text-white/80">Share a few details and a photo if you have one. We’ll follow up to learn more and prepare the right advice and quote.</p>
                    <ul class="mt-8 grid gap-4 text-sm">
                        <li class="flex gap-3"><i class="fa-solid fa-reply mt-1 text-wheat" aria-hidden="true"></i><span>We reply to discuss your piece and next steps.</span></li>
                        <li class="flex gap-3"><i class="fa-solid fa-file-invoice mt-1 text-wheat" aria-hidden="true"></i><span>You get a clear quote before any work starts.</span></li>
                        <li class="flex gap-3"><i class="fa-solid fa-camera mt-1 text-wheat" aria-hidden="true"></i><span>A photo of the piece helps us advise you faster.</span></li>
                    </ul>
                    <div class="mt-10 flex flex-wrap gap-3 text-sm font-semibold"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-forest"><i class="fa-solid fa-phone" aria-hidden="true"></i>Call us</a><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full border border-white/40 px-5 py-2.5"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp</a></div>
                </div>
            </aside>
            <form data-ajax-form enctype="multipart/form-data" action="<?= e(url('api/service-requests.php')) ?>" method="post" class="grid gap-4 p-8 sm:grid-cols-2 sm:p-10">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="text-sm font-medium sm:col-span-2">Service<select id="service-id" class="form-control mt-1" name="service_id"><option value="">Not sure yet, I’d like advice</option><?php foreach ($requestServices as $service): ?><option value="<?= (int) $service['id'] ?>"><?= e($service['name']) ?></option><?php endforeach; ?></select></label>
                <label class="text-sm font-medium">Your name<input class="form-control mt-1" name="name" required maxlength="150" autocomplete="name"></label>
                <label class="text-sm font-medium">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email"></label>
                <label class="text-sm font-medium">Phone<input class="form-control mt-1" type="tel" name="phone" required autocomplete="tel"></label>
                <label class="text-sm font-medium">Project location<input class="form-control mt-1" name="location" required></label>
                <label class="text-sm font-medium">Preferred date (optional)<input class="form-control mt-1" type="date" name="preferred_date" min="<?= e(date('Y-m-d')) ?>"></label>
                <label class="text-sm font-medium">Reference photo (optional)<input class="form-control mt-1 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-cream file:px-4 file:py-1.5 file:text-sm file:font-semibold file:text-forest" type="file" name="reference_image" accept="image/jpeg,image/png,image/webp"></label>
                <label class="text-sm font-medium sm:col-span-2">What would you like help with?<textarea class="form-control mt-1" name="description" rows="5" minlength="10" maxlength="5000" required placeholder="For example: a three-seater sofa with sagging seats; we’d like a hard-wearing fabric in a warm colour."></textarea></label>
                <button class="inline-flex w-fit items-center gap-2 rounded-full bg-forest px-7 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-olive sm:col-span-2">Send project request <i class="fa-solid fa-paper-plane text-xs" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
