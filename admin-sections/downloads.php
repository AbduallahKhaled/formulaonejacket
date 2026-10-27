<?php
// Admin GET pages outside the normal layout: payment screenshots, CSV export, packing slips, data backup.
// Included by admin.php after login only.
declare(strict_types=1);
if (!defined('FJ_ADMIN')) { http_response_code(403); exit; }

/* ---------- Payment screenshot (stored privately in data/proofs) ---------- */
if (isset($_GET['proof'])) {
    $name = (string)$_GET['proof'];
    $f = FJ_DATA . '/proofs/' . $name;
    if (!preg_match('/^\d{8}-[a-f0-9]{16}\.(jpg|png|webp|gif)$/', $name) || !is_file($f)) { http_response_code(404); exit('Not found'); }
    header('Content-Type: ' . ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'][pathinfo($name, PATHINFO_EXTENSION)]);
    header('Content-Length: ' . filesize($f));
    // a buyer made this file: even opened on its own it can only ever be shown as a picture
    header("Content-Security-Policy: default-src 'none'; img-src 'self'; sandbox");
    header('Content-Disposition: inline; filename="' . $name . '"');
    readfile($f);
    exit;
}

/* ---------- Orders as CSV (opens in Excel; UTF-8 with BOM so Arabic names show) ---------- */
if (($_GET['export'] ?? '') === 'orders') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="f1jacket-orders-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Order', 'Date', 'Status', 'Payment', 'Pay status', 'Reference', 'Customer', 'Phone', 'Governorate', 'Address', 'Jackets', 'Qty', 'Jackets (EGP)', 'Discount code', 'Discount (EGP)', 'Delivery (EGP)', 'Total (EGP)', 'Courier', 'Tracking', 'Note']);
    // Buyers type the names and addresses: a cell starting with = + - @ would run as a formula in Excel, so it gets a leading '
    $cell = fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    foreach (fj_orders() as $o) {
        $c = $o['customer'];
        $p = $o['payment'];
        fputcsv($out, array_map($cell, [
            $o['id'], date('Y-m-d H:i', strtotime($o['created'])), FJ_STATUSES[$o['status']] ?? $o['status'],
            $p['method'] === 'cod' ? 'Cash on delivery' : 'InstaPay', FJ_PAY[$p['status']] ?? $p['status'], $p['ref'] ?? '',
            $c['name'], $c['phone'], $c['gov'], $c['address'], fj_items_text($o), fj_pieces($o), (int)$o['unit_price'] * fj_pieces($o),
            $o['coupon'], (int)$o['discount'], (int)$o['delivery_fee'], (int)$o['total'],
            $o['shipping']['courier'] ?? '', $o['shipping']['tracking'] ?? '', $o['note'] ?? '',
        ]));
    }
    fclose($out);
    exit;
}

/* ---------- All data as a zip ---------- */
if (isset($_GET['backup'])) {
    $tmp = FJ_DATA . '/backups/download-' . bin2hex(random_bytes(4)) . '.zip';
    if (!is_dir(dirname($tmp))) mkdir(dirname($tmp), 0755, true);
    if (fj_backup_zip($tmp) === '') { http_response_code(500); exit('This server cannot make zip files (the PHP zip extension is off). Ask the host to turn it on.'); }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="f1jacket-data-' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

/* ---------- Packing slip: one printable page per order ---------- */
if (isset($_GET['slip'])) {
    $o = fj_order((string)$_GET['slip']);
    if (!$o) { http_response_code(404); exit('Order not found'); }
    $c = $o['customer'];
    $p = $o['payment'];
    $site = fj_site();
    $due = $p['method'] === 'cod' && $p['status'] === 'cod' ? (int)$o['total'] : 0;
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Packing slip <?= h($o['id']) ?></title>
<style>
*{box-sizing:border-box}body{font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;color:#111;background:#fff;margin:0 auto;padding:28px;max-width:760px}
h1{font-size:22px;margin:0;font-style:italic;letter-spacing:.02em}.top{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #E10600;padding-bottom:14px;margin-bottom:18px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}.box{border:1px solid #bbb;padding:12px}.box b{display:block;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#555;margin-bottom:4px}
table{width:100%;border-collapse:collapse;margin-bottom:14px}th,td{text-align:left;padding:8px;border-bottom:1px solid #ddd}th{font-size:12px;text-transform:uppercase;color:#555}td.n,th.n{text-align:right}
.tot td{border:0;padding:4px 8px}.tot .big td{font-weight:700;font-size:17px;border-top:2px solid #111}.check{display:inline-block;width:14px;height:14px;border:1.5px solid #111;vertical-align:-2px}
.cod{border:2px solid #111;padding:12px;font-size:18px;font-weight:700;text-align:center}
.print{position:fixed;top:14px;right:14px;padding:10px 18px;border:0;background:#E10600;color:#fff;font-weight:600;cursor:pointer}@media print{.print{display:none}body{padding:0}}
@media(max-width:560px){.grid{grid-template-columns:1fr}}
</style></head><body>
<button class="print" onclick="print()">Print</button>
<div class="top">
  <div><h1>FORMULA ONE JACKET</h1><div>Packing slip</div></div>
  <div style="text-align:right"><b style="font-size:18px"><?= h($o['id']) ?></b><br><?= h(date('j M Y', strtotime($o['created']))) ?><br>WhatsApp <?= h('0' . substr((string)$site['whatsapp'], 2)) ?></div>
</div>
<div class="grid">
  <div class="box"><b>Deliver to</b><?= h($c['name']) ?><br><?= h($c['phone']) ?><br><?= h($c['gov']) ?>, <?= h($c['address']) ?></div>
  <div class="box"><b>Payment</b><?= $p['method'] === 'cod' ? 'Cash on delivery' : 'InstaPay' ?>: <?= h(FJ_PAY[$p['status']] ?? $p['status']) ?><?= !empty($p['ref']) ? '<br>Ref ' . h($p['ref']) : '' ?><?php if (!empty($o['shipping']['courier'])): ?><b style="margin-top:10px">Courier</b><?= h($o['shipping']['courier']) ?> <?= h($o['shipping']['tracking'] ?? '') ?><?php endif; ?></div>
</div>
<table>
  <thead><tr><th>Packed</th><th>Item</th><th>Size</th><th class="n">Qty</th><th class="n">Price</th></tr></thead>
  <tbody><?php foreach (fj_items($o) as $i): ?><tr><td><span class="check"></span></td><td><?= h(FJ_PRODUCTS[$i['product']] ?? $i['product']) ?> racing jacket</td><td><b><?= h($i['size']) ?></b></td><td class="n"><?= (int)$i['qty'] ?></td><td class="n"><?= number_format((int)$o['unit_price'] * (int)$i['qty']) ?></td></tr><?php endforeach; ?></tbody>
</table>
<table class="tot">
  <?php if (!empty($o['discount'])): ?><tr><td class="n">Discount <?= h($o['coupon']) ?></td><td class="n" style="width:120px">−<?= number_format((int)$o['discount']) ?></td></tr><?php endif; ?>
  <tr><td class="n">Delivery</td><td class="n" style="width:120px"><?= number_format((int)$o['delivery_fee']) ?></td></tr>
  <tr class="big"><td class="n">Total EGP</td><td class="n"><?= number_format((int)$o['total']) ?></td></tr>
</table>
<div class="cod"><?= $due ? 'COLLECT ' . number_format($due) . ' EGP ON DELIVERY' : ($p['status'] === 'confirmed' || $p['status'] === 'collected' ? 'PAID. COLLECT NOTHING' : 'INSTAPAY NOT CONFIRMED YET') ?></div>
</body></html>
<?php
    exit;
}
