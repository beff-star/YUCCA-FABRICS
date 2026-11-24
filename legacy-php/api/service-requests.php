<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
verify_csrf();
$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$date = trim((string) ($_POST['preferred_date'] ?? '')) ?: null;
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $location === '' || mb_strlen($description) < 10 || mb_strlen($description) > 5000) json_response(['error' => 'Please complete the required details and describe your project in at least 10 characters.'], 422);
if ($serviceId) {
    $check = db()->prepare('SELECT id FROM services WHERE id=? AND is_active=1');
    $check->execute([$serviceId]);
    if (!$check->fetch()) json_response(['error' => 'Select an available service.'], 422);
}
if ($date !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d'))) json_response(['error' => 'Choose a future preferred date.'], 422);
try {
    $image = save_uploaded_image('reference_image', 'service-requests');
} catch (RuntimeException $exception) {
    json_response(['error' => $exception->getMessage()], 422);
}
$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO service_requests (user_id,service_id,name,email,phone,location,preferred_date,description) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([current_user()['id'] ?? null,$serviceId,$name,$email,$phone,$location,$date,$description]);
    $requestId = (int) $pdo->lastInsertId();
    if ($image) $pdo->prepare('INSERT INTO service_request_images (request_id,image) VALUES (?,?)')->execute([$requestId,$image]);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    if ($image) @unlink(APP_ROOT . '/uploads/service-requests/' . basename($image));
    throw $exception;
}
send_business_email(setting('business_email', 'hello@yuccafabrics.co.ke'), 'New service request from ' . $name, "A new service request was submitted.\n\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\nLocation: {$location}\n\n{$description}");
json_response(['message' => 'Your project request is in. Our team will be in touch with next steps.']);
