<?php
require_once __DIR__ . '/includes/functions.php';
$consultations = active_services(true);
$prices = array_map('floatval', array_filter(array_column($consultations, 'starting_price'), fn($price) => $price !== null));
$durations = array_values(array_unique(array_filter(array_column($consultations, 'duration'))));
$selectedType = in_array($_GET['type'] ?? '', CONSULTATION_TYPES, true) ? (string) $_GET['type'] : '';
$times = ['09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00'];
$helpAreas = [
    ['fa-house', '#1F5B43', 'text-white', 'Homes', 'Living rooms, bedrooms, home offices and the spaces in between, planned around how your household really lives.'],
    ['fa-building', '#16646E', 'text-white', 'Commercial spaces', 'Hospitality, retail and workspaces that need to impress on day one and stand up to daily use.'],
    ['fa-palette', '#A9461E', 'text-white', 'Fabric & colour', 'Texture, colour coordination, curtains and upholstery materials that work together across a room.'],
    ['fa-couch', '#E3A82B', 'text-ink', 'Layout & furnishing', 'Making the most of what you have, and knowing exactly what to add, move or let go.'],
];
$steps = [
    ['fa-calendar-check', 'Request a slot', 'Pick a date, a time and online or in person using the form below.'],
    ['fa-envelope-open-text', 'We confirm', 'We check the slot and confirm your appointment with you before it is final.'],
    ['fa-comments', 'Let’s talk it through', 'We look at your space, photos and ideas together, and ask the right questions.'],
    ['fa-lightbulb', 'Your next steps', 'You leave with fabric, colour and layout suggestions you can act on.'],
];
$prepare = [
    ['fa-camera', 'Photos of the room', 'A few wide shots in daylight, plus any pieces you’re unsure about.'],
    ['fa-ruler-combined', 'Rough measurements', 'Wall lengths, window sizes and the furniture you plan to keep.'],
    ['fa-images', 'Inspiration you love', 'Screenshots, magazine pages or a fabric you’ve spotted in our shop.'],
    ['fa-wallet', 'A budget range', 'Even a rough figure helps us focus on what will make the biggest difference.'],
];
$pageTitle = 'Interior design consultation | Yucca Fabrics';
$description = 'Book an interior design consultation with Yucca Fabrics, online or in person: fabric and colour advice, room styling and layout.';
require __DIR__ . '/includes/header.php';
?>
<section class="relative grid overflow-hidden bg-forest text-white lg:min-h-[560px] lg:grid-cols-2">
    <?= yucca_sprig_svg('pointer-events-none absolute -left-16 bottom-0 h-80 w-80 text-white/5') ?>
    <div class="relative flex flex-col justify-center px-5 py-16 sm:px-8 lg:py-20 lg:pl-[max(1.25rem,calc((100vw-80rem)/2+1.25rem))]">
        <p class="eyebrow">Interior design consultations</p>
        <h1 class="mt-4 font-serif text-4xl leading-[1.1] sm:text-5xl lg:text-6xl">A room that feels<br><span class="text-wheat">like it’s yours.</span></h1>
        <p class="mt-6 max-w-lg text-base leading-8 text-white/80">Talk through colour, fabric, layout and furnishing with a thoughtful design partner. Online or in person, we begin with what matters to you and finish with a plan you can act on.</p>
        <div class="mt-8 flex flex-wrap gap-3"><a href="#book" class="rounded-full bg-wheat px-6 py-3 text-sm font-semibold text-night shadow-md transition hover:bg-white">Book a consultation</a><?php if ($consultations): ?><a href="#packages" class="rounded-full border border-white/40 px-6 py-3 text-sm font-semibold transition hover:bg-white/10">Compare sessions</a><?php endif; ?></div>
        <ul class="mt-10 flex flex-wrap gap-3 text-xs font-medium">
            <li class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 ring-1 ring-white/15"><i class="fa-solid fa-video text-wheat" aria-hidden="true"></i>Online or in person</li>
            <?php if ($durations): ?><li class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 ring-1 ring-white/15"><i class="fa-solid fa-clock text-wheat" aria-hidden="true"></i><?= e(implode(' or ', $durations)) ?></li><?php endif; ?>
            <?php if ($prices): ?><li class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 ring-1 ring-white/15"><i class="fa-solid fa-tag text-wheat" aria-hidden="true"></i>From <?= e(money_whole(min($prices))) ?></li><?php endif; ?>
        </ul>
    </div>
    <div class="relative min-h-[320px]">
        <img src="<?= e(service_image('consultation-material-samples.jpg')) ?>" alt="A designer arranging fabric and material samples on a desk" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute bottom-5 left-5 right-5 flex items-center gap-3 rounded-2xl bg-white/95 p-4 text-ink shadow-xl sm:left-auto sm:max-w-xs"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[#16646E] text-white"><i class="fa-solid fa-swatchbook" aria-hidden="true"></i></span><span class="text-xs leading-5 text-gray-600"><strong class="block text-sm text-ink">Samples at hand</strong>Compare <?= count(fabric_collection()) ?> textures from our collection</span></div>
    </div>
</section>

<?php if ($consultations): ?>
<section id="packages" class="py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="mx-auto max-w-2xl text-center"><p class="eyebrow">Choose your session</p><h2 class="mt-3 font-serif text-4xl sm:text-5xl">Advice shaped around your space</h2><p class="mt-4 text-sm leading-7 text-gray-600">Start with the fabric, or step back and plan the whole room. Either way you’ll talk to someone who knows how materials look, feel and wear at home.</p></div>
        <div class="mx-auto mt-14 grid max-w-5xl gap-8 md:grid-cols-2"><?php foreach ($consultations as $service): require __DIR__ . '/includes/service-card.php'; endforeach; ?></div>
    </div>
</section>
<?php endif; ?>

<section class="bg-cream py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="max-w-2xl"><p class="eyebrow">What we can help with</p><h2 class="mt-3 font-serif text-4xl">Every room has its own story</h2></div>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($helpAreas as [$icon, $colour, $iconText, $title, $text]): ?>
                <article class="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-ink/5 transition motion-safe:hover:-translate-y-1 hover:shadow-lg"><span class="grid h-14 w-14 place-items-center rounded-2xl text-xl <?= $iconText ?>" style="background-color:<?= e($colour) ?>"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><h3 class="mt-6 font-serif text-xl"><?= e($title) ?></h3><p class="mt-3 text-sm leading-6 text-gray-600"><?= e($text) ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="mx-auto max-w-2xl text-center"><p class="eyebrow">How it works</p><h2 class="mt-3 font-serif text-4xl">Four simple steps</h2></div>
        <ol class="relative mt-14 grid gap-10 md:grid-cols-4 md:gap-6">
            <span class="absolute left-[12.5%] right-[12.5%] top-8 hidden h-0.5 bg-gradient-to-r from-[#1F5B43] via-[#E3A82B] to-[#A9461E] md:block" aria-hidden="true"></span>
            <?php foreach ($steps as $index => [$icon, $title, $text]): ?>
                <li class="relative text-center"><span class="relative mx-auto grid h-16 w-16 place-items-center rounded-full bg-white text-xl text-forest shadow-lg ring-4 ring-sand"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i><span class="absolute -right-1 -top-1 grid h-6 w-6 place-items-center rounded-full bg-clay text-[11px] font-bold text-white"><?= $index + 1 ?></span></span><h3 class="mt-5 text-lg font-semibold"><?= e($title) ?></h3><p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-gray-600"><?= e($text) ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="relative overflow-hidden bg-night py-20 text-white">
    <?= yucca_sprig_svg('pointer-events-none absolute -right-16 -top-16 h-96 w-96 text-white/5') ?>
    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-2">
        <div>
            <p class="eyebrow">Before we meet</p>
            <h2 class="mt-3 font-serif text-4xl">How to prepare</h2>
            <p class="mt-4 max-w-lg text-sm leading-7 text-cream/70">None of this is required, but a little preparation helps us make the most of your time together.</p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                <?php foreach ($prepare as [$icon, $title, $text]): ?><li class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10"><i class="fa-solid <?= e($icon) ?> text-xl text-wheat" aria-hidden="true"></i><h3 class="mt-3 font-semibold"><?= e($title) ?></h3><p class="mt-1 text-sm leading-6 text-cream/70"><?= e($text) ?></p></li><?php endforeach; ?>
            </ul>
        </div>
        <img src="<?= e(service_image('design-desk-swatches.jpg')) ?>" alt="Fabric swatches and sketches laid out on a design desk" loading="lazy" class="aspect-square w-full rounded-3xl object-cover shadow-2xl">
    </div>
</section>

<section id="book" class="bg-cream py-20">
    <div class="mx-auto max-w-7xl px-5">
        <div class="overflow-hidden rounded-3xl bg-white shadow-xl shadow-ink/10 lg:grid lg:grid-cols-[.8fr_1.2fr]">
            <aside class="relative overflow-hidden bg-[#16646E] p-8 text-white sm:p-10">
                <?= yucca_sprig_svg('pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 text-white/10') ?>
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-[.22em] text-white/90">Let’s make a plan</p>
                    <h2 class="mt-3 font-serif text-4xl">Request a consultation</h2>
                    <p class="mt-4 text-sm leading-7 text-white/85">Choose a preferred slot and we’ll confirm it with you before your appointment is final. A slot can only be held once, so if your first choice has just been taken, please pick another.</p>
                    <ul class="mt-8 grid gap-4 text-sm">
                        <li class="flex gap-3"><i class="fa-solid fa-video mt-1" aria-hidden="true"></i><span>Meet online from anywhere, or in person.</span></li>
                        <li class="flex gap-3"><i class="fa-solid fa-calendar-check mt-1" aria-hidden="true"></i><span>Appointments during opening hours<?= setting('business_hours') !== '' ? ': ' . e(setting('business_hours')) : '' ?>.</span></li>
                        <li class="flex gap-3"><i class="fa-solid fa-camera mt-1" aria-hidden="true"></i><span>Add a photo so we can prepare in advance.</span></li>
                    </ul>
                    <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" class="mt-10 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#16646E]"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>Questions? WhatsApp us</a>
                </div>
            </aside>
            <form data-ajax-form enctype="multipart/form-data" action="<?= e(url('api/consultations.php')) ?>" method="post" class="grid gap-4 p-8 sm:grid-cols-2 sm:p-10">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="text-sm font-medium">Full name<input class="form-control mt-1" name="name" required maxlength="150" autocomplete="name"></label>
                <label class="text-sm font-medium">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email"></label>
                <label class="text-sm font-medium">Phone<input class="form-control mt-1" type="tel" name="phone" required autocomplete="tel"></label>
                <label class="text-sm font-medium">Consultation type<select id="consultation-type" class="form-control mt-1" name="consultation_type" required><option value="">Choose one</option><?php foreach (CONSULTATION_TYPES as $type): ?><option<?= $type === $selectedType ? ' selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
                <label class="text-sm font-medium">Property type<select class="form-control mt-1" name="property_type" required><option value="">Choose one</option><option>Home</option><option>Apartment</option><option>Office</option><option>Hospitality / retail</option><option>Other</option></select></label>
                <label class="text-sm font-medium">Property location<input class="form-control mt-1" name="location" required maxlength="255"></label>
                <label class="text-sm font-medium">Preferred date<input class="form-control mt-1" type="date" name="preferred_date" min="<?= e(date('Y-m-d')) ?>" required></label>
                <label class="text-sm font-medium">Preferred time<select class="form-control mt-1" name="preferred_time" required><option value="">Choose a time</option><?php foreach ($times as $time): ?><option value="<?= e($time) ?>"><?= e(date('g:i A', strtotime($time))) ?></option><?php endforeach; ?></select></label>
                <label class="text-sm font-medium">Budget range (optional)<select class="form-control mt-1" name="budget"><option value="">Prefer not to say</option><option>Under KES 50,000</option><option>KES 50,000–150,000</option><option>KES 150,000–500,000</option><option>KES 500,000+</option></select></label>
                <label class="text-sm font-medium">Consultation method<select class="form-control mt-1" name="method" required><option value="online">Online</option><option value="in_person">In person</option></select></label>
                <label class="text-sm font-medium sm:col-span-2">Project description<textarea class="form-control mt-1" name="description" rows="4" minlength="10" maxlength="5000" required placeholder="Tell us about the space, what you’d like to change and anything you want to keep."></textarea></label>
                <label class="text-sm font-medium sm:col-span-2">Reference photo (optional)<input class="form-control mt-1 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-cream file:px-4 file:py-1.5 file:text-sm file:font-semibold file:text-forest" type="file" name="reference_image" accept="image/jpeg,image/png,image/webp"></label>
                <button class="inline-flex w-fit items-center gap-2 rounded-full bg-forest px-7 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-olive sm:col-span-2">Request my consultation <i class="fa-solid fa-calendar-check text-xs" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
