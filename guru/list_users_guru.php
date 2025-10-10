<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$title = 'List User Fingerprint Guru';
$active_page = 'list_users';
$required_role = ($currentUser['role'] ?? '') === 'admin' ? null : 'guru';
$csrfToken = admin_get_csrf_token();

include __DIR__ . '/../templates/layout_start.php';

// Default device parameters
$device_ip = $_POST['device_ip'] ?? '192.168.1.201';
$device_port = isset($_POST['device_port']) ? (int) $_POST['device_port'] : 4370;

// Optional: Connect to device and sync users only on valid POST with CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['device_ip'], $_POST['device_port'])) {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
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
                if ($zk->connect()) {
                    $zk->disableDevice();
                    // Ambil data pengguna dari device
                    $users = $zk->getUser();

                    // Insert/sync ke database
                    if (is_array($users)) {
                        $insertStmt = $conn->prepare("INSERT INTO users (uid, name, role, password) VALUES (:uid, :name, :role, :password)");
                        $existsStmt = $conn->prepare('SELECT 1 FROM users WHERE uid = :uid LIMIT 1');

                        foreach ($users as $key => $user) {
                            $uid = (string) $key;
                            $name = (string) ($user[1] ?? '');
                            $role = ((int) ($user[2] ?? 0) === 0) ? 'User' : 'Admin';
                            $password = (string) ($user[3] ?? '');

                            $existsStmt->execute([':uid' => $uid]);
                            if ($existsStmt->fetchColumn()) {
                                continue;
                            }

                            $insertStmt->execute([
                                ':uid' => $uid,
                                ':name' => $name,
                                ':role' => $role,
                                ':password' => $password,
                            ]);
                        }
                    }

                    // Aktifkan kembali device dan putuskan koneksi
                    $zk->enableDevice();
                    $zk->disconnect();

                    $connected_ip_message = 'Terhubung ke perangkat dengan IP: ' . htmlspecialchars($device_ip, ENT_QUOTES, 'UTF-8');
                    $connected_ip_alert_class = 'alert-info';
                } else {
                    $message = 'Gagal terhubung ke perangkat fingerprint.';
                    $alert_class = 'alert-warning';
                }
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
$pageParam = $_GET['page'] ?? '1';
$page = ctype_digit((string) $pageParam) ? (int) $pageParam : 1;
if ($page < 1) {
    $page = 1;
}
$limit = 10;
$offset = ($page - 1) * $limit;

// Ambil data dari tabel users untuk ditampilkan
$dbUsers = [];
$totalPages = 1;
try {
    $stmt = $conn->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = (int) $conn->query('SELECT FOUND_ROWS()')->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $limit));
} catch (PDOException $e) {
    $message = 'Database error: ' . $e->getMessage();
    $alert_class = 'alert-danger';
}

// Default alert info if not set by sync
if (!isset($connected_ip_message)) {
    $connected_ip_message = 'Siapkan koneksi ke perangkat dengan IP: ' . htmlspecialchars($device_ip, ENT_QUOTES, 'UTF-8');
    $connected_ip_alert_class = 'alert-secondary';
}

// Cek status dari query string
$statusMap = [
    'add_success' => ['message' => 'Data user berhasil ditambahkan.', 'class' => 'alert-success'],
    'edit_success' => ['message' => 'Data user berhasil diperbarui.', 'class' => 'alert-warning'],
    'error' => ['message' => 'Terjadi kesalahan saat memproses data.', 'class' => 'alert-danger'],
];
$status = (string) ($_GET['status'] ?? '');
if (!empty($status) && isset($statusMap[$status])) {
    $message = $statusMap[$status]['message'];
    $alert_class = $statusMap[$status]['class'];
} elseif (empty($message)) {
    $alert_class = $alert_class ?? '';
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- Form to input device IP and port -->
            <form method="POST" action="" class="py-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
                <div class="alert <?= htmlspecialchars($alert_class, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
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
                                    <?php if (!empty($dbUsers)): ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php foreach ($dbUsers as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $no++, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($user['nama_guru'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($user['nip'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($user['uid'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($user['name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($user['role'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($user['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada data pengguna fingerprint.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <!-- Dynamic Pagination -->
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Previous</span>
                                        </li>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
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

<?php include __DIR__ . '/../templates/footer.php'; ?>
<?php include __DIR__ . '/../templates/scripts.php'; ?>
<?php include __DIR__ . '/../templates/layout_end.php'; ?>
