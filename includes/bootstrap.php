<?php
declare(strict_types=1);
if(session_status()!==PHP_SESSION_ACTIVE){
 $secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
 session_set_cookie_params(['httponly'=>true,'secure'=>$secure,'samesite'=>'Lax']); session_start();
}
require_once __DIR__.'/../config/database.php';
function e(?string $v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csrf_token():string{if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));return $_SESSION['csrf_token'];}
function verify_csrf():void{$t=$_POST['csrf_token']??'';if(!is_string($t)||!hash_equals(csrf_token(),$t)){http_response_code(419);exit('Permintaan tidak valid. Muat ulang halaman.');}}
function flash(string $type,string $message):void{$_SESSION['flash']=['type'=>$type,'message'=>$message];}
function get_flash():?array{$f=$_SESSION['flash']??null;unset($_SESSION['flash']);return $f;}
function is_logged_in():bool{return isset($_SESSION['user']['id']);}
function require_login():void{if(!is_logged_in()){flash('error','Silakan login terlebih dahulu.');header('Location: ../auth/login.php');exit;}}
function require_role(string ...$roles):void{require_login();if(!in_array($_SESSION['user']['role']??'', $roles,true)){http_response_code(403);exit('Akses ditolak.');}}
