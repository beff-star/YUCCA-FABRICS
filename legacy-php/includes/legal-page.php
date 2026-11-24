<?php
// Shared layout for the legal pages. Set $legalTitle, $legalBody (trusted HTML from the page file),
// $legalActive ('privacy' or 'terms') and $legalDraftNote, then include this file.
$isAdmin = (current_user()['role'] ?? '') === 'admin';
$legalLinks = ['privacy' => ['privacy-policy.php', 'Privacy policy'], 'terms' => ['terms.php', 'Terms of use']];
require __DIR__ . '/header.php';
?>
<section class="bg-cream px-5 py-14 text-center"><p class="eyebrow">The important details</p><h1 class="mt-3 font-serif text-4xl sm:text-5xl"><?= e($legalTitle) ?></h1></section>
<section class="mx-auto grid max-w-6xl gap-8 px-5 py-12 lg:grid-cols-[1fr_260px]">
    <article class="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-ink/5 sm:p-10">
        <?php if ($isAdmin): ?><p class="mb-6 flex gap-3 rounded-2xl bg-[#FBF0D9] p-4 text-sm text-[#6B4A07]"><i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i><span><strong>Only admins see this:</strong> <?= e($legalDraftNote) ?></span></p><?php endif; ?>
        <div class="legal-body"><?= $legalBody ?></div>
    </article>
    <aside class="grid h-fit gap-4 lg:sticky lg:top-28">
        <nav class="rounded-3xl bg-white p-3 shadow-sm ring-1 ring-ink/5" aria-label="Legal pages"><?php foreach ($legalLinks as $key => [$path, $label]): ?><a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition <?= $key === $legalActive ? 'bg-forest text-white' : 'hover:bg-cream' ?>" href="<?= e(url($path)) ?>"<?= $key === $legalActive ? ' aria-current="page"' : '' ?>><i class="fa-solid <?= $key === 'privacy' ? 'fa-shield-halved' : 'fa-file-lines' ?> w-4 text-center" aria-hidden="true"></i><?= e($label) ?></a><?php endforeach; ?></nav>
        <div class="rounded-3xl bg-cream p-6 text-sm"><i class="fa-regular fa-comments text-xl text-sage" aria-hidden="true"></i><p class="mt-3 font-semibold">Questions?</p><p class="mt-1 leading-6 text-gray-600">We’re happy to explain anything here.</p><a class="mt-3 inline-flex items-center gap-2 font-semibold text-forest" href="<?= e(url('contact.php')) ?>">Contact us <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a></div>
    </aside>
</section>
<?php require __DIR__ . '/footer.php'; ?>
