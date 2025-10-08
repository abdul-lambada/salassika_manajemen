<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "List Jurusan";
$active_page = "list_jurusan"; // Untuk menandai menu aktif di sidebar
include '../../templates/header.php';
include '../../templates/sidebar.php';
// Pagination: retrieve current page and set limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
// Ambil data Jurusan dengan pagination
include '../../includes/db.php';
$stmt = $conn->query("SELECT SQL_CALC_FOUND_ROWS * FROM jurusan LIMIT $limit OFFSET $offset");
$jurusan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get total number of rows and compute total pages
$total = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($total / $limit);
// Cek status dari query string
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = '';
switch ($status) {
    case 'add_success':
        $message = 'Data jurusan berhasil ditambahkan.';
        $alert_class = 'alert-success';
        break;
    case 'edit_success':
        $message = 'Data jurusan berhasil diperbarui.';
        $alert_class = 'alert-warning';
        break;
    case 'delete_success':
        $message = 'Data jurusan berhasil dihapus.';
        $alert_class = 'alert-danger';
        break;
    case 'error':
        $message = isset($_GET['message']) ? $_GET['message'] : 'Terjadi kesalahan saat memproses data.';
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
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">List Jurusan</h1> -->
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
                            <h6 class="m-0 font-weight-bold text-primary">Data Jurusan</h6>
                        </div>
                        <div class="card-header py-3">
                            <a href="tambah_jurusan.php" class="btn btn-success btn-sm"><i class="fas fa-plus-circle"> Tambah Data</i></a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Jurusan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jurusan_list as $index => $jurusan): ?>
                                        <tr>
                                            <td><?php echo ($page - 1) * $limit + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($jurusan['nama_jurusan']); ?></td>
                                            <td>
                                                <a href="edit_jurusan.php?id=<?php echo $jurusan['id_jurusan']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"> Edit</i></a>
                                                <button type="button" class="btn btn-danger btn-sm btn-hapus-jurusan"
                                                    data-id="<?php echo $jurusan['id_jurusan']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($jurusan['nama_jurusan']); ?>"
                                                    data-toggle="modal" data-target="#hapusModal">
                                                    <i class="fas fa-trash"> Hapus</i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <!-- Modal Hapus Global -->
                            <div class="modal fade" id="hapusModal" tabindex="-1" role="dialog" aria-labelledby="hapusModalLabel" aria-hidden="true">
                              <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title" id="hapusModalLabel">Konfirmasi Hapus</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                    </button>
                                  </div>
                                  <div class="modal-body">
                                    Apakah Anda yakin ingin menghapus jurusan <b id="namaJurusanHapus"></b>?
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <a href="#" id="btnHapusJurusan" class="btn btn-danger">Hapus</a>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <!-- Dynamic Pagination -->
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <?php if($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Previous</span>
                                        </li>
                                    <?php endif; ?>
                                    <?php for($i=1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php if($page==$i) echo 'active'; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
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
    <!-- jQuery -->
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <!-- Bootstrap core JavaScript-->
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        var namaJurusanHapus = $('#namaJurusanHapus');
        var btnHapusJurusan = $('#btnHapusJurusan');
        $('.btn-hapus-jurusan').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var link = 'hapus_jurusan.php?id=' + encodeURIComponent(id);
            namaJurusanHapus.text(nama);
            btnHapusJurusan.attr('href', link);
        });
    });
    </script>