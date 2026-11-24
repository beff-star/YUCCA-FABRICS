<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
verify_csrf();
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Enter a valid email address.'], 422);
db()->prepare('INSERT INTO newsletter (email) VALUES (?) ON DUPLICATE KEY UPDATE is_active=1')->execute([$email]);
json_response(['message' => 'Thank you for joining our notes from home.']);
