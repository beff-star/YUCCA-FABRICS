<?php
require_once __DIR__.'/../includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['error'=>'Method not allowed.'],405);
verify_csrf();$orderId=filter_var($_POST['order_id']??null,FILTER_VALIDATE_INT);$reference=strtoupper(trim((string)($_POST['reference']??'')));
if(!$orderId||!preg_match('/^[A-Z0-9-]{4,80}$/',$reference))json_response(['error'=>'Enter a valid bank payment reference.'],422);
$lastOrder=$_SESSION['last_order']??null;if(!current_user()&&(!$lastOrder||(int)$lastOrder['id']!==$orderId))json_response(['error'=>'Your order session has expired.'],401);
$stmt=db()->prepare("SELECT id FROM orders WHERE id=? AND ".(current_user()?'user_id=?':'user_id IS NULL')." AND payment_method='bank_transfer' AND payment_status<>'paid'");
$stmt->execute(current_user()?[$orderId,current_user()['id']]:[$orderId]);if(!$stmt->fetch())json_response(['error'=>'This order cannot accept a bank transfer reference.'],404);
try{db()->prepare("UPDATE payments SET reference=?,status='pending' WHERE order_id=? AND method='bank_transfer'")->execute([$reference,$orderId]);}catch(PDOException $exception){if($exception->getCode()==='23000')json_response(['error'=>'That payment reference has already been submitted.'],409);throw $exception;}
json_response(['message'=>'Reference saved. We’ll confirm the transfer after checking the bank.']);
