<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$title = 'Log Kehadiran';
$active_page = 'log_absensi';
$required_role = ($currentUser['role'] ?? '') === 'admin' ? null : 'guru';

$csrfToken = admin_get_csrf_token();

$pageParam = $_GET['page'] ?? 1;
$page = is_numeric($pageParam) ? max(1, (int) $pageParam) : 1;
$limitParam = $_GET['limit'] ?? 10;
$limit = is_numeric($limitParam) ? max(1, (int) $limitParam) : 10;
$offset = ($page - 1) * $limit;

$ip_address = trim((string) ($_GET['ip'] ?? ''));

$message = '';
$alert_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;
    if (!admin_validate_csrf($submittedToken)) {
        $message = 'Invalid CSRF token.';
        $alert_class = 'alert-danger';
    } else {
        $submittedIp = trim((string) ($_POST['ip_address'] ?? ''));
        if ($submittedIp !== '' && filter_var($submittedIp, FILTER_VALIDATE_IP)) {
            $params = $_GET;
            $params['ip'] = $submittedIp;
            $redirectQuery = http_build_query($params);
            header('Location: log_absensi.php' . ($redirectQuery !== '' ? '?' . $redirectQuery : ''));
            exit;
        }

        $message = 'IP Address tidak valid atau kosong.';
        $alert_class = 'alert-danger';
    }
}

if ($message === '') {
    $status = (string) ($_GET['status'] ?? '');
    if ($status === 'delete_success') {
        $message = 'Data log kehadiran berhasil dihapus.';
        $alert_class = 'alert-success';
    } elseif ($status === 'error') {
        $message = 'Terjadi kesalahan saat menghapus data log kehadiran.';
        $alert_class = 'alert-danger';
    }
}

$log_kehadiran = [];
$totalRows = 0;

$loadLogs = static function () use ($conn, $limit, $offset, &$log_kehadiran, &$totalRows): void {
    $stmt = $conn->prepare(
        'SELECT SQL_CALC_FOUND_ROWS user_id, user_name, timestamp, verification_mode, status
         FROM tbl_kehadiran
         ORDER BY timestamp DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $log_kehadiran = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalStmt = $conn->query('SELECT FOUND_ROWS()');
    if ($totalStmt !== false) {
        $totalRows = (int) $totalStmt->fetchColumn();
    }
};

try {
    $loadLogs();
} catch (PDOException $e) {
    admin_log_message('log_absensi_errors.log', 'Database fetch error: ' . $e->getMessage(), 'ERROR');
    if ($message === '') {
        $message = 'Terjadi kesalahan saat mengambil data log kehadiran.';
        $alert_class = 'alert-danger';
    }
}

if ($ip_address !== '') {
    try {
        require_once __DIR__ . '/../includes/zklib/zklibrary.php';

        $zk = new ZKLibrary($ip_address, 4370);
        if ($zk->connect()) {
            $zk->disableDevice();

            $users = $zk->getUser() ?: [];
            $attendance = $zk->getAttendance() ?: [];

            foreach ($attendance as $entry) {
                $userId = $entry[1] ?? null;
                $rawStatus = $entry[2] ?? null;
                $rawTimestamp = $entry[3] ?? null;
                $rawVerificationMode = $entry[4] ?? null;

                if ($userId === null || $rawTimestamp === null) {
                    continue;
                }

                $timestamp = date('Y-m-d H:i:s', strtotime((string) $rawTimestamp));

                switch ($rawVerificationMode) {
                    case 1:
                        $verificationModeText = 'Fingerprint';
                        break;
                    case 2:
                        $verificationModeText = 'PIN';
                        break;
                    case 3:
                        $verificationModeText = 'Card';
                        break;
                    default:
                        $verificationModeText = 'Unknown';
                        break;
                }

                $userName = 'Unknown';
                if (isset($users[$userId]) && is_array($users[$userId]) && isset($users[$userId][1])) {
                    $userName = (string) $users[$userId][1];
                }

                $statusText = ((int) $rawStatus === 0) ? 'Masuk' : 'Keluar';

                $existsStmt = $conn->prepare(
                    'SELECT COUNT(*) FROM tbl_kehadiran WHERE user_id = :user_id AND timestamp = :timestamp'
                );
                $existsStmt->execute([
                    ':user_id' => $userId,
                    ':timestamp' => $timestamp,
                ]);

                if ((int) $existsStmt->fetchColumn() === 0) {
                    $insertStmt = $conn->prepare(
                        'INSERT INTO tbl_kehadiran (user_id, user_name, timestamp, verification_mode, status)
                         VALUES (:user_id, :user_name, :timestamp, :verification_mode, :status)'
                    );
                    $insertStmt->execute([
                        ':user_id' => $userId,
                        ':user_name' => $userName,
                        ':timestamp' => $timestamp,
                        ':verification_mode' => $verificationModeText,
                        ':status' => $statusText,
                    ]);
                }
            }

            $zk->enableDevice();
            $zk->disconnect();

            if ($message === '') {
                $message = 'Data log kehadiran berhasil diambil dari mesin.';
                $alert_class = 'alert-success';
            }

            try {
                $loadLogs();
            } catch (PDOException $e) {
                admin_log_message('log_absensi_errors.log', 'Database refresh error: ' . $e->getMessage(), 'ERROR');
                if ($message === '' || $alert_class !== 'alert-danger') {
                    $message = 'Terjadi kesalahan saat memperbarui data log kehadiran.';
                    $alert_class = 'alert-danger';
                }
            }
        } else {
            if ($message === '') {
                $message = 'Gagal terhubung ke mesin fingerprint.';
                $alert_class = 'alert-danger';
            }
        }
    } catch (Exception $e) {
        admin_log_message('log_absensi_errors.log', 'Fingerprint fetch error: ' . $e->getMessage(), 'ERROR');
        if ($message === '') {
            $message = 'Terjadi kesalahan saat mengambil data dari mesin fingerprint.';
            $alert_class = 'alert-danger';
        }
    }
}

$totalPages = $limit > 0 ? (int) ceil($totalRows / $limit) : 1;
$totalPages = max(1, $totalPages);

$baseParams = $_GET;
unset($baseParams['page']);
$baseQuery = http_build_query($baseParams);
$queryPrefix = $baseQuery === '' ? '' : $baseQuery . '&';
?>
<?php include __DIR__ . '/../templates/layout_start.php'; ?>
        <div class="container-fluid">
            <?php if ($message !== ''): ?>
                <div class="alert <?= htmlspecialchars($alert_class, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Masukkan IP Mesin Fingerprint</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group row">
                            <label for="ip_address" class="col-sm-2 col-form-label">IP Address:</label>
                            <div class="col-sm-8">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="ip_address"
                                    name="ip_address"
                                    value="<?= htmlspecialchars($ip_address, ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Contoh: 192.168.1.201"
                                >
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" name="submit_ip" class="btn btn-primary btn-block">Ambil Data</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Log Kehadiran</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>User ID</th>
                                    <th>Nama</th>
                                    <th>Tanggal &amp; Waktu</th>
                                    <th>Mode Verifikasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($log_kehadiran)): ?>
                                    <?php foreach ($log_kehadiran as $log): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($log['user_id'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($log['user_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($log['timestamp'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($log['verification_mode'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($log['status'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Tidak ada data log kehadiran.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Navigasi halaman log absensi">
                            <ul class="pagination justify-content-end">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                    <?php if ($page > 1): ?>
                                        <a class="page-link" href="?<?= htmlspecialchars($queryPrefix, ENT_QUOTES, 'UTF-8'); ?>page=<?= $page - 1; ?>">Previous</a>
                                    <?php else: ?>
                                        <span class="page-link">Previous</span>
                                    <?php endif; ?>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $page === $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?= htmlspecialchars($queryPrefix, ENT_QUOTES, 'UTF-8'); ?>page=<?= $i; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <?php if ($page < $totalPages): ?>
                                        <a class="page-link" href="?<?= htmlspecialchars($queryPrefix, ENT_QUOTES, 'UTF-8'); ?>page=<?= $page + 1; ?>">Next</a>
                                    <?php else: ?>
                                        <span class="page-link">Next</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php include __DIR__ . '/../templates/layout_end.php'; ?>
