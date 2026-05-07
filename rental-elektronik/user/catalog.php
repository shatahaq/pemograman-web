<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();

$user = currentUser();
$db = getDB();

// Filter parameters
$search = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');

// Build query
$where = ["p.status = 'tersedia'"];
$params = [];

if ($search !== '') {
    $where[] = "(p.nama_produk LIKE ? OR p.kode_produk LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($kategori !== '') {
    $where[] = "c.slug = ?";
    $params[] = $kategori;
}

$orderBy = match($sort) {
    'termurah' => 'p.harga_per_hari ASC',
    'termahal' => 'p.harga_per_hari DESC',
    default    => 'p.created_at DESC',
};

$sql = "SELECT p.*, c.nama_kategori, c.slug AS kategori_slug FROM products p JOIN categories c ON p.kategori_id = c.id WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Kategori untuk filter
$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori")->fetchAll();

$flash = getFlash('catalog');
$orderFlash = getFlash('order');
$flashMsg = $flash ?? $orderFlash;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Katalog Barang | ElektraRent</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{primary:"var(--color-primary)",accent:"var(--color-accent)",ink:"var(--color-ink)",muted:"var(--color-muted)",paper:"var(--color-paper)"}}}};</script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen">
  <header class="nav-glass sticky top-0 z-40">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="../index.php" class="flex items-center gap-3"><span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span><span class="font-display text-2xl font-bold text-ink">ElektraRent</span></a>
      <button type="button" class="icon-btn md:hidden" data-nav-toggle data-target="#userCatalogMobileMenu" aria-label="Menu"><i data-lucide="menu" class="h-5 w-5"></i></button>
      <div class="hidden items-center gap-4 md:flex">
        <a href="dashboard.php" class="font-bold text-slate-600 hover:text-primary">Dashboard</a>
        <a href="catalog.php" class="font-bold text-primary">Katalog</a>
        <a href="orders.php" class="font-bold text-slate-600 hover:text-primary">Pesanan</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </nav>
    <div id="userCatalogMobileMenu" class="hidden border-t border-slate-200 bg-white px-4 py-4 md:hidden">
      <div class="flex flex-col gap-3">
        <a href="dashboard.php" class="font-bold text-slate-700">Dashboard</a>
        <a href="catalog.php" class="font-bold text-primary">Katalog</a>
        <a href="orders.php" class="font-bold text-slate-700">Pesanan</a>
        <a href="../auth/logout.php" class="btn-secondary px-4">Logout</a>
      </div>
    </div>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8"><p class="text-sm font-black uppercase tracking-wide text-accent">Katalog Barang</p><h1 class="font-display mt-2 text-4xl font-bold text-ink">Unit Tersedia untuk Disewa</h1></div>

    <?php if($flashMsg):?><div class="mb-6 rounded-lg border p-4 text-sm font-semibold <?=$flashMsg['type']==='success'?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'?>"><?=e($flashMsg['message'])?></div><?php endif;?>

    <form method="GET" class="elegant-card mb-8 grid gap-4 p-5 md:grid-cols-[1fr_auto_auto_auto]">
      <input type="text" name="search" value="<?=e($search)?>" placeholder="Cari nama atau kode produk..." class="input-elegant">
      <select name="kategori" class="input-elegant"><option value="">Semua Kategori</option><?php foreach($categories as $cat):?><option value="<?=e($cat['slug'])?>" <?=$kategori===$cat['slug']?'selected':''?>><?=e($cat['nama_kategori'])?></option><?php endforeach;?></select>
      <select name="sort" class="input-elegant"><option value="terbaru" <?=$sort==='terbaru'?'selected':''?>>Terbaru</option><option value="termurah" <?=$sort==='termurah'?'selected':''?>>Termurah</option><option value="termahal" <?=$sort==='termahal'?'selected':''?>>Termahal</option></select>
      <button type="submit" class="btn-primary px-5"><i data-lucide="search" class="h-4 w-4"></i>Filter</button>
    </form>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <?php if(empty($products)):?>
      <div class="md:col-span-3 elegant-card p-10 text-center"><p class="text-lg font-bold text-muted">Tidak ada produk ditemukan.</p></div>
      <?php else: foreach($products as $p):?>
      <article class="catalog-card product-card">
        <div class="product-card-header"><span><?=e($p['kode_produk'])?></span><span><?=e($p['nama_kategori'])?></span></div>
        <div class="product-image-frame h-52 border-x-0 border-t-0 shadow-none"><img class="product-image" src="<?=e(productImageSrc($p['image_url']))?>" alt="<?=e($p['nama_produk'])?>"></div>
        <div class="p-5">
          <div class="mb-3 flex items-center justify-between"><span class="status-badge <?=statusBadgeClass($p['status'])?>"><?=statusLabel($p['status'])?></span><span class="serial-tag">stok: <?=str_pad((string)$p['stok'],2,'0',STR_PAD_LEFT)?></span></div>
          <h3 class="font-display text-2xl font-black text-navy leading-none"><?=e($p['nama_produk'])?></h3>
          <p class="mt-2 text-sm text-muted line-clamp-2"><?=e($p['deskripsi_produk'])?></p>
          <div class="mt-4 flex items-end justify-between border-t-2 border-ink pt-3">
            <p class="text-lg font-black text-brick"><?=formatRupiah($p['harga_per_hari'])?><span class="text-xs text-muted">/hari</span></p>
            <button type="button" class="btn-primary px-3 text-xs" onclick="openRentalModal(<?=e((string)$p['id'])?>, '<?=e($p['nama_produk'])?>', <?=e((string)$p['harga_per_hari'])?>, <?=e((string)$p['stok'])?>)">Sewa</button>
          </div>
        </div>
      </article>
      <?php endforeach; endif;?>
    </div>
  </main>

  <!-- Modal Rental -->
  <div id="rentalModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="elegant-card w-full max-w-lg p-6 sm:p-8">
      <div class="mb-6 flex items-center justify-between"><h2 class="font-display text-2xl font-bold text-ink">Form Pemesanan</h2><button type="button" onclick="closeRentalModal()" class="icon-btn"><i data-lucide="x" class="h-5 w-5"></i></button></div>
      <form action="order-store.php" method="POST" class="grid gap-4">
        <?=csrfField()?>
        <input type="hidden" name="product_id" id="modal_product_id">
        <div><label class="mb-1 block text-sm font-bold text-slate-700">Produk</label><input type="text" id="modal_product_name" class="input-elegant" readonly></div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div><label for="modal_tgl_mulai" class="mb-1 block text-sm font-bold text-slate-700">Tanggal Mulai</label><input type="date" name="tanggal_mulai" id="modal_tgl_mulai" class="input-elegant" required></div>
          <div><label for="modal_tgl_selesai" class="mb-1 block text-sm font-bold text-slate-700">Tanggal Selesai</label><input type="date" name="tanggal_selesai" id="modal_tgl_selesai" class="input-elegant" required></div>
        </div>
        <div><label for="modal_jumlah" class="mb-1 block text-sm font-bold text-slate-700">Jumlah Unit</label><input type="number" name="jumlah_unit" id="modal_jumlah" min="1" value="1" class="input-elegant" required></div>
        <div><label for="modal_catatan" class="mb-1 block text-sm font-bold text-slate-700">Catatan (opsional)</label><textarea name="catatan" id="modal_catatan" class="textarea-elegant" rows="2"></textarea></div>
        <div class="soft-panel p-4"><p class="text-sm font-bold text-muted">Estimasi: <span id="modal_estimasi" class="text-ink">Rp 0</span></p></div>
        <button type="submit" class="btn-primary w-full px-5"><i data-lucide="check-circle" class="h-5 w-5"></i>Konfirmasi Pesanan</button>
      </form>
    </div>
  </div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="../assets/js/main.js"></script>
  <script>
  let currentPrice=0,currentStock=0;
  function openRentalModal(id,name,price,stock){currentPrice=price;currentStock=stock;document.getElementById('modal_product_id').value=id;document.getElementById('modal_product_name').value=name;document.getElementById('modal_jumlah').max=stock;document.getElementById('rentalModal').classList.remove('hidden');document.getElementById('rentalModal').classList.add('flex');calcEstimasi();}
  function closeRentalModal(){document.getElementById('rentalModal').classList.add('hidden');document.getElementById('rentalModal').classList.remove('flex');}
  function calcEstimasi(){const s=document.getElementById('modal_tgl_mulai').value,e=document.getElementById('modal_tgl_selesai').value,q=parseInt(document.getElementById('modal_jumlah').value)||1;if(s&&e){const d=Math.max(1,Math.ceil((new Date(e)-new Date(s))/(86400000)));document.getElementById('modal_estimasi').textContent='Rp '+new Intl.NumberFormat('id-ID').format(d*q*currentPrice)+' ('+d+' hari)';}else{document.getElementById('modal_estimasi').textContent='Rp 0';}}
  document.getElementById('modal_tgl_mulai')?.addEventListener('change',calcEstimasi);
  document.getElementById('modal_tgl_selesai')?.addEventListener('change',calcEstimasi);
  document.getElementById('modal_jumlah')?.addEventListener('input',calcEstimasi);
  document.getElementById('rentalModal')?.addEventListener('click',function(ev){if(ev.target===this)closeRentalModal();});
  </script>
</body>
</html>
