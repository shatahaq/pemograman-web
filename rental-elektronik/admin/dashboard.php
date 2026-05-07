<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireAdmin();

$db = getDB();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM rental_orders")->fetchColumn();
$totalPendapatan = $db->query("SELECT COALESCE(SUM(jumlah_bayar),0) FROM payments WHERE status_pembayaran='lunas'")->fetchColumn();
$ordersPending = $db->query("SELECT COUNT(*) FROM rental_orders WHERE status_pesanan='pending'")->fetchColumn();
$ordersAktif = $db->query("SELECT COUNT(*) FROM rental_orders WHERE status_pesanan='aktif'")->fetchColumn();

$recentOrders = $db->query("SELECT ro.kode_pesanan,ro.status_pesanan,ro.total_harga,ro.created_at,u.nama_lengkap FROM rental_orders ro JOIN users u ON u.id=ro.user_id ORDER BY ro.created_at DESC LIMIT 5")->fetchAll();
$recentUsers = $db->query("SELECT nama_lengkap,email,status_akun,created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$flash = getFlash('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | ElektraRent</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{primary:"var(--color-primary)",accent:"var(--color-accent)",ink:"var(--color-ink)",muted:"var(--color-muted)",paper:"var(--color-paper)"}}}};</script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen">
  <header class="nav-glass sticky top-0 z-40">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="../index.php" class="flex items-center gap-3"><span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span><span class="font-display text-2xl font-bold text-ink">ElektraRent</span><span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Admin</span></a>
      <button type="button" class="icon-btn md:hidden" data-nav-toggle data-target="#adminMobileMenu" aria-label="Menu"><i data-lucide="menu" class="h-5 w-5"></i></button>
      <div class="hidden items-center gap-4 md:flex">
        <a href="dashboard.php" class="font-bold text-primary">Dashboard</a>
        <a href="products.php" class="font-bold text-slate-600 hover:text-primary">Produk</a>
        <a href="orders.php" class="font-bold text-slate-600 hover:text-primary">Pesanan</a>
        <a href="users.php" class="font-bold text-slate-600 hover:text-primary">Users</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </nav>
    <div id="adminMobileMenu" class="hidden border-t border-slate-200 bg-white px-4 py-4 md:hidden">
      <div class="flex flex-col gap-3">
        <a href="dashboard.php" class="font-bold text-primary">Dashboard</a>
        <a href="products.php" class="font-bold text-slate-700">Produk</a>
        <a href="orders.php" class="font-bold text-slate-700">Pesanan</a>
        <a href="users.php" class="font-bold text-slate-700">Users</a>
        <a href="../auth/logout.php" class="btn-secondary px-4">Logout</a>
      </div>
    </div>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <?php if($flash):?><div class="mb-6 rounded-lg border p-4 text-sm font-semibold <?=$flash['type']==='success'?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'?>"><?=e($flash['message'])?></div><?php endif;?>

    <div class="mb-8"><p class="text-sm font-black uppercase tracking-wide text-accent">Admin Panel</p><h1 class="font-display mt-2 text-4xl font-bold text-ink">Dashboard Admin</h1></div>

    <section class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
      <article class="elegant-card p-6"><span class="rounded-lg bg-sky-50 p-3 text-primary inline-block"><i data-lucide="users" class="h-6 w-6"></i></span><p class="mt-4 text-3xl font-black text-ink"><?=e((string)$totalUsers)?></p><p class="mt-1 font-bold text-muted">Total User</p></article>
      <article class="elegant-card p-6"><span class="rounded-lg bg-amber-50 p-3 text-accent inline-block"><i data-lucide="package" class="h-6 w-6"></i></span><p class="mt-4 text-3xl font-black text-ink"><?=e((string)$totalProducts)?></p><p class="mt-1 font-bold text-muted">Total Produk</p></article>
      <article class="elegant-card p-6"><span class="rounded-lg bg-emerald-50 p-3 text-emerald-700 inline-block"><i data-lucide="shopping-cart" class="h-6 w-6"></i></span><p class="mt-4 text-3xl font-black text-ink"><?=e((string)$totalOrders)?></p><p class="mt-1 font-bold text-muted">Total Pesanan</p></article>
      <article class="elegant-card p-6"><span class="rounded-lg bg-rose-50 p-3 text-rose-700 inline-block"><i data-lucide="banknote" class="h-6 w-6"></i></span><p class="mt-4 text-3xl font-black text-ink"><?=formatRupiah($totalPendapatan)?></p><p class="mt-1 font-bold text-muted">Pendapatan</p></article>
    </section>

    <section class="mt-5 grid gap-5 md:grid-cols-2">
      <article class="elegant-card p-5"><span class="rounded-lg bg-yellow-50 p-3 text-yellow-700 inline-block"><i data-lucide="clock" class="h-6 w-6"></i></span><p class="mt-4 text-3xl font-black text-ink"><?=e((string)$ordersPending)?></p><p class="mt-1 font-bold text-muted">Order Pending</p></article>
      <article class="elegant-card p-5"><span class="rounded-lg bg-blue-50 p-3 text-blue-700 inline-block"><i data-lucide="activity" class="h-6 w-6"></i></span><p class="mt-4 text-3xl font-black text-ink"><?=e((string)$ordersAktif)?></p><p class="mt-1 font-bold text-muted">Order Aktif</p></article>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-2">
      <div class="elegant-card p-5">
        <h2 class="font-display text-2xl font-bold text-ink mb-4">Pesanan Terbaru</h2>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Kode</th><th>User</th><th>Total</th><th>Status</th></tr></thead><tbody>
        <?php foreach($recentOrders as $o):?><tr><td class="font-bold"><?=e($o['kode_pesanan'])?></td><td><?=e($o['nama_lengkap'])?></td><td><?=formatRupiah($o['total_harga'])?></td><td><span class="status-badge <?=statusBadgeClass($o['status_pesanan'])?>"><?=statusLabel($o['status_pesanan'])?></span></td></tr><?php endforeach;?>
        </tbody></table></div>
      </div>
      <div class="elegant-card p-5">
        <h2 class="font-display text-2xl font-bold text-ink mb-4">User Terbaru</h2>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Nama</th><th>Email</th><th>Status</th></tr></thead><tbody>
        <?php foreach($recentUsers as $u):?><tr><td class="font-bold"><?=e($u['nama_lengkap'])?></td><td><?=e($u['email'])?></td><td><span class="status-badge <?=statusBadgeClass($u['status_akun'])?>"><?=statusLabel($u['status_akun'])?></span></td></tr><?php endforeach;?>
        </tbody></table></div>
      </div>
    </section>
  </main>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="../assets/js/main.js"></script>
</body>
</html>
