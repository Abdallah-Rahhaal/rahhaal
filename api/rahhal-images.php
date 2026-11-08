<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
$file=dirname(__DIR__).'/assets/rahhal-image-overrides.json';
if(!is_file($file)){echo '{}';exit;}
$data=json_decode((string)file_get_contents($file),true);
if(!is_array($data)){echo '{}';exit;}
$valid=[];
foreach($data as $name=>$path){
 if(!is_string($name)||!is_string($path)||strlen($name)>360)continue;
 if(!preg_match('~^/assets/uploads/[a-z]+/[a-f0-9]{24}\\.webp$~D',$path))continue;
 if(is_file(dirname(__DIR__).$path))$valid[$name]=$path;
}
echo json_encode($valid,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
