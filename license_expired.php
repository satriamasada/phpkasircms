<?php
require_once 'includes/functions.php';

// Don't call checkLogin here as it would create a redirect loop
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$info = getSubscriptionInfo();

// If license is valid and not expired, send them back to dashboard
if ($info['valid'] && !$info['expired']) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Expired - KasirPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8f9fa; height: 100vh; display: flex; align-items: center; }
        .error-card { max-width: 500px; margin: auto; border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .icon-circle { width: 100px; height: 100px; background: #fff5f5; color: #e53e3e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin: -50px auto 20px; box-shadow: 0 5px 15px rgba(229, 62, 62, 0.2); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card error-card">
            <div class="card-body p-5 text-center">
                <div class="icon-circle">
                    <i class="fas <?php echo $info['expired'] ? 'fa-calendar-times' : 'fa-exclamation-triangle'; ?>"></i>
                </div>
                <h2 class="fw-bold mb-3">
                    <?php echo $info['expired'] ? 'Masa Berlaku Habis' : 'Lisensi Tidak Valid'; ?>
                </h2>
                <p class="text-muted mb-4">
                    <?php if ($info['expired']): ?>
                        Lisensi paket <strong><?php echo $info['tier']; ?></strong> Anda telah berakhir pada tanggal <br>
                        <span class="badge bg-danger fs-6"><?php echo $info['expiry'] ?? 'Unknown'; ?></span>
                    <?php else: ?>
                        Kode lisensi yang terpasang di sistem tidak valid atau telah dimodifikasi secara ilegal.
                    <?php endif; ?>
                </p>
                
                <div class="alert alert-warning small text-start mb-4">
                    <i class="fas fa-info-circle me-2"></i> 
                    <?php echo $info['expired'] ? 'Akses ke fitur manajemen dan transaksi sementara dibatasi per hari ini.' : 'Sistem mendeteksi adanya ketidakcocokan tanda tangan digital pada kode lisensi Anda.'; ?>
                    Silakan hubungi administrator untuk mendapatkan kode lisensi yang baru.
                </div>

                <div class="d-grid gap-2">
                    <a href="https://wa.me/6281234567890" target="_blank" class="btn btn-primary rounded-pill py-2">
                        <i class="fab fa-whatsapp me-2"></i> Hubungi Sales / Admin
                    </a>
                    <a href="logout.php" class="btn btn-outline-secondary rounded-pill py-2">Logout dari Sistem</a>
                </div>
            </div>
        </div>
        <div class="text-center mt-4 text-muted small">
            &copy; 2026 KasirPOS SaaS System - License Status: <?php echo $info['tier']; ?> (Expired)
        </div>
    </div>
</body>
</html>
