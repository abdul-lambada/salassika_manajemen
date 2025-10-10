<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$csrfToken = admin_get_csrf_token();

// Ambil daftar jurusan untuk dropdown
$stmt_jurusan = $conn->query("SELECT * FROM jurusan");
$jurusan_list = $stmt_jurusan->fetchAll(PDO::FETCH_ASSOC);

// Proses tambah data kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token CSRF tidak valid.';
        $alertClass = 'alert-danger';
    } else {
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        $id_jurusan = (int)($_POST['id_jurusan'] ?? 0);
        if ($nama_kelas !== '' && $id_jurusan > 0) {
            $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, id_jurusan) VALUES (:nama_kelas, :id_jurusan)");
            $stmt->bindParam(':nama_kelas', $nama_kelas, PDO::PARAM_STR);
            $stmt->bindParam(':id_jurusan', $id_jurusan, PDO::PARAM_INT);
            if ($stmt->execute()) {
                header("Location: list_kelas.php?status=add_success");
                exit();
            }
            $message = 'Gagal menambahkan kelas.';
            $alertClass = 'alert-danger';
        } else {
            $message = 'Nama kelas dan jurusan tidak boleh kosong.';
            $alertClass = 'alert-warning';
        }
    }
}

$alert = [
    'should_display' => isset($message) && $message !== '',
    'message' => $message ?? '',
    'class' => $alertClass ?? 'alert-info',
];

$title = "Tambah Kelas";
$active_page = "list_kelas";
$required_role = 'admin';

include '../../templates/layout_start.php';
?>
        <div class="container-fluid">
            <?php if ($alert['should_display']): ?>
                <?= admin_render_alert($alert); ?>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Kelas</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                <div class="form-group">
                                    <label for="nama_kelas">Nama Kelas:</label>
                                    <input type="text" name="nama_kelas" id="nama_kelas" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="id_jurusan">Jurusan:</label>
                                    <select name="id_jurusan" id="id_jurusan" class="form-control" required>
                                        <option value="">Pilih Jurusan</option>
                                        <?php foreach ($jurusan_list as $jurusan): ?>
                                            <option value="<?= $jurusan['id_jurusan']; ?>">
                                                <?= $jurusan['nama_jurusan']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">Simpan</button>
                                <a href="list_kelas.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php include '../../templates/layout_end.php'; ?>