<?php
// The store's live settings as a script: every page loads it before app.js, so prices, sizes and stock come from the admin.
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');

$site = fj_site();
$reviews = array_values(array_map(
    fn($r) => ['name' => $r['name'], 'city' => $r['city'] ?? '', 'rating' => (int)$r['rating'], 'text' => $r['text'], 'size' => $r['size'] ?? '', 'created' => $r['created']],
    array_filter(array_reverse(fj_reviews()), fn($r) => !empty($r['shown']))
));

echo 'window.STORE=' . json_encode([
    'price' => (int)$site['price'],
    'was' => (int)$site['was'],
    'wa' => $site['whatsapp'],
    'instapay' => $site['pay_instapay'] ? $site['instapay'] : '',
    'cod' => (bool)$site['pay_cod'],
    'sizes' => $site['sizes'],
    'stock' => fj_available($site, fj_orders()),
    'ship' => $site['ship'],
    'shipOther' => (int)$site['ship_other'],
    'maxQty' => (int)$site['max_qty'],
    'reviews' => array_slice($reviews, 0, 12),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ';';
