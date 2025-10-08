<?php
ob_start();
session_start();
$title = "Edit Guru";
$active_page = "edit_guru";
include '../../templates/header.php';
include '../../templates/sidebar.php';
include '../../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id_guru = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM guru WHERE id_guru = :id_guru");
$stmt->bindParam(':id_guru', $id_guru);
$stmt->execute();
$guru = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data user terkait
$user = null;
if (!empty($guru['user_id'])) {
    $stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->execute([$guru['user_id']]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
}

$message = '';
$alert_class = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        // Ambil data dari form
        $nama_guru = $_POST['nama_guru'];
        $nip = $_POST['nip'];
        $uid = $_POST['uid'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $alamat = $_POST['alamat'];
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];
        // Validasi NIP unik
        $check_nip = $conn->prepare("SELECT id_guru FROM guru WHERE nip = ? AND id_guru != ?");
        $check_nip->execute([$nip, $id_guru]);
        if ($check_nip->rowCount() > 0) {
            throw new Exception("NIP sudah digunakan oleh guru lain");
        }
        // Validasi UID unik di users
        if ($uid !== $user['uid']) {
            $check_uid = $conn->prepare("SELECT id FROM users WHERE uid = ? AND id != ?");
            $check_uid->execute([$uid, $user['id']]);
            if ($check_uid->rowCount() > 0) {
                throw new Exception("UID sudah digunakan user lain");
            }
        }
        // Update data di tabel guru
        $stmt = $conn->prepare("UPDATE guru SET nip = ?, jenis_kelamin = ?, tanggal_lahir = ?, alamat = ? WHERE id_guru = ?");
        $stmt->execute([$nip, $jenis_kelamin, $tanggal_lahir, $alamat, $id_guru]);
        // Update data di tabel users
        $stmt_user = $conn->prepare("UPDATE users SET name = ?, password = ?, uid = ?, phone = ? WHERE id = ?");
        $stmt_user->execute([$nama_guru, $password, $uid, $_POST['phone'], $user['id']]);
        $conn->commit();
        header("Location: list_guru.php?status=edit_success");
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
            <!-- <h1 class="h3 mb-4 text-gray-800">Edit Guru</h1> -->
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
                            <h6 class="m-0 font-weight-bold text-primary">Form Edit Guru</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>Nama Guru:</label>
                                    <input type="text" name="nama_guru" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>NIP:</label>
                                    <input type="text" name="nip" class="form-control" value="<?php echo htmlspecialchars($guru['nip']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>UID (Fingerprint):</label>
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
                                                $privilege = isset($user_fp[2]) ? intval($user_fp[2]) : 0;
                                                if ($privilege !== 0) continue;
                                                $selected = ($user && $user['uid'] == $user_fp[0]) ? 'selected' : '';
                                        ?>
                                            <option value="<?= htmlspecialchars($user_fp[0]) ?>" data-name="<?= htmlspecialchars($user_fp[1]) ?>" data-role="<?= htmlspecialchars($privilege) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($user_fp[0]) ?> - <?= htmlspecialchars($user_fp[1]) ?>
                                            </option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Pilih UID dari device fingerprint untuk auto-fill data. Jika device tidak tersedia, input manual di bawah.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Password (kosongkan jika tidak ingin diubah):</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Nomor WhatsApp:</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Jenis Kelamin:</label>
                                    <select name="jenis_kelamin" class="form-control" required>
                                        <option value="Laki-laki" <?php echo ($guru['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="Perempuan" <?php echo ($guru['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Lahir:</label>
                                    <input type="date" name="tanggal_lahir" class="form-control" value="<?php echo htmlspecialchars($guru['tanggal_lahir']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Alamat:</label>
                                    <textarea name="alamat" class="form-control" required><?php echo htmlspecialchars($guru['alamat']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Nomor WhatsApp:</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Contoh: 08123456789">
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

<?php
ob_end_flush();
?>
