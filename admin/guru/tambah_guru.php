<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/zklib/zklibrary.php';
require_once __DIR__ . '/../../includes/fingerprint_config.php';

$currentUser = admin_require_auth(['admin']);

$title = 'Tambah Guru';
$active_page = 'tambah_guru';
$required_role = 'admin';

$fingerprint_users = admin_fetch_fingerprint_users();

$csrfToken = admin_get_csrf_token();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token CSRF tidak valid.';
        $alert_class = 'alert-danger';
    } else {
        try {
            $conn->beginTransaction();
            $nama_guru = $_POST['nama_guru'];
            $nip = $_POST['nip'];
            $uid = $_POST['uid'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $jenis_kelamin = $_POST['jenis_kelamin'];
            $tanggal_lahir = $_POST['tanggal_lahir'];
            $alamat = $_POST['alamat'];
            $phone = $_POST['phone'];
            $check_nip = $conn->prepare('SELECT id_guru FROM guru WHERE nip = ?');
            $check_nip->execute([$nip]);
            if ($check_nip->rowCount() > 0) {
                throw new Exception('NIP sudah digunakan');
            }
            $check_uid = $conn->prepare('SELECT id FROM users WHERE uid = ?');
            $check_uid->execute([$uid]);
            $user_id = null;
            if ($row_uid = $check_uid->fetch(PDO::FETCH_ASSOC)) {
                $user_id = $row_uid['id'];
                $update_user = $conn->prepare("UPDATE users SET name = ?, password = ?, role = 'guru' WHERE id = ?");
                $update_user->execute([$nama_guru, $password, $user_id]);
            } else {
                $stmt_user = $conn->prepare("INSERT INTO users (name, password, role, uid, phone) VALUES (?, ?, 'guru', ?, ?)");
                $stmt_user->execute([$nama_guru, $password, $uid, $phone]);
                $user_id = $conn->lastInsertId();
            }
            $check_map = $conn->prepare('SELECT id_guru FROM guru WHERE user_id = ?');
            $check_map->execute([$user_id]);
            if ($check_map->rowCount() > 0) {
                throw new Exception('UID sudah digunakan guru lain');
            }
            $stmt = $conn->prepare('INSERT INTO guru (nip, jenis_kelamin, tanggal_lahir, alamat, user_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nip, $jenis_kelamin, $tanggal_lahir, $alamat, $user_id]);
            $conn->commit();
            header('Location: list_guru.php?status=add_success');
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $message = $e->getMessage();
            $alert_class = 'alert-danger';
        }
    }
}

$alert = [
    'should_display' => !empty($message),
    'class' => $alert_class ?: 'alert-info',
    'message' => $message,
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
                            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Guru</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                <div class="form-group">
                                    <label>UID (Fingerprint):</label>
                                    <select name="uid" id="uid_select" class="form-control" required>
                                        <option value="">Pilih UID dari Device Fingerprint</option>
                                        <?php if (!empty($fingerprint_users)): ?>
                                            <?php foreach ($fingerprint_users as $user): ?>
                                                <?php
                                                // Filter hanya privilege 0 (User)
                                                $privilege = isset($user[2]) ? intval($user[2]) : 0;
                                                if ($privilege !== 0)
                                                    continue;
                                                ?>
                                                <option value="<?= htmlspecialchars($user[0]) ?>" data-name="<?= htmlspecialchars($user[1]) ?>" data-role="<?= htmlspecialchars($privilege) ?>">
                                                    <?= htmlspecialchars($user[0]) ?> - <?= htmlspecialchars($user[1]) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Pilih UID dari device fingerprint untuk auto-fill data. Jika device tidak tersedia, input manual di bawah.
                                    </small>
                                    <?php if (empty($fingerprint_users)): ?>
                                        <div class="alert alert-warning mt-2 mb-0 p-2" role="alert">
                                            Device fingerprint tidak terhubung atau tidak ada data. Silakan input data guru secara manual.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Nama Guru:</label>
                                    <input type="text" name="nama_guru" id="nama_guru" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>NIP:</label>
                                    <input type="text" name="nip" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Password:</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Jenis Kelamin:</label>
                                    <select name="jenis_kelamin" class="form-control" required>
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Lahir:</label>
                                    <input type="date" name="tanggal_lahir" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Alamat:</label>
                                    <textarea name="alamat" class="form-control" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Nomor WhatsApp:</label>
                                    <input type="text" name="phone" class="form-control" placeholder="Contoh: 08123456789">
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="list_guru.php" class="btn btn-secondary">Kembali</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include '../../templates/layout_end.php'; ?>

<script>
    // Auto-fill nama berdasarkan UID yang dipilih
    document.getElementById('uid_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const namaField = document.getElementById('nama_guru');
        if (selectedOption.value) {
            namaField.value = selectedOption.getAttribute('data-name');
            namaField.readOnly = true;
        } else {
            namaField.value = '';
            namaField.readOnly = false;
        }
    });
</script>