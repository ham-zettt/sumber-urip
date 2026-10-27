<?php
/**
 * Sumber Urip - Navigation Partial (Desktop Sidebar & Mobile Bottom Nav)
 * Following DESIGN.md Section 5.2 & 6
 */

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    [
        'id' => 'index',
        'label' => 'Kasir',
        'href' => 'index.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>'
    ],
    [
        'id' => 'history',
        'label' => 'Riwayat',
        'href' => 'history.php',
        'active_match' => ['history', 'history_detail'],
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path><path d="M12 7v5l4 2"></path></svg>'
    ],
    [
        'id' => 'reports',
        'label' => 'Laporan',
        'href' => 'reports.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="M18 17V9"></path><path d="M13 17V5"></path><path d="M8 17v-3"></path></svg>'
    ],
    [
        'id' => 'expenses',
        'label' => 'Pengeluaran',
        'href' => 'expenses.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path></svg>'
    ],
    [
        'id' => 'products',
        'label' => 'Produk',
        'href' => 'products.php',
        'icon' => '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>'
    ]
];
?>

<!-- 1. Desktop Left Sidebar (>= lg) -->
<aside class="hidden lg:flex lg:flex-col lg:w-60 lg:fixed lg:inset-y-0 lg:left-0 bg-white border-r border-line z-30">
  <!-- Sidebar Header / Logo -->
  <div class="h-16 flex items-center px-5 border-b border-line gap-2.5">
    <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600 flex-shrink-0">
      <!-- Water drop SVG (flat single hue, no gradient) -->
      <svg class="w-5 h-5 fill-brand-600" viewBox="0 0 24 24">
        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
      </svg>
    </div>
    <div class="flex flex-col">
      <span class="font-bold text-base text-brand-700 leading-tight">Sumber Urip</span>
      <span class="text-micro uppercase text-ink-muted">Isi ulang air</span>
    </div>
  </div>

  <!-- Sidebar Nav Links -->
  <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
    <?php foreach ($navItems as $item): ?>
      <?php
      $isActive = isset($item['active_match']) 
          ? in_array($currentPage, $item['active_match'], true)
          : ($currentPage === $item['id']);
      ?>
      <a href="<?= e($item['href']) ?>" 
         class="h-11 px-3 rounded-lg flex items-center gap-3 text-body transition-colors relative <?= $isActive ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-ink-soft hover:bg-surface' ?>">
        <?php if ($isActive): ?>
          <span class="absolute left-0 top-2 bottom-2 w-1 bg-brand-600 rounded-r"></span>
        <?php endif; ?>
        <span class="<?= $isActive ? 'text-brand-600' : 'text-ink-muted' ?>">
          <?= $item['icon'] ?>
        </span>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Sidebar Footer / Info -->
  <div class="p-4 border-t border-line text-small text-ink-muted flex items-center justify-between">
    <span class="text-micro uppercase">WIB (UTC+7)</span>
    <span class="tabular-nums"><?= date('H:i') ?></span>
  </div>
</aside>

<!-- 2. Mobile Top Bar (< lg) -->
<header class="lg:hidden sticky top-0 bg-white border-b border-line z-20 h-14 px-4 flex items-center justify-between">
  <div class="flex items-center gap-2">
    <div class="w-7 h-7 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600">
      <svg class="w-4 h-4 fill-brand-600" viewBox="0 0 24 24">
        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
      </svg>
    </div>
    <span class="font-bold text-brand-700 text-heading">Sumber Urip</span>
  </div>
  <div class="text-small font-medium text-ink-muted">
    <?= isset($pageTitle) ? e($pageTitle) : '' ?>
  </div>
</header>

<!-- 3. Mobile Fixed Bottom Navigation Bar (< lg) -->
<nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-line z-40 px-2 py-1 flex items-center justify-around pb-[max(env(safe-area-inset-bottom),0.5rem)]">
  <?php foreach ($navItems as $item): ?>
    <?php
    $isActive = isset($item['active_match']) 
        ? in_array($currentPage, $item['active_match'], true)
        : ($currentPage === $item['id']);
    ?>
    <a href="<?= e($item['href']) ?>" 
       class="flex flex-col items-center justify-center min-w-[60px] py-1 px-2 rounded-lg text-micro transition-colors <?= $isActive ? 'text-brand-600 font-semibold' : 'text-ink-muted hover:text-ink' ?>">
      <div class="mb-0.5 <?= $isActive ? 'text-brand-600' : 'text-ink-muted' ?>">
        <?= $item['icon'] ?>
      </div>
      <span><?= e($item['label']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>
