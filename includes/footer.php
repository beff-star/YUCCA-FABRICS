<?php
$footerPhone = setting('business_phone', '+254 700 000 000');
$footerEmail = setting('business_email', 'hello@yuccafabrics.co.ke');
$promises = [
    ['fa-swatchbook', 'A curated fabric collection', 'Bouclé, linen, velvet and more, chosen to be lived with.'],
    ['fa-couch', 'Skilled upholstery', 'Reupholstery and cushion renewal for much-loved pieces.'],
    ['fa-mobile-screen-button', 'Pay your way', 'M-Pesa, bank transfer or cash on delivery.'],
    ['fa-truck-fast', 'Delivered to your door', 'Fabric and finished pieces brought to you.'],
];
?>
</main>
<footer class="bg-night text-cream/75">
    <div class="border-b border-white/10">
        <ul class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-5 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($promises as [$icon, $title, $text]): ?>
                <li class="flex gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/5 text-wheat ring-1 ring-white/10"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><span><span class="block text-sm font-semibold text-white"><?= e($title) ?></span><span class="mt-1 block text-xs leading-5 text-cream/60"><?= e($text) ?></span></span></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-5 py-14 sm:grid-cols-2 lg:grid-cols-12">
        <div class="sm:col-span-2 lg:col-span-5 lg:pr-10">
            <a href="<?= e(url('index.php')) ?>" aria-label="Yucca Fabrics home" class="inline-flex items-center gap-3"><img src="<?= e(url('assets/images/yucca-fabrics-logo.jpeg')) ?>" alt="" width="64" height="64" class="h-16 w-16 rounded-xl bg-white object-contain p-1"><span class="leading-none"><span class="block font-serif text-xl font-semibold tracking-[.18em] text-white">YUCCA</span><span class="mt-1 block text-[10px] tracking-[.42em] text-wheat">FABRICS</span></span></a>
            <p class="mt-5 max-w-sm text-sm leading-7">A softer place to land. Fabric, furnishing and thoughtful interiors crafted around the way you live.</p>
            <form data-ajax-form action="<?= e(url('api/newsletter.php')) ?>" method="post" class="mt-6 max-w-sm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="text-xs font-semibold uppercase tracking-[.2em] text-wheat" for="newsletter-email">Notes from the studio</label>
                <p class="mt-2 text-xs text-cream/60">New fabrics, room ideas and the occasional offer. No spam.</p>
                <div class="mt-3 flex overflow-hidden rounded-full bg-white/5 ring-1 ring-white/15 focus-within:ring-wheat"><input id="newsletter-email" name="email" type="email" required placeholder="Your email address" autocomplete="email" class="min-w-0 flex-1 bg-transparent px-5 py-3 text-sm text-white placeholder:text-cream/40 focus:outline-none"><button class="m-1 rounded-full bg-wheat px-5 text-sm font-semibold text-night transition hover:bg-white">Subscribe</button></div>
            </form>
            <div class="mt-6 flex gap-2">
                <?php foreach (social_links() as $social): ?><a class="social-link" href="<?= e($social['url']) ?>" target="_blank" rel="noopener" aria-label="Yucca Fabrics on <?= e($social['label']) ?> (opens in a new tab)" title="<?= e($social['label']) ?>"><i class="fa-brands <?= e($social['icon']) ?>" aria-hidden="true"></i></a><?php endforeach; ?>
            </div>
        </div>
        <div class="lg:col-span-2"><h2 class="footer-heading">Shop</h2><ul class="footer-list"><li><a href="<?= e(url('shop.php')) ?>">All fabrics</a></li><li><a href="<?= e(url('shop.php?sort=newest')) ?>">New arrivals</a></li><li><a href="<?= e(url('gallery.php#fabric-lookbook')) ?>">Fabric lookbook</a></li><li><a href="<?= e(url('cart.php')) ?>">Shopping bag</a></li><li><a href="<?= e(url(current_user() ? 'account/orders.php' : 'auth/login.php')) ?>">Track an order</a></li></ul></div>
        <div class="lg:col-span-2"><h2 class="footer-heading">Studio</h2><ul class="footer-list"><li><a href="<?= e(url('services.php')) ?>">Upholstery services</a></li><li><a href="<?= e(url('consultations.php')) ?>">Interior design</a></li><li><a href="<?= e(url('gallery.php')) ?>">Our projects</a></li><li><a href="<?= e(url('blog.php')) ?>">Journal</a></li><li><a href="<?= e(url('about.php')) ?>">About us</a></li></ul></div>
        <div class="sm:col-span-2 lg:col-span-3"><h2 class="footer-heading">Visit or call</h2>
            <ul class="footer-list">
                <li class="flex gap-3"><i class="fa-solid fa-location-dot mt-1 w-4 text-wheat" aria-hidden="true"></i><span><?= e(setting('business_address', 'Nairobi, Kenya')) ?></span></li>
                <li class="flex gap-3"><i class="fa-solid fa-phone mt-1 w-4 text-wheat" aria-hidden="true"></i><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $footerPhone)) ?>"><?= e($footerPhone) ?></a></li>
                <li class="flex gap-3"><i class="fa-regular fa-envelope mt-1 w-4 text-wheat" aria-hidden="true"></i><a class="break-all" href="mailto:<?= e($footerEmail) ?>"><?= e($footerEmail) ?></a></li>
                <?php if (setting('business_hours') !== ''): ?><li class="flex gap-3"><i class="fa-regular fa-clock mt-1 w-4 text-wheat" aria-hidden="true"></i><span><?= e(setting('business_hours')) ?></span></li><?php endif; ?>
            </ul>
            <a href="<?= e(url('contact.php')) ?>" class="mt-6 inline-flex items-center gap-2 rounded-full border border-wheat/60 px-5 py-2.5 text-sm font-semibold text-wheat transition hover:bg-wheat hover:text-night">Get in touch <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 pb-24 pt-6 text-xs text-cream/55 sm:flex-row sm:items-center sm:justify-between sm:pb-6 sm:pr-24">
            <p>© <?= date('Y') ?> Yucca Fabrics. Made with care in Kenya.</p>
            <nav class="flex flex-wrap gap-x-5 gap-y-2" aria-label="Legal"><a class="hover:text-wheat" href="<?= e(url('privacy-policy.php')) ?>">Privacy policy</a><a class="hover:text-wheat" href="<?= e(url('terms.php')) ?>">Terms of service</a><a class="hover:text-wheat" href="<?= e(url('contact.php')) ?>">Contact</a></nav>
        </div>
    </div>
</footer>
<a href="#top" id="back-to-top" class="back-to-top" aria-label="Back to top"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></a>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
