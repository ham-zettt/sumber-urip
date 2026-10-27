<?php
/**
 * Sumber Urip - Action: Save Sale
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/queries.php';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
          || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
    }
    redirect('../index.php');
}

// Handle JSON payload or form payload
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

if (is_array($jsonData)) {
    $token = $jsonData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $items = $jsonData['items'] ?? [];
    $cashReceivedRaw = $jsonData['cash_received'] ?? null;
    $note = trim($jsonData['note'] ?? '');
    $soldAtCustom = trim($jsonData['sold_at'] ?? '');
} else {
    $token = $_POST['csrf_token'] ?? null;
    $items = $_POST['items'] ?? [];
    $cashReceivedRaw = $_POST['cash_received'] ?? null;
    $note = trim($_POST['note'] ?? '');
    $soldAtCustom = trim($_POST['sold_at'] ?? '');
}

if (!verifyCsrfToken($token)) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Token keamanan (CSRF) tidak valid atau sesi berakhir. Silakan muat ulang halaman.'], 403);
    }
    setFlashMessage('error', 'Token keamanan (CSRF) tidak valid. Silakan coba lagi.');
    redirect('../index.php');
}

// Validate items array
if (empty($items) || !is_array($items)) {
    $msg = 'Keranjang belanja kosong. Silakan pilih minimal 1 produk.';
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $msg], 422);
    }
    setFlashMessage('error', $msg);
    redirect('../index.php');
}

// Sanitize & consolidate items by product_id
$cleanItems = [];
foreach ($items as $item) {
    $pid = (int)($item['product_id'] ?? 0);
    $qty = (int)($item['quantity'] ?? 0);

    if ($pid <= 0 || $qty <= 0) {
        continue;
    }
    if ($qty > 999) {
        $qty = 999;
    }

    if (isset($cleanItems[$pid])) {
        $cleanItems[$pid] += $qty;
    } else {
        $cleanItems[$pid] = $qty;
    }
}

if (empty($cleanItems)) {
    $msg = 'Jumlah produk yang valid minimal 1 unit.';
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $msg], 422);
    }
    setFlashMessage('error', $msg);
    redirect('../index.php');
}

// Validate cash received (optional)
$cashReceived = null;
if ($cashReceivedRaw !== null && $cashReceivedRaw !== '') {
    $cashClean = (int)str_replace(['.', ',', ' '], '', (string)$cashReceivedRaw);
    if ($cashClean >= 0) {
        $cashReceived = $cashClean;
    }
}

// Validate note (max 255 chars)
if (mb_strlen($note) > 255) {
    $note = mb_substr($note, 0, 255);
}

// Validate sold_at date if provided (must be valid datetime in Asia/Jakarta)
$soldAt = date('Y-m-d H:i:s');
if (!empty($soldAtCustom)) {
    try {
        $customDt = new DateTime($soldAtCustom, new DateTimeZone('Asia/Jakarta'));
        $soldAt = $customDt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        // Fallback to current time
        $soldAt = date('Y-m-d H:i:s');
    }
}

$pdo = Database::getConnection();

try {
    $pdo->beginTransaction();

    // Fetch active products from DB to recalculate everything on server side
    $productIds = array_keys($cleanItems);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT id, name, price, cost_price, is_active 
        FROM products 
        WHERE id IN ($placeholders)
        FOR UPDATE
    ");
    $stmt->execute($productIds);
    $dbProducts = $stmt->fetchAll();

    $productMap = [];
    foreach ($dbProducts as $p) {
        $productMap[$p['id']] = $p;
    }

    // Verify all products exist and are active
    $calculatedTotalAmount = 0;
    $calculatedTotalCost = 0;
    $saleItemsData = [];

    foreach ($cleanItems as $pid => $qty) {
        if (!isset($productMap[$pid])) {
            throw new Exception("Produk dengan ID {$pid} tidak ditemukan.");
        }
        $prod = $productMap[$pid];
        if ((int)$prod['is_active'] !== 1) {
            throw new Exception("Produk '{$prod['name']}' saat ini tidak aktif.");
        }

        $unitPrice = (int)$prod['price'];
        $unitCost = (int)$prod['cost_price'];
        $subtotal = $qty * $unitPrice;
        $costSubtotal = $qty * $unitCost;

        $calculatedTotalAmount += $subtotal;
        $calculatedTotalCost += $costSubtotal;

        $saleItemsData[] = [
            'product_id' => $prod['id'],
            'product_name' => $prod['name'],
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'unit_cost' => $unitCost,
            'subtotal' => $subtotal,
            'cost_subtotal' => $costSubtotal
        ];
    }

    // Calculate change amount if cash provided
    $changeAmount = null;
    if ($cashReceived !== null) {
        $changeAmount = $cashReceived - $calculatedTotalAmount;
    }

    // Generate atomic invoice number
    $invoiceNo = generateInvoiceNumber($pdo, $soldAt);

    // Insert into sales table
    $insertSaleStmt = $pdo->prepare("
        INSERT INTO sales (
            invoice_no, sold_at, total_amount, total_cost, 
            cash_received, change_amount, note, status, created_at
        ) VALUES (
            :invoice_no, :sold_at, :total_amount, :total_cost, 
            :cash_received, :change_amount, :note, 'completed', NOW()
        )
    ");
    $insertSaleStmt->execute([
        ':invoice_no' => $invoiceNo,
        ':sold_at' => $soldAt,
        ':total_amount' => $calculatedTotalAmount,
        ':total_cost' => $calculatedTotalCost,
        ':cash_received' => $cashReceived,
        ':change_amount' => $changeAmount,
        ':note' => !empty($note) ? $note : null
    ]);

    $saleId = (int)$pdo->lastInsertId();

    // Insert sale items with price and cost snapshots
    $insertItemStmt = $pdo->prepare("
        INSERT INTO sale_items (
            sale_id, product_id, product_name, quantity, 
            unit_price, unit_cost, subtotal, cost_subtotal
        ) VALUES (
            :sale_id, :product_id, :product_name, :quantity, 
            :unit_price, :unit_cost, :subtotal, :cost_subtotal
        )
    ");

    foreach ($saleItemsData as $itemRow) {
        $insertItemStmt->execute([
            ':sale_id' => $saleId,
            ':product_id' => $itemRow['product_id'],
            ':product_name' => $itemRow['product_name'],
            ':quantity' => $itemRow['quantity'],
            ':unit_price' => $itemRow['unit_price'],
            ':unit_cost' => $itemRow['unit_cost'],
            ':subtotal' => $itemRow['subtotal'],
            ':cost_subtotal' => $itemRow['cost_subtotal']
        ]);
    }

    $pdo->commit();

    $successMsg = "Transaksi {$invoiceNo} berhasil disimpan! Total: " . formatRupiah($calculatedTotalAmount);

    if ($isAjax) {
        jsonResponse([
            'success' => true,
            'message' => $successMsg,
            'sale_id' => $saleId,
            'invoice_no' => $invoiceNo,
            'total_amount' => $calculatedTotalAmount,
            'formatted_total' => formatRupiah($calculatedTotalAmount),
            'cash_received' => $cashReceived,
            'change_amount' => $changeAmount,
            'formatted_change' => $changeAmount !== null ? formatRupiah($changeAmount) : null
        ]);
    }

    setFlashMessage('success', $successMsg);
    redirect('../index.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Save Sale Error: ' . $e->getMessage());

    $errorMsg = 'Gagal menyimpan transaksi: ' . $e->getMessage();
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $errorMsg], 500);
    }
    setFlashMessage('error', $errorMsg);
    redirect('../index.php');
}
