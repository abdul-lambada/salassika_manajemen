<?php
session_start();
// Load global configs
if (file_exists(__DIR__ . '/../includes/config.php')) {
    include __DIR__ . '/../includes/config.php';
}
if (file_exists(__DIR__ . '/../config/production.php')) {
    include __DIR__ . '/../config/production.php';
}

$title = "List User Fingerprint Guru";
$active_page = "list_users"; // Untuk menandai menu aktif di sidebar

// Enforce auth (guru/admin)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['guru','admin'])) {
    if (defined('APP_URL')) {
        header('Location: ' . APP_URL . '/auth/login.php');
    } else {
        header('Location: ../auth/login.php');
    }
    exit;
}

include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Default device parameters
$device_ip = isset($_POST['device_ip']) ? $_POST['device_ip'] : '192.168.1.201';
$device_port = isset($_POST['device_port']) ? (int)$_POST['device_port'] : 4370;

// CSRF token for POST actions
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Koneksi ke database
include '../includes/db.php';

// Optional: Connect to device and sync users only on valid POST with CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['device_ip'], $_POST['device_port'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Invalid CSRF token.';
        $alert_class = 'alert-danger';
    } else {
        // Validate IP and port
        $valid_ip = filter_var($device_ip, FILTER_VALIDATE_IP);
        $valid_port = ($device_port >= 1 && $device_port <= 65535);
        if ($valid_ip && $valid_port) {
            require '../includes/zklib/zklibrary.php';
            try {
                $zk = new ZKLibrary($device_ip, $device_port);
                $zk->connect();
                $zk->disableDevice();
                // Ambil data pengguna dari device
                $users = $zk->getUser();

                // Insert/sync ke database
                foreach ($users as $key => $user) {
                    $uid = $key;
                    $id = $user[0];
                    $name = $user[1];
                    $role = $user[2] == 0 ? 'User' : 'Admin';
                    $password = $user[3];

                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE uid = :uid");
                    $stmt->execute(['uid' => $uid]);
                    $exists = $stmt->fetchColumn();
                    if (!$exists) {
                        $insertStmt = $conn->prepare("INSERT INTO users (uid, name, role, password) VALUES (:uid, :name, :role, :password)");
                        $insertStmt->execute([
                            'uid' => $uid,
                            'name' => $name,
                            'role' => $role,
                            'password' => $password
                        ]);
                    }
                }

                // Aktifkan kembali device dan putuskan koneksi
                $zk->enableDevice();
                $zk->disconnect();

                $connected_ip_message = "Terhubung ke perangkat dengan IP: " . htmlspecialchars($device_ip);
                $connected_ip_alert_class = 'alert-info';
            } catch (Exception $e) {
                $message = 'Gagal koneksi ke device: ' . $e->getMessage();
                $alert_class = 'alert-danger';
            }
        } else {
            $message = 'IP atau port tidak valid.';
            $alert_class = 'alert-danger';
        }
    }
}

// Pagination: retrieve current page and set limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Ambil data dari tabel users untuk ditampilkan
try {
    $stmt = $conn->query("SELECT SQL_CALC_FOUND_ROWS * FROM users LIMIT $limit OFFSET $offset");
    $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of rows and compute total pages
    $total = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
    $totalPages = ceil($total / $limit);
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}

// Default alert info if not set by sync
if (!isset($connected_ip_message)) {
    $connected_ip_message = "Siapkan koneksi ke perangkat dengan IP: " . htmlspecialchars($device_ip);
    $connected_ip_alert_class = 'alert-secondary';
}

// Cek status dari query string
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = '';
switch ($status) {
    case 'add_success':
        $message = 'Data user berhasil ditambahkan.';
        $alert_class = 'alert-success';
        break;
    case 'edit_success':
        $message = 'Data user berhasil diperbarui.';
        $alert_class = 'alert-warning';
        break;
    case 'error':
        $message = 'Terjadi kesalahan saat memproses data.';
        $alert_class = 'alert-danger';
        break;
    default:
        $message = '';
        $alert_class = '';
        break;
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- Form to input device IP and port -->
            <form method="POST" action="" class="py-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="device_ip">Device IP:</label>
                    <input type="text" class="form-control" id="device_ip" name="device_ip" value="<?php echo htmlspecialchars($device_ip); ?>" required>
                </div>
                <div class="form-group">
                    <label for="device_port">Device Port:</label>
                    <input type="number" min="1" max="65535" class="form-control" id="device_port" name="device_port" value="<?php echo htmlspecialchars($device_port); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Connect</button>
            </form>
            <!-- End form -->

            <!-- Begin Alert for Connected IP -->
            <div class="alert <?php echo $connected_ip_alert_class; ?> alert-dismissible fade show" role="alert">
                <?php echo $connected_ip_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- End Alert for Connected IP -->

            <!-- Begin Alert SB Admin 2 -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <!-- End Alert SB Admin 2 -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Siswa</h6>
                        </div>
                        <div class="card-body table-responsive-sm">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Guru</th>
                                        <th>NIP</th>
                                        <th>UID</th>
                                        <th>Nama User</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($dbUsers as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($no++); ?></td>
                                            <td><?php echo htmlspecialchars($user['nama_guru']); ?></td>
                                            <td><?php echo htmlspecialchars($user['nip']); ?></td>
                                            <td><?php echo htmlspecialchars($user['uid']); ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <!-- Dynamic Pagination -->
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Previous</span>
                                        </li>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Next</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Hapus Data</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Apakah Kamu Yakin, Akan Menghapus Data Ini.!</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="hapus_users.php?id=<?php echo $users['id']; ?>">Hapus</a>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../templates/footer.php'; ?>
</div>

<script>
    // Handle modal delete button dynamically
    $('#logoutModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var userId = button.data('id'); // Extract info from data-* attributes
        var modal = $(this);
        modal.find('.delete-user-btn').attr('href', 'hapus_user.php?id=' + userId);
    });
</script>
