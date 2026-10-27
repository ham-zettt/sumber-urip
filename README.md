# Sumber Urip - Water Refill Shop Management Web App

A fast, touch-optimized web application for **Sumber Urip**, a drinking-water gallon refill depot in Indonesia. The system enables the cashier or owner to record sales in under 5 seconds with one tap per product, automatically snapshot historical costs and prices, track operational expenses, and calculate daily, weekly, and monthly net profit.

Built strictly according to [`PRD.md`](PRD.md) and [`DESIGN.md`](DESIGN.md) (Atlassian Enterprise Design System).

---

## 1. Tech Stack & Architecture

- **Backend:** Native PHP 8.0+ (No frameworks, zero composer dependencies required).
- **Database:** MySQL 5.7+ / MariaDB 10.3+, InnoDB, `utf8mb4_unicode_ci`.
- **Database Access:** PDO with prepared statements, exceptions enabled, and `SET time_zone = '+07:00'`.
- **Frontend:** Pure HTML5, CSS3, and Vanilla JavaScript.
- **Design System:** Atlassian Design System tokens (monochromatic near-black `#101214`, warm background `#f8f8f8`, navy accent `#1c2b42`, high contrast WCAG AAA compliant, pill buttons, touch target >= 44px).
- **Timezone:** `Asia/Jakarta` (WIB, UTC+7).
- **Currency:** Indonesian Rupiah (Integer storage, formatted as `Rp 12.500`).

---

## 2. Quick Setup Guide (Under 5 Minutes)

1. **Clone or Place Files**  
   Ensure the project folder `sumber-urip` is located in your web server's root (e.g. `/Applications/XAMPP/xamppfiles/htdocs/sumber-urip` or `C:\xampp\htdocs\sumber-urip`).

2. **Start Web Server and MySQL**  
   Start Apache and MySQL from XAMPP, Laragon, or your local service manager.

3. **Import Database Schema**  
   Import [`database/schema.sql`](database/schema.sql) into MySQL using phpMyAdmin or the terminal:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   *Note: This creates the `sumber_urip` database and seeds the 3 default products (`GALON_BESAR`, `GALON_KECIL`, `GALON_BARU`).*

4. **Verify Database Configuration**  
   Open [`config/config.php`](config/config.php) and verify the credentials match your environment:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'sumber_urip');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

5. **Open in Browser**  
   Navigate to:
   ```
   http://localhost/sumber-urip/
   ```

---

## 3. Key Features

- **POS / Kasir (`index.php`)**:
  - Live **Today Summary Strip** showing today's revenue, transaction count, and gross profit.
  - Large product tap buttons (min height 90px) to add 1 unit in a single tap.
  - Interactive cart with quantity adjusters (`-` / `+`, direct input), item removal, and live total calculation.
  - Cash received input with automatic change (`Kembalian`) calculation.
  - Instant server-side calculation and DB transaction with atomic invoice generation (`TRX-YYYYMMDD-NNNN`).
  - Past date/time override for retroactively entering forgotten sales.
  - Recent 5 transactions display.

- **Transaction History (`history.php` & `history_detail.php`)**:
  - Filterable by date range, completion status (`completed` or `void`), and invoice number search.
  - Pagination (20 per page).
  - Filtered totals (count and revenue).
  - Printable receipt format.
  - **Void Action (Soft Delete)**: Cancel transactions with a reason; automatically excludes them from all profit and sales reports while preserving audit history.

- **Financial Reports (`reports.php`)**:
  - **Harian (Daily)**: Single date view with previous/next date navigation.
  - **Mingguan (Weekly)**: Monday to Sunday range resolution.
  - **Bulanan (Monthly)**: Month and year selector.
  - **6 Financial Summary Cards**:
    1. Total Pendapatan (Revenue)
    2. Total Modal Barang (COGS)
    3. Laba Kotor (Gross Profit)
    4. Beban Operasional (Expenses)
    5. **Laba Bersih (Net Profit)** (Green if positive, Red if negative)
    6. Jumlah Transaksi
  - Product sales breakdown table with quantities, revenue, COGS, and gross profit.
  - Expense breakdown by category.
  - **Daily Breakdown Table**: Includes all days in the range (even zero-activity days).
  - Pure SVG/CSS daily revenue bar chart.

- **Operational Expenses (`expenses.php`)**:
  - Fixed categories: `Listrik`, `Air (PDAM)`, `Filter & Perawatan`, `Gaji`, `Transport`, `Sewa`, `Lainnya`.
  - Date and category filtering with pagination and totals.
  - Inline add, edit, and permanent delete with confirmation modal.
  - Clear reminder that new factory gallons are counted under COGS, not operational expenses.

- **Product & Price Management (`products.php`)**:
  - Edit selling price and unit cost price (modal).
  - Live calculation of unit margin (`price - cost`).
  - Price/Cost Snapshot rule: edits only affect subsequent transactions; past records remain immutable.

---

## 4. Security & Best Practices

- **CSRF Protection:** Cryptographically secure session token checked on all POST requests.
- **SQL Injection Prevention:** Strict PDO prepared statements everywhere.
- **XSS Prevention:** Output escaping via `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`.
- **Access Restrictions:** Sensitive folders (`config/`, `includes/`, `database/`) protected against direct access via `.htaccess`.
- **Known Limitation:** The system has no user login/authentication layer (per PRD Section 1.4 & 1.5). Anyone with access to the host URL can use the system. For multi-device use on a local Wi-Fi, it is recommended to run behind a local VPN or Apache HTTP Basic Authentication.
