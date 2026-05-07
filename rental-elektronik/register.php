<?php
require_once __DIR__ . '/includes/helpers.php';

if (!isLoggedIn()) {
    checkRememberCookie();
}

// Jika sudah login, redirect
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$flash = getFlash('register');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrasi | ElektraRent</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "var(--color-primary)",
            accent: "var(--color-accent)",
            ink: "var(--color-ink)",
            muted: "var(--color-muted)",
            paper: "var(--color-paper)"
          }
        }
      }
    };
  </script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="min-h-screen">
  <header class="nav-glass">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="index.php" class="flex items-center gap-3">
        <span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span>
        <span class="font-display text-2xl font-bold text-ink">ElektraRent</span>
      </a>
      <a href="login.php" class="btn-secondary px-4">
        <i data-lucide="log-in" class="h-4 w-4"></i>
        Login
      </a>
    </nav>
  </header>

  <main class="py-10 sm:py-14">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
      <section class="flex flex-col justify-between rounded-lg bg-slate-900 text-white">
        <div class="p-7 sm:p-10">
          <p class="text-sm font-black uppercase tracking-wide text-amber-300">Akun Baru</p>
          <h1 class="font-display mt-3 text-4xl font-bold leading-tight sm:text-5xl">Bangun riwayat rental yang tertata sejak pesanan pertama.</h1>
          <p class="mt-5 max-w-lg leading-7 text-white/78">Setelah registrasi, sistem akan mengirim kode verifikasi ke email Anda sebelum akses dashboard diaktifkan.</p>
        </div>
        <div class="image-frame m-5 aspect-[5/3] shadow-none sm:m-7">
          <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1200&q=85" alt="Perangkat elektronik klasik dan modern">
        </div>
      </section>

      <section class="elegant-card p-6 sm:p-8">
        <div class="mb-8">
          <p class="text-sm font-black uppercase tracking-wide text-accent">Registrasi Pengguna</p>
          <h2 class="font-display mt-2 text-4xl font-bold text-ink">Buat akun ElektraRent.</h2>
          <p class="mt-2 text-sm leading-6 text-muted">Gunakan email aktif karena kode verifikasi akan dikirim setelah formulir tersimpan.</p>
        </div>

        <?php if ($flash): ?>
        <div class="mb-5 rounded-lg border p-4 text-sm font-semibold <?= $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' ?>" role="alert">
          <?= e($flash['message']) ?>
        </div>
        <?php endif; ?>

        <form id="register_form" action="auth/register.php" method="POST" class="grid gap-5 md:grid-cols-2">
          <?= csrfField() ?>
          <div class="md:col-span-2">
            <label for="nama_lengkap" class="mb-2 block text-sm font-bold text-slate-700">Nama Lengkap</label>
            <input type="text" name="nama_lengkap" id="nama_lengkap" placeholder="Masukkan nama lengkap" class="input-elegant" required>
          </div>
          <div>
            <label for="email" class="mb-2 block text-sm font-bold text-slate-700">Email</label>
            <input type="email" name="email" id="email" placeholder="Masukkan alamat email aktif" class="input-elegant" required>
          </div>
          <div>
            <label for="no_telepon" class="mb-2 block text-sm font-bold text-slate-700">No. Telepon</label>
            <input type="tel" name="no_telepon" id="no_telepon" placeholder="Contoh: 081234567890" class="input-elegant" required>
          </div>
          <div class="md:col-span-2">
            <label for="alamat" class="mb-2 block text-sm font-bold text-slate-700">Alamat</label>
            <textarea name="alamat" id="alamat" placeholder="Masukkan alamat lengkap untuk verifikasi sewa" class="textarea-elegant" required></textarea>
          </div>
          <div>
            <label for="password" class="mb-2 block text-sm font-bold text-slate-700">Password</label>
            <input type="password" name="password" id="password" placeholder="Minimal 8 karakter" class="input-elegant" minlength="8" required>
          </div>
          <div>
            <label for="konfirmasi_password" class="mb-2 block text-sm font-bold text-slate-700">Konfirmasi Password</label>
            <input type="password" name="konfirmasi_password" id="konfirmasi_password" placeholder="Ulangi password" class="input-elegant" minlength="8" required>
          </div>
          <div class="md:col-span-2">
            <button id="register_submit_button" type="submit" class="btn-primary w-full px-5">
              <i data-lucide="user-plus" class="h-5 w-5"></i>
              Daftar Sekarang
            </button>
          </div>
        </form>
      </section>
    </div>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
