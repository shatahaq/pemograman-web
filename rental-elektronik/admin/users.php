<?php
define('BASE_PATH', '..');
require_once __DIR__ . '/../includes/helpers.php';
requireAdmin();

$db = getDB();

// Handle POST toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_status' && $userId) {
        $current = $db->prepare("SELECT status_akun, email_verified_at FROM users WHERE id=? AND role='user'");
        $current->execute([$userId]);
        $row = $current->fetch();
        if ($row) {
            $newStatus = $row['status_akun'] === 'aktif' ? 'nonaktif' : 'aktif';

            if ($newStatus === 'aktif') {
                $db->prepare("
                    UPDATE users
                    SET status_akun = ?,
                        email_verified_at = COALESCE(email_verified_at, NOW()),
                        updated_at = NOW()
                    WHERE id = ?
                ")->execute([$newStatus, $userId]);
            } else {
                $db->prepare("UPDATE users SET status_akun=?,updated_at=NOW() WHERE id=?")->execute([$newStatus, $userId]);
            }

            auditLog('toggle_user_status', 'users', $userId, ['status' => $newStatus]);
            setFlash('admin_users', "Status user berhasil diubah ke $newStatus.", 'success');
        }
        redirect('users.php');
    }
}

$users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM rental_orders WHERE user_id=u.id) AS total_orders FROM users u WHERE u.role='user' ORDER BY u.created_at DESC")->fetchAll();
$flash = getFlash('admin_users');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Users | ElektraRent Admin</title>
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
        <a href="orders.php" class="font-bold text-slate-600 hover:text-primary">Pesanan</a>
        <a href="users.php" class="font-bold text-primary">Users</a>
        <a href="../auth/logout.php" class="btn-secondary px-4"><i data-lucide="log-out" class="h-4 w-4"></i>Logout</a>
      </div>
    </nav>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8"><p class="text-sm font-black uppercase tracking-wide text-accent">Admin Panel</p><h1 class="font-display mt-2 text-4xl font-bold text-ink">Kelola Users</h1></div>

    <?php if($flash):?><div class="mb-6 rounded-lg border p-4 text-sm font-semibold <?=$flash['type']==='success'?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'?>"><?=e($flash['message'])?></div><?php endif;?>

    <div class="elegant-card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Nama</th><th>Email</th><th>No. Telepon</th><th>Total Order</th><th>Status</th><th>Terdaftar</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($users as $u):?><tr>
      <td class="font-bold"><?=e($u['nama_lengkap'])?></td>
      <td><?=e($u['email'])?></td>
      <td><?=e($u['no_telepon'])?></td>
      <td><?=e((string)$u['total_orders'])?></td>
      <td><span class="status-badge <?=statusBadgeClass($u['status_akun'])?>"><?=statusLabel($u['status_akun'])?></span></td>
      <td><?=formatTanggal($u['created_at'])?></td>
      <td>
        <form method="POST" onsubmit="return confirm('Ubah status user ini?')">
          <?=csrfField()?><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="user_id" value="<?=$u['id']?>">
          <button type="submit" class="btn-ghost px-3 text-xs"><?=$u['status_akun']==='aktif'?'Nonaktifkan':'Aktifkan'?></button>
        </form>
      </td>
    </tr><?php endforeach;?></tbody></table></div></div>
  </main>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="../assets/js/main.js"></script>
</body>
</html>
