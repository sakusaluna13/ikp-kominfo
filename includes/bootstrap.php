<?php
declare(strict_types=1);
if(session_status()!==PHP_SESSION_ACTIVE){
 $secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
 session_set_cookie_params(['httponly'=>true,'secure'=>$secure,'samesite'=>'Lax']); session_start();
}
require_once __DIR__.'/../config/database.php';

// Auto-sync foto profil jika belum ada di session
if (isset($_SESSION['user']['id']) && !isset($_SESSION['user']['photo'])) {
    try {
        $st = $pdo->prepare('SELECT foto FROM users WHERE id = :id LIMIT 1');
        $st->execute(['id' => $_SESSION['user']['id']]);
        $_SESSION['user']['photo'] = (string) ($st->fetchColumn() ?: '');
    } catch (PDOException $e) {
        $_SESSION['user']['photo'] = '';
    }
}
function e(?string $v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csrf_token():string{if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));return $_SESSION['csrf_token'];}
function verify_csrf():void{$t=$_POST['csrf_token']??'';if(!is_string($t)||!hash_equals(csrf_token(),$t)){http_response_code(419);exit('Permintaan tidak valid. Muat ulang halaman.');}}
function flash(string $type,string $message):void{$_SESSION['flash']=['type'=>$type,'message'=>$message];}
function get_flash():?array{$f=$_SESSION['flash']??null;unset($_SESSION['flash']);return $f;}
function is_logged_in():bool{return isset($_SESSION['user']['id']);}
function require_login():void{if(!is_logged_in()){flash('error','Silakan login terlebih dahulu.');header('Location: ../auth/login.php');exit;}}
function require_role(string ...$roles):void{require_login();if(!in_array($_SESSION['user']['role']??'', $roles,true)){http_response_code(403);exit('Akses ditolak.');}}
function hitung_durasi(?string $tglPinjam, ?string $tglKembali): int {
    if (!$tglPinjam) return 1;
    $target = !empty($tglKembali) ? $tglKembali : date('Y-m-d');
    $diff = (int) round((strtotime($target) - strtotime($tglPinjam)) / 86400);
    return max(1, $diff);
}
