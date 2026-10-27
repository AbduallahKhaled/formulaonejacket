// Live settings come from the admin through api/config.php (window.STORE); the values after ?? are the fallback
// for when the page is opened without the PHP backend.
const S = window.STORE || {};
const LIVE = !!window.STORE; // orders and discount codes go to the server only when it is there
const STORE_NAME = "Formula One Jacket";
const PRICE = S.price ?? 850;              // EGP
const WAS = S.was ?? 1250;
const OFFER_LEFT = S.offerLeft ?? 0, OFFER_OF = S.offerOf ?? 10; // launch price: orders still open, out of how many                    // old price in EGP; above PRICE it shows a "Sale" tag and strikes the old price
const WA_NUMBER = S.wa ?? "201557707691";  // country code + number, no "+"
const INSTAPAY = S.instapay ?? "vampotypebeats@instapay"; // empty when InstaPay is turned off in the admin
const PAY_COD = S.cod ?? true;
const SIZES = S.sizes ?? [["S", 108, 66], ["M", 114, 68], ["L", 120, 70], ["XL", 126, 72], ["XXL", 132, 74]];
// the jackets: ids match FJ_PRODUCTS in lib.php. All share PRICE and SIZES. Photos are img/<img>-front|angle|side|back.webp
const PRODUCTS = [
  { id: "classic", name: ["Classic Racing Jacket", "جاكيت سباقات كلاسيك"], sub: ["Black, white and red", "أسود وأبيض وأحمر"], short: ["Classic", "كلاسيك"], img: "p", spin: "img/spin.mp4", poster: "img/spin-poster.jpg", chip: ["#111", "#f4f1ea", "#d71920"] },
  { id: "red", name: ["Red Racing Jacket", "جاكيت سباقات أحمر"], sub: ["Red with a black band", "أحمر بشريط أسود"], short: ["Red", "أحمر"], img: "red", spin: "img/spin-red.mp4", poster: "img/spin-red.jpg", chip: ["#d71920", "#111"] },
  { id: "black", name: ["Black Racing Jacket", "جاكيت سباقات أسود"], sub: ["Black with a red band", "أسود بشريط أحمر"], short: ["Black", "أسود"], img: "black", spin: "img/spin-black.mp4", poster: "img/spin-black.jpg", chip: ["#111", "#d71920"] },
];
const prod = id => PRODUCTS.find(p => p.id === id);
// free to sell per "jacket:size" (shelf stock minus open orders)
const STOCK = S.stock ?? Object.fromEntries(PRODUCTS.flatMap(p => Object.entries({ S: 4, M: 12, L: 9, XL: 2, XXL: 0 }).map(([s, n]) => [`${p.id}:${s}`, n])));
const left = (p, s) => STOCK[`${p}:${s}`] ?? 0;
const SHIP = S.ship ? { ...S.ship, other: S.shipOther } : { other: 90 }; // EGP by governorate
const MAXQ = S.maxQty ?? 10;
const REVIEWS = S.reviews ?? [];

const GOVS = [
  ["Cairo", "القاهرة"], ["Giza", "الجيزة"], ["Alexandria", "الإسكندرية"], ["Qalyubia", "القليوبية"],
  ["Sharqia", "الشرقية"], ["Dakahlia", "الدقهلية"], ["Gharbia", "الغربية"], ["Monufia", "المنوفية"],
  ["Beheira", "البحيرة"], ["Kafr El Sheikh", "كفر الشيخ"], ["Damietta", "دمياط"], ["Port Said", "بورسعيد"],
  ["Ismailia", "الإسماعيلية"], ["Suez", "السويس"], ["Faiyum", "الفيوم"], ["Beni Suef", "بني سويف"],
  ["Minya", "المنيا"], ["Asyut", "أسيوط"], ["Sohag", "سوهاج"], ["Qena", "قنا"], ["Luxor", "الأقصر"],
  ["Aswan", "أسوان"], ["Red Sea", "البحر الأحمر"], ["New Valley", "الوادي الجديد"], ["Matrouh", "مطروح"],
  ["North Sinai", "شمال سيناء"], ["South Sinai", "جنوب سيناء"],
];

// product photos, in gallery order: [file part, label key]
const VIEWS = [["front", "vFront"], ["angle", "vAngle"], ["side", "vSide"], ["back", "vBack"]];
const photo = (p, v = "front") => `img/${p.img}-${v}.webp`;
const EN_LABELS = { vFront: "Front", vAngle: "Three-quarter", vSide: "Side", vBack: "Back" };

// pixel cars: small sprites ride on button clicks, all of them run in the track
const SMALL_CARS = ["red01", "navy05", "blue06", "test", "blue01", "yellow02"];
const ALL_CARS = [...SMALL_CARS, "navy25", "red23"];

const AR = {
  skip: "تخطي", raceStart: "انطلاق السباق", go: "انطلق!",
  annText: "الدفع عند الاستلام في كل مصر. جرّبه مع المندوب.", cartWord: "السلة", navWa: "واتساب",
  navShop: "المتجر", navFit: "المقاسات", navDelivery: "التوصيل", navFaq: "أسئلة",
  h1a: "إرث السباقات.", h1b: "طاقة الشارع.",
  heroSub: "جواكت سباقات مبطنة بالأحمر أو الأسود أو الكلاسيك، كل شعار فيها متطرز. ادفع كاش لما توصلك.",
  shopBtn: "تسوق الجواكت", viewDetails: "شوف التفاصيل", findFit: "اعرف مقاسك", egp: "ج.م", drag: "اسحب للدوران",
  pause: "إيقاف", play: "تشغيل", spinLabel: "الجاكيت من كل الزوايا. اسحب للدوران.",
  f1t: "تفاصيل الأمام والخلف", f1s: "صور من أربع زوايا", f2t: "اعرف مقاسك", f2s: "الصدر والطول لكل مقاس", f3t: "التوصيل والدفع", f3s: "كاش عند الاستلام، الشحن خلال ٤-٥ أيام عمل",
  shopTitle: "تسوق الجواكت", shopSub: "تلات جواكت سباقات بسعر واحد. اختار اللون وبعدين المقاس.", chooseSize: "اختار المقاس", sale: "خصم",
  whyTitle: "ليه تشتري مننا", whyAlt: "ليه تشتري مننا: منتجات بجودة عالية، توصيل سريع، دفع آمن، وإرجاع سهل.",
  size: "المقاس", sizeGuide: "جدول المقاسات", fQty: "الكمية",
  delEstT: "التوصيل", delEst: "الشحن خلال ٤-٥ أيام عمل، كل المحافظات", payT: "الدفع", payNote: "الدفع عند الاستلام أو إنستاباي",
  addCart: "أضف للسلة", askSize: "اسأل عن المقاس على واتساب",
  fitTitle: "المقاس والتفاصيل", sizeNote: "قصة واسعة شوية. لو محتار بين مقاسين خد الأصغر.", chest: "الصدر", length: "الطول",
  delTitle: "التوصيل والإرجاع",
  dv1t: "التغطية", dv1: "كل المحافظات الـ ٢٧.", dv2t: "الشحن", dv2: "٩٠ ج.م. لأي محافظة في مصر.",
  dv3t: "المدة", dv3: "الشحن خلال ٤ لـ ٥ أيام عمل بعد ما نأكد طلبك على واتساب.", dv4t: "الدفع", dv4: "الدفع عند الاستلام: افتح الشحنة مع المندوب وبعدين ادفع. أو ادفع مقدماً بإنستاباي وابعتلنا صورة التحويل.",
  dv5t: "الإرجاع", dv5: "جرّبه مع المندوب، ولو المقاس مش مظبوط رجّعه معاه على طول. استرداد أو استبدال خلال يومين من الاستلام. <a href=\"policies.html#returns\">سياسة الإرجاع كاملة</a>.",
  orderTitle: "إتمام الطلب", orderSub: "أربع خانات وطلبك يوصلنا على واتساب. بنأكد ونشحن وانت تدفع عند الاستلام.",
  sumTitle: "طلبك", sub: "المجموع", ship: "الشحن", total: "الإجمالي", editCart: "غيّر الجواكت أو المقاسات",
  emptyTitle: "السلة فاضية", emptySub: "اختار الجاكيت والمقاس من المتجر وارجع هنا.", emptyBtn: "شوف الجواكت",
  fName: "الاسم بالكامل", eName: "اكتب اسمك", fPhone: "رقم الموبايل", ePhone: "اكتب رقم موبايل مصري صحيح",
  fGov: "المحافظة", eGov: "اختار محافظتك", fAddr: "العنوان", eAddr: "اكتب عنوانك",
  payTitle: "طريقة الدفع", payCod: "الدفع عند الاستلام", payCodNote: "ادفع للمندوب لما الشحنة توصل.",
  send: "ابعت الطلب على واتساب",
  confTitle: "الطلب جاهز", confSub: "ابعته على واتساب عشان نأكده. الطلب مش متسجل غير لما نرد عليك.", confBtn: "ابعت على واتساب", confEdit: "عدّل الطلب",
  faqTitle: "أسئلة.", q2: "أقدر أشوفه قبل ما أدفع؟", a2: "أيوه. افتح الشحنة مع المندوب وبعدين ادفع.",
  q3: "ده منتج رسمي؟", a3: "لا. ده جاكيت بستايل سباقات كلاسيكي للمعجبين، مش منتج رسمي لأي فريق أو بطولة.",
  q4: "المقاس طلع غلط؟", a4: "جرّبه والمندوب موجود ورجّعه معاه على طول، ونبعتلك المقاس الصح. أو كلمنا خلال يومين من الاستلام.",
  q5: "أعرف منين إن طلبي اتسجل؟", a5: "بنرد عليك على واتساب نأكد المقاس والعنوان وسعر الشحن. وبعدين نشحن.",
  footNote: "متجر مستقل. غير تابع للفورمولا ١ أو الاتحاد الدولي للسيارات أو أي فريق سباقات.",
  vFront: "أمامي", vAngle: "زاوية", vSide: "جانبي", vBack: "خلفي",
  stockIn: "متوفر", stockLow: "باقي {n} بس", stockOut: "خلص", pickGov: "اختار المحافظة",
  itemLine: "المقاس {s} × {q}", turnL: "لف لليسار", turnR: "لف لليمين", pickJacket: "اختار الجاكيت",
  cartTitle: "السلة", close: "إغلاق", remove: "شيل", less: "قلّل", more: "زوّد", shipNote: "الشحن بيتحسب في صفحة الدفع.", checkoutBtn: "كمّل الطلب",
  payIpNote: "ادفع دلوقتي من تطبيق البنك وابعتلنا صورة التحويل.", ipTo: "حوّل على", ipAmount: "المبلغ", copy: "نسخ",
  copied: "اتنسخ", copyFail: "النسخ مش شغال. علّم على الرقم وانسخه.", ipGovFirst: "اختار المحافظة الأول عشان المبلغ يشمل الشحن.",
  fShot: "صورة التحويل", eShot: "ضيف صورة تحويل إنستاباي (صورة لحد ١٠ ميجا)", ipStep: "بعد كده ابعت صورة التحويل في نفس الشات.",
  shareShot: "ابعت صورة التحويل", ipAttach: "في واتساب دوس على علامة المشبك وابعت الصورة.",
  fCode: "كود الخصم (لو معاك)", apply: "تطبيق", disc: "الخصم", codeOk: "تم تطبيق خصم {n} ج.م", codeBad: "الكود ده مش شغال. اتأكد منه.",
  codeOld: "الكود ده خلص أو انتهى.", codeMin: "الكود ده بيشتغل على طلبات من {n} ج.م وأكتر.", tryLater: "محاولات كتير. استنى دقايق وجرب تاني.",
  sending: "جاري الإرسال…", errStock: "باقي {n} بس من مقاس {s}. قلّل الكمية من السلة.", errCode: "كود الخصم مبقاش شغال. شيله وجرب تاني.",
  errOrder: "حصلت مشكلة في الطلب. راجع البيانات، أو كلمنا على واتساب.", confTitleId: "طلب {id} اتسجل",
  confSubLive: "طلبك وصلنا. ابعته على واتساب كمان عشان نأكده أسرع.", shotAlt: "صورة التحويل اللي اخترتها",
  revTitle: "آراء العملاء", stars: "{n} من ٥ نجوم",
  navAff: "اشتغل معانا كمسوّق", afTitle: "اشتغل معانا كمسوّق", afLede: "انشر عن جواكتنا، وادّي متابعينك كود خاص بيك، واكسب على كل طلب يوصل بالكود بتاعك.",
  afHowT: "إزاي بتشتغل", afHow1: "املا الفورم. بنقرا كل طلب وبنرد على واتساب.", afHow2: "لما نوافق، بتاخد كود خاص بيك وبنتفق معاك على العمولة.",
  afHow3: "شارك الكود في البوستات والستوريز والفيديوهات. متابعينك بيكتبوه في صفحة الدفع.", afHow4: "بتكسب على كل طلب بالكود بتاعك يتسلم ويفضل مع صاحبه. بنحوّلك على الحساب اللي تدّيهولنا.",
  afRulesT: "القواعد", afRule1: "العمولة بتتحسب على الطلبات اللي اتسلمت بس. الطلبات الملغية أو المرتجعة أو المستردة مش بتتحسب.",
  afRule2: "الكود لمتابعينك. الطلبات اللي بتعملها لنفسك بيه مش بتاخد عليها عمولة.", afRule3: "متقولش إن الجواكت منتجات رسمية لفورمولا ١ أو لأي فريق. هي مش كده.",
  afRule4: "ممنوع السبام والريفيوهات المزيفة والإعلانات الممولة على اسم المتجر.", afRule5: "من حقنا نوقف الشراكة لو القواعد دي اتكسرت.",
  afYou: "بياناتك", afPhone: "رقم الواتساب", afEmail: "الإيميل (اختياري)", afEEmail: "راجع الإيميل",
  afWhere: "بتنشر فين", afPlatform: "المنصة الأساسية", afChoose: "اختار", afOther: "حاجة تانية", afEPick: "اختار واحدة",
  afLink: "لينك حسابك الأساسي", afELink: "حط اللينك كامل، يبدأ بـ https://", afMore: "حسابات تانية (اختياري)",
  afFollowers: "عدد المتابعين على حسابك الأساسي", afPlan: "هتروّج للجواكت إزاي؟", afEPlan: "قولنا في جملة أو اتنين",
  afPay: "استلام الفلوس", afMethod: "طريقة التحويل", afBank: "تحويل بنكي", afAccount: "حساب التحويل (عنوان إنستاباي أو رقم المحفظة أو IBAN)",
  afEAccount: "اكتب هنحوّلك فلوسك فين", afCode: "الكود اللي تحب تاخده (اختياري)", afECode: "من ٣ لـ ١٥ حرف أو رقم إنجليزي، من غير مسافات",
  afAgree: "قريت القواعد وموافق عليها.", afEAgree: "لازم توافق على القواعد الأول.", afSend: "ابعت الطلب",
  afDoneT: "طلبك وصل", afDone: "شكراً. بنقرا كل طلب وبنرد على واتساب على الرقم اللي كتبته.", afExists: "فيه طلب بالرقم ده بالفعل. هنرد عليك على واتساب.",
  navReturns: "الإرجاع والاستبدال", navShipping: "الشحن", navPrivacy: "الخصوصية", policyWa: "اسألنا على واتساب",
  rpTitle: "الإرجاع والاسترداد", rp1t: "جرّبه الأول", rp1: "راجع جدول المقاسات قبل ما تطلب. لما الشحنة توصل، افتحها وجرّب الجاكيت والمندوب موجود. لو المقاس مش مظبوط، رجّعه مع المندوب على طول ونبعتلك المقاس الصح.",
  rp2t: "خلال يومين", rp2: "تقدر تطلب استرداد أو استبدال خلال يومين من استلام طلبك. ابعتلنا على واتساب برقم الطلب.",
  rp3t: "الحالة", rp3: "الجاكيت لازم يكون مش مستخدم، بحالته الأصلية وفي تغليفه الأصلي.",
  rp4t: "المدة", rp4: "بعد ما نوافق على طلبك، الاسترداد أو الاستبدال بيتم خلال ٥ أيام عمل.",
  rp5t: "الاستبدال", rp5: "تقدر تغيّر المقاس أو لون الجاكيت. الاستبدال بمنتج تاني مش متاح.",
  rp6t: "المصاريف", rp6: "مصاريف الشحن مش بترجع. فيه رسوم خدمة ٧٥ ج.م بتتخصم من المبلغ المسترد، أو بتتدفع في حالة الاستبدال.",
  spTitle: "الشحن", sp1t: "فين", sp1: "كل محافظات مصر الـ ٢٧. مش بنشحن برا مصر.",
  sp2t: "المصاريف", sp2: "٩٠ ج.م للطلب، لأي محافظة. بتشوفها في صفحة الدفع قبل ما تطلب.",
  sp3t: "المدة", sp3: "الطلبات بتتشحن خلال ٤ لـ ٥ أيام عمل بعد ما نأكدها معاك على واتساب.",
  sp4t: "الدفع", sp4: "الدفع عند الاستلام: افتح الشحنة مع المندوب وبعدين ادفع. أو ادفع مقدماً بإنستاباي وابعتلنا صورة التحويل.",
  sp5t: "مشكلة؟", sp5: "فيه حاجة غلط في طلبك؟ كلمنا على واتساب ونحلها.",
  sp6t: "الاسترداد", sp6: "مصاريف الشحن مش بترجع ومش بتتحول لرصيد في المتجر.",
  ppTitle: "الخصوصية", pp1t: "بنحتفظ بإيه", pp1: "لما تطلب: اسمك ورقم موبايلك ومحافظتك وعنوانك والجواكت اللي اخترتها، ولو دفعت بإنستاباي صورة التحويل. لما تعمل حساب: اسمك ورقمك وكلمة السر. كلمة السر بتتخزن متشفرة، محدش يقدر يقراها ولا حتى إحنا.",
  pp2t: "في المتصفح", pp2: "السلة واللغة بيتحفظوا في متصفحك. فيه كوكي تسجيل دخول بيفضّلك مسجل لحد ٣٠ يوم. وعشان نمنع الطلبات المزعجة وتخمين كلمات السر، بنحتفظ لفترة قصيرة بسجل متشفر لعنوان الإنترنت اللي جاي منه كل طلب.",
  pp3t: "ليه", pp3: "عشان نوصلك طلبك، ونأكده معاك على واتساب، ونطبق كود الخصم، ونمنع إساءة الاستخدام. مش بنستخدم أدوات تتبع إعلانات ولا تحليلات، ومش بنبيع بياناتك أبداً.",
  pp4t: "مين بيشوفها", pp4: "فريقنا، والمندوب اللي بياخد اسمك ورقمك وعنوانك عشان يوصل الشحنة. لو بعت طلبك على واتساب، واتساب هو اللي بينقل الرسالة. الخطوط في الموقع بتتحمل من Google Fonts، واللي بتشوف عنوان الإنترنت بتاعك.",
  pp5t: "لحد إمتى", pp5: "بنحتفظ بسجلات الطلبات لحساباتنا. تقدر تطلب مننا على واتساب نمسح حسابك أو بياناتك، وهنمسحها، ما عدا اللي القانون بيلزمنا نحتفظ بيه.",
  pp6t: "التعديلات", pp6: "لو غيّرنا الصفحة دي، النسخة الجديدة بتسري من يوم ما تتنشر هنا. آخر تحديث ٢٧ أكتوبر ٢٠٢٦.",
  offerLeft: "سعر الإطلاق: فاضل {n} من {m} طلبات", signBtn: "دخول", signTitle: "حسابك",
  signSub: "اعمل حساب وخد خصم ١٠٪ على أول طلب.", signGo: "اعمل الحساب", newAcct: "حساب جديد", signIn: "تسجيل الدخول",
  fPass: "كلمة السر", signDone: "كود الخصم ١٠٪ بتاعك", signSaved: "هيتحط لوحده في صفحة الدفع وانت مسجل دخول. اطلب بنفس الرقم.",
  signUsed: "الكود ده اتستخدم في طلب قبل كده.", signHi: "أهلاً {n}", signOutBtn: "خروج", myAcct: "حسابي",
  ePass: "كلمة السر لازم تكون ٦ حروف أو أكتر.", eLogin: "الرقم أو كلمة السر غلط.", eExists: "فيه حساب بالرقم ده. سجل دخول.", errPhoneCode: "كود التسجيل بيشتغل بس مع الرقم اللي سجلت بيه.",
};
// English strings that only JS uses; everything else is read from the page itself
const EN2 = { stockIn: "In stock", stockLow: "Only {n} left", stockOut: "Sold out", pickGov: "Choose a governorate", itemLine: "Size {s} x {q}",
  play: "Play", spinLabel: "Racing jacket, 360 degree view. Drag to turn.", sale: "Sale", turnL: "Turn left", turnR: "Turn right", pickJacket: "Choose a jacket",
  close: "Close", remove: "Remove", less: "One fewer", more: "One more", shipNote: "Delivery is added at checkout.", checkoutBtn: "Checkout",
  sub: "Subtotal", egp: "EGP", emptyTitle: "Your cart is empty", emptyBtn: "See the jackets",
  copied: "Copied", copyFail: "Copy did not work. Select the text and copy it.", ipAttach: "In WhatsApp, tap the paperclip and attach the screenshot.",
  whyAlt: "Why shop with us: premium quality products, fast and reliable delivery, secure payment, easy return policy.",
  codeOk: "Discount of {n} EGP applied", codeBad: "That code doesn't work. Check the spelling.", codeOld: "That code has expired or is used up.",
  codeMin: "That code works on orders from {n} EGP.", tryLater: "Too many tries. Wait a few minutes, then try again.", sending: "Sending…",
  errStock: "Only {n} left in size {s}. Lower the quantity in your cart.", errCode: "The discount code stopped working. Remove it and try again.",
  errOrder: "Something went wrong with the order. Check the form, or message us on WhatsApp.", confTitle: "Order ready", confTitleId: "Order {id} received",
  confSub: "Send it in WhatsApp so we can confirm it. Your order is not placed until we reply.",
  confSubLive: "We have your order. Send it on WhatsApp too, so we can confirm it faster.", shotAlt: "Your payment screenshot",
  stars: "{n} out of 5 stars", offerLeft: "Launch price: {n} of {m} orders left",
  myAcct: "Account", signHi: "Hi {n}", signSaved: "It is added at checkout while you are signed in. Order with the same phone number.",
  signUsed: "This code was already used on an order.", ePass: "The password needs 6 characters or more.",
  eLogin: "Wrong phone number or password.", eExists: "There is already an account with this number. Sign in instead.",
  afEAgree: "Tick the box to agree to the rules first.", afExists: "There is already an application with this number. We will reply on WhatsApp.",
  errPhoneCode: "That sign-up code only works with the phone number you signed up with." };

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
  <p data-i18n="annText">Cash on delivery across Egypt. Try it on with the courier.</p>
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
    <button type="button" class="sign" id="signBtn" aria-haspopup="dialog" aria-controls="signPanel" hidden data-i18n="signBtn">Sign in</button>
    <button type="button" class="cart" id="cartBtn" aria-haspopup="dialog" aria-controls="cartPanel"><span data-i18n="cartWord">Cart</span> <b id="cartCount">0</b></button>
  </div>
</header>
<dialog class="cart-panel" id="cartPanel" aria-labelledby="cartTitle">
  <div class="cp-head"><h2 id="cartTitle" data-i18n="cartTitle">Your cart</h2><button type="button" class="cp-x" id="cartClose" data-i18n-label="close" aria-label="Close">&times;</button></div>
  <div class="cp-body" id="cpBody"></div>
</dialog>
<dialog class="cart-panel" id="signPanel" aria-labelledby="signTitle">
  <div class="cp-head"><h2 id="signTitle" data-i18n="signTitle">Your account</h2><button type="button" class="cp-x" id="signClose" data-i18n-label="close" aria-label="Close">&times;</button></div>
  <div class="cp-body">
    <div id="signOut">
      <p class="sign-pitch" data-i18n="signSub">Create an account and get 10% off your first order.</p>
      <div class="sign-tabs" role="group"><button type="button" data-mode="register" aria-pressed="true" data-i18n="newAcct">Create account</button><button type="button" data-mode="login" aria-pressed="false" data-i18n="signIn">Sign in</button></div>
      <form id="signForm" class="sign-form" novalidate>
        <label for="s-name" class="for-new" data-i18n="fName">Full name</label><input id="s-name" class="for-new" name="name" autocomplete="name" maxlength="80">
        <label for="s-phone" data-i18n="fPhone">Mobile number</label><input id="s-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" required maxlength="20" placeholder="01xxxxxxxxx">
        <label for="s-pass" data-i18n="fPass">Password</label><input id="s-pass" name="password" type="password" autocomplete="new-password" required minlength="6" maxlength="200">
        <p class="sign-err" id="signErr" role="alert" hidden></p>
        <button class="btn btn-red btn-full" id="signGo" data-i18n="signGo">Create account</button>
      </form>
    </div>
    <div id="signIn" hidden>
      <p class="sign-hi" id="signHi"></p>
      <p data-i18n="signDone">Your 10% code</p><p class="sign-code" id="signCode" dir="ltr"></p>
      <p id="signNote"></p>
      <button type="button" class="btn btn-ghost btn-full" id="signOutBtn" data-i18n="signOutBtn">Sign out</button>
    </div>
  </div>
</dialog>
<div class="drawer" id="drawer" hidden><nav aria-label="Menu">${links}<a href="#" data-wa data-i18n="navWa">WhatsApp</a></nav></div>
<div class="scrim" id="scrim" hidden></div>`);
document.body.insertAdjacentHTML("beforeend", `
<footer class="foot">
  <svg class="stripe" data-stripe="60" aria-hidden="true"></svg>
  <svg class="mark" data-mark role="img" aria-label="Formula One Jacket"></svg>
  <nav class="foot-links" aria-label="Footer">${links}<a href="#" data-wa data-i18n="navWa">WhatsApp</a></nav>
  <nav class="foot-links foot-legal" aria-label="Policies"><a href="policies.html#returns" data-i18n="navReturns">Returns and refunds</a><a href="policies.html#shipping" data-i18n="navShipping">Shipping</a><a href="policies.html#privacy" data-i18n="navPrivacy">Privacy</a><a href="affiliate.html" data-i18n="navAff">Become an affiliate</a></nav>
  <p class="foot-note" data-i18n="footNote">Independent store. Not affiliated with Formula 1, the FIA or any racing team.</p>
</footer>
<a class="wa-float" data-wa href="#" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 32 32" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3Zm0 23.7c-2 0-3.9-.5-5.6-1.5l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7Zm5.9-8c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2l-1 1.2c-.2.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.2 1.4 3.5c.2.2 2.4 3.6 5.8 5 2.1.9 3 1 4 .8.7-.1 1.9-.8 2.2-1.5.3-.8.3-1.4.2-1.5-.1-.2-.3-.2-.6-.4Z"/></svg>
</a>`);

// ---------- shop grid: one card per jacket, the back photo shows on hover ----------
$$("[data-grid]").forEach(ul => {
  const off = WAS > PRICE ? Math.round((1 - PRICE / WAS) * 100) : 0;
  ul.innerHTML = PRODUCTS.map(p => `<li><a class="card" href="product.html?p=${p.id}">
    <span class="card-img"><img src="${photo(p)}" alt="" width="1024" height="1536" loading="lazy"><img class="alt" src="${photo(p, "back")}" alt="" width="1024" height="1536" loading="lazy"></span>
    <span class="card-tag">${off ? `<span data-i18n="sale">Sale</span> ${off}%` : `<span data-psub="${p.id}"></span>`}</span>
    <span class="card-row"><b data-pname="${p.id}"></b><span class="card-price">${off ? `<s>${fmt(WAS)}</s> ` : ""}${fmt(PRICE)} <span data-i18n="egp">EGP</span></span></span>
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
const pn = (p, k = "name") => p[k][lang === "ar" ? 1 : 0];
// cart: lines [{p, size, qty}], at most MAXQ jackets in all, each line within its stock
let cart = [];
const pieces = () => cart.reduce((n, l) => n + l.qty, 0);
const room = l => Math.min(left(l.p, l.size), MAXQ - pieces() + l.qty); // most this line can hold
try {
  let c = JSON.parse(store.get("cart"));
  if (c && !Array.isArray(c)) c = [{ p: "classic", ...c }]; // carts saved before there were several jackets
  for (const l of Array.isArray(c) ? c : []) {
    if (!prod(l.p) || left(l.p, l.size) <= 0 || cart.some(x => x.p === l.p && x.size === l.size)) continue;
    const line = { p: l.p, size: l.size, qty: 0 };
    line.qty = Math.min(room(line), Math.max(1, l.qty | 0));
    if (line.qty > 0) cart.push(line);
  }
} catch {}
const saveCart = () => store.set("cart", cart.length ? JSON.stringify(cart) : "");
const onRender = []; // each page adds what it needs to redraw on a language switch

function render() {
  const ar = lang === "ar";
  document.documentElement.lang = lang;
  document.documentElement.dir = ar ? "rtl" : "ltr";
  // innerHTML only receives the static strings above, never user input
  $$("[data-i18n]").forEach(el => { el.innerHTML = (ar ? AR : EN)[el.dataset.i18n] ?? EN[el.dataset.i18n] ?? EN2[el.dataset.i18n]; });
  $$("[data-i18n-alt]").forEach(el => { el.alt = T(el.dataset.i18nAlt); });
  $$("[data-i18n-label]").forEach(el => el.setAttribute("aria-label", T(el.dataset.i18nLabel)));
  $$("[data-pname]").forEach(el => { el.textContent = pn(prod(el.dataset.pname)); });
  $$("[data-psub]").forEach(el => { el.textContent = pn(prod(el.dataset.psub), "sub"); });
  $("#langBtn").textContent = ar ? "English" : "عربي";
  renderCart();
  onRender.forEach(f => f());
}

// ---------- cart panel: slides in from the header cart on every page ----------
const cartPanel = $("#cartPanel"), cpBody = $("#cpBody");
function renderCart() {
  $("#cartCount").textContent = pieces();
  const egp = T("egp");
  // innerHTML only receives the static strings and numbers above, never user input
  cpBody.innerHTML = cart.length ? cart.map((l, i) => `
    <div class="cp-line">
      <img src="${photo(prod(l.p))}" alt="" width="72" height="108">
      <div class="cp-info"><b>${pn(prod(l.p))}</b><span>${T("itemLine").replace("{s}", l.size).replace("{q}", l.qty)}</span>
        <div class="cp-qty"><button type="button" data-i="${i}" data-q="-1" aria-label="${T("less")}"${l.qty <= 1 ? " disabled" : ""}>-</button><output aria-live="polite">${l.qty}</output><button type="button" data-i="${i}" data-q="1" aria-label="${T("more")}"${l.qty >= room(l) ? " disabled" : ""}>+</button></div>
        <button type="button" class="p-link" data-i="${i}" data-q="0">${T("remove")}</button></div>
      <b class="cp-price">${fmt(PRICE * l.qty)} ${egp}</b>
    </div>`).join("") + `
    <p class="sum-row"><span>${T("sub")}</span><b>${fmt(PRICE * pieces())} ${egp}</b></p>
    <p class="cp-note">${T("shipNote")}</p>
    <a href="checkout.html" class="btn btn-red btn-full">${T("checkoutBtn")}</a>`
    : `<p class="cp-empty">${T("emptyTitle")}</p><a href="shop.html" class="btn btn-red btn-full">${T("emptyBtn")}</a>`;
}
cpBody.addEventListener("click", e => {
  const b = e.target.closest("[data-q]"), l = b && cart[b.dataset.i]; if (!l) return;
  const q = +b.dataset.q, i = +b.dataset.i;
  if (q === 0) cart.splice(i, 1);
  else l.qty = Math.min(room(l), Math.max(1, l.qty + q));
  saveCart(); render();
  // keep focus inside the panel after it redraws
  ((q && $(`#cpBody [data-i="${i}"][data-q="${q}"]:not(:disabled)`)) || $("#cpBody [data-q], #cpBody .btn"))?.focus();
});
const openCart = () => { renderCart(); cartPanel.showModal(); };
$("#cartBtn").addEventListener("click", openCart);
$("#cartClose").addEventListener("click", () => cartPanel.close());
cartPanel.addEventListener("click", e => { if (e.target === cartPanel) cartPanel.close(); }); // a click on the dimmed backdrop lands on the dialog itself

// ---------- accounts: creating one gives a one-use 10% code; signed in, checkout fills in the name, phone and code ----------
const signPanel = $("#signPanel"), signForm = $("#signForm"), signErr = $("#signErr");
let me = null, signMode = "register";
const meReady = LIVE ? fetch("api/account.php").then(r => r.json()).then(r => { me = r.ok ? r.user : null; }).catch(() => {}) : Promise.resolve();
$("#signBtn").hidden = !LIVE; // the server keeps the accounts
function renderSign() {
  $("#signBtn").textContent = T(me ? "myAcct" : "signBtn");
  $("#signOut").hidden = !!me; $("#signIn").hidden = !me;
  if (me) {
    $("#signHi").textContent = T("signHi").replace("{n}", me.name);
    $("#signCode").textContent = me.code; $("#signCode").classList.toggle("used", me.used);
    $("#signNote").textContent = T(me.used ? "signUsed" : "signSaved");
  }
  $$(".sign-tabs button").forEach(b => b.setAttribute("aria-pressed", b.dataset.mode === signMode));
  $$(".for-new", signForm).forEach(el => el.hidden = signMode !== "register");
  $("#s-pass").autocomplete = signMode === "register" ? "new-password" : "current-password";
  $("#signGo").textContent = T(signMode === "register" ? "signGo" : "signIn");
}
onRender.push(renderSign);
meReady.then(renderSign);
$("#signBtn").addEventListener("click", () => { signErr.hidden = true; renderSign(); signPanel.showModal(); });
$("#signClose").addEventListener("click", () => signPanel.close());
signPanel.addEventListener("click", e => { if (e.target === signPanel) signPanel.close(); });
$(".sign-tabs").addEventListener("click", e => { const b = e.target.closest("[data-mode]"); if (b) { signMode = b.dataset.mode; signErr.hidden = true; renderSign(); } });
const SIGN_ERR = { phone: "ePhone", missing: "eName", short: "ePass", wrong: "eLogin", exists: "eExists", slow_down: "tryLater" };
signForm.addEventListener("submit", async e => {
  e.preventDefault();
  const ph = $("#s-phone");
  ph.value = ph.value.replace(/[٠-٩۰-۹]/g, d => d.charCodeAt(0) & 15).replace(/[\s-]/g, "");
  const fd = new FormData(signForm); fd.set("action", signMode);
  let r;
  try { r = await (await fetch("api/account.php", { method: "POST", body: fd })).json(); } catch { r = { error: "net" }; }
  if (r.ok) { me = r.user; signForm.reset(); return renderSign(); }
  signErr.textContent = T(SIGN_ERR[r.error] || "tryLater"); signErr.hidden = false;
});
$("#signOutBtn").addEventListener("click", async () => {
  const fd = new FormData(); fd.set("action", "logout");
  try { await fetch("api/account.php", { method: "POST", body: fd }); } catch {}
  me = null; signMode = "login"; renderSign();
});
// launch price countdown: one cell per offer order, lit while it is still open
onRender.push(() => $$("[data-offer]").forEach(el => {
  el.hidden = !OFFER_LEFT;
  el.innerHTML = `<span>${T("offerLeft").replace("{n}", OFFER_LEFT).replace("{m}", OFFER_OF)}</span><i class="offer-bar" aria-hidden="true">${"<b></b>".repeat(OFFER_LEFT)}${"<i></i>".repeat(Math.max(0, OFFER_OF - OFFER_LEFT))}</i>`;
}));
$$("[data-store-name]").forEach(el => el.textContent = STORE_NAME);
$$("[data-price]").forEach(el => el.textContent = fmt(PRICE));
$$("[data-was]").forEach(el => { el.textContent = fmt(WAS); el.hidden = WAS <= PRICE; });
$$("[data-wa]").forEach(a => a.href = waLink("Hi, I have a question about the racing jackets."));

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
  // ?p=black opens that jacket, ?v=2 its third photo
  const qs = new URLSearchParams(location.search), P = prod(qs.get("p")) || PRODUCTS[0];
  const stockOf = s => left(P.id, s);
  const firstIn = SIZES.find(s => stockOf(s[0]) > 0) || SIZES[0];
  const sel = { size: stockOf("M") > 0 ? "M" : firstIn[0], qty: 1 };
  $("#pTitle").dataset.pname = $("#pSub").dataset.psub = P.id;
  $(".size-fig img").src = photo(P);
  onRender.push(() => { document.title = `${pn(P)} | ${STORE_NAME}`; });
  const pills = $("#sizesPick"), tower = $("#tower"), bbSize = $("#bbSize"), qtyEl = $("#qty");
  SIZES.forEach(([s, c, l], i) => {
    pills.insertAdjacentHTML("beforeend", `<button type="button" role="radio" data-size="${s}">${s}</button>`);
    tower.insertAdjacentHTML("beforeend", `<button type="button" class="row" role="radio" data-size="${s}"><span class="pos">${i + 1}</span><b>${s}</b><span>${c} cm</span><span>${l} cm</span></button>`);
    bbSize.add(Object.assign(new Option(s, s), { disabled: stockOf(s) <= 0 }));
  });
  const setSize = s => { if (stockOf(s) > 0) { sel.size = s; if (sel.qty > stockOf(s)) sel.qty = stockOf(s); renderSel(); } };
  pills.addEventListener("click", e => { const b = e.target.closest("button:not(:disabled)"); if (b) setSize(b.dataset.size); });
  tower.addEventListener("click", e => { const b = e.target.closest("button"); if (b) setSize(b.dataset.size); });
  bbSize.addEventListener("change", () => setSize(bbSize.value));
  qtyEl.addEventListener("input", () => { sel.qty = Math.min(MAXQ, stockOf(sel.size), Math.max(1, parseInt(qtyEl.value, 10) || 1)); renderSel(); });

  function renderSel() {
    const [, c, l] = SIZES.find(x => x[0] === sel.size);
    $$("button", pills).forEach(b => { b.setAttribute("aria-checked", b.dataset.size === sel.size); b.disabled = stockOf(b.dataset.size) <= 0; });
    $$("button", tower).forEach(b => { b.dataset.on = b.dataset.size === sel.size; b.setAttribute("aria-checked", b.dataset.size === sel.size); });
    bbSize.value = sel.size; qtyEl.value = sel.qty; qtyEl.max = Math.min(MAXQ, stockOf(sel.size));
    const n = stockOf(sel.size), st = $("#stock");
    st.textContent = n <= 0 ? T("stockOut") : n <= 5 ? T("stockLow").replace("{n}", n) : T("stockIn");
    st.classList.toggle("low", n <= 5);
    $("#addCart").disabled = $("#bbAdd").disabled = n <= 0; // everything sold out
    $("#vChest").textContent = `${c} cm`; $("#vLen").textContent = `${l} cm`;
    $(".size-fig").style.setProperty("--k", .88 + (c - 108) / 24 * .12); // line width follows chest size
    $("#askWa").href = waLink(`Hi, I have a sizing question about the ${P.name[0]} (size ${sel.size}).`);
  }
  onRender.push(renderSel);
  // the click car gets its lap, then the cart panel opens
  const addToCart = () => {
    let l = cart.find(x => x.p === P.id && x.size === sel.size);
    if (!l) cart.push(l = { p: P.id, size: sel.size, qty: 0 });
    l.qty = Math.min(room(l), l.qty + sel.qty); // a full cart (MAXQ jackets) takes no more
    if (!l.qty) cart.splice(cart.indexOf(l), 1);
    saveCart(); renderCart(); setTimeout(openCart, reduce ? 0 : 720);
  };
  $("#addCart").addEventListener("click", addToCart);
  $("#bbAdd").addEventListener("click", addToCart);

  // gallery with loupe
  let gIdx = Math.min(VIEWS.length - 1, Math.max(0, parseInt(qs.get("v"), 10) || 0));
  const main = $("#mainImg"), stage = $("#stage"), loupe = $("#loupe"), ZOOM = 2.2;
  const label = k => lang === "ar" ? AR[k] : EN_LABELS[k];
  VIEWS.forEach(([v, k], i) => $("#thumbs").insertAdjacentHTML("beforeend",
    `<button type="button" role="tab" data-i="${i}" data-k="${k}"><img src="${photo(P, v)}" alt="" width="160" height="240" loading="lazy"></button>`));
  function show(i) {
    gIdx = i; main.src = photo(P, VIEWS[i][0]); main.alt = `${pn(P)}, ${label(VIEWS[i][1])}`;
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

  // reviews approved in the admin; customer text goes in with textContent, never as HTML
  if (REVIEWS.length) {
    $("#reviews").hidden = false;
    $("#revList").append(...REVIEWS.map(r => {
      const n = Math.max(0, Math.min(5, r.rating | 0)), li = document.createElement("li");
      li.dir = "auto"; // each review reads in its own language
      const stars = Object.assign(document.createElement("span"), { className: "stars", textContent: "★".repeat(n) + "☆".repeat(5 - n) });
      stars.setAttribute("role", "img"); stars.dataset.n = n;
      li.append(stars, Object.assign(document.createElement("p"), { textContent: r.text }),
        Object.assign(document.createElement("b"), { textContent: r.name + (r.city ? " · " + r.city : "") }));
      return li;
    }));
    onRender.push(() => $$("#revList .stars").forEach(s => s.setAttribute("aria-label", T("stars").replace("{n}", s.dataset.n))));
  }
}

// ---------- checkout page: summary from the cart, the order is saved on the server, then it goes out on WhatsApp ----------
// ---------- affiliate page: the application goes to the server, the owner reviews it in the admin ----------
if (page === "affiliate") {
  const form = $("#affForm"), err = $("#affErr"), gov = $("#a-gov");
  onRender.push(() => { const keep = gov.value; gov.innerHTML = `<option value="">${T("afChoose")}</option>` + GOVS.map(([en, a]) => `<option value="${en}">${lang === "ar" ? a : en}</option>`).join(""); gov.value = keep; });
  form.addEventListener("submit", async e => {
    e.preventDefault();
    err.hidden = true;
    const ph = $("#a-phone"), link = $("#a-link"), code = $("#a-code");
    ph.value = ph.value.replace(/[٠-٩۰-۹]/g, d => d.charCodeAt(0) & 15).replace(/[\s-]/g, "");
    if (link.value && !/^https?:\/\//i.test(link.value)) link.value = "https://" + link.value.trim();
    code.value = code.value.trim().toUpperCase();
    let first = null;
    $$("input, select, textarea", form).forEach(el => {
      if (el.type === "checkbox") return;
      el.value = el.value.trim();
      const bad = !el.checkValidity(); el.classList.toggle("invalid", bad); el.setAttribute("aria-invalid", bad);
      if (bad && !first) first = el;
    });
    if (first) return first.focus();
    if (!$("#a-agree").checked) { err.textContent = T("afEAgree"); err.hidden = false; return $("#a-agree").focus(); }
    $("#affSend").disabled = true;
    let r;
    try { r = await (await fetch("api/affiliate.php", { method: "POST", body: new FormData(form) })).json(); } catch { r = { error: "net" }; }
    $("#affSend").disabled = false;
    if (r.ok) { form.hidden = true; $("#affDone").hidden = false; return $("#affDone").focus(); }
    err.textContent = T({ phone: "ePhone", email: "afEEmail", link: "afELink", code: "afECode", exists: "afExists" }[r.error] || (r.error === "slow_down" || r.error === "net" ? "tryLater" : "errOrder"));
    err.hidden = false; err.focus();
  });
}

if (page === "checkout") {
  const gov = $("#f-gov"), form = $("#orderForm"), confirmBox = $("#confirm"), phone = $("#f-phone"), shot = $("#f-shot"), ipBox = $("#ipBox");
  const codeIn = $("#f-code"), codeMsg = $("#codeMsg"), formErr = $("#formErr"), sendBtn = $("#sendBtn"), sendText = $("#sendText");
  let disc = 0, discCode = "", discSub = 0, codeState = null, placed = null; // placed: the order the server saved
  const shipFee = () => gov.value ? (SHIP[gov.value] ?? SHIP.other) : null;
  const instapay = () => form.elements.pay.value === "instapay";
  const setText = (el, t) => { if (el.textContent !== t) el.textContent = t; }; // live regions speak only when the text really changes
  // payment ways turned off in the admin are removed, not greyed out
  const ipRadio = $("[name=pay][value=instapay]", form), codRadio = $("[name=pay][value=cod]", form);
  if (!INSTAPAY) ipRadio.closest("label").hidden = true;
  if (!PAY_COD && INSTAPAY) { codRadio.closest("label").hidden = true; ipRadio.checked = true; }
  $("#codeBox").hidden = !LIVE; // codes are checked by the server

  function renderSum() {
    const keep = gov.value, ar = lang === "ar";
    gov.innerHTML = `<option value="">${ar ? "اختار" : "Choose"}</option>` + GOVS.map(([en, a]) => `<option value="${en}">${ar ? a : en}</option>`).join("");
    gov.value = keep;
    $("#order").hidden = !cart.length; $("#emptyCart").hidden = !!cart.length;
    if (!cart.length) return;
    const sub = PRICE * pieces(), fee = shipFee(), egp = T("egp");
    if (disc && discSub !== sub) { disc = 0; discCode = ""; codeState = null; } // the quantity changed: the code is checked again on Apply
    // innerHTML only receives the static strings and numbers above, never user input
    $("#sumLines").innerHTML = cart.map(l => `<div class="sum-line"><img src="${photo(prod(l.p))}" alt="" width="64" height="96"><div><b>${pn(prod(l.p))}</b><span>${T("itemLine").replace("{s}", l.size).replace("{q}", l.qty)}</span></div><b>${fmt(PRICE * l.qty)} ${egp}</b></div>`).join("");
    $("#sumSub").textContent = `${fmt(sub)} ${egp}`;
    $("#sumDisc").hidden = !disc; $("#sumDiscAmt").textContent = `−${fmt(disc)} ${egp}`;
    $("#sumShip").textContent = fee === null ? T("pickGov") : `${fmt(fee)} ${egp}`;
    setText($("#sumTotal"), `${fmt(sub - disc + (fee ?? 0))} ${egp}`);
    setText(codeMsg, codeState ? T(codeState.key).replace("{n}", fmt(codeState.n || 0)) : "");
    codeMsg.classList.toggle("fail", !!codeState?.bad); codeIn.setAttribute("aria-invalid", !!codeState?.bad);
    // InstaPay: the amount to send is only known once delivery is
    ipBox.hidden = shot.disabled = !instapay();
    $("#ipId").textContent = INSTAPAY;
    $("#ipAmt").textContent = fee === null ? "-" : `${fmt(sub - disc + fee)} ${egp}`;
    $("#copyAmt").disabled = fee === null; $("#ipHint").hidden = fee !== null;
    $("#confTitle").textContent = placed ? T("confTitleId").replace("{id}", placed.id) : T("confTitle");
    $("#confSub").textContent = T(placed ? "confSubLive" : "confSub");
    if (!sendBtn.disabled) sendText.textContent = T("send");
  }
  onRender.push(renderSum);
  gov.addEventListener("change", renderSum);
  form.addEventListener("change", e => { if (e.target.name === "pay") renderSum(); });

  // discount code: the server prices it; Enter in the field applies the code instead of sending the order
  const CODE_ERR = { min: "codeMin", expired: "codeOld", used_up: "codeOld", slow_down: "tryLater", net: "tryLater" };
  async function applyCode() {
    const code = codeIn.value.trim();
    let r = { error: "empty" };
    if (code) {
      const fd = new FormData(); fd.append("code", code); fd.append("qty", pieces());
      try { r = await (await fetch("api/coupon.php", { method: "POST", body: fd })).json(); } catch { r = { error: "net" }; }
    }
    if (r.ok) { disc = r.discount; discCode = r.code; discSub = PRICE * pieces(); codeState = { key: "codeOk", n: r.discount }; }
    else { disc = 0; discCode = ""; codeState = { key: CODE_ERR[r.error] || "codeBad", n: r.min, bad: true }; }
    renderSum();
  }
  $("#codeBtn").addEventListener("click", applyCode);
  codeIn.addEventListener("keydown", e => { if (e.key === "Enter") { e.preventDefault(); applyCode(); } });
  // signed in: name and phone are filled in, and the account's 10% code is applied while it is unused
  meReady.then(() => {
    if (!me || !cart.length) return;
    if (!form.elements.name.value) form.elements.name.value = me.name;
    if (!phone.value) phone.value = me.phone;
    if (!me.used && !codeIn.value) { codeIn.value = me.code; applyCode(); }
  });

  // copy buttons: the amount goes out as plain digits so banking apps accept the paste
  form.addEventListener("click", async e => {
    const b = e.target.closest("[data-copy]"); if (!b) return;
    const text = b.dataset.copy === "amount" ? String(PRICE * pieces() - disc + shipFee()) : INSTAPAY, note = $("#copied");
    let failed = false;
    try { await navigator.clipboard.writeText(text); } catch { failed = true; }
    note.textContent = T(failed ? "copyFail" : "copied"); note.classList.toggle("fail", failed);
    clearTimeout(note.t); note.t = setTimeout(() => { note.textContent = ""; }, 2500);
  });
  // screenshot: checked and previewed here, then uploaded with the order
  let shotUrl = "";
  shot.addEventListener("change", () => {
    const f = shot.files[0], ok = !f || (f.type.startsWith("image/") && f.size <= 10 * 1024 * 1024);
    shot.setCustomValidity(ok ? "" : "bad");
    URL.revokeObjectURL(shotUrl); shotUrl = f && ok ? URL.createObjectURL(f) : "";
    $("#shotPrev").src = shotUrl; $("#shotPrev").hidden = !shotUrl;
    shot.classList.toggle("invalid", !ok); shot.setAttribute("aria-invalid", !ok);
  });

  const busy = on => { sendBtn.disabled = on; sendBtn.setAttribute("aria-busy", on); sendText.textContent = T(on ? "sending" : "send"); };
  function showErr(r) {
    const e = r.error || "";
    const key = e === "stock" ? "errStock" : e === "phone" ? "ePhone" : e === "coupon_phone" ? "errPhoneCode" : e.startsWith("coupon_") ? "errCode" : e === "slow_down" ? "tryLater" : "errOrder";
    if (e === "stock") STOCK[`${r.p}:${r.size}`] = r.left ?? 0;
    if (e.startsWith("coupon_")) { disc = 0; discCode = ""; codeState = null; renderSum(); }
    formErr.textContent = T(key).replace("{n}", r.left ?? 0).replace("{s}", r.size ? `${r.size} (${pn(prod(r.p) || PRODUCTS[0])})` : "");
    formErr.hidden = false; formErr.focus();
  }

  form.addEventListener("submit", async e => {
    e.preventDefault();
    if (sendBtn.disabled) return;
    formErr.hidden = true;
    // accept Arabic-Indic digits, spaces and dashes: "٠١٠ ١٢٣٤-٥٦٧٨" becomes "01012345678"
    phone.value = phone.value.replace(/[٠-٩۰-۹]/g, d => d.charCodeAt(0) & 15).replace(/[\s-]/g, "");
    let ok = true;
    $$("[required]:not(:disabled)", form).forEach(el => {
      const bad = !el.value.trim() || !el.checkValidity();
      el.classList.toggle("invalid", bad); el.setAttribute("aria-invalid", bad);
      if (bad && ok) { el.focus(); ok = false; }
    });
    if (!ok) return;
    const d = Object.fromEntries(new FormData(form)), ip = instapay(), file = ip && shot.files[0];
    if (LIVE) {
      busy(true);
      const fd = new FormData(form);
      fd.set("items", JSON.stringify(cart)); fd.set("code", disc ? discCode : ""); fd.set("lang", lang);
      let r = null;
      try { r = await (await fetch("api/order.php", { method: "POST", body: fd })).json(); } catch {}
      busy(false);
      if (r && !r.ok) return showErr(r);
      placed = r; // null when the server gave no answer: the order still goes out on WhatsApp
      if (placed) store.set("cart", ""); // saved: a new visit starts with an empty cart
      if (placed?.discount && me && discCode === me.code) me.used = true;
    }
    // the server's prices win: the admin may have changed them since this page loaded
    const sub = (placed?.unit ?? PRICE) * pieces(), fee = placed?.delivery ?? shipFee(), off = placed?.discount ?? disc, tot = placed?.total ?? sub - off + fee;
    const shotUp = !!placed?.proof;
    const msg = [
      placed ? `Order ${placed.id} - ${STORE_NAME}` : `New order - ${STORE_NAME}`, ...cart.map(l => `Item: ${prod(l.p).name[0]}, size ${l.size} x ${l.qty}`),
      `Name: ${d.name}`, `Phone: ${d.phone}`, `Governorate: ${d.gov}`, `Address: ${d.addr}`,
      `Subtotal: ${sub} EGP`, off ? `Discount (${discCode}): -${off} EGP` : "", `Delivery: ${fee} EGP`, `Total: ${tot} EGP`,
      ip ? `Payment: InstaPay, ${tot} EGP sent to ${INSTAPAY}. ${shotUp ? "Screenshot uploaded with the order." : "Screenshot follows in this chat."}` : `Payment: cash on delivery`,
    ].filter(Boolean).join("\n");
    // the browser can hand the screenshot to WhatsApp through the share sheet on most phones; elsewhere the buyer attaches it in the chat
    const canShare = !!(file && navigator.canShare?.({ files: [file] }));
    const done = () => {
      $("#confText").textContent = msg; $("#confBtn").href = waLink(msg);
      $("#ipSend").hidden = !ip || shotUp; $("#shareShot").hidden = !canShare; $("#ipAttach").hidden = canShare;
      $("#confEdit").hidden = !!placed; // a saved order is changed on WhatsApp, not placed a second time
      renderSum();
      form.hidden = true; confirmBox.hidden = false; confirmBox.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "center" });
      $("#confTitle").focus({ preventScroll: true }); // the form that held focus is gone
    };
    $("#shareShot").onclick = () => navigator.share({ files: [file], text: `InstaPay - ${d.name} - ${tot} EGP` }).catch(() => {});
    if (reduce) return done();
    const dots = $$("#sendBtn .mini-lights i"); // five lights fill, then the order summary appears
    dots.forEach((el, i) => setTimeout(() => el.classList.add("on"), i * 160));
    setTimeout(() => { dots.forEach(el => el.classList.remove("on")); done(); }, 5 * 160 + 200);
  });
  $("#editCart").addEventListener("click", openCart);
  $("#confEdit").addEventListener("click", () => { confirmBox.hidden = true; form.hidden = false; $("#f-name").focus(); });
}

render();

// ---------- home: 360 turntable. Autoplay plays the video; drag, the arrow buttons and the coast after a flick seek it ----------
// ---------- hero headline as pixel tiles, like the brand board; the h1 text stays underneath for screen readers ----------
const h1 = $(".hero h1");
if (h1) {
  const cv = document.createElement("canvas"), low = document.createElement("canvas");
  cv.className = "h1-pix"; cv.setAttribute("aria-hidden", "true"); h1.append(cv);
  const draw = () => {
    const on = lang !== "ar"; // the tiles need Latin capitals; Arabic keeps the normal type
    h1.classList.toggle("pix", on); h1.style.fontSize = "";
    if (!on) return;
    // the real text is set in Silkscreen too, so lines break where the tiles do. One font pixel (size / 8) = 2 x 2 tiles,
    // so the size is snapped to a whole number of screen pixels per tile.
    const c = Math.max(2, Math.round(parseFloat(getComputedStyle(h1).fontSize) / 16)), fs = c * 16;
    h1.style.fontSize = fs + "px";
    const box = h1.getBoundingClientRect(), dpr = devicePixelRatio || 1, lean = 4; // one tile of slant every 4 rows
    const W = Math.ceil(box.width / c) + 8, H = Math.ceil(box.height / c) + 2;
    cv.width = W * c * dpr; cv.height = H * c * dpr; cv.style.width = W * c + "px"; cv.style.height = H * c + "px";
    const out = cv.getContext("2d"); out.setTransform(dpr, 0, 0, dpr, 0, 0);
    low.width = W; low.height = H;
    const g = low.getContext("2d", { willReadFrequently: true }), r = document.createRange();
    g.font = `700 ${fs}px Silkscreen`; g.textBaseline = "alphabetic"; g.letterSpacing = -c + "px";
    const asc = g.measureText("H").fontBoundingBoxAscent;
    for (const span of $$("span", h1)) {
      out.fillStyle = getComputedStyle(span).getPropertyValue("--tile").trim() || "#fff";
      const txt = span.firstChild;
      // each word where the browser laid it out, so line breaks follow the real text
      for (const w of txt.data.matchAll(/\S+/g)) {
        r.setStart(txt, w.index); r.setEnd(txt, w.index + w[0].length);
        const b = r.getClientRects()[0]; if (!b) continue;
        const x0 = Math.round((b.left - box.left) / c), base = Math.round((b.top - box.top + asc) / c);
        g.setTransform(1, 0, 0, 1, 0, 0); g.clearRect(0, 0, W, H);
        g.setTransform(1 / c, 0, 0, 1 / c, 0, 0); g.fillText(w[0].toUpperCase(), x0 * c, base * c);
        const px = g.getImageData(0, 0, W, H).data;
        for (let y = Math.max(0, base - 12); y < Math.min(H, base + 4); y++)
          for (let x = 0; x < W; x++) if (px[(y * W + x) * 4 + 3] > 127) out.fillRect((x + Math.floor((base - y) / lean)) * c, y * c, c - 1, c - 1);
      }
    }
  };
  onRender.push(draw);
  new ResizeObserver(() => draw()).observe(h1);
  document.fonts?.ready.then(draw);
}

const vid0 = $("#spin");
if (vid0) {
  // one <video> per jacket, each loaded once: a switch shows another element instead of reloading one, so nothing flashes
  const vids = PRODUCTS.map((p, i) => { const el = i ? vid0.cloneNode() : vid0; el.poster = p.poster; if (i) { el.removeAttribute("id"); el.hidden = true; vid0.parentNode.insertBefore(el, vid0.nextSibling); } return el; });
  let vid = vids[0];
  const viewer = $("#viewer"), degEl = $("#deg"), seg = $("#seg"), toggle = $("#spinToggle"), toggleText = $("span", toggle);
  let auto = !reduce, held = false, onscreen = true, lastX = 0, lastT = 0, target = 0, v = 0, prev = 0, seeking = false, lastDeg = -1;
  const dur = () => vid.duration || 7.25; // one full turn, in seconds of video
  vids.forEach(el => el.addEventListener("loadedmetadata", () => { el.playbackRate = el.duration / 8; })); // every jacket takes 8 s per turn, whatever its clip length
  const wrap = t => (t % dur() + dur()) % dur();
  // every frame of spin.mp4 is a keyframe, so a seek decodes one frame; one seek in flight at a time keeps the jacket on the hand, not behind it
  const seek = () => { if (seeking || Math.abs(target - vid.currentTime) < .01) return; seeking = true; vid.currentTime = target; };
  vids.forEach(el => el.addEventListener("seeked", () => { if (el !== vid) return; seeking = false; if (held || v) seek(); }));
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
  const label = () => vids.forEach(el => el.setAttribute("aria-label", T("spinLabel")));
  onRender.push(label); label();
  new IntersectionObserver(([x]) => { const was = onscreen; onscreen = x.isIntersecting; run(); if (onscreen && !was) requestAnimationFrame(t => { prev = t; tick(t); }); }).observe(viewer);
  setAuto(auto);
  requestAnimationFrame(t => { prev = t; tick(t); });
  // hold each clip (about 1.3 MB) in memory: seeking then works on any server, even ones that ignore range requests, and never waits on the network
  const clips = {};
  const clip = p => clips[p.id] ??= fetch(p.spin).then(r => r.ok ? r.blob() : Promise.reject()).then(b => URL.createObjectURL(b), () => p.spin);
  const pick = $("#spinPick");
  pick.innerHTML = PRODUCTS.map(p => `<button type="button" data-p="${p.id}"><i class="chip" style="background:linear-gradient(135deg, ${p.chip.map((c, i, a) => `${c} ${i / a.length * 100}% ${(i + 1) / a.length * 100}%`).join(", ")})"></i><span data-pshort="${p.id}"></span></button>`).join("");
  let shown = null;
  function showJacket(p) {
    if (shown === p) return;
    const next = vids[PRODUCTS.indexOf(p)];
    if (vid !== next) { vid.pause(); next.currentTime = 0; next.hidden = false; vid.hidden = true; vid = next; }
    shown = p; v = 0; held = false; seeking = false; target = 0; lastDeg = -1;
    $$("button", pick).forEach(b => b.setAttribute("aria-pressed", b.dataset.p === p.id));
    $("#heroDetails").href = `product.html?p=${p.id}`;
    run();
  }
  pick.addEventListener("click", e => { const b = e.target.closest("button"); if (b) showJacket(prod(b.dataset.p)); });
  onRender.push(() => $$("[data-pshort]", pick).forEach(el => { el.textContent = pn(prod(el.dataset.pshort), "short"); }));
  onRender.at(-1)();
  showJacket(PRODUCTS[0]);
  // first clip now, the others quietly after it so a switch has nothing to download; each shows its poster until then
  const load = i => clip(PRODUCTS[i]).then(src => { vids[i].src = src; if (vids[i] === vid) run(); });
  load(0).then(() => setTimeout(() => PRODUCTS.forEach((p, i) => i && load(i)), 1500));

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
