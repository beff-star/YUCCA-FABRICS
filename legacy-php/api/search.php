<?php
require_once __DIR__.'/../includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='GET')json_response(['error'=>'Method not allowed.'],405);
$query=trim((string)($_GET['q']??''));
if(mb_strlen($query)<2||mb_strlen($query)>80)json_response(['results'=>[]]);
$stmt=db()->prepare("SELECT name,slug,colour FROM products WHERE is_active=1 AND (name LIKE ? OR colour LIKE ? OR sku LIKE ?) ORDER BY is_featured DESC,name LIMIT 6");
$term='%'.$query.'%';$stmt->execute([$term,$term,$term]);
$results=array_map(fn($row)=>['name'=>$row['name'],'slug'=>$row['slug'],'colour'=>$row['colour']],$stmt->fetchAll());
json_response(['results'=>$results]);
