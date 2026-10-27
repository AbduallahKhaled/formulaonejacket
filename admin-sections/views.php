<?php
// Admin views, one block per tab. Included by admin.php after login.
// Uses $tab, $orders (newest first), $site and $month from admin.php.
declare(strict_types=1);
if (!defined('FJ_ADMIN')) { http_response_code(403); exit; }

/* ---------- Dashboard ---------- */
if ($tab === 'dashboard'):
    $today = date('Y-m-d');
    $new = array_values(array_filter($orders, fn($o) => $o['status'] === 'new'));
    $payOpen = array_values(array_filter($orders, 'adm_pay_open'));
    $toShip = array_values(array_filter($orders, fn($o) => $o['status'] === 'confirmed'));
    $onWay = count(array_filter($orders, fn($o) => $o['status'] === 'shipped'));
    $live = fn($o) => !in_array($o['status'], ['cancelled', 'returned'], true);
    $ordersMonth = count(array_filter($orders, fn($o) => str_starts_with($o['created'], $month) && $live($o)));
    $ordersToday = count(array_filter($orders, fn($o) => str_starts_with($o['created'], $today)));
    $revMonth = array_sum(array_map(fn($o) => (int)$o['total'], array_filter($orders, fn($o) => str_starts_with($o['created'], $month) && adm_is_paid($o))));
    $codOut = array_sum(array_map(fn($o) => (int)$o['total'], array_filter($orders, fn($o) => $o['status'] === 'shipped' && $o['payment']['status'] === 'cod')));
    $stock = fj_stock($site);
    $avail = fj_available($site, $orders);
    $low = array_filter($stock, fn($s, $k) => $avail[$k] <= $s['min'], ARRAY_FILTER_USE_BOTH);
?>
  <div class="head"><h1 class="h1">Dashboard</h1><span class="muted"><?= h(date('l j F Y')) ?></span></div>
  <div class="kpis">
    <a href="?tab=orders&amp;status=new"><span>New orders to confirm</span><b><?= count($new) ?></b><em><?= $ordersToday ?> placed today</em></a>
    <a href="?tab=payments"><span>InstaPay to check</span><b><?= count($payOpen) ?></b><em><?= count(array_filter($payOpen, fn($o) => $o['payment']['status'] === 'sent')) ?> with a screenshot</em></a>
    <a href="?tab=shipping"><span>Ready to ship</span><b><?= count($toShip) ?></b><em><?= $onWay ?> on the way</em></a>
    <a href="?tab=reports"><span>Money in this month</span><b><?= adm_money($revMonth) ?></b><em><?= $ordersMonth ?> orders this month</em></a>
    <a href="?tab=shipping"><span>Cash with couriers</span><b><?= adm_money($codOut) ?></b><em>cash orders on the way</em></a>
    <a href="?tab=inventory"><span>Low stock sizes</span><b><?= count($low) ?></b><em><?= array_sum($avail) ?> jackets free to sell</em></a>
  </div>

  <div class="grid2">
    <div class="panel">
      <div class="head"><h2>New orders</h2><a class="btn btn-ghost btn-sm" href="?tab=orders&amp;status=new">All new</a></div>
      <?php if (!$new): ?><p class="muted">Nothing to confirm. New orders from the site show here until you confirm them.</p><?php else: ?>
      <div class="scroll"><table class="tbl">
        <thead><tr><th>Order</th><th>Customer</th><th>Jackets</th><th>Total</th><th>Payment</th><th><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        <?php foreach (array_slice($new, 0, 8) as $o): ?>
          <tr>
            <td><?= adm_order_link($o['id']) ?><br><span class="muted small"><?= h(date('j M, H:i', strtotime($o['created']))) ?></span></td>
            <td dir="auto"><?= h($o['customer']['name']) ?><br><span class="muted small"><?= h($o['customer']['gov']) ?></span></td>
            <td><?= h(adm_items_text($o)) ?></td>
            <td class="nowrap"><?= adm_money((int)$o['total']) ?></td>
            <td><?= adm_pay_pill($o) ?></td>
            <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= h(adm_wa($o)) ?>" target="_blank" aria-label="WhatsApp <?= h($o['customer']['name']) ?>, order <?= h($o['id']) ?>">WhatsApp</a>
              <form method="post"><?= adm_form('status', ['id' => $o['id'], 'status' => 'confirmed', 'back' => 'dashboard']) ?><button class="btn btn-sm" aria-label="Confirm order <?= h($o['id']) ?>">Confirm</button></form></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="head"><h2>Ready to ship</h2><a class="btn btn-ghost btn-sm" href="?tab=shipping">Shipping</a></div>
      <?php if (!$toShip): ?><p class="muted">No confirmed orders waiting to ship.</p><?php else: ?>
      <ul class="plain">
        <?php foreach (array_slice($toShip, 0, 6) as $o): ?>
          <li><?= adm_order_link($o['id']) ?> · <bdi><?= h($o['customer']['name']) ?></bdi> · <span class="muted"><?= h($o['customer']['gov']) ?></span><br><span class="muted small"><?= h(adm_items_text($o)) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <h3 class="h3">Stock</h3>
      <div class="sizes">
        <?php foreach ($stock as $k => $s): ?><div class="<?= $avail[$k] <= 0 ? 'out' : ($avail[$k] <= $s['min'] ? 'low' : '') ?>"><b><?= h(fj_sku_label($k)) ?></b><span><?= (int)$avail[$k] ?></span></div><?php endforeach; ?>
      </div>
      <p class="muted small">Free to sell per size (in stock minus open orders).</p>
    </div>
  </div>

  <div class="panel">
    <div class="head"><h2>Latest orders</h2><a class="btn btn-ghost btn-sm" href="?tab=orders">All orders</a></div>
    <?php if (!$orders): ?><p class="muted">No orders yet. They show here as soon as someone orders on the site.</p><?php else: ?>
    <div class="scroll"><table class="tbl">
      <thead><tr><th>Order</th><th>Placed</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
      <tbody>
      <?php foreach (array_slice($orders, 0, 8) as $o): ?>
        <tr>
          <td><?= adm_order_link($o['id']) ?></td>
          <td class="nowrap"><?= h(date('j M, H:i', strtotime($o['created']))) ?></td>
          <td dir="auto"><?= h($o['customer']['name']) ?></td>
          <td class="muted small"><?= h(adm_items_text($o)) ?></td>
          <td class="nowrap"><?= adm_money((int)$o['total']) ?></td>
          <td><?= adm_st_pill($o) ?></td>
          <td><?= adm_pay_pill($o) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>

<?php
/* ---------- Orders ---------- */
elseif ($tab === 'orders'):
    $filter = isset(FJ_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
    $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
    $counts = [];
    foreach ($orders as $o) $counts[$o['status']] = ($counts[$o['status']] ?? 0) + 1;
    $list = array_values(array_filter($orders, function ($o) use ($filter, $q) {
        if ($filter !== '' && $o['status'] !== $filter) return false;
        if ($q === '') return true;
        $c = $o['customer'];
        return str_contains(mb_strtolower($o['id'] . ' ' . $c['name'] . ' ' . $c['phone'] . ' ' . fj_eg_phone($c['phone']) . ' ' . $c['gov']), $q);
    }));
    $keepQ = $q !== '' ? '&q=' . urlencode($q) : '';
?>
  <div class="panel">
    <div class="head"><h1 class="h1">Orders</h1><span class="muted"><?= count($list) ?> of <?= count($orders) ?> · <a href="?export=orders">Download all as Excel (CSV)</a></span></div>
    <div class="filters">
      <a class="chip <?= $filter === '' ? 'on' : '' ?>" href="?tab=orders<?= h($keepQ) ?>">All <?= count($orders) ?></a>
      <?php foreach (FJ_STATUSES as $k => $v): ?>
        <a class="chip <?= $filter === $k ? 'on' : '' ?>" href="?tab=orders&amp;status=<?= $k ?><?= h($keepQ) ?>"><?= h($v) ?> <?= (int)($counts[$k] ?? 0) ?></a>
      <?php endforeach; ?>
      <form method="get" role="search" class="search"><input type="hidden" name="tab" value="orders"><?php if ($filter): ?><input type="hidden" name="status" value="<?= h($filter) ?>"><?php endif; ?><input class="input" type="search" name="q" value="<?= h($q) ?>" placeholder="Search name, phone, order no. or governorate" aria-label="Search orders"></form>
    </div>
    <?php if (!$orders): ?><p class="muted">No orders yet. They show here as soon as someone orders on the site.</p><?php elseif (!$list): ?><p class="muted">No orders match. <a href="?tab=orders">Show all orders</a></p><?php endif; ?>
    <?php foreach ($list as $o): $c = $o['customer']; $p = $o['payment']; ?>
      <details class="order" id="<?= h($o['id']) ?>" <?= $o['status'] === 'new' || count($list) === 1 ? 'open' : '' ?>>
        <summary>
          <?= adm_st_pill($o) ?>
          <b class="ltr"><?= h($o['id']) ?></b>
          <bdi><?= h($c['name']) ?></bdi>
          <span class="muted"><?= h(adm_items_text($o)) ?> · <?= h($c['gov']) ?></span>
          <b class="amt"><?= number_format((int)$o['total']) ?> EGP</b>
          <?= adm_pay_pill($o) ?>
        </summary>
        <div class="grid2 inner">
          <div>
            <p><span class="muted">Placed</span> <?= h(date('j M Y, H:i', strtotime($o['created']))) ?></p>
            <p><span class="muted">Phone</span> <a class="ltr" href="<?= h(adm_wa($o, '')) ?>" target="_blank"><?= h($c['phone']) ?></a> · <a href="tel:<?= h($c['phone']) ?>">Call</a></p>
            <p><span class="muted">Deliver to</span> <?= h($c['gov']) ?>, <bdi><?= h($c['address']) ?></bdi></p>
            <p><span class="muted">Jackets</span> <b><?= h(fj_items_text($o)) ?></b> · <?= (int)$o['unit_price'] ?> EGP each</p>
            <p><span class="muted">Delivery</span> <?= (int)$o['delivery_fee'] ?> EGP</p>
            <?php if (!empty($o['coupon'])): ?><p><span class="muted">Discount code</span> <?= h($o['coupon']) ?> (−<?= (int)$o['discount'] ?>)</p><?php endif; ?>
            <p><span class="muted">Total</span> <b><?= adm_money((int)$o['total']) ?></b></p>
            <p><span class="muted">Site language</span> <?= $o['lang'] === 'ar' ? 'Arabic' : 'English' ?></p>
            <?php if (!empty($o['shipping']['courier'])): ?><p><span class="muted">Shipped with</span> <?= h($o['shipping']['courier']) ?><?= !empty($o['shipping']['tracking']) ? ' · <span class="ltr">' . h($o['shipping']['tracking']) . '</span>' : '' ?></p><?php endif; ?>
            <form method="post" class="status-form"><?= adm_form('status', ['id' => $o['id']]) ?>
              <select class="input" name="status" aria-label="Order status"><?php foreach (FJ_STATUSES as $k => $v): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
              <button class="btn btn-sm" aria-label="Update status, order <?= h($o['id']) ?>">Update</button>
            </form>
            <div class="actions" style="margin-top:10px">
              <a class="btn btn-ghost btn-sm" href="<?= h(adm_wa($o)) ?>" target="_blank">Confirm on WhatsApp</a>
              <?php if (!empty($o['shipping']['courier'])): ?><a class="btn btn-ghost btn-sm" href="<?= h(adm_wa($o, 'shipped')) ?>" target="_blank" aria-label="Send tracking <?= h($o['id']) ?>">Send tracking</a><?php endif; ?>
              <a class="btn btn-ghost btn-sm" href="?slip=<?= h($o['id']) ?>" target="_blank" aria-label="Packing slip <?= h($o['id']) ?>">Packing slip</a>
            </div>
          </div>
          <div>
            <div class="paybox">
              <p style="margin:0 0 8px"><span class="muted"><?= $p['method'] === 'cod' ? 'Cash on delivery' : 'InstaPay' ?></span> <?= adm_pay_pill($o) ?><?= !empty($p['ref']) ? ' <span class="small">ref ' . h($p['ref']) . '</span>' : '' ?></p>
              <?php if (!empty($p['proof'])): ?><p style="margin:0 0 8px"><a href="?proof=<?= h($p['proof']) ?>" target="_blank"><img src="?proof=<?= h($p['proof']) ?>" alt="InstaPay screenshot for order <?= h($o['id']) ?> (opens in a new tab)" class="proof-thumb"></a></p><?php endif; ?>
              <?php if (adm_pay_open($o)): ?>
                <form method="post" class="payform"><?= adm_form('pay_confirm', ['id' => $o['id'], 'back' => 'orders']) ?>
                  <input class="input" name="ref" value="<?= h($p['ref'] ?? '') ?>" placeholder="Reference (optional)" aria-label="Transfer reference">
                  <button class="btn btn-sm" aria-label="Confirm transfer <?= h($o['id']) ?>">Confirm transfer</button>
                  <button class="btn btn-ghost btn-sm danger" name="action" value="pay_reject" aria-label="Reject transfer <?= h($o['id']) ?>" onclick="return confirm('Reject this transfer? The order stays unpaid.')">Reject</button>
                </form>
              <?php elseif ($p['status'] === 'rejected'): ?>
                <form method="post"><?= adm_form('pay_reset', ['id' => $o['id'], 'back' => 'orders']) ?><button class="btn btn-ghost btn-sm" aria-label="Wait for a new transfer, order <?= h($o['id']) ?>">Wait for a new transfer</button></form>
              <?php elseif ($p['method'] === 'cod'): ?><p class="muted small" style="margin:0">Marking the order delivered marks the cash collected.</p><?php endif; ?>
            </div>
            <form method="post" class="settings" style="max-width:none"><?= adm_form('note', ['id' => $o['id']]) ?>
              <label for="n-<?= h($o['id']) ?>">Your note (only you see it)</label>
              <textarea class="input" id="n-<?= h($o['id']) ?>" name="note" rows="2" maxlength="500"><?= h($o['note'] ?? '') ?></textarea>
              <button class="btn btn-ghost btn-sm" aria-label="Save note, order <?= h($o['id']) ?>">Save note</button>
            </form>
            <p class="muted small" style="margin-top:12px">History: <?= h(implode(' → ', array_map(fn($x) => (FJ_STATUSES[$x['status']] ?? $x['status']) . ' ' . date('j M H:i', strtotime($x['at'])), $o['history'] ?? []))) ?></p>
          </div>
        </div>
      </details>
    <?php endforeach; ?>
  </div>

<?php
/* ---------- InstaPay transfers ---------- */
elseif ($tab === 'payments'):
    $f = $_GET['pay'] ?? 'open';
    $ipStatuses = ['awaiting', 'sent', 'confirmed', 'rejected'];
    if ($f !== 'open' && $f !== 'all' && !in_array($f, $ipStatuses, true)) $f = 'open';
    $ip = array_values(array_filter($orders, fn($o) => $o['payment']['method'] === 'instapay' && ($o['status'] !== 'cancelled' || $o['payment']['status'] === 'confirmed')));
    $counts = ['open' => count(array_filter($ip, 'adm_pay_open')), 'all' => count($ip)];
    foreach ($ip as $o) $counts[$o['payment']['status']] = ($counts[$o['payment']['status']] ?? 0) + 1;
    $list = array_values(array_filter($ip, fn($o) => $f === 'all' || ($f === 'open' ? adm_pay_open($o) : $o['payment']['status'] === $f)));
    $waiting = array_sum(array_map(fn($o) => (int)$o['total'], array_filter($ip, 'adm_pay_open')));
?>
  <div class="head"><h1 class="h1">InstaPay transfers</h1><span class="muted"><?= adm_money($waiting) ?> waiting to be checked</span></div>
  <div class="panel">
    <p class="muted" style="margin-top:0">Customers send the money to <b class="ltr"><?= h($site['instapay']) ?></b> and upload the screenshot at checkout. Check the money arrived in your InstaPay app, then press Confirm: the order moves to Confirmed, ready to ship.</p>
    <div class="filters">
      <a class="chip <?= $f === 'open' ? 'on' : '' ?>" href="?tab=payments">To check <?= $counts['open'] ?></a>
      <?php foreach ($ipStatuses as $k): ?><a class="chip <?= $f === $k ? 'on' : '' ?>" href="?tab=payments&amp;pay=<?= $k ?>"><?= h(FJ_PAY[$k]) ?> <?= (int)($counts[$k] ?? 0) ?></a><?php endforeach; ?>
      <a class="chip <?= $f === 'all' ? 'on' : '' ?>" href="?tab=payments&amp;pay=all">All <?= $counts['all'] ?></a>
    </div>
    <?php if (!$list): ?><p class="muted">No transfers here.</p><?php else: ?>
    <div class="scroll"><table class="tbl">
      <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th><th>Screenshot</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($list as $o): $p = $o['payment']; $c = $o['customer']; ?>
        <tr id="<?= h($o['id']) ?>">
          <td><?= adm_order_link($o['id']) ?><br><span class="muted small"><?= h(date('j M Y, H:i', strtotime($o['created']))) ?></span></td>
          <td dir="auto"><?= h($c['name']) ?><br><a class="ltr small" href="<?= h(adm_wa($o, '')) ?>" target="_blank"><?= h($c['phone']) ?></a></td>
          <td class="nowrap"><b><?= adm_money((int)$o['total']) ?></b></td>
          <td><?= adm_pay_pill($o) ?><?php if (!empty($p['confirmed_at'])): ?><br><span class="muted small">confirmed <?= h(date('j M, H:i', strtotime($p['confirmed_at']))) ?></span><?php endif; ?><?= !empty($p['ref']) ? '<br><span class="small">ref ' . h($p['ref']) . '</span>' : '' ?></td>
          <td><?php if (!empty($p['proof'])): ?><a href="?proof=<?= h($p['proof']) ?>" target="_blank"><img src="?proof=<?= h($p['proof']) ?>" alt="InstaPay screenshot for order <?= h($o['id']) ?> (opens in a new tab)" class="proof-thumb" style="max-width:110px"></a><?php else: ?><span class="muted small">none, ask on WhatsApp</span><?php endif; ?></td>
          <td>
            <?php if (adm_pay_open($o)): ?>
              <form method="post" class="payform"><?= adm_form('pay_confirm', ['id' => $o['id'], 'back' => 'payments']) ?>
                <input class="input" name="ref" value="<?= h($p['ref'] ?? '') ?>" placeholder="Reference (optional)" aria-label="Transfer reference">
                <button class="btn btn-sm" aria-label="Confirm transfer <?= h($o['id']) ?>">Confirm</button>
                <button class="btn btn-ghost btn-sm danger" name="action" value="pay_reject" aria-label="Reject transfer <?= h($o['id']) ?>" onclick="return confirm('Reject this transfer? The order stays unpaid.')">Reject</button>
              </form>
            <?php elseif ($p['status'] === 'rejected'): ?>
              <form method="post"><?= adm_form('pay_reset', ['id' => $o['id'], 'back' => 'payments']) ?><button class="btn btn-ghost btn-sm" aria-label="Wait for a new transfer, order <?= h($o['id']) ?>">Wait for a new transfer</button></form>
            <?php else: ?><span class="muted small">Done</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>

<?php
/* ---------- Shipping ---------- */
elseif ($tab === 'shipping'):
    $ready = array_values(array_filter($orders, fn($o) => $o['status'] === 'confirmed'));
    $onWay = array_values(array_filter($orders, fn($o) => $o['status'] === 'shipped'));
    $done = array_slice(array_values(array_filter($orders, fn($o) => in_array($o['status'], ['delivered', 'returned'], true))), 0, 20);
?>
  <div class="head"><h1 class="h1">Shipping</h1><span class="muted">Delivery fees are in <a href="?tab=settings">Settings</a></span></div>
  <div class="panel">
    <h2>Ready to ship <span class="muted">(<?= count($ready) ?>)</span></h2>
    <?php if (!$ready): ?><p class="muted">No confirmed orders waiting. Confirm new orders on the Orders tab first.</p><?php endif; ?>
    <?php foreach ($ready as $o): $c = $o['customer']; ?>
      <div class="shiprow" id="<?= h($o['id']) ?>">
        <div>
          <b><?= adm_order_link($o['id']) ?></b> <?= adm_pay_pill($o) ?><br>
          <bdi><?= h($c['name']) ?></bdi> · <a class="ltr" href="<?= h(adm_wa($o, '')) ?>" target="_blank"><?= h($c['phone']) ?></a><br>
          <span class="muted"><?= h($c['gov']) ?>, <bdi><?= h($c['address']) ?></bdi></span><br>
          <span class="small"><?= h(adm_items_text($o)) ?> · <?= $o['payment']['method'] === 'cod' && $o['payment']['status'] === 'cod' ? 'collect <b>' . number_format((int)$o['total']) . ' EGP</b>' : 'paid' ?></span>
        </div>
        <form method="post" class="shipform"><?= adm_form('ship', ['id' => $o['id'], 'back' => 'shipping']) ?>
          <select class="input" name="courier" required aria-label="Courier"><option value="">Courier…</option><?php foreach (ADM_COURIERS as $cr): ?><option><?= h($cr) ?></option><?php endforeach; ?></select>
          <input class="input ltr" name="tracking" placeholder="Tracking number" aria-label="Tracking number">
          <button class="btn btn-sm" aria-label="Mark shipped <?= h($o['id']) ?>">Mark shipped</button>
          <a class="btn btn-ghost btn-sm" href="?slip=<?= h($o['id']) ?>" target="_blank" aria-label="Packing slip <?= h($o['id']) ?>">Packing slip</a>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="panel">
    <h2>On the way <span class="muted">(<?= count($onWay) ?>)</span></h2>
    <?php if (!$onWay): ?><p class="muted">Nothing on the way.</p><?php else: ?>
    <div class="scroll"><table class="tbl">
      <thead><tr><th>Order</th><th>Customer</th><th>Governorate</th><th>Courier</th><th>Tracking</th><th>Shipped</th><th>Collect</th><th><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($onWay as $o): $s = $o['shipping'] ?? []; ?>
        <tr id="<?= h($o['id']) ?>">
          <td><?= adm_order_link($o['id']) ?></td>
          <td dir="auto"><?= h($o['customer']['name']) ?><br><span class="muted small ltr"><?= h($o['customer']['phone']) ?></span></td>
          <td><?= h($o['customer']['gov']) ?></td>
          <td><?= h($s['courier'] ?? '—') ?></td>
          <td class="ltr"><?= h(($s['tracking'] ?? '') ?: '—') ?></td>
          <td class="nowrap"><?= !empty($s['shipped_at']) ? h(date('j M', strtotime($s['shipped_at']))) : '—' ?></td>
          <td class="nowrap"><?= $o['payment']['status'] === 'cod' ? adm_money((int)$o['total']) : '<span class="muted">paid</span>' ?></td>
          <td class="actions">
            <form method="post"><?= adm_form('deliver', ['id' => $o['id'], 'back' => 'shipping']) ?><button class="btn btn-sm" aria-label="Delivered <?= h($o['id']) ?>">Delivered</button></form>
            <form method="post"><?= adm_form('return', ['id' => $o['id'], 'back' => 'shipping']) ?><button class="btn btn-ghost btn-sm danger" aria-label="Returned <?= h($o['id']) ?>" onclick="return confirm('Mark this order returned? The jackets go back into stock.')">Returned</button></form>
            <a class="btn btn-ghost btn-sm" href="<?= h(adm_wa($o, 'shipped')) ?>" target="_blank" aria-label="Send tracking <?= h($o['id']) ?>">Send tracking</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>

  <?php if ($done): ?>
  <div class="panel">
    <h2>Recently delivered or returned</h2>
    <div class="scroll"><table class="tbl">
      <thead><tr><th>Order</th><th>Customer</th><th>Courier</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach ($done as $o): $s = $o['shipping'] ?? []; $last = end($o['history']); ?>
        <tr><td><?= adm_order_link($o['id']) ?></td><td dir="auto"><?= h($o['customer']['name']) ?></td><td><?= h($s['courier'] ?? '—') ?></td><td><?= adm_st_pill($o) ?></td><td><?= h(date('j M Y', strtotime($last['at']))) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <?php endif; ?>

<?php
/* ---------- Inventory ---------- */
elseif ($tab === 'inventory'):
    $stock = fj_stock($site);
    $held = fj_reserved($orders);
?>
  <div class="head"><h1 class="h1">Inventory</h1><span class="muted"><?= array_sum(array_column($stock, 'qty')) ?> jackets in stock</span>
    <button type="button" class="btn btn-sm" id="bulkOpen" aria-expanded="false" aria-controls="bulkPanel">+ Add stock in bulk</button></div>
  <div class="panel" id="bulkPanel" hidden>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><h2 style="margin:0">Add stock in bulk</h2><button type="button" class="btn btn-ghost btn-sm" id="bulkClose" aria-label="Close add stock in bulk">✕ Close</button></div>
    <p class="muted" style="margin-top:0">One row per jacket and size you received. The quantity is added to what is already on the shelf. The same jacket and size twice is added up.</p>
    <form method="post" id="bulkForm"><?= adm_form('stock_bulk') ?>
      <div class="scroll"><table class="tbl">
        <thead><tr><th>Jacket (colour)</th><th>Size</th><th>Quantity to add</th><th><span class="sr-only">Remove</span></th></tr></thead>
        <tbody id="bulkRows">
          <tr>
            <td><select class="input" name="b_p[]" aria-label="Jacket"><?php foreach (FJ_PRODUCTS as $id => $label): ?><option value="<?= h($id) ?>"><?= h($label) ?></option><?php endforeach; ?></select></td>
            <td><select class="input" name="b_s[]" aria-label="Size"><?php foreach (fj_size_names($site) as $sz): ?><option><?= h($sz) ?></option><?php endforeach; ?></select></td>
            <td><input class="input num" name="b_q[]" inputmode="numeric" placeholder="0" aria-label="Quantity to add" required></td>
            <td><button type="button" class="btn btn-ghost btn-sm danger" data-del aria-label="Remove row">✕</button></td>
          </tr>
        </tbody>
      </table></div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <button type="button" class="btn btn-ghost btn-sm" id="bulkAdd">+ Add row</button>
        <button class="btn">Add to stock</button>
      </div>
    </form>
  </div>
  <script>
  (() => {
    const panel = document.getElementById('bulkPanel'), open = document.getElementById('bulkOpen'), rows = document.getElementById('bulkRows');
    open.addEventListener('click', () => { panel.hidden = !panel.hidden; open.setAttribute('aria-expanded', !panel.hidden); if (!panel.hidden) rows.querySelector('select').focus(); });
    document.getElementById('bulkClose').addEventListener('click', () => { panel.hidden = true; open.setAttribute('aria-expanded', false); open.focus(); });
    // a new row copies the last one's jacket and size, with an empty quantity
    document.getElementById('bulkAdd').addEventListener('click', () => {
      const last = rows.lastElementChild, row = last.cloneNode(true);
      row.querySelectorAll('select').forEach((s, i) => s.value = last.querySelectorAll('select')[i].value);
      row.querySelector('input').value = '';
      rows.append(row); row.querySelector('input').focus();
    });
    rows.addEventListener('click', e => { if (e.target.closest('[data-del]') && rows.children.length > 1) e.target.closest('tr').remove(); });
  })();
  </script>
  <div class="panel">
    <p class="muted" style="margin-top:0">Jackets on your shelf per jacket and size. New and confirmed orders hold their jackets, so the shop only sells what is left. Marking an order shipped takes its jackets out of stock; a return puts them back. Sizes are added or removed on <a href="?tab=products">Product &amp; prices</a>.</p>
    <form method="post"><?= adm_form('save_stock') ?>
      <div class="scroll"><table class="tbl stock">
        <thead><tr><th>Jacket and size</th><th>On the shelf</th><th>Low-stock line</th><th>Held by open orders</th><th>Free to sell</th><th>State</th></tr></thead>
        <tbody>
        <?php foreach ($stock as $k => $s): $left = $s['qty'] - ($held[$k] ?? 0); ?>
          <tr>
            <td><b><?= h(fj_sku_label($k)) ?></b></td>
            <td><input class="input num" name="qty[<?= h($k) ?>]" inputmode="numeric" value="<?= (int)$s['qty'] ?>" aria-label="<?= h(fj_sku_label($k)) ?> on the shelf"></td>
            <td><input class="input num" name="min[<?= h($k) ?>]" inputmode="numeric" value="<?= (int)$s['min'] ?>" aria-label="<?= h(fj_sku_label($k)) ?> low-stock line"></td>
            <td><?= (int)($held[$k] ?? 0) ?></td>
            <td><b><?= $left ?></b></td>
            <td><?php if ($left < 0): ?><span class="pill bad">Short <?= -$left ?></span><?php elseif ($left === 0): ?><span class="pill bad">Sold out</span><?php elseif ($left <= $s['min']): ?><span class="pill warn">Low</span><?php else: ?><span class="pill ok">OK</span><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <button class="btn" style="margin-top:16px">Save stock</button>
    </form>
  </div>

<?php
/* ---------- Discount codes ---------- */
elseif ($tab === 'coupons'):
    $all = fj_coupons();
    $editCode = fj_coupon_code((string)($_GET['edit'] ?? ''));
    $e = $all[$editCode] ?? null;
    if (!$e) $editCode = '';
    $e = $e ?? ['type' => 'percent', 'value' => 10, 'min' => 0, 'expires' => '', 'max_uses' => 0, 'active' => true, 'note' => ''];
    $given = [];
    foreach ($orders as $o) if (!empty($o['coupon']) && adm_is_paid($o)) $given[$o['coupon']] = ($given[$o['coupon']] ?? 0) + (int)$o['discount'];
?>
  <div class="head"><h1 class="h1">Discount codes</h1></div>
  <div class="grid2">
    <div class="panel">
      <h2>Codes</h2>
      <?php if (!$all): ?><p class="muted">No codes yet. Add one, then share it on Instagram or with a customer. It works at checkout.</p><?php else: ?>
      <div class="scroll"><table class="tbl">
        <thead><tr><th>Code</th><th>Discount</th><th>Min order</th><th>Expires</th><th>Used</th><th>Given (paid)</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        <?php foreach ($all as $code => $c):
          $expired = !empty($c['expires']) && $c['expires'] < date('Y-m-d');
          $usedUp = !empty($c['max_uses']) && (int)$c['used'] >= (int)$c['max_uses']; ?>
          <tr>
            <td><b class="ltr"><?= h($code) ?></b><?php if (!empty($c['note'])): ?><br><span class="muted small"><?= h($c['note']) ?></span><?php endif; ?></td>
            <td class="nowrap"><?= $c['type'] === 'percent' ? (int)$c['value'] . '%' : number_format((int)$c['value']) . ' EGP' ?></td>
            <td class="nowrap"><?= (int)$c['min'] ? number_format((int)$c['min']) . ' EGP' : '—' ?></td>
            <td class="nowrap"><?= h($c['expires'] ?: 'Never') ?></td>
            <td><?= (int)$c['used'] ?><?= (int)$c['max_uses'] ? ' / ' . (int)$c['max_uses'] : '' ?></td>
            <td class="nowrap"><?= number_format($given[$code] ?? 0) ?> EGP</td>
            <td><?php if (empty($c['active'])): ?><span class="pill off">Off</span><?php elseif ($expired): ?><span class="pill warn">Expired</span><?php elseif ($usedUp): ?><span class="pill warn">Used up</span><?php else: ?><span class="pill ok">Active</span><?php endif; ?></td>
            <td class="actions">
              <a class="btn btn-ghost btn-sm" href="?tab=coupons&amp;edit=<?= h(urlencode($code)) ?>" aria-label="Edit code <?= h($code) ?>">Edit</a>
              <form method="post"><?= adm_form('coupon_toggle', ['code' => $code]) ?><button class="btn btn-ghost btn-sm" aria-label="<?= empty($c['active']) ? 'Turn on' : 'Turn off' ?> code <?= h($code) ?>"><?= empty($c['active']) ? 'Turn on' : 'Turn off' ?></button></form>
              <form method="post"><?= adm_form('coupon_delete', ['code' => $code]) ?><button class="btn btn-ghost btn-sm danger" aria-label="Delete code <?= h($code) ?>" onclick="return confirm('Delete code <?= h($code) ?>?')">Delete</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>

    <form class="panel settings" method="post"><?= adm_form('coupon_save', ['orig' => $editCode]) ?>
      <h2 style="margin:0"><?= $editCode ? 'Edit ' . h($editCode) : 'New code' ?></h2>
      <label for="c-code">Code</label>
      <div style="display:flex;gap:8px">
        <input class="input ltr" id="c-code" name="code" value="<?= h($editCode) ?>" required maxlength="30" placeholder="PITLANE10" style="text-transform:uppercase;flex:1;min-width:0">
        <button type="button" class="btn btn-ghost btn-sm" id="c-gen">Generate</button>
      </div>
      <script>
      // FJ + 6 letters and numbers without look-alikes (0/O, 1/I); saving still refuses a code that exists
      document.getElementById('c-gen').addEventListener('click', () => {
        const abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', r = crypto.getRandomValues(new Uint8Array(6));
        const el = document.getElementById('c-code'); el.value = 'FJ' + [...r].map(n => abc[n % abc.length]).join(''); el.focus();
      });
      </script>
      <div class="two">
        <div><label for="c-type">Type</label><select class="input" id="c-type" name="type"><option value="percent" <?= $e['type'] === 'percent' ? 'selected' : '' ?>>Percent off</option><option value="fixed" <?= $e['type'] === 'fixed' ? 'selected' : '' ?>>EGP off</option></select></div>
        <div><label for="c-val">Value</label><input class="input" id="c-val" name="value" inputmode="numeric" value="<?= (int)$e['value'] ?>" required></div>
      </div>
      <div class="two">
        <div><label for="c-min">Minimum order (EGP)</label><input class="input" id="c-min" name="min" inputmode="numeric" value="<?= (int)$e['min'] ?>"></div>
        <div><label for="c-max">Max uses (0 = no limit)</label><input class="input" id="c-max" name="max_uses" inputmode="numeric" value="<?= (int)$e['max_uses'] ?>"></div>
      </div>
      <label for="c-exp">Expires on (empty = never)</label>
      <input class="input" id="c-exp" type="date" name="expires" value="<?= h($e['expires']) ?>">
      <label for="c-note">Note for you (optional)</label>
      <input class="input" id="c-note" name="note" value="<?= h($e['note'] ?? '') ?>" maxlength="120" placeholder="Instagram giveaway, November">
      <label class="check"><input type="checkbox" name="active" value="1" <?= !empty($e['active']) ? 'checked' : '' ?>> Active</label>
      <p class="muted small" style="margin:0">The discount comes off the jackets, not the delivery fee.</p>
      <div style="display:flex;gap:10px"><button class="btn">Save code</button><?php if ($editCode): ?><a class="btn btn-ghost" href="?tab=coupons">Cancel</a><?php endif; ?></div>
    </form>
  </div>

<?php
/* ---------- Customers ---------- */
elseif ($tab === 'affiliates'):
    $apps = array_reverse(fj_read(FJ_DATA . '/affiliates.json') ?? []);
    $coupons = fj_coupons();
    // per approved code: delivered orders and their jacket sales after discount, the base for commission
    $sold = [];
    foreach ($orders as $o) if (!empty($o['coupon']) && $o['status'] === 'delivered') {
        $sold[$o['coupon']]['n'] = ($sold[$o['coupon']]['n'] ?? 0) + 1;
        $sold[$o['coupon']]['sales'] = ($sold[$o['coupon']]['sales'] ?? 0) + (int)$o['unit_price'] * fj_pieces($o) - (int)$o['discount'];
    }
?>
  <div class="head"><h1 class="h1">Affiliates</h1><a class="btn btn-ghost btn-sm" href="affiliate.html" target="_blank">See the application page ↗</a></div>
  <?php if (!$apps): ?><div class="panel"><p class="muted">No applications yet. The form is on affiliate.html, linked in the footer of every page.</p></div><?php endif; ?>
  <?php foreach ($apps as $a): $st = $a['status']; ?>
  <div class="panel">
    <div class="head" style="margin-bottom:.6rem"><h2><?= h($a['name']) ?></h2>
      <span class="pill <?= $st === 'approved' ? 'ok' : ($st === 'rejected' ? 'off' : 'warn') ?>"><?= h(ucfirst($st)) ?></span></div>
    <div class="scroll"><table class="tbl"><tbody>
      <tr><th>WhatsApp</th><td><a class="ltr" href="https://wa.me/<?= h($a['phone']) ?>" target="_blank">0<?= h(substr($a['phone'], 2)) ?></a><?= $a['email'] !== '' ? ' · <span class="ltr">' . h($a['email']) . '</span>' : '' ?> · <?= h($a['gov']) ?></td></tr>
      <tr><th>Posts on</th><td><?= h($a['platform']) ?>, <?= h($a['followers']) ?> followers<br><a class="ltr" href="<?= h($a['link']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= h($a['link']) ?></a><?php if ($a['more'] !== ''): ?><br><span class="ltr muted small" style="white-space:pre-line"><?= h($a['more']) ?></span><?php endif; ?></td></tr>
      <tr><th>Plan</th><td style="white-space:pre-line"><?= h($a['plan']) ?></td></tr>
      <tr><th>Payout</th><td><?= h($a['method']) ?>: <b class="ltr"><?= h($a['account']) ?></b></td></tr>
      <tr><th>Applied</th><td><?= h(date('j M Y, H:i', strtotime($a['created']))) ?></td></tr>
      <?php if ($st === 'approved'): $c = $coupons[$a['code']] ?? null; $s = $sold[$a['code']] ?? ['n' => 0, 'sales' => 0]; ?>
      <tr><th>Code</th><td><b class="ltr"><?= h($a['code']) ?></b> <?= $c ? '(' . (int)$c['value'] . '% off for followers' . (empty($c['active']) ? ', turned off' : '') . ')' : '(code deleted)' ?> · commission <?= (int)($a['commission'] ?? 0) ?>%</td></tr>
      <tr><th>Delivered orders</th><td><?= (int)$s['n'] ?> orders, <?= adm_money((int)$s['sales']) ?> in jackets after discount. Commission owed: <b><?= adm_money((int)floor($s['sales'] * (int)($a['commission'] ?? 0) / 100)) ?></b></td></tr>
      <?php endif; ?>
    </tbody></table></div>
    <?php if ($st !== 'approved'): ?>
    <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;max-width:720px;margin-top:1rem;align-items:end"><?= adm_form('aff_approve', ['id' => $a['id']]) ?>
      <div><label for="ac-<?= h($a['id']) ?>">Their code</label><input class="input ltr" id="ac-<?= h($a['id']) ?>" name="code" value="<?= h($a['code']) ?>" required maxlength="30"></div>
      <div><label for="ad-<?= h($a['id']) ?>">Discount for followers (%)</label><input class="input" id="ad-<?= h($a['id']) ?>" name="discount" inputmode="numeric" value="10" required></div>
      <div><label for="am-<?= h($a['id']) ?>">Their commission (%)</label><input class="input" id="am-<?= h($a['id']) ?>" name="commission" inputmode="numeric" value="10" required></div>
      <div><button class="btn btn-sm">Approve and create the code</button></div>
    </form>
    <?php endif; ?>
    <div style="display:flex;gap:8px;margin-top:.8rem">
      <?php if ($st === 'new'): ?><form method="post"><?= adm_form('aff_reject', ['id' => $a['id']]) ?><button class="btn btn-ghost btn-sm">Reject</button></form><?php endif; ?>
      <form method="post"><?= adm_form('aff_delete', ['id' => $a['id']]) ?><button class="btn btn-ghost btn-sm danger" onclick="return confirm('Delete this application? Their code stays on Discount codes.')">Delete</button></form>
    </div>
  </div>
  <?php endforeach; ?>

<?php
/* ---------- Customers ---------- */
elseif ($tab === 'customers'):
    $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
    $people = [];
    foreach ($orders as $o) { // newest first, so the first order seen gives the latest name and address
        $c = $o['customer'];
        $key = fj_eg_phone($c['phone']);
        $p = $people[$key] ?? ['name' => $c['name'], 'phone' => $c['phone'], 'gov' => $c['gov'], 'orders' => 0, 'paid' => 0, 'spent' => 0, 'returns' => 0, 'last' => $o['created'], 'first' => $o['created'], 'sizes' => []];
        if (!in_array($o['status'], ['cancelled', 'returned'], true)) $p['orders']++;
        if ($o['status'] === 'returned') $p['returns']++;
        if (adm_is_paid($o)) { $p['paid']++; $p['spent'] += (int)$o['total']; }
        foreach (fj_items($o) as $i) $p['sizes'][$i['size']] = true;
        $p['first'] = $o['created'];
        $people[$key] = $p;
    }
    $all = count($people);
    if ($q !== '') $people = array_filter($people, fn($p) => str_contains(mb_strtolower($p['name'] . ' ' . $p['phone'] . ' ' . $p['gov']), $q));
    $repeat = count(array_filter($people, fn($p) => $p['paid'] > 1));
?>
  <div class="head"><h1 class="h1">Customers</h1><span class="muted"><?= $all ?> people · <?= $repeat ?> bought more than once</span></div>
  <div class="panel">
    <div class="filters"><form method="get" role="search" class="search" style="margin-left:0"><input type="hidden" name="tab" value="customers"><input class="input" type="search" name="q" value="<?= h($q) ?>" placeholder="Search name, phone or governorate" aria-label="Search customers"></form></div>
    <?php if (!$people): ?><p class="muted"><?= $all ? 'No customer matches.' : 'Customers show here after their first order.' ?></p><?php else: ?>
    <div class="scroll"><table class="tbl">
      <thead><tr><th>Name</th><th>Phone</th><th>Governorate</th><th>Orders</th><th>Paid</th><th>Returns</th><th>Spent</th><th>Sizes</th><th>Last order</th><th><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($people as $p): ?>
        <tr>
          <td dir="auto"><b><?= h($p['name']) ?></b></td>
          <td><a class="ltr" href="https://wa.me/<?= h(fj_eg_phone($p['phone'])) ?>" target="_blank"><?= h($p['phone']) ?></a></td>
          <td><?= h($p['gov']) ?></td>
          <td><?= $p['orders'] ?></td>
          <td><?= $p['paid'] ?></td>
          <td><?= $p['returns'] ? '<span class="pill warn">' . $p['returns'] . '</span>' : '0' ?></td>
          <td class="nowrap"><?= adm_money($p['spent']) ?></td>
          <td><?= h(implode(', ', array_keys($p['sizes']))) ?></td>
          <td class="nowrap"><?= h(date('j M Y', strtotime($p['last']))) ?></td>
          <td><a class="btn btn-ghost btn-sm" href="?tab=orders&amp;q=<?= h(urlencode($p['phone'])) ?>" aria-label="Orders by <?= h($p['name']) ?>">Orders</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>

<?php
/* ---------- Reviews ---------- */
elseif ($tab === 'reviews'):
    $all = array_reverse(fj_reviews());
    $shown = array_filter($all, fn($r) => !empty($r['shown']));
    $avg = $all ? array_sum(array_column($all, 'rating')) / count($all) : 0;
?>
  <div class="head"><h1 class="h1">Reviews</h1><span class="muted"><?= count($all) ?> reviews<?= $all ? ' · average ' . number_format($avg, 1) . ' / 5' : '' ?> · <?= count($shown) ?> on the site</span></div>
  <div class="grid2">
    <div class="panel">
      <h2>Customer reviews</h2>
      <p class="muted small" style="margin-top:-6px">Approved reviews show on the jacket page, newest first.</p>
      <?php if (!$all): ?><p class="muted">No reviews yet. When a customer sends you feedback on WhatsApp or Instagram, add it here with their permission.</p><?php endif; ?>
      <?php foreach ($all as $r): ?>
        <div class="review">
          <div class="head" style="margin:0"><div><b><bdi><?= h($r['name']) ?></bdi></b><?= $r['city'] ? ' <span class="muted">· ' . h($r['city']) . '</span>' : '' ?></div><span class="stars" role="img" aria-label="<?= (int)$r['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$r['rating']) . '<span class="muted">' . str_repeat('★', 5 - (int)$r['rating']) . '</span>' ?></span></div>
          <p dir="auto"><?= nl2br(h($r['text'])) ?></p>
          <div class="actions">
            <?php if (!empty($r['shown'])): ?><span class="pill ok">On the site</span><?php else: ?><span class="pill warn">Hidden</span><?php endif; ?>
            <span class="muted small"><?= h($r['created']) ?><?= $r['size'] ? ' · size ' . h($r['size']) : '' ?><?= $r['source'] ? ' · via ' . h($r['source']) : '' ?><?= $r['order_id'] ? ' · ' . h($r['order_id']) : '' ?></span>
            <form method="post"><?= adm_form('review_toggle', ['id' => $r['id']]) ?><button class="btn btn-ghost btn-sm" aria-label="<?= empty($r['shown']) ? 'Show on site' : 'Hide' ?>, review by <?= h($r['name']) ?>"><?= empty($r['shown']) ? 'Show on site' : 'Hide' ?></button></form>
            <form method="post"><?= adm_form('review_delete', ['id' => $r['id']]) ?><button class="btn btn-ghost btn-sm danger" aria-label="Delete review by <?= h($r['name']) ?>" onclick="return confirm('Delete this review?')">Delete</button></form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <form class="panel settings" method="post"><?= adm_form('review_save') ?>
      <h2 style="margin:0">Add a review</h2>
      <div class="two">
        <div><label for="r-name">Customer name</label><input class="input" id="r-name" name="name" required maxlength="60"></div>
        <div><label for="r-city">City (optional)</label><input class="input" id="r-city" name="city" maxlength="40" placeholder="Alexandria"></div>
      </div>
      <div class="two">
        <div><label for="r-rate">Rating</label><select class="input" id="r-rate" name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> ★</option><?php endfor; ?></select></div>
        <div><label for="r-size">Size bought</label><select class="input" id="r-size" name="size"><option value="">—</option><?php foreach (fj_size_names($site) as $s): ?><option><?= h($s) ?></option><?php endforeach; ?></select></div>
      </div>
      <label for="r-text">What they said</label><textarea class="input" id="r-text" name="text" rows="4" required maxlength="600"></textarea>
      <div class="two">
        <div><label for="r-src">Where (optional)</label><input class="input" id="r-src" name="source" maxlength="40" placeholder="WhatsApp"></div>
        <div><label for="r-ord">Order no. (optional)</label><input class="input ltr" id="r-ord" name="order_id" maxlength="14" placeholder="FJ-261025-AB12"></div>
      </div>
      <label class="check"><input type="checkbox" name="shown" value="1" checked> Show on the site (the customer agreed)</label>
      <button class="btn">Save review</button>
    </form>
  </div>

<?php
/* ---------- Reports ---------- */
elseif ($tab === 'reports'):
    $months = [];
    for ($i = 11; $i >= 0; $i--) $months[date('Y-m', strtotime(date('Y-m-01') . " -$i month"))] = ['orders' => 0, 'paid' => 0, 'revenue' => 0, 'discount' => 0, 'pieces' => 0];
    $sizes = [];
    $govs = [];
    $pays = [];
    $paidAll = 0; $revAll = 0; $placed = 0; $returned = 0; $cancelled = 0; $piecesAll = 0;
    foreach ($orders as $o) {
        $m = substr($o['created'], 0, 7);
        $paid = adm_is_paid($o);
        if ($o['status'] === 'returned') $returned++;
        if ($o['status'] === 'cancelled') $cancelled++;
        if ($o['status'] !== 'cancelled') $placed++;
        if (isset($months[$m])) {
            if ($o['status'] !== 'cancelled') $months[$m]['orders']++;
            if ($paid) { $months[$m]['paid']++; $months[$m]['revenue'] += (int)$o['total']; $months[$m]['discount'] += (int)$o['discount']; $months[$m]['pieces'] += fj_pieces($o); }
        }
        if (!$paid) continue;
        $paidAll++;
        $revAll += (int)$o['total'];
        $piecesAll += fj_pieces($o);
        foreach (fj_items($o) as $i) {
            $k = fj_sku_label(fj_sku($i['product'], $i['size']));
            $sizes[$k]['orders'] = ($sizes[$k]['orders'] ?? 0) + (int)$i['qty'];
            $sizes[$k]['revenue'] = ($sizes[$k]['revenue'] ?? 0) + (int)$o['unit_price'] * (int)$i['qty'];
        }
        $g = $o['customer']['gov'];
        $govs[$g]['orders'] = ($govs[$g]['orders'] ?? 0) + 1;
        $govs[$g]['revenue'] = ($govs[$g]['revenue'] ?? 0) + (int)$o['total'];
        $k = $o['payment']['method'] === 'cod' ? 'Cash on delivery' : 'InstaPay';
        $pays[$k]['orders'] = ($pays[$k]['orders'] ?? 0) + 1;
        $pays[$k]['revenue'] = ($pays[$k]['revenue'] ?? 0) + (int)$o['total'];
    }
    uasort($sizes, fn($a, $b) => $b['orders'] <=> $a['orders']);
    uasort($govs, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
    $maxRev = max(1, ...array_column($months, 'revenue'));
    $bars = function (array $rows, string $col, string $label, string $first = 'Orders'): void {
        $max = max(1, ...array_column($rows, $col)); ?>
        <div class="scroll"><table class="tbl">
          <thead><tr><th><?= h($label) ?></th><th><?= h($first) ?></th><th>Revenue</th><th class="barcol"><span class="sr-only">Share</span></th></tr></thead>
          <tbody>
          <?php foreach ($rows as $k => $r): ?>
            <tr><td><?= h((string)$k) ?></td><td><?= (int)$r['orders'] ?></td><td class="nowrap"><?= adm_money((int)$r['revenue']) ?></td><td class="barcol"><span class="bar" aria-hidden="true" style="width:<?= round($r[$col] / $max * 100) ?>%"></span></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
    <?php };
?>
  <div class="head"><h1 class="h1">Reports</h1><span class="muted">Money in = delivered or InstaPay confirmed · <a href="?export=orders">Download all orders (CSV)</a></span></div>
  <div class="kpis">
    <div><span>Money in (all time)</span><b><?= adm_money($revAll) ?></b><em><?= $piecesAll ?> jackets</em></div>
    <div><span>Paid orders</span><b><?= $paidAll ?></b><em>of <?= $placed ?> placed</em></div>
    <div><span>Average order</span><b><?= adm_money($paidAll ? (int)round($revAll / $paidAll) : 0) ?></b></div>
    <div><span>Returns</span><b><?= $placed ? round($returned / $placed * 100) : 0 ?>%</b><em><?= $returned ?> returned · <?= $cancelled ?> cancelled</em></div>
  </div>
  <div class="panel">
    <h2>Last 12 months</h2>
    <div class="scroll"><table class="tbl">
      <thead><tr><th>Month</th><th>Placed</th><th>Paid</th><th>Jackets</th><th>Revenue</th><th>Discounts</th><th class="barcol"><span class="sr-only">Share</span></th></tr></thead>
      <tbody>
      <?php foreach (array_reverse($months, true) as $m => $r): ?>
        <tr><td class="nowrap"><?= h(date('M Y', strtotime("$m-01"))) ?></td><td><?= $r['orders'] ?></td><td><?= $r['paid'] ?></td><td><?= $r['pieces'] ?></td><td class="nowrap"><?= adm_money($r['revenue']) ?></td><td class="nowrap"><?= $r['discount'] ? number_format($r['discount']) . ' EGP' : '—' ?></td><td class="barcol"><span class="bar" aria-hidden="true" style="width:<?= round($r['revenue'] / $maxRev * 100) ?>%"></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <div class="grid2">
    <div class="panel"><h2>Jackets sold</h2><?php if ($sizes) $bars($sizes, 'orders', 'Jacket and size', 'Jackets'); else echo '<p class="muted">No paid orders yet.</p>'; ?>
      <p class="muted small">Jacket revenue, before delivery and discounts.</p>
      <h2 style="margin-top:22px">Payment method</h2><?php if ($pays) $bars($pays, 'orders', 'Method'); else echo '<p class="muted">No paid orders yet.</p>'; ?></div>
    <div class="panel"><h2>Governorates</h2><?php if ($govs) $bars($govs, 'revenue', 'Governorate'); else echo '<p class="muted">No paid orders yet.</p>'; ?></div>
  </div>

<?php
/* ---------- Product, price and sizes ---------- */
elseif ($tab === 'products'):
    $rows = array_merge($site['sizes'], [['', '', '']]);
?>
  <div class="head"><h1 class="h1">Product &amp; prices</h1><a class="btn btn-ghost btn-sm" href="product.html" target="_blank">See the jacket page ↗</a></div>
  <form class="panel settings" method="post" style="max-width:none"><?= adm_form('save_product') ?>
    <fieldset><legend>Price (EGP)</legend>
      <div class="two" style="max-width:420px">
        <div><label for="p-price">Price</label><input class="input" id="p-price" name="price" inputmode="numeric" value="<?= (int)$site['price'] ?>" required></div>
        <div><label for="p-was">Old price (sale)</label><input class="input" id="p-was" name="was" inputmode="numeric" value="<?= (int)$site['was'] ?>"></div>
      </div>
      <span class="muted small">Old price above the price shows a “Sale” tag and strikes the old price. 0 = no sale. Orders already placed keep their price.</span>
      <div class="two" style="max-width:420px">
        <div><label for="p-offer">Launch price</label><input class="input" id="p-offer" name="offer_price" inputmode="numeric" value="<?= (int)$site['offer_price'] ?>"></div>
        <div><label for="p-offer-n">Launch orders</label><input class="input" id="p-offer-n" name="offer_orders" inputmode="numeric" value="<?= (int)$site['offer_orders'] ?>"></div>
      </div>
      <span class="muted small">The first orders pay the launch price per jacket; the shop counts down the places left. <b><?= fj_offer_left($site, fj_orders()) ?></b> left now. Cancelled or returned launch orders give their place back. Launch price 0 = no offer.</span>
    </fieldset>
    <fieldset><legend>Sizes and measurements</legend>
      <span class="muted small">Order here = order on the site. Clear a size name to remove it, fill the empty row to add one. Stock per size is on <a href="?tab=inventory">Inventory</a>.</span>
      <div class="scroll"><table class="tbl" style="max-width:520px">
        <thead><tr><th>Size</th><th>Chest (cm)</th><th>Length (cm)</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $i => [$s, $c, $l]): ?>
          <tr>
            <td><input class="input num ltr" name="size[<?= $i ?>]" value="<?= h((string)$s) ?>" maxlength="6" placeholder="<?= $s === '' ? 'New' : '' ?>" aria-label="Size name"></td>
            <td><input class="input num" name="chest[<?= $i ?>]" inputmode="numeric" value="<?= h((string)$c) ?>" aria-label="Chest cm"></td>
            <td><input class="input num" name="length[<?= $i ?>]" inputmode="numeric" value="<?= h((string)$l) ?>" aria-label="Length cm"></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </fieldset>
    <button class="btn">Save product</button>
  </form>

<?php
/* ---------- Settings ---------- */
elseif ($tab === 'settings'):
    $ph = $site['whatsapp'] === FJ_DEFAULTS['whatsapp'] || $site['instapay'] === FJ_DEFAULTS['instapay'];
    $notify = fj_read(FJ_DATA . '/notify.json') ?? [];
    $backups = glob(FJ_DATA . '/backups/data-*.zip') ?: [];
    rsort($backups);
?>
  <div class="head"><h1 class="h1">Settings</h1></div>
  <div class="panel">
    <?php if ($ph): ?><p class="warnbox">The WhatsApp number or InstaPay address is still the sample one. Customers send orders and money there, so add your real details before going live.</p><?php endif; ?>
    <form method="post" class="settings"><?= adm_form('save_settings') ?>
      <fieldset><legend>Contact</legend>
        <label for="s-wa">WhatsApp number (orders and questions arrive here)</label>
        <input class="input ltr" id="s-wa" name="whatsapp" inputmode="tel" value="<?= h('0' . substr((string)$site['whatsapp'], 2)) ?>" placeholder="01xxxxxxxxx">
      </fieldset>
      <fieldset><legend>Payment</legend>
        <label class="check"><input type="checkbox" name="pay_cod" value="1" <?= $site['pay_cod'] ? 'checked' : '' ?>> Cash on delivery</label>
        <label class="check"><input type="checkbox" name="pay_instapay" value="1" <?= $site['pay_instapay'] ? 'checked' : '' ?>> InstaPay</label>
        <label for="s-ip">InstaPay address or mobile number (shown at checkout)</label>
        <input class="input ltr" id="s-ip" name="instapay" value="<?= h($site['instapay']) ?>" placeholder="name@instapay">
      </fieldset>
      <fieldset><legend>Delivery fees (EGP)</legend>
        <label for="s-other">Every governorate not listed below</label>
        <input class="input" id="s-other" name="ship_other" inputmode="numeric" value="<?= (int)$site['ship_other'] ?>" style="max-width:140px">
        <details style="margin-top:8px" <?= $site['ship'] ? 'open' : '' ?>><summary class="small" style="cursor:pointer">Own fee per governorate (empty = the fee above)</summary>
          <div class="govs">
            <?php foreach (FJ_GOVS as $g): ?><label><span><?= h($g) ?></span><input class="input" name="ship[<?= h($g) ?>]" inputmode="numeric" value="<?= isset($site['ship'][$g]) ? (int)$site['ship'][$g] : '' ?>" placeholder="<?= (int)$site['ship_other'] ?>"></label><?php endforeach; ?>
          </div>
        </details>
        <span class="muted small">The Delivery text in the FAQ page is written by hand: update it there if you change the fees.</span>
      </fieldset>
      <fieldset><legend>Orders</legend>
        <label for="s-max">Largest quantity in one order</label>
        <input class="input" id="s-max" name="max_qty" inputmode="numeric" value="<?= (int)$site['max_qty'] ?>" style="max-width:140px">
      </fieldset>
      <button class="btn">Save settings</button>
    </form>
  </div>

  <div class="panel">
    <div class="head"><h2>Phone alerts (Telegram)</h2><?php if (!empty($notify['telegram_token'])): ?><span class="pill ok">On</span><?php else: ?><span class="pill off">Off</span><?php endif; ?></div>
    <p class="muted" style="margin-top:0">Get a Telegram message for every new order. Free, and it works even when email doesn't.</p>
    <ol class="muted small" style="margin:0 0 14px;padding-left:18px;line-height:1.7">
      <li>In Telegram, open <b>@BotFather</b>, send <code>/newbot</code>, pick any name. It gives you a token like <code>123456:ABC…</code>.</li>
      <li>Open your new bot and press <b>Start</b>, then open <b>@userinfobot</b> and press Start: it shows your chat id (a number).</li>
      <li>Paste both here, save, then press “Send test message”.</li>
    </ol>
    <form method="post" class="settings"><?= adm_form('save_notify') ?>
      <label for="n-tok">Bot token</label>
      <input class="input ltr" id="n-tok" name="telegram_token" autocomplete="off" placeholder="<?= !empty($notify['telegram_token']) ? 'Saved. Leave empty to keep it' : '123456789:ABC…' ?>">
      <label for="n-chat">Your chat id</label>
      <input class="input ltr" id="n-chat" name="telegram_chat" value="<?= h($notify['telegram_chat'] ?? '') ?>" placeholder="123456789">
      <?php if (!empty($notify['telegram_token'])): ?><label class="check"><input type="checkbox" name="off" value="1"> Turn alerts off (deletes the token)</label><?php endif; ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn">Save alerts</button><?php if (!empty($notify['telegram_token'])): ?><button class="btn btn-ghost" name="action" value="notify_test">Send test message</button><?php endif; ?></div>
    </form>
  </div>

  <div class="panel">
    <div class="head"><h2>Admin password</h2></div>
    <form method="post" class="settings"><?= adm_form('change_password') ?>
      <label for="pw-cur">Current password</label><input class="input" id="pw-cur" type="password" name="current" autocomplete="current-password" required>
      <div class="two">
        <div><label for="pw-new">New password</label><input class="input" id="pw-new" type="password" name="pw" autocomplete="new-password" required minlength="10"></div>
        <div><label for="pw-new2">Repeat new password</label><input class="input" id="pw-new2" type="password" name="pw2" autocomplete="new-password" required minlength="10"></div>
      </div>
      <button class="btn btn-ghost">Change password</button>
    </form>
  </div>

  <div class="panel">
    <div class="head"><h2>Backups</h2><a class="btn btn-sm" href="?backup=1">Download all data now</a></div>
    <p class="muted" style="margin-top:0">The admin keeps one copy of all orders, stock, codes, reviews and settings per day (the last 14 days) in <code>data/backups</code>. Download a copy to your computer once a week too, in case the hosting account itself is lost.</p>
    <?php if ($backups): ?><p class="small">Latest daily copies: <?= h(implode(' · ', array_map(fn($f) => date('j M', strtotime(substr(basename($f, '.zip'), 5))) . ' (' . max(1, (int)round(filesize($f) / 1024)) . ' KB)', array_slice($backups, 0, 7)))) ?></p><?php elseif (!class_exists('ZipArchive')): ?><p class="warnbox">This server's PHP has no zip extension, so daily copies can't be made. Ask the host to turn on “zip”.</p><?php endif; ?>
  </div>
<?php endif;
