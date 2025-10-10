<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$csrfToken = admin_get_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token CSRF tidak valid.';
        $alertClass = 'alert-danger';
    } else {
        $nama_jurusan = trim($_POST['nama_jurusan'] ?? '');
        if ($nama_jurusan !== '') {
            $stmt = $conn->prepare("INSERT INTO jurusan (nama_jurusan) VALUES (?)");
            if ($stmt->execute([$nama_jurusan])) {
                header('Location: list_jurusan.php?status=add_success');
                exit;
            }
            $message = 'Gagal menambahkan jurusan.';
            $alertClass = 'alert-danger';
        } else {
            $message = 'Nama jurusan tidak boleh kosong.';
            $alertClass = 'alert-warning';
        }
    }
}

$alert = [
    'should_display' => isset($message) && $message !== '',
    'message' => $message ?? '',
    'class' => $alertClass ?? 'alert-info',
];

$title = "Tambah Jurusan";
$active_page = "list_jurusan";
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
                            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Jurusan</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="form-group">
                                    <label for="nama_jurusan">Nama Jurusan</label>
                                    <input type="text" class="form-control" id="nama_jurusan" name="nama_jurusan" required>
                                </div>
                                <button type="submit" class="btn btn-success">Simpan</button>
                                <a href="list_jurusan.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php include '../../templates/layout_end.php'; ?>