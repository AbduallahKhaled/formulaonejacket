<?php
// Receives an order from checkout.html, prices it from the admin settings (never from the browser), holds the stock, stores it.
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(string $msg, int $code = 400, array $extra = []): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg] + $extra);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method', 405);
if (!fj_rate_ok('order', 5, 600)) fail('slow_down', 429);

$site = fj_site();
$size = fj_str($_POST['size'] ?? '');
if (!in_array($size, fj_size_names($site), true)) fail('size');
$qty = (int)fj_str($_POST['qty'] ?? '');
if ($qty < 1 || $qty > (int)$site['max_qty']) fail('qty');

$name = fj_clean($_POST['name'] ?? '', 80);
$phone = preg_replace('/[\s-]/', '', fj_clean($_POST['phone'] ?? '', 20)) ?? '';
$gov = fj_str($_POST['gov'] ?? '');
$addr = fj_clean($_POST['addr'] ?? '', 300);
if ($name === '' || $addr === '' || !in_array($gov, FJ_GOVS, true)) fail('missing');
if (!preg_match('/^(\+?20|0)?1[0125]\d{8}$/', $phone)) fail('phone');

$pay = fj_str($_POST['pay'] ?? '') === 'instapay' ? 'instapay' : 'cod';
if ($pay === 'cod' && !$site['pay_cod']) fail('pay');
if ($pay === 'instapay' && (!$site['pay_instapay'] || $site['instapay'] === '')) fail('pay');

$sub = (int)$site['price'] * $qty;
$delivery = fj_ship_fee($site, $gov);
$coupon = fj_coupon_code(fj_str($_POST['code'] ?? ''));

// One order at a time: the stock check, the discount-code use count and the write must not interleave,
// or two buyers could take the last jacket or both use a code that had one use left.
if (!is_dir(FJ_DATA . '/orders')) mkdir(FJ_DATA . '/orders', 0755, true);
$lock = fopen(FJ_DATA . '/.order.lock', 'c');
flock($lock, LOCK_EX);
$discount = 0;
if ($coupon !== '') {
    [$discount, $cerr] = fj_coupon_check($coupon, $sub);
    if ($cerr !== '') fail('coupon_' . $cerr); // exit releases the lock
}
$total = $sub - $discount + $delivery;
$left = fj_available($site, fj_orders())[$size] ?? 0;
if ($qty > $left) fail('stock', 409, ['left' => $left]);
// stored only once the order is accepted, so refused orders leave no files behind
$proof = $pay === 'instapay' && isset($_FILES['shot']) && is_array($_FILES['shot']) ? fj_save_proof($_FILES['shot']) : '';

do {
    $id = 'FJ-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
} while (is_file(fj_order_path($id)));

$order = [
    'id' => $id,
    'created' => date('c'),
    'status' => 'new',
    'size' => $size,
    'qty' => $qty,
    'unit_price' => (int)$site['price'],
    'delivery_fee' => $delivery,
    'coupon' => $discount ? $coupon : '',
    'discount' => $discount,
    'total' => $total,
    'payment' => ['method' => $pay, 'status' => $pay === 'cod' ? 'cod' : ($proof ? 'sent' : 'awaiting'), 'proof' => $proof, 'ref' => ''],
    'customer' => ['name' => $name, 'phone' => $phone, 'gov' => $gov, 'address' => $addr],
    'lang' => fj_str($_POST['lang'] ?? '') === 'ar' ? 'ar' : 'en',
    'note' => '',
    'history' => [['status' => 'new', 'at' => date('c')]],
];
fj_write(fj_order_path($id), $order);
if ($discount) fj_coupon_use($coupon);
flock($lock, LOCK_UN);
fclose($lock);

echo json_encode(['ok' => true, 'id' => $id, 'total' => $total, 'delivery' => $delivery, 'discount' => $discount, 'proof' => $proof !== '']);

// The buyer has the answer now; the Telegram call (up to 5 s) runs after the response is sent.
if (function_exists('litespeed_finish_request')) litespeed_finish_request();
elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
$base = fj_base_url();
fj_notify("🧥 New order $id - $total EGP\nSize $size x $qty\n$name, $phone ($gov)\n"
    . ($pay === 'cod' ? 'Cash on delivery' : 'InstaPay' . ($proof ? ', screenshot uploaded' : ''))
    . ($discount ? "\nCode $coupon (-$discount)" : '') . ($base !== '' ? "\n$base/admin.php?tab=orders&q=$id" : ''));
