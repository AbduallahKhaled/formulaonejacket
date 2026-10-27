<?php
// Customer accounts (phone + password). Creating one makes a one-use 10% code that only works with that phone.
// action=me (default) | register | login | logout
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// data/accounts.json: phone (20…) => {name, hash, code, created}
function acct_all(): array { return fj_read(FJ_DATA . '/accounts.json') ?? []; }

function acct_out(string $phone, array $a): never {
    $c = fj_coupons()[$a['code']] ?? [];
    echo json_encode(['ok' => true, 'user' => ['name' => $a['name'], 'phone' => '0' . substr($phone, 2), 'code' => $a['code'],
        'used' => (int)($c['used'] ?? 0) > 0]], JSON_UNESCAPED_UNICODE);
    exit;
}

ini_set('session.gc_maxlifetime', (string)(30 * 86400)); // stay signed in for 30 days
session_name('fjacct'); // apart from the admin session
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'lifetime' => 30 * 86400,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();

$action = fj_str($_POST['action'] ?? $_GET['action'] ?? 'me');
if ($action !== 'me' && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method', 405);

if ($action === 'logout') { $_SESSION = []; session_destroy(); exit('{"ok":true}'); }

if ($action === 'me') {
    $p = (string)($_SESSION['acct'] ?? '');
    $a = $p !== '' ? (acct_all()[$p] ?? null) : null;
    $a ? acct_out($p, $a) : exit('{"ok":false}');
}

$phone = preg_replace('/[\s-]/', '', fj_clean($_POST['phone'] ?? '', 20)) ?? '';
$pass = fj_str($_POST['password'] ?? '');
if (!preg_match('/^(\+?20|0)?1[0125]\d{8}$/', $phone)) fail('phone');
$key = fj_eg_phone($phone);

if ($action === 'login') {
    if (!fj_rate_ok('acct-login', 10, 600)) fail('slow_down', 429);
    $a = acct_all()[$key] ?? null;
    if (!$a || !password_verify($pass, $a['hash'])) fail('wrong', 401);
    session_regenerate_id(true);
    $_SESSION['acct'] = $key;
    acct_out($key, $a);
}

if ($action !== 'register') fail('action');
if (!fj_rate_ok('acct-new', 5, 3600)) fail('slow_down', 429);
$name = fj_clean($_POST['name'] ?? '', 80);
if ($name === '') fail('missing');
if (strlen($pass) < 6 || strlen($pass) > 200) fail('short');

if (!is_dir(FJ_DATA)) mkdir(FJ_DATA, 0755, true);
$lock = fopen(FJ_DATA . '/.account.lock', 'c');
flock($lock, LOCK_EX); // one phone, one account and one code, even with two tabs at once
$all = acct_all();
if (isset($all[$key])) fail('exists'); // exit releases the lock
$coupons = fj_coupons();
do { $code = 'HELLO' . strtoupper(bin2hex(random_bytes(3))); } while (isset($coupons[$code]));
$coupons[$code] = ['type' => 'percent', 'value' => 10, 'min' => 0, 'expires' => '', 'max_uses' => 1, 'used' => 0,
    'active' => true, 'phone' => $key, 'note' => "New account: $name", 'created' => date('c')];
fj_save_coupons($coupons);
$all[$key] = ['name' => $name, 'hash' => password_hash($pass, PASSWORD_DEFAULT), 'code' => $code, 'created' => date('c')];
fj_write(FJ_DATA . '/accounts.json', $all);
flock($lock, LOCK_UN);
fclose($lock);

session_regenerate_id(true);
$_SESSION['acct'] = $key;
acct_out($key, $all[$key]);
