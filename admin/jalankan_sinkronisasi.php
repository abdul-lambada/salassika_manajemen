<?php
session_start();
include '../includes/db.php';
include '../includes/process_fingerprint_attendance.php';
$title = "Sinkronisasi Absensi";
$active_page = "jalankan_sinkronisasi";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$output_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_sync'])) {
    // Jalankan migrasi kolom jika belum ada
    try {
        $conn->query("SELECT is_processed FROM tbl_kehadiran LIMIT 1");
    } catch (PDOException $e) {
        if ($e->getCode() == '42S22') {
            try {
                $sql = file_get_contents('../migrations/002_add_is_processed_to_kehadiran.sql');
                $conn->exec($sql);
                $output_message .= "Kolom 'is_processed' berhasil ditambahkan ke tabel kehadiran.<br>";
            } catch (Exception $ex) {
                $output_message .= "Gagal menambahkan kolom 'is_processed': " . $ex->getMessage() . "<br>";
            }
        }
    }
    
    // Panggil fungsi pemroses dan tangkap outputnya
    $output_message .= nl2br(htmlspecialchars(processFingerprintAttendance()));
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Sinkronisasi Data Absensi Fingerprint</h1> -->

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Jalankan Proses Sinkronisasi</h6>
                </div>
                <div class="card-body">
                    <p>
                        Klik tombol di bawah ini untuk memproses data absensi mentah dari mesin fingerprint.
                        Skrip akan menentukan status kehadiran (Hadir/Telat) berdasarkan pengaturan jam kerja dan memasukkannya ke dalam tabel absensi guru dan siswa.
                    </p>
                    <p class="text-muted small">
                        Proses ini aman untuk dijalankan berkali-kali. Data yang sudah diproses tidak akan diproses ulang.
                    </p>
                    <form method="POST">
                        <button type="submit" name="run_sync" class="btn btn-primary">
                            <i class="fas fa-sync-alt fa-fw"></i> Jalankan Sinkronisasi
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($output_message): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Hasil Proses</h6>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded"><code><?= $output_message ?></code></pre>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php include __DIR__ . '/../templates/footer.php'; ?>
</div>

<?php include __DIR__ . '/../templates/scripts.php'; ?>
</body>
</html> 