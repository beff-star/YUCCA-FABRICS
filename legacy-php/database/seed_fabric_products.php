<?php
declare(strict_types=1);

// Adds the fabric lookbook (includes/fabric-collection.php) to the shop as products with photos.
// Usage: C:\xampp\php\php.exe database\seed_fabric_products.php
// Safe to re-run: existing products (matched by slug) are left untouched, so admin edits survive.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

// Placeholder prices (KES per metre) by fabric type, in line with the original demo catalogue.
// Replace them with real prices in Admin → Products.
const PLACEHOLDER_PRICES = ['Linen' => 1850, 'Bouclé' => 2400, 'Velvet' => 2600, 'Linen & velvet' => 2600, 'Fur' => 2800, 'Inverted leather' => 3200];
const PLACEHOLDER_STOCK = 40;
const FEATURED = ['santa-boucle.jpeg', 'bella-fur.jpeg', 'coco-boucle.jpeg', 'harmony-linen.jpeg'];

// Colour and a description of what each photo shows, keyed by image file.
$details = [
    'santa-boucle.jpeg' => ['Charcoal & ivory', 'A herringbone bouclé in charcoal and ivory with a soft, nubby handle. Pictured on a sectional sofa and lounge chair; a striking choice for statement chairs, sofas and cushions.'],
    'bella-fur.jpeg' => ['Forest green', 'A deep forest-green faux fur with a ribbed, channelled pile. Shown on a lounge chair and sofa for a plush, tactile look.'],
    'costa-linen.jpeg' => ['Charcoal', 'A salt-and-pepper charcoal linen with a slubby, tweed-like texture. Pictured on a deep sofa with matching cushions.'],
    'new-cubra-linen.jpeg' => ['Slate blue', 'A slate-blue linen with a fine, even weave, shown on a sofa and a curved tub chair.'],
    'coco-boucle.jpeg' => ['Mustard', 'A warm mustard bouclé with a crinkled, cloud-like surface, pictured on a modular sofa, armchair and throw.'],
    'dona-linen.jpeg' => ['Graphite', 'A deep graphite linen with a soft, matte finish, shown on a low modular sofa.'],
    'liza-boucle.jpeg' => ['Rust', 'A rust-orange bouclé with a dense, curly texture, pictured on a curved sofa and armchairs.'],
    'cito-linen.jpeg' => ['Crimson', 'A rich crimson linen with a textured weave, shown on a sectional sofa and cushions.'],
    'prox-velvet.jpeg' => ['Denim blue', 'A denim-blue velvet with a crushed, textured pile, pictured on a channel-stitched sofa.'],
    'karla-linen.jpeg' => ['Sand', 'A sand-beige linen with a nubby, natural-looking weave, shown on rounded sofas and cushions.'],
    'lenen-linen.jpeg' => ['Burnt orange', 'A burnt-orange linen with a soft, pebbled texture, pictured on a sofa and a timber-framed armchair.'],
    'rock-velvet.jpeg' => ['Camel', 'A camel-toned velvet with a gentle sheen, shown on a deep sofa and throw.'],
    'soho-inverted-leather.jpeg' => ['Stone taupe', 'A stone-taupe inverted leather with a crackled, embossed surface, pictured on dining chairs and a sofa.'],
    'harmony-linen.jpeg' => ['Navy blue', 'A navy linen with a looped, bouclé-like texture, shown on a chaise sofa and swivel armchair.'],
    'ferrari-linen-velvet.jpeg' => ['Chocolate', 'A chocolate-brown linen and velvet fabric with a soft, brushed finish, pictured on a modular sofa.'],
    'new-tokyo-linen.jpeg' => ['Mocha', 'A mocha linen with a fine, flecked weave, shown on a corner sofa and cushions.'],
    'verola-linen.jpeg' => ['Caramel', 'A caramel linen with an open basket weave, pictured on a sofa and cushions.'],
    'koya-linen.jpeg' => ['Dusty rose', 'A dusty-rose linen with a two-tone woven texture, shown on a corner sofa and armchair.'],
];

$source = APP_ROOT . '/assets/images/fabrics';
$target = APP_ROOT . '/uploads/products';
if (!is_dir($target) && !mkdir($target, 0750, true) && !is_dir($target)) {
    fwrite(STDERR, "Cannot create {$target}\n");
    exit(1);
}

$pdo = db();
$categoryId = $pdo->query("SELECT id FROM categories WHERE slug='upholstery-fabrics'")->fetchColumn();
if ($categoryId === false) {
    fwrite(STDERR, "Category 'upholstery-fabrics' is missing; import database/yucca_fabrics.sql first.\n");
    exit(1);
}

$insert = $pdo->prepare('INSERT IGNORE INTO products (category_id,name,slug,sku,description,price,stock,unit,colour,material,image,is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
$added = 0;
$pdo->beginTransaction();
try {
    foreach (fabric_collection() as $index => $fabric) {
        $file = $fabric['image'];
        if (!isset($details[$file], PLACEHOLDER_PRICES[$fabric['type']])) {
            throw new RuntimeException("No product details for {$file}; add it to \$details.");
        }
        if (!is_file("{$target}/{$file}") && !copy("{$source}/{$file}", "{$target}/{$file}")) {
            throw new RuntimeException("Could not copy {$file} to uploads/products.");
        }
        [$colour, $description] = $details[$file];
        $insert->execute([
            $categoryId,
            "{$fabric['name']} " . ucwords($fabric['type']),
            pathinfo($file, PATHINFO_FILENAME),
            sprintf('YF-%04d', 2001 + $index),
            $description . ' Sold by the metre; ask us for a swatch or a quote to upholster your piece.',
            PLACEHOLDER_PRICES[$fabric['type']],
            PLACEHOLDER_STOCK,
            'metre',
            $colour,
            $fabric['type'],
            $file,
            (int) in_array($file, FEATURED, true),
        ]);
        $added += $insert->rowCount();
    }

    // Demo products from yucca_fabrics.sql: Terracotta Weave matches the rust close-up photo; the others have
    // no matching photo, so they are hidden (not deleted) while they still have none. Re-enable in admin.
    if (!is_file("{$target}/rust-tweed-texture.jpeg") && !copy("{$source}/rust-tweed-texture.jpeg", "{$target}/rust-tweed-texture.jpeg")) {
        throw new RuntimeException('Could not copy rust-tweed-texture.jpeg to uploads/products.');
    }
    $pdo->exec("UPDATE products SET image='rust-tweed-texture.jpeg' WHERE slug='terracotta-weave' AND image IS NULL");
    $hidden = $pdo->exec("UPDATE products SET is_active=0 WHERE slug IN ('olive-grove-linen-blend','cloud-cotton-voile','coastal-stripe-performance') AND image IS NULL AND is_active=1");
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, 'Seeding failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo "Added {$added} fabric products; hid {$hidden} demo products without photos.\n";
