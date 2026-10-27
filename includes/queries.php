<?php
/**
 * Sumber Urip - Database Queries & Reporting Helper
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Fetch products list
 */
function getProducts(bool $activeOnly = false): array {
    $pdo = Database::getConnection();
    $sql = "SELECT id, code, name, price, cost_price, is_active, sort_order, updated_at 
            FROM products";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Fetch single product by ID
 */
function getProductById(int $id): ?array {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Fetch Today Summary strip data for POS / Kasir
 */
function getTodaySummary(): array {
    $pdo = Database::getConnection();
    $today = getTodayRange();

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(total_amount), 0) as revenue,
            COALESCE(SUM(total_cost), 0) as cost
        FROM sales
        WHERE status = 'completed'
          AND sold_at >= :start 
          AND sold_at < :end_exclusive
    ");
    $stmt->execute([
        ':start' => $today['start'],
        ':end_exclusive' => $today['end_exclusive']
    ]);
    $row = $stmt->fetch();

    $revenue = (int)($row['revenue'] ?? 0);
    $cost = (int)($row['cost'] ?? 0);
    $grossProfit = $revenue - $cost;
    $count = (int)($row['total_sales'] ?? 0);

    return [
        'revenue' => $revenue,
        'cost' => $cost,
        'gross_profit' => $grossProfit,
        'transaction_count' => $count
    ];
}

/**
 * Fetch recent sales with item summary (for Kasir screen)
 */
function getRecentSales(int $limit = 5): array {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT id, invoice_no, sold_at, total_amount, total_cost, cash_received, change_amount, status
        FROM sales
        ORDER BY sold_at DESC, id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $sales = $stmt->fetchAll();

    if (empty($sales)) {
        return [];
    }

    $saleIds = array_column($sales, 'id');
    $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
    
    $itemsStmt = $pdo->prepare("
        SELECT sale_id, product_name, quantity
        FROM sale_items
        WHERE sale_id IN ($placeholders)
        ORDER BY id ASC
    ");
    $itemsStmt->execute($saleIds);
    $allItems = $itemsStmt->fetchAll();

    $itemsBySale = [];
    foreach ($allItems as $item) {
        $itemsBySale[$item['sale_id']][] = $item['quantity'] . 'x ' . $item['product_name'];
    }

    foreach ($sales as &$sale) {
        $itemsList = $itemsBySale[$sale['id']] ?? [];
        $sale['items_summary'] = implode(', ', $itemsList);
    }

    return $sales;
}

/**
 * Generate unique Invoice Number format: TRX-YYYYMMDD-NNNN
 * MUST be called inside an active DB transaction with lock.
 */
function generateInvoiceNumber(PDO $pdo, ?string $targetDateStr = null): string {
    $datePart = $targetDateStr ? date('Ymd', strtotime($targetDateStr)) : date('Ymd');
    $prefix = "TRX-{$datePart}-";

    // Lock the highest invoice for this date
    $stmt = $pdo->prepare("
        SELECT invoice_no 
        FROM sales 
        WHERE invoice_no LIKE :prefix 
        ORDER BY invoice_no DESC 
        LIMIT 1 
        FOR UPDATE
    ");
    $stmt->execute([':prefix' => $prefix . '%']);
    $lastInvoice = $stmt->fetchColumn();

    $seq = 1;
    if ($lastInvoice) {
        $lastSeqStr = substr($lastInvoice, strlen($prefix));
        $seq = (int)$lastSeqStr + 1;
    }

    return sprintf('%s%04d', $prefix, $seq);
}

/**
 * Get Overall Financial Metrics for any range
 */
function getReportMetrics(string $startDateTime, string $endDateTimeExclusive): array {
    $pdo = Database::getConnection();

    // 1. Sales & Gross Profit
    $stmtSales = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(total_amount), 0) as revenue,
            COALESCE(SUM(total_cost), 0) as cogs
        FROM sales
        WHERE status = 'completed'
          AND sold_at >= :start 
          AND sold_at < :end_exclusive
    ");
    $stmtSales->execute([
        ':start' => $startDateTime,
        ':end_exclusive' => $endDateTimeExclusive
    ]);
    $salesRow = $stmtSales->fetch();

    $revenue = (int)($salesRow['revenue'] ?? 0);
    $cogs = (int)($salesRow['cogs'] ?? 0);
    $grossProfit = $revenue - $cogs;
    $transactionCount = (int)($salesRow['total_sales'] ?? 0);

    // 2. Expenses
    $startDateOnly = substr($startDateTime, 0, 10);
    $endDateOnly = substr($endDateTimeExclusive, 0, 10);

    $stmtExp = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total_expenses
        FROM expenses
        WHERE expense_date >= :start_date 
          AND expense_date < :end_date
    ");
    $stmtExp->execute([
        ':start_date' => $startDateOnly,
        ':end_date' => $endDateOnly
    ]);
    $expRow = $stmtExp->fetch();
    $totalExpenses = (int)($expRow['total_expenses'] ?? 0);

    // 3. Net Profit
    $netProfit = $grossProfit - $totalExpenses;

    return [
        'revenue' => $revenue,
        'cogs' => $cogs,
        'gross_profit' => $grossProfit,
        'expenses' => $totalExpenses,
        'net_profit' => $netProfit,
        'transaction_count' => $transactionCount
    ];
}

/**
 * Breakdown by product for a period
 */
function getProductSalesBreakdown(string $startDateTime, string $endDateTimeExclusive): array {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("
        SELECT 
            si.product_id,
            si.product_name,
            COALESCE(SUM(si.quantity), 0) as total_qty,
            COALESCE(SUM(si.subtotal), 0) as total_revenue,
            COALESCE(SUM(si.cost_subtotal), 0) as total_cogs,
            (COALESCE(SUM(si.subtotal), 0) - COALESCE(SUM(si.cost_subtotal), 0)) as gross_profit
        FROM sale_items si
        JOIN sales s ON s.id = si.sale_id
        WHERE s.status = 'completed'
          AND s.sold_at >= :start 
          AND s.sold_at < :end_exclusive
        GROUP BY si.product_id, si.product_name
        ORDER BY total_revenue DESC, total_qty DESC
    ");
    $stmt->execute([
        ':start' => $startDateTime,
        ':end_exclusive' => $endDateTimeExclusive
    ]);

    return $stmt->fetchAll();
}

/**
 * Breakdown of expenses by category for a period
 */
function getExpenseBreakdown(string $startDateTime, string $endDateTimeExclusive): array {
    $pdo = Database::getConnection();

    $startDateOnly = substr($startDateTime, 0, 10);
    $endDateOnly = substr($endDateTimeExclusive, 0, 10);

    $stmt = $pdo->prepare("
        SELECT 
            category,
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(*) as item_count
        FROM expenses
        WHERE expense_date >= :start_date 
          AND expense_date < :end_date
        GROUP BY category
        ORDER BY total_amount DESC
    ");
    $stmt->execute([
        ':start_date' => $startDateOnly,
        ':end_date' => $endDateOnly
    ]);

    return $stmt->fetchAll();
}

/**
 * Daily breakdown for weekly and monthly reports
 * Guarantees EVERY single day in range is present, even with 0 activity
 */
function getDailyBreakdown(string $startDateTime, string $endDateTimeExclusive): array {
    $pdo = Database::getConnection();

    $startDateOnly = substr($startDateTime, 0, 10);
    $endDateOnly = substr($endDateTimeExclusive, 0, 10);

    // 1. Grouped sales per day
    $stmtSales = $pdo->prepare("
        SELECT 
            DATE(sold_at) as sale_date,
            COUNT(*) as trx_count,
            COALESCE(SUM(total_amount), 0) as revenue,
            COALESCE(SUM(total_cost), 0) as cogs
        FROM sales
        WHERE status = 'completed'
          AND sold_at >= :start 
          AND sold_at < :end_exclusive
        GROUP BY DATE(sold_at)
    ");
    $stmtSales->execute([
        ':start' => $startDateTime,
        ':end_exclusive' => $endDateTimeExclusive
    ]);
    $salesByDate = [];
    foreach ($stmtSales->fetchAll() as $row) {
        $salesByDate[$row['sale_date']] = $row;
    }

    // 2. Grouped expenses per day
    $stmtExp = $pdo->prepare("
        SELECT 
            expense_date,
            COALESCE(SUM(amount), 0) as total_amount
        FROM expenses
        WHERE expense_date >= :start_date 
          AND expense_date < :end_date
        GROUP BY expense_date
    ");
    $stmtExp->execute([
        ':start_date' => $startDateOnly,
        ':end_date' => $endDateOnly
    ]);
    $expByDate = [];
    foreach ($stmtExp->fetchAll() as $row) {
        $expByDate[$row['expense_date']] = (int)$row['total_amount'];
    }

    // 3. Generate day by day array
    $startDt = new DateTime($startDateOnly);
    $endDt = new DateTime($endDateOnly); // exclusive
    
    $dailyRows = [];
    $totals = [
        'trx_count' => 0,
        'revenue' => 0,
        'cogs' => 0,
        'gross_profit' => 0,
        'expenses' => 0,
        'net_profit' => 0
    ];

    $current = clone $startDt;
    while ($current < $endDt) {
        $dateKey = $current->format('Y-m-d');
        
        $saleData = $salesByDate[$dateKey] ?? null;
        $trxCount = $saleData ? (int)$saleData['trx_count'] : 0;
        $rev = $saleData ? (int)$saleData['revenue'] : 0;
        $cogs = $saleData ? (int)$saleData['cogs'] : 0;
        $gross = $rev - $cogs;
        $exp = $expByDate[$dateKey] ?? 0;
        $net = $gross - $exp;

        $dailyRows[] = [
            'date' => $dateKey,
            'date_display' => formatDate($dateKey),
            'day_name' => $current->format('D'),
            'trx_count' => $trxCount,
            'revenue' => $rev,
            'cogs' => $cogs,
            'gross_profit' => $gross,
            'expenses' => $exp,
            'net_profit' => $net
        ];

        $totals['trx_count'] += $trxCount;
        $totals['revenue'] += $rev;
        $totals['cogs'] += $cogs;
        $totals['gross_profit'] += $gross;
        $totals['expenses'] += $exp;
        $totals['net_profit'] += $net;

        $current->modify('+1 day');
    }

    return [
        'rows' => $dailyRows,
        'totals' => $totals
    ];
}
