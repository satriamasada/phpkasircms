<?php
require_once 'includes/functions.php';

// No auto-redirect here to allow logged-in users to view the landing page
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasirPOS - Solusi Point of Sale Modern untuk Bisnis Anda</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #38b2ac 0%, #4299e1 100%);
            
            /* Theme Variables (Default Dark) */
            --bg-body: #0f172a;
            --bg-nav: rgba(15, 23, 42, 0.8);
            --bg-card: rgba(255, 255, 255, 0.05);
            --bg-pricing-popular: #764ba2;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-glass: rgba(255, 255, 255, 0.1);
            --nav-link-color: #cbd5e1;
            --pricing-card-bg: rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="light"] {
            --bg-body: #f8fafc;
            --bg-nav: rgba(248, 250, 252, 0.85);
            --bg-card: #ffffff;
            --bg-pricing-popular: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-glass: rgba(0, 0, 0, 0.08);
            --nav-link-color: #475569;
            --pricing-card-bg: #ffffff;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Navbar */
        .navbar {
            backdrop-filter: blur(10px);
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border-glass);
            padding: 15px 0;
            transition: all 0.3s;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            color: var(--nav-link-color) !important;
            font-weight: 500;
            margin: 0 15px;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--text-main) !important;
        }

        .btn-auth {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 10px 25px;
            font-weight: 600;
            color: white !important;
            transition: all 0.3s;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
            color: white !important;
        }

        #themeToggle {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border-glass);
            background: var(--bg-card);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        #themeToggle:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        /* Hero Section */
        .hero-section {
            padding: 120px 0 80px;
            position: relative;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            color: var(--text-main);
        }

        .text-gradient {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 30px;
            max-width: 550px;
        }

        .hero-image-container {
            position: relative;
            z-index: 1;
        }

        .hero-image {
            width: 100%;
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-glass);
        }

        [data-bs-theme="dark"] .hero-image {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }

        .floating-blob {
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
        }

        /* Features */
        .features-section {
            padding: 70px 0;
        }

        .section-tag {
            background: rgba(118, 75, 162, 0.1);
            color: #7c3aed;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 30px;
            height: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: #764ba2;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: white;
        }

        /* Pricing */
        .pricing-section {
            padding: 70px 0;
            background: rgba(118, 75, 162, 0.03);
        }

        .pricing-card {
            background: var(--pricing-card-bg);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .pricing-card.popular {
            border: 2px solid #764ba2;
            position: relative;
            transform: scale(1.05);
            z-index: 2;
        }

        [data-bs-theme="light"] .pricing-card {
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .popular-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-gradient);
            color: white;
            padding: 5px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .price-tag {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 20px 0;
            color: var(--text-main);
        }

        .price-tag span {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Footer */
        .footer {
            padding: 60px 0 30px;
            border-top: 1px solid var(--border-glass);
            background: var(--bg-body);
        }

        .footer-logo {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: block;
            text-decoration: none;
            color: var(--text-main);
        }

        .footer-link {
            color: var(--text-muted);
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
            transition: color 0.3s;
        }

        .footer-link:hover {
            color: var(--text-main);
        }

        @media (max-width: 991.98px) {
            .hero-title { font-size: 3rem; }
            .pricing-card.popular { transform: none; margin: 30px 0; }
        }
    </style>
    <script>
        // Init Theme ASAP to prevent flash
        const getPreferredTheme = () => {
            const storedTheme = localStorage.getItem('theme')
            if (storedTheme) return storedTheme
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        }
        document.documentElement.setAttribute('data-bs-theme', getPreferredTheme());
    </script>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-rocket me-2"></i>KasirPOS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#features">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Harga</a></li>
                    <li class="nav-item">
                        <button id="themeToggle" class="ms-lg-3 my-2 my-lg-0" title="Toggle Theme">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <?php if (isLoggedIn()): ?>
                            <a href="dashboard.php" class="btn-auth">
                                <i class="fas fa-desktop me-2"></i> Ke Dashboard
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn-auth">
                                Mulai Sekarang <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-blob" style="top: 10%; left: -5%;"></div>
        <div class="floating-blob" style="bottom: 10%; right: -5%; background: var(--secondary-gradient); opacity: 0.1;"></div>
        
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <span class="section-tag">🔥 Generasi Baru POS SaaS</span>
                    <h1 class="hero-title">Kelola Bisnis Lebih <span class="text-gradient">Cerdas</span> & Cepat.</h1>
                    <p class="hero-subtitle">Satu platform untuk semua kebutuhan toko Anda. Dari manajemen inventaris hingga laporan keuangan real-time, semua dalam genggaman.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (isLoggedIn()): ?>
                            <a href="dashboard.php" class="btn btn-auth btn-lg px-4 py-3">Ke Dashboard Utama</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-auth btn-lg px-4 py-3">Coba Gratis 14 Hari</a>
                        <?php endif; ?>
                        <a href="#features" class="btn btn-outline-primary btn-lg px-4 py-3 rounded-4" style="border-radius: 12px; border: 2px solid #764ba2; color: #764ba2; font-weight: 600;">Lihat Demo</a>
                    </div>
                    <div class="mt-5 d-flex align-items-center gap-4 text-muted">
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> No Credit Card</div>
                        <div class="d-flex align-items-center"><i class="fas fa-check-circle text-success me-2"></i> Install Instant</div>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0" data-aos="fade-left">
                    <div class="hero-image-container">
                        <img src="assets/img/hero-pos.png" alt="KasirPOS Dashboard" class="hero-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="section-tag">Fitur Unggulan</span>
                <h2 class="display-5 fw-bold mb-4">Segala yang Anda Butuhkan</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Kami menyediakan fitur lengkap untuk mempermudah operasional bisnis Anda setiap hari.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
                        <h4 class="fw-bold">Kasir POS Cepat</h4>
                        <p class="text-muted">Transaksi hanya dalam hitungan detik. Support barcode scanner dan printer thermal.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: var(--secondary-gradient)"><i class="fas fa-boxes"></i></div>
                        <h4 class="fw-bold">Manajemen Stok</h4>
                        <p class="text-muted">Pantau stok secara real-time. Notifikasi otomatis saat stok barang mulai menipis.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-user-shield"></i></div>
                        <h4 class="fw-bold">Multi-User RBAC</h4>
                        <p class="text-muted">Atur hak akses staf dengan aman. Batasi fitur sesuai dengan jabatan masing-masing.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: var(--secondary-gradient)"><i class="fas fa-chart-pie"></i></div>
                        <h4 class="fw-bold">Laporan Akurat</h4>
                        <p class="text-muted">Laporan penjualan, profit, hingga piutang pelanggan tersedia secara mendalam.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="section-tag">Harga Transparan</span>
                <h2 class="display-5 fw-bold mb-4">Pilih Paket Bisnis Anda</h2>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="pricing-card">
                        <h4 class="mb-4 fw-bold">Standard</h4>
                        <div class="price-tag">Rp 99rb<span>/bln</span></div>
                        <ul class="list-unstyled text-muted mb-5 text-start">
                            <li class="mb-3"><i class="fas fa-check text-primary me-2"></i> 1 Toko / Cabang</li>
                            <li class="mb-3"><i class="fas fa-check text-primary me-2"></i> Unlimited Produk</li>
                            <li class="mb-3"><i class="fas fa-check text-primary me-2"></i> 2 User Staff</li>
                            <li class="mb-3"><i class="fas fa-times text-danger me-2"></i> Laporan Laba/Rugi</li>
                        </ul>
                        <?php if (isLoggedIn()): ?>
                            <a href="dashboard.php" class="btn btn-outline-primary w-100 py-3 rounded-4 mt-auto" style="border: 2px solid #764ba2; color: #764ba2; font-weight: 600;">Lihat Menu</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-primary w-100 py-3 rounded-4 mt-auto" style="border: 2px solid #764ba2; color: #764ba2; font-weight: 600;">Pilih Paket</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="pricing-card popular">
                        <div class="popular-badge">Paling Laris</div>
                        <h4 class="mb-4 fw-bold">Professional</h4>
                        <div class="price-tag">Rp 199rb<span>/bln</span></div>
                        <ul class="list-unstyled text-muted mb-5 text-start">
                            <li class="mb-3"><i class="fas fa-check text-primary me-2"></i> 5 Toko / Cabang</li>
                            <li class="mb-3"><i class="fas fa-check text-primary me-2"></i> Unlimited Produk</li>
                            <li class="mb-3"><i class="fas fa-check text-primary me-2"></i> Unlimited User</li>
                            <li class="mb-3"><i class="fas fa-check text-primary me-2"></i> Laporan Laba/Rugi</li>
                        </ul>
                        <?php if (isLoggedIn()): ?>
                             <a href="dashboard.php" class="btn-auth btn-lg w-100 py-3 text-center d-block text-decoration-none mt-auto">Masuk Dashboard</a>
                        <?php else: ?>
                             <a href="login.php" class="btn-auth btn-lg w-100 py-3 text-center d-block text-decoration-none mt-auto">Coba Professional</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <a href="#" class="footer-logo">
                        <i class="fas fa-rocket me-2"></i>KasirPOS
                    </a>
                    <p class="text-muted">Solusi digital terbaik untuk manajemen retail dan UMKM di Indonesia. Modern, Aman, dan Terpercaya.</p>
                    <div class="d-flex gap-3 mt-4">
                        <a href="#" class="text-muted fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-muted fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-muted fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-muted fs-5"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 ms-lg-auto">
                    <h6 class="fw-bold mb-4">Produk</h6>
                    <a href="#" class="footer-link">Fitur POS</a>
                    <a href="#" class="footer-link">Inventaris</a>
                    <a href="#" class="footer-link">Laporan</a>
                    <a href="#" class="footer-link">Keamanan</a>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-4">Perusahaan</h6>
                    <a href="#" class="footer-link">Tentang Kami</a>
                    <a href="#" class="footer-link">Karir</a>
                    <a href="#" class="footer-link">Blog</a>
                    <a href="#" class="footer-link">Kontak</a>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-4">Bantuan</h6>
                    <a href="#" class="footer-link">Pusat Bantuan</a>
                    <a href="#" class="footer-link">Syarat & Ketentuan</a>
                    <a href="#" class="footer-link">Kebijakan Privasi</a>
                </div>
            </div>
            <div class="text-center mt-5 pt-4 border-top border-secondary opacity-50">
                <p class="text-muted small">&copy; 2026 KasirPOS SaaS. Semua Hak Dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Theme Toggle Logic
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        const updateIcon = (theme) => {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        updateIcon(getPreferredTheme());

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.padding = '10px 0';
                navbar.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
            } else {
                navbar.style.padding = '20px 0';
                navbar.style.boxShadow = 'none';
            }
        });
    </script>
</body>
</html>
