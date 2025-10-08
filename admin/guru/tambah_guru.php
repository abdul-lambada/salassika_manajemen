<?php
session_start();
$title = "Tambah Guru";
$active_page = "tambah_guru";
include '../../templates/header.php';
include '../../templates/sidebar.php';
include '../../includes/db.php';
require '../../includes/zklib/zklibrary.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Ambil data user dari device fingerprint untuk dropdown
$fingerprint_users = [];
include_once '../../includes/fingerprint_config.php';
$device_ip = FINGERPRINT_IP; // Menggunakan IP dari konfigurasi

try {
    $zk = new ZKLibrary($device_ip, 4370);
    if ($zk->connect()) {
        $zk->disableDevice();
        $fingerprint_users = $zk->getUser();
        $zk->enableDevice();
        $zk->disconnect();
    }
} catch (Exception $e) {
    // Jika gagal koneksi, tetap lanjut dengan form manual
}

$message = '';
$alert_class = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        $nama_guru = $_POST['nama_guru'];
        $nip = $_POST['nip'];
        $uid = $_POST['uid'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $alamat = $_POST['alamat'];
        $phone = $_POST['phone']; // Tambahkan input Nomor WhatsApp
        // Validasi NIP unik
        $check_nip = $conn->prepare("SELECT id_guru FROM guru WHERE nip = ?");
        $check_nip->execute(array($nip));
        if ($check_nip->rowCount() > 0) {
            throw new Exception("NIP sudah digunakan");
        }
        // Cek apakah UID sudah ada di users
        $check_uid = $conn->prepare("SELECT id FROM users WHERE uid = ?");
        $check_uid->execute(array($uid));
        $user_id = null;
        if ($row_uid = $check_uid->fetch(PDO::FETCH_ASSOC)) {
            // UID sudah ada, update data user jika perlu
            $user_id = $row_uid['id'];
            $update_user = $conn->prepare("UPDATE users SET name = ?, password = ?, role = 'guru' WHERE id = ?");
            $update_user->execute(array($nama_guru, $password, $user_id));
        } else {
            // UID belum ada, buat user baru
        $stmt_user = $conn->prepare("INSERT INTO users (name, password, role, uid, phone) VALUES (?, ?, 'guru', ?, ?)");
        $stmt_user->execute(array($nama_guru, $password, $uid, $phone));
        $user_id = $conn->lastInsertId();
        }
        // Cek apakah user_id sudah termapping ke guru lain
        $check_map = $conn->prepare("SELECT id_guru FROM guru WHERE user_id = ?");
        $check_map->execute([$user_id]);
        if ($check_map->rowCount() > 0) {
            throw new Exception("UID sudah digunakan guru lain");
        }
        // Simpan data ke tabel guru dengan user_id
        $stmt = $conn->prepare("INSERT INTO guru (nip, jenis_kelamin, tanggal_lahir, alamat, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array($nip, $jenis_kelamin, $tanggal_lahir, $alamat, $user_id));
        $conn->commit();
        header("Location: list_guru.php?status=add_success");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $message = $e->getMessage();
        $alert_class = 'alert-danger';
    }
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Tambah Guru</h1> -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Guru</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>UID (Fingerprint):</label>
                                    <select name="uid" id="uid_select" class="form-control" required>
                                        <option value="">Pilih UID dari Device Fingerprint</option>
                                        <?php if (!empty($fingerprint_users)): ?>
                                            <?php foreach ($fingerprint_users as $user): ?>
                                                <?php
                                                    // Filter hanya privilege 0 (User)
                                                    $privilege = isset($user[2]) ? intval($user[2]) : 0;
                                                    if ($privilege !== 0) continue;
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
    <?php include '../../templates/footer.php'; ?>
</div>

<?php include '../../templates/scripts.php'; ?>

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