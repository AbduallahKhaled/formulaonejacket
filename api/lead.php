<?php
// Pop-up offers (spin wheel, scratch card, claim your discount): counts views and plays per offer,
// and saves the email + phone a visitor gives to claim the prize. The admin "Pop-up offers" tab compares them.
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method', 405);
$game = fj_str($_POST['game'] ?? '');
$action = fj_str($_POST['action'] ?? '');
if (!isset(FJ_GAMES[$game]) || !in_array($action, ['view', 'play', 'lead'], true)) fail('missing');

if ($action === 'lead') {
    if (!fj_rate_ok('lead', 5, 3600)) fail('slow_down', 429);
    $email = fj_clean($_POST['email'] ?? '', 120);
    $phone = preg_replace('/[\s-]/', '', fj_clean($_POST['phone'] ?? '', 20)) ?? '';
    $code = fj_coupon_code(fj_str($_POST['code'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('email');
    if (!preg_match('/^(\+?20|0)?1[0125]\d{8}$/', $phone)) fail('phone');
    if (!in_array($code, FJ_GAMES[$game][1], true)) fail('missing'); // only the codes this offer hands out
    $phone = fj_eg_phone($phone);
} elseif (!fj_rate_ok('gamestat', 30, 3600)) fail('slow_down', 429);

if (!is_dir(FJ_DATA)) mkdir(FJ_DATA, 0755, true);
$lock = fopen(FJ_DATA . '/.games.lock', 'c');
flock($lock, LOCK_EX);
// data/games.json: {wheel|scratch|claim: {views, plays, leads}}
$stats = fj_read(FJ_DATA . '/games.json') ?? [];
$key = ['view' => 'views', 'play' => 'plays', 'lead' => 'leads'][$action];
if ($action === 'lead') {
    // data/leads.json: list of {id, created, game, code, email, phone}; a phone already on the list is not counted twice
    $leads = fj_read(FJ_DATA . '/leads.json') ?? [];
    if (!in_array($phone, array_column($leads, 'phone'), true)) {
        $leads[] = ['id' => bin2hex(random_bytes(4)), 'created' => date('c'), 'game' => $game, 'code' => $code, 'email' => $email, 'phone' => $phone];
        fj_write(FJ_DATA . '/leads.json', $leads);
        $stats[$game][$key] = (int)($stats[$game][$key] ?? 0) + 1;
    }
} else $stats[$game][$key] = (int)($stats[$game][$key] ?? 0) + 1;
fj_write(FJ_DATA . '/games.json', $stats);
flock($lock, LOCK_UN);
fclose($lock);

echo '{"ok":true}';
