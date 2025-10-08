<?php
session_start();
include '../includes/db.php';
require '../includes/zklib/zklibrary.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$title = "Testing Integrasi Sistem";
$active_page = "test_integration";

$test_results = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_tests'])) {
    $device_ip = $_POST['device_ip'] ?? '192.168.1.100';
    
    // Test 1: Koneksi Database
    $test_results[] = "=== TEST 1: KONEKSI DATABASE ===";
    try {
        $stmt = $conn->prepare("SELECT 1");
        $stmt->execute();
        $test_results[] = "✓ Koneksi database berhasil";
    } catch (Exception $e) {
        $errors[] = "✗ Koneksi database gagal: " . $e->getMessage();
    }

    // Test 2: Struktur Tabel
    $test_results[] = "\n=== TEST 2: STRUKTUR TABEL ===";
    $required_tables = ['users', 'guru', 'siswa', 'absensi_guru', 'absensi_siswa', 'tbl_kehadiran', 'kelas'];
    foreach ($required_tables as $table) {
        try {
            $stmt = $conn->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $test_results[] = "✓ Tabel $table: " . count($columns) . " kolom";
        } catch (Exception $e) {
            $errors[] = "✗ Tabel $table tidak ditemukan: " . $e->getMessage();
        }
    }

    // Test 3: Relasi Foreign Key
    $test_results[] = "\n=== TEST 3: RELASI FOREIGN KEY ===";
    try {
        // Test relasi guru -> users
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM guru g 
            LEFT JOIN users u ON g.user_id = u.id 
            WHERE u.id IS NULL
        ");
        $stmt->execute();
        $orphan_guru = $stmt->fetchColumn();
        if ($orphan_guru == 0) {
            $test_results[] = "✓ Relasi guru -> users: Valid";
        } else {
            $errors[] = "✗ Relasi guru -> users: $orphan_guru record orphan";
        }

        // Test relasi siswa -> users
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM siswa s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE u.id IS NULL
        ");
        $stmt->execute();
        $orphan_siswa = $stmt->fetchColumn();
        if ($orphan_siswa == 0) {
            $test_results[] = "✓ Relasi siswa -> users: Valid";
        } else {
            $errors[] = "✗ Relasi siswa -> users: $orphan_siswa record orphan";
        }
    } catch (Exception $e) {
        $errors[] = "✗ Error test relasi: " . $e->getMessage();
    }

    // Test 4: Koneksi Fingerprint Device
    $test_results[] = "\n=== TEST 4: KONEKSI FINGERPRINT DEVICE ===";
    try {
        $zk = new ZKLibrary($device_ip, 4370);
        if ($zk->connect()) {
            $test_results[] = "✓ Koneksi ke device fingerprint berhasil";
            
            // Test get user data
            $zk->disableDevice();
            $users = $zk->getUser();
            $test_results[] = "✓ Data user dari device: " . count($users) . " user";
            
            // Test get attendance data
            $attendance = $zk->getAttendance();
            $test_results[] = "✓ Data absensi dari device: " . count($attendance) . " record";
            
            $zk->enableDevice();
            $zk->disconnect();
        } else {
            $errors[] = "✗ Gagal koneksi ke device fingerprint";
        }
    } catch (Exception $e) {
        $errors[] = "✗ Error koneksi fingerprint: " . $e->getMessage();
    }

    // Test 5: Sinkronisasi Data
    $test_results[] = "\n=== TEST 5: SINKRONISASI DATA ===";
    try {
        // Test mapping role
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'guru' THEN 1 ELSE 0 END) as guru_count,
                SUM(CASE WHEN role = 'siswa' THEN 1 ELSE 0 END) as siswa_count
            FROM users
        ");
        $stmt->execute();
        $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $test_results[] = "✓ Total users: " . $user_stats['total_users'];
        $test_results[] = "✓ Guru: " . $user_stats['guru_count'];
        $test_results[] = "✓ Siswa: " . $user_stats['siswa_count'];

        // Test UID unik
        $stmt = $conn->prepare("
            SELECT uid, COUNT(*) as count 
            FROM users 
            WHERE uid IS NOT NULL 
            GROUP BY uid 
            HAVING COUNT(*) > 1
        ");
        $stmt->execute();
        $duplicate_uid = $stmt->fetchAll();
        if (empty($duplicate_uid)) {
            $test_results[] = "✓ UID unik: Valid";
        } else {
            $errors[] = "✗ UID duplikat ditemukan: " . count($duplicate_uid) . " record";
        }
    } catch (Exception $e) {
        $errors[] = "✗ Error test sinkronisasi: " . $e->getMessage();
    }

    // Test 6: Performa Query
    $test_results[] = "\n=== TEST 6: PERFORMA QUERY ===";
    try {
        $start_time = microtime(true);
        
        // Test query laporan absensi
        $stmt = $conn->prepare("
            SELECT 
                u.name as nama_user,
                u.role as tipe_user,
                kh.timestamp as waktu_fingerprint,
                ag.status_kehadiran as status_guru,
                asis.status_kehadiran as status_siswa
            FROM users u
            LEFT JOIN guru g ON u.id = g.user_id
            LEFT JOIN siswa s ON u.id = s.user_id
            LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id 
                AND DATE(kh.timestamp) = CURDATE()
            LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru 
                AND ag.tanggal = CURDATE()
            LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa 
                AND asis.tanggal = CURDATE()
            LIMIT 100
        ");
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);
        
        $test_results[] = "✓ Query laporan: " . count($data) . " record dalam " . $execution_time . "ms";
        
        if ($execution_time < 1000) {
            $test_results[] = "✓ Performa query: Baik (< 1 detik)";
        } elseif ($execution_time < 3000) {
            $test_results[] = "⚠ Performa query: Sedang (1-3 detik)";
        } else {
            $errors[] = "✗ Performa query: Lambat (> 3 detik)";
        }
    } catch (Exception $e) {
        $errors[] = "✗ Error test performa: " . $e->getMessage();
    }

    // Test 7: Validasi Data
    $test_results[] = "\n=== TEST 7: VALIDASI DATA ===";
    try {
        // Test data guru
        $stmt = $conn->prepare("SELECT COUNT(*) FROM guru");
        $stmt->execute();
        $guru_count = $stmt->fetchColumn();
        $test_results[] = "✓ Data guru: $guru_count record";

        // Test data siswa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM siswa");
        $stmt->execute();
        $siswa_count = $stmt->fetchColumn();
        $test_results[] = "✓ Data siswa: $siswa_count record";

        // Test data absensi hari ini
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM absensi_guru WHERE tanggal = CURDATE()) as guru_absensi,
                (SELECT COUNT(*) FROM absensi_siswa WHERE tanggal = CURDATE()) as siswa_absensi
        ");
        $stmt->execute();
        $absensi_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $test_results[] = "✓ Absensi guru hari ini: " . $absensi_stats['guru_absensi'];
        $test_results[] = "✓ Absensi siswa hari ini: " . $absensi_stats['siswa_absensi'];

        // Test data fingerprint hari ini
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_kehadiran WHERE DATE(timestamp) = CURDATE()");
        $stmt->execute();
        $fingerprint_count = $stmt->fetchColumn();
        $test_results[] = "✓ Data fingerprint hari ini: $fingerprint_count record";
    } catch (Exception $e) {
        $errors[] = "✗ Error validasi data: " . $e->getMessage();
    }

    $test_results[] = "\n=== HASIL TESTING ===";
    if (empty($errors)) {
        $test_results[] = "🎉 SEMUA TEST BERHASIL! Sistem siap digunakan.";
    } else {
        $test_results[] = "⚠ " . count($errors) . " error ditemukan. Perbaiki sebelum menggunakan sistem.";
    }
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../templates/navbar.php'; ?>
        <div class="container-fluid">
            <h1 class="h3 mb-4 text-gray-800">Testing Integrasi Sistem</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6>Error yang ditemukan:</h6>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($test_results)): ?>
                <div class="alert alert-info">
                    <h6>Hasil Testing:</h6>
                    <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 12px; max-height: 500px; overflow-y: auto;"><?= htmlspecialchars(implode("\n", $test_results)) ?></pre>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Jalankan Testing</h6>
                </div>
                <div class="card-body">
                    <p>Testing ini akan memvalidasi:</p>
                    <ul>
                        <li><strong>Koneksi Database</strong> - Memastikan database dapat diakses</li>
                        <li><strong>Struktur Tabel</strong> - Memastikan semua tabel yang diperlukan ada</li>
                        <li><strong>Relasi Foreign Key</strong> - Memastikan relasi antar tabel valid</li>
                        <li><strong>Koneksi Fingerprint</strong> - Memastikan device fingerprint dapat diakses</li>
                        <li><strong>Sinkronisasi Data</strong> - Memastikan data tersinkronisasi dengan benar</li>
                        <li><strong>Performa Query</strong> - Memastikan query berjalan dengan cepat</li>
                        <li><strong>Validasi Data</strong> - Memastikan data konsisten</li>
                    </ul>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>IP Address Device Fingerprint:</label>
                            <input type="text" name="device_ip" class="form-control" value="192.168.1.100" required>
                        </div>
                        <button type="submit" name="run_tests" class="btn btn-primary">
                            <i class="fas fa-play"></i> Jalankan Testing
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include '../templates/footer.php'; ?>
</div>

</body>
</html> 