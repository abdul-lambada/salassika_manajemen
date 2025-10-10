<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);
$csrfToken = admin_get_csrf_token();

$title = "List Users";
$active_page = "list_users"; // Untuk menandai menu aktif di sidebar
$required_role = 'admin';

// Pagination: retrieve current page and set limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Ambil data dari tabel users untuk ditampilkan
try {
    $stmt = $conn->query("SELECT SQL_CALC_FOUND_ROWS * FROM users LIMIT $limit OFFSET $offset");
    $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of rows and compute total pages
    $total = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
    $totalPages = ceil($total / $limit);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$statusMap = [
    'add_success' => ['message' => 'Data user berhasil ditambahkan.', 'class' => 'alert-success'],
    'edit_success' => ['message' => 'Data user berhasil diperbarui.', 'class' => 'alert-warning'],
    'delete_success' => ['message' => 'Data user berhasil dihapus.', 'class' => 'alert-danger'],
    'error' => ['message' => 'Terjadi kesalahan saat memproses data.', 'class' => 'alert-danger'],
];

$alert = admin_build_alert($statusMap);

include '../../templates/layout_start.php';
?>
        <div class="container-fluid">
            <!-- Begin Alert SB Admin 2 -->
            <?php if ($alert['should_display'] ?? false): ?>
                <?= admin_render_alert($alert); ?>
            <?php endif; ?>
            <!-- End Alert SB Admin 2 -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Users</h6>
                        </div>
                        <div class="card-body table-responsive-sm">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>UID</th>
                                        <th>Created At</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = $offset + 1;
                                    foreach ($dbUsers as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($no++); ?></td>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                                            <td><?php echo htmlspecialchars($user['uid']); ?></td>
                                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm btn-hapus-user"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($user['name']); ?>"
                                                    data-toggle="modal" data-target="#hapusModal">
                                                    <i class="fas fa-trash"></i> Hapus
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
                                            Apakah Anda yakin ingin menghapus user <b id="namaUserHapus"></b>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                            <a href="#" id="btnHapusUser" class="btn btn-danger">Hapus</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
<?php include '../../templates/layout_end.php'; ?>
<script>
$(document).ready(function() {
        var namaUserHapus = $('#namaUserHapus');
        var btnHapusUser = $('#btnHapusUser');
        var csrfToken = '<?= htmlspecialchars($csrfToken); ?>';
        $('.btn-hapus-user').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var link = 'hapus_users.php?id=' + encodeURIComponent(id) + '&token=' + encodeURIComponent(csrfToken);
            namaUserHapus.text(nama);
            btnHapusUser.attr('href', link);
        });
    });
</script>
