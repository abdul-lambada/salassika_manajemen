<?php
session_start();
include '../includes/db.php';
$title = "Pengaturan Jam Kerja";
$active_page = "pengaturan_jam_kerja";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success = $error = '';

// Ambil data jam kerja saat ini (kita asumsikan hanya ada satu baris)
try {
    $stmt = $conn->query("SELECT * FROM tbl_jam_kerja WHERE id = 1");
    $jam_kerja = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika tabel belum ada, jalankan migrasi
    if ($e->getCode() == '42S02') { // Kode error untuk 'Table not found'
        try {
            $sql = file_get_contents('../migrations/001_create_jam_kerja_table.sql');
            $conn->exec($sql);
            // Coba lagi ambil data setelah migrasi
            $stmt = $conn->query("SELECT * FROM tbl_jam_kerja WHERE id = 1");
            $jam_kerja = $stmt->fetch(PDO::FETCH_ASSOC);
            $success = "Tabel jam kerja berhasil dibuat dan diinisialisasi.";
        } catch (Exception $ex) {
            $error = "Gagal membuat tabel jam kerja: " . $ex->getMessage();
            $jam_kerja = null;
        }
    } else {
        $error = "Error: " . $e->getMessage();
        $jam_kerja = null;
    }
}


// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $jam_kerja) {
    $jam_masuk = $_POST['jam_masuk'];
    $jam_pulang = $_POST['jam_pulang'];
    $toleransi = (int)$_POST['toleransi_telat_menit'];

    $update_stmt = $conn->prepare("UPDATE tbl_jam_kerja SET jam_masuk = ?, jam_pulang = ?, toleransi_telat_menit = ? WHERE id = ?");
    if ($update_stmt->execute([$jam_masuk, $jam_pulang, $toleransi, $jam_kerja['id']])) {
        $success = "Pengaturan jam kerja berhasil diperbarui.";
        // Refresh data
        $stmt = $conn->query("SELECT * FROM tbl_jam_kerja WHERE id = 1");
        $jam_kerja = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Gagal memperbarui pengaturan.";
    }
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Pengaturan Jam Kerja</h1> -->

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($jam_kerja): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Jam Kerja Standar</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="jam_masuk">Jam Masuk</label>
                                <input type="time" class="form-control" id="jam_masuk" name="jam_masuk" value="<?= htmlspecialchars($jam_kerja['jam_masuk']) ?>" required>
                                <small class="form-text text-muted">Waktu di mana absensi dianggap "Hadir".</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="jam_pulang">Jam Pulang</label>
                                <input type="time" class="form-control" id="jam_pulang" name="jam_pulang" value="<?= htmlspecialchars($jam_kerja['jam_pulang']) ?>" required>
                                <small class="form-text text-muted">Waktu untuk absensi pulang.</small>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="toleransi_telat_menit">Toleransi Keterlambatan (Menit)</label>
                                <input type="number" class="form-control" id="toleransi_telat_menit" name="toleransi_telat_menit" value="<?= htmlspecialchars($jam_kerja['toleransi_telat_menit']) ?>" required>
                                <small class="form-text text-muted">Batas waktu (dalam menit) setelah jam masuk di mana absensi masih dianggap "Hadir". Lewat dari ini akan dianggap "Telat".</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                Tidak dapat memuat pengaturan jam kerja. Pastikan tabel <code>tbl_jam_kerja</code> ada dan berisi data.
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../templates/footer.php'; ?>
</div>

<?php include __DIR__ . '/../templates/scripts.php'; ?>
</body>
</html> 