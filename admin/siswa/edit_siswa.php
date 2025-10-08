<?php
session_start();
include '../../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$title = "Edit Siswa";
$active_page = "list_siswa";

// Validasi parameter ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list_siswa.php?status=error");
    exit;
}

$id_siswa = $_GET['id'];

// Ambil data siswa dan user terkait
$stmt = $conn->prepare("SELECT s.*, u.name AS user_name, u.uid AS user_uid, u.password AS user_password, u.id AS user_id FROM siswa s LEFT JOIN users u ON s.user_id = u.id WHERE s.id_siswa = :id_siswa");
$stmt->bindParam(':id_siswa', $id_siswa);
$stmt->execute();
$siswa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    header("Location: list_siswa.php?status=error");
    exit;
}

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();

        $nisn = $_POST['nisn'];
        $nama_siswa = $_POST['nama_siswa'];
        $uid = $_POST['uid'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $alamat = $_POST['alamat'];
        $id_kelas = $_POST['id_kelas'];
        $nis = $_POST['nis'];
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $siswa['user_password'];

        // Validasi NISN unik
        $check_nisn = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ? AND id_siswa != ?");
        $check_nisn->execute([$nisn, $id_siswa]);
        if ($check_nisn->rowCount() > 0) {
            throw new Exception("NISN sudah digunakan oleh siswa lain");
        }

        // Validasi NIS unik
        $check_nis = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ? AND id_siswa != ?");
        $check_nis->execute([$nis, $id_siswa]);
        if ($check_nis->rowCount() > 0) {
            throw new Exception("NIS sudah digunakan oleh siswa lain");
        }

        // Validasi UID unik di users
        if ($uid !== $siswa['user_uid']) {
            $check_uid = $conn->prepare("SELECT id FROM users WHERE uid = ? AND id != ?");
            $check_uid->execute([$uid, $siswa['user_id']]);
            if ($check_uid->rowCount() > 0) {
                throw new Exception("UID sudah digunakan user lain");
            }
        }

        // Update data siswa
        $stmt = $conn->prepare("
            UPDATE siswa SET 
                nisn = :nisn,
                nis = :nis, 
                jenis_kelamin = :jenis_kelamin, 
                tanggal_lahir = :tanggal_lahir, 
                alamat = :alamat, 
                id_kelas = :id_kelas
            WHERE id_siswa = :id_siswa
        ");
        $stmt->execute([
            ':nisn' => $nisn,
            ':nis' => $nis,
            ':jenis_kelamin' => $jenis_kelamin,
            ':tanggal_lahir' => $tanggal_lahir,
            ':alamat' => $alamat,
            ':id_kelas' => $id_kelas,
            ':id_siswa' => $id_siswa
        ]);

        // Update data di tabel users
        $stmt_user = $conn->prepare("UPDATE users SET name = ?, password = ?, uid = ?, phone = ? WHERE id = ?");
        $stmt_user->execute([$nama_siswa, $password, $uid, $_POST['phone'], $siswa['user_id']]);

        $conn->commit();
        header("Location: list_siswa.php?status=edit_success");
        exit();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}

// Ambil daftar kelas untuk dropdown
$stmt_kelas = $conn->query("SELECT id_kelas, nama_kelas FROM kelas");
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
include '../../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        
        <div class="container-fluid">          
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit Siswa</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>NISN</label>
                            <input type="text" name="nisn" class="form-control" 
                                   value="<?= htmlspecialchars($siswa['nisn']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control" 
                                   value="<?= htmlspecialchars($siswa['user_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>UID (Fingerprint)</label>
                            <select name="uid" id="uid_select" class="form-control" required>
                                <option value="">Pilih UID dari Device Fingerprint</option>
                                <?php
                                require_once '../../includes/zklib/zklibrary.php';
                                include_once '../../includes/fingerprint_config.php';
                                $zk = new ZKLibrary(FINGERPRINT_IP, FINGERPRINT_PORT);
                                $fingerprint_users = [];
                                try {
                                    if ($zk->connect()) {
                                        $zk->disableDevice();
                                        $fingerprint_users = $zk->getUser();
                                        $zk->enableDevice();
                                        $zk->disconnect();
                                    }
                                } catch (Exception $e) {}
                                if (!empty($fingerprint_users)):
                                    foreach ($fingerprint_users as $user_fp):
                                        $role = isset($user_fp[2]) ? strtolower($user_fp[2]) : 'pendaftar';
                                        if ($role !== 'pendaftar') continue;
                                        $selected = ($siswa && $siswa['user_uid'] == $user_fp[0]) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($user_fp[0]) ?>" data-name="<?= htmlspecialchars($user_fp[1]) ?>" data-role="<?= htmlspecialchars($role) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($user_fp[0]) ?> - <?= htmlspecialchars($user_fp[1]) ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                            <small class="form-text text-muted">
                                Pilih UID dari device fingerprint untuk auto-fill data. Jika device tidak tersedia, input manual di bawah.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Password Baru (kosongkan jika tidak ingin diubah)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Nomor WhatsApp:</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($siswa['phone']); ?>" placeholder="Contoh: 08123456789">
                        </div>
                        <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control" 
                                   value="<?= htmlspecialchars($siswa['nis']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control" required>
                                <option value="Laki-laki" <?= ($siswa['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= ($siswa['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" 
                                   value="<?= htmlspecialchars($siswa['tanggal_lahir']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control" required><?php echo htmlspecialchars($siswa['alamat']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="id_kelas" class="form-control" required>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id_kelas'] ?>" 
                                        <?= ($siswa['id_kelas'] == $kelas['id_kelas']) ? 'selected' : '' ?>>
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
    </div>
    
    <?php include '../../templates/footer.php'; ?>
</div>

<!-- JS SB Admin -->
<script src="../../assets/vendor/jquery/jquery.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../../assets/js/sb-admin-2.min.js"></script>

</body>
</html>