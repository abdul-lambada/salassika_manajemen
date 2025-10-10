<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$currentUser = admin_require_auth(['guru']);

$active_page = "absensi_siswa"; // Untuk menandai menu aktif di sidebar
$title = "Absensi Siswa";
$required_role = 'guru';
$csrfToken = admin_get_csrf_token();
$message = ''; // Variabel untuk menyimpan pesan sukses
$today = date('Y-m-d');

function csrf_check() {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        throw new Exception('Invalid CSRF token');
    }
}

try {
    // Ambil daftar kelas
    $stmt_kelas = $conn->prepare("SELECT * FROM Kelas ORDER BY nama_kelas");
    $stmt_kelas->execute();
    $kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

    $id_kelas = isset($_GET['id_kelas']) ? trim((string) $_GET['id_kelas']) : null;
    if ($id_kelas !== null) {
        if (!ctype_digit($id_kelas)) {
            $id_kelas = null;
        } else {
            $id_kelas = (int) $id_kelas;
        }
    }

    $loadFingerprintMap = static function (PDO $conn, int $classId, string $date): array {
        $stmt = $conn->prepare("
            SELECT s.id_siswa, kh.timestamp, kh.verification_mode, kh.status
            FROM siswa s
            JOIN users u ON s.user_id = u.id
            JOIN tbl_kehadiran kh ON u.id = kh.user_id
            WHERE s.id_kelas = :id_kelas AND DATE(kh.timestamp) = :tanggal
        ");
        $stmt->execute([
            ':id_kelas' => $classId,
            ':tanggal' => $date,
        ]);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $studentId = (int) ($row['id_siswa'] ?? 0);
            if ($studentId > 0 && !isset($map[$studentId])) {
                $map[$studentId] = $row;
            }
        }

        return $map;
    };

    $fingerprintBySiswa = [];
    if ($id_kelas !== null) {
        $fingerprintBySiswa = $loadFingerprintMap($conn, $id_kelas, $today);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_absensi'])) {
        csrf_check();

        $id_kelas_post_raw = trim((string) ($_POST['id_kelas'] ?? ''));
        $id_kelas_post = ctype_digit($id_kelas_post_raw) ? (int) $id_kelas_post_raw : null;

        $targetClassId = $id_kelas_post ?? $id_kelas;
        if ($targetClassId !== null && ($id_kelas === null || $targetClassId !== $id_kelas)) {
            $fingerprintBySiswa = $loadFingerprintMap($conn, $targetClassId, $today);
        }

        $errorsEncountered = false;
        foreach ((array) ($_POST['status'] ?? []) as $id_siswa => $status_kehadiran) {
            $id_siswa = (int) $id_siswa;
            $status_kehadiran = trim((string) $status_kehadiran);
            if ($id_siswa <= 0 || $status_kehadiran === '') {
                continue;
            }

            $fingerprint = $fingerprintBySiswa[$id_siswa] ?? null;
            if ($fingerprint && !empty($fingerprint['timestamp'])) {
                continue;
            }

            $catatan = trim((string) ($_POST['catatan'][$id_siswa] ?? ''));

            try {
                $stmt_check = $conn->prepare('SELECT 1 FROM absensi_siswa WHERE id_siswa = :id_siswa AND tanggal = :tanggal');
                $stmt_check->execute([
                    ':id_siswa' => $id_siswa,
                    ':tanggal' => $today,
                ]);

                if ($stmt_check->fetchColumn()) {
                    $stmt_update = $conn->prepare('
                        UPDATE absensi_siswa
                        SET status_kehadiran = :status_kehadiran, catatan = :catatan
                        WHERE id_siswa = :id_siswa AND tanggal = :tanggal
                    ');
                    $stmt_update->execute([
                        ':status_kehadiran' => $status_kehadiran,
                        ':catatan' => $catatan,
                        ':id_siswa' => $id_siswa,
                        ':tanggal' => $today,
                    ]);
                } else {
                    $stmt_insert = $conn->prepare('
                        INSERT INTO absensi_siswa (id_siswa, tanggal, status_kehadiran, catatan)
                        VALUES (:id_siswa, :tanggal, :status_kehadiran, :catatan)
                    ');
                    $stmt_insert->execute([
                        ':id_siswa' => $id_siswa,
                        ':tanggal' => $today,
                        ':status_kehadiran' => $status_kehadiran,
                        ':catatan' => $catatan,
                    ]);
                }
            } catch (PDOException $e) {
                admin_log_message(
                    'absensi_siswa_errors.log',
                    'Failed to save manual attendance: ' . $e->getMessage() . ' for student ID ' . $id_siswa,
                    'ERROR'
                );
                $errorsEncountered = true;
            }
        }

        $message = $errorsEncountered
            ? 'Terjadi kesalahan saat menyimpan absensi. Silakan coba lagi.'
            : 'Absensi berhasil disimpan.';

        if ($id_kelas_post !== null) {
            $id_kelas = $id_kelas_post;
            $fingerprintBySiswa = $loadFingerprintMap($conn, $id_kelas, $today);
        }
    }

    // Jika kelas dipilih melalui GET (setelah POST fallback)
    $siswa_list = array();
    $siswa_null_list = array(); // Inisialisasi array untuk siswa yang user_id-nya NULL

    if ($id_kelas) {
        // Ambil data siswa dengan informasi fingerprint, hanya yang user_id-nya tidak null
        $stmt_siswa = $conn->prepare("
            SELECT 
                s.id_siswa,
                s.user_id,
                COALESCE(u.name, s.nama_siswa) AS nama_siswa,
                s.jenis_kelamin,
                s.tanggal_lahir,
                s.alamat,
                s.nis,
                kh.timestamp AS waktu_fingerprint,
                kh.verification_mode AS mode_verifikasi,
                kh.status AS status_fingerprint,
                asis.status_kehadiran AS status_manual,
                asis.catatan AS catatan_manual
            FROM Siswa s
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN tbl_kehadiran kh ON s.user_id = kh.user_id AND DATE(kh.timestamp) = CURDATE()
            LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa AND asis.tanggal = CURDATE()
            WHERE s.id_kelas = :id_kelas AND s.user_id IS NOT NULL
            ORDER BY COALESCE(u.name, s.nama_siswa)
        ");
        $stmt_siswa->bindParam(':id_kelas', $id_kelas);
        $stmt_siswa->execute();
        $siswa_list = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

        // Cek siswa yang user_id-nya NULL
        $stmt_siswa_null = $conn->prepare("SELECT nama_siswa, nis FROM Siswa WHERE id_kelas = :id_kelas AND user_id IS NULL");
        $stmt_siswa_null->bindParam(':id_kelas', $id_kelas);
        $stmt_siswa_null->execute();
        $siswa_null_list = $stmt_siswa_null->fetchAll(PDO::FETCH_ASSOC);

        // --- Tambahan: Proses fingerprint otomatis ke absensi_siswa ---
        $jam_kerja_stmt = $conn->query("SELECT * FROM tbl_jam_kerja WHERE id = 1");
        $jam_kerja = $jam_kerja_stmt->fetch(PDO::FETCH_ASSOC);
        $jam_masuk = $jam_kerja ? $jam_kerja['jam_masuk'] : '06:30:00';
        $toleransi = $jam_kerja ? (int)$jam_kerja['toleransi_telat_menit'] : 5;
        $tanggal = date('Y-m-d');
        foreach ($siswa_list as $siswa) {
            if ($siswa['waktu_fingerprint'] && !$siswa['status_manual']) {
                // Hitung status otomatis
                $jam_masuk_full = $tanggal . ' ' . $jam_masuk;
                $batas_telat = strtotime($jam_masuk_full) + ($toleransi * 60);
                $waktu_fp = strtotime($siswa['waktu_fingerprint']);
                $auto_status = ($waktu_fp <= $batas_telat) ? 'Hadir' : 'Telat';
                // Simpan otomatis ke absensi_siswa jika belum ada
                $stmt_check = $conn->prepare("SELECT 1 FROM absensi_siswa WHERE id_siswa = ? AND tanggal = ?");
                $stmt_check->execute([$siswa['id_siswa'], $tanggal]);
                if (!$stmt_check->fetchColumn()) {
                    $stmt_insert = $conn->prepare("INSERT INTO absensi_siswa (id_siswa, tanggal, status_kehadiran, catatan) VALUES (?, ?, ?, ?)");
                    $stmt_insert->execute([$siswa['id_siswa'], $tanggal, $auto_status, 'Fingerprint']);
                }
            }
        }
        // --- END Tambahan ---
    }

    // Ambil parameter filter dan pagination
    $id_kelas_filter = isset($_GET['id_kelas_filter']) ? $_GET['id_kelas_filter'] : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Query total data untuk pagination
    $where_filter = '';
    $params_filter = [];
    if ($id_kelas_filter) {
        $where_filter = 'WHERE s.id_kelas = :id_kelas_filter';
        $params_filter[':id_kelas_filter'] = $id_kelas_filter;
    }
    $stmt_count = $conn->prepare("
        SELECT COUNT(*) FROM absensi_siswa asis
        JOIN Siswa s ON asis.id_siswa = s.id_siswa
        $where_filter
    ");
    $stmt_count->execute($params_filter);
    $total_rows = $stmt_count->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Query data riwayat absensi dengan filter dan pagination
    $stmt_history = $conn->prepare("
        SELECT 
            asis.id_absensi_siswa AS id_absensi,
            asis.tanggal,
            COALESCE(u.name, s.nama_siswa) AS nama_siswa,
            k.nama_kelas,
            asis.status_kehadiran AS status_kehadiran,
            asis.catatan,
            kh.timestamp AS waktu_fingerprint,
            kh.verification_mode AS mode_verifikasi,
            kh.status AS status_fingerprint
        FROM absensi_siswa asis
        JOIN Siswa s ON asis.id_siswa = s.id_siswa
        JOIN Kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN tbl_kehadiran kh ON s.user_id = kh.user_id AND DATE(kh.timestamp) = asis.tanggal
        $where_filter
        ORDER BY asis.tanggal DESC, COALESCE(u.name, s.nama_siswa)
        LIMIT :limit OFFSET :offset
    ");
    if ($id_kelas_filter) $stmt_history->bindParam(':id_kelas_filter', $id_kelas_filter);
    $stmt_history->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt_history->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt_history->execute();
    $absensi_list = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

    // Statistik absensi hari ini
    $stmt_stats = $conn->prepare("
        SELECT 
            COUNT(*) as total_siswa,
            SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status_kehadiran = 'Telat' THEN 1 ELSE 0 END) as telat,
            SUM(CASE WHEN status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status_kehadiran = 'Ijin' THEN 1 ELSE 0 END) as ijin,
            SUM(CASE WHEN status_kehadiran = 'Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir
        FROM absensi_siswa asis
        JOIN Siswa s ON asis.id_siswa = s.id_siswa
        WHERE asis.tanggal = CURDATE() AND s.id_kelas = :id_kelas
    ");
    $stmt_stats->bindParam(':id_kelas', $id_kelas);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Ambil pengaturan jam kerja
    $jam_kerja_stmt = $conn->query("SELECT * FROM tbl_jam_kerja WHERE id = 1");
    $jam_kerja = $jam_kerja_stmt->fetch(PDO::FETCH_ASSOC);
    $jam_masuk = $jam_kerja ? $jam_kerja['jam_masuk'] : '06:30:00';
    $toleransi = $jam_kerja ? (int)$jam_kerja['toleransi_telat_menit'] : 5;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<?php
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fingerprint_status') {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_kehadiran WHERE DATE(timestamp) = ?");
    $stmt->execute([$today]);
    $total_fp = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo json_encode(['total_fp' => $total_fp]);
    exit;
}
?>
<?php include __DIR__ . '/../templates/layout_start.php'; ?>
        <style>
        .fingerprint-status {
            font-size: 0.8em;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .status-hadir { background-color: #d4edda; color: #155724; }
        .status-telat { background-color: #fff3cd; color: #856404; }
        .status-sakit { background-color: #f8d7da; color: #721c24; }
        .status-ijin { background-color: #d1ecf1; color: #0c5460; }
        .status-tidak-hadir { background-color: #f8d7da; color: #721c24; }
        .fingerprint-badge {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7em;
        }
    </style>
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

                <!-- Form pemilihan kelas -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Pilih Kelas</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Pilih Kelas:</label>
                                    <select name="id_kelas" class="form-control" required>
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($kelas_list as $kelas): ?>
                                            <option value="<?php echo htmlspecialchars($kelas['id_kelas']); ?>" <?php if ($id_kelas == $kelas['id_kelas']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-search"></i> Tampilkan Siswa
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistik Absensi Hari Ini -->
                <?php if ($id_kelas && isset($stats)): ?>
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_siswa']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Sakit</div>
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
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Ijin</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['ijin']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
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
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Tidak Hadir</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['tidak_hadir']; ?></div>
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

                <!-- Form Absensi Siswa -->
                <?php if ($id_kelas && !empty($siswa_list)): ?>
                    <?php if (!empty($siswa_null_list)): ?>
                        <div class="alert alert-warning">
                            <b>Perhatian!</b> Ada siswa yang belum terhubung ke akun pengguna dan tidak bisa diabsen fingerprint:<br>
                            <ul>
                                <?php foreach ($siswa_null_list as $s): ?>
                                    <li><?php echo htmlspecialchars($s['nama_siswa']); ?> (NIS: <?php echo htmlspecialchars($s['nis']); ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                            Silakan hubungi admin untuk melengkapi data user siswa tersebut.
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Form Absensi Siswa - <?php echo date('d/m/Y'); ?></h6>
                            <div>
                                <span class="badge badge-primary">Fingerprint</span>
                                <span class="badge badge-secondary">Manual</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="id_kelas" value="<?php echo htmlspecialchars($id_kelas); ?>">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Nama Siswa</th>
                                                <th>NIS</th>
                                                <th>Fingerprint</th>
                                                <th>Status Kehadiran</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($siswa_list as $siswa): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($siswa['nama_siswa']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($siswa['jenis_kelamin']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                                    <td>
                                                        <?php if ($siswa['waktu_fingerprint']): ?>
                                                            <span class="fingerprint-badge">
                                                                <i class="fas fa-fingerprint"></i> <?php echo date('H:i', strtotime($siswa['waktu_fingerprint'])); ?>
                                                            </span>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($siswa['mode_verifikasi']); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Belum absen</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $auto_status = '';
                                                        if ($siswa['waktu_fingerprint']) {
                                                            $jam_masuk_full = date('Y-m-d') . ' ' . $jam_masuk;
                                                            $batas_telat = strtotime($jam_masuk_full) + ($toleransi * 60);
                                                            $waktu_fp = strtotime($siswa['waktu_fingerprint']);
                                                            if ($waktu_fp <= $batas_telat) {
                                                                $auto_status = 'Hadir';
                                                            } else {
                                                                $auto_status = 'Telat';
                                                            }
                                                        }
                                                        ?>
                                                        <select name="status[<?php echo $siswa['id_siswa']; ?>]" class="form-control" <?php if ($siswa['waktu_fingerprint']) echo 'disabled'; ?> >
                                                            <option value="Hadir" <?php echo ($siswa['status_manual'] == 'Hadir' || $auto_status == 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                                                            <option value="Telat" <?php echo ($siswa['status_manual'] == 'Telat' || $auto_status == 'Telat') ? 'selected' : ''; ?>>Telat</option>
                                                            <option value="Tidak Hadir" <?php echo ($siswa['status_manual'] == 'Tidak Hadir') ? 'selected' : ''; ?>>Tidak Hadir</option>
                                                            <option value="Sakit" <?php echo ($siswa['status_manual'] == 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                                                            <option value="Ijin" <?php echo ($siswa['status_manual'] == 'Ijin') ? 'selected' : ''; ?>>Ijin</option>
                                                        </select>
                                                        <?php if ($siswa['waktu_fingerprint']): ?>
                                                            <input type="hidden" name="status[<?php echo $siswa['id_siswa']; ?>]" value="<?php echo $auto_status; ?>">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="catatan[<?php echo $siswa['id_siswa']; ?>]" 
                                                               class="form-control" placeholder="Catatan" 
                                                               value="<?php echo htmlspecialchars(isset($siswa['catatan_manual']) ? $siswa['catatan_manual'] : ''); ?>">
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
                <?php endif; ?>

                <!-- Riwayat Absensi -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Riwayat Absensi</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="form-inline mb-2">
                            <label for="id_kelas_filter" class="mr-2">Filter Kelas:</label>
                            <select name="id_kelas_filter" id="id_kelas_filter" class="form-control mr-2">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?php echo htmlspecialchars($kelas['id_kelas']); ?>" <?php if ($id_kelas_filter == $kelas['id_kelas']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama Siswa</th>
                                        <th>Kelas</th>
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
                                                <td><?php echo htmlspecialchars($absensi['nama_siswa']); ?></td>
                                                <td><?php echo htmlspecialchars($absensi['nama_kelas']); ?></td>
                                                <td>
                                                    <span class="fingerprint-status status-<?php echo strtolower(str_replace(' ', '-', $absensi['status_kehadiran'])); ?>">
                                                        <?php echo htmlspecialchars($absensi['status_kehadiran']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($absensi['waktu_fingerprint']): ?>
                                                        <span class="fingerprint-badge">
                                                            <?php echo date('H:i', strtotime($absensi['waktu_fingerprint'])); ?>
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
                                            <td colspan="6" class="text-center text-muted">Belum ada data absensi</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                <ul class="pagination justify-content-end">
                    <!-- Tombol Previous -->
                    <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="?id_kelas=<?php echo urlencode($id_kelas); ?>&id_kelas_filter=<?php echo urlencode($id_kelas_filter); ?>&page=<?php echo $page-1; ?>" tabindex="-1">Previous</a>
                    </li>
                    <!-- Nomor Halaman -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?id_kelas=<?php echo urlencode($id_kelas); ?>&id_kelas_filter=<?php echo urlencode($id_kelas_filter); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <!-- Tombol Next -->
                    <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link" href="?id_kelas=<?php echo urlencode($id_kelas); ?>&id_kelas_filter=<?php echo urlencode($id_kelas_filter); ?>&page=<?php echo $page+1; ?>">Next</a>
                    </li>
                </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        <div id="badge-fingerprint" class="badge badge-warning mb-2" style="display:none;">Ada Absen Fingerprint Baru!</div>
<?php include __DIR__ . '/../templates/layout_end.php'; ?>
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
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
        var btn = this.querySelector('button[type=submit]');
        if (btn) {
            btn.disabled = true;
        }
    });
});
</script>