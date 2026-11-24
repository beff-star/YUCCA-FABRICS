<?php
require_once __DIR__.'/../includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
$body=file_get_contents('php://input');
try{$payload=json_decode($body,true,512,JSON_THROW_ON_ERROR);}catch(JsonException $exception){http_response_code(400);exit;}
$callbackData=$payload['Body']['stkCallback']??null;
if(!is_array($callbackData)||empty($callbackData['CheckoutRequestID'])||!isset($callbackData['ResultCode'])){http_response_code(400);exit;}
$pdo=db();$stmt=$pdo->prepare("SELECT p.id,p.order_id,p.provider_payload,o.total FROM payments p JOIN orders o ON o.id=p.order_id WHERE p.method='mpesa' AND JSON_UNQUOTE(JSON_EXTRACT(p.provider_payload,'$.checkout_request_id'))=? LIMIT 1");
$stmt->execute([(string)$callbackData['CheckoutRequestID']]);$payment=$stmt->fetch();
if(!$payment){error_log('M-Pesa callback received for an unknown checkout request.');http_response_code(200);echo '{"ResultCode":0,"ResultDesc":"Accepted"}';exit;}
$metadata=[];
foreach(($callbackData['CallbackMetadata']['Item']??[]) as $item){if(isset($item['Name']))$metadata[(string)$item['Name']]=$item['Value']??null;}
$record=['checkout_request_id'=>(string)$callbackData['CheckoutRequestID'],'merchant_request_id'=>(string)($callbackData['MerchantRequestID']??''),'callback_result_code'=>(int)$callbackData['ResultCode'],'callback_description'=>substr((string)($callbackData['ResultDesc']??''),0,255),'receipt_reference'=>isset($metadata['MpesaReceiptNumber'])?(string)$metadata['MpesaReceiptNumber']:null,'callback_received_at'=>date(DATE_ATOM)];
$pdo->prepare('UPDATE payments SET provider_payload=? WHERE id=?')->execute([json_encode($record,JSON_THROW_ON_ERROR),$payment['id']]);
if((int)$callbackData['ResultCode']===0){
    error_log('M-Pesa reports a successful callback for order payment ID '.$payment['id'].'; payment remains pending until verified in the Daraja portal.');
}else{
    $pdo->prepare("UPDATE payments SET status='failed' WHERE id=? AND status='pending'")->execute([$payment['id']]);
    $pdo->prepare("UPDATE orders SET payment_status='failed' WHERE id=? AND payment_status='pending'")->execute([$payment['order_id']]);
}
http_response_code(200);header('Content-Type: application/json');echo '{"ResultCode":0,"ResultDesc":"Accepted"}';
