<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$errors = [];
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid name and email address.';
    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email=? AND id<>?');
        $check->execute([$email, $user['id']]);
        if ($check->fetch()) {
            $errors[] = 'That email is already in use.';
        } else {
            db()->prepare('UPDATE users SET name=?,email=?,phone=? WHERE id=?')->execute([$name, $email, $phone ?: null, $user['id']]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $user = current_user();
            flash('success', 'Your profile has been updated.');
        }
    }
}
$pageTitle = 'Edit profile | Yucca Fabrics';
$accountActive = 'profile';
$accountTitle = 'Profile details';
$accountIntro = 'Keep your contact details up to date so we can reach you about orders and bookings.';
require __DIR__ . '/../includes/account-start.php';
?>
<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-ink/5 sm:p-8">
        <?php if ($message = flash('success')): ?><p class="mb-5 flex items-center gap-3 rounded-2xl bg-[#DFF0E7] p-4 text-sm text-[#17442F]" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= e($message) ?></p><?php endif; ?>
        <?php foreach ($errors as $error): ?><p class="mb-5 flex items-center gap-3 rounded-2xl bg-[#F6E1DE] p-4 text-sm text-[#7A2E22]" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><?= e($error) ?></p><?php endforeach; ?>
        <form method="post" class="grid gap-4 sm:grid-cols-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="text-sm font-medium sm:col-span-2">Full name<input class="form-control mt-1" name="name" value="<?= e($user['name']) ?>" required autocomplete="name"></label>
            <label class="text-sm font-medium">Email<input class="form-control mt-1" type="email" name="email" value="<?= e($user['email']) ?>" required autocomplete="email"></label>
            <label class="text-sm font-medium">Phone<input class="form-control mt-1" type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" autocomplete="tel"></label>
            <button class="w-fit rounded-full bg-forest px-7 py-3 text-sm font-semibold text-white transition hover:bg-olive sm:col-span-2">Save changes</button>
        </form>
    </div>
    <aside class="h-fit rounded-3xl bg-cream p-6"><i class="fa-solid fa-key text-xl text-sage" aria-hidden="true"></i><h2 class="mt-3 font-serif text-xl">Password</h2><p class="mt-2 text-sm leading-6 text-gray-600">To change your password, we’ll email you a secure one-time link.</p><a class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-forest" href="<?= e(url('auth/forgot-password.php')) ?>">Send a reset link <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a></aside>
</div>
<?php require __DIR__ . '/../includes/account-end.php'; ?>
