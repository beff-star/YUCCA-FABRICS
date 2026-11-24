<?php
require_once __DIR__.'/../includes/functions.php';
if(!current_user())json_response(['error'=>'Sign in to save favourites.'],401);
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['error'=>'Method not allowed.'],405);
verify_csrf();$productId=filter_var($_POST['product_id']??null,FILTER_VALIDATE_INT);$action=(string)($_POST['action']??'add');
if(!$productId||!in_array($action,['add','remove'],true))json_response(['error'=>'Invalid wishlist request.'],422);
$pdo=db();$pdo->prepare('INSERT INTO wishlists (user_id) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)')->execute([current_user()['id']]);$wishlistId=(int)$pdo->lastInsertId();
if($action==='remove')$pdo->prepare('DELETE FROM wishlist_items WHERE wishlist_id=? AND product_id=?')->execute([$wishlistId,$productId]);
else{$check=$pdo->prepare('SELECT id FROM products WHERE id=? AND is_active=1');$check->execute([$productId]);if(!$check->fetch())json_response(['error'=>'This fabric is no longer available.'],404);$pdo->prepare('INSERT IGNORE INTO wishlist_items (wishlist_id,product_id) VALUES (?,?)')->execute([$wishlistId,$productId]);}
json_response(['message'=>$action==='remove'?'Removed from favourites.':'Saved to your favourites.']);
