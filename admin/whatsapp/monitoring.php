<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$title = "Monitoring WhatsApp";
$active_page = "whatsapp_monitoring";
include '../../templates/header.php';
include '../../templates/sidebar.php';

// Koneksi ke database
include '../../includes/db.php';
require_once __DIR__ . '/../../includes/wa_util.php';

$waService = new WhatsAppService($conn);

// Handle delete actions
$message = '';
$alert_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_single']) && isset($_POST['log_id'])) {
        try {
            $logId = (int)$_POST['log_id'];
            $stmt = $conn->prepare("DELETE FROM whatsapp_logs WHERE id = ?");
            $stmt->execute([$logId]);
            $message = 'Log berhasil dihapus!';
            $alert_class = 'alert-success';
        } catch (Exception $e) {
            $message = 'Error menghapus log: ' . $e->getMessage();
            $alert_class = 'alert-danger';
        }
    } elseif (isset($_POST['delete_all'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM whatsapp_logs");
            $stmt->execute();
            $message = 'Semua log berhasil dihapus!';
            $alert_class = 'alert-success';
        } catch (Exception $e) {
            $message = 'Error menghapus semua log: ' . $e->getMessage();
            $alert_class = 'alert-danger';
        }
    } elseif (isset($_POST['delete_selected']) && isset($_POST['selected_logs'])) {
        try {
            $selectedLogs = $_POST['selected_logs'];
            if (!empty($selectedLogs)) {
                $placeholders = str_repeat('?,', count($selectedLogs) - 1) . '?';
                $stmt = $conn->prepare("DELETE FROM whatsapp_logs WHERE id IN ($placeholders)");
                $stmt->execute($selectedLogs);
                $message = count($selectedLogs) . ' log berhasil dihapus!';
                $alert_class = 'alert-success';
            }
        } catch (Exception $e) {
            $message = 'Error menghapus log yang dipilih: ' . $e->getMessage();
            $alert_class = 'alert-danger';
        }
    }
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
$offset = ($page - 1) * $per_page;

// Cek status dari query string
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get total count for pagination
try {
    $countWhereClause = '';
    $countParams = [];
    
    if (!empty($status)) {
        $countWhereClause = 'WHERE status = ?';
        $countParams[] = $status;
    }
    
    $countSql = "SELECT COUNT(*) as total FROM whatsapp_logs $countWhereClause";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $per_page);
    
    // Ensure page is within valid range
    if ($page < 1) $page = 1;
    if ($page > $totalPages) $page = $totalPages;
    
} catch (Exception $e) {
    $totalRecords = 0;
    $totalPages = 1;
    $page = 1;
}

// Ambil data log pengiriman WhatsApp dengan pagination
try {
    $whereClause = '';
    $params = [];
    
    if (!empty($status)) {
        $whereClause = 'WHERE status = ?';
        $params[] = $status;
    }
    
    // Fix: Use LIMIT and OFFSET directly in the query string, not as parameters
    $sql = "SELECT * FROM whatsapp_logs $whereClause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan statement: " . $conn->errorInfo()[2]);
    }
    
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = $waService->getStats(7); // Last 7 days
    
} catch (Exception $e) {
    $message = 'Error mengambil data log: ' . $e->getMessage();
    $alert_class = 'alert-danger';
    $logs = [];
    $stats = [];
}
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- Begin Alert SB Admin 2 -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <!-- End Alert SB Admin 2 -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Monitoring Pengiriman WhatsApp</h1>
                <div class="d-none d-sm-inline-block">
                    <span class="badge bg-primary">Fonnte API</span>
                </div>
            </div>
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Pesan (7 hari)
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $totalMessages = 0;
                                        foreach ($stats as $stat) {
                                            $totalMessages += $stat['total_messages'];
                                        }
                                        echo $totalMessages;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Berhasil
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $sentCount = 0;
                                        foreach ($stats as $stat) {
                                            $sentCount += $stat['sent_count'];
                                        }
                                        echo $sentCount;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Gagal
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $failedCount = 0;
                                        foreach ($stats as $stat) {
                                            $failedCount += $stat['failed_count'];
                                        }
                                        echo $failedCount;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $pendingCount = 0;
                                        foreach ($stats as $stat) {
                                            $pendingCount += $stat['pending_count'];
                                        }
                                        echo $pendingCount;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Log Pengiriman Pesan</h6>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="delete_all" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus semua log?')">
                            <i class="fas fa-trash"></i> Hapus Semua
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <form method="POST" id="bulkDeleteForm">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="30"><input type="checkbox" id="selectAllHeader"></th>
                                        <th>Waktu</th>
                                        <th>Nomor Tujuan</th>
                                        <th>Tipe Pesan</th>
                                        <th>Template</th>
                                        <th>Pesan</th>
                                        <th>Status</th>
                                        <th>Detail</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada data log pengiriman</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="log-checkbox" name="selected_logs[]" value="<?php echo $log['id']; ?>">
                                            </td>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($log['phone_number']); ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($log['message_type']); ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['template_name'])): ?>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($log['template_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $messageText = htmlspecialchars($log['message']);
                                                if (strlen($messageText) > 50) {
                                                    echo substr($messageText, 0, 50) . '...';
                                                } else {
                                                    echo $messageText;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($log['status'] == 'sent'): ?>
                                                    <span class="badge badge-success">Berhasil</span>
                                                <?php elseif ($log['status'] == 'pending'): ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php elseif ($log['status'] == 'error'): ?>
                                                    <span class="badge badge-danger">Error</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($log['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['status_detail'])): ?>
                                                    <span class="text-danger"><?php echo htmlspecialchars($log['status_detail']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (!empty($log['response'])): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#responseModal<?php echo $log['id']; ?>" title="Lihat Response">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                                        <button type="submit" name="delete_single" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus log ini?')" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Response Modal -->
                                        <?php if (!empty($log['response'])): ?>
                                        <div class="modal fade" id="responseModal<?php echo $log['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="responseModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="responseModalLabel">Response Detail</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <pre><?php echo htmlspecialchars($log['response']); ?></pre>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" name="delete_selected" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Yakin ingin menghapus log yang dipilih?')" id="deleteSelectedBtn" disabled>
                            <i class="fas fa-trash"></i> Hapus Terpilih
                        </button>
                    </form>
                    
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    Menampilkan <?php echo ($offset + 1); ?> - <?php echo min($offset + $per_page, $totalRecords); ?> dari <?php echo $totalRecords; ?> log
                                </small>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm">
                                    <!-- Previous Page -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>&per_page=<?php echo $per_page; ?>&status=<?php echo urlencode($status); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page Numbers -->
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    if ($startPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&per_page=<?php echo $per_page; ?>&status=<?php echo urlencode($status); ?>">1</a>
                                        </li>
                                        <?php if ($startPage > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($endPage < $totalPages): ?>
                                        <?php if ($endPage < $totalPages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&per_page=<?php echo $per_page; ?>&status=<?php echo urlencode($status); ?>"><?php echo $totalPages; ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Next Page -->
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>&per_page=<?php echo $per_page; ?>&status=<?php echo urlencode($status); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../../templates/footer.php'; ?>
</div>

<!-- Delete All Modal -->
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
                <p class="text-muted">Total log yang akan dihapus: <strong><?php echo $totalRecords; ?></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="delete_all" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus Semua
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/scripts.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable with custom settings
    $('#dataTable').DataTable({
        "order": [[ 1, "desc" ]], // Sort by time column (index 1) descending
        "pageLength": <?php echo $per_page; ?>,
        "lengthChange": false,
        "searching": false,
        "info": false,
        "paging": false, // Disable DataTable pagination, use custom pagination
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
    
    // Bulk delete functionality
    $('#selectAllHeader').on('change', function() {
        $('.log-checkbox').prop('checked', this.checked);
        updateDeleteButton();
    });

    $('.log-checkbox').on('change', function() {
        var totalCheckboxes = $('.log-checkbox').length;
        var checkedCheckboxes = $('.log-checkbox:checked').length;
        
        if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
            $('#selectAllHeader').prop('checked', true);
        } else {
            $('#selectAllHeader').prop('checked', false);
        }
        updateDeleteButton();
    });

    function updateDeleteButton() {
        var checkedCount = $('.log-checkbox:checked').length;
        $('#deleteSelectedBtn').prop('disabled', checkedCount === 0);
        if (checkedCount > 0) {
            $('#deleteSelectedBtn').html('<i class="fas fa-trash"></i> Hapus Terpilih (' + checkedCount + ')');
        } else {
            $('#deleteSelectedBtn').html('<i class="fas fa-trash"></i> Hapus Terpilih');
        }
    }

    $('#deleteSelectedBtn').on('click', function() {
        var checkedCount = $('.log-checkbox:checked').length;
        if (checkedCount === 0) {
            alert('Pilih log yang akan dihapus terlebih dahulu.');
            return;
        }
        
        if (confirm('Apakah Anda yakin ingin menghapus ' + checkedCount + ' log yang dipilih?')) {
            // Add hidden input for bulk delete action
            if (!$('input[name="delete_selected"]').length) {
                $('#deleteForm').append('<input type="hidden" name="delete_selected" value="1">');
            }
            $('#deleteForm').submit();
        }
    });

    // Handle form submission for bulk delete
    $('#deleteForm').on('submit', function(e) {
        var checkedBoxes = $('.log-checkbox:checked');
        if (checkedBoxes.length > 0 && $('input[name="delete_selected"]').length > 0) {
            // Ensure all selected checkboxes are included in form
            checkedBoxes.each(function() {
                if (!$(this).is(':checked')) {
                    $(this).prop('checked', true);
                }
            });
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Refresh page when clicking refresh button
    $('.dropdown-item[onclick="location.reload()"]').on('click', function(e) {
        e.preventDefault();
        location.reload();
    });
});
</script>

</body>
</html>