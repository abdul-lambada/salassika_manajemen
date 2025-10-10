<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$title = "List Kelas";
$active_page = "list_kelas";
$required_role = 'admin';
$csrfToken = admin_get_csrf_token();

// Pagination: retrieve current page and set limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$limit = 10;
$offset = ($page - 1) * $limit;

// Ambil data Kelas dengan pagination
$stmt = $conn->prepare("
    SELECT k.*, j.nama_jurusan 
    FROM kelas k 
    JOIN jurusan j ON k.id_jurusan = j.id_jurusan 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$kelas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of rows and compute total pages
$totalRows = (int)$conn->query("SELECT COUNT(*) as total FROM kelas")->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = max(1, (int)ceil($totalRows / $limit));

$statusMap = [
    'add_success' => ['message' => 'Data kelas berhasil ditambahkan.', 'class' => 'alert-success'],
    'edit_success' => ['message' => 'Data kelas berhasil diperbarui.', 'class' => 'alert-warning'],
    'delete_success' => ['message' => 'Data kelas berhasil dihapus.', 'class' => 'alert-danger'],
    'error' => ['message' => admin_request_param('message', 'Terjadi kesalahan saat memproses data.'), 'class' => 'alert-danger'],
];

$alert = admin_build_alert($statusMap);

include '../../templates/layout_start.php';
?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">List Kelas</h1> -->
            <?php if ($alert['should_display'] ?? false): ?>
                <?= admin_render_alert($alert); ?>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Kelas</h6>
                        </div>
                        <div class="card-header py-3">
                            <a href="tambah_kelas.php" class="btn btn-success btn-sm"><i class="fas fa-plus-circle"> Tambah Data</i></a>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Kelas</th>
                                        <th>Jurusan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kelas_list as $index => $kelas): ?>
                                        <tr>
                                            <td><?php echo ($page - 1) * $limit + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($kelas['nama_kelas']); ?></td>
                                            <td><?php echo htmlspecialchars($kelas['nama_jurusan']); ?></td>
                                            <td>
                                                <a href="edit_kelas.php?id=<?php echo $kelas['id_kelas']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"> Edit</i></a>
                                                <button type="button" class="btn btn-danger btn-sm btn-hapus-kelas"
                                                    data-id="<?php echo $kelas['id_kelas']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($kelas['nama_kelas']); ?>"
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
                                    Apakah Anda yakin ingin menghapus kelas <b id="namaKelasHapus"></b>?
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <a href="#" id="btnHapusKelas" class="btn btn-danger">Hapus</a>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <!-- Dynamic Pagination -->
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <li class="page-item <?= $page > 1 ? '' : 'disabled' ?>">
                                        <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page < $totalPages ? '' : 'disabled' ?>">
                                        <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next</a>
                                    </li>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../../templates/layout_end.php'; ?>
<script>
    $(document).ready(function() {
        var namaKelasHapus = $('#namaKelasHapus');
        var btnHapusKelas = $('#btnHapusKelas');
        var csrfToken = '<?= htmlspecialchars($csrfToken); ?>';
        $('.btn-hapus-kelas').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var link = 'hapus_kelas.php?id=' + encodeURIComponent(id) + '&token=' + encodeURIComponent(csrfToken);
            namaKelasHapus.text(nama);
            btnHapusKelas.attr('href', link);
        });
    });
</script>