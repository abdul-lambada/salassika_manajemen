<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);
$title = "Manajemen Device Fingerprint";
$active_page = 'manage_devices';
$required_role = 'admin';
$message = '';
$alert_class = '';
$csrfToken = admin_get_csrf_token();

try {
    // Tambah device
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_device'])) {
        if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
            throw new Exception('Token CSRF tidak valid.');
        }
        $ip = trim($_POST['ip']);
        $port = (int)$_POST['port'];
        $nama_lokasi = trim($_POST['nama_lokasi']);
        $keterangan = trim($_POST['keterangan'] ?? '');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) { throw new Exception('IP Address tidak valid'); }
        if ($port < 1 || $port > 65535) { throw new Exception('Port tidak valid'); }
        $stmt = $conn->prepare("INSERT INTO fingerprint_devices (ip, port, nama_lokasi, keterangan, is_active) VALUES (?, ?, ?, ?, 1)");
        if ($stmt->execute([$ip, $port, $nama_lokasi, $keterangan])) {
            $message = 'Device berhasil ditambahkan.';
            $alert_class = 'alert-success';
        } else {
            throw new Exception('Gagal menambah device.');
        }
    }
    // Edit device
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_device'])) {
        if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
            throw new Exception('Token CSRF tidak valid.');
        }
        $id = (int)$_POST['id'];
        $ip = trim($_POST['ip']);
        $port = (int)$_POST['port'];
        $nama_lokasi = trim($_POST['nama_lokasi']);
        $keterangan = trim($_POST['keterangan'] ?? '');
        if ($id <= 0) { throw new Exception('ID tidak valid'); }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) { throw new Exception('IP Address tidak valid'); }
        if ($port < 1 || $port > 65535) { throw new Exception('Port tidak valid'); }
        $stmt = $conn->prepare("UPDATE fingerprint_devices SET ip=?, port=?, nama_lokasi=?, keterangan=? WHERE id=?");
        if ($stmt->execute([$ip, $port, $nama_lokasi, $keterangan, $id])) {
            $message = 'Device berhasil diupdate.';
            $alert_class = 'alert-success';
        } else {
            throw new Exception('Gagal update device.');
        }
    }
    // Hapus device (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_device'])) {
        if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
            throw new Exception('Token CSRF tidak valid.');
        }
        $id = (int)$_POST['id'];
        if ($id <= 0) { throw new Exception('ID tidak valid'); }
        $stmt = $conn->prepare("DELETE FROM fingerprint_devices WHERE id=?");
        if ($stmt->execute([$id])) {
            $message = 'Device berhasil dihapus.';
            $alert_class = 'alert-success';
        } else {
            throw new Exception('Gagal hapus device.');
        }
    }
    // Aktif/nonaktif device (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_device'])) {
        if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
            throw new Exception('Token CSRF tidak valid.');
        }
        $id = (int)$_POST['id'];
        if ($id <= 0) { throw new Exception('ID tidak valid'); }
        $stmt = $conn->prepare("UPDATE fingerprint_devices SET is_active = 1 - is_active WHERE id=?");
        $stmt->execute([$id]);
    }
} catch (Exception $ex) {
    $message = $ex->getMessage();
    $alert_class = 'alert-danger';
}
// Ambil data device
$stmt = $conn->query("SELECT * FROM fingerprint_devices ORDER BY id DESC");
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/layout_start.php';

$alert = [
    'should_display' => $message !== '',
    'class' => $alert_class ?: 'alert-info',
    'message' => $message,
];
?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Manajemen Device Fingerprint</h1> -->
            <?php if ($alert['should_display']): ?>
                <?= admin_render_alert($alert); ?>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3"><b>Tambah Device</b></div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
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
                                                <form method="POST" action="" style="display:inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                                    <button type="submit" name="toggle_device" class="btn btn-sm btn-warning">Aktif/Nonaktif</button>
                                                </form>
                                                <form method="POST" action="" style="display:inline" onsubmit="return confirm('Hapus device ini?')">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                                    <button type="submit" name="hapus_device" class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php include '../../templates/layout_end.php'; ?>