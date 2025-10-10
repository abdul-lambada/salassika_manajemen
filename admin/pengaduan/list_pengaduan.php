<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$title = 'List Pengaduan';
$active_page = 'list_pengaduan';  // Untuk menandai menu aktif di sidebar
$required_role = 'admin';
$csrfToken = admin_get_csrf_token();

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pengaduan'])) {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
        header('Location: list_pengaduan.php?status=error&message=' . urlencode('Token CSRF tidak valid.'));
        exit;
    }

    $id_pengaduan = (int) $_POST['id_pengaduan'];
    $statusPengaduan = $_POST['status'] ?? 'pending';

    try {
        $stmtUpdate = $conn->prepare('UPDATE pengaduan SET status = :status WHERE id_pengaduan = :id_pengaduan');
        $stmtUpdate->bindParam(':status', $statusPengaduan, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':id_pengaduan', $id_pengaduan, PDO::PARAM_INT);
        $stmtUpdate->execute();
        header('Location: list_pengaduan.php?status=update_success');
        exit;
    } catch (PDOException $e) {
        header('Location: list_pengaduan.php?status=error');
        exit;
    }
}

$stmt = $conn->prepare('SELECT * FROM pengaduan ORDER BY tanggal_pengaduan DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pengaduan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = (int) $conn->query('SELECT COUNT(*) FROM pengaduan')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $limit));

$statusMap = [
    'add_success' => ['message' => 'Data pengaduan berhasil ditambahkan.', 'class' => 'alert-success'],
    'update_success' => ['message' => 'Status pengaduan berhasil diperbarui.', 'class' => 'alert-warning'],
    'delete_success' => ['message' => 'Data pengaduan berhasil dihapus.', 'class' => 'alert-danger'],
    'error' => ['message' => admin_request_param('message', 'Terjadi kesalahan saat memproses data.'), 'class' => 'alert-danger'],
];

$alert = admin_build_alert($statusMap);

include '../../templates/layout_start.php';
?>

        <div class="container-fluid">
            <?php if ($alert['should_display'] ?? false): ?>
                <?= admin_render_alert($alert); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Pengaduan</h6>
                        </div>
                        <div class="card-body table-responsive-sm">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Pelapor</th>
                                        <th>Kategori</th>
                                        <th>Judul Pengaduan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengaduan_list as $index => $pengaduan): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?></td>
                                            <td><?php echo htmlspecialchars($pengaduan['kategori']); ?></td>
                                            <td><?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?></td>
                                            <td>
                                                <?php
                                                $status_text = '';
                                                $status_color = '';
                                                $status_icon = '';

                                                switch ($pengaduan['status']) {
                                                    case 'pending':
                                                        $status_text = 'Pending';
                                                        $status_color = 'text-warning';
                                                        $status_icon = '<i class="fas fa-clock"></i>';
                                                        break;
                                                    case 'diproses':
                                                        $status_text = 'Diproses';
                                                        $status_color = 'text-primary';
                                                        $status_icon = '<i class="fas fa-spinner fa-spin"></i>';
                                                        break;
                                                    case 'selesai':
                                                        $status_text = 'Selesai';
                                                        $status_color = 'text-success';
                                                        $status_icon = '<i class="fas fa-check-circle"></i>';
                                                        break;
                                                    default:
                                                        $status_text = 'Tidak Diketahui';
                                                        $status_color = 'text-secondary';
                                                        $status_icon = '<i class="fas fa-question-circle"></i>';
                                                        break;
                                                }
                                                ?>
                                                <span class="<?php echo $status_color; ?>">
                                                    <?php echo $status_icon . ' ' . $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="id_pengaduan" value="<?php echo $pengaduan['id_pengaduan']; ?>">
                                                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $pengaduan['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="diproses" <?php echo $pengaduan['status'] === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                                        <option value="selesai" <?php echo $pengaduan['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                                    </select>
                                                </form>
                                                <a href="detail_pengaduan.php?id=<?php echo $pengaduan['id_pengaduan']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                                 <!-- Tombol Cetak PDF -->
                                                 <a href="cetak_laporan_pengaduan.php?id=<?php echo $pengaduan['id_pengaduan']; ?>" class="btn btn-danger btn-sm" target="_blank">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                <a href="#" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#hapusModal-<?php echo $pengaduan['id_pengaduan']; ?>"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>

                                        <!-- Modal Konfirmasi Hapus -->
                                        <div class="modal fade" id="hapusModal-<?php echo $pengaduan['id_pengaduan']; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="exampleModalLabel">Hapus Data</h5>
                                                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">×</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">Apakah Anda yakin akan menghapus data ini?</div>
                                                    <div class="modal-footer">
                                                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                                                        <a class="btn btn-danger" href="hapus_pengaduan.php?id=<?php echo $pengaduan['id_pengaduan']; ?>&token=<?= urlencode($csrfToken); ?>">Hapus</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Dynamic Pagination -->
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <li class="page-item <?= $page > 1 ? '' : 'disabled' ?>">
                                        <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page < $totalPages ? '' : 'disabled' ?>">
                                        <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php include '../../templates/layout_end.php'; ?>