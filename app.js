// Edit these before launch.
const STORE_NAME = "Formula One Jacket";
const PRICE = 1450;               // EGP, placeholder
const WAS = 0;                    // old price in EGP; above PRICE it shows a "Sale" tag and strikes the old price
const WA_NUMBER = "201000000000"; // placeholder: country code + number, no "+"
const INSTAPAY = "yourstore@instapay"; // placeholder: your InstaPay address, or the mobile number linked to it
// mock measurements and stock: replace with the real numbers
const SIZES = [["S", 108, 66], ["M", 114, 68], ["L", 120, 70], ["XL", 126, 72], ["XXL", 132, 74]];
const STOCK = { S: 4, M: 12, L: 9, XL: 2, XXL: 0 };
const SHIP = { Cairo: 70, Giza: 70, other: 90 }; // EGP by governorate

const GOVS = [
  ["Cairo", "القاهرة"], ["Giza", "الجيزة"], ["Alexandria", "الإسكندرية"], ["Qalyubia", "القليوبية"],
  ["Sharqia", "الشرقية"], ["Dakahlia", "الدقهلية"], ["Gharbia", "الغربية"], ["Monufia", "المنوفية"],
  ["Beheira", "البحيرة"], ["Kafr El Sheikh", "كفر الشيخ"], ["Damietta", "دمياط"], ["Port Said", "بورسعيد"],
  ["Ismailia", "الإسماعيلية"], ["Suez", "السويس"], ["Faiyum", "الفيوم"], ["Beni Suef", "بني سويف"],
  ["Minya", "المنيا"], ["Asyut", "أسيوط"], ["Sohag", "سوهاج"], ["Qena", "قنا"], ["Luxor", "الأقصر"],
  ["Aswan", "أسوان"], ["Red Sea", "البحر الأحمر"], ["New Valley", "الوادي الجديد"], ["Matrouh", "مطروح"],
  ["North Sinai", "شمال سيناء"], ["South Sinai", "جنوب سيناء"],
];

// product photos: the shop grid and the product gallery both use these
const GALLERY = [["img/p-front.webp", "vFront"], ["img/p-angle.webp", "vAngle"], ["img/p-side.webp", "vSide"], ["img/p-back.webp", "vBack"]];
const EN_LABELS = { vFront: "Front", vAngle: "Three-quarter", vSide: "Side", vBack: "Back" };

// pixel cars: small sprites ride on button clicks, all of them run in the track
const SMALL_CARS = ["red01", "navy05", "blue06", "test", "blue01", "yellow02"];
const ALL_CARS = [...SMALL_CARS, "navy25", "red23"];

const AR = {
  skip: "تخطي", raceStart: "انطلاق السباق", go: "انطلق!",
  annText: "الدفع عند الاستلام في كل مصر. تبديل المقاس مجاناً خلال ٧ أيام.", cartWord: "السلة", navWa: "واتساب",
  navShop: "المتجر", navFit: "المقاسات", navDelivery: "التوصيل", navFaq: "أسئلة",
  h1a: "إرث السباقات.", h1b: "طاقة الشارع.",
  heroSub: "جاكيت سباقات مبطن أسود وأبيض وأحمر، كل شعار فيه متطرز. ادفع كاش لما يوصلك.",
  shopBtn: "تسوق الجاكيت", viewDetails: "شوف التفاصيل", findFit: "اعرف مقاسك", egp: "ج.م", drag: "اسحب للدوران",
  pause: "إيقاف", play: "تشغيل", spinLabel: "الجاكيت من كل الزوايا. اسحب للدوران.",
  f1t: "تفاصيل الأمام والخلف", f1s: "صور من أربع زوايا", f2t: "اعرف مقاسك", f2s: "الصدر والطول لكل مقاس", f3t: "التوصيل والدفع", f3s: "كاش عند الاستلام، من ٢ لـ ٥ أيام",
  shopTitle: "تسوق الجاكيت", shopSub: "جاكيت واحد من أربع زوايا. اختار الصورة وبعدين المقاس.", chooseSize: "اختار المقاس", sale: "خصم",
  whyTitle: "ليه تشتري مننا", whyAlt: "ليه تشتري مننا: منتجات بجودة عالية، توصيل سريع، دفع آمن، وإرجاع سهل.",
  pSub: "أسود وأبيض وأحمر", pName: "جاكيت سباقات", size: "المقاس", sizeGuide: "جدول المقاسات", fQty: "الكمية",
  delEstT: "التوصيل", delEst: "من ٢ لـ ٥ أيام، كل المحافظات", payT: "الدفع", payNote: "الدفع عند الاستلام أو إنستاباي",
  addCart: "أضف للسلة", askSize: "اسأل عن المقاس على واتساب",
  fitTitle: "المقاس والتفاصيل", sizeNote: "قصة واسعة شوية. لو محتار بين مقاسين خد الأصغر.", chest: "الصدر", length: "الطول",
  delTitle: "التوصيل والإرجاع",
  dv1t: "التغطية", dv1: "كل المحافظات الـ ٢٧.", dv2t: "الشحن", dv2: "القاهرة والجيزة ٧٠ ج.م. باقي المحافظات ٩٠ ج.م.",
  dv3t: "المدة", dv3: "من ٢ لـ ٥ أيام بعد ما نأكد طلبك على واتساب.", dv4t: "الدفع", dv4: "الدفع عند الاستلام: افتح الشحنة مع المندوب وبعدين ادفع. أو ادفع مقدماً بإنستاباي وابعتلنا صورة التحويل.",
  dv5t: "الإرجاع", dv5: "المقاس غلط؟ كلمنا خلال ٧ أيام ونبدله. مصاريف شحن المرتجع عليك.",
  orderTitle: "إتمام الطلب", orderSub: "أربع خانات وطلبك يوصلنا على واتساب. بنأكد ونشحن وانت تدفع عند الاستلام.",
  sumTitle: "طلبك", sub: "المجموع", ship: "الشحن", total: "الإجمالي", editCart: "غيّر المقاس",
  emptyTitle: "السلة فاضية", emptySub: "اختار مقاسك من صفحة الجاكيت وارجع هنا.", emptyBtn: "شوف الجاكيت",
  fName: "الاسم بالكامل", eName: "اكتب اسمك", fPhone: "رقم الموبايل", ePhone: "اكتب رقم موبايل مصري صحيح",
  fGov: "المحافظة", eGov: "اختار محافظتك", fAddr: "العنوان", eAddr: "اكتب عنوانك",
  payTitle: "طريقة الدفع", payCod: "الدفع عند الاستلام", payCodNote: "ادفع للمندوب لما الشحنة توصل.",
  send: "ابعت الطلب على واتساب",
  confTitle: "الطلب جاهز", confSub: "ابعته على واتساب عشان نأكده. الطلب مش متسجل غير لما نرد عليك.", confBtn: "ابعت على واتساب", confEdit: "عدّل الطلب",
  faqTitle: "أسئلة.", q2: "أقدر أشوفه قبل ما أدفع؟", a2: "أيوه. افتح الشحنة مع المندوب وبعدين ادفع.",
  q3: "ده منتج رسمي؟", a3: "لا. ده جاكيت بستايل سباقات كلاسيكي للمعجبين، مش منتج رسمي لأي فريق أو بطولة.",
  q4: "المقاس طلع غلط؟", a4: "كلمنا خلال ٧ أيام ونبدله. مصاريف شحن المرتجع عليك.",
  q5: "أعرف منين إن طلبي اتسجل؟", a5: "بنرد عليك على واتساب نأكد المقاس والعنوان وسعر الشحن. وبعدين نشحن.",
  footNote: "متجر مستقل. غير تابع للفورمولا ١ أو الاتحاد الدولي للسيارات أو أي فريق سباقات.",
  vFront: "أمامي", vAngle: "زاوية", vSide: "جانبي", vBack: "خلفي",
  stockIn: "متوفر", stockLow: "باقي {n} بس", stockOut: "خلص", pickGov: "اختار المحافظة",
  itemLine: "المقاس {s} × {q}", turnL: "لف لليسار", turnR: "لف لليمين",
  cartTitle: "السلة", close: "إغلاق", remove: "شيل", less: "قلّل", more: "زوّد", shipNote: "الشحن بيتحسب في صفحة الدفع.", checkoutBtn: "كمّل الطلب",
  payIpNote: "ادفع دلوقتي من تطبيق البنك وابعتلنا صورة التحويل.", ipTo: "حوّل على", ipAmount: "المبلغ", copy: "نسخ",
  copied: "اتنسخ", copyFail: "النسخ مش شغال. علّم على الرقم وانسخه.", ipGovFirst: "اختار المحافظة الأول عشان المبلغ يشمل الشحن.",
  fShot: "صورة التحويل", eShot: "ضيف صورة تحويل إنستاباي (صورة لحد ١٠ ميجا)", ipStep: "بعد كده ابعت صورة التحويل في نفس الشات.",
  shareShot: "ابعت صورة التحويل", ipAttach: "في واتساب دوس على علامة المشبك وابعت الصورة.",
};
// English strings that only JS uses; everything else is read from the page itself
const EN2 = { stockIn: "In stock", stockLow: "Only {n} left", stockOut: "Sold out", pickGov: "Choose a governorate", itemLine: "Size {s} x {q}",
  play: "Play", spinLabel: "Racing jacket, 360 degree view. Drag to turn.", sale: "Sale", turnL: "Turn left", turnR: "Turn right",
  close: "Close", remove: "Remove", less: "One fewer", more: "One more", shipNote: "Delivery is added at checkout.", checkoutBtn: "Checkout",
  pName: "Racing Jacket", sub: "Subtotal", egp: "EGP", emptyTitle: "Your cart is empty", emptyBtn: "See the jacket",
  copied: "Copied", copyFail: "Copy did not work. Select the text and copy it.", ipAttach: "In WhatsApp, tap the paperclip and attach the screenshot.",
  whyAlt: "Why shop with us: premium quality products, fast and reliable delivery, secure payment, easy return policy." };

const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];
const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
const store = { get: k => { try { return localStorage.getItem(k); } catch { return null; } },
                set: (k, v) => { try { localStorage.setItem(k, v); } catch {} } };
const fmt = n => n.toLocaleString("en-US");
const waLink = text => `https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(text)}`;
const page = document.body.dataset.page;

// ---------- shared header and footer, written once here for every page ----------
const NAV = [["shop.html", "navShop", "Shop"], ["product.html#fit", "navFit", "Size guide"], ["faq.html#delivery", "navDelivery", "Delivery"], ["faq.html#faq", "navFaq", "FAQ"]];
const links = NAV.map(([h, k, t]) => `<a href="${h}" data-i18n="${k}"${h === page + ".html" ? ' aria-current="page"' : ""}>${t}</a>`).join("");
document.body.insertAdjacentHTML("afterbegin", `
<div class="announce" id="announce">
  <p data-i18n="annText">Cash on delivery across Egypt. Free size swap in 7 days.</p>
  <button type="button" id="annClose" aria-label="Close">&times;</button>
</div>
<header class="nav">
  <button type="button" class="menu-btn" id="menuBtn" aria-label="Menu" aria-expanded="false" aria-controls="drawer">
    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M3 6h18v2H3zM3 11h18v2H3zM3 16h18v2H3z"/></svg>
  </button>
  <a href="index.html" class="brand"><svg class="mark" data-mark aria-hidden="true"></svg><span class="sr-only" data-store-name></span></a>
  <nav class="nav-links" aria-label="Main">${links}</nav>
  <div class="nav-r">
    <button type="button" class="lang" id="langBtn">عربي</button>
    <button type="button" class="cart" id="cartBtn" aria-haspopup="dialog" aria-controls="cartPanel"><span data-i18n="cartWord">Cart</span> <b id="cartCount">0</b></button>
  </div>
</header>
<dialog class="cart-panel" id="cartPanel" aria-labelledby="cartTitle">
  <div class="cp-head"><h2 id="cartTitle" data-i18n="cartTitle">Your cart</h2><button type="button" class="cp-x" id="cartClose" data-i18n-label="close" aria-label="Close">&times;</button></div>
  <div class="cp-body" id="cpBody"></div>
</dialog>
<div class="drawer" id="drawer" hidden><nav aria-label="Menu">${links}<a href="#" data-wa data-i18n="navWa">WhatsApp</a></nav></div>
<div class="scrim" id="scrim" hidden></div>`);
document.body.insertAdjacentHTML("beforeend", `
<footer class="foot">
  <svg class="stripe" data-stripe="60" aria-hidden="true"></svg>
  <svg class="mark" data-mark role="img" aria-label="Formula One Jacket"></svg>
  <nav class="foot-links" aria-label="Footer">${links}<a href="#" data-wa data-i18n="navWa">WhatsApp</a></nav>
  <p class="foot-note" data-i18n="footNote">Independent store. Not affiliated with Formula 1, the FIA or any racing team.</p>
</footer>
<a class="wa-float" data-wa href="#" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 32 32" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3Zm0 23.7c-2 0-3.9-.5-5.6-1.5l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7Zm5.9-8c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.2 1.4 3.5c.2.2 2.4 3.6 5.8 5 2.1.9 3 1 4 .8.7-.1 1.9-.8 2.2-1.5.3-.8.3-1.4.2-1.5-.1-.2-.3-.2-.6-.4Z"/></svg>
</a>`);

// ---------- shop grid: one card per photo, each opens the product page on that photo ----------
$$("[data-grid]").forEach(ul => {
  const off = WAS > PRICE ? Math.round((1 - PRICE / WAS) * 100) : 0;
  ul.innerHTML = GALLERY.map(([src, k], i) => `<li><a class="card" href="product.html?v=${i}">
    <span class="card-img"><img src="${src}" alt="" width="1024" height="1536" loading="lazy"><img class="alt" src="${GALLERY[GALLERY.length - 1 - i][0]}" alt="" width="1024" height="1536" loading="lazy"></span>
    <span class="card-tag">${off ? `<span data-i18n="sale">Sale</span> ${off}%` : `<span data-i18n="${k}">${EN_LABELS[k]}</span>`}</span>
    <span class="card-row"><b data-i18n="pName">Racing Jacket</b><span class="card-price">${off ? `<s>${fmt(WAS)}</s> ` : ""}${fmt(PRICE)} <span data-i18n="egp">EGP</span></span></span>
    <span class="card-cta" data-i18n="chooseSize">Choose size</span></a></li>`).join("");
});

// ---------- pixel art: logo mark and speed stripes, drawn as square cells ----------
// logo lockup traced cell by cell from the brand board (src-ref): pixel mark, wordmark under its left side
const MARK = [
  ".......##########.##......##",
  "......##########.###......##",
  "....##...........##......##.",
  "...##..#######..##.......##.",
  "..##..#######..###......##..",
  ".##.##........#######..###..",
  "##.##.........######..###...",
  "......................###...",
  ".....................###....",
  "....................###.....",
];
const cells = list => list.map(([x, y]) => `<rect x="${x * 10}" y="${y * 10}" width="9" height="9"/>`).join("");
const markCells = MARK.flatMap((row, y) => [...row].flatMap((c, x) => c === "#" ? [[x, y]] : []));
$$("[data-mark]").forEach(svg => {
  svg.setAttribute("viewBox", `0 0 ${MARK[0].length * 10} ${MARK.length * 10}`);
  svg.innerHTML = cells(markCells) + `<text x="1" y="87" textLength="196" lengthAdjust="spacing" fill="currentColor" font-family="Barlow Condensed, sans-serif" font-style="italic" font-weight="700" font-size="12.5">${STORE_NAME.toUpperCase()}</text>`;
});
$$("[data-stripe]").forEach(svg => {
  const cols = +svg.dataset.stripe, rows = 4, out = [];
  let seed = 9; const rnd = () => (seed = seed * 16807 % 2147483647) / 2147483647;
  for (let y = 0; y < rows; y++) for (let x = 0; x < cols; x++) {
    const t = x / cols; // solid at the start, dissolving into loose pixels at the end
    if (t > .55 && rnd() < (t - .55) / .45) continue;
    out.push([x + rows - 1 - y, y]); // slanted like a speed stripe
  }
  svg.setAttribute("viewBox", `0 0 ${(cols + rows) * 10} ${rows * 10}`); svg.innerHTML = cells(out);
});

// ---------- state: language and a one-line cart that survives page changes ----------
let lang = store.get("lang") || "en";
const EN = Object.fromEntries($$("[data-i18n]").map(el => [el.dataset.i18n, el.innerHTML]));
const T = k => (lang === "ar" ? AR[k] : EN[k] ?? EN2[k]) ?? EN2[k] ?? k;
// shortcut: the cart holds one line of the one product; make it a list when a second product arrives
let cart = null;
try { const c = JSON.parse(store.get("cart")); if (c && STOCK[c.size] > 0) cart = { size: c.size, qty: Math.min(10, STOCK[c.size], Math.max(1, c.qty | 0)) }; } catch {}
const saveCart = c => { cart = { ...c }; store.set("cart", JSON.stringify(cart)); };
const onRender = []; // each page adds what it needs to redraw on a language switch

function render() {
  const ar = lang === "ar";
  document.documentElement.lang = lang;
  document.documentElement.dir = ar ? "rtl" : "ltr";
  // innerHTML only receives the static strings above, never user input
  $$("[data-i18n]").forEach(el => { el.innerHTML = (ar ? AR : EN)[el.dataset.i18n] ?? EN[el.dataset.i18n] ?? EN2[el.dataset.i18n]; });
  $$("[data-i18n-alt]").forEach(el => { el.alt = T(el.dataset.i18nAlt); });
  $$("[data-i18n-label]").forEach(el => el.setAttribute("aria-label", T(el.dataset.i18nLabel)));
  $("#langBtn").textContent = ar ? "English" : "عربي";
  renderCart();
  onRender.forEach(f => f());
}

// ---------- cart panel: slides in from the header cart on every page ----------
const cartPanel = $("#cartPanel"), cpBody = $("#cpBody");
function renderCart() {
  $("#cartCount").textContent = cart ? cart.qty : 0;
  const egp = T("egp");
  // innerHTML only receives the static strings and numbers above, never user input
  cpBody.innerHTML = cart ? `
    <div class="cp-line">
      <img src="img/p-front.webp" alt="" width="72" height="108">
      <div class="cp-info"><b>${T("pName")}</b><span>${T("itemLine").replace("{s}", cart.size).replace("{q}", cart.qty)}</span>
        <div class="cp-qty"><button type="button" data-q="-1" aria-label="${T("less")}"${cart.qty <= 1 ? " disabled" : ""}>-</button><output aria-live="polite">${cart.qty}</output><button type="button" data-q="1" aria-label="${T("more")}"${cart.qty >= Math.min(10, STOCK[cart.size]) ? " disabled" : ""}>+</button></div>
        <button type="button" class="p-link" data-q="0">${T("remove")}</button></div>
      <b class="cp-price">${fmt(PRICE * cart.qty)} ${egp}</b>
    </div>
    <p class="sum-row"><span>${T("sub")}</span><b>${fmt(PRICE * cart.qty)} ${egp}</b></p>
    <p class="cp-note">${T("shipNote")}</p>
    <a href="checkout.html" class="btn btn-red btn-full">${T("checkoutBtn")}</a>`
    : `<p class="cp-empty">${T("emptyTitle")}</p><a href="product.html" class="btn btn-red btn-full">${T("emptyBtn")}</a>`;
}
cpBody.addEventListener("click", e => {
  const b = e.target.closest("[data-q]"); if (!b || !cart) return;
  const q = +b.dataset.q;
  if (q === 0) { cart = null; store.set("cart", ""); }
  else saveCart({ size: cart.size, qty: Math.min(10, STOCK[cart.size], Math.max(1, cart.qty + q)) });
  render();
  $(q === 0 ? "#cpBody .btn" : `#cpBody [data-q="${q}"]:not(:disabled)`)?.focus(); // keep focus inside the panel after it redraws
});
const openCart = () => { renderCart(); cartPanel.showModal(); };
$("#cartBtn").addEventListener("click", openCart);
$("#cartClose").addEventListener("click", () => cartPanel.close());
cartPanel.addEventListener("click", e => { if (e.target === cartPanel) cartPanel.close(); }); // a click on the dimmed backdrop lands on the dialog itself
$$("[data-store-name]").forEach(el => el.textContent = STORE_NAME);
$$("[data-price]").forEach(el => el.textContent = fmt(PRICE));
$$("[data-wa]").forEach(a => a.href = waLink("Hi, I have a question about the racing jacket."));

// header: announcement bar, menu drawer, language
$("#annClose").addEventListener("click", () => { $("#announce").hidden = true; });
const drawer = $("#drawer"), scrim = $("#scrim"), menuBtn = $("#menuBtn");
function menu(open) { drawer.hidden = scrim.hidden = !open; menuBtn.setAttribute("aria-expanded", open); }
menuBtn.addEventListener("click", () => menu(drawer.hidden));
scrim.addEventListener("click", () => menu(false));
drawer.addEventListener("click", e => { if (e.target.closest("a")) menu(false); });
addEventListener("keydown", e => { if (e.key === "Escape") menu(false); });
$("#langBtn").addEventListener("click", () => { lang = lang === "ar" ? "en" : "ar"; store.set("lang", lang); render(); });

// ---------- product page: gallery, sizes, add to cart ----------
if (page === "product") {
  const firstIn = SIZES.find(s => STOCK[s[0]] > 0) || SIZES[0];
  const sel = cart ? { ...cart } : { size: STOCK.M > 0 ? "M" : firstIn[0], qty: 1 };
  const pills = $("#sizesPick"), tower = $("#tower"), bbSize = $("#bbSize"), qtyEl = $("#qty");
  SIZES.forEach(([s, c, l], i) => {
    pills.insertAdjacentHTML("beforeend", `<button type="button" role="radio" data-size="${s}">${s}</button>`);
    tower.insertAdjacentHTML("beforeend", `<button type="button" class="row" role="radio" data-size="${s}"><span class="pos">${i + 1}</span><b>${s}</b><span>${c} cm</span><span>${l} cm</span></button>`);
    bbSize.add(Object.assign(new Option(s, s), { disabled: STOCK[s] <= 0 }));
  });
  const setSize = s => { if (STOCK[s] > 0) { sel.size = s; if (sel.qty > STOCK[s]) sel.qty = STOCK[s]; renderSel(); } };
  pills.addEventListener("click", e => { const b = e.target.closest("button:not(:disabled)"); if (b) setSize(b.dataset.size); });
  tower.addEventListener("click", e => { const b = e.target.closest("button"); if (b) setSize(b.dataset.size); });
  bbSize.addEventListener("change", () => setSize(bbSize.value));
  qtyEl.addEventListener("input", () => { sel.qty = Math.min(10, STOCK[sel.size], Math.max(1, parseInt(qtyEl.value, 10) || 1)); renderSel(); });

  function renderSel() {
    const [, c, l] = SIZES.find(x => x[0] === sel.size);
    $$("button", pills).forEach(b => { b.setAttribute("aria-checked", b.dataset.size === sel.size); b.disabled = STOCK[b.dataset.size] <= 0; });
    $$("button", tower).forEach(b => { b.dataset.on = b.dataset.size === sel.size; b.setAttribute("aria-checked", b.dataset.size === sel.size); });
    bbSize.value = sel.size; qtyEl.value = sel.qty; qtyEl.max = Math.min(10, STOCK[sel.size]);
    const left = STOCK[sel.size], st = $("#stock");
    st.textContent = left <= 0 ? T("stockOut") : left <= 5 ? T("stockLow").replace("{n}", left) : T("stockIn");
    st.classList.toggle("low", left <= 5);
    $("#addCart").disabled = $("#bbAdd").disabled = left <= 0; // everything sold out
    $("#vChest").textContent = `${c} cm`; $("#vLen").textContent = `${l} cm`;
    $(".size-fig").style.setProperty("--k", .88 + (c - 108) / 24 * .12); // line width follows chest size
    $("#askWa").href = waLink(`Hi, I have a sizing question about the racing jacket (size ${sel.size}).`);
  }
  onRender.push(renderSel);
  // the click car gets its lap, then the cart panel opens
  const addToCart = () => { saveCart(sel); renderCart(); setTimeout(openCart, reduce ? 0 : 720); };
  $("#addCart").addEventListener("click", addToCart);
  $("#bbAdd").addEventListener("click", addToCart);

  // gallery with loupe; ?v=2 opens on the third photo (the shop grid links this way)
  let gIdx = Math.min(GALLERY.length - 1, Math.max(0, parseInt(new URLSearchParams(location.search).get("v"), 10) || 0));
  const main = $("#mainImg"), stage = $("#stage"), loupe = $("#loupe"), ZOOM = 2.2;
  const label = k => lang === "ar" ? AR[k] : EN_LABELS[k];
  GALLERY.forEach(([src, k], i) => $("#thumbs").insertAdjacentHTML("beforeend",
    `<button type="button" role="tab" data-i="${i}" data-k="${k}"><img src="${src}" alt="" width="160" height="240" loading="lazy"></button>`));
  function show(i) {
    gIdx = i; main.src = GALLERY[i][0]; main.alt = label(GALLERY[i][1]);
    $$("#thumbs button").forEach((x, j) => x.setAttribute("aria-selected", j === i));
  }
  $("#thumbs").addEventListener("click", e => { const b = e.target.closest("button"); if (b) show(+b.dataset.i); });
  onRender.push(() => { $$("#thumbs button").forEach(b => b.setAttribute("aria-label", label(b.dataset.k))); show(gIdx); });
  function moveLoupe(e) {
    const r = stage.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top, half = loupe.offsetWidth / 2;
    loupe.style.left = x + "px"; loupe.style.top = y + "px";
    loupe.style.backgroundImage = `url(${main.currentSrc || main.src})`;
    loupe.style.backgroundSize = `${r.width * ZOOM}px ${r.height * ZOOM}px`; // photos fill the stage exactly (same 2:3 shape)
    loupe.style.backgroundPosition = `${half - x * ZOOM}px ${half - y * ZOOM}px`;
  }
  stage.addEventListener("pointermove", moveLoupe);
  stage.addEventListener("pointerdown", e => { if (e.pointerType !== "mouse") { stage.classList.add("touching"); moveLoupe(e); } });
  addEventListener("pointerup", () => stage.classList.remove("touching"));

  // phone: a buy bar appears once the main Add to cart button scrolls away
  const bar = $("#buyBar"); bar.hidden = false;
  new IntersectionObserver(([x]) => bar.classList.toggle("show", !x.isIntersecting)).observe($("#addCart"));
}

// ---------- checkout page: summary from the cart, then the order goes out on WhatsApp ----------
if (page === "checkout") {
  const gov = $("#f-gov"), form = $("#orderForm"), confirmBox = $("#confirm"), phone = $("#f-phone"), shot = $("#f-shot"), ipBox = $("#ipBox");
  const shipFee = () => gov.value ? (SHIP[gov.value] ?? SHIP.other) : null;
  const instapay = () => form.elements.pay.value === "instapay";
  function renderSum() {
    const keep = gov.value, ar = lang === "ar";
    gov.innerHTML = `<option value="">${ar ? "اختار" : "Choose"}</option>` + GOVS.map(([en, a]) => `<option value="${en}">${ar ? a : en}</option>`).join("");
    gov.value = keep;
    $("#order").hidden = !cart; $("#emptyCart").hidden = !!cart;
    if (!cart) return;
    const sub = PRICE * cart.qty, fee = shipFee(), egp = T("egp");
    $("#sumItem").textContent = T("itemLine").replace("{s}", cart.size).replace("{q}", cart.qty);
    $("#sumLine").textContent = `${fmt(sub)} ${egp}`;
    $("#sumSub").textContent = `${fmt(sub)} ${egp}`;
    $("#sumShip").textContent = fee === null ? T("pickGov") : `${fmt(fee)} ${egp}`;
    $("#sumTotal").textContent = `${fmt(sub + (fee ?? 0))} ${egp}`;
    // InstaPay: the amount to send is only known once delivery is
    ipBox.hidden = shot.disabled = !instapay();
    $("#ipId").textContent = INSTAPAY;
    $("#ipAmt").textContent = fee === null ? "-" : `${fmt(sub + fee)} ${egp}`;
    $("#copyAmt").disabled = fee === null; $("#ipHint").hidden = fee !== null;
  }
  onRender.push(renderSum);
  gov.addEventListener("change", renderSum);
  form.addEventListener("change", e => { if (e.target.name === "pay") renderSum(); });

  // copy buttons: the amount goes out as plain digits so banking apps accept the paste
  form.addEventListener("click", async e => {
    const b = e.target.closest("[data-copy]"); if (!b) return;
    const text = b.dataset.copy === "amount" ? String(PRICE * cart.qty + shipFee()) : INSTAPAY, note = $("#copied");
    let failed = false;
    try { await navigator.clipboard.writeText(text); } catch { failed = true; }
    note.textContent = T(failed ? "copyFail" : "copied"); note.classList.toggle("fail", failed);
    clearTimeout(note.t); note.t = setTimeout(() => { note.textContent = ""; }, 2500);
  });
  // screenshot: checked and previewed here; it never leaves the phone until the buyer shares it
  let shotUrl = "";
  shot.addEventListener("change", () => {
    const f = shot.files[0], ok = !f || (f.type.startsWith("image/") && f.size <= 10 * 1024 * 1024);
    shot.setCustomValidity(ok ? "" : "bad");
    URL.revokeObjectURL(shotUrl); shotUrl = f && ok ? URL.createObjectURL(f) : "";
    $("#shotPrev").src = shotUrl; $("#shotPrev").hidden = !shotUrl;
    shot.classList.toggle("invalid", !ok);
  });

  form.addEventListener("submit", e => {
    e.preventDefault();
    // accept Arabic-Indic digits, spaces and dashes: "٠١٠ ١٢٣٤-٥٦٧٨" becomes "01012345678"
    phone.value = phone.value.replace(/[٠-٩۰-۹]/g, d => d.charCodeAt(0) & 15).replace(/[\s-]/g, "");
    let ok = true;
    $$("input:not([type=radio]):not(:disabled), select, textarea", form).forEach(el => {
      const bad = !el.value.trim() || !el.checkValidity();
      el.classList.toggle("invalid", bad);
      if (bad && ok) { el.focus(); ok = false; }
    });
    if (!ok) return;
    const d = Object.fromEntries(new FormData(form)), fee = shipFee(), sub = PRICE * cart.qty, ip = instapay();
    const msg = [
      `New order - ${STORE_NAME}`, `Item: Racing Jacket, size ${cart.size} x ${cart.qty}`,
      `Name: ${d.name}`, `Phone: ${d.phone}`, `Governorate: ${d.gov}`, `Address: ${d.addr}`,
      `Subtotal: ${sub} EGP`, `Delivery: ${fee} EGP`, `Total: ${sub + fee} EGP`,
      ip ? `Payment: InstaPay, ${sub + fee} EGP sent to ${INSTAPAY}. Screenshot follows in this chat.` : `Payment: cash on delivery`,
    ].join("\n");
    // the browser can hand the screenshot to WhatsApp through the share sheet on most phones; elsewhere the buyer attaches it in the chat
    const file = ip && shot.files[0], canShare = !!(file && navigator.canShare?.({ files: [file] }));
    const done = () => {
      $("#confText").textContent = msg; $("#confBtn").href = waLink(msg);
      $("#ipSend").hidden = !ip; $("#shareShot").hidden = !canShare; $("#ipAttach").hidden = canShare;
      form.hidden = true; confirmBox.hidden = false; confirmBox.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "center" });
    };
    $("#shareShot").onclick = () => navigator.share({ files: [file], text: `InstaPay - ${d.name} - ${sub + fee} EGP` }).catch(() => {});
    if (reduce) return done();
    const dots = $$("#sendBtn .mini-lights i"); // five lights fill, then the order summary appears
    dots.forEach((el, i) => setTimeout(() => el.classList.add("on"), i * 160));
    setTimeout(() => { dots.forEach(el => el.classList.remove("on")); done(); }, 5 * 160 + 200);
  });
  $("#confEdit").addEventListener("click", () => { confirmBox.hidden = true; form.hidden = false; });
}

render();

// ---------- home: 360 turntable. Autoplay plays the video; drag, the arrow buttons and the coast after a flick seek it ----------
const vid = $("#spin");
if (vid) {
  const viewer = $("#viewer"), degEl = $("#deg"), seg = $("#seg"), toggle = $("#spinToggle"), toggleText = $("span", toggle);
  let auto = !reduce, held = false, onscreen = true, lastX = 0, lastT = 0, target = 0, v = 0, prev = 0, seeking = false, lastDeg = -1;
  const dur = () => vid.duration || 7.25; // one full turn, in seconds of video
  const wrap = t => (t % dur() + dur()) % dur();
  // every frame of spin.mp4 is a keyframe, so a seek decodes one frame; one seek in flight at a time keeps the jacket on the hand, not behind it
  const seek = () => { if (seeking || Math.abs(target - vid.currentTime) < .01) return; seeking = true; vid.currentTime = target; };
  vid.addEventListener("seeked", () => { seeking = false; if (held || v) seek(); });
  const run = () => { if (auto && onscreen && !held && !v) vid.play().catch(() => {}); else vid.pause(); };
  function tick(now) {
    const dt = Math.min((now - prev) / 1000, .05); prev = now;
    if (!held && v) { // coast after a flick or an arrow press, slowing smoothly
      target = wrap(target + v * dt); v *= Math.exp(-dt * 4);
      if (Math.abs(v) < .04) { v = 0; run(); }
      seek();
    }
    const deg = Math.round(vid.currentTime / dur() * 360) % 360;
    if (deg !== lastDeg) { lastDeg = deg; degEl.textContent = String(deg).padStart(3, "0") + "°"; seg.style.setProperty("--p", deg / 3.6 + "%"); }
    if (onscreen) requestAnimationFrame(tick);
  }
  viewer.addEventListener("pointerdown", e => {
    if (e.target.closest("button")) return;
    held = true; v = 0; lastX = e.clientX; lastT = e.timeStamp; target = vid.currentTime; run();
    viewer.classList.add("drag"); viewer.setPointerCapture(e.pointerId);
  });
  viewer.addEventListener("pointermove", e => {
    if (!held) return;
    const d = -(e.clientX - lastX) / viewer.offsetWidth * dur() * .7, dt = Math.max((e.timeStamp - lastT) / 1000, .008); // one width of drag = 0.7 of a turn
    target = wrap(target + d); v = .7 * (d / dt) + .3 * v; lastX = e.clientX; lastT = e.timeStamp;
    seek();
  });
  ["pointerup", "pointercancel"].forEach(ev => viewer.addEventListener(ev, e => {
    if (!held) return;
    held = false; viewer.classList.remove("drag");
    v = reduce || e.timeStamp - lastT > 90 ? 0 : Math.max(-dur() * 1.5, Math.min(dur() * 1.5, v)); // a hand that stopped before letting go does not coast
    run();
  }));
  // arrow buttons turn 45 degrees: the coast covers v / 4, so v = (turn / 8) * 4
  function turn(dir) {
    target = vid.currentTime;
    if (reduce) { v = 0; target = wrap(target + dir * dur() / 8); vid.pause(); seek(); return; }
    v = dir * dur() / 2; run();
  }
  $("#turnL").addEventListener("click", () => turn(1));
  $("#turnR").addEventListener("click", () => turn(-1));
  function setAuto(on) {
    auto = on; toggle.dataset.paused = !on;
    toggleText.dataset.i18n = on ? "pause" : "play"; toggleText.textContent = T(toggleText.dataset.i18n);
    run();
  }
  toggle.addEventListener("click", () => setAuto(!auto));
  onRender.push(() => vid.setAttribute("aria-label", T("spinLabel")));
  vid.setAttribute("aria-label", T("spinLabel"));
  new IntersectionObserver(([x]) => { const was = onscreen; onscreen = x.isIntersecting; run(); if (onscreen && !was) requestAnimationFrame(t => { prev = t; tick(t); }); }).observe(viewer);
  setAuto(auto);
  requestAnimationFrame(t => { prev = t; tick(t); });
  // hold the whole clip (1.1 MB) in memory: seeking then works on any server, even ones that ignore range requests, and never waits on the network
  fetch(vid.dataset.src).then(r => r.ok ? r.blob() : Promise.reject()).then(b => URL.createObjectURL(b), () => vid.dataset.src)
    .then(src => { vid.src = src; run(); });

  // phone: the hero buttons sit where the WhatsApp button would, so it waits until the hero scrolls away
  new IntersectionObserver(([x]) => document.body.classList.toggle("hero-on", x.isIntersecting)).observe($(".hero"));
}

// ---------- pixel track: all cars, list doubled so the loop is seamless ----------
const lane = $("#lane");
if (lane) lane.innerHTML = [...ALL_CARS, ...ALL_CARS].map(c => `<img src="img/cars/${c}.png" alt="" loading="lazy">`).join("");

// ---------- click car: a pixel car drives from the start of any button to its end ----------
SMALL_CARS.forEach(c => { new Image().src = `img/cars/${c}.png`; }); // warm the cache so the first click has a car
// ---------- logo pop: plain links (no car on them) flash a pixel team logo on a little notched tile ----------
// [sprite in img/logos, tile colour for logos that are white or dark]
const LOGOS = [["mercedes", "#383838"], ["redbull", "#0b1a2e"], ["pirelli"], ["alpine"], ["haas"], ["f1"], ["ferrari"], ["williams"], ["mclaren", "#383838"]];
LOGOS.forEach(([n]) => { new Image().src = `img/logos/${n}.png`; });
function logoPop(a) {
  const [n, tile] = LOGOS[Math.random() * LOGOS.length | 0], r = a.getBoundingClientRect(), size = Math.max(44, Math.min(72, r.height * 2.2));
  const pop = document.createElement("span"); pop.className = "logo-pop"; pop.innerHTML = `<img src="img/logos/${n}.png" alt="">`;
  pop.style.cssText = `left:${r.left + r.width / 2}px;top:${r.top + r.height / 2}px;width:${size}px;height:${size}px${tile ? `;background:${tile}` : ""}`;
  (a.closest("dialog[open]") || document.body).append(pop); // inside the open cart panel, or it would sit under it
  a.classList.add("racing");
  // stepped timing so it snaps like pixel art: pop in past full size, settle, then lift away
  return pop.animate([
    { transform: "translate(-50%, -50%) scale(.2)", opacity: 1 },
    { transform: "translate(-50%, -50%) scale(1.15)", opacity: 1, offset: .3 },
    { transform: "translate(-50%, -50%) scale(1)", opacity: 1, offset: .45 },
    { transform: "translate(-50%, -50%) scale(1)", opacity: 1, offset: .75 },
    { transform: "translate(-50%, -110%) scale(1)", opacity: 0 },
  ], { duration: 760, easing: "steps(10, end)" }).finished.finally(() => { pop.remove(); a.classList.remove("racing"); });
}

document.addEventListener("click", e => {
  const b = e.target.closest("button, .btn"), a = !b && e.target.closest("a[href]"), el = b || a;
  if (!el || reduce || el.classList.contains("racing")) return;
  // anything that opens another page waits for its animation to finish
  const go = el.matches("a[href]") && !el.target && !e.ctrlKey && !e.metaKey && !e.shiftKey && !el.getAttribute("href").startsWith("#");
  if (go) e.preventDefault();
  if (a) { logoPop(a).then(() => { if (go) location.href = a.href; }); return; }
  const w = b.offsetWidth, h = b.offsetHeight, ch = Math.max(14, Math.min(h * .62, 32)), cw = ch * 240 / 90;
  const rtl = document.documentElement.dir === "rtl", flip = rtl ? 1 : -1; // sprites face left: mirror them to drive right
  const car = new Image(); car.src = `img/cars/${SMALL_CARS[Math.random() * SMALL_CARS.length | 0]}.png`; car.alt = ""; car.className = "btn-car";
  car.style.height = ch + "px"; car.style.top = (h - ch) / 2 + "px";
  const trail = document.createElement("i"); trail.className = "btn-trail";
  trail.style.top = (h - ch) / 2 + ch * .5 + "px"; trail.style.height = Math.max(3, ch * .22) + "px";
  if (getComputedStyle(b).position === "static") b.style.position = "relative";
  b.classList.add("racing"); b.append(trail, car);
  const ease = "cubic-bezier(.5, 0, .25, 1)", ms = 700;
  trail.animate([{ transform: "scaleX(0)", opacity: .55 }, { transform: "scaleX(1)", opacity: .55, offset: .8 }, { transform: "scaleX(1)", opacity: 0 }], { duration: ms, easing: ease });
  car.animate([{ transform: `translateX(${rtl ? w : -cw}px) scaleX(${flip})` }, { transform: `translateX(${rtl ? -cw : w}px) scaleX(${flip})` }], { duration: ms, easing: ease })
    .finished.finally(() => { car.remove(); trail.remove(); b.classList.remove("racing"); if (go) location.href = b.href; });
});

// ---------- loader (home only): text scramble, countdown 5..1, lights out ----------
const lights = $("#lights");
function scramble(el, ms = 700) {
  const text = el.textContent, pool = "!<>-_/[]{}=+*^?#";
  if (reduce) return;
  const t0 = performance.now();
  (function step(now) {
    const p = Math.min((now - t0) / ms, 1);
    el.textContent = [...text].map((c, i) => c === " " || i < p * text.length ? c : pool[Math.random() * pool.length | 0]).join("");
    if (p < 1) requestAnimationFrame(step);
  })(t0);
}
if (lights) {
  const count = $("#count"), pct = $("#pct"), STEP = 700, timers = [];
  const lightsOut = () => { timers.forEach(clearTimeout); lights.classList.add("gone"); setTimeout(() => lights.remove(), 900); };
  let seen = false; try { seen = sessionStorage.getItem("lights"); sessionStorage.setItem("lights", 1); } catch {}
  if (reduce || seen) lights.remove();
  else {
    const bulbs = $$(".gantry i");
    scramble($(".count-label"));
    bulbs.forEach((el, i) => timers.push(setTimeout(() => {
      el.classList.add("on");
      count.textContent = 5 - i;
      pct.textContent = String((i + 1) * 20 - 5).padStart(2, "0") + "%";
      count.classList.remove("tick"); void count.offsetWidth; count.classList.add("tick");
    }, 250 + i * STEP)));
    const hold = 250 + 5 * STEP + 300 + Math.random() * 500; // real starts hold the lights a random beat
    timers.push(setTimeout(() => { bulbs.forEach(el => el.classList.remove("on")); lights.classList.add("out"); $("#go").classList.add("on"); scramble($("#go"), 500); pct.textContent = "100%"; }, hold));
    timers.push(setTimeout(lightsOut, hold + 800));
    $("#skipIntro").addEventListener("click", lightsOut);
  }
}
