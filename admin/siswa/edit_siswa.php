<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/zklib/zklibrary.php';

$currentUser = admin_require_auth(['admin']);

$csrfToken = admin_get_csrf_token();

$title = "Edit Siswa";
$active_page = "list_siswa";
$required_role = 'admin';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list_siswa.php?status=error");
    exit;
}

$id_siswa = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT s.*, u.name AS user_name, u.uid AS user_uid, u.password AS user_password, u.id AS user_id, u.phone AS user_phone FROM siswa s LEFT JOIN users u ON s.user_id = u.id WHERE s.id_siswa = :id_siswa");
$stmt->bindParam(':id_siswa', $id_siswa, PDO::PARAM_INT);
$stmt->execute();
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    header("Location: list_siswa.php?status=error");
    exit;
}

$fingerprint_users = admin_fetch_fingerprint_users();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'Token CSRF tidak valid.';
        $alertClass = 'alert-danger';
    } else {
        try {
            $conn->beginTransaction();

            $nisn = trim($_POST['nisn'] ?? '');
            $nama_siswa = trim($_POST['nama_siswa'] ?? '');
            $uid = trim($_POST['uid'] ?? '');
            $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
            $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
            $alamat = trim($_POST['alamat'] ?? '');
            $id_kelas = (int)($_POST['id_kelas'] ?? 0);
            $nis = trim($_POST['nis'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $newPassword = $_POST['password'] ?? '';

            if ($nama_siswa === '' || $nis === '' || $id_kelas <= 0) {
                throw new Exception('Nama siswa, NIS, dan kelas wajib diisi.');
            }

            $check_nisn = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ? AND id_siswa != ?");
            $check_nisn->execute([$nisn, $id_siswa]);
            if ($nisn !== '' && $check_nisn->rowCount() > 0) {
                throw new Exception('NISN sudah digunakan oleh siswa lain.');
            }

            $check_nis = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ? AND id_siswa != ?");
            $check_nis->execute([$nis, $id_siswa]);
            if ($check_nis->rowCount() > 0) {
                throw new Exception('NIS sudah digunakan oleh siswa lain.');
            }

            if ($uid !== '' && $uid !== ($siswa['user_uid'] ?? '')) {
                $check_uid = $conn->prepare("SELECT id FROM users WHERE uid = ? AND id != ?");
                $check_uid->execute([$uid, $siswa['user_id']]);
                if ($check_uid->rowCount() > 0) {
                    throw new Exception('UID sudah digunakan user lain.');
                }
            }

            $stmtUpdate = $conn->prepare("UPDATE siswa SET nisn = :nisn, nis = :nis, jenis_kelamin = :jk, tanggal_lahir = :tgl, alamat = :alamat, id_kelas = :kelas WHERE id_siswa = :id");
            $stmtUpdate->execute([
                ':nisn' => $nisn,
                ':nis' => $nis,
                ':jk' => $jenis_kelamin,
                ':tgl' => $tanggal_lahir,
                ':alamat' => $alamat,
                ':kelas' => $id_kelas,
                ':id' => $id_siswa,
            ]);

            if (!empty($siswa['user_id'])) {
                $passwordHash = $siswa['user_password'];
                if ($newPassword !== '') {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                $stmtUser = $conn->prepare("UPDATE users SET name = ?, password = ?, uid = ?, phone = ? WHERE id = ?");
                $stmtUser->execute([$nama_siswa, $passwordHash, $uid ?: null, $phone, $siswa['user_id']]);
            }

            $conn->commit();
            header("Location: list_siswa.php?status=edit_success");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $message = $e->getMessage();
            $alertClass = 'alert-danger';
        }
    }
}

$stmt_kelas = $conn->query("SELECT id_kelas, nama_kelas FROM kelas");
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$alert = [
    'should_display' => isset($message) && $message !== '',
    'message' => $message ?? '',
    'class' => $alertClass ?? 'alert-info',
];

include '../../templates/layout_start.php';
?>
        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit Siswa</h6>
                </div>
                <div class="card-body">
                    <?php if ($alert['should_display']): ?>
                        <?= admin_render_alert($alert); ?>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                        <div class="form-group">
                            <label>NISN</label>
                            <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($siswa['nisn']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control" value="<?= htmlspecialchars($siswa['user_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>UID (Fingerprint)</label>
                            <select name="uid" id="uid_select" class="form-control">
                                <option value="">Pilih UID dari Device Fingerprint</option>
                                <?php foreach ($fingerprint_users as $user_fp): ?>
                                    <?php
                                        $roleRaw = $user_fp[2] ?? '';
                                        $roleLower = strtolower((string)$roleRaw);
                                        $roleInt = (int)$roleRaw;
                                        if ($roleLower !== 'pendaftar' && !in_array($roleInt, [2, 14, 15], true)) {
                                            continue;
                                        }
                                        $selected = ($siswa['user_uid'] ?? '') === $user_fp[0] ? 'selected' : '';
                                    ?>
                                    <option value="<?= htmlspecialchars($user_fp[0]) ?>" data-name="<?= htmlspecialchars($user_fp[1]) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($user_fp[0]) ?> - <?= htmlspecialchars($user_fp[1]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Biarkan kosong jika tidak menggunakan UID fingerprint.</small>
                        </div>
                        <div class="form-group">
                            <label>Password Baru (opsional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
                        </div>
                        <div class="form-group">
                            <label>Nomor WhatsApp</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($siswa['user_phone'] ?? '') ?>" placeholder="Contoh: 08123456789">
                        </div>
                        <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($siswa['nis']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control" required>
                                <option value="Laki-laki" <?= ($siswa['jenis_kelamin'] === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= ($siswa['jenis_kelamin'] === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($siswa['tanggal_lahir']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control" required><?= htmlspecialchars($siswa['alamat']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="id_kelas" class="form-control" required>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id_kelas'] ?>" <?= ((int)$siswa['id_kelas'] === (int)$kelas['id_kelas']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="list_siswa.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
<?php include '../../templates/layout_end.php'; ?>