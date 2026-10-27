<?php
/**
 * Sumber Urip - Pengeluaran Operasional (Expenses)
 * Conforms strictly to DESIGN.md Section 8.4
 */

$pageTitle = 'Pengeluaran';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/queries.php';

$pdo = Database::getConnection();

// Check if editing an existing expense
$editId = (int)($_GET['edit'] ?? 0);
$editExpense = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $editId]);
    $editExpense = $stmt->fetch();
}

// Filter parameters
$today = date('Y-m-d');
$filterStart = trim($_GET['start_date'] ?? date('Y-m-01'));
$filterEnd = trim($_GET['end_date'] ?? $today);
$filterCategory = trim($_GET['category'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Validate filter dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterStart)) $filterStart = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterEnd)) $filterEnd = $today;

$where = ["expense_date >= :start_date AND expense_date <= :end_date"];
$params = [
    ':start_date' => $filterStart,
    ':end_date' => $filterEnd
];

if ($filterCategory !== 'all' && in_array($filterCategory, EXPENSE_CATEGORIES, true)) {
    $where[] = "category = :category";
    $params[':category'] = $filterCategory;
}

$whereSql = implode(' AND ', $where);

// Total summary for filtered expenses
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total_rows, COALESCE(SUM(amount), 0) as total_filtered_amount 
    FROM expenses 
    WHERE $whereSql
");
$countStmt->execute($params);
$summaryRow = $countStmt->fetch();

$totalRows = (int)($summaryRow['total_rows'] ?? 0);
$totalFilteredAmount = (int)($summaryRow['total_filtered_amount'] ?? 0);
$totalPages = max(1, ceil($totalRows / $perPage));

// Paginated items
$listStmt = $pdo->prepare("
    SELECT id, expense_date, category, description, amount, created_at 
    FROM expenses 
    WHERE $whereSql 
    ORDER BY expense_date DESC, id DESC 
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) {
    $listStmt->bindValue($k, $v);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$expenses = $listStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header (DESIGN.md Section 8.4) -->
<div class="flex items-baseline justify-between">
  <div>
    <h1 class="text-title text-ink font-bold">Pengeluaran</h1>
    <p class="text-small text-ink-muted">Pencatatan beban operasional depot air minum</p>
  </div>
</div>

<!-- Two-Column Layout on Desktop: Form on the left (lg:w-96), list on the right -->
<div class="grid grid-cols-1 lg:grid-cols-[24rem_1fr] gap-6 items-start">
  
  <!-- Left Column: Form Card -->
  <div class="space-y-4">
    <div class="card p-5 space-y-4 lg:sticky lg:top-6">
      <div class="flex items-center justify-between border-b border-line pb-3">
        <h2 class="text-heading text-ink font-semibold"><?= $editExpense ? 'Edit pengeluaran' : 'Catat pengeluaran' ?></h2>
        <?php if ($editExpense): ?>
          <a href="expenses.php" class="text-small font-medium text-brand-700 hover:underline">Batal edit</a>
        <?php endif; ?>
      </div>

      <form method="POST" action="actions/save_expense.php" class="space-y-3.5">
        <?= csrfField() ?>
        <?php if ($editExpense): ?>
          <input type="hidden" name="expense_id" value="<?= $editExpense['id'] ?>">
        <?php endif; ?>

        <div>
          <label for="expense_date" class="label">Tanggal pengeluaran</label>
          <input type="date" id="expense_date" name="expense_date" value="<?= e($editExpense['expense_date'] ?? $today) ?>" required class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
        </div>

        <div>
          <label for="category" class="label">Kategori beban</label>
          <select id="category" name="category" required class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none">
            <option value="" disabled <?= !$editExpense ? 'selected' : '' ?>>Pilih kategori...</option>
            <?php foreach (EXPENSE_CATEGORIES as $cat): ?>
              <option value="<?= e($cat) ?>" <?= ($editExpense['category'] ?? '') === $cat ? 'selected' : '' ?>>
                <?= e($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="amount" class="label">Jumlah (Rp)</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-small text-ink-muted pointer-events-none">Rp</span>
            <input type="text" id="amount" name="amount" inputmode="numeric" placeholder="Contoh: 150.000" value="<?= $editExpense ? number_format($editExpense['amount'], 0, ',', '.') : '' ?>" required class="h-11 w-full rounded border border-line-strong bg-white pl-10 pr-3 text-body tabular-nums text-ink placeholder:text-ink-faint focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
          </div>
          <p class="mt-1 text-micro text-ink-muted">Hanya angka nominal pengeluaran.</p>
        </div>

        <div>
          <label for="description" class="label">Keterangan (opsional)</label>
          <input type="text" id="description" name="description" maxlength="255" placeholder="Contoh: Pembelian cartridge filter sedimen" value="<?= e($editExpense['description'] ?? '') ?>" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink placeholder:text-ink-faint focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
        </div>

        <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded bg-brand-600 px-5 text-body font-semibold text-white transition-colors hover:bg-brand-700 active:bg-brand-800">
          <?= $editExpense ? 'Simpan perubahan' : 'Tambah pengeluaran' ?>
        </button>
      </form>

      <!-- Info Alert: New Galon Hint (DESIGN.md Section 8.4) -->
      <div class="rounded-lg border border-line bg-brand-50/60 p-3 text-small text-ink-soft space-y-1">
        <p class="font-semibold text-brand-700">Petunjuk Pembelian Galon Baru</p>
        <p class="text-ink-muted leading-relaxed">
          Pembelian galon baru dari pabrik tidak perlu dicatat di sini karena sudah otomatis dihitung sebagai modal barang (COGS) saat transaksi penjualan.
        </p>
      </div>
    </div>
  </div>

  <!-- Right Column: Filter & List -->
  <div class="space-y-4">
    <!-- Filters Bar (DESIGN.md Section 8.2 & 8.4) -->
    <div class="card p-4">
      <form method="GET" action="expenses.php" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
        <div>
          <label for="filterStart" class="label">Dari tanggal</label>
          <input type="date" id="filterStart" name="start_date" value="<?= e($filterStart) ?>" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:outline-none" />
        </div>

        <div>
          <label for="filterEnd" class="label">Sampai tanggal</label>
          <input type="date" id="filterEnd" name="end_date" value="<?= e($filterEnd) ?>" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:outline-none" />
        </div>

        <div class="flex gap-2">
          <div class="flex-1">
            <label for="filterCat" class="label">Kategori</label>
            <select id="filterCat" name="category" class="h-11 w-full rounded border border-line-strong bg-white px-3 text-body text-ink focus:border-brand-600 focus:outline-none">
              <option value="all" <?= $filterCategory === 'all' ? 'selected' : '' ?>>Semua</option>
              <?php foreach (EXPENSE_CATEGORIES as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="h-11 inline-flex items-center justify-center rounded bg-brand-600 px-4 text-body font-semibold text-white hover:bg-brand-700 transition-colors self-end">
            Terapkan
          </button>
        </div>
      </form>
    </div>

    <!-- Expenses Table Card -->
    <div class="card overflow-hidden">
      <?php if (empty($expenses)): ?>
        <p class="p-6 text-center text-small text-ink-muted">Belum ada catatan pengeluaran pada rentang tanggal ini.</p>
      <?php else: ?>
        <!-- Desktop Table View (>= md) -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-surface text-micro uppercase text-ink-muted h-10 border-b border-line">
                <th scope="col" class="px-4 font-semibold">Tanggal</th>
                <th scope="col" class="px-4 font-semibold">Kategori</th>
                <th scope="col" class="px-4 font-semibold">Keterangan</th>
                <th scope="col" class="px-4 font-semibold text-right">Biaya</th>
                <th scope="col" class="px-4 font-semibold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-line">
              <?php foreach ($expenses as $exp): ?>
                <tr class="h-14 hover:bg-surface/50 transition-colors">
                  <td class="px-4 tabular-nums text-small text-ink font-medium whitespace-nowrap"><?= formatDate($exp['expense_date']) ?></td>
                  <td class="px-4 text-small">
                    <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-micro font-medium text-brand-700">
                      <?= e($exp['category']) ?>
                    </span>
                  </td>
                  <td class="px-4 text-small text-ink-soft max-w-xs truncate"><?= e($exp['description'] ?: '-') ?></td>
                  <td class="px-4 num tabular-nums font-semibold text-danger-700"><?= formatRupiah($exp['amount']) ?></td>
                  <td class="px-4 text-right space-x-1 whitespace-nowrap">
                    <a href="expenses.php?edit=<?= $exp['id'] ?>&start_date=<?= urlencode($filterStart) ?>&end_date=<?= urlencode($filterEnd) ?>&category=<?= urlencode($filterCategory) ?>" class="inline-flex h-8 items-center rounded px-2.5 text-small font-medium text-brand-700 hover:bg-brand-50 transition-colors">
                      Edit
                    </a>
                    <button type="button" onclick="confirmDelete(<?= $exp['id'] ?>, '<?= e(addslashes($exp['category'])) ?> - <?= formatRupiah($exp['amount']) ?>')" class="inline-flex h-8 items-center rounded px-2 text-small font-medium text-danger-600 hover:bg-danger-50 transition-colors">
                      Hapus
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="bg-surface h-12 border-t-2 border-line">
                <th colspan="3" class="px-4 text-small uppercase text-ink-muted font-semibold">Total Pengeluaran Terfilter</th>
                <th class="px-4 num tabular-nums text-heading font-bold text-danger-700"><?= formatRupiah($totalFilteredAmount) ?></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Mobile Stacked List View (< md) (DESIGN.md Section 7.6) -->
        <div class="md:hidden divide-y divide-line">
          <?php foreach ($expenses as $exp): ?>
            <div class="p-4 space-y-2">
              <div class="flex items-baseline justify-between gap-2">
                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-micro font-medium text-brand-700"><?= e($exp['category']) ?></span>
                <span class="font-semibold text-body tabular-nums text-danger-700"><?= formatRupiah($exp['amount']) ?></span>
              </div>
              <div class="text-small text-ink-soft"><?= e($exp['description'] ?: 'Tidak ada keterangan') ?></div>
              <div class="flex items-center justify-between text-micro text-ink-muted pt-1">
                <span><?= formatDate($exp['expense_date']) ?></span>
                <div class="space-x-3">
                  <a href="expenses.php?edit=<?= $exp['id'] ?>&start_date=<?= urlencode($filterStart) ?>&end_date=<?= urlencode($filterEnd) ?>&category=<?= urlencode($filterCategory) ?>" class="font-medium text-brand-700">Edit</a>
                  <button type="button" onclick="confirmDelete(<?= $exp['id'] ?>, '<?= e(addslashes($exp['category'])) ?> - <?= formatRupiah($exp['amount']) ?>')" class="font-medium text-danger-600">Hapus</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="p-4 bg-surface flex justify-between items-center text-small font-semibold">
            <span>Total Terfilter:</span>
            <span class="tabular-nums text-danger-700 text-body"><?= formatRupiah($totalFilteredAmount) ?></span>
          </div>
        </div>

        <!-- Pagination (DESIGN.md Section 7.6) -->
        <?php if ($totalPages > 1): ?>
          <?php $queryParams = $_GET; ?>
          <div class="p-4 bg-surface border-t border-line flex items-center justify-between text-small">
            <?php if ($page > 1): ?>
              <?php $queryParams['page'] = $page - 1; ?>
              <a href="?<?= http_build_query($queryParams) ?>" class="inline-flex h-9 items-center rounded px-3 font-medium text-brand-700 border border-line bg-white hover:bg-surface">Sebelumnya</a>
            <?php else: ?>
              <span class="inline-flex h-9 items-center rounded px-3 text-ink-faint border border-line bg-white cursor-not-allowed">Sebelumnya</span>
            <?php endif; ?>

            <span class="text-ink-muted tabular-nums">Halaman <?= $page ?> dari <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
              <?php $queryParams['page'] = $page + 1; ?>
              <a href="?<?= http_build_query($queryParams) ?>" class="inline-flex h-9 items-center rounded px-3 font-medium text-brand-700 border border-line bg-white hover:bg-surface">Berikutnya</a>
            <?php else: ?>
              <span class="inline-flex h-9 items-center rounded px-3 text-ink-faint border border-line bg-white cursor-not-allowed">Berikutnya</span>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Dialog Delete Confirm (DESIGN.md Section 7.9) -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-ink/40">
  <div class="bg-white border border-line rounded-xl shadow-pop max-w-sm w-full p-6 space-y-4">
    <h3 class="text-heading font-bold text-ink">Hapus catatan pengeluaran?</h3>
    <p class="text-small text-ink-muted">
      Tindakan ini permanen dan akan langsung mengurangi total beban operasional pada laporan keuangan.
    </p>
    <div id="deleteModalText" class="p-3 bg-surface rounded-lg text-small text-ink font-semibold tabular-nums"></div>

    <form method="POST" action="actions/delete_expense.php" class="flex gap-2 pt-2">
      <?= csrfField() ?>
      <input type="hidden" name="expense_id" id="deleteExpenseId" value="">

      <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('flex');" class="flex-1 h-11 inline-flex items-center justify-center rounded border border-line-strong bg-white text-body font-medium text-ink hover:bg-surface transition-colors">
        Batal
      </button>
      <button type="submit" class="flex-1 h-11 inline-flex items-center justify-center rounded bg-danger-600 text-body font-semibold text-white hover:bg-danger-700 transition-colors">
        Ya, hapus
      </button>
    </form>
  </div>
</div>

<script>
function confirmDelete(id, text) {
  document.getElementById('deleteExpenseId').value = id;
  document.getElementById('deleteModalText').textContent = text;
  const m = document.getElementById('deleteModal');
  m.classList.remove('hidden');
  m.classList.add('flex');
}

// Auto format thousand separator on amount input
document.addEventListener('DOMContentLoaded', function () {
  const amountInput = document.getElementById('amount');
  if (amountInput) {
    amountInput.addEventListener('input', function () {
      const raw = this.value.replace(/[^0-9]/g, '');
      if (raw !== '') {
        this.value = parseInt(raw, 10).toLocaleString('id-ID');
      } else {
        this.value = '';
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
