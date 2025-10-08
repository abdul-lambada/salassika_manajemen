<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "View Logs";
$active_page = 'view_logs';
include '../../templates/header.php';
include '../../templates/sidebar.php';

$message = '';
$alert_class = '';
$log_content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_log'])) {
        $log_file = $_POST['log_file'];
        $log_path = __DIR__ . '/../../logs/' . basename($log_file);
        if (file_exists($log_path) && file_put_contents($log_path, '') !== false) {
            $message = "Log file berhasil dibersihkan.";
            $alert_class = 'alert-success';
        } else {
            $message = "Gagal membersihkan log file.";
            $alert_class = 'alert-danger';
        }
    }
}
// Get log file
$log_file = isset($_GET['file']) ? $_GET['file'] : 'cron_sync.log';
$log_path = __DIR__ . '/../../logs/' . basename($log_file);
if (file_exists($log_path)) {
    $log_content = file_get_contents($log_path);
    $log_size = filesize($log_path);
    $log_modified = date('Y-m-d H:i:s', filemtime($log_path));
} else {
    $log_content = "Log file tidak ditemukan.";
    $log_size = 0;
    $log_modified = 'N/A';
}
// Get available log files
$log_dir = __DIR__ . '/../../logs/';
$log_files = [];
if (is_dir($log_dir)) {
    $files = scandir($log_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
            $log_files[] = $file;
        }
    }
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <h1 class="h3 mb-4 text-gray-800">View Logs</h1>
            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
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
                                    <a href="?file=<?php echo urlencode($file); ?>" 
                                       class="list-group-item list-group-item-action <?php echo $file === $log_file ? 'active' : ''; ?>">
                                        <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($file); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <?php if (file_exists($log_path)): ?>
                                <hr>
                                <div class="text-muted small">
                                    <p><strong>File Info:</strong></p>
                                    <p>Size: <?php echo number_format($log_size); ?> bytes</p>
                                    <p>Modified: <?php echo $log_modified; ?></p>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('Yakin ingin membersihkan log file ini?');">
                                    <input type="hidden" name="log_file" value="<?php echo htmlspecialchars($log_file); ?>">
                                    <button type="submit" name="clear_log" class="btn btn-warning btn-sm btn-block">
                                        <i class="fas fa-trash"></i> Clear Log
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Log Content -->
                <div class="col-lg-9">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?php echo htmlspecialchars($log_file); ?>
                            </h6>
                            <div>
                                <button class="btn btn-sm btn-primary" onclick="location.reload()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="downloadLog()">
                                    <i class="fas fa-download"></i> Download
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($log_content)): ?>
                                <div class="log-container" style="max-height: 600px; overflow-y: auto;">
                                    <pre class="bg-dark text-light p-3" style="font-size: 12px; line-height: 1.4;"><?php echo htmlspecialchars($log_content); ?></pre>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                                    <p>Log file kosong atau tidak ditemukan.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Log Statistics -->
            <?php if (file_exists($log_path) && !empty($log_content)): ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Statistik Log</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $lines = explode("\n", $log_content);
                                $total_lines = count($lines);
                                $error_lines = 0;
                                $warning_lines = 0;
                                $success_lines = 0;
                                foreach ($lines as $line) {
                                    if (stripos($line, 'ERROR') !== false) {
                                        $error_lines++;
                                    } elseif (stripos($line, 'WARNING') !== false) {
                                        $warning_lines++;
                                    } elseif (stripos($line, 'SUCCESS') !== false || stripos($line, 'Berhasil') !== false) {
                                        $success_lines++;
                                    }
                                }
                                ?>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-primary"><?php echo $total_lines; ?></h4>
                                            <p class="text-muted">Total Lines</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-success"><?php echo $success_lines; ?></h4>
                                            <p class="text-muted">Success</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-warning"><?php echo $warning_lines; ?></h4>
                                            <p class="text-muted">Warning</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-danger"><?php echo $error_lines; ?></h4>
                                            <p class="text-muted">Error</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>
<script>
function downloadLog() {
    const logFile = '<?php echo htmlspecialchars($log_file); ?>';
    const link = document.createElement('a');
    link.href = '../../logs/' + logFile;
    link.download = logFile;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script> 