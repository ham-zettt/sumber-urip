<?php
/**
 * Sumber Urip - Kasir (Point of Sale)
 * Conforms strictly to DESIGN.md
 */

$pageTitle = 'Kasir';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/queries.php';

// Fetch active products
$products = getProducts(true);

// Fetch Today Summary
$todaySummary = getTodaySummary();

// Fetch Recent 5 Sales
$recentSales = getRecentSales(5);

// Check if this is an AJAX partial refresh request
if (isset($_GET['ajax_refresh'])) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <div id="summaryStripContainer">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="card p-3">
          <p class="text-micro uppercase text-ink-muted">Pendapatan hari ini</p>
          <p class="mt-1 text-title tabular-nums text-ink"><?= formatRupiah($todaySummary['revenue']) ?></p>
        </div>
        <div class="card p-3">
          <p class="text-micro uppercase text-ink-muted">Transaksi selesai</p>
          <p class="mt-1 text-title tabular-nums text-ink"><?= number_format($todaySummary['transaction_count'], 0, ',', '.') ?></p>
        </div>
        <div class="card p-3 <?= $todaySummary['gross_profit'] >= 0 ? 'border-brand-200 bg-brand-50/50' : 'border-danger-600/30 bg-danger-50' ?>">
          <p class="text-micro uppercase <?= $todaySummary['gross_profit'] >= 0 ? 'text-brand-700' : 'text-danger-700' ?>">Laba kotor hari ini</p>
          <p class="mt-1 text-title tabular-nums font-bold <?= $todaySummary['gross_profit'] >= 0 ? 'text-brand-700' : 'text-danger-700' ?>">
            <?= formatRupiah($todaySummary['gross_profit']) ?>
          </p>
        </div>
      </div>
    </div>

    <div id="recentTransactionsContainer">
      <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-line flex items-center justify-between bg-surface">
          <h2 class="text-heading text-ink">Transaksi terbaru</h2>
          <a href="history.php" class="text-small font-medium text-brand-700 hover:underline">Lihat semua</a>
        </div>
        <?php if (empty($recentSales)): ?>
          <p class="p-4 text-small text-ink-muted">Belum ada transaksi hari ini.</p>
        <?php else: ?>
          <div class="divide-y divide-line">
            <?php foreach ($recentSales as $sale): ?>
              <div class="p-3 sm:px-4 flex items-center justify-between hover:bg-surface/50 transition-colors">
                <div class="min-w-0 pr-3">
                  <div class="flex items-center gap-2">
                    <a href="history_detail.php?id=<?= $sale['id'] ?>" class="text-body font-semibold text-brand-700 hover:underline tabular-nums">
                      <?= e($sale['invoice_no']) ?>
                    </a>
                    <?php if ($sale['status'] === 'completed'): ?>
                      <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-micro font-medium text-success-700">Selesai</span>
                    <?php else: ?>
                      <span class="inline-flex items-center rounded-full bg-danger-50 px-2 py-0.5 text-micro font-medium text-danger-700">Dibatalkan</span>
                    <?php endif; ?>
                  </div>
                  <div class="text-small text-ink-muted truncate mt-0.5"><?= e($sale['items_summary']) ?></div>
                </div>
                <div class="text-right flex-shrink-0">
                  <div class="text-body font-semibold text-ink tabular-nums <?= $sale['status'] === 'void' ? 'line-through text-ink-muted' : '' ?>">
                    <?= formatRupiah($sale['total_amount']) ?>
                  </div>
                  <div class="text-micro text-ink-muted tabular-nums"><?= date('H:i', strtotime($sale['sold_at'])) ?> WIB</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header (DESIGN.md Section 8.1) -->
<div class="flex items-baseline justify-between">
  <div>
    <h1 class="text-title text-ink font-bold">Kasir</h1>
    <p class="text-small text-ink-muted"><?= formatDate(date('Y-m-d')) ?> &bull; Rekam transaksi penjualan cepat</p>
  </div>
</div>

<!-- 1. Today Summary Strip -->
<div id="summaryStripContainer">
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div class="card p-3">
      <p class="text-micro uppercase text-ink-muted">Pendapatan hari ini</p>
      <p class="mt-1 text-title tabular-nums text-ink font-semibold"><?= formatRupiah($todaySummary['revenue']) ?></p>
    </div>
    <div class="card p-3">
      <p class="text-micro uppercase text-ink-muted">Transaksi selesai</p>
      <p class="mt-1 text-title tabular-nums text-ink font-semibold"><?= number_format($todaySummary['transaction_count'], 0, ',', '.') ?></p>
    </div>
    <div class="card p-3 <?= $todaySummary['gross_profit'] >= 0 ? 'border-brand-200 bg-brand-50/50' : 'border-danger-600/30 bg-danger-50' ?>">
      <p class="text-micro uppercase <?= $todaySummary['gross_profit'] >= 0 ? 'text-brand-700' : 'text-danger-700' ?>">Laba kotor hari ini</p>
      <p class="mt-1 text-title tabular-nums font-bold <?= $todaySummary['gross_profit'] >= 0 ? 'text-brand-700' : 'text-danger-700' ?>">
        <?= formatRupiah($todaySummary['gross_profit']) ?>
      </p>
    </div>
  </div>
</div>

<!-- 2. Main Kasir Grid (Left: Products + Recent, Right: Cart) -->
<div class="grid grid-cols-1 lg:grid-cols-[1fr_24rem] gap-6 items-start">
  
  <!-- Left Column -->
  <div class="space-y-6">
    <!-- Product Buttons Grid (DESIGN.md Section 7.3) -->
    <div>
      <h2 class="text-heading text-ink mb-3 font-semibold">Pilih produk</h2>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <?php foreach ($products as $p): ?>
          <?php
          $catLabel = $p['code'] === 'GALON_BARU' ? 'Galon baru' : 'Isi ulang';
          ?>
          <button type="button" 
                  data-product="<?= e($p['code']) ?>"
                  data-id="<?= $p['id'] ?>"
                  data-code="<?= e($p['code']) ?>"
                  data-name="<?= e($p['name']) ?>"
                  data-price="<?= $p['price'] ?>"
                  class="product-btn group relative flex min-h-[104px] flex-col justify-between rounded-lg border border-line bg-white p-4 text-left transition-colors hover:border-brand-300 hover:bg-brand-50/50 active:bg-brand-100 focus-visible:ring-2">
            <span class="text-micro uppercase text-ink-muted"><?= e($catLabel) ?></span>
            <span class="mt-2 block text-heading text-ink font-semibold"><?= e($p['name']) ?></span>
            <span class="mt-1 block text-body font-semibold tabular-nums text-brand-700"><?= formatRupiah($p['price']) ?></span>
            
            <!-- Quantity badge appears when item is in cart -->
            <span class="product-qty-badge absolute right-3 top-3 hidden h-6 min-w-6 items-center justify-center rounded-full bg-brand-600 px-2 text-small font-semibold tabular-nums text-white">0</span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Recent Transactions (DESIGN.md Section 8.1) -->
    <div id="recentTransactionsContainer">
      <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-line flex items-center justify-between bg-surface">
          <h2 class="text-heading text-ink font-semibold">Transaksi terbaru</h2>
          <a href="history.php" class="text-small font-medium text-brand-700 hover:underline">Lihat semua</a>
        </div>
        <?php if (empty($recentSales)): ?>
          <p class="p-4 text-small text-ink-muted">Belum ada transaksi hari ini.</p>
        <?php else: ?>
          <div class="divide-y divide-line">
            <?php foreach ($recentSales as $sale): ?>
              <div class="p-3 sm:px-4 flex items-center justify-between hover:bg-surface/50 transition-colors">
                <div class="min-w-0 pr-3">
                  <div class="flex items-center gap-2">
                    <a href="history_detail.php?id=<?= $sale['id'] ?>" class="text-body font-semibold text-brand-700 hover:underline tabular-nums">
                      <?= e($sale['invoice_no']) ?>
                    </a>
                    <?php if ($sale['status'] === 'completed'): ?>
                      <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-micro font-medium text-success-700">Selesai</span>
                    <?php else: ?>
                      <span class="inline-flex items-center rounded-full bg-danger-50 px-2 py-0.5 text-micro font-medium text-danger-700">Dibatalkan</span>
                    <?php endif; ?>
                  </div>
                  <div class="text-small text-ink-muted truncate mt-0.5"><?= e($sale['items_summary']) ?></div>
                </div>
                <div class="text-right flex-shrink-0">
                  <div class="text-body font-semibold text-ink tabular-nums <?= $sale['status'] === 'void' ? 'line-through text-ink-muted' : '' ?>">
                    <?= formatRupiah($sale['total_amount']) ?>
                  </div>
                  <div class="text-micro text-ink-muted tabular-nums"><?= date('H:i', strtotime($sale['sold_at'])) ?> WIB</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Column: Cart Panel (DESIGN.md Section 7.4) -->
  <div>
    <div class="card p-5 lg:sticky lg:top-6 w-full space-y-4">
      <div class="flex items-center justify-between border-b border-line pb-3">
        <h2 class="text-heading text-ink font-semibold">Keranjang</h2>
        <span class="text-micro uppercase text-ink-muted">Penjualan saat ini</span>
      </div>

      <!-- Items List -->
      <div id="cartItemsList" class="divide-y divide-line max-h-80 overflow-y-auto">
        <!-- Rendered dynamically by pos.js -->
      </div>
      <div id="cartEmptyMessage" class="text-center py-8 text-small text-ink-muted">
        Belum ada item. Ketuk produk untuk menambahkan.
      </div>

      <!-- Total Block (DESIGN.md Section 7.4) -->
      <div class="border-t border-line pt-3">
        <p class="text-micro uppercase text-ink-muted">Total</p>
        <p id="cartTotalDisplay" class="text-display tabular-nums text-ink font-bold leading-tight">Rp 0</p>
      </div>

      <!-- Cash Received & Change Calculation -->
      <div class="space-y-2 border-t border-line pt-3">
        <div>
          <label for="cashReceivedInput" class="label">Uang diterima (Rp)</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-small text-ink-muted pointer-events-none">Rp</span>
            <input id="cashReceivedInput" inputmode="numeric" placeholder="Contoh: 50.000" class="h-11 w-full rounded border border-line-strong bg-white pl-10 pr-3 text-body tabular-nums text-ink placeholder:text-ink-faint transition-colors hover:border-ink-faint focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
          </div>
        </div>

        <div id="changeDisplayWrap" class="hidden flex items-center justify-between py-1 px-2.5 rounded bg-surface">
          <span class="text-small text-ink-soft font-medium">Kembalian:</span>
          <span id="changeValueDisplay" class="font-semibold text-success-700 tabular-nums">Rp 0</span>
        </div>
        <p id="changeWarningNotice" class="hidden text-small text-warning-600"></p>

        <div>
          <label for="noteInput" class="label">Catatan (opsional)</label>
          <input id="noteInput" type="text" maxlength="255" placeholder="Contoh: Titip 2 galon kosong" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink placeholder:text-ink-faint transition-colors hover:border-ink-faint focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
        </div>

        <!-- Forgotten Sale Date Entry -->
        <details class="text-small">
          <summary class="cursor-pointer text-brand-700 font-medium py-1">Atur tanggal transaksi lampau</summary>
          <div class="mt-2 p-3 bg-surface rounded border border-line space-y-1">
            <label for="soldAtInput" class="label">Waktu transaksi</label>
            <input type="datetime-local" id="soldAtInput" class="h-10 w-full rounded border border-line-strong bg-white px-2.5 text-small text-ink" />
            <p class="text-micro text-ink-muted">Kosongkan untuk memakai waktu saat ini.</p>
          </div>
        </details>
      </div>

      <?= csrfField() ?>

      <!-- Buttons (DESIGN.md Section 7.1) -->
      <div class="flex gap-2 pt-2">
        <button type="button" id="btnClearCart" disabled class="inline-flex h-11 items-center justify-center gap-2 rounded border border-line-strong bg-white px-4 text-body font-medium text-ink transition-colors hover:bg-surface active:bg-surface-sunken disabled:text-ink-faint disabled:cursor-not-allowed">
          Kosongkan
        </button>
        <button type="button" id="btnSaveSale" disabled class="flex-1 inline-flex h-11 items-center justify-center gap-2 rounded bg-brand-600 px-5 text-body font-semibold text-white transition-colors hover:bg-brand-700 active:bg-brand-800 disabled:cursor-not-allowed disabled:bg-line-strong disabled:text-white">
          Simpan transaksi
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Mobile Sticky Bottom Bar (DESIGN.md Section 7.4) -->
<div id="mobileStickyBar" class="hidden lg:hidden fixed bottom-14 inset-x-0 bg-white border-t border-line px-4 py-2.5 flex items-center justify-between z-30 shadow-card">
  <div>
    <div id="mobileStickyCount" class="text-micro uppercase text-ink-muted">0 item</div>
    <div id="mobileStickyTotal" class="text-heading font-bold text-ink tabular-nums">Rp 0</div>
  </div>
  <button type="button" id="btnMobileSave" class="h-11 px-5 rounded bg-brand-600 text-body font-semibold text-white hover:bg-brand-700 active:bg-brand-800 transition-colors">
    Simpan
  </button>
</div>

<!-- Modal Dialog: Sale Success (DESIGN.md Section 7.9) -->
<div id="saleSuccessModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-ink/40">
  <div class="bg-white border border-line rounded-xl shadow-pop max-w-sm w-full p-6 space-y-4 animate-in fade-in zoom-in-95 duration-150">
    <div class="text-center space-y-2">
      <div class="w-12 h-12 rounded-full bg-success-50 text-success-600 mx-auto flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
      </div>
      <h3 class="text-heading font-bold text-ink">Transaksi berhasil</h3>
      <p id="modalInvoiceNo" class="text-small text-ink-muted tabular-nums font-semibold"></p>
    </div>

    <div class="bg-surface rounded-lg p-3 space-y-2 text-small">
      <div class="flex justify-between">
        <span class="text-ink-muted">Total:</span>
        <span id="modalTotalAmount" class="font-semibold text-ink tabular-nums">Rp 0</span>
      </div>
      <div class="flex justify-between">
        <span class="text-ink-muted">Uang diterima:</span>
        <span id="modalCashReceived" class="tabular-nums text-ink">-</span>
      </div>
      <div class="flex justify-between border-t border-line pt-2 font-semibold">
        <span class="text-ink-soft">Kembalian:</span>
        <span id="modalChangeAmount" class="tabular-nums text-success-700">-</span>
      </div>
    </div>

    <div class="flex gap-2 pt-2">
      <button type="button" onclick="closeSaleSuccessModal()" class="flex-1 h-11 inline-flex items-center justify-center rounded border border-line-strong bg-white text-body font-medium text-ink hover:bg-surface transition-colors">
        Transaksi baru
      </button>
      <a href="#" id="modalDetailLink" class="flex-1 h-11 inline-flex items-center justify-center rounded bg-brand-600 text-body font-semibold text-white hover:bg-brand-700 transition-colors">
        Lihat struk
      </a>
    </div>
  </div>
</div>

<?php
$extraScripts = '<script src="assets/js/pos.js"></script>';
require_once __DIR__ . '/includes/footer.php';
?>
