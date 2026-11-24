<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
if (is_file(APP_ROOT . '/.env') && is_readable(APP_ROOT . '/.env')) {
    $environment = parse_ini_file(APP_ROOT . '/.env', false, INI_SCANNER_RAW);
    if ($environment === false) {
        throw new RuntimeException('The .env configuration file could not be read.');
    }
    foreach ($environment as $key => $value) {
        if (getenv($key) === false && is_string($value)) {
            putenv($key . '=' . trim($value, "\"'"));
        }
    }
}
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Nairobi');
define('APP_NAME', getenv('APP_NAME') ?: 'Yucca Fabrics');
$configuredBase = getenv('APP_BASE_URL');
if ($configuredBase === false) {
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $applicationRoot = realpath(APP_ROOT);
    $relativeRoot = ($documentRoot && $applicationRoot) ? str_replace('\\', '/', substr($applicationRoot, strlen($documentRoot))) : '';
    $configuredBase = '/' . trim($relativeRoot, '/');
}
define('BASE_URL', rtrim($configuredBase, '/'));
define('APP_SITE_URL', rtrim(getenv('APP_SITE_URL') ?: '', '/'));
define('CURRENCY', getenv('APP_CURRENCY') ?: 'KES');
define('TAX_RATE', (float) (getenv('APP_TAX_RATE') ?: '0.16'));
define('DELIVERY_FEE', (float) (getenv('APP_DELIVERY_FEE') ?: '350'));
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL));

// Never show raw PHP/SQL errors to visitors: log the detail, show a calm message. APP_DEBUG=true shows it locally.
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
if (PHP_SAPI !== 'cli') {
    set_exception_handler(function (Throwable $exception): void {
        error_log('Uncaught ' . get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
        if (!headers_sent()) http_response_code(500);
        $wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') || preg_match('#/(api|payment)/#', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($wantsJson) {
            if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Something went wrong on our side. Please try again in a moment.']);
            return;
        }
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Something went wrong | Yucca Fabrics</title></head>'
            . '<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#F7F4EE;color:#2A2B25;font-family:system-ui,sans-serif;text-align:center;padding:24px">'
            . '<main><p style="letter-spacing:.2em;font-size:12px;text-transform:uppercase;color:#576829">Yucca Fabrics</p><h1 style="font-size:32px;margin:12px 0">Something went wrong on our side</h1>'
            . '<p style="color:#4B4C44">Please try again in a moment. If it keeps happening, call or WhatsApp us and we’ll help.</p>'
            . '<p style="margin-top:28px"><a href="' . htmlspecialchars(BASE_URL . '/index.php', ENT_QUOTES) . '" style="background:#3F5320;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none">Back to the homepage</a></p>'
            . (APP_DEBUG ? '<pre style="text-align:left;white-space:pre-wrap;margin-top:32px">' . htmlspecialchars((string) $exception, ENT_QUOTES) . '</pre>' : '')
            . '</main></body></html>';
    });
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - (int) $_SESSION['created_at'] > 7200) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['created_at'] = time();
}
