<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$title = 'View Logs';
$active_page = 'view_logs';
$required_role = 'admin';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

$message = '';
$alert_class = '';
$log_content = '';

$logDir = realpath(__DIR__ . '/../../logs');
if ($logDir === false) {
    $logDir = __DIR__ . '/../../logs';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $message = 'Token CSRF tidak valid.';
        $alert_class = 'alert-danger';
    } else {
        $log_file = basename($_POST['log_file'] ?? '');
        $log_path = realpath($logDir . DIRECTORY_SEPARATOR . $log_file);

        if ($log_path && strpos($log_path, $logDir) === 0 && is_writable($log_path)) {
            if (file_put_contents($log_path, '') !== false) {
                $message = 'Log file berhasil dibersihkan.';
                $alert_class = 'alert-success';
            } else {
                $message = 'Gagal membersihkan log file.';
                $alert_class = 'alert-danger';
            }
        } else {
            $message = 'Log file tidak valid.';
            $alert_class = 'alert-danger';
        }
    }
}

$requestedFile = isset($_GET['file']) ? basename($_GET['file']) : 'cron_sync.log';
$logPath = realpath($logDir . DIRECTORY_SEPARATOR . $requestedFile);

if ($logPath && strpos($logPath, $logDir) === 0 && is_file($logPath)) {
    $log_content = file_get_contents($logPath);
    $log_size = filesize($logPath);
    $log_modified = date('Y-m-d H:i:s', filemtime($logPath));
} else {
    $log_content = 'Log file tidak ditemukan.';
    $log_size = 0;
    $log_modified = 'N/A';
}

$log_files = [];
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
            $log_files[] = $file;
        }
    }
}

$alert = [
    'should_display' => !empty($message),
    'class' => $alert_class ?: 'alert-info',
    'message' => $message,
];

include '../../templates/layout_start.php';
?>
        <div class="container-fluid">
            <h1 class="h3 mb-4 text-gray-800">View Logs</h1>
            <?php if ($alert['should_display']): ?>
                <?= admin_render_alert($alert); ?>
            <?php endif; ?>
            <div class="row">
                <!-- Log File Selector -->
                <div class="col-lg-3">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Pilih Log File</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($log_files as $file): ?>
                                    <a href="?file=<?= urlencode($file); ?>"
                                       class="list-group-item list-group-item-action <?= $file === $requestedFile ? 'active' : ''; ?>">
                                        <i class="fas fa-file-alt"></i> <?= htmlspecialchars($file); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($logPath && strpos($logPath, $logDir) === 0 && is_file($logPath)): ?>
                                <hr>
                                <div class="text-muted small">
                                    <p><strong>File Info:</strong></p>
                                    <p>Size: <?= number_format($log_size); ?> bytes</p>
                                    <p>Modified: <?= $log_modified; ?></p>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('Yakin ingin membersihkan log file ini?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="log_file" value="<?= htmlspecialchars($requestedFile); ?>">
                                    <button type="submit" name="clear_log" class="btn btn-warning btn-sm btn-block">
                                        <i class="fas fa-trash"></i> Clear Log
                                    </button>
                                </form>
                            <?php endif; ?>
                    </div>
                </div>
                <!-- Log Content -->
                <div class="col-lg-9">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?= htmlspecialchars($requestedFile); ?>
                            </h6>
                            <div>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-primary" type="button" onclick="location.reload()">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                    <button class="btn btn-sm btn-secondary" type="button" onclick="downloadLog()">
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($log_content)): ?>
                                <div class="log-container" style="max-height: 600px; overflow-y: auto;">
                                    <pre class="bg-dark text-light p-3" style="font-size: 12px; line-height: 1.4;"><?= htmlspecialchars($log_content); ?></pre>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php include '../../templates/layout_end.php'; ?>
<script>
function downloadLog() {
    const logFile = '<?= htmlspecialchars($requestedFile); ?>';
    const link = document.createElement('a');
    link.href = '../../logs/' + logFile;
    link.download = logFile;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
setInterval(function() {
    location.reload();
}, 30000);
</script>