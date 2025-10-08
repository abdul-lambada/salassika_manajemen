<?php
session_start();
include '../includes/db.php';
include_once '../includes/email_util.php';

// Periksa apakah sesi 'user' tersedia
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit;
}

$active_page = "absensi_guru"; // Untuk menandai menu aktif di sidebar
$message = ''; // Variabel untuk menyimpan pesan sukses

try {
    // Ambil daftar guru
    $stmt_guru = $conn->prepare("SELECT * FROM guru ORDER BY nama_guru");
    $stmt_guru->execute();
    $guru_list = $stmt_guru->fetchAll(PDO::FETCH_ASSOC);

    // Jika form absensi disubmit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_absensi'])) {
        $tanggal = date('Y-m-d');
        $error = false;
        foreach ($_POST['status'] as $id_guru => $status_kehadiran) {
            // Cek apakah guru sudah fingerprint hari ini
            $stmt_fp = $conn->prepare("SELECT timestamp FROM guru g LEFT JOIN users u ON g.user_id = u.id LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id AND DATE(kh.timestamp) = ? WHERE g.id_guru = ? LIMIT 1");
            $stmt_fp->execute([$tanggal, $id_guru]);
            $fingerprint = $stmt_fp->fetch(PDO::FETCH_ASSOC);
            if ($fingerprint && $fingerprint['timestamp']) {
                // Jika fingerprint sudah ada, abaikan input manual
                continue;
            }

            // Cek apakah absensi sudah ada untuk hari ini
            $stmt_check = $conn->prepare("SELECT * FROM absensi_guru WHERE id_guru = :id_guru AND tanggal = :tanggal");
            $stmt_check->bindParam(':id_guru', $id_guru);
            $stmt_check->bindParam(':tanggal', $tanggal);
            $stmt_check->execute();

            if ($stmt_check->rowCount() > 0) {
                // Update absensi yang sudah ada
                $stmt_update = $conn->prepare("
                    UPDATE absensi_guru 
                    SET status_kehadiran = :status_kehadiran, catatan = :catatan 
                    WHERE id_guru = :id_guru AND tanggal = :tanggal
                ");
                $catatan = htmlspecialchars($_POST['catatan'][$id_guru]);
                $stmt_update->bindParam(':id_guru', $id_guru);
                $stmt_update->bindParam(':tanggal', $tanggal);
                $stmt_update->bindParam(':status_kehadiran', $status_kehadiran);
                $stmt_update->bindParam(':catatan', $catatan);
                $stmt_update->execute();
            } else {
                // Simpan absensi baru
                $stmt_insert = $conn->prepare("
                    INSERT INTO absensi_guru (id_guru, tanggal, status_kehadiran, catatan)
                    VALUES (:id_guru, :tanggal, :status_kehadiran, :catatan)
                ");
                $catatan = htmlspecialchars($_POST['catatan'][$id_guru]);
                $stmt_insert->bindParam(':id_guru', $id_guru);
                $stmt_insert->bindParam(':tanggal', $tanggal);
                $stmt_insert->bindParam(':status_kehadiran', $status_kehadiran);
                $stmt_insert->bindParam(':catatan', $catatan);
                $stmt_insert->execute();
            }

            // Setelah absensi disimpan, cek status dan kirim email jika Telat/Izin
            if (in_array($status_kehadiran, ['Telat', 'Izin'])) {
                // Ambil email guru
                $stmt_email = $conn->prepare("SELECT u.email, u.name FROM users u JOIN guru g ON u.id = g.user_id WHERE g.id_guru = ?");
                $stmt_email->execute([$id_guru]);
                $user = $stmt_email->fetch(PDO::FETCH_ASSOC);
                if ($user && !empty($user['email'])) {
                    $subject = "Notifikasi Absensi: $status_kehadiran";
                    $body = "<p>Yth. {$user['name']},<br>Anda tercatat <b>$status_kehadiran</b> pada $tanggal.<br>Silakan cek sistem absensi untuk detail.</p>";
                    sendAbsensiNotification($user['email'], $subject, $body);
                }
            }
        }

        // Set pesan sukses
        $message = 'Absensi berhasil disimpan.';
    }

    // Ambil data guru dengan informasi fingerprint hari ini
    $stmt_guru_fingerprint = $conn->prepare("
        SELECT 
            g.id_guru,
            g.nama_guru,
            g.nip,
            g.jenis_kelamin,
            u.id as user_id,
            u.uid as fingerprint_uid,
            u.name AS user_name,
            kh.timestamp AS waktu_fingerprint,
            kh.verification_mode AS mode_verifikasi,
            kh.status AS status_fingerprint,
            ag.status_kehadiran AS status_manual,
            ag.catatan AS catatan_manual
        FROM guru g
        LEFT JOIN users u ON g.user_id = u.id
        LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id AND DATE(kh.timestamp) = CURDATE()
        LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru AND ag.tanggal = CURDATE()
        ORDER BY g.nama_guru
    ");
    $stmt_guru_fingerprint->execute();
    $guru_fingerprint_list = $stmt_guru_fingerprint->fetchAll(PDO::FETCH_ASSOC);

    // Ambil pengaturan jam kerja
    $jam_kerja_stmt = $conn->query("SELECT * FROM tbl_jam_kerja WHERE id = 1");
    $jam_kerja = $jam_kerja_stmt->fetch(PDO::FETCH_ASSOC);
    $jam_masuk = $jam_kerja ? $jam_kerja['jam_masuk'] : '06:30:00';
    $toleransi = $jam_kerja ? (int)$jam_kerja['toleransi_telat_menit'] : 5;

    // Statistik absensi guru hari ini
    $stmt_stats = $conn->prepare("
        SELECT 
            COUNT(*) as total_guru,
            SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status_kehadiran = 'Telat' THEN 1 ELSE 0 END) as telat,
            SUM(CASE WHEN status_kehadiran = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status_kehadiran = 'Alfa' THEN 1 ELSE 0 END) as alfa
        FROM absensi_guru ag
        WHERE ag.tanggal = CURDATE()
    ");
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Pagination setup
    $limit = 10; // Jumlah data per halaman
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Hitung total data
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM absensi_guru");
    $stmt_count->execute();
    $total_data = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_data / $limit);

    // Ambil riwayat absensi dengan limit dan offset
    $stmt_history = $conn->prepare("
        SELECT 
            ag.id_absensi_guru AS id_absensi,
            ag.tanggal,
            g.nama_guru,
            u.name AS user_name,
            ag.status_kehadiran AS status_kehadiran,
            ag.catatan,
            kh.timestamp AS waktu_kehadiran,
            kh.verification_mode AS mode_verifikasi,
            kh.status AS status_verifikasi
        FROM absensi_guru ag
        JOIN guru g ON ag.id_guru = g.id_guru
        LEFT JOIN users u ON g.user_id = u.id
        LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id AND DATE(kh.timestamp) = ag.tanggal
        ORDER BY ag.tanggal DESC, g.nama_guru
        LIMIT :limit OFFSET :offset
    ");
    $stmt_history->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_history->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt_history->execute();
    $absensi_list = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Tambahkan endpoint AJAX untuk polling fingerprint baru
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fingerprint_status') {
    include __DIR__ . '/../includes/db.php';
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_kehadiran WHERE DATE(timestamp) = ?");
    $stmt->execute([$today]);
    $total_fp = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo json_encode(['total_fp' => $total_fp]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi Guru - Management Salassika</title>
    <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.css" rel="stylesheet">
    <style>
        .fingerprint-status {
            font-size: 0.8em;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .status-hadir { background-color: #d4edda; color: #155724; }
        .status-telat { background-color: #fff3cd; color: #856404; }
        .status-izin { background-color: #d1ecf1; color: #0c5460; }
        .status-sakit { background-color: #f8d7da; color: #721c24; }
        .status-alfa { background-color: #f8d7da; color: #721c24; }
        .fingerprint-badge {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7em;
        }
    </style>
</head>

<body id="page-top">
    <?php include __DIR__ . '/../templates/header.php'; ?>
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            
            <?php include __DIR__ . '/../templates/navbar.php'; ?>
            <div class="container-fluid">
                <!-- Tampilkan pesan sukses jika ada -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Statistik Absensi Hari Ini -->
                <?php if (isset($stats)): ?>
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_guru']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Hadir</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['hadir']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Telat</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['telat']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Izin</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['izin']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Sakit</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['sakit']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-procedures fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Alfa</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['alfa']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form Absensi Guru -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Form Absensi Guru - <?php echo date('d/m/Y'); ?></h6>
                        <div>
                            <span class="badge badge-primary">Fingerprint</span>
                            <span class="badge badge-secondary">Manual</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nama Guru</th>
                                            <th>NIP</th>
                                            <th>Fingerprint</th>
                                            <th>Status Kehadiran</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($guru_fingerprint_list as $guru): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($guru['nama_guru'] ?: $guru['user_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($guru['jenis_kelamin']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($guru['nip']); ?></td>
                                                <td>
                                                    <?php
                                                    // Cek apakah guru sudah fingerprint hari ini
                                                    $today = date('Y-m-d');
                                                    $stmt_fp = $conn->prepare("SELECT timestamp FROM tbl_kehadiran WHERE user_id = ? AND DATE(timestamp) = ? LIMIT 1");
                                                    $stmt_fp->execute([$guru['user_id'], $today]);
                                                    $fingerprint = $stmt_fp->fetch(PDO::FETCH_ASSOC);

                                                    // Logika status otomatis
                                                    $status_kehadiran = '';
                                                    $disable_manual = false;
                                                    if ($fingerprint) {
                                                        // Cek jam masuk dan toleransi (misal jam 07:00, toleransi 15 menit)
                                                        $jam_masuk = '07:00:00';
                                                        $toleransi = 15 * 60; // 15 menit dalam detik
                                                        $waktu_fp = strtotime($fingerprint['timestamp']);
                                                        $waktu_masuk = strtotime($today . ' ' . $jam_masuk);
                                                        if ($waktu_fp <= $waktu_masuk + $toleransi) {
                                                            $status_kehadiran = 'Hadir';
                                                        } else {
                                                            $status_kehadiran = 'Telat';
                                                        }
                                                        $disable_manual = true;
                                                    }
                                                    ?>
                                                    <?php if ($fingerprint): ?>
                                                        <span class="fingerprint-badge">
                                                            <i class="fas fa-fingerprint"></i> <?php echo date('H:i', strtotime($fingerprint['timestamp'])); ?>
                                                        </span>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($guru['mode_verifikasi']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum absen</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <select name="status[<?php echo $guru['id_guru']; ?>]" class="form-control" <?php echo $disable_manual ? 'disabled' : ''; ?>>
                                                        <option value="Hadir" <?php echo ($guru['status_manual'] == 'Hadir' || $status_kehadiran == 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                                                        <option value="Telat" <?php echo ($guru['status_manual'] == 'Telat' || $status_kehadiran == 'Telat') ? 'selected' : ''; ?>>Telat</option>
                                                        <option value="Izin" <?php echo ($guru['status_manual'] == 'Izin') ? 'selected' : ''; ?>>Izin</option>
                                                        <option value="Sakit" <?php echo ($guru['status_manual'] == 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                                                        <option value="Alfa" <?php echo ($guru['status_manual'] == 'Alfa') ? 'selected' : ''; ?>>Alfa</option>
                                                    </select>
                                                    <?php if ($disable_manual): ?>
                                                        <input type="hidden" name="status[<?php echo $guru['id_guru']; ?>]" value="<?php echo $status_kehadiran; ?>">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="text" name="catatan[<?php echo $guru['id_guru']; ?>]" 
                                                           class="form-control" placeholder="Catatan" 
                                                           value="<?php echo htmlspecialchars(isset($guru['catatan_manual']) ? $guru['catatan_manual'] : ''); ?>"
                                                           <?php echo $disable_manual ? 'disabled' : ''; ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" name="submit_absensi" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Absensi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Riwayat Absensi -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Riwayat Absensi</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama Guru</th>
                                        <th>Status Kehadiran</th>
                                        <th>Fingerprint</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($absensi_list)): ?>
                                        <?php foreach ($absensi_list as $absensi): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($absensi['tanggal'])); ?></td>
                                                <td><?php echo htmlspecialchars($absensi['nama_guru'] ?: $absensi['user_name']); ?></td>
                                                <td>
                                                    <span class="fingerprint-status status-<?php echo strtolower($absensi['status_kehadiran']); ?>">
                                                        <?php echo htmlspecialchars($absensi['status_kehadiran']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($absensi['waktu_kehadiran']): ?>
                                                        <span class="fingerprint-badge">
                                                            <?php echo date('H:i', strtotime($absensi['waktu_kehadiran'])); ?>
                                                        </span>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($absensi['mode_verifikasi']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($absensi['catatan'] ?: '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada data absensi</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <!-- Tombol Previous -->
                                    <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>" tabindex="-1">Previous</a>
                                    </li>
                                    <!-- Nomor Halaman -->
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <!-- Tombol Next -->
                                    <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include __DIR__ . '/../templates/footer.php'; ?>
    </div>
    <div id="badge-fingerprint" class="badge badge-warning mb-2" style="display:none;">Ada Absen Fingerprint Baru!</div>
    <script>
// Polling AJAX fingerprint baru
setInterval(function() {
    fetch('?ajax=fingerprint_status')
        .then(res => res.json())
        .then(data => {
            if (window.last_fp_count !== undefined && data.total_fp > window.last_fp_count) {
                document.getElementById('badge-fingerprint').style.display = 'inline-block';
            }
            window.last_fp_count = data.total_fp;
        });
}, 10000);
</script>
<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
});
</script>
</body>

</html>