<?php
require_once __DIR__.'/config/config.php';
header('Content-Type: text/plain; charset=utf-8');
$base=APP_SITE_URL;
if($base===''){$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=(string)($_SERVER['HTTP_HOST']??'localhost');if(!preg_match('/^[a-zA-Z0-9.-]+(?::\d{1,5})?$/',$host))$host='localhost';$base=$scheme.'://'.$host.BASE_URL;}else{$base.=BASE_URL;}
echo "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /account/\nDisallow: /auth/\nDisallow: /api/\nDisallow: /database/\nSitemap: ".$base."/sitemap.xml\n";
