<?php
/**
 * Sumber Urip - Action: Update Products Prices & Costs
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../products.php');
}

$token = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken($token)) {
    setFlashMessage('error', 'Token keamanan tidak valid. Silakan coba kembali.');
    redirect('../products.php');
}

$productsData = $_POST['products'] ?? [];

if (!is_array($productsData) || empty($productsData)) {
    setFlashMessage('error', 'Tidak ada data produk yang dikirim.');
    redirect('../products.php');
}

$pdo = Database::getConnection();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE products 
        SET price = :price, 
            cost_price = :cost_price, 
            is_active = :is_active 
        WHERE id = :id
    ");

    $warnings = [];

    foreach ($productsData as $id => $data) {
        $productId = (int)$id;
        $price = (int)str_replace(['.', ',', ' '], '', (string)($data['price'] ?? 0));
        $costPrice = (int)str_replace(['.', ',', ' '], '', (string)($data['cost_price'] ?? 0));
        $isActive = !empty($data['is_active']) ? 1 : 0;

        // Validation rules
        if ($price <= 0) {
            throw new Exception("Harga jual untuk produk harus lebih dari 0.");
        }
        if ($costPrice < 0) {
            throw new Exception("Harga modal tidak boleh bernilai negatif.");
        }

        if ($costPrice > $price) {
            $warnings[] = "Perhatian: Harga modal lebih tinggi daripada harga jual.";
        }

        $stmt->execute([
            ':price' => $price,
            ':cost_price' => $costPrice,
            ':is_active' => $isActive,
            ':id' => $productId
        ]);
    }

    $pdo->commit();

    $message = 'Harga dan pengaturan produk berhasil diperbarui.';
    if (!empty($warnings)) {
        $message .= ' Catatan: Terdapat produk dengan harga modal melebihi harga jual!';
        setFlashMessage('warning', $message);
    } else {
        setFlashMessage('success', $message);
    }

    redirect('../products.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Update Products Error: ' . $e->getMessage());
    setFlashMessage('error', 'Gagal memperbarui produk: ' . $e->getMessage());
    redirect('../products.php');
}
