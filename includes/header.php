<?php
/**
 * Sumber Urip - Shared Header Partial
 * Conforms strictly to DESIGN.md
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id" class="bg-white text-ink antialiased">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="description" content="Aplikasi kasir dan manajemen depot air minum Sumber Urip. Cepat, tenang, presisi, dan elegan.">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e(APP_NAME) ?></title>

  <!-- Google Fonts: Plus Jakarta Sans (400, 500, 600, 700) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind 3.4 Play CDN with exact configuration from DESIGN.md Section 2.1 -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50:  '#F2F7FD',
              100: '#E3EEFB',
              200: '#C7DCF7',
              300: '#9CC1F0',
              400: '#669FE6',
              500: '#3F82DA',
              600: '#2766C0', // primary
              700: '#1F52A0', // primary hover
              800: '#1C4482',
              900: '#1B3A6B',
              950: '#112445',
            },
            ink: {
              DEFAULT: '#0F1B2D', // primary text
              soft:    '#33425A', // secondary text
              muted:   '#5B6B82', // tertiary text, labels
              faint:   '#8795AA', // placeholders, disabled text
            },
            line: {
              DEFAULT: '#E4EAF2', // hairline borders, dividers
              strong:  '#CBD5E3', // input borders
            },
            surface: {
              DEFAULT: '#F7F9FC', // subtle section / table header background
              sunken:  '#EEF2F8', // pressed / hover on neutral items
            },
            success: { 50: '#EAF6F0', 600: '#1F8A5B', 700: '#176B47' },
            danger:  { 50: '#FBEDEC', 600: '#C2403A', 700: '#9E322D' },
            warning: { 50: '#FBF3E3', 600: '#B7791F', 700: '#8F5E17' },
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
          },
          fontSize: {
            'display': ['2.25rem', { lineHeight: '2.5rem', letterSpacing: '-0.02em', fontWeight: '700' }],
            'title':   ['1.5rem',  { lineHeight: '2rem',   letterSpacing: '-0.015em', fontWeight: '700' }],
            'heading': ['1.125rem',{ lineHeight: '1.75rem',letterSpacing: '-0.01em', fontWeight: '600' }],
            'body':    ['0.9375rem',{ lineHeight: '1.5rem' }],
            'small':   ['0.8125rem',{ lineHeight: '1.25rem' }],
            'micro':   ['0.6875rem',{ lineHeight: '1rem', letterSpacing: '0.06em', fontWeight: '600' }],
          },
          borderRadius: {
            DEFAULT: '0.5rem',  // 8px: buttons, inputs
            lg: '0.625rem',     // 10px: cards
            xl: '0.875rem',     // 14px: large panels, modals
          },
          boxShadow: {
            card:  '0 1px 2px rgba(15, 27, 45, 0.04)',
            pop:   '0 8px 24px -8px rgba(15, 27, 45, 0.16)',
          },
          ringColor: { DEFAULT: '#2766C0' },
        },
      },
    };
  </script>

  <!-- Compiled CSS Fallback -->
  <link rel="stylesheet" href="assets/css/app.css">
  
  <style>
    /* Section 2.2 Base styles */
    html { font-feature-settings: 'ss01', 'cv11'; }
    .num { font-variant-numeric: tabular-nums; text-align: right; }
    .label { display: block; font-size: 0.8125rem; line-height: 1.25rem; font-weight: 500; color: #33425A; margin-bottom: 0.375rem; }
    .card { background-color: #FFFFFF; border: 1px solid #E4EAF2; border-radius: 0.625rem; box-shadow: 0 1px 2px rgba(15, 27, 45, 0.04); }
  </style>
</head>
<body class="font-sans text-body min-h-screen bg-white">

<?php require_once __DIR__ . '/nav.php'; ?>

<!-- App Content Wrapper (Desktop offset lg:pl-60, mobile padding-bottom for tab bar) -->
<div class="lg:pl-60 flex flex-col min-h-screen pb-24 lg:pb-12 bg-white">
  
  <!-- Flash Toast Message (DESIGN.md Section 7.9) -->
  <?php if ($flash): ?>
    <?php
    $isSuccess = $flash['type'] === 'success';
    $toastBorder = $isSuccess ? 'border-success-600' : 'border-danger-600';
    $iconColor = $isSuccess ? 'text-success-600' : 'text-danger-600';
    ?>
    <div id="flashToast" class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-6 sm:top-6 z-50 max-w-md bg-white border border-line border-l-4 <?= $toastBorder ?> rounded-lg shadow-pop px-4 py-3 flex items-center justify-between gap-3 transition-opacity duration-200">
      <div class="flex items-center gap-2.5">
        <span class="<?= $iconColor ?>">
          <?php if ($isSuccess): ?>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <?php else: ?>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <?php endif; ?>
        </span>
        <span class="text-small font-medium text-ink"><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="text-ink-muted hover:text-ink text-body p-1" onclick="document.getElementById('flashToast').remove()">
        &times;
      </button>
    </div>
    <script>
      setTimeout(() => {
        const t = document.getElementById('flashToast');
        if (t) {
          t.style.opacity = '0';
          setTimeout(() => t.remove(), 200);
        }
      }, 4000);
    </script>
  <?php endif; ?>

  <!-- Main View Container: max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 -->
  <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
