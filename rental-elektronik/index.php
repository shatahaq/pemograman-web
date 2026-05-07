<?php
require_once __DIR__ . '/includes/helpers.php';

$db = getDB();

// Ambil produk unggulan (tersedia, 3 terbaru)
$stmtFeatured = $db->query("
    SELECT p.*, c.nama_kategori, c.slug AS kategori_slug
    FROM products p
    JOIN categories c ON p.kategori_id = c.id
    WHERE p.status = 'tersedia'
    ORDER BY p.created_at DESC
    LIMIT 3
");
$featuredProducts = $stmtFeatured->fetchAll();

// Statistik
$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE status='tersedia'")->fetchColumn();
$totalCategories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM rental_orders")->fetchColumn();

// Flash newsletter
$newsletterFlash = getFlash('newsletter');

// Kartu warna untuk produk
$cardColors = ['', 'neo-card-ochre', 'neo-card-brick'];
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="ElektraRent adalah sistem rental barang elektronik dengan estetika vintage modern technology untuk kamera, laptop, drone, audio, dan perangkat produksi." />
        <title>ElektraRent | Vintage Modern Electronic Rental</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            paper: "var(--color-paper)",
                            cream: "var(--color-cream)",
                            ink: "var(--color-ink)",
                            muted: "var(--color-muted)",
                            navy: "var(--color-navy)",
                            brick: "var(--color-brick)",
                            ochre: "var(--color-ochre)",
                        },
                        fontFamily: {
                            display: ["var(--font-display)"],
                            mono: ["var(--font-mono)"],
                        },
                    },
                },
            };
        </script>
        <link rel="stylesheet" href="assets/css/style.css" />
    </head>
    <body class="site-shell min-h-screen text-ink">
        <header class="site-header sticky top-0 z-50">
            <nav class="editorial-container flex items-center justify-between gap-5 py-4" aria-label="Navigasi utama ElektraRent">
                <a href="index.php" class="flex items-center gap-4">
                    <span class="brand-mark" aria-hidden="true"><i data-lucide="zap" class="h-6 w-6"></i></span>
                    <span class="leading-none">
                        <span class="font-display block text-3xl font-black tracking-[-0.04em] text-navy">ElektraRent</span>
                        <span class="mt-1 block text-[0.66rem] font-black uppercase tracking-[0.22em] text-brick">Electronic rental archive</span>
                    </span>
                </a>

                <button id="index_mobile_nav_toggle_button" type="button" class="icon-button md:hidden" data-nav-toggle data-target="#indexMobileMenu" aria-label="Buka navigasi utama" aria-expanded="false">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>

                <div class="hidden items-center gap-8 md:flex">
                    <a href="#koleksi" class="nav-link">Koleksi</a>
                    <a href="#alur" class="nav-link">Alur</a>
                    <a href="#arsip" class="nav-link">Arsip</a>
                    <a href="#newsletter" class="nav-link">Newsletter</a>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php" class="btn-ghost px-4">Dashboard</a>
                        <?php else: ?>
                            <a href="user/dashboard.php" class="btn-ghost px-4">Dashboard</a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="btn-primary px-5">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-ghost px-4">Login</a>
                        <a href="register.php" class="btn-primary px-5">Daftar</a>
                    <?php endif; ?>
                </div>
            </nav>

            <div id="indexMobileMenu" class="mobile-menu-panel hidden md:hidden">
                <div class="editorial-container grid gap-3 py-4">
                    <a href="#koleksi" class="nav-link w-fit">Koleksi</a>
                    <a href="#alur" class="nav-link w-fit">Alur</a>
                    <a href="#arsip" class="nav-link w-fit">Arsip</a>
                    <a href="#newsletter" class="nav-link w-fit">Newsletter</a>
                    <div class="grid gap-3 pt-3 sm:grid-cols-2">
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php' ?>" class="btn-ghost px-4">Dashboard</a>
                            <a href="auth/logout.php" class="btn-primary px-4">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-ghost px-4">Login</a>
                            <a href="register.php" class="btn-primary px-4">Daftar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section class="editorial-container py-2 sm:py-3 lg:py-4">
                <div class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr] lg:items-stretch">
                    <div class="hero-professional-panel flex flex-col justify-between p-3 sm:p-4 lg:p-5">
                        <div class="relative z-10">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="hero-issue">
                                    <i data-lucide="satellite" class="h-4 w-4"></i>
                                    ElektraRent System
                                </span>
                                <span class="serial-tag">Inventory cloud ready</span>
                            </div>
                            <hr class="editorial-rule my-3" />
                            <p class="eyebrow mb-3">Vintage ocean technology</p>
                            <h1 class="hero-professional-title crt-glow text-navy">
                                Rental elektronik untuk kerja kreatif modern.
                            </h1>
                            <p class="hero-professional-copy mt-4">
                                ElektraRent membantu kamu menyewa kamera, laptop, drone, proyektor, dan perangkat produksi
                                dengan alur pemesanan yang jelas, stok transparan, serta pengalaman visual vintage yang tetap terasa modern.
                            </p>
                        </div>

                        <div class="relative z-10 mt-2 grid gap-2 xl:grid-cols-[1fr_1fr] xl:items-end">
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <a id="index_catalog_cta_button" href="user/catalog.php" class="btn-primary px-6">
                                    <i data-lucide="monitor-smartphone" class="h-5 w-5"></i>
                                    Lihat Katalog
                                </a>
                                <a id="index_login_cta_button" href="<?= isLoggedIn() ? (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php') : 'login.php' ?>" class="btn-secondary px-6">
                                    <i data-lucide="log-in" class="h-5 w-5"></i>
                                    <?= isLoggedIn() ? 'Dashboard' : 'Masuk Akun' ?>
                                </a>
                            </div>

                            <div class="hero-metrics-grid grid gap-3 sm:grid-cols-3">
                                <div class="hero-metric-card">
                                    <strong><?= e((string)$totalProducts) ?></strong>
                                    <span>Unit siap sewa</span>
                                </div>
                                <div class="hero-metric-card">
                                    <strong><?= e((string)$totalCategories) ?></strong>
                                    <span>Kategori device</span>
                                </div>
                                <div class="hero-metric-card">
                                    <strong>24J</strong>
                                    <span>Validasi order</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hero-visual-stage">
                        <span class="hero-radar-badge">
                            <i data-lucide="radar" class="h-4 w-4"></i>
                            Live device telemetry
                        </span>
                        <figure class="hero-visual-main">
                            <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1400&q=85" alt="Sirkuit elektronik modern bernuansa biru laut" />
                        </figure>
                        <div class="hero-floating-console">
                            <div class="hero-console-row">
                                <span>Catalog status</span>
                                <span class="hero-console-status">Online</span>
                            </div>
                            <div class="hero-console-row">
                                <span>Popular unit</span>
                                <span>Camera / Laptop</span>
                            </div>
                            <div class="hero-console-row">
                                <span>Rental mode</span>
                                <span>Daily booking</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section-band py-12" aria-label="Ringkasan layanan ElektraRent">
                <div class="editorial-container grid gap-5 md:grid-cols-4">
                    <div class="hardware-tile">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.14em] text-brick">Kategori</p>
                        <p class="font-display mt-3 text-5xl font-black leading-none text-navy"><?= str_pad((string)$totalCategories, 2, '0', STR_PAD_LEFT) ?></p>
                        <p class="mt-2 text-xs font-bold uppercase tracking-[0.08em] text-muted">kamera, laptop, audio, drone</p>
                    </div>
                    <div class="hardware-tile">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.14em] text-brick">Transaksi</p>
                        <p class="font-display mt-3 text-5xl font-black leading-none text-navy"><?= e((string)$totalOrders) ?></p>
                        <p class="mt-2 text-xs font-bold uppercase tracking-[0.08em] text-muted">total order tercatat</p>
                    </div>
                    <div class="hardware-tile">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.14em] text-brick">SLA</p>
                        <p class="font-display mt-3 text-5xl font-black leading-none text-navy">24J</p>
                        <p class="mt-2 text-xs font-bold uppercase tracking-[0.08em] text-muted">validasi order maksimal</p>
                    </div>
                    <div class="hardware-tile">
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.14em] text-brick">Status</p>
                        <p class="font-display mt-3 text-5xl font-black leading-none text-navy">Live</p>
                        <p class="mt-2 text-xs font-bold uppercase tracking-[0.08em] text-muted">sistem backend aktif</p>
                    </div>
                </div>
            </section>

            <section id="koleksi" class="editorial-container py-16 lg:py-20">
                <div class="mb-10 grid gap-6 border-b-[3px] border-ink pb-8 lg:grid-cols-[1fr_0.55fr] lg:items-end">
                    <div>
                        <p class="eyebrow">Koleksi Unggulan</p>
                        <h2 class="font-display mt-4 max-w-4xl text-5xl font-black leading-[0.95] tracking-[-0.06em] text-navy md:text-7xl">
                            Perangkat elektronik dengan tekstur klasik dan performa modern.
                        </h2>
                    </div>
                    <div class="lg:text-right">
                        <p class="text-sm font-semibold leading-7 text-muted">
                            Produk unggulan ditampilkan dari database berdasarkan ketersediaan dan tanggal terbaru.
                        </p>
                        <a href="user/catalog.php" class="btn-ghost mt-5 px-5">
                            Lihat semua unit
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </a>
                    </div>
                </div>

                <div class="grid gap-7 md:grid-cols-3">
                    <?php foreach ($featuredProducts as $i => $product): ?>
                    <article class="catalog-card product-card <?= $cardColors[$i] ?? '' ?>">
                        <div class="product-card-header">
                            <span><?= e($product['kode_produk']) ?></span>
                            <span><?= e($product['nama_kategori']) ?></span>
                        </div>
                        <div class="product-image-frame h-64 border-x-0 border-t-0 shadow-none">
                            <img class="product-image" src="<?= e(productImageSrc($product['image_url'])) ?>" alt="<?= e($product['nama_produk']) ?>" />
                        </div>
                        <div class="p-5">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <span class="status-badge <?= statusBadgeClass($product['status']) ?>"><?= statusLabel($product['status']) ?></span>
                                <span class="serial-tag">stok: <?= str_pad((string)$product['stok'], 2, '0', STR_PAD_LEFT) ?></span>
                            </div>
                            <h3 class="font-display text-3xl font-black leading-none tracking-[-0.04em] text-navy">
                                <?= e($product['nama_produk']) ?>
                            </h3>
                            <p class="mt-4 text-sm font-semibold leading-7 text-muted">
                                <?= e($product['deskripsi_produk']) ?>
                            </p>
                            <div class="mt-5 flex items-end justify-between gap-4 border-t-[3px] border-ink pt-4">
                                <p class="text-lg font-black text-brick">
                                    <?= formatRupiah($product['harga_per_hari']) ?><span class="text-xs text-muted"> / hari</span>
                                </p>
                                <a href="user/catalog.php" class="btn-ghost min-h-10 px-3 text-xs">Detail</a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="alur" class="section-band py-16 lg:py-20">
                <div class="editorial-container grid gap-8 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
                    <div class="paper-panel p-5 sm:p-7">
                        <p class="eyebrow">Alur Sewa</p>
                        <h2 class="font-display mt-4 text-5xl font-black leading-[0.95] tracking-[-0.06em] text-navy md:text-6xl">
                            Seperti mengisi kartu katalog perpustakaan hardware.
                        </h2>
                        <p class="mt-5 text-sm font-semibold leading-7 text-muted">
                            Pengguna memilih unit, mengirim form pemesanan, admin memverifikasi, lalu barang siap diambil atau dikirim sesuai kebijakan rental.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <article class="neo-card p-5">
                            <span class="brand-mark brand-mark--small"><i data-lucide="search" class="h-5 w-5"></i></span>
                            <p class="mt-6 text-[0.68rem] font-black uppercase tracking-[0.16em] text-brick">Step 01</p>
                            <h3 class="font-display mt-2 text-3xl font-black leading-none text-navy">Pilih Unit</h3>
                            <p class="mt-4 text-sm font-semibold leading-7 text-muted">Filter kategori, harga, status, dan stok pada katalog elektronik.</p>
                        </article>
                        <article class="neo-card neo-card-ochre p-5">
                            <span class="brand-mark brand-mark--small"><i data-lucide="calendar-check" class="h-5 w-5"></i></span>
                            <p class="mt-6 text-[0.68rem] font-black uppercase tracking-[0.16em] text-brick">Step 02</p>
                            <h3 class="font-display mt-2 text-3xl font-black leading-none text-navy">Kunci Jadwal</h3>
                            <p class="mt-4 text-sm font-semibold leading-7 text-muted">Isi tanggal mulai, tanggal selesai, dan jumlah unit di modal pemesanan.</p>
                        </article>
                        <article class="neo-card neo-card-brick p-5">
                            <span class="brand-mark brand-mark--small"><i data-lucide="package-check" class="h-5 w-5"></i></span>
                            <p class="mt-6 text-[0.68rem] font-black uppercase tracking-[0.16em] text-brick">Step 03</p>
                            <h3 class="font-display mt-2 text-3xl font-black leading-none text-navy">Ambil Barang</h3>
                            <p class="mt-4 text-sm font-semibold leading-7 text-muted">Admin menyiapkan unit, mencatat kondisi, dan memperbarui status order.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="arsip" class="editorial-container py-16 lg:py-20">
                <div class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-stretch">
                    <figure class="image-frame min-h-[420px]">
                        <img src="https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1200&q=85" alt="Perangkat keras dan workstation retro modern" />
                        <figcaption class="image-caption absolute bottom-0 left-0 right-0">
                            Inventory desk / tactile technology
                        </figcaption>
                    </figure>

                    <div class="grid gap-5">
                        <div>
                            <p class="eyebrow">Kenapa ElektraRent</p>
                            <h2 class="font-display mt-4 text-5xl font-black leading-[0.95] tracking-[-0.06em] text-navy md:text-6xl">
                                Backend terintegrasi, visualnya tetap editorial.
                            </h2>
                        </div>
                        <div class="grid gap-4">
                            <div class="paper-panel p-5">
                                <h3 class="text-sm font-black uppercase tracking-[0.12em] text-brick">Data dari Database</h3>
                                <p class="mt-2 text-sm font-semibold leading-7 text-muted">Semua produk, pesanan, dan pengguna dikelola melalui database MySQL dengan query PDO yang aman.</p>
                            </div>
                            <div class="paper-panel p-5">
                                <h3 class="text-sm font-black uppercase tracking-[0.12em] text-brick">Keamanan Terverifikasi</h3>
                                <p class="mt-2 text-sm font-semibold leading-7 text-muted">CSRF protection, password hashing, prepared statements, dan validasi server-side pada setiap form.</p>
                            </div>
                            <div class="paper-panel p-5">
                                <h3 class="text-sm font-black uppercase tracking-[0.12em] text-brick">Session PHP Aman</h3>
                                <p class="mt-2 text-sm font-semibold leading-7 text-muted">Autentikasi berbasis session PHP dengan regenerate ID dan proteksi halaman berdasarkan role.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="newsletter" class="section-band py-16">
                <div class="editorial-container">
                    <div class="paper-panel grid gap-8 p-6 sm:p-8 lg:grid-cols-[1fr_0.9fr] lg:items-center lg:p-10">
                        <div>
                            <p class="eyebrow">Newsletter</p>
                            <h2 class="font-display mt-4 text-4xl font-black leading-none tracking-[-0.05em] text-navy md:text-6xl">
                                Catatan unit baru dari bengkel rental.
                            </h2>
                            <p class="mt-5 text-sm font-semibold leading-7 text-muted">
                                Daftarkan email Anda untuk mendapat informasi unit terbaru dan promo rental dari ElektraRent.
                            </p>
                        </div>

                        <form action="newsletter/store.php" method="POST" class="grid gap-4">
                            <?= csrfField() ?>
                            <?php if ($newsletterFlash): ?>
                                <div class="rounded-lg border p-4 text-sm font-semibold <?= $newsletterFlash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
                                    <?= e($newsletterFlash['message']) ?>
                                </div>
                            <?php endif; ?>
                            <label for="email_subscriber" class="form-label">Email subscriber</label>
                            <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                                <input type="email" name="email_subscriber" id="email_subscriber" class="input-retro" placeholder="contoh: nama@email.com" required />
                                <button id="index_newsletter_submit_button" type="submit" class="btn-primary px-5">
                                    <i data-lucide="send" class="h-4 w-4"></i>
                                    Kirim Sinyal
                                </button>
                            </div>
                            <p class="text-xs font-bold uppercase tracking-[0.08em] text-muted">
                                Email Anda akan tersimpan aman di database kami.
                            </p>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t-[3px] border-ink bg-[var(--color-navy)] text-cream">
            <div class="editorial-container grid gap-6 py-8 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <p class="font-display text-3xl font-black tracking-[-0.04em]">ElektraRent</p>
                    <p class="mt-2 text-xs font-bold uppercase tracking-[0.16em] text-cream/70">Vintage modern electronic rental system</p>
                </div>
                <div class="flex flex-wrap gap-4 text-xs font-black uppercase tracking-[0.12em] text-cream/80">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php' ?>" class="hover:text-ochre">Dashboard</a>
                        <a href="auth/logout.php" class="hover:text-ochre">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="hover:text-ochre">Login</a>
                        <a href="register.php" class="hover:text-ochre">Registrasi</a>
                    <?php endif; ?>
                    <span>&copy; <?= date('Y') ?></span>
                </div>
            </div>
        </footer>

        <script src="https://unpkg.com/lucide@latest"></script>
        <script src="assets/js/main.js"></script>
    </body>
</html>
