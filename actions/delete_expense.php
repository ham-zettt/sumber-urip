<?php
/**
 * Sumber Urip - Action: Delete Expense
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../expenses.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken($token)) {
    setFlashMessage('error', 'Token keamanan tidak valid. Silakan coba kembali.');
    redirect('../expenses.php');
}

$id = (int)($_POST['expense_id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('error', 'ID pengeluaran tidak valid.');
    redirect('../expenses.php');
}

$pdo = Database::getConnection();

try {
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id");
    $stmt->execute([':id' => $id]);

    setFlashMessage('success', 'Catatan pengeluaran berhasil dihapus.');
    redirect('../expenses.php');

} catch (Exception $e) {
    error_log('Delete Expense Error: ' . $e->getMessage());
    setFlashMessage('error', 'Gagal menghapus pengeluaran.');
    redirect('../expenses.php');
}
