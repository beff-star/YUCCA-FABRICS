<?php
require_once __DIR__.'/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$base=APP_SITE_URL . BASE_URL;
if($base===''){
    $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
    $host=(string)($_SERVER['HTTP_HOST']??'localhost');
    if(!preg_match('/^[a-zA-Z0-9.-]+(?::\d{1,5})?$/',$host))$host='localhost';
    $base=$scheme.'://'.$host.BASE_URL;
}
$urls=array_map(fn($path)=>$base.'/'.ltrim($path,'/'),['','about.php','shop.php','services.php','consultations.php','gallery.php','blog.php','contact.php']);
foreach(db()->query('SELECT slug,updated_at FROM products WHERE is_active=1')->fetchAll() as $row)$urls[]=[$base.'/product-details.php?slug='.rawurlencode($row['slug']),$row['updated_at']];
foreach(db()->query("SELECT slug,published_at FROM blog_posts WHERE status='published' AND published_at<=NOW()")->fetchAll() as $row)$urls[]=[$base.'/article.php?slug='.rawurlencode($row['slug']),$row['published_at']];
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach($urls as $url){$loc=is_array($url)?$url[0]:$url;$lastmod=is_array($url)?'<lastmod>'.htmlspecialchars(date('Y-m-d',strtotime($url[1])),ENT_XML1).'</lastmod>':'';echo '<url><loc>'.htmlspecialchars($loc,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc>'.$lastmod."</url>\n";}
echo "</urlset>\n";
