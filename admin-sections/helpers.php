<?php
// Shared helpers for the admin sections. Included by admin.php after login only.
declare(strict_types=1);
if (!defined('FJ_ADMIN')) { http_response_code(403); exit; }

/** Sidebar: tab => label. Order here = order in the menu. */
const ADM_MENU = [
    'dashboard' => 'Dashboard',
    'orders' => 'Orders',
    'payments' => 'InstaPay transfers',
    'shipping' => 'Shipping',
    'inventory' => 'Inventory',
    'coupons' => 'Discount codes',
    'affiliates' => 'Affiliates',
    'customers' => 'Customers',
    'reviews' => 'Reviews',
    'reports' => 'Reports',
];
const ADM_MENU_STORE = [
    'products' => 'Product & prices',
    'settings' => 'Settings',
];

const ADM_COURIERS = ['Bosta', 'Aramex', 'J&T Express', 'Mylerz', 'R2S', 'Egypt Post', 'Own delivery'];

/** Money in: delivered (cash collected) or InstaPay confirmed, and not cancelled or returned. */
function adm_is_paid(array $o): bool {
    if (in_array($o['status'], ['cancelled', 'returned'], true)) return false;
    return $o['status'] === 'delivered' || in_array($o['payment']['status'], ['confirmed', 'collected'], true);
}

/** An InstaPay transfer that still needs the owner: no screenshot yet, or one to check. */
function adm_pay_open(array $o): bool {
    return $o['payment']['method'] === 'instapay' && in_array($o['payment']['status'], ['awaiting', 'sent'], true)
        && !in_array($o['status'], ['cancelled', 'returned'], true);
}

function adm_pay_pill(array $o): string {
    $s = $o['payment']['status'];
    return '<span class="pay pay-' . h($s) . '">' . h(FJ_PAY[$s] ?? $s) . '</span>';
}

function adm_st_pill(array $o): string {
    return '<span class="st st-' . h($o['status']) . '">' . h(FJ_STATUSES[$o['status']] ?? $o['status']) . '</span>';
}

/**
 * Change an order's status and keep the rest in step: shipping takes the jackets out of stock, a return or a
 * cancel after shipping puts them back, delivering a cash order marks the cash collected.
 */
function adm_set_status(array &$o, string $st): void {
    if ($o['status'] === $st) return;
    $o['status'] = $st;
    $o['history'][] = ['status' => $st, 'at' => date('c')];
    if (in_array($st, ['shipped', 'delivered'], true)) {
        fj_stock_move($o, -1);
        $o['shipping']['shipped_at'] ??= date('c');
    }
    if ($st === 'delivered') {
        $o['shipping']['delivered_at'] ??= date('c');
        if ($o['payment']['method'] === 'cod') $o['payment']['status'] = 'collected';
    }
    if (in_array($st, ['cancelled', 'returned'], true)) fj_stock_move($o, 1);
    if ($st === 'new' || $st === 'confirmed') {
        // back to an open order: it holds stock again through fj_reserved, so pieces that came back are not counted twice
        fj_stock_move($o, 1);
        if ($o['payment']['method'] === 'cod') $o['payment']['status'] = 'cod';
    }
}

function adm_items_text(array $o): string { return fj_items_text($o); }

function adm_order_link(string $id): string {
    return '<a class="ltr" href="?tab=orders&amp;q=' . h(urlencode($id)) . '#' . h($id) . '">' . h($id) . '</a>';
}

function adm_money(int $n): string { return number_format($n) . ' <small>EGP</small>'; }

/** WhatsApp chat with the customer, with a message ready in the language they ordered in. */
function adm_wa(array $o, string $kind = 'confirm'): string {
    $ar = ($o['lang'] ?? 'en') === 'ar';
    $c = $o['customer'];
    $s = $o['shipping'] ?? [];
    $text = match ($kind) {
        'confirm' => $ar
            ? "أهلاً {$c['name']}، معاك متجر Formula One Jacket. بنأكد طلبك {$o['id']}: " . fj_items_text($o) . "، الإجمالي {$o['total']} ج.م شامل الشحن، على العنوان: {$c['gov']}، {$c['address']}. نشحنه؟"
            : "Hi {$c['name']}, this is Formula One Jacket. Confirming your order {$o['id']}: " . fj_items_text($o) . ", total {$o['total']} EGP with delivery, to {$c['gov']}, {$c['address']}. Shall we ship it?",
        'shipped' => $ar
            ? "طلبك {$o['id']} اتشحن مع " . ($s['courier'] ?? '') . (!empty($s['tracking']) ? "، رقم التتبع {$s['tracking']}" : '') . '. هيوصلك خلال ٢ لـ ٥ أيام.'
            : "Your order {$o['id']} is on its way with " . ($s['courier'] ?? '') . (!empty($s['tracking']) ? ", tracking {$s['tracking']}" : '') . '. It arrives in 2 to 5 days.',
        default => '',
    };
    return 'https://wa.me/' . fj_eg_phone($c['phone']) . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/** Hidden CSRF + action fields for a POST form. */
function adm_form(string $action, array $extra = []): string {
    $s = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf'] ?? '') . '"><input type="hidden" name="action" value="' . h($action) . '">';
    foreach ($extra as $k => $v) $s .= '<input type="hidden" name="' . h($k) . '" value="' . h((string)$v) . '">';
    return $s;
}
