<?php
// Formula One Jacket server helpers: settings, orders, stock per size, discount codes, reviews,
// Telegram alerts, payment screenshots, backups. Plain JSON files in data/, no database.
declare(strict_types=1);
date_default_timezone_set('Africa/Cairo');
// Errors go to the server log, never into the page: a crash must not print server paths to a visitor.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

const FJ_ROOT = __DIR__;
const FJ_DATA = __DIR__ . '/data';

// Second lock on data/ (orders, customers, screenshots, tokens) in case the root .htaccess rules are lost or not applied.
if (is_dir(FJ_DATA) && !is_file(FJ_DATA . '/.htaccess')) @file_put_contents(FJ_DATA . '/.htaccess', "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");

/** Used until the owner saves their own in the admin (data/site.json). */
const FJ_DEFAULTS = [
    'price' => 1250,
    'was' => 0,
    // launch offer: the first offer_orders orders pay offer_price per jacket, then the price above (0 orders = off)
    'offer_price' => 850,
    'offer_orders' => 10,
    'whatsapp' => '201557707691',
    'instapay' => 'vampotypebeats@instapay',
    'pay_cod' => true,
    'pay_instapay' => true,
    // [size, chest cm, length cm]
    'sizes' => [['S', 108, 66], ['M', 114, 68], ['L', 120, 70], ['XL', 126, 72], ['XXL', 132, 74]],
    'ship' => [], // governorates that cost something other than ship_other
    'ship_other' => 90,
    'max_qty' => 10,
];
/** Keep in sync with GOVS in app.js (the English names are the values the checkout posts). */
const FJ_GOVS = ['Cairo', 'Giza', 'Alexandria', 'Qalyubia', 'Sharqia', 'Dakahlia', 'Gharbia', 'Monufia', 'Beheira', 'Kafr El Sheikh',
    'Damietta', 'Port Said', 'Ismailia', 'Suez', 'Faiyum', 'Beni Suef', 'Minya', 'Asyut', 'Sohag', 'Qena', 'Luxor', 'Aswan',
    'Red Sea', 'New Valley', 'Matrouh', 'North Sinai', 'South Sinai'];
const FJ_ID = '/^FJ-[0-9]{6}-[A-Z0-9]{4}$/';
/** The jackets on sale. Keep the ids in sync with PRODUCTS in app.js. All share the one price and size list from the admin. */
const FJ_PRODUCTS = ['classic' => 'Classic', 'red' => 'Red', 'black' => 'Black'];

const FJ_STATUSES = [
    'new' => 'New',
    'confirmed' => 'Confirmed',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
    'returned' => 'Returned',
];
/** Payment status per order: cash on delivery (cod, collected) or InstaPay (awaiting, sent, confirmed, rejected). */
const FJ_PAY = [
    'cod' => 'Cash on delivery',
    'collected' => 'Cash collected',
    'awaiting' => 'Awaiting InstaPay',
    'sent' => 'Screenshot received',
    'confirmed' => 'InstaPay confirmed',
    'rejected' => 'InstaPay rejected',
];

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Form text: anything that is not a plain string (e.g. name[]=x) becomes '' instead of crashing the request. */
function fj_clean(mixed $s, int $max = 300): string {
    if (!is_string($s) && !is_int($s)) return '';
    return mb_substr(trim(str_replace(["\r", "\0"], '', (string)$s)), 0, $max);
}

/** Request value as a string; arrays become ''. */
function fj_str(mixed $v): string { return is_string($v) || is_int($v) ? (string)$v : ''; }

/**
 * The site address for links in Telegram alerts. Saved by the logged-in admin (admin.php), because a buyer controls
 * the Host header of their own request and could otherwise point the owner's "open the order" link at a fake login page.
 */
function fj_base_url(): string { return (string)(fj_read(FJ_DATA . '/base.json')['url'] ?? ''); }

/* ---------- JSON file storage ---------- */
function fj_read(string $path): ?array {
    if (!is_file($path)) return null;
    $d = json_decode((string)file_get_contents($path), true);
    return is_array($d) ? $d : null;
}

function fj_write(string $path, array $data): void {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    rename($tmp, $path);
}

function fj_list(string $dir): array {
    $out = [];
    foreach (glob(FJ_DATA . "/$dir/*.json") ?: [] as $f) if ($d = fj_read($f)) $out[] = $d;
    return $out;
}

/* ---------- Settings ---------- */
function fj_site(): array { return array_merge(FJ_DEFAULTS, fj_read(FJ_DATA . '/site.json') ?? []); }

/** Saves data/site.json and keeps the last 20 versions in data/backups. */
function fj_save_site(array $c): void {
    $file = FJ_DATA . '/site.json';
    if (is_file($file)) {
        $dir = FJ_DATA . '/backups';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        copy($file, "$dir/site-" . date('Ymd-His') . '.json');
        $all = glob("$dir/site-*.json") ?: [];
        sort($all);
        foreach (array_slice($all, 0, max(0, count($all) - 20)) as $old) @unlink($old);
    }
    fj_write($file, $c);
}

function fj_ship_fee(array $site, string $gov): int { return (int)($site['ship'][$gov] ?? $site['ship_other']); }
function fj_size_names(array $site): array { return array_column($site['sizes'], 0); }

/* ---------- Orders ---------- */
function fj_order_path(string $id): string { return FJ_DATA . "/orders/$id.json"; }
function fj_order(string $id): ?array { return preg_match(FJ_ID, $id) ? fj_read(fj_order_path($id)) : null; }
function fj_orders(): array {
    $o = fj_list('orders');
    usort($o, fn($a, $b) => strcmp($b['created'], $a['created']));
    return $o;
}

/** Launch-offer orders still open: cancelled or returned offer orders give their place back. */
function fj_offer_left(array $site, array $orders): int {
    $used = count(array_filter($orders, fn($o) => !empty($o['offer']) && !in_array($o['status'] ?? '', ['cancelled', 'returned'], true)));
    return (int)$site['offer_price'] > 0 ? max(0, (int)$site['offer_orders'] - $used) : 0;
}

/** The price per jacket right now: the launch price while offer orders are left. */
function fj_unit_price(array $site, array $orders): int {
    return fj_offer_left($site, $orders) > 0 ? (int)$site['offer_price'] : (int)$site['price'];
}

function fj_eg_phone(string $v): string {
    $d = preg_replace('/\D+/', '', $v) ?? '';
    if (str_starts_with($d, '0020')) $d = substr($d, 2);
    if (str_starts_with($d, '01') && strlen($d) === 11) $d = '2' . $d;
    if (str_starts_with($d, '1') && strlen($d) === 10) $d = '20' . $d;
    return $d;
}

/* ---------- Stock per jacket and size ---------- */
/** Order lines [{product, size, qty}]. Orders placed before there were several jackets hold one size and qty of the classic. */
function fj_items(array $o): array {
    return $o['items'] ?? [['product' => 'classic', 'size' => (string)($o['size'] ?? ''), 'qty' => (int)($o['qty'] ?? 0)]];
}
function fj_pieces(array $o): int { return array_sum(array_column(fj_items($o), 'qty')); }
/** Stock key of one jacket in one size, e.g. "red:M". */
function fj_sku(string $product, string $size): string { return "$product:$size"; }
/** "Red M" for a stock key. */
function fj_sku_label(string $k): string { [$p, $s] = explode(':', $k, 2) + ['', '']; return (FJ_PRODUCTS[$p] ?? $p) . " $s"; }
function fj_items_text(array $o): string {
    return implode(', ', array_map(fn($i) => fj_sku_label(fj_sku($i['product'], $i['size'])) . ' × ' . (int)$i['qty'], fj_items($o)));
}

/** data/stock.json: "product:size" => {qty, min}. Rows that are missing start at 0 with a low-stock line of 3. */
function fj_stock(?array $site = null): array {
    $s = fj_read(FJ_DATA . '/stock.json') ?? ['classic:S' => ['qty' => 4], 'classic:M' => ['qty' => 12], 'classic:L' => ['qty' => 9], 'classic:XL' => ['qty' => 2]];
    $out = [];
    foreach (array_keys(FJ_PRODUCTS) as $p) foreach (fj_size_names($site ?? fj_site()) as $k) {
        $row = $s[fj_sku($p, $k)] ?? ($p === 'classic' ? $s[$k] ?? null : null); // stock.json from before held the classic by size alone
        $out[fj_sku($p, $k)] = ['qty' => (int)($row['qty'] ?? 0), 'min' => (int)($row['min'] ?? 3)];
    }
    return $out;
}

/** Pieces held by orders not shipped yet (new or confirmed), per stock key. Shipping takes them out of stock for real. */
function fj_reserved(array $orders): array {
    $need = [];
    foreach ($orders as $o) {
        if (!in_array($o['status'] ?? '', ['new', 'confirmed'], true)) continue;
        foreach (fj_items($o) as $i) $need[fj_sku($i['product'], $i['size'])] = ($need[fj_sku($i['product'], $i['size'])] ?? 0) + (int)$i['qty'];
    }
    return $need;
}

/** What the shop can still sell per stock key: in stock minus what open orders hold. */
function fj_available(array $site, array $orders): array {
    $res = fj_reserved($orders);
    $out = [];
    foreach (fj_stock($site) as $k => $s) $out[$k] = max(0, $s['qty'] - ($res[$k] ?? 0));
    return $out;
}

/** Moves an order's pieces out of stock (ship) or back in (return / cancel after shipping), once each way. */
function fj_stock_move(array &$o, int $sign): void {
    $out = !empty($o['stock_used']) && empty($o['stock_back']);
    if (($sign < 0 && $out) || ($sign > 0 && !$out)) return;
    $stock = fj_stock() + (fj_read(FJ_DATA . '/stock.json') ?? []); // keeps rows of sizes taken off the site
    foreach (fj_items($o) as $i) {
        $k = fj_sku($i['product'], $i['size']);
        $stock[$k]['qty'] = (int)($stock[$k]['qty'] ?? 0) + $sign * (int)$i['qty'];
        $stock[$k]['min'] ??= 3;
    }
    fj_write(FJ_DATA . '/stock.json', $stock);
    $o['stock_used'] = true;
    $o['stock_back'] = $sign > 0;
}

/* ---------- Discount codes ---------- */
/** data/coupons.json: CODE => {type: percent|fixed, value, min, expires (Y-m-d or ''), max_uses (0 = no limit), used, active, note, created} */
// prize codes handed out by the spin wheel and scratch card in app.js; the admin can edit or switch them off like any code
const FJ_PRIZE_CODES = [
    'SPIN10'    => ['type' => 'percent', 'value' => 10, 'min' => 0, 'expires' => '', 'max_uses' => 0, 'used' => 0, 'active' => true, 'note' => 'Spin wheel prize', 'created' => '2026-10-28'],
    'FREESHIP'  => ['type' => 'fixed', 'value' => 90, 'min' => 0, 'expires' => '', 'max_uses' => 0, 'used' => 0, 'active' => true, 'note' => 'Spin wheel prize: free delivery (90 EGP off)', 'created' => '2026-10-28'],
    'SCRATCH10' => ['type' => 'percent', 'value' => 10, 'min' => 0, 'expires' => '', 'max_uses' => 0, 'used' => 0, 'active' => true, 'note' => 'Scratch card prize', 'created' => '2026-10-28'],
    'CLAIM10'   => ['type' => 'percent', 'value' => 10, 'min' => 0, 'expires' => '', 'max_uses' => 0, 'used' => 0, 'active' => true, 'note' => 'Claim-your-discount pop-up', 'created' => '2026-10-28'],
];
// the three pop-up offers (A/B/C test) and the codes each one hands out; api/lead.php counts them, admin "Pop-up offers" compares them
const FJ_GAMES = ['wheel' => ['Spin wheel', ['SPIN10', 'FREESHIP']], 'scratch' => ['Scratch card', ['SCRATCH10']], 'claim' => ['Claim your discount', ['CLAIM10']]];
function fj_coupons(): array { return (fj_read(FJ_DATA . '/coupons.json') ?? []) + FJ_PRIZE_CODES; }
function fj_save_coupons(array $c): void { ksort($c); fj_write(FJ_DATA . '/coupons.json', $c); }
function fj_coupon_code(string $s): string { return strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $s) ?? ''); }

/** Discount in EGP for a code on the jacket subtotal (before delivery). Returns [discount, error code or '', min]. */
function fj_coupon_check(string $code, int $subtotal): array {
    $code = fj_coupon_code($code);
    if ($code === '') return [0, 'empty', 0];
    $c = fj_coupons()[$code] ?? null;
    if (!$c || empty($c['active'])) return [0, 'invalid', 0];
    if (!empty($c['expires']) && $c['expires'] < date('Y-m-d')) return [0, 'expired', 0];
    if (!empty($c['max_uses']) && (int)($c['used'] ?? 0) >= (int)$c['max_uses']) return [0, 'used_up', 0];
    if ($subtotal < (int)($c['min'] ?? 0)) return [0, 'min', (int)$c['min']];
    $d = $c['type'] === 'percent'
        ? (int)floor($subtotal * min(100, max(0, (int)$c['value'])) / 100)
        : min($subtotal, max(0, (int)$c['value']));
    return [$d, '', 0];
}

function fj_coupon_use(string $code): void {
    $all = fj_coupons();
    if (!isset($all[$code])) return;
    $all[$code]['used'] = (int)($all[$code]['used'] ?? 0) + 1;
    fj_save_coupons($all);
}

/* ---------- Reviews (entered or approved by the owner) ---------- */
/** data/reviews.json: list of {id, name, city, rating 1-5, text, size, source, order_id, shown, created} */
function fj_reviews(): array { return fj_read(FJ_DATA . '/reviews.json') ?? []; }
function fj_save_reviews(array $r): void { fj_write(FJ_DATA . '/reviews.json', array_values($r)); }

/* ---------- Telegram alerts ---------- */
/** data/notify.json: {telegram_token, telegram_chat}. Returns true when Telegram accepted the message. */
function fj_notify(string $text): bool {
    $n = fj_read(FJ_DATA . '/notify.json') ?? [];
    if (empty($n['telegram_token']) || empty($n['telegram_chat'])) return false;
    $url = 'https://api.telegram.org/bot' . $n['telegram_token'] . '/sendMessage';
    $body = http_build_query(['chat_id' => $n['telegram_chat'], 'text' => $text, 'disable_web_page_preview' => 'true']);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
        $res = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $body, 'timeout' => 5, 'ignore_errors' => true]]);
        $res = @file_get_contents($url, false, $ctx);
    }
    return is_string($res) && str_contains($res, '"ok":true');
}

/* ---------- Payment screenshots (private: data/proofs, shown only in the admin) ---------- */
function fj_save_proof(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 10 * 1024 * 1024) return '';
    $info = @getimagesize($file['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2] ?? 0] ?? null;
    if (!$ext) return '';
    $dir = FJ_DATA . '/proofs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], "$dir/$name") ? $name : '';
}

/** Simple per-IP limit for the public endpoints: at most $max hits per $seconds. */
function fj_rate_ok(string $bucket, int $max, int $seconds): bool {
    $f = FJ_DATA . '/limits/' . $bucket . '-' . substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'fj'), 0, 16) . '.json';
    $hits = array_values(array_filter(fj_read($f) ?? [], fn($t) => is_int($t) && $t > time() - $seconds));
    if (count($hits) >= $max) return false;
    $hits[] = time();
    fj_write($f, $hits);
    // one file per visitor address: now and then drop the ones idle for a day, so the folder cannot grow forever
    if (random_int(1, 200) === 1) {
        foreach (glob(FJ_DATA . '/limits/*.json') ?: [] as $old) if (filemtime($old) < time() - 86400) @unlink($old);
    }
    return true;
}

/* ---------- Backups ---------- */
/** Zip of everything in data/ except backups, limits and the admin password. Returns the zip path or ''. */
function fj_backup_zip(string $path): string {
    if (!class_exists('ZipArchive') || !is_dir(FJ_DATA)) return '';
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return '';
    $base = realpath(FJ_DATA);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($base) + 1));
        if (preg_match('#^(backups|limits)/|^admin\.json$|^SETUP-CODE\.txt$|^config-cache\.js$|^\.htaccess$|^login-fails\.json$|\.tmp$|^\.order\.lock$#', $rel)) continue;
        $zip->addFile($file->getPathname(), $rel);
    }
    $zip->close();
    return is_file($path) ? $path : '';
}

/** One backup a day in data/backups/data-YYYYMMDD.zip, keeping the last 14. Called when the admin opens. */
function fj_backup_daily(): void {
    $dir = FJ_DATA . '/backups';
    $path = "$dir/data-" . date('Ymd') . '.zip';
    if (is_file($path)) return;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (fj_backup_zip($path) === '') return;
    $all = glob("$dir/data-*.zip") ?: [];
    sort($all);
    foreach (array_slice($all, 0, max(0, count($all) - 14)) as $old) @unlink($old);
}
