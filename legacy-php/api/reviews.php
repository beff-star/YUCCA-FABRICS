<?php
require_once __DIR__.'/../includes/functions.php';
if(!current_user())json_response(['error'=>'Sign in to leave a review.'],401);
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['error'=>'Method not allowed.'],405);
verify_csrf();$productId=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT);$rating=filter_var($_POST['rating']??null,FILTER_VALIDATE_INT);$review=trim((string)($_POST['review']??''));$title=trim((string)($_POST['title']??''));
if(!$productId||!$rating||$rating<1||$rating>5||mb_strlen($review)<10||mb_strlen($review)>2000)json_response(['error'=>'Choose a rating and write a review between 10 and 2,000 characters.'],422);
$purchase=db()->prepare("SELECT o.id FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.user_id=? AND oi.product_id=? AND o.status='delivered' LIMIT 1");$purchase->execute([current_user()['id'],$productId]);$orderId=$purchase->fetchColumn();if(!$orderId)json_response(['error'=>'Reviews are available after a delivered purchase.'],403);
try{db()->prepare('INSERT INTO product_reviews (product_id,user_id,order_id,rating,title,review) VALUES (?,?,?,?,?,?)')->execute([$productId,current_user()['id'],$orderId,$rating,$title?:null,$review]);}catch(PDOException $exception){if($exception->getCode()==='23000')json_response(['error'=>'You have already reviewed this purchase.'],409);throw $exception;}
json_response(['message'=>'Thank you. Your review will appear after our team approves it.']);
