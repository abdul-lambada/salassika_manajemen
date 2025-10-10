<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/attendance_whatsapp_automation.php';

$currentUser = admin_require_auth(['admin']);

$title = 'Pengaturan Otomatisasi WhatsApp';
$active_page = 'whatsapp_automation';
$required_role = 'admin';
$csrfToken = admin_get_csrf_token();

// Initialize automation service
$automation = new AttendanceWhatsAppAutomation($conn);
$config = null;
$alert = ['should_display' => false, 'message' => '', 'class' => 'alert-info'];

try {
    // Get current automation configuration
    $stmt = $conn->prepare("
        SELECT * FROM whatsapp_automation_config 
        LIMIT 1
    ");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Create default config if not exists
        $stmt = $conn->prepare("
            INSERT INTO whatsapp_automation_config 
            (notify_late_arrival, notify_absence, notify_parents, notify_admin, late_threshold_minutes, absence_check_time, daily_summary_time, is_active) 
            VALUES (1, 1, 1, 1, 15, '09:00:00', '16:00:00', 1)
        ");
        $stmt->execute();
        
        // Reload config
        $stmt = $conn->prepare("
            SELECT * FROM whatsapp_automation_config 
            LIMIT 1
        ");
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $alert = ['should_display' => true, 'message' => 'Error mengambil konfigurasi: ' . $e->getMessage(), 'class' => 'alert-danger'];
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        $alert = ['should_display' => true, 'message' => 'Token CSRF tidak valid.', 'class' => 'alert-danger'];
    } else {
        try {
            $notify_late_arrival = isset($_POST['notify_late_arrival']) ? 1 : 0;
            $notify_absence = isset($_POST['notify_absence']) ? 1 : 0;
            $notify_parents = isset($_POST['notify_parents']) ? 1 : 0;
            $notify_admin = isset($_POST['notify_admin']) ? 1 : 0;
            $late_threshold_minutes = (int)$_POST['late_threshold_minutes'];
            $absence_check_time = $_POST['absence_check_time'];
            $daily_summary_time = $_POST['daily_summary_time'];
            $weekend_notifications = isset($_POST['weekend_notifications']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $stmt = $conn->prepare("\n                UPDATE whatsapp_automation_config \n                SET notify_late_arrival = ?, notify_absence = ?, notify_parents = ?, notify_admin = ?,\n                    late_threshold_minutes = ?, absence_check_time = ?, daily_summary_time = ?,\n                    weekend_notifications = ?, is_active = ?\n                WHERE id = 1\n            ");

            $success = $stmt->execute([
                $notify_late_arrival,
                $notify_absence,
                $notify_parents,
                $notify_admin,
                $late_threshold_minutes,
                $absence_check_time,
                $daily_summary_time,
                $weekend_notifications,
                $is_active,
            ]);

            if ($success) {
                $alert = ['should_display' => true, 'message' => 'Pengaturan otomatisasi WhatsApp berhasil disimpan!', 'class' => 'alert-success'];
            } else {
                throw new Exception('Gagal menyimpan pengaturan');
            }
        } catch (Exception $e) {
            $alert = ['should_display' => true, 'message' => 'Error menyimpan konfigurasi: ' . $e->getMessage(), 'class' => 'alert-danger'];
        }
    }
}
// Handle test notifications
if (isset($_GET['test_notification'])) {
    if (!admin_validate_csrf($_GET['token'] ?? null)) {
        $alert = ['should_display' => true, 'message' => 'Token CSRF tidak valid untuk aksi test.', 'class' => 'alert-danger'];
    } else {
    try {
        $test_type = $_GET['test_notification'];

        switch ($test_type) {
            case 'daily_summary':
                $result = sendDailyAttendanceSummary();
                if ($result['success']) {
                    $alert = ['should_display' => true, 'message' => 'Test ringkasan harian berhasil dikirim!', 'class' => 'alert-success'];
                } else {
                    $alert = ['should_display' => true, 'message' => 'Test ringkasan harian gagal: ' . $result['message'], 'class' => 'alert-danger'];
                }
                break;

            case 'absent_check':
                $result = checkAbsentStudents();
                if ($result['success']) {
                    $alert = ['should_display' => true, 'message' => 'Test pengecekan ketidakhadiran berhasil dijalankan! Ditemukan ' . $result['absent_count'] . ' siswa tidak hadir.', 'class' => 'alert-success'];
                } else {
                    $alert = ['should_display' => true, 'message' => 'Test pengecekan ketidakhadiran gagal: ' . $result['message'], 'class' => 'alert-danger'];
                }
                break;

            default:
                $alert = ['should_display' => true, 'message' => 'Jenis test notifikasi tidak dikenal.', 'class' => 'alert-warning'];
                break;
        }
    } catch (\Throwable $e) {
        $alert = ['should_display' => true, 'message' => 'Error test notifikasi: ' . $e->getMessage(), 'class' => 'alert-danger'];
    }
    }
}
?>

<?php include '../../templates/layout_start.php'; ?>
        
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Pengaturan Otomatisasi WhatsApp</h1>
            </div>

            <?php if ($alert['should_display'] ?? false): ?>
                <?= admin_render_alert($alert); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Konfigurasi Otomatisasi</h6>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?= isset($config['is_active']) && $config['is_active'] ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="is_active">Aktifkan Otomatisasi WhatsApp</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="notify_late_arrival" name="notify_late_arrival" <?= isset($config['notify_late_arrival']) && $config['notify_late_arrival'] ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="notify_late_arrival">Notifikasi Keterlambatan</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="notify_absence" name="notify_absence" <?= isset($config['notify_absence']) && $config['notify_absence'] ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="notify_absence">Notifikasi Ketidakhadiran</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="notify_parents" name="notify_parents" <?= isset($config['notify_parents']) && $config['notify_parents'] ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="notify_parents">Notifikasi ke Orang Tua (Siswa)</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="notify_admin" name="notify_admin" <?= isset($config['notify_admin']) && $config['notify_admin'] ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="notify_admin">Notifikasi ke Admin</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="weekend_notifications" name="weekend_notifications" <?= isset($config['weekend_notifications']) && $config['weekend_notifications'] ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="weekend_notifications">Kirim Notifikasi saat Weekend</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="late_threshold_minutes">Batas Waktu Keterlambatan (menit)</label>
                                    <input type="number" class="form-control" id="late_threshold_minutes" name="late_threshold_minutes" 
                                           value="<?= htmlspecialchars(isset($config['late_threshold_minutes']) ? $config['late_threshold_minutes'] : '15') ?>" min="1" max="120">
                                    <small class="form-text text-muted">Notifikasi khusus akan dikirim jika terlambat lebih dari jumlah menit ini</small>
                                </div>

                                <div class="form-group">
                                    <label for="absence_check_time">Waktu Pengecekan Ketidakhadiran</label>
                                    <input type="time" class="form-control" id="absence_check_time" name="absence_check_time" 
                                           value="<?= htmlspecialchars(isset($config['absence_check_time']) ? substr($config['absence_check_time'], 0, 5) : '09:00') ?>">
                                    <small class="form-text text-muted">Waktu sistem akan memeriksa siswa yang tidak hadir</small>
                                </div>

                                <div class="form-group">
                                    <label for="daily_summary_time">Waktu Ringkasan Harian</label>
                                    <input type="time" class="form-control" id="daily_summary_time" name="daily_summary_time" 
                                           value="<?= htmlspecialchars(isset($config['daily_summary_time']) ? substr($config['daily_summary_time'], 0, 5) : '16:00') ?>">
                                    <small class="form-text text-muted">Waktu pengiriman ringkasan kehadiran harian ke admin</small>
                                </div>

                                <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Test Otomatisasi</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Jalankan test untuk memverifikasi konfigurasi otomatisasi WhatsApp</p>
                            
                            <a href="?test_notification=daily_summary&token=<?= urlencode($csrfToken); ?>" class="btn btn-info btn-block mb-3">
                                <i class="fas fa-chart-bar mr-2"></i>Test Ringkasan Harian
                            </a>
                            <a href="?test_notification=absent_check&token=<?= urlencode($csrfToken); ?>" class="btn btn-warning btn-block">
                                <i class="fas fa-user-check mr-2"></i>Test Cek Ketidakhadiran
                            </a>
                        </div>
                    </div>
                    
{{ ... }}
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Log Otomatisasi</h6>
                        </div>
                        <div class="card-body">
                            <a href="automation_logs.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-history mr-2"></i>Lihat Log Otomatisasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php include '../../templates/layout_end.php'; ?>
