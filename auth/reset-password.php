<?php
require_once __DIR__ . '/../includes/functions.php';
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$done = false;
$invalidLink = false; // only a bad or expired link hides the form; a weak password lets the user retry
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $invalidLink = true;
    $error = 'This reset link is invalid or expired.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Use at least 10 characters with uppercase, lowercase and a number.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() FOR UPDATE');
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();
        if (!$reset) {
            $pdo->rollBack();
            $invalidLink = true;
            $error = 'This reset link is invalid or expired.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$reset['id']]);
            $pdo->commit();
            $done = true;
        }
    }
}
$pageTitle = 'Choose a new password | Yucca Fabrics';
$authEyebrow = 'Account help';
$authTitle = 'Choose a new password';
$authIntro = $done ? '' : 'Pick something you haven’t used before.';
require __DIR__ . '/../includes/auth-start.php';
?>
<?php if ($done): ?>
    <?= form_alert('Your password has been updated. You can now sign in with it.', 'success') ?>
    <a class="mt-6 inline-block rounded-full bg-forest px-6 py-3.5 font-semibold text-white transition hover:bg-olive" href="<?= e(url('auth/login.php')) ?>">Sign in</a>
<?php elseif ($invalidLink): ?>
    <?= form_alert($error) ?>
    <a class="mt-6 inline-block rounded-full bg-forest px-6 py-3.5 font-semibold text-white transition hover:bg-olive" href="<?= e(url('auth/forgot-password.php')) ?>">Request a new link</a>
<?php else: ?>
    <?php if ($error): ?><div class="mb-5"><?= form_alert($error) ?></div><?php endif; ?>
    <form method="post" class="grid gap-5">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <?= password_input_html('password', 'new-password', 'New password', 'new-password', 10, 'At least 10 characters, with an uppercase letter, a lowercase letter and a number.') ?>
        <button class="rounded-full bg-forest px-6 py-3.5 font-semibold text-white transition hover:bg-olive">Update password</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/auth-end.php'; ?>
