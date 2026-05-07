<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireAdmin();

$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);

    if ($action === 'update_status' && $orderId) {
        $newStatus = $_POST['status_pesanan'] ?? '';
        if (in_array($newStatus, ['pending','aktif','selesai','dibatalkan'])) {
            // Jika dibatalkan, kembalikan stok
            if ($newStatus === 'dibatalkan') {
                $items = $db->prepare("SELECT product_id, jumlah_unit FROM rental_order_items WHERE rental_order_id=?");
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $item) {
                    $db->prepare("UPDATE products SET stok=stok+?, status='tersedia' WHERE id=?")->execute([$item['jumlah_unit'], $item['product_id']]);
                    $db->prepare("INSERT INTO stock_movements (product_id,tipe,jumlah,keterangan,created_at) VALUES (?,'masuk',?,?,NOW())")->execute([$item['product_id'], $item['jumlah_unit'], "Pembatalan order"]);
                }
                $db->prepare("UPDATE payments SET status_pembayaran='gagal' WHERE rental_order_id=?")->execute([$orderId]);
            }
            // Jika aktif, set pembayaran lunas
            if ($newStatus === 'aktif') {
                $db->prepare("UPDATE payments SET status_pembayaran='lunas', tanggal_bayar=NOW() WHERE rental_order_id=?")->execute([$orderId]);
            }
            // Jika selesai, kembalikan stok
            if ($newStatus === 'selesai') {
                $items = $db->prepare("SELECT product_id, jumlah_unit FROM rental_order_items WHERE rental_order_id=?");
                $items->execute([$orderId]);
                foreach ($items->fetchAll() as $item) {
                    $db->prepare("UPDATE products SET stok=stok+?, status='tersedia' WHERE id=?")->execute([$item['jumlah_unit'], $item['product_id']]);
                    $db->prepare("INSERT INTO stock_movements (product_id,tipe,jumlah,keterangan,created_at) VALUES (?,'masuk',?,?,NOW())")->execute([$item['product_id'], $item['jumlah_unit'], "Pengembalian order"]);
                }
            }

            $db->prepare("UPDATE rental_orders SET status_pesanan=?,updated_at=NOW() WHERE id=?")->execute([$newStatus, $orderId]);
            auditLog('update_order_status', 'rental_orders', $orderId, ['status' => $newStatus]);
            setFlash('admin_orders', "Status pesanan berhasil diperbarui.", 'success');
        }
        redirect('orders.php');
    }
}

$statusFilter = trim($_GET['status'] ?? '');
$where = "1=1";
$params = [];
if (in_array($statusFilter, ['pending','aktif','selesai','dibatalkan'])) {
    $where = "ro.status_pesanan = ?";
    $params[] = $statusFilter;
}

$orders = $db->prepare("SELECT ro.id, ro.kode_pesanan, ro.user_id, ro.tanggal_mulai, ro.tanggal_selesai, ro.total_harga, ro.status_pesanan, ro.catatan, ro.created_at, ro.updated_at, MAX(u.nama_lengkap) AS nama_lengkap, MAX(u.email) AS email, GROUP_CONCAT(p.nama_produk SEPARATOR ', ') AS produk_list, MAX(pay.status_pembayaran) AS status_pembayaran FROM rental_orders ro JOIN users u ON u.id=ro.user_id JOIN rental_order_items roi ON roi.rental_order_id=ro.id JOIN products p ON p.id=roi.product_id LEFT JOIN payments pay ON pay.rental_order_id=ro.id WHERE $where GROUP BY ro.id, ro.kode_pesanan, ro.user_id, ro.tanggal_mulai, ro.tanggal_selesai, ro.total_harga, ro.status_pesanan, ro.catatan, ro.created_at, ro.updated_at ORDER BY ro.created_at DESC");
$orders->execute($params);
$orders = $orders->fetchAll();

$flash = getFlash('admin_orders');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Pesanan | ElektraRent Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{primary:"var(--color-primary)",accent:"var(--color-accent)",ink:"var(--color-ink)",muted:"var(--color-muted)"}}}};</script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen">
  <header class="nav-glass sticky top-0 z-40">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="../index.php" class="flex items-center gap-3"><span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span><span class="font-display text-2xl font-bold text-ink">ElektraRent</span><span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Admin</span></a>
      <div class="hidden items-center gap-4 md:flex">
        <a href="dashboard.php" class="font-bold text-slate-600 hover:text-primary">Dashboard</a>
        <a href="products.php" class="font-bold text-slate-600 hover:text-primary">Produk</a>
        <a href="orders.php" class="font-bold text-primary">Pesanan</a>
        <a href="users.php" class="font-bold text-slate-600 hover:text-primary">Users</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </nav>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8"><p class="text-sm font-black uppercase tracking-wide text-accent">Admin Panel</p><h1 class="font-display mt-2 text-4xl font-bold text-ink">Kelola Pesanan</h1></div>

    <?php if($flash):?><div class="mb-6 rounded-lg border p-4 text-sm font-semibold <?=$flash['type']==='success'?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'?>"><?=e($flash['message'])?></div><?php endif;?>

    <div class="mb-6 flex flex-wrap gap-2">
      <a href="orders.php" class="btn-<?=$statusFilter===''?'primary':'ghost'?> px-4 text-sm">Semua</a>
      <a href="orders.php?status=pending" class="btn-<?=$statusFilter==='pending'?'primary':'ghost'?> px-4 text-sm">Pending</a>
      <a href="orders.php?status=aktif" class="btn-<?=$statusFilter==='aktif'?'primary':'ghost'?> px-4 text-sm">Aktif</a>
      <a href="orders.php?status=selesai" class="btn-<?=$statusFilter==='selesai'?'primary':'ghost'?> px-4 text-sm">Selesai</a>
      <a href="orders.php?status=dibatalkan" class="btn-<?=$statusFilter==='dibatalkan'?'primary':'ghost'?> px-4 text-sm">Dibatalkan</a>
    </div>

    <div class="elegant-card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Kode</th><th>User</th><th>Produk</th><th>Periode</th><th>Total</th><th>Bayar</th><th>Status / Aksi</th></tr></thead><tbody>
    <?php if(empty($orders)):?><tr><td colspan="7" class="text-center text-muted py-10">Tidak ada pesanan.</td></tr>
    <?php else: foreach($orders as $o):?><tr>
      <td class="font-bold"><?=e($o['kode_pesanan'])?></td>
      <td><?=e($o['nama_lengkap'])?></td>
      <td class="max-w-[180px] truncate"><?=e($o['produk_list'])?></td>
      <td class="whitespace-nowrap text-xs"><?=formatTanggal($o['tanggal_mulai'])?> - <?=formatTanggal($o['tanggal_selesai'])?></td>
      <td class="whitespace-nowrap"><?=formatRupiah($o['total_harga'])?></td>
      <td><span class="status-badge <?=statusBadgeClass($o['status_pembayaran']??'pending')?>"><?=statusLabel($o['status_pembayaran']??'pending')?></span></td>
      <td>
        <form method="POST" class="flex flex-col gap-1">
          <?=csrfField()?><input type="hidden" name="action" value="update_status"><input type="hidden" name="order_id" value="<?=$o['id']?>">
          <select name="status_pesanan" class="input-elegant text-xs py-1 px-2 w-full">
            <?php foreach(['pending','aktif','selesai','dibatalkan'] as $s):?><option value="<?=$s?>" <?=$o['status_pesanan']===$s?'selected':''?>><?=statusLabel($s)?></option><?php endforeach;?>
          </select>
          <button type="submit" class="btn-ghost px-2 text-xs w-full">Update</button>
        </form>
      </td>
    </tr><?php endforeach; endif;?></tbody></table></div></div>
  </main>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="../assets/js/main.js"></script>
</body>
</html>
