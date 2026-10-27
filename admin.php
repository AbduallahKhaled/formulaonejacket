<?php
// Formula One Jacket admin: dashboard, orders, InstaPay, shipping, inventory, discount codes, customers, reviews,
// reports, product & prices, settings. One password, no database.
declare(strict_types=1);
require __DIR__ . '/lib.php';

ini_set('session.use_strict_mode', '1'); // never adopt a session id the server did not create
ini_set('session.use_only_cookies', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
// 8 hours without a click logs the admin out (a laptop left open, a shared computer)
if (!empty($_SESSION['admin']) && ($_SESSION['seen'] ?? 0) < time() - 8 * 3600) { $_SESSION = []; session_regenerate_id(true); }
$_SESSION['seen'] = time();
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
// The admin shows customer text: if anything ever slipped past h(), the page still cannot load outside scripts or send data away.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");

$authFile = FJ_DATA . '/admin.json';
$auth = fj_read($authFile);
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
$flash = $_SESSION['flash'] ?? '';
$flashBad = !empty($_SESSION['flash_bad']);
unset($_SESSION['flash'], $_SESSION['flash_bad']);

function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? '')); }
function go(string $q = '', string $msg = '', bool $bad = false): never {
    if ($msg !== '') { $_SESSION['flash'] = $msg; $_SESSION['flash_bad'] = $bad; }
    header('Location: admin.php' . ($q !== '' ? "?$q" : ''), true, 303);
    exit;
}
$post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

/* ---------- First run: create the password ---------- */
if (!$auth) {
    // A fresh upload has no password yet, so whoever opens admin.php first would own the store. The first password
    // therefore needs a code that only someone with access to the hosting files can read.
    $codeFile = FJ_DATA . '/SETUP-CODE.txt';
    if (!is_file($codeFile)) {
        if (!is_dir(FJ_DATA)) mkdir(FJ_DATA, 0755, true);
        file_put_contents($codeFile, bin2hex(random_bytes(8)) . "\n");
    }
    $err = '';
    if ($post && csrf_ok()) {
        usleep(400000);
        $pw = fj_str($_POST['pw'] ?? '');
        if (!hash_equals(trim((string)file_get_contents($codeFile)), trim(fj_str($_POST['setup'] ?? '')))) $err = 'The setup code is wrong. Copy it from data/SETUP-CODE.txt.';
        elseif (strlen($pw) < 10) $err = 'Use at least 10 characters.';
        elseif ($pw !== ($_POST['pw2'] ?? '')) $err = 'The two passwords don\'t match.';
        else {
            fj_write($authFile, ['hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('c')]);
            @unlink($codeFile);
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            go('', 'Password saved. Welcome to your store admin.');
        }
    }
    page_head('Set up admin');
    $inv = $err ? ' aria-invalid="true"' : '';
    echo '<main class="panel login"><h1>Create your admin password</h1><p class="muted">First time here. This password protects your orders and customers.</p>';
    if ($err) echo '<p class="err" id="login-err" role="alert">' . h($err) . '</p>';
    echo '<form method="post" class="settings"><input type="hidden" name="csrf" value="' . h($csrf) . '">
      <label for="setup">Setup code</label><input class="input ltr" id="setup" name="setup" autocomplete="off" required aria-describedby="setup-hint">
      <span class="muted small" id="setup-hint">In your hosting File Manager, open <code>data/SETUP-CODE.txt</code> and copy the code. It is deleted once the password is saved.</span>
      <label for="pw">Password</label><input class="input" id="pw" type="password" name="pw" autocomplete="new-password" required minlength="10" aria-describedby="pw-hint' . ($err ? ' login-err' : '') . '"' . $inv . '>
      <span class="muted small" id="pw-hint">At least 10 characters.</span>
      <label for="pw2">Repeat password</label><input class="input" id="pw2" type="password" name="pw2" autocomplete="new-password" required minlength="10"' . $inv . '>
      <button class="btn">Save password</button></form></main>';
    page_foot();
    exit;
}

/* ---------- Login / logout ---------- */
if (isset($_GET['logout'])) { $_SESSION = []; session_destroy(); header('Location: admin.php'); exit; }
if (empty($_SESSION['admin'])) {
    $err = '';
    // 5 wrong passwords from one address lock the login for 15 minutes.
    $failFile = FJ_DATA . '/login-fails.json';
    $ipKey = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'fj'), 0, 16);
    $fails = array_filter(fj_read($failFile) ?? [], fn($f) => ($f['until'] ?? 0) > time() || ($f['last'] ?? 0) > time() - 3600);
    $mine = $fails[$ipKey] ?? ['n' => 0, 'until' => 0, 'last' => 0];
    if ($post && csrf_ok()) {
        usleep(400000);
        if ($mine['until'] > time()) {
            $err = 'Too many wrong tries. Try again in ' . (int)ceil(($mine['until'] - time()) / 60) . ' minutes.';
        } elseif (password_verify(fj_str($_POST['pw'] ?? ''), (string)$auth['hash'])) {
            unset($fails[$ipKey]);
            fj_write($failFile, $fails);
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            go();
        } else {
            $mine['n']++;
            $mine['last'] = time();
            if ($mine['n'] >= 5) {
                $mine = ['n' => 0, 'until' => time() + 900, 'last' => time()];
                $err = 'Too many wrong tries. Login is locked for 15 minutes.';
                fj_notify('⚠️ Store admin: 5 wrong passwords in a row. Login locked for 15 minutes.');
            } else {
                $left = 5 - $mine['n'];
                $err = "Wrong password. $left " . ($left === 1 ? 'try' : 'tries') . ' left.';
            }
            $fails[$ipKey] = $mine;
            fj_write($failFile, $fails);
        }
    }
    page_head('Admin login');
    echo '<main class="panel login"><h1>Formula One Jacket admin</h1>';
    if ($err) echo '<p class="err" id="login-err" role="alert">' . h($err) . '</p>';
    echo '<form method="post" class="settings"><input type="hidden" name="csrf" value="' . h($csrf) . '">
      <label for="pw">Password</label><input class="input" id="pw" type="password" name="pw" autocomplete="current-password" required autofocus' . ($err ? ' aria-invalid="true" aria-describedby="login-err"' : '') . '>
      <button class="btn">Log in</button></form></main>';
    page_foot();
    exit;
}

define('FJ_ADMIN', true);
// The owner's own address is the one trusted for links in Telegram alerts (see fj_base_url).
$here = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
if (preg_match('#^https?://[A-Za-z0-9.-]+(:\d{1,5})?$#', $here) && $here !== fj_base_url()) fj_write(FJ_DATA . '/base.json', ['url' => $here]);
require __DIR__ . '/admin-sections/helpers.php';
require __DIR__ . '/admin-sections/downloads.php';
fj_backup_daily();

/* ---------- Actions ---------- */
if ($post) {
    if (!csrf_ok()) go('', 'Session expired, please try again.', true);
    $action = (string)($_POST['action'] ?? '');
    require __DIR__ . '/admin-sections/actions.php';
    go('', 'Unknown action.', true);
}

/* ---------- Views ---------- */
$tab = (string)($_GET['tab'] ?? 'dashboard');
if (!isset(ADM_MENU[$tab]) && !isset(ADM_MENU_STORE[$tab])) $tab = 'dashboard';
$month = date('Y-m');
$site = fj_site();
$orders = fj_orders();
$avail = fj_available($site, $orders);
$stockNow = fj_stock($site);
$badges = [
    'orders' => count(array_filter($orders, fn($o) => $o['status'] === 'new')),
    'payments' => count(array_filter($orders, 'adm_pay_open')),
    'shipping' => count(array_filter($orders, fn($o) => $o['status'] === 'confirmed')),
    'inventory' => count(array_filter($stockNow, fn($s, $k) => $avail[$k] <= $s['min'], ARRAY_FILTER_USE_BOTH)),
];

$badgeWord = ['orders' => ' new', 'payments' => ' to check', 'shipping' => ' to ship', 'inventory' => ' sizes low'];
page_head((ADM_MENU[$tab] ?? ADM_MENU_STORE[$tab]) . ' · Store admin');
?>
<a class="skip" href="#content">Skip to content</a>
<div class="shell">
<aside class="side">
  <div class="brand">FORMULA <span>ONE</span> JACKET</div>
  <nav aria-label="Admin">
    <?php foreach (ADM_MENU as $k => $label): ?>
      <a class="<?= $tab === $k ? 'on' : '' ?>" href="?tab=<?= $k ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= h($label) ?><?php if (!empty($badges[$k])): ?><span class="badge"><?= $badges[$k] ?><span class="sr-only"><?= $badgeWord[$k] ?></span></span><?php endif; ?></a>
    <?php endforeach; ?>
    <span class="navlabel">Store</span>
    <?php foreach (ADM_MENU_STORE as $k => $label): ?>
      <a class="<?= $tab === $k ? 'on' : '' ?>" href="?tab=<?= $k ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
    <span class="navlabel"></span>
    <a href="index.html" target="_blank">View site <span aria-hidden="true">↗</span><span class="sr-only"> (opens in a new tab)</span></a>
    <a href="?logout=1">Log out</a>
  </nav>
</aside>
<main class="main" id="content" tabindex="-1">
<?php if ($flash): ?><div class="flash<?= $flashBad ? ' bad' : '' ?>" role="<?= $flashBad ? 'alert' : 'status' ?>" tabindex="-1"><?= h($flash) ?></div><?php endif; ?>
<?php require __DIR__ . '/admin-sections/views.php'; ?>
</main>
</div>
<?php
page_foot();

/* ---------- Layout ---------- */
function page_head(string $title): void { ?>
<!doctype html>
<html lang="en" dir="ltr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title><meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,700;1,800&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{--red:#E10600;--bg:#0e0e0e;--surface:#171717;--surface-2:#222;--line:#2e2e2e;--text:#f4f1ea;--muted:#a3a3a3;--faint:#8f8f8f}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px/1.5 Inter,system-ui,sans-serif}a{color:#ff6b63}
.wrap{max-width:1440px;margin:0 auto;padding:20px 16px 60px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border:0;background:var(--red);color:#fff;font:600 .9rem Inter,sans-serif;cursor:pointer;text-decoration:none}.btn:hover{background:#b80500}
.btn-ghost{background:transparent;border:1px solid var(--line);color:var(--text)}.btn-ghost:hover{background:var(--surface-2)}.btn-sm{padding:6px 12px;font-size:.82rem}
.input{width:100%;min-height:42px;padding:8px 12px;background:var(--surface);border:1px solid var(--line);color:var(--text);font:inherit}.input:focus{outline:2px solid var(--red);outline-offset:0}
.panel{background:var(--surface);border:1px solid var(--line);padding:20px}.login{max-width:420px;margin:12vh auto 0}.login h1{font:italic 800 1.8rem "Barlow Condensed",sans-serif;margin:0 0 10px}
.muted{color:var(--muted)}.err{color:#ff8a84}.small{font-size:.8rem}.nowrap{white-space:nowrap}.ltr{direction:ltr;unicode-bidi:embed}
.shell{display:grid;grid-template-columns:230px minmax(0,1fr);gap:26px;align-items:start}
.side{position:sticky;top:20px;display:grid;gap:18px}.brand{font:italic 800 1.35rem "Barlow Condensed",sans-serif;padding:4px 12px;border-left:4px solid var(--red)}.brand span{color:var(--red)}
.side nav{display:grid;gap:2px}.side nav a{display:flex;justify-content:space-between;align-items:center;padding:9px 12px;color:var(--muted);text-decoration:none;font-size:.92rem}.side nav a.on,.side nav a:hover{background:var(--surface-2);color:#fff}.side nav a.on{box-shadow:inset 3px 0 var(--red)}
.navlabel{display:block;margin:14px 12px 4px;font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.badge{min-width:22px;padding:1px 7px;background:var(--red);color:#fff;font-size:.72rem;font-weight:700;text-align:center}
.main{min-width:0;display:grid;grid-template-columns:minmax(0,1fr);gap:18px;align-content:start}
.h1{font:italic 800 2rem "Barlow Condensed",sans-serif;margin:0;text-transform:uppercase}.h3{font-size:1rem;margin:20px 0 8px}
.panel h2{font-size:1.1rem;margin:0 0 14px}.head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}.head h2{margin:0}
@media(max-width:900px){.shell{grid-template-columns:1fr}.side{position:static}.side nav{display:flex;overflow-x:auto;gap:4px}.side nav a{white-space:nowrap}.navlabel{display:none}}
.plain{list-style:none;padding:0;margin:0;display:grid;gap:10px}
.kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}.kpis>*{display:block;padding:16px;background:var(--surface);border:1px solid var(--line);color:inherit;text-decoration:none}.kpis a:hover{border-color:var(--red)}.kpis span{display:block;color:var(--muted);font-size:.8rem}.kpis b{display:block;font:700 1.7rem "Barlow Condensed",sans-serif;margin-top:4px}.kpis em{display:block;font-style:normal;color:var(--faint);font-size:.75rem}
.grid2{display:grid;grid-template-columns:1.2fr 1fr;gap:18px;align-items:start}.grid2>*{min-width:0}@media(max-width:1280px){.grid2{grid-template-columns:minmax(0,1fr)}}
.tbl{width:100%;border-collapse:collapse}.tbl th,.tbl td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);font-size:.9rem;vertical-align:top}.tbl th{color:var(--muted);font-weight:600;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em}.scroll{overflow-x:auto}
.st,.pay,.pill{font-size:.72rem;font-weight:700;padding:3px 9px;background:var(--surface-2);white-space:nowrap}
.st-new,.pay-awaiting,.pill.warn{background:rgba(255,184,77,.16);color:#ffc971}.st-confirmed,.pay-sent{background:rgba(55,118,255,.2);color:#a9c3ff}.st-shipped{background:rgba(140,82,255,.2);color:#cdb6ff}
.st-delivered,.pay-confirmed,.pay-collected,.pill.ok{background:rgba(62,224,143,.15);color:#7ff0b5}.st-returned,.pay-rejected,.pill.bad{background:rgba(225,6,0,.2);color:#ff8a84}.st-cancelled,.pill.off{color:var(--faint)}.pay-cod{color:#ddd}
.order{border:1px solid var(--line);margin-bottom:10px;background:var(--bg)}.order summary{cursor:pointer;list-style:none;display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:14px 16px}.order summary::-webkit-details-marker{display:none}.order .amt{margin-left:auto}
.order .inner{padding:0 16px 16px;gap:24px}.order p{margin:6px 0}.order p .muted{display:inline-block;min-width:110px}
.paybox{margin:0 0 12px;padding:12px;border:1px solid var(--line)}.proof-thumb{max-width:180px;max-height:180px;border:1px solid var(--line);display:block}
.payform,.shipform,.status-form,.actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center}.payform .input,.shipform .input,.status-form .input{width:auto;max-width:200px;min-height:36px;font-size:.85rem}.actions form{margin:0}
/* in a table the actions stay a real cell (flex would push them out of the column); the buttons wrap inside it */
.tbl td.actions{display:table-cell;width:1%}.tbl td.actions>*{display:inline-flex;margin:0 4px 6px 0}.tbl td.actions .btn{white-space:nowrap}
.shiprow{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 0;border-bottom:1px solid var(--line)}.danger{color:#ff8a84}
.filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:12px 0 16px}.chip{padding:6px 12px;border:1px solid var(--line);color:var(--muted);font-size:.85rem;text-decoration:none}.chip.on,.chip:hover{background:var(--surface-2);color:#fff}.search{margin-left:auto;min-width:220px;flex:1;max-width:360px}
.stock .num,.num{max-width:110px;min-height:38px}
.sizes{display:flex;gap:8px;flex-wrap:wrap}.sizes div{min-width:64px;padding:8px;border:1px solid var(--line);text-align:center}.sizes b{display:block}.sizes span{font:700 1.4rem "Barlow Condensed",sans-serif}.sizes .low{border-color:#ffb84d}.sizes .out{border-color:var(--red);color:#ff8a84}
.review{padding:14px 0;border-bottom:1px solid var(--line)}.review p{margin:8px 0}.stars{color:#ffc971;letter-spacing:2px}
.barcol{width:30%}.bar{display:block;height:8px;background:var(--red);min-width:2px}
.flash{padding:12px 16px;background:rgba(62,224,143,.12);border:1px solid rgba(62,224,143,.35)}
.warnbox{padding:12px 16px;background:rgba(255,184,77,.12);border:1px solid rgba(255,184,77,.4);color:#ffd99a}
.settings{display:grid;gap:10px;max-width:560px}.settings fieldset{border:1px solid var(--line);padding:16px;display:grid;gap:8px;margin:0 0 8px}.settings legend{padding:0 6px;font-weight:700}.settings label{font-size:.85rem;color:var(--muted)}.settings .two{display:grid;grid-template-columns:1fr 1fr;gap:12px}.settings .btn{justify-self:start}
.check{display:flex;gap:8px;align-items:center;font-size:.9rem;color:var(--text)!important}
.govs{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin-top:8px}.govs label{display:grid;gap:2px}.govs .input{min-height:34px}
code{background:var(--surface-2);padding:1px 5px}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.skip{position:absolute;left:16px;top:-60px;z-index:9;padding:10px 16px;background:#fff;color:#000;font-weight:700}.skip:focus{top:12px}
a:focus-visible,button:focus-visible,summary:focus-visible,.chip:focus-visible{outline:2px solid #fff;outline-offset:2px}.main:focus,.flash:focus{outline:0}
.flash.bad{background:rgba(225,6,0,.14);border-color:rgba(255,138,132,.5)}
</style></head><body><div class="wrap">
<?php }

function page_foot(): void { ?>
</div><script>document.querySelector('.flash')?.focus()</script></body></html>
<?php }
