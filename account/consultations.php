<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$stmt = db()->prepare('SELECT consultation_type,property_type,method,preferred_date,preferred_time,status,admin_notes FROM consultations WHERE user_id=? ORDER BY preferred_date DESC,preferred_time DESC');
$stmt->execute([current_user()['id']]);
$rows = $stmt->fetchAll();
$pageTitle = 'My consultations | Yucca Fabrics';
$accountActive = 'consultations';
$accountTitle = 'My consultations';
$accountIntro = 'Your design conversations, and any notes we’ve shared with you.';
$accountAction = ['Book a consultation', 'consultations.php#book', 'fa-calendar-plus'];
require __DIR__ . '/../includes/account-start.php';
?>
<?php if (!$rows): ?>
    <div class="rounded-3xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-ink/5"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-2xl text-forest"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span><h2 class="mt-5 font-serif text-2xl">No consultations yet</h2><p class="mt-2 text-sm text-gray-600">Talk through colour, fabric and layout with us, online or in person.</p><a class="mt-6 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white" href="<?= e(url('consultations.php')) ?>">See consultation options</a></div>
<?php else: ?>
    <div class="grid gap-4">
        <?php foreach ($rows as $row): $isPast = $row['preferred_date'] < date('Y-m-d'); ?>
            <article class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 sm:flex-row sm:items-start <?= $isPast ? 'opacity-80' : '' ?>">
                <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-cream text-center leading-tight"><span><span class="block text-[11px] font-semibold uppercase tracking-wider text-clay"><?= e(date('M', strtotime($row['preferred_date']))) ?></span><span class="block font-serif text-2xl"><?= e(date('j', strtotime($row['preferred_date']))) ?></span></span></span>
                <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-serif text-xl"><?= e($row['consultation_type']) ?></h2><?= status_badge($row['status']) ?></div><p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600"><span><i class="fa-regular fa-clock mr-1.5" aria-hidden="true"></i><?= e(date('D j M Y, g:i A', strtotime($row['preferred_date'] . ' ' . $row['preferred_time']))) ?></span><span><i class="fa-solid <?= $row['method'] === 'online' ? 'fa-video' : 'fa-house' ?> mr-1.5" aria-hidden="true"></i><?= $row['method'] === 'online' ? 'Online' : 'In person' ?></span><?php if ($row['property_type']): ?><span><i class="fa-solid fa-building mr-1.5" aria-hidden="true"></i><?= e($row['property_type']) ?></span><?php endif; ?></p><?php if ($row['admin_notes']): ?><p class="mt-4 rounded-2xl bg-cream p-4 text-sm leading-6"><strong class="block text-xs uppercase tracking-wider text-sage">Note from Yucca</strong><?= e($row['admin_notes']) ?></p><?php endif; ?></div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/account-end.php'; ?>
