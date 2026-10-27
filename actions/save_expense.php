<?php
/**
 * Sumber Urip - Action: Save Expense (Create or Edit)
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

$id = !empty($_POST['expense_id']) ? (int)$_POST['expense_id'] : null;
$date = trim($_POST['expense_date'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$amountRaw = trim($_POST['amount'] ?? '');

// Validation
$amount = (int)str_replace(['.', ',', ' '], '', $amountRaw);

if ($amount <= 0) {
    setFlashMessage('error', 'Jumlah pengeluaran harus berupa angka lebih dari 0.');
    redirect('../expenses.php');
}

if (!in_array($category, EXPENSE_CATEGORIES, true)) {
    setFlashMessage('error', 'Kategori pengeluaran tidak valid.');
    redirect('../expenses.php');
}

if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    setFlashMessage('error', 'Format tanggal tidak valid.');
    redirect('../expenses.php');
}

if (mb_strlen($description) > 255) {
    $description = mb_substr($description, 0, 255);
}

$pdo = Database::getConnection();

try {
    if ($id && $id > 0) {
        // Update existing expense
        $stmt = $pdo->prepare("
            UPDATE expenses 
            SET expense_date = :expense_date,
                category = :category,
                description = :description,
                amount = :amount,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':expense_date' => $date,
            ':category' => $category,
            ':description' => !empty($description) ? $description : null,
            ':amount' => $amount,
            ':id' => $id
        ]);
        setFlashMessage('success', 'Pengeluaran operasional berhasil diperbarui.');
    } else {
        // Insert new expense
        $stmt = $pdo->prepare("
            INSERT INTO expenses (expense_date, category, description, amount, created_at)
            VALUES (:expense_date, :category, :description, :amount, NOW())
        ");
        $stmt->execute([
            ':expense_date' => $date,
            ':category' => $category,
            ':description' => !empty($description) ? $description : null,
            ':amount' => $amount
        ]);
        setFlashMessage('success', 'Pengeluaran sebesar ' . formatRupiah($amount) . ' berhasil dicatat.');
    }

    redirect('../expenses.php');

} catch (Exception $e) {
    error_log('Save Expense Error: ' . $e->getMessage());
    setFlashMessage('error', 'Gagal menyimpan pengeluaran: ' . $e->getMessage());
    redirect('../expenses.php');
}
