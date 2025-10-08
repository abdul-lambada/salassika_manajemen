<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "Log Otomatisasi WhatsApp";
$active_page = "whatsapp_automation_logs";

include '../../templates/header.php';
include '../../templates/sidebar.php';
include '../../includes/db.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Handle delete logs
if (isset($_GET['action']) && $_GET['action'] === 'delete_logs') {
    try {
        $stmt = $conn->prepare("DELETE FROM whatsapp_automation_logs");
        $stmt->execute();
        $message = 'Log otomatisasi berhasil dihapus!';
        $status = 'success';
    } catch (Exception $e) {
        $message = 'Error menghapus log: ' . $e->getMessage();
        $status = 'danger';
    }
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM whatsapp_automation_logs");
$stmt_count->execute();
$total_logs = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_logs / $limit);

// Get logs with pagination
$stmt_logs = $conn->prepare("
    SELECT wal.*, u.name as user_name
    FROM whatsapp_automation_logs wal
    LEFT JOIN users u ON wal.user_id = u.id
    ORDER BY wal.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt_logs->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_logs->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Log Otomatisasi WhatsApp</h1>
                <div>
                    <a href="?action=delete_logs" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus semua log?')">
                        <i class="fas fa-trash mr-1"></i>Hapus Semua Log
                    </a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $status ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Pengguna</th>
                                    <th>Tipe Pengguna</th>
                                    <th>Status Kehadiran</th>
                                    <th>Tipe Notifikasi</th>
                                    <th>Nomor Tujuan</th>
                                    <th>Tipe Penerima</th>
                                    <th>Template Digunakan</th>
                                    <th>Pesan Terkirim</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">Tidak ada log otomatisasi</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($log['user_name']) ?></td>
                                            <td><?= ucfirst($log['user_type']) ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $log['attendance_status'])) ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $log['notification_type'])) ?></td>
                                            <td><?= htmlspecialchars($log['recipient_phone']) ?></td>
                                            <td><?= ucfirst($log['recipient_type']) ?></td>
                                            <td><?= htmlspecialchars($log['template_used'] ?: '-') ?></td>
                                            <td>
                                                <?php if ($log['message_sent']): ?>
                                                    <span class="badge badge-success">Terkirim</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Gagal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($log['error_message'] ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../../templates/footer.php'; ?>
</div>

<?php include __DIR__ . '/../../templates/scripts.php'; ?>

</body>
</html>
