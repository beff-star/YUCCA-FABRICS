<?php
require_once __DIR__ . '/includes/functions.php';
$faqs = db()->query('SELECT question,answer FROM faqs WHERE is_active=1 ORDER BY sort_order LIMIT 8')->fetchAll();
$phone = setting('business_phone', '+254 700 000 000');
$email = setting('business_email', 'hello@yuccafabrics.co.ke');
$address = setting('business_address', 'Nairobi, Kenya');
// Google Maps URLs (documented, no API key): opens directions to the saved address without embedding a tracker.
$mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address);
$channels = [
    ['fa-solid fa-phone', '#1F5B43', 'text-white', 'Call us', $phone, 'tel:' . preg_replace('/[^0-9+]/', '', $phone), false],
    ['fa-brands fa-whatsapp', '#16646E', 'text-white', 'WhatsApp', 'Chat with us', whatsapp_url(), true],
    ['fa-regular fa-envelope', '#A9461E', 'text-white', 'Email', $email, 'mailto:' . $email, false],
    ['fa-solid fa-location-dot', '#E3A82B', 'text-ink', 'Visit', $address, $mapsUrl, true],
];
$pageTitle = 'Contact Yucca Fabrics';
$description = 'Call, WhatsApp, email or visit Yucca Fabrics in ' . $address . ' for fabrics, upholstery and interior design.';
require __DIR__ . '/includes/header.php';
?>
<section class="relative overflow-hidden bg-cream px-5 pb-24 pt-16 text-center">
    <?= yucca_sprig_svg('pointer-events-none absolute -left-16 -top-10 hidden h-72 w-72 text-olive/10 sm:block') ?>
    <div class="relative"><p class="eyebrow">We’re happy to help</p><h1 class="mt-3 font-serif text-4xl sm:text-5xl">Let’s have a conversation</h1><p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-gray-600">A fabric question, a project idea or a quick hello. Reach us whichever way suits you, and we’ll get back to you as soon as we can.</p></div>
</section>
<section class="relative mx-auto -mt-16 max-w-7xl px-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($channels as [$icon, $colour, $iconText, $label, $value, $href, $external]): ?>
            <a href="<?= e($href) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?> class="group flex items-center gap-4 rounded-3xl bg-white p-5 shadow-lg shadow-ink/5 ring-1 ring-ink/5 transition motion-safe:hover:-translate-y-1"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-lg <?= $iconText ?>" style="background-color:<?= e($colour) ?>"><i class="<?= e($icon) ?>" aria-hidden="true"></i></span><span class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-[.16em] text-gray-500"><?= e($label) ?></span><span class="block truncate font-semibold group-hover:text-forest"><?= e($value) ?></span></span></a>
        <?php endforeach; ?>
    </div>
</section>
<section class="mx-auto grid max-w-7xl gap-8 px-5 py-16 lg:grid-cols-[1.35fr_.65fr]">
    <div class="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-ink/5 sm:p-10">
        <p class="eyebrow">Send a message</p><h2 class="mt-2 font-serif text-3xl">How can we help?</h2><p class="mt-2 text-sm text-gray-600">Tell us a little about what you need and we’ll reply by email or phone.</p>
        <form data-ajax-form action="<?= e(url('api/contact.php')) ?>" method="post" class="mt-8 grid gap-4 sm:grid-cols-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="text-sm font-medium">Your name<input class="form-control mt-1" name="name" required maxlength="150" autocomplete="name"></label>
            <label class="text-sm font-medium">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email"></label>
            <label class="text-sm font-medium">Phone (optional)<input class="form-control mt-1" type="tel" name="phone" autocomplete="tel"></label>
            <label class="text-sm font-medium">Subject<input class="form-control mt-1" name="subject" required maxlength="180" placeholder="e.g. Fabric for a corner sofa"></label>
            <label class="text-sm font-medium sm:col-span-2">Message<textarea class="form-control mt-1" name="message" rows="6" minlength="10" maxlength="5000" required></textarea></label>
            <div class="hidden" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
            <button class="inline-flex w-fit items-center gap-2 rounded-full bg-forest px-7 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-olive sm:col-span-2">Send your message <i class="fa-solid fa-paper-plane text-xs" aria-hidden="true"></i></button>
        </form>
    </div>
    <aside class="grid content-start gap-6">
        <div class="relative overflow-hidden rounded-3xl bg-forest p-7 text-white">
            <?= yucca_sprig_svg('pointer-events-none absolute -bottom-8 -right-8 h-40 w-40 text-white/10') ?>
            <i class="fa-solid fa-location-dot text-2xl text-wheat" aria-hidden="true"></i>
            <h2 class="mt-4 font-serif text-2xl">Find us</h2>
            <p class="mt-2 text-sm leading-6 text-white/90"><?= e($address) ?></p>
            <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" class="relative mt-6 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-forest">Get directions <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i><span class="sr-only"> (opens Google Maps)</span></a>
        </div>
        <div class="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-ink/5"><i class="fa-regular fa-clock text-2xl text-sage" aria-hidden="true"></i><h2 class="mt-4 font-serif text-2xl">Opening hours</h2><p class="mt-2 text-sm leading-6 text-gray-600"><?= e(setting('business_hours', 'Monday–Saturday, 9 AM–5 PM')) ?></p><p class="mt-4 text-xs leading-5 text-gray-500">Planning a visit for a consultation? <a class="font-semibold text-forest underline" href="<?= e(url('consultations.php#book')) ?>">Book a slot</a> so we can prepare samples for you.</p></div>
    </aside>
</section>
<?php if ($faqs): ?>
<section class="bg-cream py-16">
    <div class="mx-auto max-w-3xl px-5"><p class="eyebrow text-center">Good to know</p><h2 class="mt-2 text-center font-serif text-3xl">A few helpful answers</h2>
        <div class="mt-8 grid gap-3"><?php foreach ($faqs as $faq): ?><details class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-ink/5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold"><?= e($faq['question']) ?><i class="fa-solid fa-plus text-sm text-sage transition group-open:rotate-45" aria-hidden="true"></i></summary><p class="mt-3 text-sm leading-7 text-gray-600"><?= e($faq['answer']) ?></p></details><?php endforeach; ?></div>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
