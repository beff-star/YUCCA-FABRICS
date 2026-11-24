<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$name = trim((string) ($argv[1] ?? readline('Administrator name: ')));
$email = strtolower(trim((string) ($argv[2] ?? readline('Administrator email: '))));
$username = strtolower(trim((string) ($argv[3] ?? readline('Administrator username: '))));
$password = getenv('YUCCA_ADMIN_PASSWORD') ?: (string) readline('Administrator password (input is visible): ');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9_]{3,60}$/', $username) || strlen($password) < 12) {
    fwrite(STDERR, "Provide a name, valid email, username (3-60 letters/numbers/underscores), and password of at least 12 characters.\n");
    exit(1);
}

$stmt = db()->prepare("INSERT INTO users (name, username, email, password_hash, role) VALUES (:name, :username, :email, :password, 'admin')");
try {
    $stmt->execute([
        'name' => $name,
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Administrator account created for {$email}.\n");
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "That email or username is already in use.\n");
        exit(1);
    }
    throw $exception;
}
