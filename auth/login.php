<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user()) redirect((current_user()['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'account/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $now = time();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $attempts = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE email=? AND ip_address=? AND attempted_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
    $attempts->execute([$email, $ip]);
    if ((int) $attempts->fetchColumn() >= 8) {
        $error = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
    } else {
        $stmt = db()->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && $user['status'] === 'active' && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            session_regenerate_id(true);
            unset($user['password_hash'], $user['status']);
            set_current_user($user);
            db()->prepare('DELETE FROM login_attempts WHERE email=? AND ip_address=?')->execute([$email, $ip]);
            restore_user_cart();
            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'account/dashboard.php');
        }
        db()->prepare('INSERT INTO login_attempts (email,ip_address) VALUES (?,?)')->execute([$email, $ip]);
        $error = 'The email or password is incorrect, or the account is unavailable.';
    }
}
$pageTitle = 'Sign in | Yucca Fabrics';
$authEyebrow = 'Your Yucca account';
$authTitle = 'Welcome back';
$authIntro = 'Sign in to follow your orders, bookings and saved fabrics.';
require __DIR__ . '/../includes/auth-start.php';
?>
<?php if ($error): ?><div class="mb-5"><?= form_alert($error) ?></div><?php endif; ?>
<form method="post" class="grid gap-5">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label class="text-sm font-medium">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
    <?= password_input_html('password', 'login-password', 'Password', 'current-password') ?>
    <div class="-mt-2 text-right"><a class="text-sm font-medium text-forest underline-offset-4 hover:underline" href="<?= e(url('auth/forgot-password.php')) ?>">Forgot your password?</a></div>
    <button class="rounded-full bg-forest px-6 py-3.5 font-semibold text-white transition hover:bg-olive">Sign in</button>
</form>
<p class="mt-8 rounded-2xl bg-cream p-4 text-center text-sm">New to Yucca? <a class="font-semibold text-forest underline" href="<?= e(url('auth/register.php')) ?>">Create an account</a></p>
<?php require __DIR__ . '/../includes/auth-end.php'; ?>
