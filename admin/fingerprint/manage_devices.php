<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
include '../../includes/db.php';
$title = "Manajemen Device Fingerprint";
$active_page = 'manage_devices';
$message = '';
$alert_class = '';

// Tambah device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_device'])) {
    $ip = $_POST['ip'];
    $port = $_POST['port'];
    $nama_lokasi = $_POST['nama_lokasi'];
    $keterangan = $_POST['keterangan'];
    $stmt = $conn->prepare("INSERT INTO fingerprint_devices (ip, port, nama_lokasi, keterangan, is_active) VALUES (?, ?, ?, ?, 1)");
    if ($stmt->execute([$ip, $port, $nama_lokasi, $keterangan])) {
        $message = 'Device berhasil ditambahkan.';
        $alert_class = 'alert-success';
    } else {
        $message = 'Gagal menambah device.';
        $alert_class = 'alert-danger';
    }
}
// Edit device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_device'])) {
    $id = $_POST['id'];
    $ip = $_POST['ip'];
    $port = $_POST['port'];
    $nama_lokasi = $_POST['nama_lokasi'];
    $keterangan = $_POST['keterangan'];
    $stmt = $conn->prepare("UPDATE fingerprint_devices SET ip=?, port=?, nama_lokasi=?, keterangan=? WHERE id=?");
    if ($stmt->execute([$ip, $port, $nama_lokasi, $keterangan, $id])) {
        $message = 'Device berhasil diupdate.';
        $alert_class = 'alert-success';
    } else {
        $message = 'Gagal update device.';
        $alert_class = 'alert-danger';
    }
}
// Hapus device
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM fingerprint_devices WHERE id=?");
    if ($stmt->execute([$id])) {
        $message = 'Device berhasil dihapus.';
        $alert_class = 'alert-success';
    } else {
        $message = 'Gagal hapus device.';
        $alert_class = 'alert-danger';
    }
}
// Aktif/nonaktif device
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $conn->prepare("UPDATE fingerprint_devices SET is_active = 1 - is_active WHERE id=?");
    $stmt->execute([$id]);
}
// Ambil data device
$stmt = $conn->query("SELECT * FROM fingerprint_devices ORDER BY id DESC");
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/header.php';
include '../../templates/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Manajemen Device Fingerprint</h1> -->
            <?php if ($message): ?>
                <div class="alert <?= $alert_class ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><b>Tambah Device</b></div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>IP Address</label>
                                    <input type="text" name="ip" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="port" class="form-control" value="4370" required>
                                </div>
                                <div class="form-group">
                                    <label>Nama Lokasi</label>
                                    <input type="text" name="nama_lokasi" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control">
                                </div>
                                <button type="submit" name="tambah_device" class="btn btn-primary">Tambah</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><b>Daftar Device</b></div>
                        <div class="card-body table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>IP</th>
                                        <th>Port</th>
                                        <th>Lokasi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $d): ?>
                                        <tr>
                                            <td><?= $d['id'] ?></td>
                                            <td><?= htmlspecialchars($d['ip']) ?></td>
                                            <td><?= htmlspecialchars($d['port']) ?></td>
                                            <td><?= htmlspecialchars($d['nama_lokasi']) ?></td>
                                            <td>
                                                <?php if ($d['is_active']): ?>
                                                    <span class="badge badge-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?toggle=<?= $d['id'] ?>" class="btn btn-sm btn-warning">Aktif/Nonaktif</a>
                                                <a href="?hapus=<?= $d['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus device ini?')">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>
<?php include '../../templates/scripts.php'; ?>
</body>
</html> 