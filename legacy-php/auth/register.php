<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user()) {
    redirect('account/dashboard.php');
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Enter your full name.';
    if (!preg_match('/^[a-zA-Z0-9_]{3,60}$/', $username)) $errors[] = 'Username must be 3–60 letters, numbers or underscores.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) $errors[] = 'Password must be at least 10 characters and include uppercase, lowercase and a number.';
    if (!isset($_POST['terms'])) $errors[] = 'Please accept the terms and privacy policy.';
    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $errors[] = 'That email or username is already registered.';
        } else {
            $stmt = db()->prepare("INSERT INTO users (name, username, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $username, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
            set_current_user(['id' => (int) db()->lastInsertId(), 'name' => $name, 'email' => $email, 'phone' => $phone, 'role' => 'customer']);
            session_regenerate_id(true);
            restore_user_cart();
            redirect('account/dashboard.php');
        }
    }
}
$pageTitle = 'Create an account | Yucca Fabrics';
$authEyebrow = 'Welcome to Yucca';
$authTitle = 'Create your account';
$authIntro = 'Keep track of orders, service requests and consultations in one place.';
require __DIR__ . '/../includes/auth-start.php';
?>
<?php foreach ($errors as $error): ?><div class="mb-3"><?= form_alert($error) ?></div><?php endforeach; ?>
<form method="post" class="mt-2 grid gap-5 sm:grid-cols-2">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label class="text-sm font-medium sm:col-span-2">Full name<input class="form-control mt-1" name="name" required maxlength="150" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>"></label>
    <label class="text-sm font-medium">Username<input class="form-control mt-1" name="username" required minlength="3" maxlength="60" pattern="[A-Za-z0-9_]{3,60}" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>" aria-describedby="username-hint"><span class="mt-1.5 block text-xs font-normal text-gray-500" id="username-hint">Letters, numbers and underscores.</span></label>
    <label class="text-sm font-medium">Phone (optional)<input class="form-control mt-1" type="tel" name="phone" autocomplete="tel" value="<?= e($_POST['phone'] ?? '') ?>"></label>
    <label class="text-sm font-medium sm:col-span-2">Email<input class="form-control mt-1" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
    <div class="sm:col-span-2"><?= password_input_html('password', 'register-password', 'Password', 'new-password', 10, 'At least 10 characters, with an uppercase letter, a lowercase letter and a number.') ?></div>
    <label class="flex items-start gap-3 text-sm sm:col-span-2"><input type="checkbox" name="terms" required class="mt-0.5 h-4 w-4 accent-[#3F5320]"<?= isset($_POST['terms']) ? ' checked' : '' ?>><span>I agree to the <a class="font-semibold text-forest underline" href="<?= e(url('terms.php')) ?>">terms</a> and <a class="font-semibold text-forest underline" href="<?= e(url('privacy-policy.php')) ?>">privacy policy</a>.</span></label>
    <button class="rounded-full bg-forest px-6 py-3.5 font-semibold text-white transition hover:bg-olive sm:col-span-2">Create account</button>
</form>
<p class="mt-8 rounded-2xl bg-cream p-4 text-center text-sm">Already have an account? <a class="font-semibold text-forest underline" href="<?= e(url('auth/login.php')) ?>">Sign in</a></p>
<?php require __DIR__ . '/../includes/auth-end.php'; ?>
