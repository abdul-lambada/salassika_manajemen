<?php
// Aktifkan output buffering untuk menghindari masalah header
ob_start();

$title = "Log Kehadiran";
$active_page = "log_absensi"; // Untuk menandai menu aktif di sidebar
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Pagination: retrieve current page and set limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Ambil nilai IP dari query string atau gunakan default kosong
$ip_address = isset($_GET['ip']) ? $_GET['ip'] : '';

// Cek apakah tombol submit ditekan untuk mengambil data dari mesin fingerprint
$message = '';
$alert_class = '';
if (isset($_POST['submit_ip'])) {
    $ip_address = trim($_POST['ip_address']);
    if (!empty($ip_address)) {
        // Redirect dengan parameter IP
        header("Location: log_absensi.php?ip=" . urlencode($ip_address));
        exit();
    } else {
        $message = 'IP Address tidak boleh kosong.';
        $alert_class = 'alert-danger';
    }
}

// Handle status messages
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = '';
$alert_class = '';

if ($status == 'delete_success') {
    $message = 'Data log kehadiran berhasil dihapus.';
    $alert_class = 'alert-success';
} elseif ($status == 'error') {
    $message = 'Terjadi kesalahan saat menghapus data log kehadiran.';
    $alert_class = 'alert-danger';
}

// Ambil data log kehadiran dari database
include '../includes/db.php';

// Query untuk mengambil data log kehadiran
$stmt = $conn->query("SELECT SQL_CALC_FOUND_ROWS * FROM tbl_kehadiran ORDER BY timestamp DESC LIMIT $limit OFFSET $offset");
$log_kehadiran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of rows and compute total pages
$total = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($total / $limit);

// Ambil data dari mesin fingerprint jika IP address tersedia
if (!empty($ip_address)) {
    require '../includes/zklib/zklibrary.php';

    try {
        // Inisialisasi koneksi ke mesin X100-C
        $zk = new ZKLibrary($ip_address, 4370);
        $zk->connect();
        $zk->disableDevice();

        // Ambil data pengguna dan log kehadiran
        $users = $zk->getUser(); // Data pengguna
        $log_kehadiran_mesin = $zk->getAttendance(); // Data kehadiran

        // Simpan data kehadiran ke database
        foreach ($log_kehadiran_mesin as $row) {
            $uid = $row[0]; // ID unik internal mesin (tidak digunakan)
            $user_id = $row[1]; // ID pengguna
            $status = $row[2]; // Status kehadiran (0 = Masuk, 1 = Keluar)
            $timestamp = date('Y-m-d H:i:s', strtotime($row[3])); // Format waktu
            $verification_mode = isset($row[4]) ? $row[4] : 'Unknown'; // Mode verifikasi

            // Mapping mode verifikasi ke teks yang lebih deskriptif
            switch ($verification_mode) {
                case 1:
                    $verification_mode_text = 'Fingerprint';
                    break;
                case 2:
                    $verification_mode_text = 'PIN';
                    break;
                case 3:
                    $verification_mode_text = 'Card';
                    break;
                default:
                    $verification_mode_text = 'Unknown';
                    break;
            }

            // Ambil nama pengguna dari data pengguna
            $user_name = isset($users[$user_id]) ? $users[$user_id][1] : 'Unknown'; // Nama pengguna

            // Konversi status ke teks
            $status_text = $status == 0 ? 'Masuk' : 'Keluar';

            // Cek apakah data sudah ada di database
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_kehadiran WHERE user_id = ? AND timestamp = ?");
            $check_stmt->execute([$user_id, $timestamp]);
            $exists = $check_stmt->fetchColumn();

            if (!$exists) {
                // Insert data baru ke database
                $insert_stmt = $conn->prepare("INSERT INTO tbl_kehadiran (user_id, user_name, timestamp, verification_mode, status) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$user_id, $user_name, $timestamp, $verification_mode_text, $status_text]);
            }
        }

        // Aktifkan kembali mesin dan putuskan koneksi
        $zk->enableDevice();
        $zk->disconnect();

        $message = 'Data log kehadiran berhasil diambil dari mesin.';
        $alert_class = 'alert-success';

        // Refresh data log kehadiran setelah menyimpan data baru
        $stmt = $conn->query("SELECT SQL_CALC_FOUND_ROWS * FROM tbl_kehadiran ORDER BY timestamp DESC LIMIT $limit OFFSET $offset");
        $log_kehadiran = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $message = 'Gagal terhubung ke mesin fingerprint: ' . $e->getMessage();
        $alert_class = 'alert-danger';
    }
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- Begin Alert SB Admin 2 -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <!-- End Alert SB Admin 2 -->

            <!-- Form Input IP Address -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Masukkan IP Mesin Fingerprint</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group row">
                                    <label for="ip_address" class="col-sm-2 col-form-label">IP Address:</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="ip_address" name="ip_address" value="<?php echo htmlspecialchars($ip_address); ?>" placeholder="Contoh: 192.168.1.201">
                                    </div>
                                    <div class="col-sm-2">
                                        <button type="submit" name="submit_ip" class="btn btn-primary btn-block">Ambil Data</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Log Kehadiran</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-responsive-sm">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Nama</th>
                                        <th>Tanggal & Waktu</th>
                                        <th>Mode Verifikasi</th>
                                        <th>Status</th>
                                        <!-- <th>Aksi</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($log_kehadiran)): ?>
                                        <?php foreach ($log_kehadiran as $log): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['user_id']); ?></td>
                                                <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                                <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                                <td><?php echo htmlspecialchars($log['verification_mode']); ?></td>
                                                <td><?php echo htmlspecialchars($log['status']); ?></td>
                                                <!-- <td>
                                                    <a href="#" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-trash"> Hapus</i></a>
                                                </td> -->
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada data log kehadiran.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <!-- Dynamic Pagination -->
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&ip=<?php echo urlencode($ip_address); ?>">Previous</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Previous</span>
                                        </li>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&ip=<?php echo urlencode($ip_address); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&ip=<?php echo urlencode($ip_address); ?>">Next</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Next</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Data -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Hapus Data</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Apakah Kamu Yakin, Akan Menghapus Data Ini.!</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="hapus_kehadiran.php?id=<?php echo $log['id']; ?>">Hapus</a>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../templates/footer.php'; ?>
</div>

<?php
// Kirim output buffered ke browser
ob_end_flush();
?>