<?php
// Admin form actions. Included by admin.php inside its POST block, after the login and CSRF checks. $action is set there.
declare(strict_types=1);
if (!defined('FJ_ADMIN')) { http_response_code(403); exit; }

$back = isset(ADM_MENU[$_POST['back'] ?? '']) ? $_POST['back'] : 'orders';

/* ---------- Orders ---------- */
if (in_array($action, ['status', 'note', 'pay_confirm', 'pay_reject', 'pay_reset', 'ship', 'deliver', 'return'], true)) {
    $id = (string)($_POST['id'] ?? '');
    $o = fj_order($id);
    if (!$o) go("tab=$back", 'Order not found.', true);
    $msg = 'Saved.';
    if ($action === 'status') {
        $st = (string)($_POST['status'] ?? '');
        if (!isset(FJ_STATUSES[$st])) go("tab=$back", 'Unknown status.', true);
        adm_set_status($o, $st);
        $msg = "Order $id marked " . FJ_STATUSES[$st] . '.';
    } elseif ($action === 'note') {
        $o['note'] = fj_clean($_POST['note'] ?? '', 500);
        $msg = "Note saved on $id.";
    } elseif (str_starts_with($action, 'pay_')) {
        if ($o['payment']['method'] !== 'instapay') go("tab=$back#$id", 'This order is cash on delivery.', true);
        $ref = fj_clean($_POST['ref'] ?? '', 60);
        if ($ref !== '') $o['payment']['ref'] = $ref;
        if ($action === 'pay_confirm') {
            $o['payment']['status'] = 'confirmed';
            $o['payment']['confirmed_at'] = date('c');
            if ($o['status'] === 'new') adm_set_status($o, 'confirmed');
            $msg = "Transfer for $id confirmed. The order is paid and ready to ship.";
        } elseif ($action === 'pay_reject') {
            $o['payment']['status'] = 'rejected';
            $msg = "Transfer for $id rejected. Message the customer, then wait for a new transfer or cancel the order.";
        } else {
            $o['payment']['status'] = 'awaiting';
            $msg = "Order $id is waiting for its transfer again.";
        }
    } elseif ($action === 'ship') {
        $courier = fj_clean($_POST['courier'] ?? '', 40);
        if ($courier === '') go("tab=shipping#$id", 'Pick the courier first.', true);
        $o['shipping']['courier'] = $courier;
        $o['shipping']['tracking'] = fj_clean($_POST['tracking'] ?? '', 60);
        adm_set_status($o, 'shipped');
        $msg = "Order $id shipped with $courier. Its jackets are out of stock now.";
    } elseif ($action === 'deliver') {
        adm_set_status($o, 'delivered');
        $msg = "Order $id delivered" . ($o['payment']['method'] === 'cod' ? ', cash collected.' : '.');
    } else {
        adm_set_status($o, 'returned');
        $msg = "Order $id returned. Its jackets are back in stock.";
    }
    fj_write(fj_order_path($id), $o);
    go("tab=$back#$id", $msg);
}

/* ---------- Inventory ---------- */
if ($action === 'save_stock') {
    $stock = fj_stock();
    foreach ($stock as $k => $row) {
        foreach (['qty', 'min'] as $f) {
            $v = trim((string)($_POST[$f][$k] ?? ''));
            if (!preg_match('/^\d{1,5}$/', $v)) go('tab=inventory', fj_sku_label($k) . ': write whole numbers only.', true);
            $stock[$k][$f] = (int)$v;
        }
    }
    fj_write(FJ_DATA . '/stock.json', $stock + (fj_read(FJ_DATA . '/stock.json') ?? [])); // keep rows of sizes taken off the site
    go('tab=inventory', 'Stock saved. The shop shows it on the next page load.');
}

if ($action === 'stock_bulk') {
    $site = fj_site();
    $stock = fj_stock($site);
    $add = [];
    foreach ((array)($_POST['b_p'] ?? []) as $i => $p) {
        $sz = (string)($_POST['b_s'][$i] ?? '');
        $q = trim((string)($_POST['b_q'][$i] ?? ''));
        if (!isset(FJ_PRODUCTS[$p]) || !in_array($sz, fj_size_names($site), true)) go('tab=inventory', 'Pick a jacket and a size on every row.', true);
        if (!preg_match('/^\d{1,4}$/', $q) || (int)$q < 1) go('tab=inventory', 'Quantities are whole numbers from 1.', true);
        $k = fj_sku((string)$p, $sz);
        $add[$k] = ($add[$k] ?? 0) + (int)$q;
    }
    if (!$add) go('tab=inventory', 'Add at least one row.', true);
    foreach ($add as $k => $q) $stock[$k] = ['qty' => ($stock[$k]['qty'] ?? 0) + $q, 'min' => $stock[$k]['min'] ?? 2];
    fj_write(FJ_DATA . '/stock.json', $stock + (fj_read(FJ_DATA . '/stock.json') ?? []));
    $done = implode(', ', array_map(fn($k, $q) => "$q × " . fj_sku_label($k), array_keys($add), $add));
    go('tab=inventory', "Added to stock: $done.");
}

/* ---------- Product, price and sizes ---------- */
if ($action === 'save_product') {
    $site = fj_site();
    foreach (['price' => 'Price', 'was' => 'Old price', 'offer_price' => 'Launch price', 'offer_orders' => 'Launch orders'] as $k => $label) {
        $v = trim((string)($_POST[$k] ?? '0'));
        if (!preg_match('/^\d{1,6}$/', $v === '' ? '0' : $v)) go('tab=products', "$label must be a whole number of EGP.", true);
        $site[$k] = (int)$v;
    }
    if ($site['price'] < 1) go('tab=products', 'The price must be at least 1 EGP.', true);
    if ($site['was'] && $site['was'] <= $site['price']) go('tab=products', 'The old price must be higher than the price, or 0 for no sale.', true);
    if ($site['offer_price'] >= $site['price']) go('tab=products', 'The launch price must be lower than the price, or 0 for no launch offer.', true);
    $sizes = [];
    foreach ((array)($_POST['size'] ?? []) as $i => $name) {
        $name = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$name) ?? '');
        if ($name === '') continue; // an empty row removes that size
        $chest = (int)($_POST['chest'][$i] ?? 0);
        $len = (int)($_POST['length'][$i] ?? 0);
        if ($chest < 40 || $chest > 250 || $len < 30 || $len > 150) go('tab=products', "Size $name: write the chest and length in cm.", true);
        if (in_array($name, array_column($sizes, 0), true)) go('tab=products', "Size $name is on the list twice.", true);
        $sizes[] = [$name, $chest, $len];
    }
    if (!$sizes) go('tab=products', 'Keep at least one size.', true);
    $site['sizes'] = $sizes;
    fj_save_site($site);
    go('tab=products', 'Saved. The shop uses the new price and sizes on the next page load.');
}

/* ---------- Settings ---------- */
if ($action === 'save_settings') {
    $site = fj_site();
    $wa = preg_replace('/\D/', '', (string)($_POST['whatsapp'] ?? '')) ?? '';
    if (str_starts_with($wa, '01')) $wa = '2' . $wa; // 01xxxxxxxxx -> 201xxxxxxxxx
    if (!preg_match('/^201[0125]\d{8}$/', $wa)) go('tab=settings', 'WhatsApp: write an Egyptian mobile number, e.g. 01012345678.', true);
    $ip = fj_clean($_POST['instapay'] ?? '', 80);
    $site['pay_cod'] = !empty($_POST['pay_cod']);
    $site['pay_instapay'] = !empty($_POST['pay_instapay']);
    if (!$site['pay_cod'] && !$site['pay_instapay']) go('tab=settings', 'Keep at least one way to pay.', true);
    if ($site['pay_instapay'] && $ip === '') go('tab=settings', 'Write your InstaPay address or number, or turn InstaPay off.', true);
    $other = trim((string)($_POST['ship_other'] ?? ''));
    if (!preg_match('/^\d{1,5}$/', $other)) go('tab=settings', 'Delivery fee for other governorates must be a whole number of EGP.', true);
    $ship = [];
    foreach (FJ_GOVS as $g) {
        $v = trim((string)($_POST['ship'][$g] ?? ''));
        if ($v === '') continue; // empty = the "other governorates" fee
        if (!preg_match('/^\d{1,5}$/', $v)) go('tab=settings', "$g: the delivery fee must be a whole number of EGP.", true);
        $ship[$g] = (int)$v;
    }
    $max = (int)($_POST['max_qty'] ?? 0);
    if ($max < 1 || $max > 100) go('tab=settings', 'Largest quantity in one order must be between 1 and 100.', true);
    $site['whatsapp'] = $wa;
    $site['instapay'] = $ip;
    $site['ship'] = $ship;
    $site['ship_other'] = (int)$other;
    $site['max_qty'] = $max;
    fj_save_site($site);
    go('tab=settings', 'Settings saved.');
}

if ($action === 'save_notify' || $action === 'notify_test') {
    $n = fj_read(FJ_DATA . '/notify.json') ?? [];
    if (!empty($_POST['off'])) {
        fj_write(FJ_DATA . '/notify.json', []);
        go('tab=settings', 'Telegram alerts turned off.');
    }
    $tok = trim((string)($_POST['telegram_token'] ?? ''));
    if ($tok !== '') {
        if (!preg_match('/^\d{5,15}:[A-Za-z0-9_-]{20,60}$/', $tok)) go('tab=settings', 'That bot token does not look right. Copy it again from @BotFather.', true);
        $n['telegram_token'] = $tok;
    }
    $chat = trim((string)($_POST['telegram_chat'] ?? ''));
    if (!preg_match('/^-?\d{4,20}$/', $chat)) go('tab=settings', 'The chat id is a number. Get it from @userinfobot.', true);
    $n['telegram_chat'] = $chat;
    if (empty($n['telegram_token'])) go('tab=settings', 'Paste the bot token first.', true);
    fj_write(FJ_DATA . '/notify.json', $n);
    if ($action === 'notify_test') {
        $sent = fj_notify('✅ Formula One Jacket alerts work. New orders will show up here.');
        go('tab=settings', $sent ? 'Test message sent. Check Telegram.' : 'Telegram did not accept it. Check the token and chat id, and press Start in your bot first.', !$sent);
    }
    go('tab=settings', 'Alerts saved. Press “Send test message” to check them.');
}

if ($action === 'change_password') {
    $auth = fj_read(FJ_DATA . '/admin.json') ?? [];
    $pw = (string)($_POST['pw'] ?? '');
    if (!password_verify((string)($_POST['current'] ?? ''), (string)($auth['hash'] ?? ''))) go('tab=settings', 'The current password is wrong.', true);
    if (strlen($pw) < 10) go('tab=settings', 'Use at least 10 characters for the new password.', true);
    if ($pw !== ($_POST['pw2'] ?? '')) go('tab=settings', 'The two new passwords don\'t match.', true);
    fj_write(FJ_DATA . '/admin.json', ['hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => $auth['created'] ?? date('c'), 'changed' => date('c')]);
    session_regenerate_id(true);
    go('tab=settings', 'Password changed.');
}

/* ---------- Discount codes ---------- */
if ($action === 'coupon_save') {
    $code = fj_coupon_code((string)($_POST['code'] ?? ''));
    if ($code === '' || strlen($code) > 30) go('tab=coupons', 'The code can use letters, numbers, - and _ (up to 30).', true);
    $all = fj_coupons();
    $orig = fj_coupon_code((string)($_POST['orig'] ?? ''));
    if ($code !== $orig && isset($all[$code])) go('tab=coupons', "The code $code already exists.", true);
    $type = ($_POST['type'] ?? '') === 'fixed' ? 'fixed' : 'percent';
    $value = (int)($_POST['value'] ?? 0);
    if ($value < 1 || ($type === 'percent' && $value > 100)) go('tab=coupons', $type === 'percent' ? 'Percent must be 1 to 100.' : 'Amount must be at least 1 EGP.', true);
    $expires = (string)($_POST['expires'] ?? '');
    if ($expires !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires)) $expires = '';
    $old = $all[$orig] ?? [];
    if ($orig !== '' && $orig !== $code) unset($all[$orig]);
    $all[$code] = [
        'type' => $type,
        'value' => $value,
        'min' => max(0, (int)($_POST['min'] ?? 0)),
        'expires' => $expires,
        'max_uses' => max(0, (int)($_POST['max_uses'] ?? 0)),
        'used' => (int)($old['used'] ?? 0),
        'active' => !empty($_POST['active']),
        'note' => fj_clean($_POST['note'] ?? '', 120),
        'created' => $old['created'] ?? date('Y-m-d'),
    ] + array_intersect_key($old, ['phone' => 1]); // a sign-up code stays tied to its phone
    fj_save_coupons($all);
    go('tab=coupons', "Code $code saved.");
}
if ($action === 'coupon_toggle' || $action === 'coupon_delete') {
    $all = fj_coupons();
    $code = fj_coupon_code((string)($_POST['code'] ?? ''));
    if (!isset($all[$code])) go('tab=coupons', 'Code not found.', true);
    if ($action === 'coupon_delete') { unset($all[$code]); $msg = "Code $code deleted."; }
    else { $all[$code]['active'] = empty($all[$code]['active']); $msg = "Code $code " . ($all[$code]['active'] ? 'turned on.' : 'turned off.'); }
    fj_save_coupons($all);
    go('tab=coupons', $msg);
}

/* ---------- Affiliates ---------- */
if (in_array($action, ['aff_approve', 'aff_reject', 'aff_delete'], true)) {
    $all = fj_read(FJ_DATA . '/affiliates.json') ?? [];
    $i = array_search((string)($_POST['id'] ?? ''), array_column($all, 'id'), true);
    if ($i === false) go('tab=affiliates', 'Application not found.', true);
    $name = $all[$i]['name'];
    if ($action === 'aff_delete') { array_splice($all, $i, 1); $msg = "Application from $name deleted."; }
    elseif ($action === 'aff_reject') { $all[$i]['status'] = 'rejected'; $msg = "Application from $name rejected."; }
    else {
        $code = fj_coupon_code((string)($_POST['code'] ?? ''));
        $disc = (int)($_POST['discount'] ?? 0);
        $comm = (int)($_POST['commission'] ?? 0);
        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) go('tab=affiliates', 'The code can use letters, numbers, - and _ (3 to 30).', true);
        if ($disc < 1 || $disc > 100 || $comm < 0 || $comm > 100) go('tab=affiliates', 'Discount is 1 to 100%, commission 0 to 100%.', true);
        $coupons = fj_coupons();
        if (isset($coupons[$code])) go('tab=affiliates', "The code $code already exists. Pick another one.", true);
        $coupons[$code] = ['type' => 'percent', 'value' => $disc, 'min' => 0, 'expires' => '', 'max_uses' => 0, 'used' => 0,
            'active' => true, 'note' => "Affiliate: $name", 'created' => date('Y-m-d')];
        fj_save_coupons($coupons);
        $all[$i] = ['status' => 'approved', 'code' => $code, 'commission' => $comm] + $all[$i];
        $msg = "$name approved. Code $code is live; send it to them on WhatsApp.";
    }
    fj_write(FJ_DATA . '/affiliates.json', array_values($all));
    go('tab=affiliates', $msg);
}

/* ---------- Reviews ---------- */
if ($action === 'review_save') {
    $name = fj_clean($_POST['name'] ?? '', 60);
    $text = fj_clean($_POST['text'] ?? '', 600);
    if ($name === '' || $text === '') go('tab=reviews', 'Write the customer name and what they said.', true);
    $all = fj_reviews();
    $all[] = [
        'id' => bin2hex(random_bytes(4)),
        'name' => $name,
        'city' => fj_clean($_POST['city'] ?? '', 40),
        'rating' => max(1, min(5, (int)($_POST['rating'] ?? 5))),
        'text' => $text,
        'size' => in_array($_POST['size'] ?? '', fj_size_names(fj_site()), true) ? $_POST['size'] : '',
        'order_id' => preg_match(FJ_ID, (string)($_POST['order_id'] ?? '')) ? $_POST['order_id'] : '',
        'source' => fj_clean($_POST['source'] ?? '', 40),
        'shown' => !empty($_POST['shown']),
        'created' => date('Y-m-d'),
    ];
    fj_save_reviews($all);
    go('tab=reviews', 'Review saved.');
}
if ($action === 'review_toggle' || $action === 'review_delete') {
    $id = (string)($_POST['id'] ?? '');
    $all = fj_reviews();
    $found = false;
    foreach ($all as $k => $r) {
        if (($r['id'] ?? '') !== $id) continue;
        $found = true;
        if ($action === 'review_delete') unset($all[$k]);
        else $all[$k]['shown'] = empty($r['shown']);
    }
    if (!$found) go('tab=reviews', 'Review not found.', true);
    fj_save_reviews($all);
    go('tab=reviews', $action === 'review_delete' ? 'Review deleted.' : 'Review updated.');
}
