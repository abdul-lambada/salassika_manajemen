<?php
session_start();
include '../../includes/db.php';
require '../../includes/zklib/zklibrary.php';

// Redirect jika bukan admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$title = "Tambah Siswa";
$active_page = "list_siswa";

// Ambil data kelas untuk dropdown
$stmt_kelas = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

// Ambil data dari device fingerprint
include_once '../../includes/fingerprint_config.php';
$zk = new ZKLibrary(FINGERPRINT_IP, FINGERPRINT_PORT);
$fingerprint_users = [];
if ($zk->connect()) {
    $zk->disableDevice();
    $fingerprint_users = $zk->getUser();
    $zk->enableDevice();
    $zk->disconnect();
}

$error_message = '';

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
        $phone = $_POST['phone']; // Tambahkan input Nomor WhatsApp
        // Validasi NISN unik
        $check_nisn = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ?");
        $check_nisn->execute(array($nisn));
        if ($check_nisn->rowCount() > 0) {
            throw new Exception("NISN sudah digunakan");
        }
        // Validasi NIS unik
        $check_nis = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
        $check_nis->execute(array($nis));
        if ($check_nis->rowCount() > 0) {
            throw new Exception("NIS sudah digunakan");
        }
        // Cek apakah UID sudah ada di users
        $check_uid = $conn->prepare("SELECT id FROM users WHERE uid = ?");
        $check_uid->execute(array($uid));
        $user_id = null;
        $password = password_hash('123456', PASSWORD_DEFAULT);
        if ($row_uid = $check_uid->fetch(PDO::FETCH_ASSOC)) {
            // UID sudah ada, update data user jika perlu
            $user_id = $row_uid['id'];
            $update_user = $conn->prepare("UPDATE users SET name = ?, password = ?, role = 'pendaftar' WHERE id = ?");
            $update_user->execute(array($nama_siswa, $password, $user_id));
        } else {
            // UID belum ada, buat user baru dengan role pendaftar
            $stmt_user = $conn->prepare("INSERT INTO users (name, password, role, uid, phone) VALUES (?, ?, 'pendaftar', ?, ?)");
            $stmt_user->execute(array($nama_siswa, $password, $uid, $phone));
            $user_id = $conn->lastInsertId();
        }
        // Cek apakah user_id sudah termapping ke siswa lain
        $check_map = $conn->prepare("SELECT id_siswa FROM siswa WHERE user_id = ?");
        $check_map->execute([$user_id]);
        if ($check_map->rowCount() > 0) {
            throw new Exception("UID sudah digunakan siswa lain");
        }
        // Insert ke tabel siswa
        $stmt = $conn->prepare("
            INSERT INTO siswa 
            (nisn, nama_siswa, jenis_kelamin, tanggal_lahir, alamat, id_kelas, nis, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(array(
            $nisn,
            $nama_siswa,
            $jenis_kelamin,
            $tanggal_lahir,
            $alamat,
            $id_kelas,
            $nis,
            $user_id
        ));
        $conn->commit();
        header("Location: list_siswa.php?status=add_success");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}

include '../../templates/header.php';
include '../../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        
        <div class="container-fluid">
            <!-- Page Heading -->
            <!-- <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Tambah Siswa</h1>
            </div> -->
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Tambah Siswa</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>UID (Fingerprint):</label>
                            <select name="uid" id="uid_select" class="form-control" required>
                                <option value="">Pilih UID dari Device Fingerprint</option>
                                <?php if (!empty($fingerprint_users)): ?>
                                    <?php foreach ($fingerprint_users as $user): ?>
                                        <?php
                                            // Filter hanya role/hak 'pendaftar' (string case-insensitive) atau kode role fingerprint X100-C (misal: 2, 14, 15)
                                            $role = isset($user[2]) ? strtolower($user[2]) : '';
                                            $role_int = isset($user[2]) ? intval($user[2]) : -1;
                                            if ($role !== 'pendaftar' && $role_int !== 2 && $role_int !== 14 && $role_int !== 15) continue;
                                        ?>
                                        <option value="<?= htmlspecialchars($user[0]) ?>" data-name="<?= htmlspecialchars($user[1]) ?>" data-role="<?= htmlspecialchars($role) ?>">
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
                                    Device fingerprint tidak terhubung atau tidak ada data. Silakan input data siswa secara manual.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama_siswa" id="nama_siswa" class="form-control" 
                                   value="<?= isset($_POST['nama_siswa']) ? htmlspecialchars($_POST['nama_siswa']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label>NISN</label>
                            <input type="text" name="nisn" class="form-control" 
                                   value="<?= isset($_POST['nisn']) ? htmlspecialchars($_POST['nisn']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control" 
                                   value="<?= isset($_POST['nis']) ? htmlspecialchars($_POST['nis']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nomor WhatsApp:</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" 
                                   value="<?= isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : '' ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Nomor WhatsApp:</label>
                            <input type="text" name="phone" class="form-control" placeholder="Contoh: 08123456789">
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="list_siswa.php" class="btn btn-secondary">Kembali</a>
                        
                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="id_kelas" class="form-control" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id_kelas'] ?>" 
                                        <?= (isset($_POST['id_kelas']) && $_POST['id_kelas'] == $kelas['id_kelas']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan</button>
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

<script>
    // Auto-fill nama berdasarkan UID yang dipilih
    document.getElementById('uid_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const namaField = document.getElementById('nama_siswa');
        
        if (selectedOption.value) {
            namaField.value = selectedOption.getAttribute('data-name');
            namaField.readOnly = true;
        } else {
            namaField.value = '';
            namaField.readOnly = false;
        }
    });
</script>

</body>
</html>