<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$title = "Optimasi Database";
$active_page = "optimize_database";

$optimization_results = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optimize'])) {
    try {
        $conn->beginTransaction();

        // 1. Optimasi tabel users
        $optimization_results[] = "=== Optimasi Tabel Users ===";
        
        // Index untuk uid (unique)
        try {
            $conn->exec("CREATE UNIQUE INDEX idx_users_uid ON users(uid)");
            $optimization_results[] = "✓ Index UNIQUE pada users.uid berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index UNIQUE pada users.uid sudah ada";
            } else {
                $errors[] = "✗ Error membuat index users.uid: " . $e->getMessage();
            }
        }

        // Index untuk role
        try {
            $conn->exec("CREATE INDEX idx_users_role ON users(role)");
            $optimization_results[] = "✓ Index pada users.role berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index pada users.role sudah ada";
            } else {
                $errors[] = "✗ Error membuat index users.role: " . $e->getMessage();
            }
        }

        // 2. Optimasi tabel guru
        $optimization_results[] = "\n=== Optimasi Tabel Guru ===";
        
        // Index untuk user_id (foreign key)
        try {
            $conn->exec("CREATE INDEX idx_guru_user_id ON guru(user_id)");
            $optimization_results[] = "✓ Index pada guru.user_id berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index pada guru.user_id sudah ada";
            } else {
                $errors[] = "✗ Error membuat index guru.user_id: " . $e->getMessage();
            }
        }

        // Index untuk nip (unique)
        try {
            $conn->exec("CREATE UNIQUE INDEX idx_guru_nip ON guru(nip)");
            $optimization_results[] = "✓ Index UNIQUE pada guru.nip berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index UNIQUE pada guru.nip sudah ada";
            } else {
                $errors[] = "✗ Error membuat index guru.nip: " . $e->getMessage();
            }
        }

        // 3. Optimasi tabel siswa
        $optimization_results[] = "\n=== Optimasi Tabel Siswa ===";
        
        // Index untuk user_id (foreign key)
        try {
            $conn->exec("CREATE INDEX idx_siswa_user_id ON siswa(user_id)");
            $optimization_results[] = "✓ Index pada siswa.user_id berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index pada siswa.user_id sudah ada";
            } else {
                $errors[] = "✗ Error membuat index siswa.user_id: " . $e->getMessage();
            }
        }

        // Index untuk nisn (unique)
        try {
            $conn->exec("CREATE UNIQUE INDEX idx_siswa_nisn ON siswa(nisn)");
            $optimization_results[] = "✓ Index UNIQUE pada siswa.nisn berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index UNIQUE pada siswa.nisn sudah ada";
            } else {
                $errors[] = "✗ Error membuat index siswa.nisn: " . $e->getMessage();
            }
        }

        // Index untuk nis (unique)
        try {
            $conn->exec("CREATE UNIQUE INDEX idx_siswa_nis ON siswa(nis)");
            $optimization_results[] = "✓ Index UNIQUE pada siswa.nis berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index UNIQUE pada siswa.nis sudah ada";
            } else {
                $errors[] = "✗ Error membuat index siswa.nis: " . $e->getMessage();
            }
        }

        // Index untuk id_kelas
        try {
            $conn->exec("CREATE INDEX idx_siswa_kelas ON siswa(id_kelas)");
            $optimization_results[] = "✓ Index pada siswa.id_kelas berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index pada siswa.id_kelas sudah ada";
            } else {
                $errors[] = "✗ Error membuat index siswa.id_kelas: " . $e->getMessage();
            }
        }

        // 4. Optimasi tabel absensi_guru
        $optimization_results[] = "\n=== Optimasi Tabel Absensi Guru ===";
        
        // Composite index untuk id_guru dan tanggal
        try {
            $conn->exec("CREATE INDEX idx_absensi_guru_guru_tanggal ON absensi_guru(id_guru, tanggal)");
            $optimization_results[] = "✓ Composite index pada absensi_guru(id_guru, tanggal) berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Composite index pada absensi_guru(id_guru, tanggal) sudah ada";
            } else {
                $errors[] = "✗ Error membuat composite index absensi_guru: " . $e->getMessage();
            }
        }

        // Index untuk tanggal
        try {
            $conn->exec("CREATE INDEX idx_absensi_guru_tanggal ON absensi_guru(tanggal)");
            $optimization_results[] = "✓ Index pada absensi_guru.tanggal berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index pada absensi_guru.tanggal sudah ada";
            } else {
                $errors[] = "✗ Error membuat index absensi_guru.tanggal: " . $e->getMessage();
            }
        }

        // 5. Optimasi tabel absensi_siswa
        $optimization_results[] = "\n=== Optimasi Tabel Absensi Siswa ===";
        
        // Composite index untuk id_siswa dan tanggal
        try {
            $conn->exec("CREATE INDEX idx_absensi_siswa_siswa_tanggal ON absensi_siswa(id_siswa, tanggal)");
            $optimization_results[] = "✓ Composite index pada absensi_siswa(id_siswa, tanggal) berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Composite index pada absensi_siswa(id_siswa, tanggal) sudah ada";
            } else {
                $errors[] = "✗ Error membuat composite index absensi_siswa: " . $e->getMessage();
            }
        }

        // Index untuk tanggal
        try {
            $conn->exec("CREATE INDEX idx_absensi_siswa_tanggal ON absensi_siswa(tanggal)");
            $optimization_results[] = "✓ Index pada absensi_siswa.tanggal berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index pada absensi_siswa.tanggal sudah ada";
            } else {
                $errors[] = "✗ Error membuat index absensi_siswa.tanggal: " . $e->getMessage();
            }
        }

        // 6. Optimasi tabel tbl_kehadiran
        $optimization_results[] = "\n=== Optimasi Tabel Kehadiran Fingerprint ===";
        
        // Composite index untuk user_id dan timestamp
        try {
            $conn->exec("CREATE INDEX idx_kehadiran_user_timestamp ON tbl_kehadiran(user_id, timestamp)");
            $optimization_results[] = "✓ Composite index pada tbl_kehadiran(user_id, timestamp) berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Composite index pada tbl_kehadiran(user_id, timestamp) sudah ada";
            } else {
                $errors[] = "✗ Error membuat composite index tbl_kehadiran: " . $e->getMessage();
            }
        }

        // Index untuk timestamp
        try {
            $conn->exec("CREATE INDEX idx_kehadiran_timestamp ON tbl_kehadiran(timestamp)");
            $optimization_results[] = "✓ Index pada tbl_kehadiran.timestamp berhasil dibuat";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $optimization_results[] = "ℹ Index pada tbl_kehadiran.timestamp sudah ada";
            } else {
                $errors[] = "✗ Error membuat index tbl_kehadiran.timestamp: " . $e->getMessage();
            }
        }

        // 7. Analisis tabel untuk optimasi query
        $optimization_results[] = "\n=== Analisis Tabel ===";
        
        $tables = ['users', 'guru', 'siswa', 'absensi_guru', 'absensi_siswa', 'tbl_kehadiran'];
        foreach ($tables as $table) {
            try {
                $conn->exec("ANALYZE TABLE $table");
                $optimization_results[] = "✓ Analisis tabel $table berhasil";
            } catch (PDOException $e) {
                $errors[] = "✗ Error menganalisis tabel $table: " . $e->getMessage();
            }
        }

        $conn->commit();
        $optimization_results[] = "\n=== Optimasi Database Selesai ===";
        $optimization_results[] = "Semua index dan optimasi berhasil diterapkan!";

    } catch (Exception $e) {
        $conn->rollBack();
        $errors[] = "✗ Error umum: " . $e->getMessage();
    }
}

// Get current index information
$current_indexes = [];
try {
    $tables = ['users', 'guru', 'siswa', 'absensi_guru', 'absensi_siswa', 'tbl_kehadiran'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW INDEX FROM $table");
        $stmt->execute();
        $current_indexes[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $errors[] = "Error mengambil informasi index: " . $e->getMessage();
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Optimasi Database</h1> -->

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6>Error yang terjadi:</h6>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($optimization_results)): ?>
                <div class="alert alert-success">
                    <h6>Hasil Optimasi:</h6>
                    <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 12px;"><?= htmlspecialchars(implode("\n", $optimization_results)) ?></pre>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Optimasi Database</h6>
                        </div>
                        <div class="card-body">
                            <p>Optimasi ini akan membuat index yang diperlukan untuk meningkatkan performa query database:</p>
                            <ul>
                                <li><strong>Index UNIQUE</strong> untuk field yang harus unik (uid, nip, nisn, nis)</li>
                                <li><strong>Index Foreign Key</strong> untuk relasi antar tabel</li>
                                <li><strong>Composite Index</strong> untuk query yang sering menggunakan kombinasi field</li>
                                <li><strong>Index Tanggal</strong> untuk query berdasarkan tanggal</li>
                            </ul>
                            
                            <form method="POST" action="" onsubmit="return confirm('Yakin ingin menjalankan optimasi database?')">
                                <button type="submit" name="optimize" class="btn btn-primary">
                                    <i class="fas fa-database"></i> Jalankan Optimasi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Index Saat Ini</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($current_indexes as $table => $indexes): ?>
                                <h6><?= ucfirst($table) ?></h6>
                                <ul class="list-unstyled">
                                    <?php foreach ($indexes as $index): ?>
                                        <li>
                                            <small class="text-muted">
                                                <?= $index['Key_name'] ?> 
                                                (<?= $index['Column_name'] ?>)
                                                <?= $index['Non_unique'] == 0 ? ' - UNIQUE' : '' ?>
                                            </small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../templates/footer.php'; ?>
</div>

</body>
</html> 