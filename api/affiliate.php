<?php
// Affiliate applications from affiliate.html. The owner reviews them on the admin Affiliates tab.
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
if (!fj_rate_ok('affiliate', 3, 3600)) fail('slow_down', 429);

$f = fn(string $k, int $max) => fj_clean($_POST[$k] ?? '', $max);
$a = [
    'name' => $f('name', 80), 'phone' => preg_replace('/[\s-]/', '', $f('phone', 20)) ?? '', 'email' => $f('email', 120),
    'gov' => fj_str($_POST['gov'] ?? ''), 'platform' => $f('platform', 20), 'link' => $f('link', 200), 'more' => $f('more', 400),
    'followers' => $f('followers', 40), 'plan' => $f('plan', 800), 'method' => $f('method', 20), 'account' => $f('account', 120),
    'code' => fj_coupon_code($f('code', 15)),
];
if ($a['name'] === '' || $a['plan'] === '' || $a['account'] === '' || $a['followers'] === '' || empty($_POST['agree'])) fail('missing');
if (!preg_match('/^(\+?20|0)?1[0125]\d{8}$/', $a['phone'])) fail('phone');
if ($a['email'] !== '' && !filter_var($a['email'], FILTER_VALIDATE_EMAIL)) fail('email');
if (!in_array($a['gov'], FJ_GOVS, true)) fail('missing');
if (!in_array($a['platform'], ['Instagram', 'TikTok', 'Facebook', 'YouTube', 'Snapchat', 'Other'], true)) fail('missing');
if (!in_array($a['method'], ['InstaPay', 'Vodafone Cash', 'Bank transfer'], true)) fail('missing');
if (!preg_match('~^https?://[^\s<>"]+\.[^\s<>"]+$~i', $a['link'])) fail('link'); // shown to the owner as a link, so only web addresses
if ($a['code'] !== '' && !preg_match('/^[A-Z0-9]{3,15}$/', $a['code'])) fail('code');
$a['phone'] = fj_eg_phone($a['phone']);

if (!is_dir(FJ_DATA)) mkdir(FJ_DATA, 0755, true);
$lock = fopen(FJ_DATA . '/.affiliate.lock', 'c');
flock($lock, LOCK_EX);
// data/affiliates.json: list of applications; one open application per phone number
$all = fj_read(FJ_DATA . '/affiliates.json') ?? [];
foreach ($all as $old) if ($old['phone'] === $a['phone'] && $old['status'] !== 'rejected') fail('exists'); // exit releases the lock
$all[] = ['id' => bin2hex(random_bytes(4)), 'created' => date('c'), 'status' => 'new'] + $a;
fj_write(FJ_DATA . '/affiliates.json', $all);
flock($lock, LOCK_UN);
fclose($lock);

echo '{"ok":true}';
if (function_exists('litespeed_finish_request')) litespeed_finish_request();
elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
$base = fj_base_url();
fj_notify("🤝 New affiliate application\n{$a['name']}, 0" . substr($a['phone'], 2) . "\n{$a['platform']}: {$a['link']} ({$a['followers']})"
    . ($base !== '' ? "\n$base/admin.php?tab=affiliates" : ''));
