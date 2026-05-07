<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('catalog.php'); }
requireCsrf();

$user = currentUser();
$db = getDB();

$productId = (int)($_POST['product_id'] ?? 0);
$tglMulai = trim($_POST['tanggal_mulai'] ?? '');
$tglSelesai = trim($_POST['tanggal_selesai'] ?? '');
$jumlahUnit = max(1, (int)($_POST['jumlah_unit'] ?? 1));
$catatan = trim($_POST['catatan'] ?? '');

// Validasi
if (!$productId || !isValidDate($tglMulai) || !isValidDate($tglSelesai)) {
    setFlash('catalog', 'Data pemesanan tidak valid.', 'danger');
    redirect('catalog.php');
}

if (strtotime($tglSelesai) <= strtotime($tglMulai)) {
    setFlash('catalog', 'Tanggal selesai harus setelah tanggal mulai.', 'danger');
    redirect('catalog.php');
}

// Cek produk & stok
$stmt = $db->prepare("SELECT p.*, c.slug AS kategori_slug FROM products p JOIN categories c ON p.kategori_id=c.id WHERE p.id=? AND p.status='tersedia' LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('catalog', 'Produk tidak ditemukan atau tidak tersedia.', 'danger');
    redirect('catalog.php');
}
if ($product['stok'] < $jumlahUnit) {
    setFlash('catalog', 'Stok tidak mencukupi. Tersedia: ' . $product['stok'] . ' unit.', 'danger');
    redirect('catalog.php');
}

$jumlahHari = max(1, (int)ceil((strtotime($tglSelesai) - strtotime($tglMulai)) / 86400));
$subtotal = $product['harga_per_hari'] * $jumlahUnit * $jumlahHari;

try {
    $db->beginTransaction();

    $kodePesanan = generateOrderCode();

    // Insert order
    $stmt = $db->prepare("INSERT INTO rental_orders (kode_pesanan,user_id,tanggal_mulai,tanggal_selesai,jumlah_hari,total_harga,status_pesanan,catatan,created_at) VALUES (?,?,?,?,?,?,'pending',?,NOW())");
    $stmt->execute([$kodePesanan, $user['id'], $tglMulai, $tglSelesai, $jumlahHari, $subtotal, $catatan ?: null]);
    $orderId = (int)$db->lastInsertId();

    // Insert order item
    $stmt = $db->prepare("INSERT INTO rental_order_items (rental_order_id,product_id,jumlah_unit,harga_per_hari,subtotal) VALUES (?,?,?,?,?)");
    $stmt->execute([$orderId, $productId, $jumlahUnit, $product['harga_per_hari'], $subtotal]);

    // Insert payment (pending)
    $stmt = $db->prepare("INSERT INTO payments (rental_order_id,metode_pembayaran,jumlah_bayar,status_pembayaran,created_at) VALUES (?,'transfer_bank',?,'pending',NOW())");
    $stmt->execute([$orderId, $subtotal]);

    // Kurangi stok
    $stmt = $db->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
    $stmt->execute([$jumlahUnit, $productId]);

    // Update status produk jika stok habis
    $stmt = $db->prepare("UPDATE products SET status = 'habis' WHERE id = ? AND stok <= 0");
    $stmt->execute([$productId]);

    // Catat stock movement
    $stmt = $db->prepare("INSERT INTO stock_movements (product_id,tipe,jumlah,keterangan,created_at) VALUES (?,'keluar',?,?,NOW())");
    $stmt->execute([$productId, $jumlahUnit, "Order $kodePesanan"]);

    $db->commit();

    auditLog('create_order', 'rental_orders', $orderId, ['kode' => $kodePesanan, 'total' => $subtotal]);
    setFlash('order', "Pesanan $kodePesanan berhasil dibuat! Total: " . formatRupiah($subtotal), 'success');
    redirect('orders.php');

} catch (Exception $ex) {
    $db->rollBack();
    setFlash('catalog', 'Gagal membuat pesanan. Silakan coba lagi.', 'danger');
    redirect('catalog.php');
}
