<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
verify_csrf();
$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$type = trim((string) ($_POST['consultation_type'] ?? ''));
$property = trim((string) ($_POST['property_type'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$date = trim((string) ($_POST['preferred_date'] ?? ''));
$time = trim((string) ($_POST['preferred_time'] ?? ''));
$budget = trim((string) ($_POST['budget'] ?? ''));
$method = (string) ($_POST['method'] ?? '');
$description = trim((string) ($_POST['description'] ?? ''));
$timePattern = '/^(?:(?:09|1[0-6]):(?:00|30)|17:00)$/';
if (preg_match($timePattern, $time)) $time .= ':00';
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $type === '' || $property === '' || $location === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d') || !preg_match('/^(?:(?:09|1[0-6]):(?:00|30):00|17:00:00)$/', $time) || ($date === date('Y-m-d') && $time <= date('H:i:s')) || !in_array($method, ['online','in_person'], true) || mb_strlen($description) < 10 || mb_strlen($description) > 5000) {
    json_response(['error' => 'Please complete the form and choose an available future date and time.'], 422);
}
try {
    $image = save_uploaded_image('reference_image', 'consultations');
} catch (RuntimeException $exception) {
    json_response(['error' => $exception->getMessage()], 422);
}
$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO consultation_slots (available_date,available_time) VALUES (?,?) ON DUPLICATE KEY UPDATE available_date=VALUES(available_date)')->execute([$date,$time]);
    $slot = $pdo->prepare('SELECT consultation_id FROM consultation_slots WHERE available_date=? AND available_time=? FOR UPDATE');
    $slot->execute([$date,$time]);
    if ($slot->fetchColumn() !== null) {
        $pdo->rollBack();
        if ($image) @unlink(APP_ROOT . '/uploads/consultations/' . basename($image));
        json_response(['error' => 'That time was just booked. Please choose another slot.'], 409);
    }
    $stmt = $pdo->prepare('INSERT INTO consultations (user_id,name,email,phone,consultation_type,property_type,location,preferred_date,preferred_time,budget,method,description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([current_user()['id'] ?? null,$name,$email,$phone,$type,$property,$location,$date,$time,$budget ?: null,$method,$description]);
    $consultationId = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE consultation_slots SET consultation_id=? WHERE available_date=? AND available_time=?')->execute([$consultationId,$date,$time]);
    if ($image) $pdo->prepare('INSERT INTO consultation_images (consultation_id,image) VALUES (?,?)')->execute([$consultationId,$image]);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    if ($image) @unlink(APP_ROOT . '/uploads/consultations/' . basename($image));
    throw $exception;
}
send_business_email($email, 'Yucca Fabrics consultation request received', "Hello {$name},\n\nWe have received your consultation request for {$date} at {$time}. This is a request, not a confirmed appointment; our team will contact you shortly.\n\nYucca Fabrics");
send_business_email(setting('business_email', 'hello@yuccafabrics.co.ke'), 'New interior design consultation request', "A new consultation request was submitted by {$name} ({$email}) for {$date} at {$time}.\n\n{$description}");
json_response(['message' => 'Your consultation request has been received. We’ll confirm your appointment by email or phone.']);
