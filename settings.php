<?php
require_once 'includes/functions.php';
checkLogin();
requirePermission('manage_rbac'); // Only admin can access this

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $keys = ['app_name', 'app_owner', 'app_contact', 'app_address'];
    $success = true;
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($keys as $key) {
            $value = $_POST[$key] ?? '';
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        $message = "Pengaturan sistem berhasil diperbarui!";
        // Clear cache for current request
        $app_settings_cache = null;
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Gagal memperbarui pengaturan: " . $e->getMessage();
        $message_type = 'danger';
    }
}

include 'layouts/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white p-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-white bg-opacity-10 text-white me-3">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold">System Configuration</h4>
                            <p class="mb-0 small text-white-50">Kelola identitas aplikasi dan data pemilik sistem</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> shadow-sm border-0 mb-4">
                            <i class="fas fa-info-circle me-2"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="update_settings" value="1">
                        
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-bold small text-muted text-uppercase">Informasi Aplikasi</label>
                                <div class="mb-3">
                                    <label for="app_name" class="form-label">Nama Aplikasi / Brand</label>
                                    <input type="text" class="form-control form-control-lg rounded-3" id="app_name" name="app_name" 
                                           value="<?php echo htmlspecialchars(getSetting('app_name', 'POS PREMIUM SYSTEM')); ?>" required>
                                    <div class="form-text small">Nama ini akan muncul di Sidebar, Tab Browser, dan Header Nota.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="app_owner" class="form-label">Nama Pemilik / Perusahaan</label>
                                <input type="text" class="form-control rounded-3" id="app_owner" name="app_owner" 
                                       value="<?php echo htmlspecialchars(getSetting('app_owner')); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="app_contact" class="form-label">Kontak Pusat / WhatsApp</label>
                                <input type="text" class="form-control rounded-3" id="app_contact" name="app_contact" 
                                       value="<?php echo htmlspecialchars(getSetting('app_contact')); ?>">
                            </div>

                            <div class="col-12">
                                <label for="app_address" class="form-label">Alamat Kantor Pusat</label>
                                <textarea class="form-control rounded-3" id="app_address" name="app_address" rows="3"><?php echo htmlspecialchars(getSetting('app_address')); ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4 opacity-50">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold">
                                <i class="fas fa-save me-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 p-4 bg-light rounded-4 border text-center">
                <p class="small text-muted mb-0">
                    <i class="fas fa-info-circle text-info me-1"></i>
                    Informasi di atas adalah identitas <strong>Global</strong>. Informasi detail cabang tetap dikelola melalui menu <a href="branches.php" class="text-decoration-none">Kelola Cabang</a>.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.stats-icon {
    width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 12px;
}
.form-control:focus {
    border-color: #764ba2;
    box-shadow: 0 0 0 0.25rem rgba(118, 75, 162, 0.1);
}
</style>

<?php include 'layouts/footer.php'; ?>
