<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/zklib/zklibrary.php';

$currentUser = admin_require_auth(['admin']);

$csrfToken = admin_get_csrf_token();

$title = "Tambah Siswa";
$active_page = "list_siswa";
$required_role = 'admin';

// Ambil data kelas untuk dropdown
$stmt_kelas = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

// Ambil data dari device fingerprint (optional availability)
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

            if ($nama_siswa === '' || $nis === '' || $id_kelas <= 0) {
                throw new Exception('Nama siswa, NIS, dan kelas wajib diisi.');
            }

            $check_nisn = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ?");
            $check_nisn->execute([$nisn]);
            if ($nisn !== '' && $check_nisn->rowCount() > 0) {
                throw new Exception('NISN sudah digunakan.');
            }

            $check_nis = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
            $check_nis->execute([$nis]);
            if ($check_nis->rowCount() > 0) {
                throw new Exception('NIS sudah digunakan.');
            }

            $password = password_hash('123456', PASSWORD_DEFAULT);
            $user_id = null;

            if ($uid !== '') {
                $check_uid = $conn->prepare("SELECT id FROM users WHERE uid = ?");
                $check_uid->execute([$uid]);
                if ($row_uid = $check_uid->fetch(PDO::FETCH_ASSOC)) {
                    $user_id = (int)$row_uid['id'];
                    $update_user = $conn->prepare("UPDATE users SET name = ?, password = ?, role = 'pendaftar', phone = ? WHERE id = ?");
                    $update_user->execute([$nama_siswa, $password, $phone, $user_id]);
                } else {
                    $stmt_user = $conn->prepare("INSERT INTO users (name, password, role, uid, phone) VALUES (?, ?, 'pendaftar', ?, ?)");
                    $stmt_user->execute([$nama_siswa, $password, $uid, $phone]);
                    $user_id = (int)$conn->lastInsertId();
                }

                $check_map = $conn->prepare("SELECT id_siswa FROM siswa WHERE user_id = ?");
                $check_map->execute([$user_id]);
                if ($check_map->rowCount() > 0) {
                    throw new Exception('UID sudah digunakan siswa lain.');
                }
            } else {
                $stmt_user = $conn->prepare("INSERT INTO users (name, password, role, phone) VALUES (?, ?, 'pendaftar', ?)");
                $stmt_user->execute([$nama_siswa, $password, $phone]);
                $user_id = (int)$conn->lastInsertId();
            }

            $stmt = $conn->prepare("INSERT INTO siswa (nisn, nama_siswa, jenis_kelamin, tanggal_lahir, alamat, id_kelas, nis, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nisn, $nama_siswa, $jenis_kelamin, $tanggal_lahir, $alamat, $id_kelas, $nis, $user_id]);

            $conn->commit();
            header('Location: list_siswa.php?status=add_success');
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $message = $e->getMessage();
            $alertClass = 'alert-danger';
        }
    }
}

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

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Tambah Siswa</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">

                        <div class="form-group">
                            <label>UID (Fingerprint):</label>
                            <select name="uid" id="uid_select" class="form-control">
                                <option value="">Pilih UID dari Device Fingerprint</option>
                                <?php foreach ($fingerprint_users as $user): ?>
                                    <?php
                                        $roleRaw = $user[2] ?? '';
                                        $roleLower = strtolower((string)$roleRaw);
                                        $roleInt = (int)$roleRaw;
                                        if ($roleLower !== 'pendaftar' && !in_array($roleInt, [2, 14, 15], true)) {
                                            continue;
                                        }
                                    ?>
                                    <option value="<?= htmlspecialchars($user[0]) ?>" data-name="<?= htmlspecialchars($user[1]) ?>">
                                        <?= htmlspecialchars($user[0]) ?> - <?= htmlspecialchars($user[1]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Pilih UID dari device fingerprint untuk auto-fill. Jika device tidak tersedia, biarkan kosong.</small>
                            <?php if (empty($fingerprint_users)): ?>
                                <div class="alert alert-warning mt-2 mb-0 p-2" role="alert">
                                    Device fingerprint tidak terhubung atau kosong. Silakan input manual.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama_siswa" id="nama_siswa" class="form-control" value="<?= htmlspecialchars($_POST['nama_siswa'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>NISN</label>
                            <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($_POST['nisn'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Nomor WhatsApp</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="Contoh: 08123456789">
                        </div>

                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki" <?= (($_POST['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= (($_POST['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="id_kelas" class="form-control" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id_kelas'] ?>" <?= ((int)($_POST['id_kelas'] ?? 0) === (int)$kelas['id_kelas']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="list_siswa.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
<?php include '../../templates/layout_end.php'; ?>

<script>
    const uidSelect = document.getElementById('uid_select');
    const namaField = document.getElementById('nama_siswa');
    if (uidSelect) {
        uidSelect.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            if (option && option.value) {
                namaField.value = option.getAttribute('data-name') || '';
                namaField.readOnly = true;
            } else {
                namaField.readOnly = false;
                if (!('<?= htmlspecialchars($_POST['nama_siswa'] ?? '') ?>')) {
                    namaField.value = '';
                }
            }
        });
    }
</script>