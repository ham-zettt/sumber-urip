<?php
/**
 * Sumber Urip - Configuration File
 */

// Error reporting toggle
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Timezone configuration (Mandatory: Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'sumber_urip');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Constants
define('APP_NAME', 'Sumber Urip');
define('APP_TAGLINE', 'Manajemen Depot Air Minum');
define('ITEMS_PER_PAGE', 20);

// Fixed Expense Categories (PRD Section 4.4)
define('EXPENSE_CATEGORIES', [
    'Listrik',
    'Air (PDAM)',
    'Filter & Perawatan',
    'Gaji',
    'Transport',
    'Sewa',
    'Lainnya'
]);

// Start Session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
