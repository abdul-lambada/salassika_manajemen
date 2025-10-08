<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "Kelola Pengguna Fingerprint";
$active_page = 'manage_devices';
include '../../templates/header.php';
include '../../templates/sidebar.php';
require '../../includes/zklib/zklibrary.php';
include '../../includes/db.php';

$message = '';
$alert_class = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $user_id = $_POST['user_id'];
                $user_name = $_POST['user_name'];
                $user_type = $_POST['user_type'];
                $device_ip = $_POST['device_ip'];
                $privilege = ($user_type === 'guru') ? 0 : 2;
                try {
                    $zk = new ZKLibrary($device_ip, 4370);
                    if ($zk->connect()) {
                        $zk->disableDevice();
                        $result = $zk->setUser($user_id, $user_id, $user_name, '', $privilege);
                        $zk->enableDevice();
                        $zk->disconnect();
                        if ($result) {
                            $message = "Pengguna berhasil ditambahkan ke fingerprint: $user_name";
                            $alert_class = 'alert-success';
                        } else {
                            $message = "Gagal menambahkan pengguna ke fingerprint";
                            $alert_class = 'alert-danger';
                        }
                    } else {
                        $message = "Gagal terhubung ke perangkat fingerprint";
                        $alert_class = 'alert-danger';
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $alert_class = 'alert-danger';
                }
                break;
            case 'delete_user':
                $user_id = $_POST['user_id'];
                $device_ip = $_POST['device_ip'];
                try {
                    $zk = new ZKLibrary($device_ip, 4370);
                    if ($zk->connect()) {
                        $zk->disableDevice();
                        $result = $zk->deleteUser($user_id);
                        $zk->enableDevice();
                        $zk->disconnect();
                        if ($result) {
                            $message = "Pengguna berhasil dihapus dari fingerprint";
                            $alert_class = 'alert-success';
                        } else {
                            $message = "Gagal menghapus pengguna dari fingerprint";
                            $alert_class = 'alert-danger';
                        }
                    } else {
                        $message = "Gagal terhubung ke perangkat fingerprint";
                        $alert_class = 'alert-danger';
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $alert_class = 'alert-danger';
                }
                break;
            case 'sync_users':
                $device_ip = $_POST['device_ip'];
                try {
                    $zk = new ZKLibrary($device_ip, 4370);
                    if ($zk->connect()) {
                        $zk->disableDevice();
                        $fingerprint_users = $zk->getUser();
                        $zk->enableDevice();
                        $zk->disconnect();
                        $synced_count = 0;
                        $warning_msgs = [];
                        foreach ($fingerprint_users as $uid => $user) {
                            $user_id = $user[0];
                            $user_name = $user[1];
                            $privilege = isset($user[2]) ? $user[2] : 0;
                            if ($privilege == 0) {
                                $role = 'guru';
                            } elseif ($privilege == 2) {
                                $role = 'siswa';
                            } elseif ($privilege == 14 || $privilege == 15) {
                                continue;
                            } else {
                                $role = 'siswa';
                            }
                            $check_stmt = $conn->prepare("SELECT id, name, role FROM users WHERE uid = ?");
                            $check_stmt->execute([$uid]);
                            $user_db = $check_stmt->fetch(PDO::FETCH_ASSOC);
                            if (!$user_db) {
                                // Insert user baru
                                $insert_stmt = $conn->prepare("INSERT INTO users (uid, name, role) VALUES (?, ?, ?)");
                                $insert_stmt->execute([$uid, $user_name, $role]);
                                $user_id_db = $conn->lastInsertId();
                                $synced_count++;
                            } else {
                                $user_id_db = $user_db['id'];
                                // Jika nama/role tidak cocok, update agar konsisten
                                if ($user_db['name'] !== $user_name || $user_db['role'] !== $role) {
                                    $update_stmt = $conn->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
                                    $update_stmt->execute([$user_name, $role, $user_id_db]);
                                }
                            }
                            // Mapping ke guru/siswa
                            if ($role === 'siswa') {
                                $siswa_stmt = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
                                $siswa_stmt->execute([$uid]);
                                $siswa = $siswa_stmt->fetch(PDO::FETCH_ASSOC);
                                if ($siswa) {
                                    $update_stmt = $conn->prepare("UPDATE siswa SET user_id = ? WHERE id_siswa = ? AND (user_id IS NULL OR user_id != ?)");
                                    $update_stmt->execute([$user_id_db, $siswa['id_siswa'], $user_id_db]);
                                } else {
                                    $warning_msgs[] = "UID $uid (Siswa) belum di-mapping ke data siswa manapun.";
                                }
                            } elseif ($role === 'guru') {
                                $guru_stmt = $conn->prepare("SELECT id_guru FROM guru WHERE nip = ?");
                                $guru_stmt->execute([$uid]);
                                $guru = $guru_stmt->fetch(PDO::FETCH_ASSOC);
                                if ($guru) {
                                    $update_stmt = $conn->prepare("UPDATE guru SET user_id = ? WHERE id_guru = ? AND (user_id IS NULL OR user_id != ?)");
                                    $update_stmt->execute([$user_id_db, $guru['id_guru'], $user_id_db]);
                                } else {
                                    $warning_msgs[] = "UID $uid (Guru) belum di-mapping ke data guru manapun.";
                                }
                            }
                        }
                        $message = "Sinkronisasi selesai. $synced_count pengguna baru ditambahkan.";
                        if (!empty($warning_msgs)) {
                            $message .= '<br><b>Warning:</b><br>' . implode('<br>', $warning_msgs);
                        }
                        $alert_class = 'alert-success';
                    } else {
                        $message = "Gagal terhubung ke perangkat fingerprint";
                        $alert_class = 'alert-danger';
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $alert_class = 'alert-danger';
                }
                break;
        }
    }
}
// Tambahkan tombol sinkronisasi semua device di atas form sinkronisasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_all_devices'])) {
    $all_devices_stmt = $conn->query("SELECT * FROM fingerprint_devices WHERE is_active = 1 ORDER BY nama_lokasi, ip");
    $all_devices = $all_devices_stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_synced = 0;
    $all_warnings = [];
    foreach ($all_devices as $dev) {
        $device_ip = $dev['ip'];
        try {
            $zk = new ZKLibrary($device_ip, 4370);
            if ($zk->connect()) {
                $zk->disableDevice();
                $fingerprint_users = $zk->getUser();
                $zk->enableDevice();
                $zk->disconnect();
                $synced_count = 0;
                $warning_msgs = [];
                foreach ($fingerprint_users as $uid => $user) {
                    $user_id = $user[0];
                    $user_name = $user[1];
                    $privilege = isset($user[2]) ? $user[2] : 0;
                    if ($privilege == 0) {
                        $role = 'guru';
                    } elseif ($privilege == 2) {
                        $role = 'siswa';
                    } elseif ($privilege == 14 || $privilege == 15) {
                        continue;
                    } else {
                        $role = 'siswa';
                    }
                    $check_stmt = $conn->prepare("SELECT id, name, role FROM users WHERE uid = ?");
                    $check_stmt->execute([$uid]);
                    $user_db = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$user_db) {
                        // Insert user baru
                        $insert_stmt = $conn->prepare("INSERT INTO users (uid, name, role) VALUES (?, ?, ?)");
                        $insert_stmt->execute([$uid, $user_name, $role]);
                        $user_id_db = $conn->lastInsertId();
                        $synced_count++;
                    } else {
                        $user_id_db = $user_db['id'];
                        // Jika nama/role tidak cocok, update agar konsisten
                        if ($user_db['name'] !== $user_name || $user_db['role'] !== $role) {
                            $update_stmt = $conn->prepare("UPDATE users SET name = ?, role = ? WHERE id = ?");
                            $update_stmt->execute([$user_name, $role, $user_id_db]);
                        }
                    }
                    // Mapping ke guru/siswa
                    if ($role === 'siswa') {
                        $siswa_stmt = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
                        $siswa_stmt->execute([$uid]);
                        $siswa = $siswa_stmt->fetch(PDO::FETCH_ASSOC);
                        if ($siswa) {
                            $update_stmt = $conn->prepare("UPDATE siswa SET user_id = ? WHERE id_siswa = ? AND (user_id IS NULL OR user_id != ?)");
                            $update_stmt->execute([$user_id_db, $siswa['id_siswa'], $user_id_db]);
                        } else {
                            $warning_msgs[] = "UID $uid (Siswa) belum di-mapping ke data siswa manapun.";
                        }
                    } elseif ($role === 'guru') {
                        $guru_stmt = $conn->prepare("SELECT id_guru FROM guru WHERE nip = ?");
                        $guru_stmt->execute([$uid]);
                        $guru = $guru_stmt->fetch(PDO::FETCH_ASSOC);
                        if ($guru) {
                            $update_stmt = $conn->prepare("UPDATE guru SET user_id = ? WHERE id_guru = ? AND (user_id IS NULL OR user_id != ?)");
                            $update_stmt->execute([$user_id_db, $guru['id_guru'], $user_id_db]);
                        } else {
                            $warning_msgs[] = "UID $uid (Guru) belum di-mapping ke data guru manapun.";
                        }
                    }
                }
                $total_synced += $synced_count;
                if (!empty($warning_msgs)) {
                    $all_warnings = array_merge($all_warnings, $warning_msgs);
                }
            } else {
                $all_warnings[] = "Gagal terhubung ke device fingerprint di IP $device_ip.";
            }
        } catch (Exception $e) {
            $all_warnings[] = "Error device $device_ip: " . $e->getMessage();
        }
    }
    $message = "Sinkronisasi semua device selesai. $total_synced pengguna baru ditambahkan.";
    if (!empty($all_warnings)) {
        $message .= '<br><b>Warning:</b><br>' . implode('<br>', $all_warnings);
    }
    $alert_class = 'alert-success';
}
// Handle map/unmap POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'map_guru' && !empty($_POST['id_guru']) && !empty($_POST['uid'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE uid = ?");
        $stmt->execute([$_POST['uid']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $update = $conn->prepare("UPDATE guru SET user_id = ? WHERE id_guru = ?");
            $update->execute([$user['id'], $_POST['id_guru']]);
        }
    } elseif ($_POST['action'] === 'unmap_guru' && !empty($_POST['id_guru'])) {
        $update = $conn->prepare("UPDATE guru SET user_id = NULL WHERE id_guru = ?");
        $update->execute([$_POST['id_guru']]);
    } elseif ($_POST['action'] === 'map_siswa' && !empty($_POST['id_siswa']) && !empty($_POST['uid'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE uid = ?");
        $stmt->execute([$_POST['uid']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $update = $conn->prepare("UPDATE siswa SET user_id = ? WHERE id_siswa = ?");
            $update->execute([$user['id'], $_POST['id_siswa']]);
        }
    } elseif ($_POST['action'] === 'unmap_siswa' && !empty($_POST['id_siswa'])) {
        $update = $conn->prepare("UPDATE siswa SET user_id = NULL WHERE id_siswa = ?");
        $update->execute([$_POST['id_siswa']]);
    }
}
// Ambil data pengguna dari database
$stmt = $conn->query("SELECT * FROM users ORDER BY name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Ambil data siswa dan guru untuk dropdown
$stmt_siswa = $conn->query("SELECT s.id_siswa, s.nama_siswa, s.nis, s.nisn, u.uid FROM siswa s LEFT JOIN users u ON s.user_id = u.id WHERE s.user_id IS NOT NULL ORDER BY s.nama_siswa");
$siswa_list = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);
$stmt_guru = $conn->query("SELECT g.id_guru, g.nama_guru, g.nip, u.uid, u.name AS user_name FROM guru g LEFT JOIN users u ON g.user_id = u.id WHERE g.user_id IS NOT NULL ORDER BY g.nama_guru");
$guru_list = $stmt_guru->fetchAll(PDO::FETCH_ASSOC);
$uid_used = [];
foreach ($siswa_list as $s) { if ($s['uid']) $uid_used[] = $s['uid']; }
foreach ($guru_list as $g) { if ($g['uid']) $uid_used[] = $g['uid']; }
$uid_used = array_unique($uid_used);
$all_uid_stmt = $conn->query("SELECT uid FROM users ORDER BY uid");
$all_uid = $all_uid_stmt->fetchAll(PDO::FETCH_COLUMN);
$uid_available = array_diff($all_uid, $uid_used);
// Ambil daftar device fingerprint aktif
$device_stmt = $conn->query("SELECT * FROM fingerprint_devices WHERE is_active = 1 ORDER BY nama_lokasi, ip");
$device_list = $device_stmt->fetchAll(PDO::FETCH_ASSOC);
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'kelola';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <?php if ($tab === 'sinkronisasi'): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <form method="POST" action="">
                            <button type="submit" name="sync_all_devices" class="btn btn-info">
                                <i class="fas fa-sync-alt"></i> Sinkronisasi Semua Device
                            </button>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Sinkronisasi Pengguna</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="sync_users">
                                    <div class="form-group">
                                        <label for="sync_device_ip">Device Fingerprint:</label>
                                        <select class="form-control" id="sync_device_ip" name="device_ip" required>
                                            <?php foreach ($device_list as $dev): ?>
                                                <option value="<?= htmlspecialchars($dev['ip']) ?>" <?php if (isset($_POST['device_ip']) && $_POST['device_ip'] == $dev['ip']) echo 'selected'; ?>>
                                                    <?= htmlspecialchars($dev['nama_lokasi']) ?> (<?= htmlspecialchars($dev['ip']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success">Sinkronisasi Pengguna</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($tab === 'mapping'): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <ul class="nav nav-tabs mb-3" id="fingerprintTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="guru-tab" data-toggle="tab" href="#guru" role="tab">Daftar Guru</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="siswa-tab" data-toggle="tab" href="#siswa" role="tab">Daftar Siswa</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="fingerprintTabContent">
                            <div class="tab-pane fade show active" id="guru" role="tabpanel">
                                <div class="card mb-4">
                                    <div class="card-header">Guru</div>
                                    <div class="card-body table-responsive-sm">
                                        <table class="table table-bordered">
                                            <thead><tr><th>No</th><th>Nama Guru</th><th>NIP</th><th>UID Fingerprint</th><th>Aksi</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($guru_list as $i => $guru): ?>
                                                <tr>
                                                    <td><?= $i+1 ?></td>
                                                    <td><?php echo htmlspecialchars($guru['nama_guru'] ?: $guru['user_name']); ?></td>
                                                    <td><?= htmlspecialchars($guru['nip']) ?></td>
                                                    <td><?= $guru['uid'] ? htmlspecialchars($guru['uid']) : '-' ?></td>
                                                    <td>
                                                        <?php if ($guru['uid']): ?>
                                                            <form method="POST" action="" style="display:inline;">
                                                                <input type="hidden" name="action" value="unmap_guru">
                                                                <input type="hidden" name="id_guru" value="<?= $guru['id_guru'] ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm">Unmap</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" action="" style="display:inline;">
                                                                <input type="hidden" name="action" value="map_guru">
                                                                <input type="hidden" name="id_guru" value="<?= $guru['id_guru'] ?>">
                                                                <select name="uid" class="form-control form-control-sm d-inline" style="width:auto;display:inline-block;">
                                                                    <option value="">Pilih UID</option>
                                                                    <?php foreach ($uid_available as $uid): ?>
                                                                        <option value="<?= htmlspecialchars($uid) ?>"><?= htmlspecialchars($uid) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <button type="submit" class="btn btn-primary btn-sm">Map</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="siswa" role="tabpanel">
                                <div class="card mb-4">
                                    <div class="card-header">Siswa</div>
                                    <div class="card-body table-responsive-sm">
                                        <table class="table table-bordered">
                                            <thead><tr><th>No</th><th>Nama Siswa</th><th>NIS</th><th>UID Fingerprint</th><th>Aksi</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($siswa_list as $i => $siswa): ?>
                                                <tr>
                                                    <td><?= $i+1 ?></td>
                                                    <td><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                                                    <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                                    <td><?= $siswa['uid'] ? htmlspecialchars($siswa['uid']) : '-' ?></td>
                                                    <td>
                                                        <?php if ($siswa['uid']): ?>
                                                            <form method="POST" action="" style="display:inline;">
                                                                <input type="hidden" name="action" value="unmap_siswa">
                                                                <input type="hidden" name="id_siswa" value="<?= $siswa['id_siswa'] ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm">Unmap</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" action="" style="display:inline;">
                                                                <input type="hidden" name="action" value="map_siswa">
                                                                <input type="hidden" name="id_siswa" value="<?= $siswa['id_siswa'] ?>">
                                                                <select name="uid" class="form-control form-control-sm d-inline" style="width:auto;display:inline-block;">
                                                                    <option value="">Pilih UID</option>
                                                                    <?php foreach ($uid_available as $uid): ?>
                                                                        <option value="<?= htmlspecialchars($uid) ?>"><?= htmlspecialchars($uid) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <button type="submit" class="btn btn-primary btn-sm">Map</button>
                                                            </form>
                                                        <?php endif; ?>
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
            <?php else: ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <form method="POST" action="">
                            <button type="submit" name="sync_all_devices" class="btn btn-info">
                                <i class="fas fa-sync-alt"></i> Sinkronisasi Semua Device
                            </button>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <!-- Form Tambah Pengguna -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Tambah Pengguna Fingerprint</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="add_user">
                                    <div class="form-group">
                                        <label for="device_ip">Device Fingerprint:</label>
                                        <select class="form-control" id="device_ip" name="device_ip" required>
                                            <?php foreach ($device_list as $dev): ?>
                                                <option value="<?= htmlspecialchars($dev['ip']) ?>" <?php if (isset($_POST['device_ip']) && $_POST['device_ip'] == $dev['ip']) echo 'selected'; ?>>
                                                    <?= htmlspecialchars($dev['nama_lokasi']) ?> (<?= htmlspecialchars($dev['ip']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="user_type">Tipe Pengguna:</label>
                                        <select class="form-control" id="user_type" name="user_type" required>
                                            <option value="">Pilih Tipe</option>
                                            <option value="siswa">Siswa</option>
                                            <option value="guru">Guru</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="siswa_select" style="display: none;">
                                        <label for="siswa_id">Pilih Siswa:</label>
                                        <select class="form-control" id="siswa_id" name="siswa_id">
                                            <option value="">Pilih Siswa</option>
                                            <?php foreach ($siswa_list as $siswa): ?>
                                                <option value="<?php echo $siswa['id_siswa']; ?>" data-uid="<?php echo $siswa['uid']; ?>" data-nama="<?php echo $siswa['nama_siswa']; ?>">
                                                    <?php echo $siswa['nama_siswa']; ?> (NIS: <?php echo $siswa['nis']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group" id="guru_select" style="display: none;">
                                        <label for="guru_id">Pilih Guru:</label>
                                        <select class="form-control" id="guru_id" name="guru_id">
                                            <option value="">Pilih Guru</option>
                                            <?php foreach ($guru_list as $guru): ?>
                                                <option value="<?php echo $guru['id_guru']; ?>" data-uid="<?php echo $guru['uid']; ?>" data-nama="<?php echo $guru['nama_guru']; ?>">
                                                    <?php echo $guru['nama_guru']; ?> (NIP: <?php echo $guru['nip']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" id="user_id" name="user_id">
                                    <input type="hidden" id="user_name" name="user_name">
                                    <button type="submit" class="btn btn-primary">Tambah Pengguna</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Sinkronisasi Pengguna -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Sinkronisasi Pengguna</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="sync_users">
                                    <div class="form-group">
                                        <label for="sync_device_ip">Device Fingerprint:</label>
                                        <select class="form-control" id="sync_device_ip" name="device_ip" required>
                                            <?php foreach ($device_list as $dev): ?>
                                                <option value="<?= htmlspecialchars($dev['ip']) ?>" <?php if (isset($_POST['device_ip']) && $_POST['device_ip'] == $dev['ip']) echo 'selected'; ?>>
                                                    <?= htmlspecialchars($dev['nama_lokasi']) ?> (<?= htmlspecialchars($dev['ip']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success">Sinkronisasi Pengguna</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>

<!-- jQuery -->
<script src="../../assets/vendor/jquery/jquery.min.js"></script>
<!-- Bootstrap core JavaScript-->
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
$(function () {
  $('#fingerprintTab a[data-toggle="tab"]').on('click', function (e) {
    e.preventDefault();
    $(this).tab('show');
  });
});
</script>
<script>
document.getElementById('user_type').addEventListener('change', function() {
    const userType = this.value;
    const siswaSelect = document.getElementById('siswa_select');
    const guruSelect = document.getElementById('guru_select');
    if (userType === 'siswa') {
        siswaSelect.style.display = 'block';
        guruSelect.style.display = 'none';
    } else if (userType === 'guru') {
        siswaSelect.style.display = 'none';
        guruSelect.style.display = 'block';
    } else {
        siswaSelect.style.display = 'none';
        guruSelect.style.display = 'none';
    }
});
document.getElementById('siswa_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const uid = selectedOption.getAttribute('data-uid');
        const nama = selectedOption.getAttribute('data-nama');
        document.getElementById('user_id').value = uid;
        document.getElementById('user_name').value = nama;
    }
});
document.getElementById('guru_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const uid = selectedOption.getAttribute('data-uid');
        const nama = selectedOption.getAttribute('data-nama');
        document.getElementById('user_id').value = uid;
        document.getElementById('user_name').value = nama;
    }
});
</script>
