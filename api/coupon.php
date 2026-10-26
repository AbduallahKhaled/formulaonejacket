<?php
// Checkout preview of a discount code. api/order.php checks the code again; this only shows the customer the amount.
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('{"ok":false}'); }
if (!fj_rate_ok('coupon', 20, 600)) { http_response_code(429); exit('{"ok":false,"error":"slow_down"}'); }

$site = fj_site();
$qty = max(1, min((int)($_POST['qty'] ?? 1), (int)$site['max_qty']));
[$discount, $err, $min] = fj_coupon_check((string)($_POST['code'] ?? ''), (int)$site['price'] * $qty);
echo json_encode($err === '' ? ['ok' => true, 'code' => fj_coupon_code((string)$_POST['code']), 'discount' => $discount] : ['ok' => false, 'error' => $err, 'min' => $min]);
