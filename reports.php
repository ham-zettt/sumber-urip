<?php
/**
 * Sumber Urip - Laporan Keuangan (Reports)
 * Conforms strictly to DESIGN.md
 */

$pageTitle = 'Laporan';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/queries.php';

$mode = trim($_GET['mode'] ?? 'daily');
if (!in_array($mode, ['daily', 'weekly', 'monthly'], true)) {
    $mode = 'daily';
}

$today = date('Y-m-d');

// Determine period range based on mode
if ($mode === 'daily') {
    $dateParam = trim($_GET['date'] ?? $today);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) $dateParam = $today;
    $range = getDailyRange($dateParam);

    $prevDate = date('Y-m-d', strtotime($dateParam . ' -1 day'));
    $nextDate = date('Y-m-d', strtotime($dateParam . ' +1 day'));

    $periodTitle = $range['date_display'];
    $prevLink = "reports.php?mode=daily&date={$prevDate}";
    $nextLink = "reports.php?mode=daily&date={$nextDate}";

} elseif ($mode === 'weekly') {
    $dateParam = trim($_GET['date'] ?? $today);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) $dateParam = $today;
    $range = getWeekRange($dateParam);

    $periodTitle = $range['date_display'];
    $prevLink = "reports.php?mode=weekly&date={$range['prev_week_date']}";
    $nextLink = "reports.php?mode=weekly&date={$range['next_week_date']}";

} else { // monthly
    $yearParam = (int)($_GET['year'] ?? date('Y'));
    $monthParam = (int)($_GET['month'] ?? date('n'));
    if ($yearParam < 2000 || $yearParam > 2100) $yearParam = (int)date('Y');
    if ($monthParam < 1 || $monthParam > 12) $monthParam = (int)date('n');

    $range = getMonthRange($yearParam, $monthParam);

    $periodTitle = $range['date_display'];
    $prevLink = "reports.php?mode=monthly&year={$range['prev_year']}&month={$range['prev_month']}";
    $nextLink = "reports.php?mode=monthly&year={$range['next_year']}&month={$range['next_month']}";
}

// Fetch Metrics & Breakdowns
$metrics = getReportMetrics($range['start'], $range['end_exclusive']);
$productBreakdown = getProductSalesBreakdown($range['start'], $range['end_exclusive']);
$expenseBreakdown = getExpenseBreakdown($range['start'], $range['end_exclusive']);

// For weekly and monthly: fetch daily breakdown table
$dailyData = null;
if ($mode === 'weekly' || $mode === 'monthly') {
    $dailyData = getDailyBreakdown($range['start'], $range['end_exclusive']);
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header & Period Controls (DESIGN.md Section 8.3 & 7.8) -->
<div class="space-y-4">
  <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-3">
    <div>
      <h1 class="text-title text-ink font-bold">Laporan</h1>
      <p class="text-small text-ink-muted">Analisis pendapatan, modal barang, beban, dan laba bersih</p>
    </div>
    <div class="print:hidden">
      <button type="button" onclick="window.print()" class="h-9 inline-flex items-center gap-2 rounded border border-line-strong bg-white px-3 text-small font-medium text-ink hover:bg-surface transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>Cetak</span>
      </button>
    </div>
  </div>

  <!-- Segmented Control + Period Picker (DESIGN.md Section 7.8) -->
  <div class="card p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 print:hidden">
    <!-- Segmented Tabs -->
    <div class="inline-flex rounded-lg bg-surface p-1 self-start">
      <a href="reports.php?mode=daily<?= isset($dateParam) ? '&date=' . urlencode($dateParam) : '' ?>" class="rounded px-4 py-2 text-small transition-colors <?= $mode === 'daily' ? 'font-semibold bg-white text-brand-700 shadow-card' : 'font-medium text-ink-muted hover:text-ink' ?>">
        Harian
      </a>
      <a href="reports.php?mode=weekly<?= isset($dateParam) ? '&date=' . urlencode($dateParam) : '' ?>" class="rounded px-4 py-2 text-small transition-colors <?= $mode === 'weekly' ? 'font-semibold bg-white text-brand-700 shadow-card' : 'font-medium text-ink-muted hover:text-ink' ?>">
        Mingguan
      </a>
      <a href="reports.php?mode=monthly" class="rounded px-4 py-2 text-small transition-colors <?= $mode === 'monthly' ? 'font-semibold bg-white text-brand-700 shadow-card' : 'font-medium text-ink-muted hover:text-ink' ?>">
        Bulanan
      </a>
    </div>

    <!-- Period Navigator -->
    <div class="flex items-center gap-2 flex-wrap">
      <a href="<?= e($prevLink) ?>" class="h-9 w-9 inline-flex items-center justify-center rounded border border-line-strong bg-white text-ink hover:bg-surface transition-colors" title="Periode sebelumnya">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
      </a>

      <!-- Date selector form -->
      <form method="GET" action="reports.php" class="inline-flex items-center gap-1.5">
        <input type="hidden" name="mode" value="<?= e($mode) ?>">
        <?php if ($mode === 'daily' || $mode === 'weekly'): ?>
          <input type="date" name="date" value="<?= e($dateParam) ?>" onchange="this.form.submit()" class="h-9 rounded border border-line-strong bg-white px-2.5 text-small text-ink" />
        <?php else: ?>
          <select name="month" onchange="this.form.submit()" class="h-9 rounded border border-line-strong bg-white px-2 text-small text-ink">
            <?php
            $indonesianMonths = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            foreach ($indonesianMonths as $mNum => $mLabel): ?>
              <option value="<?= $mNum ?>" <?= $monthParam === $mNum ? 'selected' : '' ?>><?= $mLabel ?></option>
            <?php endforeach; ?>
          </select>
          <select name="year" onchange="this.form.submit()" class="h-9 rounded border border-line-strong bg-white px-2 text-small text-ink">
            <?php
            $curY = (int)date('Y');
            for ($y = $curY - 2; $y <= $curY + 1; $y++): ?>
              <option value="<?= $y ?>" <?= $yearParam === $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        <?php endif; ?>
      </form>

      <a href="<?= e($nextLink) ?>" class="h-9 w-9 inline-flex items-center justify-center rounded border border-line-strong bg-white text-ink hover:bg-surface transition-colors" title="Periode berikutnya">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </a>

      <span class="text-small text-ink-muted pl-2 font-medium"><?= e($periodTitle) ?></span>
    </div>
  </div>
</div>

<!-- 1. Six Financial Summary Cards (DESIGN.md Section 7.5 & 8.3) -->
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
  <!-- 1. Pendapatan -->
  <div class="card p-4">
    <p class="text-micro uppercase text-ink-muted">Pendapatan</p>
    <p class="mt-2 text-title tabular-nums font-semibold text-ink"><?= formatRupiah($metrics['revenue']) ?></p>
    <p class="mt-1 text-small text-ink-muted"><?= number_format($metrics['transaction_count'], 0, ',', '.') ?> transaksi selesai</p>
  </div>

  <!-- 2. Modal Barang (COGS) -->
  <div class="card p-4">
    <p class="text-micro uppercase text-ink-muted">Modal barang (COGS)</p>
    <p class="mt-2 text-title tabular-nums font-semibold text-ink"><?= formatRupiah($metrics['cogs']) ?></p>
    <p class="mt-1 text-small text-ink-muted">Modal air & galon</p>
  </div>

  <!-- 3. Laba Kotor -->
  <div class="card p-4">
    <p class="text-micro uppercase text-ink-muted">Laba kotor</p>
    <p class="mt-2 text-title tabular-nums font-semibold text-ink"><?= formatRupiah($metrics['gross_profit']) ?></p>
    <p class="mt-1 text-small text-ink-muted">Pendapatan &minus; Modal</p>
  </div>

  <!-- 4. Pengeluaran -->
  <div class="card p-4">
    <p class="text-micro uppercase text-ink-muted">Beban operasional</p>
    <p class="mt-2 text-title tabular-nums font-semibold text-danger-700"><?= formatRupiah($metrics['expenses']) ?></p>
    <p class="mt-1 text-small text-ink-muted">Listrik, air, gaji, dsb.</p>
  </div>

  <!-- 5. Laba Bersih (EMPHASIZED: DESIGN.md Section 7.5) -->
  <?php
  $isNegativeProfit = $metrics['net_profit'] < 0;
  $profitCardClass = $isNegativeProfit 
      ? 'border-danger-600/30 bg-danger-50 col-span-2 lg:col-span-1' 
      : 'border-brand-200 bg-brand-50 col-span-2 lg:col-span-1';
  $profitTextClass = $isNegativeProfit ? 'text-danger-700' : 'text-brand-700';
  ?>
  <div class="card p-4 <?= $profitCardClass ?>">
    <p class="text-micro uppercase <?= $profitTextClass ?> font-semibold">Laba bersih</p>
    <p class="mt-2 text-display tabular-nums font-bold <?= $profitTextClass ?>">
      <?= formatRupiah($metrics['net_profit']) ?>
    </p>
    <p class="mt-1 text-small <?= $profitTextClass ?> opacity-90">Laba kotor &minus; Beban</p>
  </div>

  <!-- 6. Jumlah Transaksi -->
  <div class="card p-4 col-span-2 lg:col-span-1">
    <p class="text-micro uppercase text-ink-muted">Volume penjualan</p>
    <p class="mt-2 text-title tabular-nums font-semibold text-ink"><?= number_format($metrics['transaction_count'], 0, ',', '.') ?></p>
    <p class="mt-1 text-small text-ink-muted">Total transaksi tercatat</p>
  </div>
</div>

<!-- Daily Bar Chart (DESIGN.md Section 7.10) -->
<?php if (($mode === 'weekly' || $mode === 'monthly') && !empty($dailyData['rows'])): ?>
  <?php
  $maxRevenue = 1;
  foreach ($dailyData['rows'] as $row) {
      if ($row['revenue'] > $maxRevenue) $maxRevenue = $row['revenue'];
  }
  ?>
  <div class="card p-4 space-y-3 print:hidden">
    <div class="flex items-center justify-between">
      <h2 class="text-heading text-ink font-semibold">Grafik pendapatan harian</h2>
      <span class="text-small text-ink-muted">Tertinggi: <?= formatRupiah($maxRevenue) ?></span>
    </div>

    <div class="flex items-end gap-1.5 sm:gap-2 h-36 border-b border-line pb-2 pt-4">
      <?php foreach ($dailyData['rows'] as $r): ?>
        <?php
        $pct = min(100, max(2, round(($r['revenue'] / $maxRevenue) * 100)));
        $dayLabel = date('d/m', strtotime($r['date']));
        $isToday = $r['date'] === date('Y-m-d');
        ?>
        <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
          <!-- Tooltip -->
          <div class="hidden group-hover:block absolute bottom-full mb-1 z-20 bg-ink text-white text-micro rounded px-2 py-1 shadow-card whitespace-nowrap pointer-events-none">
            <?= e($r['date_display']) ?>: <?= formatRupiah($r['revenue']) ?> (Laba: <?= formatRupiah($r['net_profit']) ?>)
          </div>

          <div class="w-full max-w-[28px] rounded-t transition-colors <?= $isToday ? 'bg-brand-800' : 'bg-brand-600 hover:bg-brand-700' ?>" style="height: <?= $pct ?>%;"></div>
          <span class="text-[0.6875rem] text-ink-muted tabular-nums mt-1"><?= e($dayLabel) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- 2. Breakdown Tables Side by Side on lg (DESIGN.md Section 8.3) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <!-- Product Breakdown Table -->
  <div class="card overflow-hidden">
    <div class="px-4 py-3 bg-surface border-b border-line">
      <h2 class="text-heading text-ink font-semibold">Rincian per produk</h2>
    </div>
    <?php if (empty($productBreakdown)): ?>
      <p class="p-4 text-small text-ink-muted">Tidak ada penjualan pada periode ini.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-surface/50 text-micro uppercase text-ink-muted h-10 border-b border-line">
              <th scope="col" class="px-4 font-semibold">Produk</th>
              <th scope="col" class="px-3 font-semibold text-center">Terjual</th>
              <th scope="col" class="px-4 font-semibold text-right">Pendapatan</th>
              <th scope="col" class="px-4 font-semibold text-right">Laba Kotor</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <?php foreach ($productBreakdown as $pb): ?>
              <tr class="h-12 hover:bg-surface/50 transition-colors">
                <td class="px-4 font-medium text-ink text-small"><?= e($pb['product_name']) ?></td>
                <td class="px-3 text-center tabular-nums text-small text-ink-soft"><?= number_format($pb['total_qty'], 0, ',', '.') ?></td>
                <td class="px-4 num tabular-nums text-small text-ink font-medium"><?= formatRupiah($pb['total_revenue']) ?></td>
                <td class="px-4 num tabular-nums text-small text-brand-700 font-semibold"><?= formatRupiah($pb['gross_profit']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Expense Breakdown by Category -->
  <div class="card overflow-hidden">
    <div class="px-4 py-3 bg-surface border-b border-line">
      <h2 class="text-heading text-ink font-semibold">Beban per kategori</h2>
    </div>
    <?php if (empty($expenseBreakdown)): ?>
      <p class="p-4 text-small text-ink-muted">Tidak ada beban operasional pada periode ini.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-surface/50 text-micro uppercase text-ink-muted h-10 border-b border-line">
              <th scope="col" class="px-4 font-semibold">Kategori</th>
              <th scope="col" class="px-4 font-semibold text-center">Frekuensi</th>
              <th scope="col" class="px-4 font-semibold text-right">Total Biaya</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <?php foreach ($expenseBreakdown as $eb): ?>
              <tr class="h-12 hover:bg-surface/50 transition-colors">
                <td class="px-4 font-medium text-ink text-small">
                  <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-micro font-medium text-brand-700">
                    <?= e($eb['category']) ?>
                  </span>
                </td>
                <td class="px-4 text-center tabular-nums text-small text-ink-soft"><?= $eb['item_count'] ?> kali</td>
                <td class="px-4 num tabular-nums text-small text-danger-700 font-semibold"><?= formatRupiah($eb['total_amount']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- 3. Daily Breakdown Table for Weekly & Monthly modes (DESIGN.md Section 8.3 & PRD AC-8) -->
<?php if (($mode === 'weekly' || $mode === 'monthly') && !empty($dailyData)): ?>
  <div class="card overflow-hidden">
    <div class="px-4 py-3 bg-surface border-b border-line flex items-center justify-between">
      <h2 class="text-heading text-ink font-semibold">Rincian harian periode</h2>
      <span class="text-small text-ink-muted">Menampilkan semua hari (termasuk tanpa transaksi)</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface/50 text-micro uppercase text-ink-muted h-10 border-b border-line">
            <th scope="col" class="px-4 font-semibold">Tanggal</th>
            <th scope="col" class="px-3 font-semibold text-center">Trx</th>
            <th scope="col" class="px-4 font-semibold text-right">Pendapatan</th>
            <th scope="col" class="px-4 font-semibold text-right">Modal</th>
            <th scope="col" class="px-4 font-semibold text-right">Laba Kotor</th>
            <th scope="col" class="px-4 font-semibold text-right">Beban</th>
            <th scope="col" class="px-4 font-semibold text-right">Laba Bersih</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line">
          <?php foreach ($dailyData['rows'] as $day): ?>
            <?php
            $isNegative = $day['net_profit'] < 0;
            ?>
            <tr class="h-12 hover:bg-surface/50 transition-colors">
              <td class="px-4 tabular-nums text-small font-medium text-ink">
                <a href="reports.php?mode=daily&date=<?= e($day['date']) ?>" class="text-brand-700 hover:underline">
                  <?= e($day['date_display']) ?>
                </a>
              </td>
              <td class="px-3 text-center tabular-nums text-small text-ink-soft"><?= $day['trx_count'] ?></td>
              <td class="px-4 num tabular-nums text-small text-ink"><?= formatRupiah($day['revenue']) ?></td>
              <td class="px-4 num tabular-nums text-small text-ink-soft"><?= formatRupiah($day['cogs']) ?></td>
              <td class="px-4 num tabular-nums text-small text-ink font-medium"><?= formatRupiah($day['gross_profit']) ?></td>
              <td class="px-4 num tabular-nums text-small text-danger-700"><?= formatRupiah($day['expenses']) ?></td>
              <td class="px-4 num tabular-nums text-small font-semibold <?= $isNegative ? 'text-danger-700' : 'text-success-700' ?>">
                <?= formatRupiah($day['net_profit']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="bg-surface h-12 border-t-2 border-line">
            <th class="px-4 text-small uppercase text-ink-muted font-semibold">Total Periode</th>
            <th class="px-3 text-center tabular-nums text-small font-semibold text-ink"><?= number_format($dailyData['totals']['trx_count'], 0, ',', '.') ?></th>
            <th class="px-4 num tabular-nums text-small font-semibold text-ink"><?= formatRupiah($dailyData['totals']['revenue']) ?></th>
            <th class="px-4 num tabular-nums text-small font-semibold text-ink-soft"><?= formatRupiah($dailyData['totals']['cogs']) ?></th>
            <th class="px-4 num tabular-nums text-small font-semibold text-ink"><?= formatRupiah($dailyData['totals']['gross_profit']) ?></th>
            <th class="px-4 num tabular-nums text-small font-semibold text-danger-700"><?= formatRupiah($dailyData['totals']['expenses']) ?></th>
            <th class="px-4 num tabular-nums text-body font-bold <?= $dailyData['totals']['net_profit'] < 0 ? 'text-danger-700' : 'text-brand-700' ?>">
              <?= formatRupiah($dailyData['totals']['net_profit']) ?>
            </th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
