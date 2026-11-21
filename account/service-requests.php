<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$stmt = db()->prepare('SELECT sr.*,s.name AS service_name,s.slug AS service_slug FROM service_requests sr LEFT JOIN services s ON s.id=sr.service_id WHERE sr.user_id=? ORDER BY sr.created_at DESC');
$stmt->execute([current_user()['id']]);
$rows = $stmt->fetchAll();
$pageTitle = 'My service requests | Yucca Fabrics';
$accountActive = 'requests';
$accountTitle = 'Service requests';
$accountIntro = 'Upholstery projects you’ve asked us about, and our replies.';
$accountAction = ['Start a project', 'services.php#request-form', 'fa-plus'];
require __DIR__ . '/../includes/account-start.php';
?>
<?php if (!$rows): ?>
    <div class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-ink/5"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-2xl text-forest"><i class="fa-solid fa-couch" aria-hidden="true"></i></span><h2 class="mt-5 font-serif text-2xl">No service requests yet</h2><p class="mt-2 text-sm text-gray-600">Send us a photo of a sofa or chair that needs some love, and we’ll advise.</p><a class="mt-6 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white" href="<?= e(url('services.php')) ?>">Explore our services</a></div>
<?php else: ?>
    <div class="grid gap-4">
        <?php foreach ($rows as $row): $details = service_details($row['service_slug'] ?? ''); ?>
            <article class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 sm:flex-row sm:items-start">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-xl <?= $details['theme']['text'] === 'light' ? 'text-white' : 'text-ink' ?>" style="background-color:<?= e($details['theme']['bg']) ?>"><i class="fa-solid <?= e($details['icon']) ?>" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-serif text-xl"><?= e($row['service_name'] ?? 'Project enquiry') ?></h2><?= status_badge($row['status']) ?></div><p class="mt-1 text-xs text-gray-500">Sent <?= e(date('j M Y', strtotime($row['created_at']))) ?><?= $row['location'] ? ' · ' . e($row['location']) : '' ?></p><p class="mt-3 text-sm leading-6 text-gray-600"><?= e($row['description']) ?></p><?php if ($row['admin_response']): ?><p class="mt-4 rounded-2xl bg-cream p-4 text-sm leading-6"><strong class="block text-xs uppercase tracking-wider text-sage">Reply from Yucca</strong><?= e($row['admin_response']) ?></p><?php endif; ?></div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/account-end.php'; ?>
