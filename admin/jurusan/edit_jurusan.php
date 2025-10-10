<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$title = "Edit Jurusan";
$active_page = "list_jurusan";
$required_role = 'admin';
$csrfToken = admin_get_csrf_token();

$id_jurusan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_jurusan <= 0) {
    header('Location: list_jurusan.php?status=error&message=' . urlencode('Jurusan tidak ditemukan.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token CSRF tidak valid.';
        $alertClass = 'alert-danger';
    } else {
        $nama_jurusan = trim($_POST['nama_jurusan'] ?? '');
        if ($nama_jurusan !== '') {
            $stmt = $conn->prepare("UPDATE jurusan SET nama_jurusan = ? WHERE id_jurusan = ?");
            if ($stmt->execute([$nama_jurusan, $id_jurusan])) {
                header('Location: list_jurusan.php?status=edit_success');
                exit;
            }
            $message = 'Gagal mengedit jurusan.';
            $alertClass = 'alert-danger';
        } else {
            $message = 'Nama jurusan tidak boleh kosong.';
            $alertClass = 'alert-warning';
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM jurusan WHERE id_jurusan = ?");
$stmt->execute([$id_jurusan]);
$jurusan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$jurusan) {
    header('Location: list_jurusan.php?status=error');
    exit;
}

$statusMap = [];
$alert = [
    'should_display' => isset($message) && $message !== '',
    'message' => $message ?? '',
    'class' => $alertClass ?? 'alert-info',
];

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
                            <h6 class="m-0 font-weight-bold text-primary">Form Edit Jurusan</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                <div class="form-group">
                                    <label for="nama_jurusan">Nama Jurusan</label>
                                    <input type="text" class="form-control" id="nama_jurusan" name="nama_jurusan" value="<?= htmlspecialchars($jurusan['nama_jurusan']); ?>" required>
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