<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "List Pengaduan";
$active_page = "list_pengaduan"; // Untuk menandai menu aktif di sidebar
include '../../templates/header.php';
include '../../templates/sidebar.php';

// Pagination: retrieve current page and set limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Ambil data Pengaduan dengan pagination
include '../../includes/db.php';

// Handle update status via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pengaduan'])) {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    $status = $_POST['status'];

    try {
        $stmt = $conn->prepare("UPDATE pengaduan SET status = :status WHERE id_pengaduan = :id_pengaduan");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id_pengaduan', $id_pengaduan);
        $stmt->execute();
        header("Location: list_pengaduan.php?status=update_success");
        exit;
    } catch (\PDOException $e) {
        header("Location: list_pengaduan.php?status=error");
        exit;
    }
}

$stmt = $conn->query("SELECT SQL_CALC_FOUND_ROWS * FROM pengaduan ORDER BY tanggal_pengaduan DESC LIMIT $limit OFFSET $offset");
$pengaduan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of rows and compute total pages
$total = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($total / $limit);

// Cek status dari query string
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = '';
switch ($status) {
    case 'add_success':
        $message = 'Data pengaduan berhasil ditambahkan.';
        $alert_class = 'alert-success';
        break;
    case 'update_success':
        $message = 'Status pengaduan berhasil diperbarui.';
        $alert_class = 'alert-warning';
        break;
    case 'delete_success':
        $message = 'Data pengaduan berhasil dihapus.';
        $alert_class = 'alert-danger';
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
        <?php include '../../templates/navbar.php'; ?>

        <!-- Begin Page Content -->
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
                                                        <a class="btn btn-danger" href="hapus_pengaduan.php?id=<?php echo $pengaduan['id_pengaduan']; ?>">Hapus</a>
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
    <?php include '../../templates/footer.php'; ?>
</div>