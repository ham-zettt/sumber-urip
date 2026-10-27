/**
 * Sumber Urip - Core Application Javascript
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Menu Toggle
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const navMenu = document.getElementById('navMenu');

  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener('click', function () {
      navMenu.classList.toggle('active');
      const isExpanded = navMenu.classList.contains('active');
      mobileToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    });
  }

  // 2. Flash Message Dismiss
  document.querySelectorAll('.flash-alert .alert-close').forEach(function (button) {
    button.addEventListener('click', function () {
      const alert = button.closest('.flash-alert');
      if (alert) {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 250);
      }
    });
  });

  // 3. Auto-hide flash alerts after 5 seconds
  const autoAlerts = document.querySelectorAll('.flash-alert.auto-dismiss');
  autoAlerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 250);
    }, 5000);
  });
});

/**
 * Global helper: Format integer number to Indonesian Rupiah string
 */
function formatRupiahJs(number, withPrefix = true) {
  const num = parseInt(number, 10) || 0;
  const isNegative = num < 0;
  const absFormatted = Math.abs(num).toLocaleString('id-ID');
  if (withPrefix) {
    return (isNegative ? '-Rp ' : 'Rp ') + absFormatted;
  }
  return (isNegative ? '-' : '') + absFormatted;
}

/**
 * Modal Management Helpers
 */
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}
