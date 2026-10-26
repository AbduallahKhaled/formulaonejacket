# Accessibility handoff: admin panel + checkout

Scope: `admin.php` + `admin-sections/views.php` (owner-facing, English, dark), and the checkout flow in
`checkout.html` + `app.js`, including the pieces still to be wired (coupon field, server order POST,
`#formErr`, product-page reviews). Target: WCAG 2.2 AA.

Status key: **OK** = already done in code · **FIX** = gap found in current code · **NEW** = applies to unbuilt work.

---

## 1. Accessibility specification

### 1.1 Admin: page frame

| # | Element | Requirement | Status |
|---|---|---|---|
| A1 | Skip link | First focusable element in `<body>`: `<a class="skip" href="#content">Skip to content</a>`, visually hidden until focused. `section.main` gets `id="content" tabindex="-1"`. The sidebar has 13 links before any content. | FIX |
| A2 | Landmarks | `page_head()` opens `<main class="wrap">` around the whole shell, so the sidebar `<aside>` sits inside `<main>`. Change the wrapper to `<div class="wrap">` and make `section.main` the `<main id="content">`. | FIX |
| A3 | Page title | `<title>` = `"{Tab label} · Store admin"` (currently always "Store admin"), so the browser history and screen-reader page announcements say which tab loaded. | FIX |
| A4 | Headings | Exactly one `<h1>` per tab (the `.h1` tab title), panels use `<h2>`, sub-blocks `<h3>`. No skipped levels. | Verify |
| A5 | Nav current page | `aria-current="page"` on the active tab link. | OK |
| A6 | Nav badges | `<span class="badge">3</span>` reads "Orders3". Add hidden context: `<span class="badge">3<span class="sr-only"> new</span></span>` (orders), `" to check"` (payments), `" to ship"` (shipping), `" sizes low"` (inventory). Add the `.sr-only` class to the admin CSS. | FIX |
| A7 | New-tab links | "View site ↗" and the proof thumbnails use `target="_blank"`. Add `<span class="sr-only"> (opens in a new tab)</span>`. | FIX |
| A8 | Flash message | After every POST the page redirects and shows `.flash`. Give it `role="status" tabindex="-1"` and focus it on load (`autofocus` won't work on a div, so use one line of inline JS: `document.querySelector('.flash')?.focus()`). Errors from `go()` (e.g. "Session expired") should use the `.flash.bad` style and `role="alert"`. | FIX |

### 1.2 Admin: login and first-run setup

| # | Element | Requirement | Status |
|---|---|---|---|
| L1 | Password labels | `<label>Password</label>` has no `for`, so the input has no accessible name (screen readers say "edit, protected"). Add `id="pw"`/`for="pw"` and `id="pw2"`/`for="pw2"`. | FIX |
| L2 | Error message | `<p class="err" id="login-err" role="alert">`. The input gets `aria-invalid="true" aria-describedby="login-err"` when an error is shown. | FIX |
| L3 | Lockout text | The current wording already states the time left ("Try again in N minutes"). Keep it. | OK |
| L4 | Paste and password managers | Never block paste. `autocomplete="current-password"` / `new-password` are already set (WCAG 2.2 3.3.8). | OK |
| L5 | Setup rules | Show "At least 10 characters" as hint text before submit, tied by `aria-describedby`, not only as an error afterwards. | FIX |

### 1.3 Admin: tables, order cards, forms

| # | Element | Requirement | Status |
|---|---|---|---|
| T1 | Empty headers | `<th></th>` action/bar columns (dashboard, shipping, coupons, customers, reports) need a name: `<th><span class="sr-only">Actions</span></th>`. For bar columns use "Share". | FIX |
| T2 | Bars | `.bar` spans are decorative (the number is in the next cell). Add `aria-hidden="true"`. | FIX |
| T3 | Order cards | `<details>/<summary>` stays native, so the keyboard (Enter/Space) and expanded state come for free. Don't add click handlers. The summary text must start with the order ID. | OK |
| T4 | Repeated buttons | Rows full of "Confirm", "Delivered", "Returned", "Delete" give a screen-reader button list nobody can tell apart. Add `aria-label` with the subject, e.g. `aria-label="Confirm order FJ-261025-AB12"` and `aria-label="Delete code RACE10"`. | FIX |
| T5 | Destructive confirms | Keep the native `confirm()` dialogs (accessible, focus returns). Every Reject/Returned/Delete already has one. | OK |
| T6 | Status and payment pills | The colour plus a text label is fine (1.4.1). Never ship a colour-only dot. | OK |
| T7 | Star ratings | `<span class="stars" aria-label="4 of 5">`: an `aria-label` on a plain span is ignored. Use `role="img" aria-label="4 out of 5 stars"`. | FIX |
| T8 | Proof thumbnails | `alt="InstaPay screenshot for order {id}"`, so the links can be told apart. | FIX |
| T9 | Customer text | Names and addresses can be Arabic inside the English page. Wrap them in `dir="auto"` (`<td dir="auto">`) so the bidi order is right. | FIX |
| T10 | Form labels | Coupon, review, product and settings forms already use `<label for>`. Table-cell inputs use `aria-label`. | OK |
| T11 | Number inputs | Stock, price and coupon inputs: add `pattern="[0-9]*"` and server-side errors that name the field ("Price must be a whole number"), shown in the flash. | Verify |
| T12 | Search boxes | Enter submits (no visible button). Keep the `aria-label`. Add `role="search"` on the `<form>`. | FIX |

### 1.4 Admin: visual

| # | Element | Requirement | Status |
|---|---|---|---|
| V1 | Focus ring | Only `.input:focus` has a style. Add `a:focus-visible, button:focus-visible, summary:focus-visible, .chip:focus-visible { outline: 2px solid #fff; outline-offset: 2px }`. White on #0e0e0e/#171717 is well over 3:1. A red ring on a red `.btn` would vanish. | FIX |
| V2 | `--faint` #6b6b6b text | It measures about 3.4:1 on #171717 and fails 4.5:1. It is used for `.navlabel`, `.kpis em`, `.st-cancelled` and `.pill.off`. Raise it to **#8f8f8f** (≥ 5:1). | FIX |
| V3 | Button hover | White on `#ff1a12` is about 3.9:1, which fails for the small `.btn-sm` text. Use a darker hover, `#b80500` (≥ 6:1), not a lighter one. | FIX |
| V4 | Base button | White on #E10600 is about 5:1. | OK |
| V5 | Target size | `.btn-sm` is about 31px tall, which passes 2.5.8 (24px). Keep `min-height:32px` if the padding changes. | OK |
| V6 | Reflow | Under 900px the sidebar becomes a horizontal scroll strip and tables sit in `.scroll`. At 320px CSS width, nothing outside `.scroll` may scroll sideways. | Verify |

### 1.5 Storefront checkout (`checkout.html` + `app.js`)

| # | Element | Requirement | Status |
|---|---|---|---|
| C1 | Field errors | `.invalid` is toggled but `aria-invalid` is not set, and the `<small class="err">` isn't tied to its field. Give each err an id (`e-name`, `e-phone`, …), set `aria-describedby` on the input and toggle `aria-invalid` alongside `.invalid`. | FIX |
| C2 | First error focus | On submit, focus moves to the first invalid field (`app.js:350`). | OK |
| C3 | Submit busy state (server POST) | While posting, set `sendBtn.disabled = true` and `aria-busy="true"`, and swap the label to "Sending…" / "جاري الإرسال…". Restore both on any error. | NEW |
| C4 | `#formErr` (server errors) | `<p id="formErr" class="form-err" role="alert" tabindex="-1" hidden>` above the submit button. On a stock, phone, coupon or slow_down error: fill it with a translated message, unhide it, focus it. For a stock error, say what to do: "Only 2 left in size L. Lower the quantity." | NEW |
| C5 | Success handoff | `form.hidden = true` drops focus to `<body>` (`app.js:365`). After showing `#confirm`, focus its `<h2>` (`tabindex="-1"`). Keep the `scrollIntoView`. | FIX |
| C6 | Order number | Put the order number in the confirm heading text ("Order FJ-… ready"), so the focus move announces it. | NEW |
| C7 | Coupon field | `<label for="f-code">Discount code</label><input id="f-code" autocomplete="off" dir="ltr">` plus `<button type="button" id="codeBtn">Apply</button>`. The result goes in `<p id="codeMsg" aria-live="polite">`. On error the input gets `aria-invalid` + `aria-describedby="codeMsg"`. Enter inside the field applies the code and does not submit the order. | NEW |
| C8 | Discount row | `#sumDisc` is a normal `.sum-row` with a visible minus sign and the word "Discount". The colour is not the only cue. | NEW |
| C9 | Live summary | `aria-live="polite"` on the whole `.summary` re-reads the full block on each change. Move it to `#sumTotal` only (plus `#codeMsg`). | FIX |
| C10 | Payment options off | When the admin turns COD or InstaPay off, remove the radio with `hidden` (not opacity) and `checked` the remaining one. If only one is left, keep the fieldset/legend so the context is still there. | NEW |
| C11 | Screenshot upload | The file input keeps its `<label>`. The error text names the limit ("an image up to 10 MB"). After choosing a file, the preview `<img>` gets `alt="Your payment screenshot"` (it's content, not decoration). | FIX |
| C12 | RTL/AR | Switching language sets `<html lang="ar" dir="rtl">`. Phone, InstaPay ID, amount, coupon and order number keep `dir="ltr"`. All new strings exist in both languages. | Verify |

### 1.6 Product-page reviews (new)

| # | Element | Requirement | Status |
|---|---|---|---|
| R1 | Structure | `<section aria-labelledby="revTitle"><h2 id="revTitle">What riders say</h2><ul>` with one `<li>` per review. Use the next heading level after the product title. | NEW |
| R2 | Stars | `<span role="img" aria-label="5 out of 5 stars">★★★★★</span>`, translated. | NEW |
| R3 | Text | Built with `textContent` (escaped) and `dir="auto"` per review, since reviews may be in either language. | NEW |
| R4 | Motion | If reviews auto-scroll or rotate: honour `reduce` (already defined in `app.js:81`) and give a pause control. Simplest: a static list, no carousel. | NEW |

---

## 2. Test cases

**Keyboard**
1. Admin: load any tab and press Tab once. "Skip to content" is visible. Press Enter, then Tab: focus lands on the first control in the tab content, not in the sidebar.
2. Admin: Tab through the Orders tab. Every link, button, `<summary>` and input shows a clearly visible ring (V1). Enter/Space on a summary opens and closes the order card.
3. Admin: confirm an InstaPay transfer. After the redirect, focus is on the flash message and the SR reads "Transfer confirmed…".
4. Checkout: submit empty. Focus goes to Full name. Tab to Phone, type `123`, submit: focus returns to Phone.
5. Checkout: in the coupon field, type a code and press Enter. The code applies and the order is **not** submitted.
6. Checkout: submit a valid order. Focus lands on the "Order FJ-… ready" heading. Tab reaches "Send on WhatsApp" next.
7. Checkout, stock error (order qty above stock from the admin): focus lands on `#formErr`, which states the size and the quantity left. The submit button is enabled again.

**Screen reader (NVDA + Chrome, VoiceOver + Safari iOS)**
8. Admin login: focus the field and hear "Password, edit, protected". Wrong password: hear "Wrong password. 4 tries left." and the field reports invalid.
9. Admin sidebar: "Orders, 3 new, link".
10. Shipping tab: the buttons list (NVDA Ins+F7) shows distinct names ("Delivered FJ-…", "Returned FJ-…").
11. Reviews tab and product page: stars read "4 out of 5 stars", once.
12. Checkout Phone error: on focus hear "Phone, invalid entry, Enter a valid Egyptian mobile number".
13. Checkout coupon: apply a bad code and hear the error once (polite). Apply a good one and hear "Discount … applied", then the new total once.
14. Arabic checkout: the SR switches to the Arabic voice. The phone number and order number are read left-to-right.

**Visual**
15. Admin: `--faint` text, `.btn` / `.btn-sm` default and hover all measure ≥ 4.5:1 (use a contrast checker on the computed colours).
16. Admin and storefront focus rings are ≥ 3:1 against the surface they sit on (2.4.11 / 1.4.11).
17. Grayscale the screen (Win+Ctrl+C): every order and payment state is still readable from its pill text.

**Zoom and reflow**
18. Storefront checkout at 400% (320 CSS px): one column, no sideways page scroll, the summary and form fully usable.
19. Admin at 200%: the sidebar becomes the horizontal strip. Tables scroll inside `.scroll` only. The order cards wrap without clipping the amount.
20. Text spacing bookmarklet (1.4.12) on checkout: no clipped labels or errors.

**Motion**
21. OS "reduce motion" on: the checkout success shows instantly (no start-lights animation), the scroll is not smooth, and any review list does not move.

---

## 3. WCAG 2.2 mapping

| SC | Level | Requirements |
|---|---|---|
| 1.1.1 Non-text content | A | T2, T7, T8, C11, R2 |
| 1.3.1 Info and relationships | A | A2, A4, L1, T1, T10, T12, C1, R1 |
| 1.3.2 Meaningful sequence | A | T9, C12, R3 |
| 1.4.1 Use of colour | A | T6, C8 |
| 1.4.3 Contrast (minimum) | AA | V2, V3, V4 |
| 1.4.10 Reflow | AA | V6, tests 18–19 |
| 1.4.11 Non-text contrast | AA | V1 |
| 1.4.12 Text spacing | AA | test 20 |
| 2.1.1 Keyboard | A | T3, C7 |
| 2.2.2 Pause, stop, hide | A | R4 |
| 2.3.3 Animation from interactions | AAA (good practice) | R4, test 21 |
| 2.4.1 Bypass blocks | A | A1 |
| 2.4.2 Page titled | A | A3 |
| 2.4.3 Focus order | A | A8, C2, C4, C5 |
| 2.4.4 Link purpose | A | A7, T8 |
| 2.4.6 Headings and labels | AA | A4, T4 |
| 2.4.7 / 2.4.11 Focus visible / not obscured | AA | V1 |
| 2.5.3 Label in name | A | L1, T4 (the aria-label starts with the visible word) |
| 2.5.8 Target size (minimum) | AA | V5 |
| 3.1.1 / 3.1.2 Language of page / parts | A / AA | C12 |
| 3.2.2 On input | A | C7 (Enter in the coupon field does not submit the order) |
| 3.3.1 Error identification | A | L2, C1, C4, C7 |
| 3.3.2 Labels or instructions | A | L5, C7, C11 |
| 3.3.3 Error suggestion | AA | C4, C11, T11 |
| 3.3.4 Error prevention (financial) | AA | T5. Checkout has a review step (the WhatsApp confirm screen) |
| 3.3.8 Accessible authentication | AA | L4 |
| 4.1.2 Name, role, value | A | L1, L2, T7, C1, C3 |
| 4.1.3 Status messages | AA | A8, C4, C7, C9 |

---

## 4. Risk register

| Risk | Why it matters | Approach |
|---|---|---|
| The PRG redirect drops screen-reader context | Every admin action reloads the page, so the SR starts at the top and misses the flash. | A8: focus the flash on load. No AJAX rewrite needed. |
| `aria-live` on a large region | Re-renders of `.summary` read the whole block, which is noisy enough that users mute it. | C9: put live on the total only. |
| Focus lost on `hidden` | The checkout hides the form after submit, and any future "remove radio" logic can drop focus onto the body. | C5/C10: always move focus explicitly after hiding the focused element. |
| `aria-label` overriding the visible text | T4 labels must *start* with the visible word ("Delivered FJ-…"), or voice-control users saying "click Delivered" fail (2.5.3). | Use the visible text plus the ID, never a rewording. |
| Mixed-direction data | Arabic names in the English admin, and phone numbers in the Arabic storefront, can render out of order. | `dir="auto"` on user data, `dir="ltr"` on numbers and IDs. |
| Native `confirm()` | It is accessible, but it can't be styled or translated beyond its text. | Fine for a single-owner admin. Don't replace it with a custom modal unless you add `<dialog>` with focus return. |
| Gaps automated tools won't catch | axe won't flag duplicate button names (T4), lost focus (C5) or noisy live regions (C9). | Run manual tests 3, 6, 10 and 13 every release. |
| Third-party fonts | If Google Fonts fails, the italic 800 Barlow headings fall back to system italic. That's acceptable, no info loss. | None needed. |

---

## 5. Definition of done

- [ ] All **FIX** rows are done. All **NEW** rows are built as specified with the checkout wiring.
- [ ] axe DevTools: 0 critical/serious issues on admin login, dashboard, orders, payments, shipping and settings, and on checkout (EN and AR).
- [ ] Keyboard tests 1–7 pass with no mouse.
- [ ] Screen-reader tests 8–14 pass on NVDA + Chrome (desktop) and VoiceOver + Safari (iOS checkout).
- [ ] Contrast tests 15–16 pass: no text below 4.5:1 and no UI boundary or focus ring below 3:1.
- [ ] Zoom/reflow tests 18–20 pass.
- [ ] Reduced-motion test 21 passes.
- [ ] Every new AR/EN string exists in both languages, including error and busy text.
