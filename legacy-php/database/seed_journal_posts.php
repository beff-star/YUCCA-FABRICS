<?php
declare(strict_types=1);

// Publishes the starter journal articles with cover photos.
// Usage: C:\xampp\php\php.exe database\seed_journal_posts.php
// Safe to re-run: posts are matched by slug and never overwritten, so edits made in admin survive.
// Formatting: blank line = new paragraph, "## " = heading, "- " = bullet (see journal_body_html()).

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$categories = ['fabric-notes' => 'Fabric Notes', 'at-home' => 'At Home', 'upholstery' => 'Upholstery'];

// Cover images are copied from the tracked assets into uploads/blog/, where admin uploads also live.
$posts = [
    [
        'category' => 'fabric-notes',
        'title' => 'Bouclé, linen or velvet? Choosing the right upholstery fabric',
        'slug' => 'choosing-upholstery-fabric',
        'image' => 'services/fabric-consultation-samples.jpg',
        'excerpt' => 'Three of the most loved upholstery fabrics, and how to choose between them for the way you really live.',
        'body' => <<<'TEXT'
The fabric you choose decides how a sofa looks on the first day and how it feels after five years. Bouclé, linen and velvet are three of the most popular upholstery textures, you’ll find all three in our collection, and each one suits a different kind of home.

## Bouclé: soft, sculptural and forgiving
Bouclé is woven from looped yarn, which gives it that nubby, cloud-like surface. It hides small marks well and looks especially good on curved chairs and modular sofas.
- Best for: statement chairs, sofas in adult living rooms, headboards
- Think twice if: a cat likes to climb the furniture, because claws can catch the loops

## Linen: relaxed and breathable
Linen and linen-look weaves have a natural, slightly textured finish that stays cool in warm weather. They soften with use and suit calm, neutral rooms.
- Best for: everyday sofas, dining chairs, cushions and curtains
- Think twice if: you want a crisp look all the time, since natural linen creases

## Velvet: rich colour and a gentle sheen
Velvet has a short, dense pile that catches the light, so colours look deeper and more luxurious. Modern velvets are far tougher than their reputation suggests.
- Best for: accent chairs, headboards, sofas in deep colours
- Think twice if: the piece sits in strong direct sun, which can fade any pile fabric over time

## Questions to ask before you choose
- Who uses the piece every day: children, pets, guests?
- Does it sit in direct sunlight?
- Do you want it to feel cool, cosy or somewhere in between?
- How do you plan to clean it?

If you’re still unsure, browse the fabric lookbook or book a fabric and colour consultation. Seeing and touching samples side by side makes the decision much easier.
TEXT,
    ],
    [
        'category' => 'fabric-notes',
        'title' => 'How to care for linen upholstery',
        'slug' => 'caring-for-linen-upholstery',
        'image' => 'fabrics/karla-linen.jpeg',
        'excerpt' => 'Simple habits that keep linen sofas and chairs looking fresh for years.',
        'body' => <<<'TEXT'
Linen is one of the most comfortable upholstery fabrics you can live with. A few simple habits keep it looking its best for years.

## Every week
- Vacuum with a soft brush attachment to lift dust before it settles into the weave
- Plump and turn seat cushions so they wear evenly
- Shake out scatter cushions and throws

## When something spills
Act quickly, but gently. Blot, never rub: rubbing pushes the spill deeper and can roughen the surface.
- Blot with a clean, dry white cloth, working from the outside in
- For water-based spills, dab with a cloth barely dampened with clean water
- Let it dry naturally, away from direct heat

Always check the care label first, and test any cleaner on a hidden area. If you’re unsure, ask us before you try anything stronger.

## Protect it from the sun
Strong sunlight fades every natural fibre over time. Draw sheer curtains during the brightest hours, or angle the sofa so it isn’t in the same patch of sun all afternoon.

## Once or twice a year
A professional deep clean refreshes the fabric and lifts the dust that daily vacuuming misses, which matters in dry, dusty months.

Looked after well, linen develops a softer, lived-in character that only gets better with time.
TEXT,
    ],
    [
        'category' => 'upholstery',
        'title' => 'Reupholster or replace? Five signs your sofa is worth saving',
        'slug' => 'reupholster-or-replace-your-sofa',
        'image' => 'services/reupholstery-tufted-corduroy.jpg',
        'excerpt' => 'Before you buy new, check whether the sofa you already own just needs a new cover and fresh padding.',
        'body' => <<<'TEXT'
A worn sofa is not always a finished sofa. Often the frame is perfectly sound and it is only the fabric and padding that have given up. Here’s how to tell.

## 1. The frame is solid
Sit down firmly and rock gently. If there are no creaks, wobbles or cracks, the frame is the most valuable part of the piece, and it is worth keeping.

## 2. You love the shape
Proportions that suit your room are hard to find. Reupholstery lets you keep a shape that works and change everything else.

## 3. The seats have sagged
Flat, sunken seats usually mean tired foam or loose webbing, not a broken sofa. Both can be renewed.

## 4. It has a story
A chair from a parent or grandparent carries memories no new piece can. New fabric gives it another chapter.

## 5. It was built to last
Older, well-made furniture is often heavier and sturdier than many flat-pack alternatives. If it was built well once, it can be enjoyed for many more years.

## When replacing makes more sense
- The frame is cracked, warped or infested
- The piece no longer fits your room or your needs
- The springs are broken throughout and the frame is weak

Not sure which side your sofa falls on? Send us a photo through our upholstery services page and we’ll tell you honestly what we think.
TEXT,
    ],
    [
        'category' => 'at-home',
        'title' => 'Bringing colour into a neutral room',
        'slug' => 'bringing-colour-into-a-neutral-room',
        'image' => 'services/cushion-renewal-mustard-cushion.jpg',
        'excerpt' => 'Calm, neutral rooms come alive with one confident colour. Here’s how to add it without starting again.',
        'body' => <<<'TEXT'
Neutral rooms are restful and easy to live with, but they can start to feel flat. The good news is that you don’t need to repaint to bring them to life.

## Start small with cushions and throws
Cushions are the easiest way to test a colour. Mustard, rust and deep green all sit beautifully against beige, grey and cream. Begin with two or three, and add a throw in a related shade.

## Choose one hero colour
Pick a single colour to lead, then repeat it three times around the room: a cushion, a vase, a print. Repetition makes a colour look intentional rather than accidental.

## Let texture do some of the work
A room in one colour still feels rich when the textures change. Mix a smooth linen with a nubby bouclé and a soft velvet, and the room gains depth without any loud pattern.

## Go bolder with one piece
Ready for more? Reupholster a single accent chair in a strong colour. It becomes the room’s focal point, and it is far easier to change later than a whole sofa.

## A few combinations to try
- Sand and stone walls with burnt orange and caramel
- Soft grey with forest green and ivory
- Warm white with navy, dusty rose and natural wood

Browse the fabric lookbook for colour ideas, or book a room styling consultation and we’ll build a palette with you.
TEXT,
    ],
    [
        'category' => 'at-home',
        'title' => 'How to measure for made-to-measure curtains',
        'slug' => 'how-to-measure-for-curtains',
        'image' => 'services/design-desk-swatches.jpg',
        'excerpt' => 'A step-by-step guide to measuring your windows so your curtains hang beautifully.',
        'body' => <<<'TEXT'
Good curtains start with good measurements. Take your time, use a steel tape measure, and write everything down.

## Before you start
Fit your track or pole first if you can. You measure from the hardware, not the window frame, so having it in place gives the most accurate result.

## Measure the width
- Measure the full length of the track or pole, not just the window
- For a pole, measure between the finials (the decorative ends)
- Allow the track to extend beyond the window on each side, so the curtains can open fully and let in more light

## Measure the drop
The drop is the finished length of the curtain. Decide where you want them to end, then measure from the top of the track, or from the eyelet or ring position on a pole.
- Sill length: to just above the window sill
- Below sill: about 15 cm below the sill
- Floor length: stop just above the floor so the hem doesn’t drag
- Puddled: add extra length for a soft, relaxed pool on the floor

## Think about fullness
Curtains look best when there is more fabric than the width of the track, so they fall in generous folds. The right amount depends on the heading style and the fabric, so tell us your track width and we’ll work out the fabric you need.

## Write it all down
Note each window separately, label them by room, and take a photo of the window with the track in place. It saves time and avoids costly mistakes.
TEXT,
    ],
    [
        'category' => 'at-home',
        'title' => 'Getting the most from an interior design consultation',
        'slug' => 'getting-the-most-from-a-design-consultation',
        'image' => 'services/room-styling-swatches-plans.jpg',
        'excerpt' => 'A little preparation turns one conversation into a clear plan for your room.',
        'body' => <<<'TEXT'
A design consultation is a focused conversation about your space. Come prepared, and you’ll leave with clear, practical next steps.

## Before the session
- Take wide photos of the room in daylight, from each corner
- Note the rough size of the room, the windows and any furniture you’re keeping
- Gather a few images of rooms you love, even if you can’t say why
- Think about a budget range, even a rough one

## During the session
Be honest about how you really use the room. Where do you sit in the evening? Where do bags and shoes end up? The best design solves everyday problems as well as looking good.

Bring any fabric, cushion or object you love. It often becomes the starting point for the whole palette.

## Questions worth asking
- What should we change first for the biggest difference?
- Which fabrics will stand up to our household?
- What can we keep, move or reuse?
- How do we add colour without overwhelming the room?

## After the session
Start with the changes that make the biggest difference, and work through the rest at your own pace. A good plan doesn’t have to be done all at once.

Ready to begin? Book a consultation online or in person, and choose a slot that suits you.
TEXT,
    ],
];

$pdo = db();
$targetDir = APP_ROOT . '/uploads/blog';
if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Cannot create {$targetDir}\n");
    exit(1);
}

$added = 0;
$pdo->beginTransaction();
try {
    $category = $pdo->prepare('INSERT IGNORE INTO blog_categories (name, slug) VALUES (?, ?)');
    foreach ($categories as $slug => $name) $category->execute([$name, $slug]);
    $categoryIds = $pdo->query('SELECT slug, id FROM blog_categories')->fetchAll(PDO::FETCH_KEY_PAIR);

    // Earlier entries get later timestamps, so the first post above is the featured (newest) one.
    $insert = $pdo->prepare("INSERT IGNORE INTO blog_posts (category_id, title, slug, excerpt, body, image, status, published_at) VALUES (?, ?, ?, ?, ?, ?, 'published', NOW() - INTERVAL ? MINUTE)");
    foreach ($posts as $index => $post) {
        $file = basename($post['image']);
        $source = APP_ROOT . '/assets/images/' . $post['image'];
        if (!is_file("{$targetDir}/{$file}") && !copy($source, "{$targetDir}/{$file}")) {
            throw new RuntimeException("Could not copy {$post['image']} to uploads/blog.");
        }
        $insert->execute([$categoryIds[$post['category']] ?? null, $post['title'], $post['slug'], $post['excerpt'], $post['body'], $file, $index]);
        $added += $insert->rowCount();
    }
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, 'Seeding failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo "Published {$added} journal posts.\n";
