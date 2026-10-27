<?php
/**
 * Sumber Urip - Action: Void (Cancel) Sale
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../history.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken($token)) {
    setFlashMessage('error', 'Token keamanan tidak valid. Silakan coba kembali.');
    redirect('../history.php');
}

$saleId = (int)($_POST['sale_id'] ?? 0);
$reason = trim($_POST['void_reason'] ?? '');

if (mb_strlen($reason) > 255) {
    $reason = mb_substr($reason, 0, 255);
}

if ($saleId <= 0) {
    setFlashMessage('error', 'ID transaksi tidak valid.');
    redirect('../history.php');
}

$pdo = Database::getConnection();

try {
    // Check current status
    $stmt = $pdo->prepare("SELECT id, invoice_no, status FROM sales WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $saleId]);
    $sale = $stmt->fetch();

    if (!$sale) {
        setFlashMessage('error', 'Transaksi tidak ditemukan.');
        redirect('../history.php');
    }

    if ($sale['status'] === 'void') {
        setFlashMessage('warning', 'Transaksi ini sudah dibatalkan sebelumnya.');
        redirect("../history_detail.php?id={$saleId}");
    }

    // Perform soft-delete
    $updateStmt = $pdo->prepare("
        UPDATE sales 
        SET status = 'void',
            voided_at = NOW(),
            void_reason = :reason
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':reason' => !empty($reason) ? $reason : 'Dibatalkan oleh kasir',
        ':id' => $saleId
    ]);

    setFlashMessage('success', "Transaksi {$sale['invoice_no']} berhasil dibatalkan.");
    redirect("../history_detail.php?id={$saleId}");

} catch (Exception $e) {
    error_log('Void Sale Error: ' . $e->getMessage());
    setFlashMessage('error', 'Terjadi kesalahan saat membatalkan transaksi.');
    redirect("../history_detail.php?id={$saleId}");
}
