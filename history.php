<?php
/**
 * Sumber Urip - Riwayat Transaksi (History)
 * Conforms strictly to DESIGN.md
 */

$pageTitle = 'Riwayat Transaksi';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/queries.php';

$pdo = Database::getConnection();

// Filter inputs
$today = date('Y-m-d');
$startDate = trim($_GET['start_date'] ?? $today);
$endDate = trim($_GET['end_date'] ?? $today);
$status = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $endDate = $today;

$startDateTime = $startDate . ' 00:00:00';
$endExclusive = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

// Base WHERE clauses
$where = ["s.sold_at >= :start AND s.sold_at < :end_exclusive"];
$params = [
    ':start' => $startDateTime,
    ':end_exclusive' => $endExclusive
];

if (in_array($status, ['completed', 'void'], true)) {
    $where[] = "s.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where[] = "(s.invoice_no LIKE :search OR s.note LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);

// Count total items and calculate totals for filtered result
$countSql = "
    SELECT 
        COUNT(*) as total_rows,
        SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        COALESCE(SUM(CASE WHEN s.status = 'completed' THEN s.total_amount ELSE 0 END), 0) as total_revenue
    FROM sales s
    WHERE $whereSql
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$summaryRow = $countStmt->fetch();

$totalRows = (int)($summaryRow['total_rows'] ?? 0);
$completedCount = (int)($summaryRow['completed_count'] ?? 0);
$totalRevenue = (int)($summaryRow['total_revenue'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch paginated sales
$listSql = "
    SELECT s.id, s.invoice_no, s.sold_at, s.total_amount, s.cash_received, s.change_amount, s.status, s.note
    FROM sales s
    WHERE $whereSql
    ORDER BY s.sold_at DESC, s.id DESC
    LIMIT :limit OFFSET :offset
";
$listStmt = $pdo->prepare($listSql);
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$sales = $listStmt->fetchAll();

// Fetch items summary for listed sales
$itemsBySale = [];
if (!empty($sales)) {
    $saleIds = array_column($sales, 'id');
    $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
    
    $itemsStmt = $pdo->prepare("
        SELECT sale_id, product_name, quantity
        FROM sale_items
        WHERE sale_id IN ($placeholders)
        ORDER BY id ASC
    ");
    $itemsStmt->execute($saleIds);
    foreach ($itemsStmt->fetchAll() as $item) {
        $itemsBySale[$item['sale_id']][] = $item['quantity'] . 'x ' . $item['product_name'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Title (DESIGN.md Section 4 & 8.2) -->
<div class="flex items-baseline justify-between">
  <div>
    <h1 class="text-title text-ink font-bold">Riwayat</h1>
    <p class="text-small text-ink-muted">Daftar transaksi penjualan dan struk pembayaran</p>
  </div>
</div>

<!-- Filter Row (DESIGN.md Section 8.2) -->
<div class="card p-4">
  <form method="GET" action="history.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
    <div>
      <label for="filterStartDate" class="label">Dari tanggal</label>
      <input type="date" id="filterStartDate" name="start_date" value="<?= e($startDate) ?>" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
    </div>

    <div>
      <label for="filterEndDate" class="label">Sampai tanggal</label>
      <input type="date" id="filterEndDate" name="end_date" value="<?= e($endDate) ?>" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
    </div>

    <div>
      <label for="filterStatus" class="label">Status</label>
      <select id="filterStatus" name="status" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua status</option>
        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option>
        <option value="void" <?= $status === 'void' ? 'selected' : '' ?>>Dibatalkan</option>
      </select>
    </div>

    <div>
      <label for="filterSearch" class="label">Cari nota</label>
      <input type="text" id="filterSearch" name="search" placeholder="Nomor nota..." value="<?= e($search) ?>" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink placeholder:text-ink-faint focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
    </div>

    <div class="flex gap-2">
      <button type="submit" class="flex-1 h-11 inline-flex items-center justify-center rounded bg-brand-600 px-4 text-body font-semibold text-white hover:bg-brand-700 transition-colors">
        Terapkan
      </button>
      <a href="history.php" class="h-11 inline-flex items-center justify-center rounded px-3 text-body font-medium text-brand-700 hover:bg-brand-50 transition-colors">
        Reset
      </a>
    </div>
  </form>
</div>

<!-- Transactions List / Table (DESIGN.md Section 7.6) -->
<div class="card overflow-hidden">
  <?php if (empty($sales)): ?>
    <div class="text-center py-12 text-small text-ink-muted">
      Tidak ada transaksi yang cocok dengan kriteria filter.
    </div>
  <?php else: ?>
    <!-- Desktop Table View (>= md) -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface text-micro uppercase text-ink-muted h-10 border-b border-line">
            <th scope="col" class="px-4 font-semibold">No. Nota</th>
            <th scope="col" class="px-4 font-semibold">Waktu</th>
            <th scope="col" class="px-4 font-semibold">Item</th>
            <th scope="col" class="px-4 font-semibold text-right">Total</th>
            <th scope="col" class="px-4 font-semibold text-center">Status</th>
            <th scope="col" class="px-4 font-semibold text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sales as $sale): ?>
            <?php
            $isVoid = $sale['status'] === 'void';
            $rowTextClass = $isVoid ? 'text-ink-muted' : 'text-ink';
            ?>
            <tr class="h-14 border-b border-line last:border-0 hover:bg-surface/60 transition-colors">
              <td class="px-4 tabular-nums">
                <a href="history_detail.php?id=<?= $sale['id'] ?>" class="font-semibold text-brand-700 hover:underline">
                  <?= e($sale['invoice_no']) ?>
                </a>
                <?php if (!empty($sale['note'])): ?>
                  <div class="text-micro text-ink-muted truncate max-w-xs"><?= e($sale['note']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 tabular-nums text-small <?= $rowTextClass ?>"><?= formatDate($sale['sold_at'], true) ?></td>
              <td class="px-4 text-small <?= $rowTextClass ?> max-w-sm truncate">
                <?= e(implode(', ', $itemsBySale[$sale['id']] ?? [])) ?>
              </td>
              <td class="px-4 num font-semibold <?= $isVoid ? 'line-through text-ink-muted' : 'text-ink' ?>">
                <?= formatRupiah($sale['total_amount']) ?>
              </td>
              <td class="px-4 text-center">
                <?php if (!$isVoid): ?>
                  <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-small font-medium text-success-700">Selesai</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full bg-danger-50 px-2.5 py-0.5 text-small font-medium text-danger-700">Dibatalkan</span>
                <?php endif; ?>
              </td>
              <td class="px-4 text-right">
                <a href="history_detail.php?id=<?= $sale['id'] ?>" class="inline-flex h-9 items-center gap-1 rounded px-3 text-small font-medium text-brand-700 hover:bg-brand-50 transition-colors">
                  Detail
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile Stacked List View (< md) (DESIGN.md Section 7.6) -->
    <div class="md:hidden divide-y divide-line">
      <?php foreach ($sales as $sale): ?>
        <?php $isVoid = $sale['status'] === 'void'; ?>
        <a href="history_detail.php?id=<?= $sale['id'] ?>" class="block p-4 hover:bg-surface/60 transition-colors">
          <div class="flex items-baseline justify-between gap-2">
            <span class="font-semibold text-body text-brand-700 tabular-nums"><?= e($sale['invoice_no']) ?></span>
            <span class="font-semibold text-body tabular-nums <?= $isVoid ? 'line-through text-ink-muted' : 'text-ink' ?>">
              <?= formatRupiah($sale['total_amount']) ?>
            </span>
          </div>
          <div class="flex items-center justify-between text-small text-ink-muted mt-1">
            <span class="truncate pr-2"><?= e(implode(', ', $itemsBySale[$sale['id']] ?? [])) ?></span>
            <span class="flex-shrink-0 tabular-nums"><?= date('d M, H:i', strtotime($sale['sold_at'])) ?></span>
          </div>
          <div class="mt-2 flex items-center justify-between">
            <div>
              <?php if (!$isVoid): ?>
                <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-micro font-medium text-success-700">Selesai</span>
              <?php else: ?>
                <span class="inline-flex items-center rounded-full bg-danger-50 px-2 py-0.5 text-micro font-medium text-danger-700">Dibatalkan</span>
              <?php endif; ?>
            </div>
            <span class="text-micro font-medium text-brand-700">Lihat detail &rarr;</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Footer Summary (DESIGN.md Section 8.2) -->
    <div class="p-4 bg-surface border-t border-line flex flex-col sm:flex-row items-center justify-between gap-2 text-small text-ink-soft">
      <div>
        Menampilkan <strong><?= count($sales) ?></strong> dari <strong><?= number_format($totalRows, 0, ',', '.') ?></strong> transaksi &bull; Total selesai: <strong class="text-ink font-semibold tabular-nums"><?= formatRupiah($totalRevenue) ?></strong>
      </div>

      <!-- Pagination (DESIGN.md Section 7.6) -->
      <?php if ($totalPages > 1): ?>
        <?php $queryParams = $_GET; ?>
        <div class="flex items-center gap-3">
          <?php if ($page > 1): ?>
            <?php $queryParams['page'] = $page - 1; ?>
            <a href="?<?= http_build_query($queryParams) ?>" class="inline-flex h-9 items-center rounded px-3 text-small font-medium text-brand-700 hover:bg-white border border-line">Sebelumnya</a>
          <?php else: ?>
            <span class="inline-flex h-9 items-center rounded px-3 text-small text-ink-faint border border-line cursor-not-allowed">Sebelumnya</span>
          <?php endif; ?>

          <span class="text-small text-ink-muted tabular-nums">Halaman <?= $page ?> dari <?= $totalPages ?></span>

          <?php if ($page < $totalPages): ?>
            <?php $queryParams['page'] = $page + 1; ?>
            <a href="?<?= http_build_query($queryParams) ?>" class="inline-flex h-9 items-center rounded px-3 text-small font-medium text-brand-700 hover:bg-white border border-line">Berikutnya</a>
          <?php else: ?>
            <span class="inline-flex h-9 items-center rounded px-3 text-small text-ink-faint border border-line cursor-not-allowed">Berikutnya</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
