# PRD: Sumber Urip - Water Refill Shop Management Website

**Version:** 1.0  |  **Audience:** AI coding agent  |  **Stack:** Native PHP 8.x + MySQL/MariaDB  |  **Auth:** None (no register, no login)

---

## 1. Overview

### 1.1 Purpose

Build a simple, fast, mobile-friendly web application for **Sumber Urip**, a drinking-water gallon refill shop in Indonesia. The owner or cashier uses it to record sales with one tap per product, see automatic totals, track product cost (modal) and expenses, and view daily, weekly and monthly revenue, gross profit and net profit.

### 1.2 Problem Statement

Sales are currently recorded by hand (or not at all). This makes it hard to know the real daily income, how much profit each product makes, and whether operational expenses (electricity, filters, salary, etc.) are eating the profit.

### 1.3 Goals

- Record a sale in **under 5 seconds** using large product buttons.
- Calculate totals automatically and **always on the server side**.
- Show accurate revenue, cost of goods sold (COGS), gross profit and net profit for any day, week or month.
- Keep a permanent, auditable transaction history.
- Run on cheap hosting or a local XAMPP/Laragon server with zero external services.

### 1.4 Non-Goals (Out of Scope for v1)

- User registration, login, roles, permissions, password reset.
- Stock/inventory counting, supplier management, customer database or debt tracking.
- Online ordering, payment gateway, delivery tracking, printing to thermal printer (browser print is optional).
- Multi-branch support, multi-language switcher, REST API for third parties.

### 1.5 Users

Single shop. The owner and any cashier use the same open interface. There is **no user concept** in the system.

---

## 2. Technical Requirements

| Item | Requirement |
| --- | --- |
| Backend | Native PHP 8.0+ (no framework, no Composer dependency required) |
| Database | MySQL 5.7+ / MariaDB 10.3+, InnoDB, utf8mb4 |
| DB access | PDO with prepared statements only; exceptions enabled |
| Frontend | HTML5, CSS3, vanilla JavaScript (no build step). Tailwind CSS local file or CDN is acceptable |
| Timezone | `Asia/Jakarta` (set in PHP and in the MySQL session) |
| Currency | Indonesian Rupiah. Store as **integer** (no decimals). Display as `Rp 12.500` |
| UI language | **Indonesian** (all labels, buttons, messages). Code, DB names and comments in English |
| Date display | `dd MMM yyyy` (e.g., 27 Okt 2026); time `HH:mm` (24h) |
| Browser | Latest Chrome, Edge, Firefox, Safari; Android Chrome is the primary mobile target |
| Deployment | Works on Apache/Nginx + PHP, XAMPP, Laragon, or shared hosting (cPanel) |

---

## 3. Business Rules and Definitions

### 3.1 Products (exactly three in v1)

| Code | Name (UI) | Meaning |
| --- | --- | --- |
| `GALON_BESAR` | Galon Besar | Refill of a customer-owned large gallon (19 L type) |
| `GALON_KECIL` | Galon Kecil | Refill of a customer-owned small gallon |
| `GALON_BARU` | Galon Baru (Pabrik) | Sale of a brand-new filled gallon bought from the factory/distributor |

Each product has a **selling price** (`price`) and a **cost price per unit / modal** (`cost_price`), both editable on the Products page. The seed values below are **placeholders only** and must be editable by the owner:

| Product | Default price | Default cost |
| --- | --- | --- |
| Galon Besar | 6000 | 2500 |
| Galon Kecil | 3000 | 1200 |
| Galon Baru (Pabrik) | 55000 | 45000 |

For refill products, cost is the estimated cost per refill (water, electricity, filter, etc.). For new gallons, cost is the purchase price from the factory.

### 3.2 Price and Cost Snapshot (critical)

When a sale is saved, the system **copies the current `price` and `cost_price` of each product into the sale item row** (`unit_price`, `unit_cost`). Later changes to product price or cost must **never** alter historical transactions or past reports.

### 3.3 Formulas

- `item_subtotal = quantity x unit_price`
- `item_cost_subtotal = quantity x unit_cost`
- `sale_total = SUM(item_subtotal)`
- **Revenue (Pendapatan)** = `SUM(sale_total)` of completed sales in the period
- **COGS / Modal Barang** = `SUM(item_cost_subtotal)` of completed sales in the period
- **Gross Profit (Laba Kotor)** = `Revenue - COGS`
- **Operating Expenses (Pengeluaran Operasional)** = `SUM(expenses.amount)` in the period
- **Net Profit (Laba Bersih)** = `Gross Profit - Operating Expenses` (can be negative; show in red)

### 3.4 Other Rules

- Voided sales are **excluded from every report** but remain visible in history with a `Dibatalkan` badge.
- Do **not** record the purchase of new gallons as an operational expense; it is already counted as COGS. Avoid double counting (add a short hint on the expense form).
- Week definition: **Monday to Sunday**.
- Period boundaries use the shop timezone (`Asia/Jakarta`).
- Quantity must be an integer between 1 and 999. Prices, costs and expense amounts must be integers >= 0 (price must be > 0).

---

## 4. Functional Requirements

### 4.1 FR-1: Sales Recording (Home page `/index.php`)

This is the main screen and must be optimized for touch.

**Layout**

1. A compact **Today summary strip** at the top: today's revenue, number of transactions, gross profit.
2. **Three large product buttons** (min height 90 px), each showing product name and current price. Tapping a button adds **1 unit** of that product to the current cart.
3. A **Cart panel** listing items with: product name, unit price, quantity controls (`-` / `+`, plus direct numeric input), line subtotal, and a remove button.
4. A prominent **Total** (`Rp`) that updates instantly as the cart changes.
5. Optional fields: **Uang diterima** (cash received) with automatic **Kembalian** (change) display, and an optional **Catatan** (note, max 255 chars). Cash received is informational and must not block saving; if filled and less than total, show a warning but still allow saving.
6. Buttons: **Simpan Transaksi** (primary) and **Kosongkan** (clear cart).
7. A **Recent transactions** list (last 5) below the cart for quick checking.

**Behavior**

- Prices come from the database and are rendered into the page. The JS total is for display only; the **server recalculates everything** from product IDs and quantities. Never trust prices or totals sent from the browser.
- Disable the save button when the cart is empty or while a request is being submitted (prevent double submit).
- After a successful save: show a success message with the invoice number and total, clear the cart, refresh the summary strip and recent list. Use POST-Redirect-GET or an AJAX call that returns JSON; either is acceptable.
- A transaction may contain one or more products.
- Invoice number format: `TRX-YYYYMMDD-NNNN` (daily sequence starting at 0001). Generate it inside the DB transaction and guarantee uniqueness.
- Saving must be atomic: insert into `sales` and `sale_items` inside one DB transaction; roll back on any error.
- `sold_at` defaults to the current server time. (Optional/Should: allow choosing a past date/time to enter forgotten sales.)

### 4.2 FR-2: Transaction History (`/history.php`)

- Table list, newest first: invoice number, date and time, items summary (e.g., `2x Galon Besar, 1x Galon Baru`), total, status.
- Filters: date range (from/to), status (all/completed/void), and invoice search. Default range = today.
- Pagination (20 per page).
- Footer totals for the filtered result: transaction count and total revenue (completed only).
- **Detail view** (`/history_detail.php?id=`): all items with qty, unit price, subtotal; total; cash received/change; note; status.
- **Void action**: button `Batalkan Transaksi` with a confirmation dialog and optional reason (max 255 chars). This is a **soft delete** (`status = 'void'`, `voided_at`, `void_reason`). Voided sales cannot be edited or un-voided in v1. Hard deletion of sales is not allowed.

### 4.3 FR-3: Product and Price Management (`/products.php`)

- List the three products with editable `price` and `cost_price` and an active toggle.
- Show the resulting **margin per unit** (`price - cost_price`) live while editing.
- Validate: integers only, price > 0, cost >= 0. Warn (do not block) if cost > price.
- Product creation/deletion is not required in v1 (exactly three products, seeded by the SQL file). Names and codes are fixed; only price, cost and active status are editable.
- Show a note: *Perubahan harga hanya berlaku untuk transaksi berikutnya.*

### 4.4 FR-4: Operational Expenses (`/expenses.php`)

- Form: **date** (default today), **category**, **description** (optional, max 255), **amount** (Rp, integer > 0).
- Fixed categories (PHP constant array): `Listrik`, `Air (PDAM)`, `Filter & Perawatan`, `Gaji`, `Transport`, `Sewa`, `Lainnya`.
- List with filters (date range, category), newest first, pagination, and a total for the filtered range.
- Edit and delete (hard delete with confirmation) are allowed.
- Show a hint: *Pembelian galon baru dari pabrik tidak perlu dicatat di sini karena sudah dihitung sebagai modal barang.*

### 4.5 FR-5: Reports (`/reports.php`)

One page with three tabs/modes: **Harian**, **Mingguan**, **Bulanan**.

| Mode | Selector | Range |
| --- | --- | --- |
| Daily | Date picker (default today) | That day, 00:00:00 to 23:59:59 |
| Weekly | Any date picker (default today) | Monday to Sunday containing the date; show the resolved range |
| Monthly | Month + year selector (default current) | First to last day of the month |

Every mode shows these **summary cards**:

1. Total Pendapatan (Revenue)
2. Total Modal Barang (COGS)
3. Laba Kotor (Gross Profit)
4. Total Pengeluaran Operasional (Expenses)
5. **Laba Bersih (Net Profit)**: visually emphasized; green if >= 0, red if < 0
6. Number of transactions

Below the cards:

- **Per-product breakdown table**: product, quantity sold, revenue, COGS, gross profit.
- **Expense breakdown by category**: category, total.
- **Daily breakdown table** (weekly and monthly modes): one row per date in the range (include days with zero activity) with revenue, COGS, gross profit, expenses, net profit, and a totals row.
- Optional (Could): a simple bar chart of daily revenue built with plain CSS/SVG or a locally bundled lightweight library. No heavy dependency required.
- Previous/Next period navigation buttons.

All figures must exclude voided sales and must match the sums in History and Expenses for the same range.

### 4.6 FR-6: Navigation and Layout

- Top navigation (collapses to a hamburger or bottom bar on mobile): **Kasir** (home), **Riwayat**, **Laporan**, **Pengeluaran**, **Produk**.
- Shared header/footer partials. Branding text: **Sumber Urip**.
- Clean, uncluttered design with a water-themed palette (blue/teal on white). Large readable numbers. Minimum touch target 44 px.

---

## 5. Database Design

Database name: `sumber_urip`. Charset `utf8mb4`, collation `utf8mb4_unicode_ci`, engine InnoDB. Deliver as `database/schema.sql` containing the tables and seed data below.

```sql
CREATE TABLE products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  price INT UNSIGNED NOT NULL,
  cost_price INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sales (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(20) NOT NULL UNIQUE,
  sold_at DATETIME NOT NULL,
  total_amount INT UNSIGNED NOT NULL,
  total_cost INT UNSIGNED NOT NULL,
  cash_received INT UNSIGNED NULL,
  change_amount INT NULL,
  note VARCHAR(255) NULL,
  status ENUM('completed','void') NOT NULL DEFAULT 'completed',
  voided_at DATETIME NULL,
  void_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sales_sold_at (sold_at),
  INDEX idx_sales_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(100) NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  unit_price INT UNSIGNED NOT NULL,
  unit_cost INT UNSIGNED NOT NULL,
  subtotal INT UNSIGNED NOT NULL,
  cost_subtotal INT UNSIGNED NOT NULL,
  CONSTRAINT fk_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id),
  INDEX idx_items_sale (sale_id),
  INDEX idx_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE expenses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  category VARCHAR(50) NOT NULL,
  description VARCHAR(255) NULL,
  amount INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_expenses_date (expense_date),
  INDEX idx_expenses_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (code, name, price, cost_price, sort_order) VALUES
('GALON_BESAR', 'Galon Besar', 6000, 2500, 1),
('GALON_KECIL', 'Galon Kecil', 3000, 1200, 2),
('GALON_BARU', 'Galon Baru (Pabrik)', 55000, 45000, 3);
```

**Notes**

- `total_cost` and `unit_cost` store the cost snapshot used for profit reports.
- `product_name` is also snapshotted so history stays readable even if names change later.
- Report queries must aggregate on `sales.status = 'completed'` and use range conditions such as `sold_at >= :start AND sold_at < :end_exclusive` (index friendly), not `DATE(sold_at)`.

---

## 6. Suggested Project Structure

```
sumber-urip/
|-- index.php              # Kasir (POS)
|-- history.php
|-- history_detail.php
|-- reports.php
|-- expenses.php
|-- products.php
|-- actions/               # POST handlers (save_sale, void_sale, save_expense, ...)
|-- config/
|   |-- config.php         # DB credentials, timezone, constants (categories, page size)
|   `-- database.php       # PDO singleton
|-- includes/
|   |-- header.php
|   |-- footer.php
|   |-- functions.php      # formatRupiah(), formatDate(), csrf helpers, date-range helpers
|   `-- queries.php        # report and summary queries
|-- assets/
|   |-- css/style.css
|   `-- js/pos.js, app.js
|-- database/schema.sql
`-- README.md              # Setup guide in English
```

Code must be readable and commented, with logic separated from presentation where practical (queries in functions, HTML in templates).

---

## 7. Non-Functional Requirements

### 7.1 Security (even without login)

- PDO prepared statements for **all** queries; no string-concatenated SQL.
- Escape all output with `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`.
- CSRF token (session-based) on every POST form/request.
- Server-side validation of every input (types, ranges, allowed categories, valid dates). Return friendly Indonesian error messages.
- Use `POST` for state-changing actions; never change data via `GET`.
- Do not display raw PHP/DB errors to users; log them to a file (`error_log`) and show a generic message. Provide a `display_errors` toggle in config.
- Keep DB credentials only in `config/config.php`; block directory listing and direct access to `config/`, `includes/`, `database/` via `.htaccess`.
- **Known limitation to document in README:** because there is no login, anyone who can open the URL can use the app. Recommend running it on a local network or protecting it with server-level HTTP Basic Auth. This is outside application scope.

### 7.2 Data Integrity

- Money is always integer Rupiah; never use floats.
- Use DB transactions for saving a sale. Sale totals are computed server-side from `products` rows (fetched inside the transaction).
- Reject saving if a product is inactive or does not exist.

### 7.3 Performance and Usability

- Page load under 1 second on a basic shared host for normal data volumes (up to \~100k sales).
- Mobile-first responsive layout; works from 360 px width upward.
- Number inputs use `inputmode="numeric"`; Rupiah values are formatted with thousand separators (dot).
- Clear feedback for success and error states; confirmation dialogs for void and delete.

---

## 8. User Flows

**Record a sale:** Open Kasir -> tap *Galon Besar* twice and *Galon Baru* once -> total shows automatically -> (optional) enter cash received to see change -> tap *Simpan Transaksi* -> success message with invoice number -> cart resets.

**Check today's profit:** Open Laporan -> Harian (today preselected) -> read Revenue, COGS, Gross Profit, Expenses, Net Profit.

**Record an expense:** Open Pengeluaran -> choose category, enter amount -> Save -> appears in list and is deducted from net profit in reports.

**Fix a mistake:** Open Riwayat -> open the transaction -> *Batalkan Transaksi* -> confirm -> sale is marked void and excluded from reports; re-enter the correct sale from Kasir.

**Update a price:** Open Produk -> edit price/cost -> Save -> only new sales use the new values.

---

## 9. Acceptance Criteria

1. Importing `schema.sql` creates all tables and seeds exactly three products.
2. On Kasir, tapping a product button adds it to the cart; tapping again increases quantity; total updates instantly and equals the sum of `qty x price`.
3. Saving a sale stores the sale and items atomically with the correct invoice number, and the stored `total_amount` equals a server-side recalculation even if the browser payload is tampered with.
4. Changing a product price afterward does **not** change old transactions, history totals or past reports.
5. Daily, weekly (Mon-Sun) and monthly reports return the correct Revenue, COGS, Gross Profit, Expenses and Net Profit. Example: 3 x Galon Besar (6000/2500) + 1 x Galon Baru (55000/45000) = Revenue 73.000, COGS 52.500, Gross Profit 20.500; with a 5.000 expense, Net Profit = 15.500.
6. Voided sales disappear from all report totals but remain in history with a `Dibatalkan` badge.
7. Expenses can be added, edited, deleted and filtered; reports reflect them immediately.
8. Weekly and monthly reports list every day in the range, including days with zero data.
9. Net profit displays in red when negative.
10. Invalid input (negative amount, zero quantity, unknown product, malformed date) is rejected with a clear Indonesian message and nothing is saved.
11. The app has no register or login page and no session-based user concept (a session is used only for CSRF tokens and flash messages).
12. All pages are usable on a 360 px wide phone without horizontal scrolling.
13. README explains setup in under 10 steps (create DB, import SQL, edit config, open URL).

---

## 10. Delivery and Implementation Order

**Deliverables:** full source code, `database/schema.sql`, `README.md` (setup, config, known limitations), and `.htaccess` files for basic protection.

**Recommended build order**

1. Project skeleton, config, PDO connection, helpers (`formatRupiah`, date ranges, CSRF), header/footer layout.
2. Database schema and seed data.
3. Products page (price/cost editing).
4. Kasir page with cart UI and the save-sale handler (server-side calculation, DB transaction, invoice number).
5. History list, filters, detail view and void action.
6. Expenses CRUD.
7. Reports (daily, weekly, monthly) with summary cards and breakdown tables.
8. Responsive polish, validation messages, security pass, README.

**Priority (MoSCoW)**

- **Must:** FR-1 (without past-date entry), FR-2, FR-3, FR-4, FR-5 (cards and breakdown tables), security rules, price/cost snapshot.
- **Should:** cash received and change, past-date sale entry, previous/next period navigation, invoice search.
- **Could:** revenue bar chart, print-friendly receipt/report view, CSV export of reports.

---

## 11. Open Questions for the Owner (do not block development)

- Confirm the real selling price and cost for each of the three products (seed values are placeholders).
- Should refill cost include electricity and filter wear, or should those stay as separate operational expenses? (Default in this PRD: estimated per-refill cost lives in `cost_price`; large recurring bills go under expenses.)
- Is stock counting of new gallons needed in a later version?
