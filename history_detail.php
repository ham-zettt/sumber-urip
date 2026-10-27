<?php
/**
 * Sumber Urip - Detail Transaksi & Pembatalan (Void)
 * Conforms strictly to DESIGN.md
 */

$pageTitle = 'Detail Transaksi';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/queries.php';

$saleId = (int)($_GET['id'] ?? 0);

if ($saleId <= 0) {
    setFlashMessage('error', 'ID transaksi tidak valid.');
    redirect('history.php');
}

$pdo = Database::getConnection();

// Fetch sale details
$stmt = $pdo->prepare("
    SELECT id, invoice_no, sold_at, total_amount, total_cost, cash_received, change_amount, note, status, voided_at, void_reason, created_at
    FROM sales
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $saleId]);
$sale = $stmt->fetch();

if (!$sale) {
    setFlashMessage('error', 'Transaksi tidak ditemukan.');
    redirect('history.php');
}

// Fetch sale items
$itemsStmt = $pdo->prepare("
    SELECT id, product_id, product_name, quantity, unit_price, unit_cost, subtotal, cost_subtotal
    FROM sale_items
    WHERE sale_id = :sale_id
    ORDER BY id ASC
");
$itemsStmt->execute([':sale_id' => $saleId]);
$items = $itemsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header (DESIGN.md Section 8.2) -->
<div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-3">
  <div>
    <h1 class="text-title text-ink font-bold tabular-nums">Nota <?= e($sale['invoice_no']) ?></h1>
    <p class="text-small text-ink-muted"><?= formatDate($sale['sold_at'], true) ?> WIB</p>
  </div>
  <div class="flex items-center gap-2 print:hidden">
    <a href="history.php" class="h-11 inline-flex items-center gap-2 rounded border border-line-strong bg-white px-4 text-body font-medium text-ink hover:bg-surface transition-colors">
      &larr; Riwayat
    </a>
    <button type="button" onclick="window.print()" class="h-11 inline-flex items-center gap-2 rounded bg-brand-600 px-5 text-body font-semibold text-white hover:bg-brand-700 transition-colors">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      <span>Cetak struk</span>
    </button>
  </div>
</div>

<!-- Two-Column Layout (Desktop: Items left, Summary right) (DESIGN.md Section 8.2) -->
<div class="grid grid-cols-1 lg:grid-cols-[1fr_22rem] gap-6 items-start">
  
  <!-- Left: Items Card -->
  <div class="card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-line bg-surface flex items-center justify-between">
      <h2 class="text-heading text-ink font-semibold">Rincian item belanja</h2>
      <span class="text-small text-ink-muted"><?= count($items) ?> item</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface/50 text-micro uppercase text-ink-muted h-10 border-b border-line">
            <th scope="col" class="px-4 font-semibold">Nama Produk</th>
            <th scope="col" class="px-4 font-semibold text-center">Qty</th>
            <th scope="col" class="px-4 font-semibold text-right">Harga Satuan</th>
            <th scope="col" class="px-4 font-semibold text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line">
          <?php foreach ($items as $item): ?>
            <tr class="h-14 hover:bg-surface/50 transition-colors">
              <td class="px-4 font-medium text-ink"><?= e($item['product_name']) ?></td>
              <td class="px-4 text-center tabular-nums text-ink-soft"><?= $item['quantity'] ?></td>
              <td class="px-4 num tabular-nums text-ink-soft"><?= formatRupiah($item['unit_price']) ?></td>
              <td class="px-4 num tabular-nums font-semibold text-ink"><?= formatRupiah($item['subtotal']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="bg-surface h-12 border-t-2 border-line">
            <th colspan="3" class="px-4 text-small uppercase text-ink-muted text-right font-semibold">Total Tagihan</th>
            <th class="px-4 num tabular-nums text-heading font-bold text-ink"><?= formatRupiah($sale['total_amount']) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <?php if (!empty($sale['note'])): ?>
      <div class="p-4 border-t border-line bg-surface/30">
        <p class="text-micro uppercase text-ink-muted mb-1">Catatan</p>
        <p class="text-small text-ink"><?= e($sale['note']) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right: Status, Payment, & Void Summary -->
  <div class="space-y-4">
    <div class="card p-5 space-y-4">
      <div class="flex items-center justify-between border-b border-line pb-3">
        <span class="text-micro uppercase text-ink-muted">Status Transaksi</span>
        <?php if ($sale['status'] === 'completed'): ?>
          <span class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-small font-medium text-success-700">Selesai</span>
        <?php else: ?>
          <span class="inline-flex items-center rounded-full bg-danger-50 px-2.5 py-0.5 text-small font-medium text-danger-700">Dibatalkan</span>
        <?php endif; ?>
      </div>

      <?php if ($sale['status'] === 'void'): ?>
        <div class="p-3 rounded-lg border border-danger-600/30 bg-danger-50 text-small text-danger-700 space-y-1">
          <p class="font-semibold">Informasi Pembatalan</p>
          <p>Waktu: <?= formatDate($sale['voided_at'], true) ?> WIB</p>
          <p>Alasan: <?= e($sale['void_reason'] ?: 'Tidak ada keterangan') ?></p>
        </div>
      <?php endif; ?>

      <!-- Payment Breakdown -->
      <div class="space-y-2 text-small">
        <div class="flex justify-between">
          <span class="text-ink-muted">Total Tagihan:</span>
          <span class="font-semibold tabular-nums text-ink"><?= formatRupiah($sale['total_amount']) ?></span>
        </div>
        <?php if ($sale['cash_received'] !== null): ?>
          <div class="flex justify-between">
            <span class="text-ink-muted">Uang Diterima:</span>
            <span class="tabular-nums text-ink"><?= formatRupiah($sale['cash_received']) ?></span>
          </div>
          <div class="flex justify-between border-t border-line pt-2 font-semibold">
            <span class="text-ink-soft">Kembalian:</span>
            <span class="tabular-nums text-success-700"><?= formatRupiah($sale['change_amount'] ?? 0) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Cost & Profit Snapshot (Internal) -->
      <div class="border-t border-line pt-3 space-y-1.5 text-small">
        <p class="text-micro uppercase text-ink-muted">Kalkulasi Margin</p>
        <div class="flex justify-between text-ink-soft">
          <span>Modal Barang (COGS):</span>
          <span class="tabular-nums"><?= formatRupiah($sale['total_cost']) ?></span>
        </div>
        <div class="flex justify-between font-semibold text-brand-700">
          <span>Laba Kotor:</span>
          <span class="tabular-nums"><?= formatRupiah($sale['total_amount'] - $sale['total_cost']) ?></span>
        </div>
      </div>

      <!-- Void Action (DESIGN.md Section 8.2 & 7.1) -->
      <?php if ($sale['status'] === 'completed'): ?>
        <div class="border-t border-line pt-4 print:hidden">
          <button type="button" onclick="document.getElementById('voidModal').classList.remove('hidden'); document.getElementById('voidModal').classList.add('flex');" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded border border-danger-600/30 bg-white px-5 text-body font-medium text-danger-600 hover:bg-danger-50 transition-colors">
            Batalkan transaksi
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Confirm Modal: Void Sale (DESIGN.md Section 7.9) -->
<div id="voidModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-ink/40">
  <div class="bg-white border border-line rounded-xl shadow-pop max-w-sm w-full p-6 space-y-4">
    <h3 class="text-heading font-bold text-ink">Batalkan transaksi <?= e($sale['invoice_no']) ?>?</h3>
    <p class="text-small text-ink-muted">
      Transaksi yang dibatalkan akan otomatis dikeluarkan dari semua laporan laba dan tidak dapat dipulihkan.
    </p>

    <form method="POST" action="actions/void_sale.php" class="space-y-3">
      <?= csrfField() ?>
      <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">

      <div>
        <label for="voidReason" class="label">Alasan pembatalan (opsional)</label>
        <input type="text" id="voidReason" name="void_reason" placeholder="Contoh: Salah pilih galon" maxlength="255" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink placeholder:text-ink-faint focus:border-brand-600 focus:outline-none" />
      </div>

      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden'); document.getElementById('voidModal').classList.remove('flex');" class="flex-1 h-11 inline-flex items-center justify-center rounded border border-line-strong bg-white text-body font-medium text-ink hover:bg-surface transition-colors">
          Batal
        </button>
        <button type="submit" class="flex-1 h-11 inline-flex items-center justify-center rounded bg-danger-600 text-body font-semibold text-white hover:bg-danger-700 transition-colors">
          Ya, batalkan
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
