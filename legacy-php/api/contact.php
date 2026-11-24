<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
verify_csrf();
if (!empty($_POST['website'])) json_response(['message' => 'Thank you. We’ll be in touch soon.']);
$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || mb_strlen($message) < 10 || mb_strlen($message) > 5000) json_response(['error' => 'Please complete each field and provide a message of at least 10 characters.'], 422);
db()->prepare('INSERT INTO contacts (name,email,phone,subject,message) VALUES (?,?,?,?,?)')->execute([$name,$email,$phone ?: null,$subject,$message]);
send_business_email(setting('business_email', 'hello@yuccafabrics.co.ke'), 'Website enquiry: ' . $subject, "From: {$name}\nEmail: {$email}\nPhone: {$phone}\n\n{$message}");
json_response(['message' => 'Thank you for reaching out. We’ll be in touch soon.']);
