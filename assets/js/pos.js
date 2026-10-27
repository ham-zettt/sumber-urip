/**
 * Sumber Urip - POS (Kasir) Interactive Controller
 * Strict compliance with DESIGN.md
 */

(function () {
  'use strict';

  // Cart state: array of { id, code, name, price, qty }
  let cart = [];

  // DOM Elements
  const cartItemsList = document.getElementById('cartItemsList');
  const cartEmptyMessage = document.getElementById('cartEmptyMessage');
  const cartTotalDisplay = document.getElementById('cartTotalDisplay');
  const cashReceivedInput = document.getElementById('cashReceivedInput');
  const changeDisplayWrap = document.getElementById('changeDisplayWrap');
  const changeValueDisplay = document.getElementById('changeValueDisplay');
  const changeWarningNotice = document.getElementById('changeWarningNotice');
  const noteInput = document.getElementById('noteInput');
  const soldAtInput = document.getElementById('soldAtInput');
  const btnSaveSale = document.getElementById('btnSaveSale');
  const btnClearCart = document.getElementById('btnClearCart');
  const mobileStickyBar = document.getElementById('mobileStickyBar');
  const mobileStickyCount = document.getElementById('mobileStickyCount');
  const mobileStickyTotal = document.getElementById('mobileStickyTotal');
  const btnMobileSave = document.getElementById('btnMobileSave');

  // Add product to cart
  function addProductToCart(productId, productCode, productName, productPrice) {
    const existing = cart.find(item => item.id === productId);
    if (existing) {
      if (existing.qty < 999) {
        existing.qty += 1;
      }
    } else {
      cart.push({
        id: productId,
        code: productCode,
        name: productName,
        price: parseInt(productPrice, 10),
        qty: 1
      });
    }
    renderCart();
  }

  // Update quantity
  function updateQuantity(productId, newQty) {
    const item = cart.find(i => i.id === productId);
    if (!item) return;

    if (newQty <= 0) {
      removeFromCart(productId);
      return;
    }

    item.qty = Math.min(Math.max(1, newQty), 999);
    renderCart();
  }

  // Remove product from cart
  function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    renderCart();
  }

  // Clear entire cart
  function clearCart() {
    if (cart.length === 0) return;
    cart = [];
    if (cashReceivedInput) cashReceivedInput.value = '';
    if (noteInput) noteInput.value = '';
    renderCart();
  }

  // Calculate cart total
  function getCartTotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
  }

  function getCartItemCount() {
    return cart.reduce((sum, item) => sum + item.qty, 0);
  }

  // Format currency helper
  function formatRupiahJs(number, withPrefix = true) {
    const num = parseInt(number, 10) || 0;
    const isNegative = num < 0;
    const absFormatted = Math.abs(num).toLocaleString('id-ID');
    if (withPrefix) {
      return (isNegative ? '-Rp ' : 'Rp ') + absFormatted;
    }
    return (isNegative ? '-' : '') + absFormatted;
  }

  // Calculate change amount
  function updateChangeCalculation() {
    const total = getCartTotal();
    const rawVal = cashReceivedInput ? cashReceivedInput.value.replace(/[^0-9]/g, '') : '';
    
    if (rawVal === '') {
      if (changeDisplayWrap) changeDisplayWrap.classList.add('hidden');
      if (changeWarningNotice) changeWarningNotice.classList.add('hidden');
      return;
    }

    const cash = parseInt(rawVal, 10) || 0;
    const change = cash - total;

    if (changeDisplayWrap) {
      changeDisplayWrap.classList.remove('hidden');
      changeValueDisplay.textContent = formatRupiahJs(change);
      
      if (change < 0) {
        changeValueDisplay.className = 'font-semibold text-danger-600 tabular-nums';
        if (changeWarningNotice) {
          changeWarningNotice.classList.remove('hidden');
          changeWarningNotice.textContent = 'Uang diterima kurang dari total tagihan.';
        }
      } else {
        changeValueDisplay.className = 'font-semibold text-success-700 tabular-nums';
        if (changeWarningNotice) {
          changeWarningNotice.classList.add('hidden');
        }
      }
    }
  }

  // Sync Product Buttons UI (DESIGN.md Section 7.3: In-cart state)
  function syncProductButtons() {
    document.querySelectorAll('.product-btn').forEach(btn => {
      const pid = parseInt(btn.dataset.id, 10);
      const inCartItem = cart.find(i => i.id === pid);
      const badge = btn.querySelector('.product-qty-badge');

      if (inCartItem && inCartItem.qty > 0) {
        btn.classList.add('border-brand-600', 'bg-brand-50/30');
        btn.classList.remove('border-line');
        if (badge) {
          badge.textContent = inCartItem.qty;
          badge.classList.remove('hidden');
          badge.classList.add('flex');
        }
      } else {
        btn.classList.remove('border-brand-600', 'bg-brand-50/30');
        btn.classList.add('border-line');
        if (badge) {
          badge.classList.add('hidden');
          badge.classList.remove('flex');
        }
      }
    });
  }

  // Render cart UI
  function renderCart() {
    syncProductButtons();

    if (!cartItemsList) return;

    const total = getCartTotal();
    const totalCount = getCartItemCount();

    if (cart.length === 0) {
      cartItemsList.innerHTML = '';
      if (cartEmptyMessage) cartEmptyMessage.classList.remove('hidden');
      if (cartTotalDisplay) cartTotalDisplay.textContent = 'Rp 0';
      if (btnSaveSale) btnSaveSale.disabled = true;
      if (btnClearCart) btnClearCart.disabled = true;
      if (changeDisplayWrap) changeDisplayWrap.classList.add('hidden');
      if (changeWarningNotice) changeWarningNotice.classList.add('hidden');
      if (mobileStickyBar) mobileStickyBar.classList.add('hidden');
      return;
    }

    if (cartEmptyMessage) cartEmptyMessage.classList.add('hidden');
    if (btnSaveSale) btnSaveSale.disabled = false;
    if (btnClearCart) btnClearCart.disabled = false;

    let html = '';
    cart.forEach(item => {
      const lineSubtotal = item.price * item.qty;
      html += `
        <div class="flex items-center justify-between py-3 border-b border-line last:border-b-0 gap-2" data-id="${item.id}">
          <div class="flex-1 min-w-0 pr-2">
            <div class="text-body font-medium text-ink truncate">${escapeHtml(item.name)}</div>
            <div class="text-small text-ink-muted tabular-nums">${formatRupiahJs(item.price)}</div>
          </div>
          <div class="flex items-center gap-1">
            <button type="button" class="btn-minus w-9 h-9 rounded border border-line-strong bg-white hover:bg-surface text-ink flex items-center justify-center font-bold" data-id="${item.id}" aria-label="Kurangi">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
            <input type="number" class="qty-input w-11 h-9 rounded border border-line-strong text-center text-body font-semibold tabular-nums text-ink focus:border-brand-600 focus:outline-none" data-id="${item.id}" value="${item.qty}" min="1" max="999" inputmode="numeric">
            <button type="button" class="btn-plus w-9 h-9 rounded border border-line-strong bg-white hover:bg-surface text-ink flex items-center justify-center font-bold" data-id="${item.id}" aria-label="Tambah">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
          </div>
          <div class="num tabular-nums font-semibold text-ink min-w-[70px] text-right pl-2">
            ${formatRupiahJs(lineSubtotal, false)}
          </div>
          <button type="button" class="cart-remove-btn text-ink-muted hover:text-danger-600 p-1.5 transition-colors" data-id="${item.id}" title="Hapus item" aria-label="Hapus">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </button>
        </div>
      `;
    });

    cartItemsList.innerHTML = html;

    if (cartTotalDisplay) {
      cartTotalDisplay.textContent = formatRupiahJs(total);
    }

    // Update Mobile Sticky Summary Bar
    if (mobileStickyBar) {
      mobileStickyBar.classList.remove('hidden');
      if (mobileStickyCount) mobileStickyCount.textContent = totalCount + ' item';
      if (mobileStickyTotal) mobileStickyTotal.textContent = formatRupiahJs(total);
    }

    updateChangeCalculation();
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Handle saving sale
  async function submitSale() {
    if (cart.length === 0) {
      alert('Keranjang belanja masih kosong.');
      return;
    }

    const csrfTokenEl = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfTokenEl ? csrfTokenEl.value : '';

    const payload = {
      csrf_token: csrfToken,
      items: cart.map(i => ({ product_id: i.id, quantity: i.qty })),
      cash_received: cashReceivedInput ? cashReceivedInput.value.replace(/[^0-9]/g, '') : null,
      note: noteInput ? noteInput.value.trim() : '',
      sold_at: soldAtInput ? soldAtInput.value.trim() : ''
    };

    // Button loading state (DESIGN.md Section 7.1)
    if (btnSaveSale) {
      btnSaveSale.disabled = true;
      btnSaveSale.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span><span>Menyimpan...</span>';
    }
    if (btnMobileSave) {
      btnMobileSave.disabled = true;
      btnMobileSave.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>';
    }

    try {
      const response = await fetch('actions/save_sale.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (data.success) {
        showSuccessModal(data);
        clearCart();
        refreshRecentAndSummary();
      } else {
        alert(data.message || 'Gagal menyimpan transaksi.');
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan jaringan saat menyimpan transaksi.');
    } finally {
      if (btnSaveSale) {
        btnSaveSale.disabled = cart.length === 0;
        btnSaveSale.innerHTML = 'Simpan transaksi';
      }
      if (btnMobileSave) {
        btnMobileSave.disabled = cart.length === 0;
        btnMobileSave.innerHTML = 'Simpan';
      }
    }
  }

  // Show Success Modal
  function showSuccessModal(data) {
    const modal = document.getElementById('saleSuccessModal');
    if (!modal) {
      alert(data.message);
      return;
    }

    const invEl = document.getElementById('modalInvoiceNo');
    const totalEl = document.getElementById('modalTotalAmount');
    const cashEl = document.getElementById('modalCashReceived');
    const changeEl = document.getElementById('modalChangeAmount');
    const detailLink = document.getElementById('modalDetailLink');

    if (invEl) invEl.textContent = data.invoice_no;
    if (totalEl) totalEl.textContent = data.formatted_total;
    if (cashEl) {
      cashEl.textContent = data.cash_received ? formatRupiahJs(data.cash_received) : '-';
    }
    if (changeEl) {
      changeEl.textContent = data.formatted_change ? data.formatted_change : '-';
    }
    if (detailLink) {
      detailLink.href = 'history_detail.php?id=' + data.sale_id;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }

  // Refresh recent sales and summary strip dynamically
  async function refreshRecentAndSummary() {
    try {
      const res = await fetch('index.php?ajax_refresh=1');
      if (res.ok) {
        const text = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');

        const newStrip = doc.getElementById('summaryStripContainer');
        const oldStrip = document.getElementById('summaryStripContainer');
        if (newStrip && oldStrip) {
          oldStrip.innerHTML = newStrip.innerHTML;
        }

        const newRecent = doc.getElementById('recentTransactionsContainer');
        const oldRecent = document.getElementById('recentTransactionsContainer');
        if (newRecent && oldRecent) {
          oldRecent.innerHTML = newRecent.innerHTML;
        }
      }
    } catch (e) {
      console.warn('Could not refresh summary strip dynamically:', e);
    }
  }

  // Event Listeners
  document.addEventListener('DOMContentLoaded', function () {
    // 1. Product button tap handler
    document.querySelectorAll('.product-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = parseInt(this.dataset.id, 10);
        const code = this.dataset.code;
        const name = this.dataset.name;
        const price = parseInt(this.dataset.price, 10);
        addProductToCart(id, code, name, price);
      });
    });

    // 2. Cart item quantity controls and removal
    if (cartItemsList) {
      cartItemsList.addEventListener('click', function (e) {
        const btnMinus = e.target.closest('.btn-minus');
        const btnPlus = e.target.closest('.btn-plus');
        const btnRemove = e.target.closest('.cart-remove-btn');

        if (btnMinus) {
          const id = parseInt(btnMinus.dataset.id, 10);
          const item = cart.find(i => i.id === id);
          if (item) updateQuantity(id, item.qty - 1);
        } else if (btnPlus) {
          const id = parseInt(btnPlus.dataset.id, 10);
          const item = cart.find(i => i.id === id);
          if (item) updateQuantity(id, item.qty + 1);
        } else if (btnRemove) {
          const id = parseInt(btnRemove.dataset.id, 10);
          removeFromCart(id);
        }
      });

      cartItemsList.addEventListener('change', function (e) {
        if (e.target.classList.contains('qty-input')) {
          const id = parseInt(e.target.dataset.id, 10);
          const val = parseInt(e.target.value, 10) || 1;
          updateQuantity(id, val);
        }
      });
    }

    // 3. Clear cart button
    if (btnClearCart) {
      btnClearCart.addEventListener('click', function () {
        if (confirm('Kosongkan semua pesanan di keranjang?')) {
          clearCart();
        }
      });
    }

    // 4. Cash received input change calculation & formatting
    if (cashReceivedInput) {
      cashReceivedInput.addEventListener('input', function () {
        const raw = this.value.replace(/[^0-9]/g, '');
        if (raw !== '') {
          this.value = parseInt(raw, 10).toLocaleString('id-ID');
        } else {
          this.value = '';
        }
        updateChangeCalculation();
      });
    }

    // 5. Submit Sale via AJAX
    if (btnSaveSale) {
      btnSaveSale.addEventListener('click', submitSale);
    }
    if (btnMobileSave) {
      btnMobileSave.addEventListener('click', submitSale);
    }

    // Initialize empty cart
    renderCart();
  });

  // Modal closer
  window.closeSaleSuccessModal = function () {
    const modal = document.getElementById('saleSuccessModal');
    if (modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      document.body.style.overflow = '';
    }
  };

})();
