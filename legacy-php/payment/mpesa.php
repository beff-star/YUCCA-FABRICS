<?php
require_once __DIR__.'/../includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['error'=>'Method not allowed.'],405);
verify_csrf();
$orderId=filter_var($_POST['order_id']??null,FILTER_VALIDATE_INT);
$phone=preg_replace('/[\s()+-]/','',(string)($_POST['phone']??''));
if(str_starts_with($phone,'0'))$phone='254'.substr($phone,1);
if(preg_match('/^[17]\d{8}$/',$phone))$phone='254'.$phone;
if(!$orderId||!preg_match('/^254(?:7|1)\d{8}$/',$phone))json_response(['error'=>'Enter a valid Kenyan mobile number.'],422);
$lastOrder=$_SESSION['last_order']??null;
if(!current_user()&&(!$lastOrder||(int)$lastOrder['id']!==$orderId))json_response(['error'=>'Your order session has expired.'],401);
$orderStmt=db()->prepare("SELECT id,order_number,total FROM orders WHERE id=? AND ".(current_user()?'user_id=?':'user_id IS NULL')." AND payment_method='mpesa' AND status<>'cancelled' AND payment_status<>'paid'");
$orderStmt->execute(current_user()?[$orderId,current_user()['id']]:[$orderId]);$order=$orderStmt->fetch();
if(!$order)json_response(['error'=>'This order cannot be paid with M-Pesa.'],404);
$key=getenv('MPESA_CONSUMER_KEY');$secret=getenv('MPESA_CONSUMER_SECRET');$shortcode=getenv('MPESA_SHORTCODE');$passkey=getenv('MPESA_PASSKEY');$callback=getenv('MPESA_CALLBACK_URL');$environment=getenv('MPESA_ENV')?:'sandbox';
if(!$key||!$secret||!$shortcode||!$passkey||!$callback||!filter_var($callback,FILTER_VALIDATE_URL))json_response(['error'=>'M-Pesa is not configured yet. Please contact us to arrange payment.'],503);
if(!function_exists('curl_init'))json_response(['error'=>'The server does not have the cURL extension required for M-Pesa.'],503);
$host=$environment==='production'?'https://api.safaricom.co.ke':'https://sandbox.safaricom.co.ke';
$request=function(string $url,array $headers,?array $body=null):array{
    $curl=curl_init($url);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>25,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    if($body!==null){curl_setopt($curl,CURLOPT_POST,true);curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));}
    $response=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);$curlError=curl_error($curl);curl_close($curl);
    if($response===false)throw new RuntimeException('M-Pesa connection failed: '.$curlError);
    $decoded=json_decode($response,true);if($status<200||$status>=300||!is_array($decoded))throw new RuntimeException('M-Pesa returned an unexpected response. HTTP '.$status);
    return $decoded;
};
try{
    $credentials=base64_encode($key.':'.$secret);
    $tokenData=$request($host.'/oauth/v1/generate?grant_type=client_credentials',['Authorization: Basic '.$credentials,'Accept: application/json']);
    $accessToken=$tokenData['access_token']??'';
    if(!is_string($accessToken)||$accessToken==='')throw new RuntimeException('M-Pesa did not provide an access token.');
    $timestamp=date('YmdHis');$password=base64_encode($shortcode.$passkey.$timestamp);
    $payload=['BusinessShortCode'=>$shortcode,'Password'=>$password,'Timestamp'=>$timestamp,'TransactionType'=>'CustomerPayBillOnline','Amount'=>(int)ceil((float)$order['total']),'PartyA'=>$phone,'PartyB'=>$shortcode,'PhoneNumber'=>$phone,'CallBackURL'=>$callback,'AccountReference'=>$order['order_number'],'TransactionDesc'=>'Yucca Fabrics order '.$order['order_number']];
    $result=$request($host.'/mpesa/stkpush/v1/processrequest',['Authorization: Bearer '.$accessToken,'Content-Type: application/json','Accept: application/json'],$payload);
    if(empty($result['CheckoutRequestID'])||($result['ResponseCode']??null)!=='0')throw new RuntimeException((string)($result['errorMessage']??$result['ResponseDescription']??'M-Pesa could not start the payment request.'));
    $payment=db()->prepare("UPDATE payments SET provider_payload=? WHERE order_id=? AND method='mpesa'");
    $payment->execute([json_encode(['checkout_request_id'=>$result['CheckoutRequestID'],'merchant_request_id'=>$result['MerchantRequestID']??null,'phone'=>$phone,'requested_at'=>date(DATE_ATOM)],JSON_THROW_ON_ERROR),$orderId]);
    json_response(['message'=>'Payment request sent to your phone. Enter your M-Pesa PIN to complete it.','checkout_request_id'=>$result['CheckoutRequestID']]);
}catch(Throwable $exception){error_log('M-Pesa request failed: '.$exception->getMessage());json_response(['error'=>'We could not start the M-Pesa request. Please try again or choose another payment method.'],502);}
