<?php
require_once __DIR__ . '/includes/helpers.php';

if (!isLoggedIn()) {
    checkRememberCookie();
}

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$loginFlash = getFlash('login');
$authFlash  = getFlash('auth');
$verifyFlash = getFlash('verify_email');
$flash = $loginFlash ?? $authFlash ?? $verifyFlash;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | ElektraRent</title>
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
  <main class="grid min-h-screen lg:grid-cols-[0.95fr_1.05fr]">
    <section class="relative hidden overflow-hidden lg:block">
      <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1400&q=85" alt="Workspace elektronik modern" class="h-full w-full object-cover">
      <div class="absolute inset-0 bg-slate-950/45"></div>
      <div class="absolute inset-x-0 bottom-0 p-12 text-white">
        <a href="index.php" class="mb-8 flex items-center gap-3">
          <span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span>
          <span class="font-display text-3xl font-bold">ElektraRent</span>
        </a>
        <h1 class="font-display max-w-xl text-5xl font-bold leading-tight">Masuk ke ruang kendali rental elektronik.</h1>
        <p class="mt-4 max-w-lg leading-7 text-white/84">Pantau pesanan, cek tagihan, kelola produk, dan lanjutkan transaksi yang sedang berjalan.</p>
      </div>
    </section>

    <section class="flex items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
      <div class="w-full max-w-md">
        <a href="index.php" class="mb-8 inline-flex items-center gap-3 lg:hidden">
          <span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span>
          <span class="font-display text-3xl font-bold text-ink">ElektraRent</span>
        </a>

        <div class="elegant-card p-6 sm:p-8">
          <div class="mb-8">
            <p class="text-sm font-black uppercase tracking-wide text-accent">Login Pengguna</p>
            <h2 class="font-display mt-2 text-4xl font-bold text-ink">Selamat datang kembali.</h2>
            <p class="mt-2 text-sm leading-6 text-muted">Masukkan email dan password akun ElektraRent Anda.</p>
          </div>

          <?php if ($flash): ?>
          <div class="mb-5 rounded-lg border p-4 text-sm font-semibold <?= $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' ?>" role="alert">
            <?= e($flash['message']) ?>
          </div>
          <?php endif; ?>

          <form id="login_form" action="auth/login.php" method="POST" class="space-y-5">
            <?= csrfField() ?>
            <div>
              <label for="email" class="mb-2 block text-sm font-bold text-slate-700">Email</label>
              <input type="email" name="email" id="email" placeholder="Masukkan email terdaftar" class="input-elegant" required>
            </div>
            <div>
              <label for="password" class="mb-2 block text-sm font-bold text-slate-700">Password</label>
              <input type="password" name="password" id="password" placeholder="Masukkan password akun" class="input-elegant" required>
            </div>
            <div class="flex items-center justify-between gap-4">
              <label for="remember_me" class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                <input type="checkbox" name="remember_me" id="remember_me" class="h-4 w-4 rounded border-slate-300 text-primary">
                Ingat Saya
              </label>
              <a href="register.php" class="text-sm font-bold text-primary hover:text-primary-dark">Belum punya akun?</a>
            </div>
            <button id="login_submit_button" type="submit" class="btn-primary w-full px-5">
              <i data-lucide="log-in" class="h-5 w-5"></i>
              Login
            </button>
          </form>
        </div>

        <p class="mt-6 text-center text-sm text-muted">
          Kembali ke <a href="index.php" class="font-bold text-primary">beranda</a>
        </p>
      </div>
    </section>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
