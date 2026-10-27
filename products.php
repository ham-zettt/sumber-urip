<?php
/**
 * Sumber Urip - Manajemen Produk & Harga
 * Conforms strictly to DESIGN.md Section 8.5
 */

$pageTitle = 'Produk & Harga';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/queries.php';

$products = getProducts(false); // fetch all 3 products

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header (DESIGN.md Section 8.5) -->
<div class="flex items-baseline justify-between">
  <div>
    <h1 class="text-title text-ink font-bold">Produk</h1>
    <p class="text-small text-ink-muted">Pengaturan harga jual dan modal per unit</p>
  </div>
</div>

<!-- Info Alert (DESIGN.md Section 8.5 & 7.9) -->
<div class="rounded-lg border border-line bg-brand-50/60 p-4 flex items-start gap-3 text-small">
  <div class="text-brand-600 flex-shrink-0 mt-0.5">
    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
  </div>
  <div class="text-ink-soft space-y-0.5">
    <p class="font-semibold text-brand-700">Ketentuan Perubahan Harga</p>
    <p class="text-ink-muted leading-relaxed">
      Perubahan harga hanya berlaku untuk transaksi berikutnya. Data transaksi lampau tetap utuh menggunakan snapshot harga saat transaksi dicatat.
    </p>
  </div>
</div>

<form method="POST" action="actions/update_products.php" id="productsForm" class="space-y-4">
  <?= csrfField() ?>

  <!-- Three Rows (Cards) per DESIGN.md Section 8.5 -->
  <div class="space-y-3">
    <?php foreach ($products as $p): ?>
      <?php
      $margin = (int)$p['price'] - (int)$p['cost_price'];
      $isNeg = $margin < 0;
      ?>
      <div class="card p-5 product-row" data-id="<?= $p['id'] ?>">
        <div class="grid grid-cols-1 md:grid-cols-[14rem_1fr_1fr_10rem_auto] gap-4 items-center">
          <!-- Product Name & Code -->
          <div>
            <span class="text-micro uppercase text-ink-muted"><?= $p['code'] === 'GALON_BARU' ? 'Galon baru pabrik' : 'Isi ulang galon' ?></span>
            <h3 class="text-heading text-ink font-semibold mt-0.5"><?= e($p['name']) ?></h3>
            <span class="text-micro text-ink-faint font-mono"><?= e($p['code']) ?></span>
          </div>

          <!-- Price Input -->
          <div>
            <label class="label" for="price_<?= $p['id'] ?>">Harga jual (Rp)</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-small text-ink-muted pointer-events-none">Rp</span>
              <input type="number" 
                     id="price_<?= $p['id'] ?>"
                     name="products[<?= $p['id'] ?>][price]" 
                     value="<?= (int)$p['price'] ?>" 
                     min="1" 
                     required
                     data-id="<?= $p['id'] ?>"
                     class="price-input h-11 w-full rounded border border-line-strong bg-white pl-10 pr-3 text-body tabular-nums text-ink focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
          </div>

          <!-- Cost Input -->
          <div>
            <label class="label" for="cost_<?= $p['id'] ?>">Harga modal (Rp)</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-small text-ink-muted pointer-events-none">Rp</span>
              <input type="number" 
                     id="cost_<?= $p['id'] ?>"
                     name="products[<?= $p['id'] ?>][cost_price]" 
                     value="<?= (int)$p['cost_price'] ?>" 
                     min="0" 
                     required
                     data-id="<?= $p['id'] ?>"
                     class="cost-input h-11 w-full rounded border border-line-strong bg-white pl-10 pr-3 text-body tabular-nums text-ink focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
          </div>

          <!-- Live Margin Calculation -->
          <div class="bg-surface rounded-lg p-2.5 text-right">
            <span class="text-micro uppercase text-ink-muted block">Margin / Unit</span>
            <span id="margin_<?= $p['id'] ?>" class="text-body font-bold tabular-nums <?= $isNeg ? 'text-danger-700' : 'text-success-700' ?>">
              <?= formatRupiah($margin) ?>
            </span>
          </div>

          <!-- Active Toggle -->
          <div class="flex items-center gap-2 pl-2">
            <label class="inline-flex items-center gap-2 cursor-pointer text-small text-ink-soft select-none">
              <input type="checkbox" 
                     name="products[<?= $p['id'] ?>][is_active]" 
                     value="1" 
                     <?= (int)$p['is_active'] === 1 ? 'checked' : '' ?>
                     class="w-5 h-5 rounded border-line-strong text-brand-600 focus:ring-brand-600 cursor-pointer" />
              <span>Aktif di kasir</span>
            </label>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Primary Save Button (DESIGN.md Section 8.5 & 7.1) -->
  <div class="flex justify-end pt-2">
    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded bg-brand-600 px-6 text-body font-semibold text-white transition-colors hover:bg-brand-700 active:bg-brand-800">
      Simpan perubahan
    </button>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function formatRp(num) {
    const isNeg = num < 0;
    const abs = Math.abs(num).toLocaleString('id-ID');
    return (isNeg ? '-Rp ' : 'Rp ') + abs;
  }

  document.querySelectorAll('.product-row').forEach(row => {
    const id = row.dataset.id;
    const priceInput = row.querySelector('.price-input');
    const costInput = row.querySelector('.cost-input');
    const marginEl = document.getElementById('margin_' + id);

    function update() {
      const p = parseInt(priceInput.value, 10) || 0;
      const c = parseInt(costInput.value, 10) || 0;
      const m = p - c;
      marginEl.textContent = formatRp(m);
      if (m < 0) {
        marginEl.className = 'text-body font-bold tabular-nums text-danger-700';
      } else {
        marginEl.className = 'text-body font-bold tabular-nums text-success-700';
      }
    }

    priceInput.addEventListener('input', update);
    costInput.addEventListener('input', update);
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
