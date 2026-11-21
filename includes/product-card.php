<?php
// Included inside product loops with $product set to a products row (p.*).
/** @var array $product */
$productUrl = url('product-details.php?slug=' . rawurlencode($product['slug'])); $onSale = $product['sale_price'] !== null && (float) $product['sale_price'] < (float) $product['price']; ?>
<article class="group flex flex-col overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-ink/5 transition motion-safe:hover:-translate-y-1 hover:shadow-lg">
    <a href="<?= e($productUrl) ?>" class="relative block aspect-[4/5] overflow-hidden bg-cream">
        <img src="<?= e(product_image($product['image'] ?? null)) ?>" alt="<?= e($product['name']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-700 motion-safe:group-hover:scale-105">
        <?php if ($onSale): ?><span class="absolute left-3 top-3 rounded-full bg-clay px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-white">Sale</span><?php elseif ((int) $product['stock'] === 0): ?><span class="absolute left-3 top-3 rounded-full bg-ink/80 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-white">Sold out</span><?php endif; ?>
    </a>
    <div class="flex flex-1 flex-col p-3 sm:p-4">
        <p class="truncate text-[10px] uppercase tracking-[.16em] text-sage"><?= e($product['material'] ?: ($product['category_name'] ?? 'Yucca collection')) ?></p>
        <h3 class="mt-1.5 font-serif text-base leading-snug sm:text-lg"><a href="<?= e($productUrl) ?>"><?= e($product['name']) ?></a></h3>
        <?php if (!empty($product['colour'])): ?><p class="mt-0.5 truncate text-xs text-gray-500"><?= e($product['colour']) ?></p><?php endif; ?>
        <?php if(isset($product['average_rating'])&&$product['average_rating']!==null): ?><p class="mt-1 text-xs text-clay" aria-label="Average customer rating <?= e(number_format((float)$product['average_rating'],1)) ?> out of 5">★ <?= e(number_format((float)$product['average_rating'],1)) ?> / 5</p><?php endif; ?>
        <p class="mt-2 text-sm font-semibold text-forest"><?= product_price_html($product) ?></p>
        <div class="mt-auto flex items-center gap-2 pt-3">
            <?php if ((int) $product['stock'] > 0): ?><button data-add-cart="<?= (int) $product['id'] ?>" class="flex-1 rounded-full border border-forest px-3 py-2 text-xs font-semibold text-forest transition hover:bg-forest hover:text-white">Add to bag</button><?php else: ?><a href="<?= e(url('contact.php')) ?>" class="flex-1 rounded-full border border-ink/20 px-3 py-2 text-center text-xs font-semibold text-gray-600">Ask about stock</a><?php endif; ?>
            <?php if (current_user() && current_user()['role']==='customer'): ?><button data-add-wishlist="<?= (int)$product['id'] ?>" class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-clay hover:bg-cream" aria-label="Save <?= e($product['name']) ?> to favourites"><i class="fa-regular fa-heart" aria-hidden="true"></i></button><?php endif; ?>
        </div>
    </div>
</article>
