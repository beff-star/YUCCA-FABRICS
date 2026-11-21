<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    return APP_SITE_URL !== '' ? APP_SITE_URL . url($path) : url($path);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your session expired. Refresh the page and try again.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function current_user(): ?array
{
    if (array_key_exists('current_user_cache_loaded', $GLOBALS)) {
        return $GLOBALS['current_user_cache'];
    }
    $GLOBALS['current_user_cache_loaded'] = true;
    $stored = $_SESSION['user'] ?? null;
    if (!$stored || empty($stored['id'])) {
        $GLOBALS['current_user_cache'] = null;
        return null;
    }
    $stmt = db()->prepare("SELECT id,name,email,phone,role FROM users WHERE id=? AND status='active' LIMIT 1");
    $stmt->execute([(int) $stored['id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        $GLOBALS['current_user_cache'] = null;
        return null;
    }
    $_SESSION['user'] = $user;
    $GLOBALS['current_user_cache'] = $user;
    return $user;
}

function set_current_user(array $user): void
{
    $_SESSION['user'] = $user;
    $GLOBALS['current_user_cache_loaded'] = true;
    $GLOBALS['current_user_cache'] = $user;
}

function require_login(): void
{
    if (!current_user()) {
        flash('error', 'Please sign in to continue.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    // Signed-out visitors are sent to sign in; signed-in customers get a branded "no access" page.
    if (!current_user()) {
        flash('error', 'Please sign in with an administrator account to continue.');
        redirect('auth/login.php');
    }
    if ((current_user()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        $pageTitle = 'No access | Yucca Fabrics';
        require __DIR__ . '/header.php';
        echo '<section class="mx-auto max-w-2xl px-5 py-24 text-center"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-cream text-2xl text-forest"><i class="fa-solid fa-lock" aria-hidden="true"></i></span><h1 class="mt-6 font-serif text-4xl">This area is for the Yucca team</h1><p class="mt-3 text-sm text-gray-600">Your account doesn’t have access to the administration pages.</p><a class="mt-8 inline-block rounded-full bg-forest px-6 py-3 text-sm font-semibold text-white" href="' . e(url('account/dashboard.php')) . '">Go to my account</a></section>';
        require __DIR__ . '/footer.php';
        exit;
    }
}

function money(float|int|string $amount): string
{
    return CURRENCY . ' ' . number_format((float) $amount, 2);
}

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, name, slug, price, sale_price, stock, unit, image FROM products WHERE is_active = 1 AND id IN ({$placeholders})");
    $stmt->execute($ids);
    $items = [];
    foreach ($stmt->fetchAll() as $product) {
        $quantity = max(1, min((int) $cart[$product['id']], (int) $product['stock']));
        $price = $product['sale_price'] !== null ? (float) $product['sale_price'] : (float) $product['price'];
        $items[] = $product + ['quantity' => $quantity, 'unit_price' => $price, 'line_total' => $price * $quantity];
    }
    return $items;
}

function cart_count(): int
{
    return array_sum(array_map('intval', $_SESSION['cart'] ?? []));
}

function coupon_for_checkout(string $code, float $subtotal, ?int $userId = null, bool $lock = false): ?array
{
    $sql = 'SELECT * FROM coupons WHERE code=? AND is_active=1 AND (starts_at IS NULL OR starts_at<=NOW()) AND (expires_at IS NULL OR expires_at>=NOW()) LIMIT 1';
    if ($lock) $sql .= ' FOR UPDATE';
    $stmt = db()->prepare($sql);
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();
    if (!$coupon || $subtotal < (float) $coupon['minimum_order']) return null;
    if ($coupon['usage_limit'] !== null) {
        $usage = db()->prepare('SELECT COUNT(*) FROM coupon_usage WHERE coupon_id=?');
        $usage->execute([$coupon['id']]);
        if ((int) $usage->fetchColumn() >= (int) $coupon['usage_limit']) return null;
    }
    if ($userId !== null) {
        $usage = db()->prepare('SELECT COUNT(*) FROM coupon_usage WHERE coupon_id=? AND user_id=?');
        $usage->execute([$coupon['id'], $userId]);
        if ((int) $usage->fetchColumn() > 0) return null;
    }
    $discount = $coupon['discount_type'] === 'percent'
        ? $subtotal * (float) $coupon['discount_value'] / 100
        : (float) $coupon['discount_value'];
    $coupon['discount_amount'] = round(min($subtotal, $discount), 2);
    return $coupon;
}

function persist_cart(): void
{
    $user = current_user();
    if (!$user) {
        return;
    }
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO cart (user_id) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $stmt->execute([(int) $user['id']]);
    $cartId = (int) $pdo->lastInsertId();
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
    if (!empty($_SESSION['cart'])) {
        $insert = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) SELECT ?, id, ? FROM products WHERE id = ? AND is_active = 1');
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $insert->execute([$cartId, max(1, (int) $quantity), (int) $productId]);
        }
    }
}

function restore_user_cart(): void
{
    $user = current_user();
    if (!$user) {
        return;
    }
    $stmt = db()->prepare('SELECT ci.product_id, ci.quantity FROM cart_items ci JOIN cart c ON c.id = ci.cart_id WHERE c.user_id = ?');
    $stmt->execute([(int) $user['id']]);
    $stored = [];
    foreach ($stmt->fetchAll() as $row) {
        $stored[(int) $row['product_id']] = (int) $row['quantity'];
    }
    $_SESSION['cart'] = array_replace($stored, $_SESSION['cart'] ?? []);
    persist_cart();
}

// Neutral swatch photo, so a product without its own image never looks like another product.
const PRODUCT_PLACEHOLDER_IMAGE = 'assets/images/fabrics/piki-17-fabric-samples.jpeg';

/** Price markup for cards and the product page: sale price with the original struck through, plus the unit. */
function product_price_html(array $product): string
{
    $unit = '<span class="text-xs font-normal text-gray-500"> / ' . e($product['unit'] ?? 'metre') . '</span>';
    $price = (float) $product['price'];
    $sale = $product['sale_price'] !== null ? (float) $product['sale_price'] : null;
    if ($sale !== null && $sale < $price) {
        return '<span class="text-clay">' . e(money($sale)) . '</span> <s class="text-xs font-normal text-gray-500">' . e(money($price)) . '</s>' . $unit;
    }
    return e(money($price)) . $unit;
}

function product_image(?string $image): string
{
    return $image ? url('uploads/products/' . rawurlencode(basename($image))) : url(PRODUCT_PLACEHOLDER_IMAGE);
}

function fabric_image(string $file): string
{
    return url('assets/images/fabrics/' . rawurlencode(basename($file)));
}

function service_image(string $file): string
{
    return url('assets/images/services/' . rawurlencode(basename($file)));
}

/** Look, icon and longer copy for a service slug (see includes/service-details.php), merged over the defaults. */
function service_details(string $slug): array
{
    static $details;
    $details ??= require __DIR__ . '/service-details.php';
    return ($details[$slug] ?? []) + $details['default'];
}

// Service categories booked through the consultation form rather than the project request form.
const CONSULTATION_CATEGORY_SLUGS = ['fabric-sourcing', 'interior-styling'];
const CONSULTATION_TYPES = ['Residential interior design', 'Commercial design', 'Living room styling', 'Bedroom styling', 'Office design', 'Fabric and colour selection'];

function is_consultation_service(array $service): bool
{
    return in_array($service['category_slug'] ?? '', CONSULTATION_CATEGORY_SLUGS, true);
}

/** Services with their category name and slug, for the services and consultations pages. */
function active_services(bool $consultationsOnly = false): array
{
    $services = db()->query('SELECT s.*, c.name AS category_name, c.slug AS category_slug FROM services s LEFT JOIN service_categories c ON c.id=s.category_id WHERE s.is_active=1 ORDER BY s.id')->fetchAll();
    return $consultationsOnly ? array_values(array_filter($services, 'is_consultation_service')) : $services;
}

/** Whole-number price for chips and headlines, e.g. "KES 2,500". */
function money_whole(float|int|string $amount): string
{
    return CURRENCY . ' ' . number_format((float) $amount);
}

/** Decorative yucca-leaf sprig echoing the logo; purely ornamental, so hidden from assistive tech. */
function yucca_sprig_svg(string $class = ''): string
{
    $leaves = ['M100 195Q70 120 25 60Q85 115 100 195Z', 'M100 195Q80 110 55 25Q95 105 100 195Z', 'M100 195Q88 100 92 8Q108 100 100 195Z', 'M100 195Q120 110 148 28Q106 105 100 195Z', 'M100 195Q130 120 178 62Q118 118 100 195Z', 'M100 195Q60 160 8 140Q70 150 100 195Z', 'M100 195Q140 158 194 140Q130 150 100 195Z'];
    $paths = implode('', array_map(fn(string $d): string => '<path d="' . $d . '"/>', $leaves));
    return '<svg class="' . e($class) . '" viewBox="0 0 200 200" fill="currentColor" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

function journal_image(?string $image): string
{
    return $image ? url('uploads/blog/' . rawurlencode(basename($image))) : fabric_image('soho-inverted-leather.jpeg');
}

function read_minutes(string $body): int
{
    return max(1, (int) ceil(str_word_count(strip_tags($body)) / 200));
}

/**
 * Journal article text to HTML. Everything is escaped first, then a tiny, safe subset of Markdown is applied:
 * blank lines separate paragraphs, "## " starts a heading and "- " starts a bullet. Plain text still works.
 */
function journal_body_html(string $body): string
{
    $html = '';
    $paragraph = [];
    $list = [];
    $flush = function () use (&$html, &$paragraph, &$list): void {
        if ($paragraph) $html .= '<p>' . implode('<br>', array_map('e', $paragraph)) . '</p>';
        if ($list) $html .= '<ul>' . implode('', array_map(fn(string $item): string => '<li>' . e($item) . '</li>', $list)) . '</ul>';
        $paragraph = $list = [];
    };
    // Line by line, so a heading, its paragraph and a list can follow each other without blank lines.
    foreach (explode("\n", str_replace("\r\n", "\n", trim($body))) as $line) {
        $line = trim($line);
        if ($line === '') {
            $flush();
        } elseif (str_starts_with($line, '## ')) {
            $flush();
            $html .= '<h2>' . e(substr($line, 3)) . '</h2>';
        } elseif (str_starts_with($line, '- ')) {
            if ($paragraph) $flush();
            $list[] = substr($line, 2);
        } else {
            if ($list) $flush();
            $paragraph[] = $line;
        }
    }
    $flush();
    return $html;
}

/** Latest published journal posts with their category, optionally excluding one post. */
function latest_journal_posts(int $limit, int $excludeId = 0): array
{
    $stmt = db()->prepare("SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id=bp.category_id WHERE bp.status='published' AND bp.published_at<=NOW() AND bp.id<>? ORDER BY bp.published_at DESC LIMIT " . max(1, $limit));
    $stmt->execute([$excludeId]);
    return $stmt->fetchAll();
}

function payment_method_label(string $method): string
{
    return ['cod' => 'Cash on delivery', 'mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank transfer'][$method] ?? ucfirst(str_replace('_', ' ', $method));
}

/** Coloured pill for order, payment, consultation and request statuses (dark text on a light tint for AA). */
function status_badge(string $status): string
{
    $tones = [
        'bg-[#FBF0D9] text-[#6B4A07]' => ['pending', 'new', 'unpaid', 'sent'],
        'bg-[#DDEFF1] text-[#0F4A52]' => ['confirmed', 'processing', 'shipped', 'quoted', 'in_progress', 'approved', 'accepted'],
        'bg-[#DFF0E7] text-[#17442F]' => ['delivered', 'paid', 'completed', 'replied'],
        'bg-[#F6E1DE] text-[#7A2E22]' => ['cancelled', 'failed', 'declined', 'rejected', 'expired'],
    ];
    $class = 'bg-gray-100 text-gray-700';
    foreach ($tones as $tone => $statuses) if (in_array($status, $statuses, true)) $class = $tone;
    return '<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ' . $class . '"><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>' . e(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

/** Success, info or error message box used above forms and for flash messages. */
function form_alert(string $message, string $type = 'error'): string
{
    [$class, $icon, $role] = match ($type) {
        'success' => ['bg-[#DFF0E7] text-[#17442F]', 'fa-circle-check', 'status'],
        'info' => ['bg-[#DDEFF1] text-[#0F4A52]', 'fa-circle-info', 'status'],
        default => ['bg-[#F6E1DE] text-[#7A2E22]', 'fa-circle-exclamation', 'alert'],
    };
    return '<p class="flex items-start gap-3 rounded-2xl p-4 text-sm ' . $class . '" role="' . $role . '"><i class="fa-solid ' . $icon . ' mt-0.5" aria-hidden="true"></i><span>' . e($message) . '</span></p>';
}

/** Password input with a show/hide button (wired up in main.js via data-toggle-password). */
function password_input_html(string $name, string $id, string $label, string $autocomplete, int $minLength = 0, string $hint = ''): string
{
    $hintId = $hint !== '' ? $id . '-hint' : '';
    return '<div><label class="text-sm font-medium" for="' . e($id) . '">' . e($label) . '</label><div class="relative mt-1">'
        . '<input class="form-control pr-12" type="password" id="' . e($id) . '" name="' . e($name) . '" required autocomplete="' . e($autocomplete) . '"' . ($minLength ? ' minlength="' . $minLength . '"' : '') . ($hintId ? ' aria-describedby="' . e($hintId) . '"' : '') . '>'
        . '<button type="button" class="absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-lg text-gray-500 transition hover:text-forest" data-toggle-password aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button></div>'
        . ($hint !== '' ? '<p class="mt-1.5 text-xs text-gray-500" id="' . e($hintId) . '">' . e($hint) . '</p>' : '') . '</div>';
}

/** Bag → details & payment → confirmation progress bar for the purchase journey (1-based step). */
function checkout_steps(int $current): string
{
    $html = '<ol class="mt-6 flex items-center gap-2 text-xs font-semibold sm:gap-3 sm:text-sm" aria-label="Checkout progress">';
    foreach (['Bag', 'Details & payment', 'Confirmation'] as $index => $label) {
        $step = $index + 1;
        $state = $step < $current ? 'done' : ($step === $current ? 'current' : 'next');
        if ($index > 0) $html .= '<li class="h-px w-6 bg-ink/20 sm:w-10" aria-hidden="true"></li>';
        $badge = $state === 'done' ? '<i class="fa-solid fa-check text-[10px]" aria-hidden="true"></i>' : (string) $step;
        $tone = $state === 'next' ? 'bg-white text-gray-500 ring-1 ring-ink/15' : 'bg-forest text-white';
        $html .= '<li class="flex items-center gap-2' . ($state === 'next' ? ' text-gray-500' : '') . '"' . ($state === 'current' ? ' aria-current="step"' : '') . '><span class="grid h-7 w-7 place-items-center rounded-full ' . $tone . '">' . $badge . '</span><span class="' . ($state === 'current' ? '' : 'hidden sm:inline') . '">' . e($label) . '</span></li>';
    }
    return $html . '</ol>';
}

/** Real counts from the catalogue, plus milestones only when an admin has entered verified figures in settings. */
function site_numbers(): array
{
    $count = fn(string $sql): int => (int) db()->query($sql)->fetchColumn();
    return array_filter([
        'Fabrics in the collection' => $count('SELECT COUNT(*) FROM products WHERE is_active=1') ?: '',
        'Fabric types, from bouclé to velvet' => $count("SELECT COUNT(DISTINCT material) FROM products WHERE is_active=1 AND material IS NOT NULL AND material<>''") ?: '',
        'Upholstery and design services' => $count('SELECT COUNT(*) FROM services WHERE is_active=1') ?: '',
        'Years of experience' => setting('years_experience'),
        'Completed projects' => setting('completed_projects'),
        'Happy customers' => setting('verified_customers'),
    ], fn($value) => $value !== '');
}

function fabric_collection(): array
{
    static $collection;
    return $collection ??= require __DIR__ . '/fabric-collection.php';
}

function save_uploaded_image(string $field, string $folder): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image upload failed or exceeds the 5 MB limit.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Upload a valid JPG, PNG or WebP image.');
    }
    $directory = APP_ROOT . '/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Image storage is unavailable.');
    }
    $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('The image could not be saved.');
    }
    return $filename;
}

function setting(string $key, string $fallback = ''): string
{
    static $settings;
    if ($settings === null) {
        $settings = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM website_settings')->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $exception) {
            error_log('Unable to load website settings: ' . $exception->getMessage());
        }
    }
    return (string) ($settings[$key] ?? $fallback);
}

function whatsapp_url(): string
{
    if (setting('whatsapp_url') !== '') return setting('whatsapp_url');
    // wa.me needs the international format without "+", so a local 07… number becomes 2547….
    $number = preg_replace('/\D/', '', setting('business_phone', '+254 700 000 000'));
    if (str_starts_with($number, '0')) $number = '254' . substr($number, 1);
    return 'https://wa.me/' . $number;
}

/**
 * Footer social icons. Until real pages are entered in Admin → Website settings, each link falls back to the
 * platform's home page as a placeholder (`?:` so a saved-but-blank setting also falls back).
 */
function social_links(): array
{
    return [
        ['label' => 'Instagram', 'icon' => 'fa-instagram', 'url' => setting('instagram_url') ?: 'https://www.instagram.com/'],
        ['label' => 'Facebook', 'icon' => 'fa-facebook-f', 'url' => setting('facebook_url') ?: 'https://www.facebook.com/'],
        ['label' => 'TikTok', 'icon' => 'fa-tiktok', 'url' => setting('tiktok_url') ?: 'https://www.tiktok.com/'],
        ['label' => 'WhatsApp', 'icon' => 'fa-whatsapp', 'url' => whatsapp_url()],
    ];
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function send_business_email(string $recipient, string $subject, string $message): bool
{
    $from = setting('business_email', 'hello@yuccafabrics.co.ke');
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log('Email was not sent because a recipient or sender address is invalid.');
        return false;
    }
    $headers = "From: Yucca Fabrics <{$from}>\r\nContent-Type: text/plain; charset=UTF-8";
    if (!@mail($recipient, $subject, $message, $headers)) {
        error_log('Email delivery failed for recipient ' . $recipient);
        return false;
    }
    return true;
}
