<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireAdmin();

$db = getDB();

// Upload directory
$uploadDir = __DIR__ . '/../assets/images/products/';
$uploadUrlBase = '../assets/images/products/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Ensure upload directory exists with security protections
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
// Prevent PHP execution in upload directory
$htaccessPath = $uploadDir . '.htaccess';
if (!file_exists($htaccessPath)) {
    file_put_contents($htaccessPath, "# Security: prevent script execution in upload dir\nphp_flag engine off\nAddHandler default-handler .php .phtml .php3 .php4 .php5 .php7 .phps .pht\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps|pht)$\">\n    Require all denied\n</FilesMatch>\n");
}

/**
 * Handle file upload, return filename atau null.
 */
function handleImageUpload(string $inputName, ?string $oldImage = null): ?string
{
    global $uploadDir, $allowedTypes, $maxFileSize;

    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldImage; // Tidak ada file baru, pakai gambar lama
    }

    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('products', 'Gagal upload gambar. Error code: ' . e((string)$file['error']), 'danger');
        return $oldImage;
    }

    if ($file['size'] > $maxFileSize) {
        setFlash('products', 'Ukuran gambar terlalu besar. Maksimal 5MB.', 'danger');
        return $oldImage;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes, true)) {
        setFlash('products', 'Format gambar tidak didukung. Gunakan JPG, PNG, WebP, atau GIF.', 'danger');
        return $oldImage;
    }

    // Generate unique filename (no user-controlled parts)
    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
    $filename = 'product_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadDir . sanitizeFilename($filename);

    // Verify destination is within the upload directory (path traversal protection)
    $realUploadDir = realpath($uploadDir);
    if ($realUploadDir === false) {
        setFlash('products', 'Direktori upload tidak ditemukan.', 'danger');
        return $oldImage;
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        setFlash('products', 'Gagal menyimpan gambar ke server.', 'danger');
        return $oldImage;
    }

    // Verify the saved file is actually within the upload directory
    $realDest = realpath($destination);
    if ($realDest === false || !str_starts_with($realDest, $realUploadDir)) {
        @unlink($destination);
        setFlash('products', 'Path upload tidak valid.', 'danger');
        return $oldImage;
    }

    // Hapus gambar lama jika ada dan file lokal
    if ($oldImage && !str_starts_with($oldImage, 'http')) {
        $oldPath = $uploadDir . sanitizeFilename($oldImage);
        $realOldPath = realpath($oldPath);
        if ($realOldPath && str_starts_with($realOldPath, $realUploadDir) && file_exists($realOldPath)) {
            @unlink($realOldPath);
        }
    }

    return $filename;
}

// Handle POST actions (add/edit/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $kategoriId = (int)($_POST['kategori_id'] ?? 0);
        $nama = trim($_POST['nama_produk'] ?? '');
        $deskripsi = trim($_POST['deskripsi_produk'] ?? '');
        $harga = (float)($_POST['harga_per_hari'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);

        if (!$kategoriId || empty($nama) || $harga <= 0) {
            setFlash('products', 'Data produk tidak lengkap.', 'danger');
            redirect('products.php');
        }

        $imageFile = handleImageUpload('gambar_produk');

        $catSlug = $db->prepare("SELECT slug FROM categories WHERE id=?");
        $catSlug->execute([$kategoriId]);
        $slug = $catSlug->fetchColumn() ?: 'general';
        $kode = generateProductCode($slug);

        $stmt = $db->prepare("INSERT INTO products (kode_produk,kategori_id,nama_produk,deskripsi_produk,harga_per_hari,stok,status,image_url,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        $status = $stok > 0 ? 'tersedia' : 'habis';
        $stmt->execute([$kode, $kategoriId, $nama, $deskripsi, $harga, $stok, $status, $imageFile]);
        $newId = (int)$db->lastInsertId();

        $db->prepare("INSERT INTO stock_movements (product_id,tipe,jumlah,keterangan,created_at) VALUES (?,'masuk',?,?,NOW())")->execute([$newId, $stok, "Stok awal $kode"]);
        auditLog('add_product', 'products', $newId, ['kode' => $kode]);
        setFlash('products', "Produk $kode berhasil ditambahkan.", 'success');
        redirect('products.php');
    }

    if ($action === 'edit') {
        $id = (int)($_POST['product_id'] ?? 0);
        $nama = trim($_POST['nama_produk'] ?? '');
        $deskripsi = trim($_POST['deskripsi_produk'] ?? '');
        $harga = (float)($_POST['harga_per_hari'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);
        $statusProduk = $_POST['status_produk'] ?? 'tersedia';
        // Whitelist validation for status
        if (!in_array($statusProduk, ['tersedia', 'habis', 'maintenance'], true)) {
            $statusProduk = 'tersedia';
        }

        // Ambil gambar lama
        $stmtOld = $db->prepare("SELECT image_url FROM products WHERE id=?");
        $stmtOld->execute([$id]);
        $oldImage = $stmtOld->fetchColumn();

        $imageFile = handleImageUpload('gambar_produk', $oldImage);

        $stmt = $db->prepare("UPDATE products SET nama_produk=?,deskripsi_produk=?,harga_per_hari=?,stok=?,status=?,image_url=?,updated_at=NOW() WHERE id=?");
        $stmt->execute([$nama, $deskripsi, $harga, $stok, $statusProduk, $imageFile, $id]);
        auditLog('edit_product', 'products', $id);
        setFlash('products', "Produk berhasil diperbarui.", 'success');
        redirect('products.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['product_id'] ?? 0);
        $check = $db->prepare("SELECT COUNT(*) FROM rental_order_items WHERE product_id=?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            setFlash('products', 'Produk tidak bisa dihapus karena terkait pesanan.', 'danger');
        } else {
            // Hapus gambar jika lokal
            $stmtImg = $db->prepare("SELECT image_url FROM products WHERE id=?");
            $stmtImg->execute([$id]);
            $img = $stmtImg->fetchColumn();
            if ($img && !str_starts_with($img, 'http')) {
                $delPath = $uploadDir . sanitizeFilename($img);
                $realDel = realpath($delPath);
                $realBase = realpath($uploadDir);
                if ($realDel && $realBase && str_starts_with($realDel, $realBase) && file_exists($realDel)) {
                    @unlink($realDel);
                }
            }
            $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
            auditLog('delete_product', 'products', $id);
            setFlash('products', 'Produk berhasil dihapus.', 'success');
        }
        redirect('products.php');
    }
}

$products = $db->query("SELECT p.*,c.nama_kategori FROM products p JOIN categories c ON p.kategori_id=c.id ORDER BY p.created_at DESC")->fetchAll();
$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori")->fetchAll();
$flash = getFlash('products');
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Produk | ElektraRent Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{primary:"var(--color-primary)",accent:"var(--color-accent)",ink:"var(--color-ink)",muted:"var(--color-muted)"}}}};</script>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .upload-area{position:relative;border:2px dashed var(--color-muted);border-radius:.75rem;padding:1.5rem;text-align:center;cursor:pointer;transition:all .2s}
    .upload-area:hover,.upload-area.dragover{border-color:var(--color-primary);background:rgba(46,79,79,.04)}
    .upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer}
    .preview-img{max-height:120px;border-radius:.5rem;margin-top:.75rem;border:2px solid var(--color-muted)}
  </style>
</head>
<body class="min-h-screen">
  <header class="nav-glass sticky top-0 z-40">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="../index.php" class="flex items-center gap-3"><span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span><span class="font-display text-2xl font-bold text-ink">ElektraRent</span><span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Admin</span></a>
      <div class="hidden items-center gap-4 md:flex">
        <a href="dashboard.php" class="font-bold text-slate-600 hover:text-primary">Dashboard</a>
        <a href="products.php" class="font-bold text-primary">Produk</a>
        <a href="orders.php" class="font-bold text-slate-600 hover:text-primary">Pesanan</a>
        <a href="users.php" class="font-bold text-slate-600 hover:text-primary">Users</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </nav>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-center justify-between"><div><p class="text-sm font-black uppercase tracking-wide text-accent">Admin Panel</p><h1 class="font-display mt-2 text-4xl font-bold text-ink">Kelola Produk</h1></div><button type="button" onclick="document.getElementById('addModal').classList.remove('hidden');document.getElementById('addModal').classList.add('flex')" class="btn-primary px-5"><i data-lucide="plus" class="h-5 w-5"></i>Tambah Produk</button></div>

    <?php if($flash):?><div class="mb-6 rounded-lg border p-4 text-sm font-semibold <?=$flash['type']==='success'?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'?>"><?=e($flash['message'])?></div><?php endif;?>

    <div class="elegant-card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Gambar</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Harga/Hari</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($products as $p):?><tr>
      <td><img src="<?=e(productImageSrc($p['image_url']))?>" alt="<?=e($p['nama_produk'])?>" class="h-12 w-16 rounded object-cover border border-slate-200"></td>
      <td class="font-bold"><?=e($p['kode_produk'])?></td>
      <td><?=e($p['nama_produk'])?></td>
      <td><?=e($p['nama_kategori'])?></td>
      <td><?=formatRupiah($p['harga_per_hari'])?></td>
      <td><?=e((string)$p['stok'])?></td>
      <td><span class="status-badge <?=statusBadgeClass($p['status'])?>"><?=statusLabel($p['status'])?></span></td>
      <td class="flex gap-2">
        <button type="button" class="btn-ghost px-2 text-xs" onclick='openEditModal(<?=htmlspecialchars(json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8')?>)'>Edit</button>
        <form method="POST" onsubmit="return confirm('Hapus produk ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?=$p['id']?>"><?=csrfField()?><button type="submit" class="btn-ghost px-2 text-xs text-red-600">Hapus</button></form>
      </td>
    </tr><?php endforeach;?></tbody></table></div></div>
  </main>

  <!-- Add Modal -->
  <div id="addModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="elegant-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
      <div class="mb-6 flex items-center justify-between"><h2 class="font-display text-2xl font-bold text-ink">Tambah Produk</h2><button type="button" onclick="document.getElementById('addModal').classList.add('hidden');document.getElementById('addModal').classList.remove('flex')" class="icon-btn"><i data-lucide="x" class="h-5 w-5"></i></button></div>
      <form method="POST" enctype="multipart/form-data" class="grid gap-4">
        <?=csrfField()?><input type="hidden" name="action" value="add">
        <div><label class="mb-1 block text-sm font-bold">Kategori</label><select name="kategori_id" class="input-elegant" required><?php foreach($categories as $c):?><option value="<?=$c['id']?>"><?=e($c['nama_kategori'])?></option><?php endforeach;?></select></div>
        <div><label class="mb-1 block text-sm font-bold">Nama Produk</label><input type="text" name="nama_produk" class="input-elegant" required></div>
        <div><label class="mb-1 block text-sm font-bold">Deskripsi</label><textarea name="deskripsi_produk" class="textarea-elegant" rows="2"></textarea></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-bold">Harga/Hari (Rp)</label><input type="number" name="harga_per_hari" class="input-elegant" min="0" required></div><div><label class="mb-1 block text-sm font-bold">Stok</label><input type="number" name="stok" class="input-elegant" min="0" required></div></div>
        <div>
          <label class="mb-1 block text-sm font-bold">Gambar Produk</label>
          <div class="upload-area" id="addUploadArea">
            <input type="file" name="gambar_produk" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this,'addPreview')">
            <i data-lucide="image-plus" class="mx-auto h-8 w-8 text-muted"></i>
            <p class="mt-2 text-sm font-bold text-muted">Klik atau seret gambar ke sini</p>
            <p class="mt-1 text-xs text-slate-400">JPG, PNG, WebP, GIF — Maks 5MB</p>
            <img id="addPreview" class="preview-img mx-auto hidden" alt="Preview">
          </div>
        </div>
        <button type="submit" class="btn-primary w-full">Simpan</button>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="elegant-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
      <div class="mb-6 flex items-center justify-between"><h2 class="font-display text-2xl font-bold text-ink">Edit Produk</h2><button type="button" onclick="document.getElementById('editModal').classList.add('hidden');document.getElementById('editModal').classList.remove('flex')" class="icon-btn"><i data-lucide="x" class="h-5 w-5"></i></button></div>
      <form method="POST" enctype="multipart/form-data" class="grid gap-4">
        <?=csrfField()?><input type="hidden" name="action" value="edit"><input type="hidden" name="product_id" id="edit_id">
        <div><label class="mb-1 block text-sm font-bold">Nama Produk</label><input type="text" name="nama_produk" id="edit_nama" class="input-elegant" required></div>
        <div><label class="mb-1 block text-sm font-bold">Deskripsi</label><textarea name="deskripsi_produk" id="edit_deskripsi" class="textarea-elegant" rows="2"></textarea></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-bold">Harga/Hari (Rp)</label><input type="number" name="harga_per_hari" id="edit_harga" class="input-elegant" min="0" required></div><div><label class="mb-1 block text-sm font-bold">Stok</label><input type="number" name="stok" id="edit_stok" class="input-elegant" min="0" required></div></div>
        <div><label class="mb-1 block text-sm font-bold">Status</label><select name="status_produk" id="edit_status" class="input-elegant"><option value="tersedia">Tersedia</option><option value="habis">Habis</option><option value="maintenance">Maintenance</option></select></div>
        <div>
          <label class="mb-1 block text-sm font-bold">Gambar Produk</label>
          <div class="upload-area" id="editUploadArea">
            <input type="file" name="gambar_produk" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this,'editPreview')">
            <i data-lucide="image-plus" class="mx-auto h-8 w-8 text-muted"></i>
            <p class="mt-2 text-sm font-bold text-muted">Klik atau seret gambar baru</p>
            <p class="mt-1 text-xs text-slate-400">Kosongkan jika tidak ingin ganti gambar</p>
            <img id="editPreview" class="preview-img mx-auto hidden" alt="Preview">
          </div>
          <p id="editCurrentImage" class="mt-2 text-xs text-slate-500"></p>
        </div>
        <button type="submit" class="btn-primary w-full">Simpan Perubahan</button>
      </form>
    </div>
  </div>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="../assets/js/main.js"></script>
  <script>
  // Preview gambar sebelum upload
  function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Drag & drop support
  document.querySelectorAll('.upload-area').forEach(function(area) {
    area.addEventListener('dragover', function(e) {
      e.preventDefault();
      area.classList.add('dragover');
    });
    area.addEventListener('dragleave', function() {
      area.classList.remove('dragover');
    });
    area.addEventListener('drop', function(e) {
      e.preventDefault();
      area.classList.remove('dragover');
      const fileInput = area.querySelector('input[type=file]');
      if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
      }
    });
  });

  // Open edit modal
  function openEditModal(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_nama').value = p.nama_produk;
    document.getElementById('edit_deskripsi').value = p.deskripsi_produk || '';
    document.getElementById('edit_harga').value = p.harga_per_hari;
    document.getElementById('edit_stok').value = p.stok;
    document.getElementById('edit_status').value = p.status;

    // Show current image info
    const currentImgEl = document.getElementById('editCurrentImage');
    const editPreview = document.getElementById('editPreview');

    if (p.image_url) {
      let src = p.image_url;
      if (!src.startsWith('http')) {
        src = '../assets/images/products/' + src;
      }
      editPreview.src = src;
      editPreview.classList.remove('hidden');
      currentImgEl.textContent = 'Gambar saat ini: ' + p.image_url;
    } else {
      editPreview.classList.add('hidden');
      editPreview.src = '';
      currentImgEl.textContent = 'Belum ada gambar';
    }

    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
  }

  // Modal close on backdrop click
  document.getElementById('addModal')?.addEventListener('click', function(e) {
    if (e.target === this) { this.classList.add('hidden'); this.classList.remove('flex'); }
  });
  document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) { this.classList.add('hidden'); this.classList.remove('flex'); }
  });
  </script>
</body>
</html>
