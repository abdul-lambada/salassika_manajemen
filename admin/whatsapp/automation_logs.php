<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/wa_util.php';

$currentUser = admin_require_auth(['admin']);

$title = "Log Otomatisasi WhatsApp";
$active_page = "whatsapp_automation_logs";
$required_role = 'admin';
$csrfToken = admin_get_csrf_token();

$alert = ['should_display' => false, 'message' => '', 'class' => 'alert-info'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        $alert = ['should_display' => true, 'message' => 'Token CSRF tidak valid.', 'class' => 'alert-danger'];
    } else {
        try {
            if (isset($_POST['delete_single']) && isset($_POST['log_id'])) {
                $logId = (int)$_POST['log_id'];
                $stmt = $conn->prepare('DELETE FROM whatsapp_automation_logs WHERE id = :log_id');
                $stmt->bindValue(':log_id', $logId, PDO::PARAM_INT);
                $stmt->execute();
                $alert = ['should_display' => true, 'message' => 'Log otomatisasi berhasil dihapus!', 'class' => 'alert-success'];
            } elseif (isset($_POST['delete_all'])) {
                $stmt = $conn->prepare('DELETE FROM whatsapp_automation_logs');
                $stmt->execute();
                $alert = ['should_display' => true, 'message' => 'Semua log otomatisasi berhasil dihapus!', 'class' => 'alert-success'];
            }
        } catch (Throwable $e) {
            $alert = ['should_display' => true, 'message' => 'Error menghapus log: ' . $e->getMessage(), 'class' => 'alert-danger'];
        }
    }
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt_count = $conn->query('SELECT COUNT(*) AS total FROM whatsapp_automation_logs');
$total_logs = (int)$stmt_count->fetchColumn();
$total_pages = max(1, (int)ceil($total_logs / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

$stmt_logs = $conn->prepare('
    SELECT wal.*, u.name AS user_name
    FROM whatsapp_automation_logs wal
    LEFT JOIN users u ON wal.user_id = u.id
    ORDER BY wal.created_at DESC
    LIMIT :limit OFFSET :offset
');
$stmt_logs->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_logs->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

include '../../templates/layout_start.php';
?>
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Log Otomatisasi WhatsApp</h1>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                    <button type="submit" name="delete_all" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus semua log?')">
                        <i class="fas fa-trash mr-1"></i>Hapus Semua Log
                    </button>
                </form>
            </div>

            <?php if ($alert['should_display'] ?? false): ?>
                <?= admin_render_alert($alert); ?>
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
                                            <td><?= htmlspecialchars($log['user_name'] ?? '-') ?></td>
                                            <td><?= ucfirst($log['user_type']); ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $log['attendance_status'])); ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $log['notification_type'])); ?></td>
                                            <td><?= htmlspecialchars($log['recipient_phone']); ?></td>
                                            <td><?= ucfirst($log['recipient_type']); ?></td>
                                            <td><?= htmlspecialchars($log['template_used'] ?: '-'); ?></td>
                                            <td>
                                                <?php if ($log['message_sent']): ?>
                                                    <span class="badge badge-success">Terkirim</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Gagal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($log['error_message'] ?: '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

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
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
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
<?php include '../../templates/layout_end.php'; ?>

<div class="modal fade" id="deleteAllModal" tabindex="-1" role="dialog" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAllModalLabel">Hapus Semua Log</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Peringatan!</strong> Tindakan ini akan menghapus semua log pengiriman WhatsApp. Tindakan ini tidak dapat dibatalkan.
                </div>
                <p>Apakah Anda yakin ingin menghapus semua log pengiriman WhatsApp?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                    <button type="submit" name="delete_all" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus Semua
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
