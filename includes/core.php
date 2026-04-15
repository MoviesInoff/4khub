<?php
require_once __DIR__ . '/../config.php';

// ── DATABASE ──────────────────────────────────────────────
class DB {
    private static $pdo = null;
    public static function conn() {
        if (!self::$pdo) {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8';
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ));
            } catch (PDOException $e) {
                die('<div style="font-family:sans-serif;padding:30px;background:#0a0a0f;color:#ef4444"><h2>DB Error</h2><p>'.htmlspecialchars($e->getMessage()).'</p></div>');
            }
        }
        return self::$pdo;
    }
    public static function q($sql, $p=array()) {
        $s = self::conn()->prepare($sql); $s->execute($p); return $s;
    }
    public static function row($sql, $p=array()) { return self::q($sql,$p)->fetch(); }
    public static function rows($sql, $p=array()) { return self::q($sql,$p)->fetchAll(); }
    public static function insert($sql, $p=array()) { self::q($sql,$p); return self::conn()->lastInsertId(); }
    public static function exec($sql, $p=array()) { return self::q($sql,$p)->rowCount(); }
}

// ── SETTINGS ──────────────────────────────────────────────
function setting($key, $default='') {
    try {
        $r = DB::row("SELECT setting_value FROM settings WHERE setting_key=?", array($key));
        return $r ? $r['setting_value'] : $default;
    } catch(Exception $e) { return $default; }
}
function setSetting($key, $val) {
    DB::exec("INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=?", array($key,$val,$val));
}

// ── SESSION ───────────────────────────────────────────────
function sess() {
    if (session_status()===PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params(604800, '/');
        session_start();
    }
}
function loggedIn() { sess(); return !empty($_SESSION['uid']); }
function isAdmin()  { sess(); return !empty($_SESSION['role']) && $_SESSION['role']==='admin'; }
function curUser()  { if(!loggedIn()) return null; return DB::row("SELECT * FROM users WHERE id=?",array($_SESSION['uid'])); }

function doLogin($email, $pass) {
    $u = DB::row("SELECT * FROM users WHERE email=? AND is_active=1", array($email));
    if ($u && password_verify($pass, $u['password'])) {
        sess(); session_regenerate_id(true);
        $_SESSION['uid']=$u['id']; $_SESSION['uname']=$u['username']; $_SESSION['role']=$u['role'];
        return true;
    }
    return false;
}
function doLogout() { sess(); $_SESSION=array(); session_destroy(); header('Location: /index.php'); exit; }
function requireAdmin() {
    sess();
    if (!loggedIn()) { header('Location: /login.php'); exit; }
    if (!isAdmin())  { header('Location: /index.php'); exit; }
}

function csrf() {
    sess();
    if (empty($_SESSION[CSRF_KEY])) {
        $_SESSION[CSRF_KEY] = function_exists('random_bytes') ? bin2hex(random_bytes(32)) : bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION[CSRF_KEY];
}
function verifyCsrf($t) { sess(); return !empty($_SESSION[CSRF_KEY]) && !empty($t) && hash_equals($_SESSION[CSRF_KEY],$t); }

// ── TMDB API ──────────────────────────────────────────────
function tmdbRequest($endpoint, $params=array()) {
    $key = setting('tmdb_api_key','');
    if (!$key) return array('results'=>array(),'error'=>'no_key');
    $params['api_key']=$key; $params['language']='en-US';
    $url = TMDB_BASE.$endpoint.'?'.http_build_query($params);
    $data = null;
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL=>$url, CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>15, CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_SSL_VERIFYHOST=>false, CURLOPT_USERAGENT=>'CineHub/1.0',
        ));
        $r = curl_exec($ch); curl_close($ch);
        if ($r) $data = json_decode($r, true);
    }
    if (!$data) {
        $ctx = stream_context_create(array('http'=>array('timeout'=>12,'ignore_errors'=>true,'header'=>"User-Agent: CineHub/1.0\r\n"),'ssl'=>array('verify_peer'=>false,'verify_peer_name'=>false)));
        $r = @file_get_contents($url, false, $ctx);
        if ($r) $data = json_decode($r, true);
    }
    return is_array($data) ? $data : array('results'=>array());
}

function tmdbImg($path, $size='w500') {
    if (!$path) return '/assets/images/no-poster.jpg';
    return TMDB_IMG.$size.$path;
}

// ── SLUG ──────────────────────────────────────────────────
function makeSlug($title, $year='', $type='movie') {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($year) $slug .= '-'.$year;
    $slug .= '-'.($type==='tv'?'series':'movie');
    // Check uniqueness
    $base = $slug; $i = 2;
    while (DB::row("SELECT id FROM media WHERE slug=?", array($slug))) {
        $slug = $base.'-'.$i++;
    }
    return $slug;
}

// ── HELPERS ───────────────────────────────────────────────
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function jd($s) { return $s ? json_decode($s, true) : array(); }
function je($a) { return json_encode($a); }

function formatRuntime($min) {
    if (!$min) return '';
    return floor($min/60).'h '.($min%60).'m';
}

function tagsJson($media) {
    $tags = jd(isset($media['tags'])?$media['tags']:'[]');
    return $tags ? $tags : array();
}

function primaryColor() {
    return setting('primary_color', '#f97316');
}
