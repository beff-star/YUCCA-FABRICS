<?php
// Opens the split sign-in layout. Set $authEyebrow, $authTitle and optional $authIntro before including,
// then close with includes/auth-end.php. The photo panel is hidden on small screens.
require __DIR__ . '/header.php';
$authBenefits = ['Follow your orders from bag to doorstep', 'Save favourite fabrics to compare later', 'Manage consultations and upholstery requests'];
?>
<section class="bg-cream px-5 py-10 sm:py-16">
    <div class="mx-auto grid max-w-6xl overflow-hidden rounded-3xl bg-white shadow-xl shadow-ink/10 lg:grid-cols-[1fr_1.05fr]">
        <div class="relative hidden min-h-[560px] lg:block">
            <img src="<?= e(fabric_image('harmony-linen.jpeg')) ?>" alt="" class="absolute inset-0 h-full w-full object-cover object-top">
            <span class="absolute inset-0 bg-gradient-to-t from-night via-night/70 to-night/10" aria-hidden="true"></span>
            <div class="absolute inset-x-0 bottom-0 p-10 text-white">
                <?= yucca_sprig_svg('h-12 w-12 text-wheat') ?>
                <p class="mt-4 font-serif text-3xl leading-tight">A softer place to land.</p>
                <ul class="mt-6 grid gap-3 text-sm text-white/90">
                    <?php foreach ($authBenefits as $benefit): ?><li class="flex gap-3"><i class="fa-solid fa-circle-check mt-0.5 text-wheat" aria-hidden="true"></i><?= e($benefit) ?></li><?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="p-7 sm:p-12">
            <p class="eyebrow"><?= e($authEyebrow) ?></p>
            <h1 class="mt-3 font-serif text-3xl sm:text-4xl"><?= e($authTitle) ?></h1>
            <?php if (!empty($authIntro)): ?><p class="mt-3 text-sm leading-6 text-gray-600"><?= e($authIntro) ?></p><?php endif; ?>
            <div class="mt-8">
