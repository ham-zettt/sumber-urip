# DESIGN.md: Sumber Urip Design System

**For:** AI coding agent implementing the Sumber Urip website (see PRD)
**Stack:** Native PHP templates + Tailwind CSS 3.4 (utility classes only) + vanilla JS
**Theme:** Light mode only. White background. Blue is the dominant brand color.
**Character:** Calm, precise, minimal, elegant. A reliable tool, not a marketing page.

---

## 1. Design Principles

1. **Clarity over decoration.** Every element must help the cashier record a sale or read a number. If it does not, remove it.
2. **Blue carries the brand.** Blue is used for primary actions, active states, key numbers and charts. Everything else is white and cool neutral.
3. **Numbers are the hero.** Rupiah values are large, tabular, right-aligned and easy to scan.
4. **Borders and whitespace, not shadows.** Structure comes from 1 px hairlines and generous spacing. Shadows are nearly invisible.
5. **Touch first.** The cashier uses a phone or tablet with wet or busy hands. Targets are big, spacing is generous.
6. **Quiet motion.** Short, subtle transitions only. Nothing bounces, floats or glows.

### 1.1 Anti-"AI Slop" Rules (strictly enforced)

Do **not** use any of the following:
- Gradients of any kind (backgrounds, buttons, text, borders). Flat colors only.
- Purple, pink, neon, or teal-to-purple color combinations. Only the palette below.
- Glassmorphism, backdrop blur, glow effects, colored drop shadows, blurred blobs or orbs.
- Emoji as icons or decoration. Use the line icon set defined in section 6.
- Centered marketing hero sections, "feature card" grids with icon circles, testimonial blocks, fake stats.
- Oversized corner radii (`rounded-2xl`, `rounded-3xl`) on everything. Max radius is `rounded-xl` for large containers.
- Heavy `shadow-lg` / `shadow-xl` on cards. Never stack shadow and thick border.
- Dark mode, dark sections or dark hero bands.
- Generic placeholder copy ("Lorem ipsum", "Welcome to our platform"). Use real Indonesian UI copy.
- Decorative illustrations, stock photos, 3D shapes, animated backgrounds.
- Bounce, wiggle, pulse or infinite animations. Skeleton shimmer is the only allowed looping animation, and only if loading takes longer than 400 ms.
- Mixing more than one icon style or more than two font families.

---

## 2. Tailwind Setup

Use Tailwind **3.4**. For production, build with the **Tailwind standalone CLI** (no Node/Composer needed) into `assets/css/app.css`. For quick development, the Play CDN with the same inline config is acceptable.

### 2.1 `tailwind.config.js`

```js
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.php', './includes/**/*.php', './actions/**/*.php', './assets/js/**/*.js'],
  theme: {
    extend: {
      colors: {
        brand: {
          50:  '#F2F7FD',
          100: '#E3EEFB',
          200: '#C7DCF7',
          300: '#9CC1F0',
          400: '#669FE6',
          500: '#3F82DA',
          600: '#2766C0', // primary
          700: '#1F52A0', // primary hover
          800: '#1C4482',
          900: '#1B3A6B',
          950: '#112445',
        },
        ink: {
          DEFAULT: '#0F1B2D', // primary text
          soft:    '#33425A', // secondary text
          muted:   '#5B6B82', // tertiary text, labels
          faint:   '#8795AA', // placeholders, disabled text
        },
        line: {
          DEFAULT: '#E4EAF2', // hairline borders, dividers
          strong:  '#CBD5E3', // input borders
        },
        surface: {
          DEFAULT: '#F7F9FC', // subtle section / table header background
          sunken:  '#EEF2F8', // pressed / hover on neutral items
        },
        success: { 50: '#EAF6F0', 600: '#1F8A5B', 700: '#176B47' },
        danger:  { 50: '#FBEDEC', 600: '#C2403A', 700: '#9E322D' },
        warning: { 50: '#FBF3E3', 600: '#B7791F', 700: '#8F5E17' },
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        // [size, {lineHeight, letterSpacing, fontWeight}]
        'display': ['2.25rem', { lineHeight: '2.5rem', letterSpacing: '-0.02em', fontWeight: '700' }],
        'title':   ['1.5rem',  { lineHeight: '2rem',   letterSpacing: '-0.015em', fontWeight: '700' }],
        'heading': ['1.125rem',{ lineHeight: '1.75rem',letterSpacing: '-0.01em', fontWeight: '600' }],
        'body':    ['0.9375rem',{ lineHeight: '1.5rem' }],
        'small':   ['0.8125rem',{ lineHeight: '1.25rem' }],
        'micro':   ['0.6875rem',{ lineHeight: '1rem', letterSpacing: '0.06em', fontWeight: '600' }],
      },
      borderRadius: {
        DEFAULT: '0.5rem',  // 8px: buttons, inputs
        lg: '0.625rem',     // 10px: cards
        xl: '0.875rem',     // 14px: large panels, modals (maximum)
      },
      boxShadow: {
        card:  '0 1px 2px rgba(15, 27, 45, 0.04)',
        pop:   '0 8px 24px -8px rgba(15, 27, 45, 0.16)', // modals, dropdowns only
      },
      ringColor: { DEFAULT: '#2766C0' },
    },
  },
  plugins: [],
};
```

### 2.2 Base CSS (`assets/css/input.css`)

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  html { @apply bg-white text-ink antialiased; font-feature-settings: 'ss01', 'cv11'; }
  body { @apply font-sans text-body min-h-screen; }
  ::selection { @apply bg-brand-100 text-brand-900; }
  :focus-visible { @apply outline-none ring-2 ring-brand-600 ring-offset-2 ring-offset-white; }
}

@layer components {
  .num { @apply tabular-nums text-right; }       /* all money / quantity cells */
  .label { @apply block text-small font-medium text-ink-soft mb-1.5; }
  .card  { @apply bg-white border border-line rounded-lg shadow-card; }
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
```

### 2.3 Font Loading

Load **Plus Jakarta Sans** (weights 400, 500, 600, 700) from Google Fonts with `display=swap`, and preconnect. This is the **only** font family. Use `tabular-nums` on all numeric UI.

---

## 3. Color Usage

| Role | Token | Usage |
|---|---|---|
| Page background | `bg-white` | Entire app background. Never gray or tinted pages |
| Subtle surface | `bg-surface` | Table headers, summary strips, inset areas |
| Primary action | `bg-brand-600` (hover `brand-700`) | Main button per screen, active nav, selected states |
| Soft brand | `bg-brand-50` / `brand-100` | Active nav background, selected tab, info chips, chart tracks |
| Text | `text-ink` | Headings, numbers, body |
| Secondary text | `text-ink-soft`, `text-ink-muted` | Descriptions, labels, table headers |
| Borders | `border-line` | Cards, tables, dividers. Inputs use `border-line-strong` |
| Positive | `success-600` | Net profit >= 0, success toast |
| Negative | `danger-600` | Net profit < 0, void badge, destructive actions, errors |
| Warning | `warning-600` | Soft warnings (cash received < total, cost > price) |

Rules:
- Blue should occupy roughly 10-15% of the visible UI (buttons, active states, key figures, chart bars). The rest is white and neutral.
- One **primary** (solid blue) button per view. Others are secondary (outline) or ghost.
- Never rely on color alone: pair status colors with text or icon.
- Text contrast must meet WCAG AA (4.5:1). `ink-faint` is only for placeholders and disabled text.

---

## 4. Typography

| Style | Class | Use |
|---|---|---|
| Display | `text-display` | Total in cart, hero metric (Laba Bersih) |
| Title | `text-title` | Page title |
| Heading | `text-heading` | Card and section titles |
| Body | `text-body` | Default text, table cells, inputs |
| Small | `text-small text-ink-muted` | Helper text, captions, meta |
| Micro | `text-micro uppercase text-ink-muted` | Table headers, stat card labels |

Rules:
- Page title: `text-title text-ink`, with a one-line description in `text-small text-ink-muted` beneath it.
- All money and quantity values use `tabular-nums` and are right-aligned in tables.
- Format money as `Rp 12.500`. Render `Rp` in `text-ink-muted` at ~70% of the number size, the number in `text-ink font-semibold`.
- Sentence case for all labels and buttons (e.g., "Simpan transaksi"), except micro labels which are uppercase.
- Never use more than three font sizes within a single card.

---

## 5. Layout, Spacing and Shape

### 5.1 Grid and Spacing
- 4 px base unit. Use Tailwind spacing: `1, 2, 3, 4, 6, 8, 12`. Prefer `gap-4` / `gap-6` between blocks, `p-4` (mobile) and `p-6` (desktop) inside cards.
- Content max width: `max-w-6xl mx-auto`, page padding `px-4 sm:px-6 lg:px-8`, vertical rhythm `space-y-6`.
- Breakpoints: design **mobile first** at 360 px; enhance at `md` (768) and `lg` (1024).

### 5.2 App Shell
- **Mobile (< lg):** top bar (logo + page title) and a **fixed bottom tab bar** with 5 items: Kasir, Riwayat, Laporan, Pengeluaran, Produk. Add `pb-24` to page content to clear the bar. Respect `env(safe-area-inset-bottom)`.
- **Desktop (>= lg):** fixed **left sidebar** `w-60`, white, with a right hairline border (`border-r border-line`). Logo at top, nav items below. No top bar.
- Logo: wordmark "Sumber Urip" in `font-bold text-brand-700` with a small solid-blue water-drop glyph (single flat SVG, no gradient). Subline "Isi ulang air" in `text-micro` muted.
- Nav item: `h-11 px-3 rounded-lg flex items-center gap-3 text-body text-ink-soft hover:bg-surface`. Active: `bg-brand-50 text-brand-700 font-semibold` with a 2 px left indicator bar in `brand-600` on desktop.

### 5.3 Shape and Elevation
- Cards: `bg-white border border-line rounded-lg shadow-card`. No hover lift.
- Buttons and inputs: `rounded` (8 px). Chips and badges: `rounded-full`.
- Modals and dropdowns: `rounded-xl shadow-pop border border-line`. Backdrop is a flat `bg-ink/40` with no blur.
- Dividers: `border-t border-line`. Prefer dividers to extra cards.

---

## 6. Iconography

- One set only: **Lucide** (or Heroicons Outline), inline SVG, `stroke-width 1.75`, `size-5` default, `size-4` inside buttons and badges. Color inherits via `currentColor`.
- Suggested icons: Kasir = `shopping-cart` or `receipt`, Riwayat = `history`, Laporan = `bar-chart-3`, Pengeluaran = `wallet`, Produk = `package`, Hapus = `trash-2`, Batalkan = `ban`, Tambah = `plus`, Kurang = `minus`.
- Icons accompany text labels in navigation. Never use icon-only buttons without `aria-label`.

---

## 7. Components

### 7.1 Buttons

Heights: `h-11` default (44 px touch), `h-12` for the main "Simpan transaksi" button on mobile.

```html
<!-- Primary -->
<button class="inline-flex h-11 items-center justify-center gap-2 rounded bg-brand-600 px-5 text-body font-semibold text-white transition-colors hover:bg-brand-700 active:bg-brand-800 disabled:cursor-not-allowed disabled:bg-line-strong disabled:text-white">Simpan transaksi</button>

<!-- Secondary -->
<button class="inline-flex h-11 items-center justify-center gap-2 rounded border border-line-strong bg-white px-5 text-body font-medium text-ink transition-colors hover:bg-surface active:bg-surface-sunken disabled:text-ink-faint">Kosongkan</button>

<!-- Ghost -->
<button class="inline-flex h-11 items-center gap-2 rounded px-3 text-body font-medium text-brand-700 hover:bg-brand-50">Lihat detail</button>

<!-- Destructive (outline, used for void/delete) -->
<button class="inline-flex h-11 items-center gap-2 rounded border border-danger-600/30 bg-white px-5 text-body font-medium text-danger-600 hover:bg-danger-50">Batalkan transaksi</button>
```

Loading state: replace label with a 16 px spinner (simple 2 px border ring, `animate-spin`) and keep width; disable the button.

### 7.2 Form Inputs

```html
<label class="label" for="amount">Jumlah (Rp)</label>
<input id="amount" inputmode="numeric" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body tabular-nums text-ink placeholder:text-ink-faint transition-colors hover:border-ink-faint focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
<p class="mt-1.5 text-small text-ink-muted">Hanya angka, tanpa titik.</p>
```

- Error: `border-danger-600` + message `text-small text-danger-600` below, with `aria-describedby`.
- Money inputs show a fixed `Rp` prefix inside the field on the left (`pl-10`, prefix in `text-ink-muted`).
- Select and date inputs share the same height and border styling. Use native controls styled with Tailwind (no heavy custom dropdown libraries).

### 7.3 Product Button (Kasir, the signature component)

Three equal cards in a responsive grid: `grid grid-cols-1 sm:grid-cols-3 gap-3`. On very small phones they may stack as full-width rows with a horizontal layout (name left, price right).

```html
<button type="button" data-product="GALON_BESAR"
  class="group relative flex min-h-[104px] flex-col justify-between rounded-lg border border-line bg-white p-4 text-left transition-colors hover:border-brand-300 hover:bg-brand-50/50 active:bg-brand-100 focus-visible:ring-2">
  <span class="text-micro uppercase text-ink-muted">Isi ulang</span>
  <span class="mt-2 block text-heading text-ink">Galon Besar</span>
  <span class="mt-1 block text-body font-semibold tabular-nums text-brand-700">Rp 6.000</span>
  <!-- quantity badge appears when the item is in the cart -->
  <span class="absolute right-3 top-3 hidden h-6 min-w-6 items-center justify-center rounded-full bg-brand-600 px-2 text-small font-semibold tabular-nums text-white">2</span>
</button>
```

- Small category label per product: "Isi ulang" for Galon Besar and Galon Kecil, "Galon baru" for Galon Baru (Pabrik).
- In-cart state: border becomes `border-brand-600`, quantity badge visible. Tap feedback is a 100 ms background change only (no scale bounce).
- Do not use different bright colors per product. Differentiate by label and name only.

### 7.4 Cart Panel

- Desktop: sticky right column `lg:sticky lg:top-6`, `card`, width ~ `w-96`. Mobile: cart is a section below the products, plus a **sticky summary bar** above the bottom tab bar showing item count, total and a primary "Simpan" button.
- Cart line: product name (`font-medium`), unit price (`text-small text-ink-muted`), stepper (`-` / numeric input / `+`, 36 px square ghost buttons with `border-line-strong`), line subtotal (`tabular-nums font-semibold`), remove (`ghost`, `trash-2`).
- **Total block:** separated by `border-t border-line`, label "Total" in `text-micro uppercase`, value in `text-display tabular-nums text-ink`.
- Cash received and change: two compact rows under the total. Change shows in `text-success-700`; insufficient cash shows a `warning` helper text.
- Empty state: centered `text-small text-ink-muted` "Belum ada item. Ketuk produk untuk menambahkan." with a muted line icon. No illustration.

### 7.5 Stat Card (Dashboard strip and Reports)

```html
<div class="card p-4">
  <p class="text-micro uppercase text-ink-muted">Pendapatan</p>
  <p class="mt-2 text-title tabular-nums text-ink">Rp 1.250.000</p>
  <p class="mt-1 text-small text-ink-muted">24 transaksi</p>
</div>
```

- Reports grid: `grid grid-cols-2 lg:grid-cols-3 gap-3`. 
- **Laba Bersih** card is emphasized: spans two columns on mobile, `border-brand-200 bg-brand-50`, value in `text-display`. If the value is negative, switch to `border-danger-600/30 bg-danger-50` with `text-danger-700` and a leading minus sign.
- No sparkline decoration, no trend arrows unless real comparison data exists.

### 7.6 Tables

- Wrap in `card overflow-hidden`; inner `overflow-x-auto` for small screens.
- Header: `bg-surface text-micro uppercase text-ink-muted`, `h-10`, `border-b border-line`.
- Rows: `h-14`, `border-b border-line last:border-0`, `hover:bg-surface/60`. Numeric columns use `num`.
- Totals row: `bg-surface font-semibold`.
- On mobile (< md) History and Expenses tables convert to **stacked list rows** (two lines: primary text + amount, secondary meta underneath) rather than horizontal scrolling.
- Pagination: simple "Sebelumnya / Berikutnya" ghost buttons with "Halaman 2 dari 9" in the middle.

### 7.7 Badges and Chips

```html
<span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-small font-medium text-success-700">Selesai</span>
<span class="inline-flex items-center rounded-full bg-danger-50 px-2.5 py-0.5 text-small font-medium text-danger-700">Dibatalkan</span>
<span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-small font-medium text-brand-700">Listrik</span>
```

Voided rows: text turns `text-ink-muted` and the total gets `line-through`.

### 7.8 Segmented Control / Tabs (Harian, Mingguan, Bulanan)

```html
<div class="inline-flex rounded-lg bg-surface p-1">
  <a class="rounded px-4 py-2 text-small font-semibold bg-white text-brand-700 shadow-card">Harian</a>
  <a class="rounded px-4 py-2 text-small font-medium text-ink-muted hover:text-ink">Mingguan</a>
  <a class="rounded px-4 py-2 text-small font-medium text-ink-muted hover:text-ink">Bulanan</a>
</div>
```

Next to it: previous/next period icon buttons and the date/month picker. Show the resolved range ("Senin 26 Okt - Minggu 1 Nov 2026") in `text-small text-ink-muted`.

### 7.9 Feedback
- **Toast:** top-center on mobile, bottom-right on desktop. `card shadow-pop px-4 py-3`, 3 px colored left border (`success-600` or `danger-600`), auto-dismiss in 4 s. Success copy example: "Transaksi TRX-20261027-0004 tersimpan. Total Rp 15.000."
- **Confirm modal:** centered `rounded-xl`, title, one sentence of consequence, secondary "Batal" and destructive "Ya, batalkan". Esc closes. Focus is trapped.
- **Inline alert:** `rounded-lg border px-4 py-3 text-small` with tinted background (`danger-50` / `warning-50` / `brand-50`) and matching text color.
- **Empty states:** one line of explanation plus one action button. No illustrations.

### 7.10 Charts (optional daily revenue chart)
- Single hue: bars in `brand-600`, track/grid in `line`, axis labels `text-small text-ink-muted`. Highlight the selected day with `brand-800`; zero days show a 2 px baseline.
- Build with plain SVG or CSS grid. No heavy chart libraries, no gradients, no 3D, no pie charts.
- Provide a visually hidden data table or `aria-label` summary for accessibility.

---

## 8. Page Blueprints

### 8.1 Kasir (`index.php`)
- Top: page title "Kasir" + today's date. Beneath it a **summary strip** (3 stat cards: Pendapatan hari ini, Transaksi, Laba kotor) using compact `p-3` variant.
- Desktop: two columns `lg:grid-cols-[1fr_24rem]`. Left: section heading "Pilih produk", the three product buttons, then "Transaksi terbaru" (last 5, compact rows). Right: sticky cart panel.
- Mobile: summary strip (horizontal scroll or 3-up compact), products, cart, recent list; sticky bottom action bar above the tab bar.

### 8.2 Riwayat (`history.php`, `history_detail.php`)
- Filter row in a `card p-4`: date from, date to, status select, invoice search, "Terapkan" primary and "Reset" ghost. On mobile, filters collapse into an expandable section.
- Result list/table, then a footer line: "20 transaksi - Total Rp 1.250.000".
- Detail page: two-column on desktop (items table left, summary and status right), single column on mobile. Destructive "Batalkan transaksi" at the bottom of the summary card, never prominent.

### 8.3 Laporan (`reports.php`)
- Header: segmented control + period picker + prev/next.
- Row 1: summary cards (Pendapatan, Modal barang, Laba kotor, Pengeluaran, Transaksi) then the emphasized **Laba bersih** card.
- Row 2: per-product breakdown table and expense-by-category table side by side on `lg`, stacked on mobile.
- Row 3 (weekly and monthly): daily breakdown table with a totals row, optional bar chart above it.

### 8.4 Pengeluaran (`expenses.php`)
- Desktop: form card on the left (`lg:w-96`), list on the right. Mobile: "Tambah pengeluaran" primary button opens the form as a full-width sheet/section; the list follows.
- Hint under the form in an info alert about not recording new-gallon purchases.

### 8.5 Produk (`products.php`)
- Three rows (cards) each with: product name, price input, cost input, live margin per unit (`text-success-700`, or `text-danger-700` if negative), active toggle. One "Simpan perubahan" primary button. Info alert: "Perubahan harga hanya berlaku untuk transaksi berikutnya."

---

## 9. Motion

- Transitions: `transition-colors duration-150 ease-out`. Modals and toasts: 150-200 ms opacity + 8 px translate-y fade in. Nothing else animates.
- Number changes in the cart total update instantly (no counting animation).
- Respect `prefers-reduced-motion` (already handled in base CSS).

---

## 10. Accessibility and Responsiveness Checklist

- Touch targets >= 44 x 44 px (steppers may be 36 px visually but must have a 44 px hit area).
- Visible focus ring on every interactive element (`ring-2 ring-brand-600 ring-offset-2`).
- All form fields have visible labels. Errors are announced (`role="alert"`), linked with `aria-describedby`.
- Semantic HTML: `<nav>`, `<main>`, `<table>` with `<th scope>`, buttons are `<button>`, links are `<a>`.
- Set `<html lang="id">`. Add `<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">` and `<meta name="theme-color" content="#FFFFFF">`.
- No horizontal page scroll at 360 px. Tables degrade to stacked rows.
- Print stylesheet (optional): hide navigation, white background, keep tables and totals.

---

## 11. Copy Guidelines (Indonesian UI)

- Tone: short, polite, neutral. Use "Anda" sparingly; prefer direct labels.
- Buttons are verbs: "Simpan transaksi", "Tambah pengeluaran", "Batalkan transaksi", "Terapkan", "Reset".
- Errors say what to fix: "Jumlah harus lebih dari 0." not "Input tidak valid."
- Dates: `27 Okt 2026`, time `14:35`. Money: `Rp 1.250.000`. Negative money: `-Rp 25.000`.
- Month names and weekdays in Indonesian.

---

## 12. Implementation Notes for the Agent

1. Create shared PHP partials (`header.php`, `footer.php`, `nav.php`) that implement the app shell once; all pages reuse them.
2. Put reusable markup in small PHP helper functions (e.g., `render_stat_card()`, `render_badge()`, `format_rupiah()`) so classes are not duplicated across pages.
3. Do not introduce any CSS framework other than Tailwind, and do not add custom CSS beyond section 2.2 unless truly necessary.
4. Before finishing, review every screen against section 1.1 (anti-slop rules) and section 10 (accessibility checklist).
