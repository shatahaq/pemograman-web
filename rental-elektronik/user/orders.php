<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$user = currentUser();
$db = getDB();

$statusFilter = trim($_GET['status'] ?? '');
$where = ["ro.user_id = ?"];
$params = [$user['id']];

if (in_array($statusFilter, ['pending','aktif','selesai','dibatalkan'])) {
    $where[] = "ro.status_pesanan = ?";
    $params[] = $statusFilter;
}

$sql = "SELECT ro.id, ro.kode_pesanan, ro.user_id, ro.tanggal_mulai, ro.tanggal_selesai, ro.total_harga, ro.status_pesanan, ro.catatan, ro.created_at, ro.updated_at, GROUP_CONCAT(p.nama_produk SEPARATOR ', ') AS produk_list, MAX(pay.status_pembayaran) AS status_pembayaran FROM rental_orders ro JOIN rental_order_items roi ON roi.rental_order_id=ro.id JOIN products p ON p.id=roi.product_id LEFT JOIN payments pay ON pay.rental_order_id=ro.id WHERE " . implode(' AND ', $where) . " GROUP BY ro.id, ro.kode_pesanan, ro.user_id, ro.tanggal_mulai, ro.tanggal_selesai, ro.total_harga, ro.status_pesanan, ro.catatan, ro.created_at, ro.updated_at ORDER BY ro.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$flash = getFlash('order');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Saya | ElektraRent</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{primary:"var(--color-primary)",accent:"var(--color-accent)",ink:"var(--color-ink)",muted:"var(--color-muted)",paper:"var(--color-paper)"}}}};</script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen">
  <header class="nav-glass sticky top-0 z-40">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="../index.php" class="flex items-center gap-3"><span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span><span class="font-display text-2xl font-bold text-ink">ElektraRent</span></a>
      <div class="hidden items-center gap-4 md:flex">
        <a href="dashboard.php" class="font-bold text-slate-600 hover:text-primary">Dashboard</a>
        <a href="catalog.php" class="font-bold text-slate-600 hover:text-primary">Katalog</a>
        <a href="orders.php" class="font-bold text-primary">Pesanan</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </nav>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8"><p class="text-sm font-black uppercase tracking-wide text-accent">Riwayat Pesanan</p><h1 class="font-display mt-2 text-4xl font-bold text-ink">Pesanan Saya</h1></div>

    <?php if($flash):?><div class="mb-6 rounded-lg border p-4 text-sm font-semibold <?=$flash['type']==='success'?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'?>"><?=e($flash['message'])?></div><?php endif;?>

    <div class="mb-6 flex flex-wrap gap-2">
      <a href="orders.php" class="btn-<?=$statusFilter===''?'primary':'ghost'?> px-4 text-sm">Semua</a>
      <a href="orders.php?status=pending" class="btn-<?=$statusFilter==='pending'?'primary':'ghost'?> px-4 text-sm">Pending</a>
      <a href="orders.php?status=aktif" class="btn-<?=$statusFilter==='aktif'?'primary':'ghost'?> px-4 text-sm">Aktif</a>
      <a href="orders.php?status=selesai" class="btn-<?=$statusFilter==='selesai'?'primary':'ghost'?> px-4 text-sm">Selesai</a>
      <a href="orders.php?status=dibatalkan" class="btn-<?=$statusFilter==='dibatalkan'?'primary':'ghost'?> px-4 text-sm">Dibatalkan</a>
    </div>

    <div class="elegant-card">
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Kode</th><th>Produk</th><th>Periode</th><th>Total</th><th>Pembayaran</th><th>Status</th></tr></thead>
          <tbody>
          <?php if(empty($orders)):?><tr><td colspan="6" class="text-center text-muted py-10">Belum ada pesanan.</td></tr>
          <?php else: foreach($orders as $o):?>
          <tr>
            <td class="font-bold"><?=e($o['kode_pesanan'])?></td>
            <td><?=e($o['produk_list'])?></td>
            <td><?=formatTanggal($o['tanggal_mulai'])?> - <?=formatTanggal($o['tanggal_selesai'])?></td>
            <td><?=formatRupiah($o['total_harga'])?></td>
            <td><span class="status-badge <?=statusBadgeClass($o['status_pembayaran'] ?? 'pending')?>"><?=statusLabel($o['status_pembayaran'] ?? 'pending')?></span></td>
            <td><span class="status-badge <?=statusBadgeClass($o['status_pesanan'])?>"><?=statusLabel($o['status_pesanan'])?></span></td>
          </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="../assets/js/main.js"></script>
</body>
</html>
