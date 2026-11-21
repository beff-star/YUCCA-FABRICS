<?php
require_once __DIR__ . '/../includes/functions.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND status = ? LIMIT 1');
        $stmt->execute([$email, 'active']);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')->execute([$user['id'], $hash]);
            $resetLink = url('auth/reset-password.php?token=' . rawurlencode($token));
            $subject = 'Reset your Yucca Fabrics password';
            $body = "Use this one-time link within one hour to reset your password:\n\n{$resetLink}";
            $from = setting('business_email', 'hello@yuccafabrics.co.ke');
            if (!@mail($email, $subject, $body, 'From: ' . $from)) {
                error_log('Password reset email could not be sent for user ID ' . $user['id']);
            }
        }
    }
    $message = 'If an active account matches that email, password reset instructions will be sent.';
}
$pageTitle = 'Reset password | Yucca Fabrics';
$authEyebrow = 'Account help';
$authTitle = 'Forgot your password?';
$authIntro = 'Enter your email and, if it belongs to an active account, we’ll send a one-time reset link that works for one hour.';
require __DIR__ . '/../includes/auth-start.php';
?>
<?php if ($message): ?><div class="mb-5"><?= form_alert($message, 'success') ?></div><?php endif; ?>
<form method="post" class="grid gap-5">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label class="text-sm font-medium">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email"></label>
    <button class="rounded-full bg-forest px-6 py-3.5 font-semibold text-white transition hover:bg-olive">Send reset link</button>
</form>
<p class="mt-8 rounded-2xl bg-cream p-4 text-center text-sm">Remembered it? <a class="font-semibold text-forest underline" href="<?= e(url('auth/login.php')) ?>">Back to sign in</a></p>
<?php require __DIR__ . '/../includes/auth-end.php'; ?>
