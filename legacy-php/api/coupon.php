<?php
require_once __DIR__.'/../includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['error'=>'Method not allowed.'],405);
verify_csrf();
$action=(string)($_POST['action']??'apply');
if($action==='remove'){unset($_SESSION['coupon']);json_response(['message'=>'Coupon removed.']);}
if($action!=='apply')json_response(['error'=>'Invalid coupon action.'],422);
$code=strtoupper(trim((string)($_POST['code']??'')));
if(!preg_match('/^[A-Z0-9_-]{3,40}$/',$code))json_response(['error'=>'Enter a valid coupon code.'],422);
$subtotal=array_sum(array_column(cart_items(),'line_total'));
$coupon=coupon_for_checkout($code,$subtotal,current_user()?(int)current_user()['id']:null);
if(!$coupon)json_response(['error'=>'That coupon is not available for this bag. Check the code, minimum spend and expiry.'],422);
$_SESSION['coupon']=$code;
json_response(['message'=>'Coupon applied. You save '.money($coupon['discount_amount']).'.']);
