<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/email_verification.php';

if (!isLoggedIn()) {
    checkRememberCookie();
}

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$db = getDB();
$pendingUserId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);
$pendingUser = $pendingUserId > 0 ? getPendingVerificationUser($db, $pendingUserId) : null;

if (!$pendingUser) {
    clearPendingVerificationUser();
    setFlash('login', 'Silakan login atau registrasi untuk melanjutkan verifikasi email.', 'danger');
    redirect('login.php');
}

rememberPendingVerificationUser($pendingUser);

$verification = latestOpenEmailVerification($db, (int) $pendingUser['id']);
$resendWait = secondsUntilVerificationResend($verification);
$flash = getFlash('verify_email');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Email | ElektraRent</title>
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
  <main class="grid min-h-screen lg:grid-cols-[0.9fr_1.1fr]">
    <section class="relative hidden overflow-hidden bg-slate-900 text-white lg:block">
      <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1400&q=85" alt="Sirkuit elektronik modern" class="h-full w-full object-cover opacity-70">
      <div class="absolute inset-0 bg-slate-950/55"></div>
      <div class="absolute inset-x-0 bottom-0 p-12">
        <a href="index.php" class="mb-8 flex items-center gap-3">
          <span class="brand-mark"><i data-lucide="zap" class="h-5 w-5"></i></span>
          <span class="font-display text-3xl font-bold">ElektraRent</span>
        </a>
        <p class="text-sm font-black uppercase tracking-wide text-amber-300">Email Verification</p>
        <h1 class="font-display mt-3 max-w-xl text-5xl font-bold leading-tight">Aktifkan akses rental elektronik Anda.</h1>
        <p class="mt-4 max-w-lg leading-7 text-white/84">Kode verifikasi menjaga akun tetap aman sebelum dashboard dan pemesanan dapat digunakan.</p>
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
            <p class="text-sm font-black uppercase tracking-wide text-accent">Verifikasi Email</p>
            <h2 class="font-display mt-2 text-4xl font-bold text-ink">Masukkan kode OTP.</h2>
            <p class="mt-2 text-sm leading-6 text-muted">
              Kode 6 digit dikirim ke <span class="font-black text-ink"><?= e($pendingUser['email']) ?></span> dan berlaku selama 10 menit.
            </p>
          </div>

          <?php if ($flash): ?>
          <div class="mb-5 rounded-lg border p-4 text-sm font-semibold <?= $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' ?>" role="alert">
            <?= e($flash['message']) ?>
          </div>
          <?php endif; ?>

          <form id="verify_email_form" action="auth/verify-email.php" method="POST" class="space-y-5">
            <?= csrfField() ?>
            <div>
              <label for="verification_code" class="mb-2 block text-sm font-bold text-slate-700">Kode Verifikasi</label>
              <input
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                name="verification_code"
                id="verification_code"
                placeholder="123456"
                class="input-elegant text-center font-mono text-2xl font-black tracking-[0.35em]"
                autocomplete="one-time-code"
                required
              >
            </div>
            <button id="verify_email_submit_button" type="submit" class="btn-primary w-full px-5">
              <i data-lucide="badge-check" class="h-5 w-5"></i>
              Verifikasi Email
            </button>
          </form>

          <div class="mt-6 border-t border-slate-200 pt-5">
            <form id="resend_verification_form" action="auth/resend-verification.php" method="POST">
              <?= csrfField() ?>
              <button id="resend_verification_button" type="submit" class="btn-secondary w-full px-5" <?= $resendWait > 0 ? 'disabled' : '' ?>>
                <i data-lucide="send" class="h-4 w-4"></i>
                <?= $resendWait > 0 ? 'Kirim ulang dalam ' . e((string) $resendWait) . ' detik' : 'Kirim Ulang Kode' ?>
              </button>
            </form>
            <p class="mt-3 text-center text-xs font-semibold leading-5 text-muted">
              Kode baru hanya dapat diminta setiap 60 detik.
            </p>
          </div>
        </div>

        <p class="mt-6 text-center text-sm text-muted">
          Sudah aktif? <a href="login.php" class="font-bold text-primary">Masuk ke akun</a>
        </p>
      </div>
    </section>
  </main>

  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
