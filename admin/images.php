<?php
declare(strict_types=1);
session_start(['cookie_httponly'=>true,'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','cookie_samesite'=>'Strict']);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
$config=dirname(__DIR__).'/api/rahhal-media-secret.php';
if(!is_file($config)){http_response_code(503);exit('لوحة الصور غير مفعلة: أنشئ api/rahhal-media-secret.php حسب التعليمات.');}
$secret=require $config;
if(!is_string($secret)||strlen($secret)<20){http_response_code(503);exit('إعداد كلمة المرور غير صالح.');}
if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));
$error='';$success='';$authenticated=!empty($_SESSION['rahhal_media_admin']);
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))){http_response_code(403);exit('طلب غير صالح');}
 if(isset($_POST['logout'])){unset($_SESSION['rahhal_media_admin']);$authenticated=false;}
 elseif(!$authenticated){
   if(password_verify((string)($_POST['password']??''),$secret)){session_regenerate_id(true);$_SESSION['rahhal_media_admin']=true;$authenticated=true;}
   else{$error='كلمة المرور غير صحيحة';sleep(1);}
 }else{
   $country=(string)($_POST['country']??'');
   $place=trim((string)($_POST['place']??''));
   $allowed=['uae','ksa','mauritius','srilanka','netherlands','belgium','japan','china'];
   $file=$_FILES['photo']??null;
   try{
     if(!in_array($country,$allowed,true))throw new RuntimeException('اختر دولة صحيحة');
     if($place===''||strlen($place)>360)throw new RuntimeException('اكتب اسم المكان كما يظهر في البطاقة');
     if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>8*1024*1024)throw new RuntimeException('اختر صورة أقل من 8 ميجابايت');
     if(!extension_loaded('gd')||!function_exists('imagewebp'))throw new RuntimeException('يجب تفعيل PHP GD وWebP على الاستضافة');
     $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
     $types=['image/jpeg'=>'imagecreatefromjpeg','image/png'=>'imagecreatefrompng','image/webp'=>'imagecreatefromwebp'];
     if(!isset($types[$mime]))throw new RuntimeException('المسموح JPG أو PNG أو WebP فقط');
     $dims=@getimagesize($file['tmp_name']);
     if(!$dims||$dims[0]<1||$dims[1]<1||$dims[0]*$dims[1]>30000000)throw new RuntimeException('أبعاد الصورة غير مدعومة');
     $source=@$types[$mime]($file['tmp_name']);
     if(!$source)throw new RuntimeException('تعذر قراءة الصورة');
     $w=imagesx($source);$h=imagesy($source);$scale=min(1,1600/max($w,$h));$nw=max(1,(int)round($w*$scale));$nh=max(1,(int)round($h*$scale));
     $target=imagecreatetruecolor($nw,$nh);$white=imagecolorallocate($target,255,255,255);imagefill($target,0,0,$white);
     imagecopyresampled($target,$source,0,0,0,0,$nw,$nh,$w,$h);imagedestroy($source);
     $folder=dirname(__DIR__).'/assets/uploads/'.$country;
     if(!is_dir($folder)&&!mkdir($folder,0755,true))throw new RuntimeException('تعذر إنشاء مجلد الصور');
     $filename=bin2hex(random_bytes(12)).'.webp';$full=$folder.'/'.$filename;
     if(!imagewebp($target,$full,78)){imagedestroy($target);throw new RuntimeException('تعذر حفظ الصورة');}
     imagedestroy($target);chmod($full,0644);
     $manifest=dirname(__DIR__).'/assets/rahhal-image-overrides.json';
     $map=[];
     if(is_file($manifest)){$loaded=json_decode((string)file_get_contents($manifest),true);if(is_array($loaded))$map=$loaded;}
     $map[$place]='/assets/uploads/'.$country.'/'.$filename;
     $tmp=$manifest.'.'.bin2hex(random_bytes(5)).'.tmp';
     if(file_put_contents($tmp,json_encode($map,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),LOCK_EX)===false||!rename($tmp,$manifest))throw new RuntimeException('تعذر حفظ ربط الصورة');
     $success='تم ضغط الصورة وحفظها وربطها بالاسم المحدد. حدّث الموقع للتأكد من ظهورها.';
   }catch(Throwable $e){$error=$e->getMessage();}
 }
}
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>إدارة صور رحّال</title><style>body{font-family:Tahoma,Arial,sans-serif;background:#eef4f5;color:#153b45;margin:0;padding:30px 14px}.panel{max-width:580px;margin:auto;background:white;padding:28px;border-radius:20px;box-shadow:0 10px 30px #1233}h1{margin-top:0}label{display:block;margin:15px 0 7px;font-weight:bold}input,select,button{box-sizing:border-box;width:100%;padding:13px;border:1px solid #c5d8dd;border-radius:10px;font:inherit}button{margin-top:18px;background:#087a82;color:white;border:0;cursor:pointer}.error{color:#a11919}.success{color:#16754c}.hint{font-size:13px;color:#567}</style></head><body><main class="panel"><h1>📷 إدارة صور رحّال</h1><?php if($error):?><p class="error"><?=h($error)?></p><?php endif;?><?php if($success):?><p class="success"><?=h($success)?></p><?php endif;?><?php if(!$authenticated):?><p>سجّل الدخول بكلمة مرور إدارة الصور.</p><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label>كلمة المرور</label><input type="password" name="password" required autocomplete="current-password"><button>دخول</button></form><?php else:?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label>الدولة</label><select name="country" required><option value="japan">🇯🇵 اليابان</option><option value="china">🇨🇳 الصين</option><option value="netherlands">🇳🇱 هولندا</option><option value="belgium">🇧🇪 بلجيكا</option><option value="uae">🇦🇪 الإمارات</option><option value="ksa">🇸🇦 السعودية</option><option value="mauritius">🇲🇺 موريشيوس</option><option value="srilanka">🇱🇰 سريلانكا</option></select><label>اسم المكان في البطاقة (مطابق تمامًا)</label><input name="place" required maxlength="180" placeholder="مثال: فندق شيبويا توبو – Shibuya Tobu Hotel"><label>الصورة</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required><p class="hint">تُحوّل تلقائيًا إلى WebP، بحد أقصى 1600 بكسل و8 ميجابايت للصورة الأصلية. الصور تُحفظ على Hostinger ولا تزيد حجم GitHub.</p><button>ضغط الصورة وربطها بالمكان</button></form><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><button name="logout" value="1" style="background:#5c777b">تسجيل الخروج</button></form><?php endif;?></main></body></html>