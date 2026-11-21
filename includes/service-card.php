<?php
// Included inside service loops with $service set to a row from active_services().
/** @var array $service */
$details = service_details($service['slug']);
$light = $details['theme']['text'] === 'light';
$accent = $details['theme']['bg'];
// 90% keeps secondary text at 5:1 or better on every theme colour (80% dropped below AA on terracotta).
$muted = $light ? 'text-white/90' : 'text-ink/90';
$isConsultation = is_consultation_service($service);
if ($isConsultation) {
    $ctaLabel = 'Book this session';
    $ctaHref = url('consultations.php' . ($details['consultation_type'] !== '' ? '?type=' . rawurlencode($details['consultation_type']) : '') . '#book');
    $ctaFill = ['select' => '#consultation-type', 'value' => $details['consultation_type']];
} else {
    $ctaLabel = 'Request a quote';
    $ctaHref = '#request-form';
    $ctaFill = ['select' => '#service-id', 'value' => (string) (int) $service['id']];
}
$meta = array_filter([
    $service['duration'] ? ['fa-clock', $service['duration']] : null,
    $service['starting_price'] !== null ? ['fa-tag', 'From ' . money_whole($service['starting_price'])] : null,
]);
?>
<article class="group flex flex-col overflow-hidden rounded-3xl shadow-xl shadow-ink/15 <?= $light ? 'text-white' : 'text-ink' ?>" style="background-color:<?= e($accent) ?>">
    <div class="relative aspect-[16/10] overflow-hidden">
        <img src="<?= e(service_image($details['image'])) ?>" alt="<?= e($details['image_alt']) ?>" loading="lazy" class="h-full w-full object-cover motion-safe:transition motion-safe:duration-700 motion-safe:group-hover:scale-105">
        <span class="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent"></span>
        <span class="absolute left-5 top-5 rounded-full bg-white/90 px-3 py-1 text-[10px] font-semibold uppercase tracking-[.18em] text-ink"><?= e($service['category_name'] ?? 'Yucca service') ?></span>
    </div>
    <div class="relative flex flex-1 flex-col p-6 sm:p-8">
        <span class="absolute -top-7 right-6 grid h-14 w-14 place-items-center rounded-2xl bg-white text-xl shadow-lg" style="color:<?= e($light ? $accent : '#2A2B25') ?>"><i class="fa-solid <?= e($details['icon']) ?>" aria-hidden="true"></i></span>
        <h3 class="pr-16 font-serif text-2xl sm:text-3xl"><?= e($service['name']) ?></h3>
        <?php if ($details['tagline'] !== ''): ?><p class="mt-1 text-sm font-semibold"><?= e($details['tagline']) ?></p><?php endif; ?>
        <p class="mt-4 text-[15px] leading-7"><?= e($service['description']) ?></p>
        <?php if ($details['story'] !== ''): ?><p class="mt-3 text-sm leading-7 <?= $muted ?>"><?= e($details['story']) ?></p><?php endif; ?>
        <?php if ($details['includes']): ?>
            <p class="mt-6 text-[11px] font-semibold uppercase tracking-[.2em] <?= $muted ?>">What’s included</p>
            <ul class="mt-3 grid gap-2.5 text-sm leading-6">
                <?php foreach ($details['includes'] as $item): ?><li class="flex gap-3"><i class="fa-solid fa-circle-check mt-1 shrink-0" aria-hidden="true"></i><span><?= e($item) ?></span></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($details['ideal'] !== ''): ?><p class="mt-6 rounded-2xl <?= $light ? 'bg-white/10' : 'bg-black/10' ?> p-4 text-xs leading-5"><strong class="font-semibold">Ideal for:</strong> <?= e($details['ideal']) ?></p><?php endif; ?>
        <div class="mt-auto flex flex-wrap items-center justify-between gap-4 pt-7">
            <div class="flex flex-wrap gap-2 text-xs"><?php foreach ($meta as [$icon, $label]): ?><span class="inline-flex items-center gap-1.5 rounded-full border <?= $light ? 'border-white/35' : 'border-ink/25' ?> px-3 py-1.5"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i><?= e($label) ?></span><?php endforeach; ?></div>
            <a href="<?= e($ctaHref) ?>" data-fill-select="<?= e($ctaFill['select']) ?>" data-fill-value="<?= e($ctaFill['value']) ?>" class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold shadow-md transition hover:-translate-y-0.5 <?= $light ? 'bg-white' : 'bg-ink text-white' ?>"<?= $light ? ' style="color:' . e($accent) . '"' : '' ?>><?= e($ctaLabel) ?> <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i><span class="sr-only"> for <?= e($service['name']) ?></span></a>
        </div>
    </div>
</article>
