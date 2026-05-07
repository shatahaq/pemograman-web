<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$user = currentUser();
$db = getDB();

$stmtAktif = $db->prepare("SELECT COUNT(*) FROM rental_orders WHERE user_id = ? AND status_pesanan = 'aktif'");
$stmtAktif->execute([$user['id']]);
$activeOrders = $stmtAktif->fetchColumn();

$stmtTotal = $db->prepare("SELECT COUNT(*) FROM rental_orders WHERE user_id = ?");
$stmtTotal->execute([$user['id']]);
$totalOrders = $stmtTotal->fetchColumn();

$stmtPending = $db->prepare("SELECT COALESCE(SUM(ro.total_harga),0) FROM rental_orders ro JOIN payments p ON p.rental_order_id=ro.id WHERE ro.user_id=? AND p.status_pembayaran='pending'");
$stmtPending->execute([$user['id']]);
$pendingAmount = $stmtPending->fetchColumn();

$stmtRecent = $db->prepare("SELECT ro.kode_pesanan, ro.tanggal_mulai, ro.tanggal_selesai, ro.total_harga, ro.status_pesanan, GROUP_CONCAT(p.nama_produk SEPARATOR ', ') AS produk_list FROM rental_orders ro JOIN rental_order_items roi ON roi.rental_order_id=ro.id JOIN products p ON p.id=roi.product_id WHERE ro.user_id=? GROUP BY ro.id, ro.kode_pesanan, ro.tanggal_mulai, ro.tanggal_selesai, ro.total_harga, ro.status_pesanan, ro.created_at ORDER BY ro.created_at DESC LIMIT 5");
$stmtRecent->execute([$user['id']]);
$recentOrders = $stmtRecent->fetchAll();

$flash = getFlash('dashboard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Pengguna | ElektraRent</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{primary:"var(--color-primary)",accent:"var(--color-accent)",ink:"var(--color-ink)",muted:"var(--color-muted)",paper:"var(--color-paper)"}}}};</script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen">
  <header class="nav-glass sticky top-0 z-40">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="../index.php" class="flex items-center gap-3"><span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span><span class="font-display text-2xl font-bold text-ink">ElektraRent</span></a>
      <button type="button" class="icon-btn md:hidden" data-nav-toggle data-target="#userDashboardMobileMenu" aria-label="Buka navigasi"><i data-lucide="menu" class="h-5 w-5"></i></button>
      <div class="hidden items-center gap-4 md:flex">
        <a href="dashboard.php" class="font-bold text-primary">Dashboard</a>
        <a href="catalog.php" class="font-bold text-slate-600 hover:text-primary">Katalog</a>
        <a href="orders.php" class="font-bold text-slate-600 hover:text-primary">Pesanan</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </nav>
    <div id="userDashboardMobileMenu" class="hidden border-t border-slate-200 bg-white px-4 py-4 md:hidden">
      <div class="flex flex-col gap-3">
        <a href="dashboard.php" class="font-bold text-primary">Dashboard</a>
        <a href="catalog.php" class="font-bold text-slate-700">Katalog</a>
        <a href="orders.php" class="font-bold text-slate-700">Pesanan</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </div>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <?php if($flash):?><div class="mb-6 rounded-lg border p-4 text-sm font-semibold <?=$flash['type']==='success'?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'?>"><?=e($flash['message'])?></div><?php endif;?>
    <section class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-stretch">
      <div class="soft-panel p-6 sm:p-8">
        <p class="text-sm font-black uppercase tracking-wide text-accent">Dashboard Pengguna</p>
        <h1 class="font-display mt-2 text-4xl font-bold text-ink">Halo, <?=e($user['nama_lengkap'])?>.</h1>
        <p class="mt-3 max-w-2xl leading-7 text-muted">Pantau rental aktif, riwayat transaksi, dan status tagihan Anda.</p>
        <div class="mt-7 flex flex-wrap gap-3">
          <a href="catalog.php" class="btn-primary px-5"><i data-lucide="shopping-bag" class="h-5 w-5"></i>Sewa Barang</a>
          <a href="orders.php" class="btn-secondary px-5"><i data-lucide="receipt-text" class="h-5 w-5"></i>Lihat Pesanan</a>
        </div>
      </div>
      <div class="image-frame min-h-[280px]"><img src="https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1100&q=85" alt="Dashboard"></div>
    </section>
    <section class="mt-8 grid gap-5 md:grid-cols-3">
      <article class="elegant-card p-6"><div class="flex items-center justify-between"><span class="rounded-lg bg-sky-50 p-3 text-primary"><i data-lucide="package-open" class="h-6 w-6"></i></span><span class="text-sm font-bold text-muted">Aktif</span></div><p class="mt-5 text-3xl font-black text-ink"><?=e((string)$activeOrders)?></p><p class="mt-1 font-bold text-muted">Barang aktif disewa</p></article>
      <article class="elegant-card p-6"><div class="flex items-center justify-between"><span class="rounded-lg bg-amber-50 p-3 text-accent"><i data-lucide="repeat-2" class="h-6 w-6"></i></span><span class="text-sm font-bold text-muted">Total</span></div><p class="mt-5 text-3xl font-black text-ink"><?=e((string)$totalOrders)?></p><p class="mt-1 font-bold text-muted">Total transaksi</p></article>
      <article class="elegant-card p-6"><div class="flex items-center justify-between"><span class="rounded-lg bg-rose-50 p-3 text-red-700"><i data-lucide="wallet-cards" class="h-6 w-6"></i></span><span class="text-sm font-bold text-muted">Pending</span></div><p class="mt-5 text-3xl font-black text-ink"><?=formatRupiah($pendingAmount)?></p><p class="mt-1 font-bold text-muted">Tagihan pending</p></article>
    </section>
    <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_22rem]">
      <div class="elegant-card p-5 sm:p-6">
        <div class="mb-5 flex items-center justify-between gap-4"><div><p class="text-sm font-black uppercase tracking-wide text-accent">Pesanan Terbaru</p><h2 class="font-display mt-1 text-3xl font-bold text-ink">Aktivitas rental</h2></div><a href="orders.php" class="icon-btn" aria-label="Semua pesanan"><i data-lucide="arrow-up-right" class="h-5 w-5"></i></a></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>ID Pesanan</th><th>Produk</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead><tbody>
          <?php if(empty($recentOrders)):?><tr><td colspan="5" class="text-center text-muted py-8">Belum ada pesanan.</td></tr>
          <?php else: foreach($recentOrders as $o):?><tr><td class="font-bold"><?=e($o['kode_pesanan'])?></td><td><?=e($o['produk_list'])?></td><td><?=formatTanggal($o['tanggal_mulai'])?> - <?=formatTanggal($o['tanggal_selesai'])?></td><td><?=formatRupiah($o['total_harga'])?></td><td><span class="status-badge <?=statusBadgeClass($o['status_pesanan'])?>"><?=statusLabel($o['status_pesanan'])?></span></td></tr>
          <?php endforeach; endif;?></tbody></table></div>
      </div>
      <aside class="elegant-card p-5 sm:p-6">
        <p class="text-sm font-black uppercase tracking-wide text-accent">Shortcut</p>
        <h2 class="font-display mt-1 text-3xl font-bold text-ink">Menu cepat</h2>
        <div class="mt-5 grid gap-3">
          <a href="catalog.php" class="soft-panel flex items-center gap-3 p-4 font-bold text-slate-700 hover:text-primary"><i data-lucide="grid-3x3" class="h-5 w-5"></i>Katalog Barang</a>
          <a href="orders.php" class="soft-panel flex items-center gap-3 p-4 font-bold text-slate-700 hover:text-primary"><i data-lucide="clipboard-list" class="h-5 w-5"></i>Riwayat Pesanan</a>
          <a href="../auth/logout.php" class="soft-panel flex items-center gap-3 p-4 font-bold text-slate-700 hover:text-primary"><i data-lucide="lock-keyhole" class="h-5 w-5"></i>Ganti Akun</a>
        </div>
      </aside>
    </section>
  </main>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="../assets/js/main.js"></script>
</body>
</html>
