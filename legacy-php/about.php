<?php
require_once __DIR__ . '/includes/functions.php';
$numbers = site_numbers();
$offers = [
    ['fa-swatchbook', '#16646E', 'Fabrics', 'A curated collection of bouclé, linen, velvet and leather, chosen to look beautiful and live well.', 'Shop fabrics', 'shop.php', fabric_image('santa-boucle.jpeg'), 'Santa herringbone bouclé on a sofa'],
    ['fa-couch', '#1F5B43', 'Upholstery', 'Reupholstery and cushion renewal that give much-loved furniture another chapter.', 'Upholstery services', 'services.php', service_image('reupholstery-tufted-corduroy.jpg'), 'Green corduroy upholstery with tufted buttons'],
    ['fa-compass-drafting', '#A9461E', 'Interior design', 'Consultations on colour, fabric and layout, online or in person, shaped around how you live.', 'Book a consultation', 'consultations.php', service_image('room-styling-swatches-plans.jpg'), 'Colour swatches held over room plans'],
];
$values = [
    ['fa-hand-holding-heart', '#1F5B43', 'text-white', 'Care', 'Good design starts with listening. We take time to understand your space, your routines and what matters to you.'],
    ['fa-scissors', '#E3A82B', 'text-ink', 'Craft', 'We value durable materials and skilled, attentive work, so the things we make look good and keep going.'],
    ['fa-palette', '#A9461E', 'text-white', 'Character', 'We make room for individuality, colour and the pieces you already love, instead of a one-size-fits-all look.'],
];
$pageTitle = 'Our story | Yucca Fabrics';
$description = 'Yucca Fabrics brings together beautiful fabrics, careful upholstery and thoughtful interior design in ' . setting('business_address', 'Nairobi, Kenya') . '.';
require __DIR__ . '/includes/header.php';
?>
<section class="relative overflow-hidden bg-cream">
    <?= yucca_sprig_svg('pointer-events-none absolute -right-20 -top-16 hidden h-96 w-96 text-olive/10 lg:block') ?>
    <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 lg:grid-cols-2 lg:py-24">
        <div>
            <p class="eyebrow">Our story</p>
            <h1 class="mt-4 font-serif text-4xl leading-[1.08] sm:text-5xl lg:text-6xl">Spaces with a<br><span class="text-clay">softer side.</span></h1>
            <p class="mt-6 max-w-xl text-base leading-8 text-gray-600">Yucca Fabrics is a home for thoughtful textiles, careful craftsmanship and spaces that feel like the people who live in them. We help you choose, restore and style, one room or one chair at a time.</p>
            <div class="mt-8 flex flex-wrap gap-3"><a href="#what-we-do" class="rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-olive">What we do</a><a href="<?= e(url('contact.php')) ?>" class="rounded-full border border-forest/30 bg-white/60 px-6 py-3 text-sm font-semibold text-forest transition hover:bg-white">Say hello</a></div>
        </div>
        <div class="relative mx-auto w-full max-w-lg">
            <span class="absolute -left-6 -top-6 h-36 w-36 rounded-full bg-[#E3A82B]" aria-hidden="true"></span>
            <span class="absolute -bottom-8 -right-6 h-44 w-44 rounded-full bg-[#1F5B43]" aria-hidden="true"></span>
            <div class="relative grid h-[420px] grid-cols-5 gap-3 sm:h-[500px]">
                <img src="<?= e(service_image('green-sofa-cushions.jpg')) ?>" alt="A green sofa with patterned cushions" class="col-span-3 h-full w-full rounded-3xl object-cover shadow-xl">
                <div class="col-span-2 grid gap-3"><img src="<?= e(fabric_image('piki-17-fabric-samples.jpeg')) ?>" alt="Woven upholstery fabric samples in navy, silver and grey" class="h-full w-full rounded-3xl object-cover shadow-xl"><img src="<?= e(fabric_image('coco-boucle.jpeg')) ?>" alt="Mustard Coco bouclé on a sofa and armchair" class="h-full w-full rounded-3xl object-cover object-top shadow-xl"></div>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-20 lg:grid-cols-2">
    <img src="<?= e(service_image('upholstery-workshop-chair.jpg')) ?>" alt="A curved upholstered chair in a workshop" loading="lazy" class="aspect-[4/5] w-full max-w-lg rounded-3xl object-cover shadow-xl">
    <div>
        <p class="eyebrow">Our point of view</p>
        <h2 class="mt-4 font-serif text-4xl leading-tight">A home should feel like yours.</h2>
        <p class="mt-5 text-[15px] leading-8 text-gray-600">We bring fabrics, furniture and the details in between together with an appreciation for how a home is really used. We listen first, then help you find colours and materials that suit your rooms, routines and budget.</p>
        <p class="mt-4 text-[15px] leading-8 text-gray-600">Whether you’re choosing a single fabric, restoring a much-loved chair or refreshing a whole room, our approach is the same: personal advice, considered choices and care in the making.</p>
        <div class="mt-8 flex gap-5 rounded-3xl bg-cream p-6">
            <?= yucca_sprig_svg('h-16 w-16 shrink-0 text-olive') ?>
            <div><h3 class="font-serif text-xl">The yucca in our name</h3><p class="mt-2 text-sm leading-7 text-gray-600">Yucca plants are evergreen, hardy and at home in the sun. That’s what we look for in a fabric too: beauty that lasts, and comfort that keeps going through busy, sunny, everyday life.</p></div>
        </div>
    </div>
</section>

<section id="what-we-do" class="bg-cream py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="mx-auto max-w-2xl text-center"><p class="eyebrow">What we do</p><h2 class="mt-3 font-serif text-4xl sm:text-5xl">Three ways we can help</h2></div>
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            <?php foreach ($offers as [$icon, $colour, $title, $text, $label, $href, $image, $alt]): ?>
                <a href="<?= e(url($href)) ?>" class="group flex flex-col overflow-hidden rounded-3xl text-white shadow-xl shadow-ink/10 transition motion-safe:hover:-translate-y-1" style="background-color:<?= e($colour) ?>">
                    <span class="block aspect-[4/3] overflow-hidden"><img src="<?= e($image) ?>" alt="<?= e($alt) ?>" loading="lazy" class="h-full w-full object-cover object-top motion-safe:transition motion-safe:duration-700 motion-safe:group-hover:scale-105"></span>
                    <span class="flex flex-1 flex-col p-7"><span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-lg" style="color:<?= e($colour) ?>"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><span class="mt-5 font-serif text-2xl"><?= e($title) ?></span><span class="mt-2 text-sm leading-6 text-white/90"><?= e($text) ?></span><span class="mt-auto inline-flex items-center gap-2 pt-6 text-sm font-semibold"><?= e($label) ?> <i class="fa-solid fa-arrow-right text-xs transition group-hover:translate-x-1" aria-hidden="true"></i></span></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-5 py-20">
    <div class="max-w-2xl"><p class="eyebrow">What guides us</p><h2 class="mt-3 font-serif text-4xl">Care, craft and character</h2></div>
    <div class="mt-10 grid gap-6 md:grid-cols-3">
        <?php foreach ($values as [$icon, $colour, $iconText, $title, $text]): ?>
            <article class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-ink/5"><span class="grid h-14 w-14 place-items-center rounded-2xl text-xl <?= $iconText ?>" style="background-color:<?= e($colour) ?>"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><h3 class="mt-6 font-serif text-2xl"><?= e($title) ?></h3><p class="mt-3 text-sm leading-7 text-gray-600"><?= e($text) ?></p></article>
        <?php endforeach; ?>
    </div>
</section>

<section class="relative overflow-hidden bg-forest px-5 py-20 text-center text-white">
    <?= yucca_sprig_svg('pointer-events-none absolute -bottom-16 left-1/2 h-80 w-80 -translate-x-1/2 text-white/5') ?>
    <div class="relative mx-auto max-w-4xl"><p class="eyebrow">Our promise</p><p class="mt-6 font-serif text-3xl leading-relaxed sm:text-4xl">“Beautiful things matter most when they make everyday life feel a little better.”</p></div>
</section>

<?php if ($numbers): ?>
<section class="bg-white py-16">
    <div class="mx-auto max-w-6xl px-5">
        <p class="eyebrow text-center">The collection at a glance</p>
        <div class="mt-8 grid grid-cols-2 gap-6 text-center md:grid-cols-<?= min(4, count($numbers)) ?>">
            <?php foreach ($numbers as $label => $value): ?><div class="rounded-3xl bg-cream px-4 py-8"><p class="font-serif text-4xl text-forest"><?= e((string) $value) ?></p><p class="mt-2 text-xs uppercase tracking-[.16em] text-gray-600"><?= e($label) ?></p></div><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="mx-auto max-w-7xl px-5 py-20">
    <div class="relative overflow-hidden rounded-3xl bg-night px-8 py-12 text-white sm:px-12 lg:flex lg:items-center lg:justify-between lg:gap-10">
        <?= yucca_sprig_svg('pointer-events-none absolute -right-10 -top-10 h-64 w-64 text-white/5') ?>
        <div class="relative"><p class="eyebrow">Come and see us</p><h2 class="mt-3 font-serif text-3xl sm:text-4xl">Let’s talk about your space</h2>
            <ul class="mt-5 grid gap-2 text-sm text-cream/80"><li><i class="fa-solid fa-location-dot mr-2 w-4 text-wheat" aria-hidden="true"></i><?= e(setting('business_address', 'Nairobi, Kenya')) ?></li><?php if (setting('business_hours') !== ''): ?><li><i class="fa-regular fa-clock mr-2 w-4 text-wheat" aria-hidden="true"></i><?= e(setting('business_hours')) ?></li><?php endif; ?></ul>
        </div>
        <div class="relative mt-8 flex flex-wrap gap-3 lg:mt-0 lg:shrink-0"><a href="<?= e(url('contact.php')) ?>" class="rounded-full bg-wheat px-6 py-3 text-sm font-semibold text-night transition hover:bg-white">Contact us</a><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full border border-white/30 px-6 py-3 text-sm font-semibold transition hover:bg-white/10"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp</a></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
