<?php
// The store's live settings as a script: every page loads it before app.js, so prices, sizes and stock come from the admin.
declare(strict_types=1);
require __DIR__ . '/../lib.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
header('Expires: 0'); // an Expires header already set keeps the host's mod_expires from adding a 7-day one
header('X-Content-Type-Options: nosniff');

// Every page view needs this, and stock means reading every order file. The answer is kept in data/config-cache.js and
// rebuilt when orders, stock, settings or reviews change (their modified times are the key). A change in the last
// second is not trusted yet, since file times only count whole seconds.
clearstatcache();
$times = array_map(fn($p) => (int)@filemtime(FJ_DATA . $p), ['/orders', '/stock.json', '/site.json', '/reviews.json']);
$key = '// ' . implode('-', $times) . "\n";
$cacheFile = FJ_DATA . '/config-cache.js';
$cached = is_file($cacheFile) ? (string)file_get_contents($cacheFile) : '';
if (str_starts_with($cached, $key)) exit(substr($cached, strlen($key)));

$site = fj_site();
$reviews = array_values(array_map(
    fn($r) => ['name' => $r['name'], 'city' => $r['city'] ?? '', 'rating' => (int)$r['rating'], 'text' => $r['text'], 'size' => $r['size'] ?? '', 'created' => $r['created']],
    array_filter(array_reverse(fj_reviews()), fn($r) => !empty($r['shown']))
));

$js = 'window.STORE=' . json_encode([
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
if (is_dir(FJ_DATA) && max($times) < time() - 1) {
    $tmp = $cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $key . $js) !== false) rename($tmp, $cacheFile);
}
echo $js;
