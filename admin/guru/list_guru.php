<?php
include '../../includes/db.php';
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    require_once '../../vendor/autoload.php';
    require_once '../../vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
    $file = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = PHPExcel_IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    if (empty($rows) || !isset($rows[0]) || count($rows[0]) < 2) {
        $status = 'import_warning';
        $msg = 'Format file tidak valid atau kosong.';
    } else {
        $header = array_map('strtolower', $rows[0]);
        $success = 0; $fail = 0; $fail_msg = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = array_combine($header, $rows[$i]);
            if (empty($row['nip']) || empty($row['nama guru'])) continue;
            $nip = $row['nip'];
            $stmt = $conn->prepare("SELECT id_guru FROM guru WHERE nip = ?");
            $stmt->execute([$nip]);
            if ($stmt->rowCount() > 0) { $fail++; $fail_msg[] = "NIP $nip sudah ada"; continue; }
            $password = password_hash('123456', PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("INSERT INTO users (name, password, role, uid) VALUES (?, ?, 'guru', NULL)");
            $stmt_user->execute([$row['nama guru'], $password]);
            $user_id = $conn->lastInsertId();
            $stmt_guru = $conn->prepare("INSERT INTO guru (nama_guru, nip, jenis_kelamin, tanggal_lahir, alamat, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_guru->execute([
                $row['nama guru'],
                $nip,
                isset($row['jenis kelamin']) ? $row['jenis kelamin'] : '',
                isset($row['tanggal lahir']) ? $row['tanggal lahir'] : '',
                isset($row['alamat']) ? $row['alamat'] : '',
                $user_id
            ]);
            $success++;
        }
        $status = ($fail > 0) ? 'import_warning' : 'import_success';
        $msg = "Import selesai. Berhasil: $success, Gagal: $fail" . ($fail ? (" (".implode(", ", $fail_msg).")") : '');
    }
    header("Location: list_guru.php?status=$status&msg=" . urlencode($msg));
    exit();
}
use PhpOffice\PhpSpreadsheet\IOFactory;
$title = "List Guru";
$active_page = "list_guru"; // Untuk menandai menu aktif di sidebar
include '../../templates/header.php';
include '../../templates/sidebar.php';

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Ambil total jumlah data guru
$stmt_total = $conn->query("SELECT COUNT(*) AS total FROM guru");
$totalRecords = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);
if ($totalPages < 1) $totalPages = 1;

// Ambil data guru dengan limit dan offset
$stmt = $conn->prepare("
    SELECT 
        g.*, 
        u.name AS user_name,
        u.uid AS user_uid
    FROM guru g
    LEFT JOIN users u ON g.user_id = u.id
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$guru_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cek status dari query string
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = '';
switch ($status) {
    case 'add_success':
        $message = 'Data guru berhasil ditambahkan.';
        $alert_class = 'alert-success';
        break;
    case 'edit_success':
        $message = 'Data guru berhasil diperbarui.';
        $alert_class = 'alert-warning';
        break;
    case 'delete_success':
        $message = 'Data guru berhasil dihapus.';
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
// Tampilkan pesan dari GET jika ada
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $alert_class = (isset($_GET['status']) && $_GET['status'] === 'import_warning') ? 'alert-warning' : 'alert-success';
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">List Guru</h1> -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Guru</h6>
                        </div>
                        <div class="card-header py-3">
                            <a href="tambah_guru.php" class="btn btn-success btn-sm"><i class="fas fa-plus-circle"></i> Tambah Guru</a>
                            <form method="POST" action="" enctype="multipart/form-data" style="display:inline;">
                                <input type="file" name="excel_file" accept=".xlsx, .xls" required>
                                <button type="submit" name="import_excel" class="btn btn-primary btn-sm"><i class="fas fa-file-import"></i> Import Excel</button>
                            </form>
                            <a href="../../assets/format_data_guru.xlsx" class="btn btn-info btn-sm" download><i class="fas fa-download"></i> Unduh Format Excel</a>
                        </div>
                        <div class="card-body table-responsive-sm">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Guru</th>
                                        <th>NIP</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Tanggal Lahir</th>
                                        <th>Alamat</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($guru_list)): ?>
                                        <?php foreach ($guru_list as $index => $guru): ?>
                                            <tr>
                                                <td><?php echo ($page - 1) * $limit + $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($guru['nama_guru'] ?: $guru['user_name']); ?></td>
                                                <td><?php echo htmlspecialchars($guru['nip']); ?></td>
                                                <td><?php echo htmlspecialchars($guru['jenis_kelamin']); ?></td>
                                                <td><?php echo htmlspecialchars($guru['tanggal_lahir']); ?></td>
                                                <td><?php echo htmlspecialchars($guru['alamat']); ?></td>
                                                <td><?php echo htmlspecialchars(isset($guru['user_name']) ? $guru['user_name'] : 'Tidak ada user'); ?></td>
                                                <td>
                                                    <a href="edit_guru.php?id=<?php echo htmlspecialchars($guru['id_guru']); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                                    <button type="button" class="btn btn-danger btn-sm btn-hapus-guru"
                                                        data-id="<?php echo $guru['id_guru']; ?>"
                                                        data-nama="<?php echo htmlspecialchars($guru['nama_guru']); ?>"
                                                        data-toggle="modal" data-target="#hapusModal">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data guru.</td>
                                        </tr>
                                    <?php endif; ?>
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
                                    Apakah Anda yakin ingin menghapus guru <b id="namaGuruHapus"></b>?
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <a href="#" id="btnHapusGuru" class="btn btn-danger">Hapus</a>
                                    <span id="linkHapusGuru" style="margin-left:10px;"></span>
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
    <?php include '../../templates/scripts.php'; ?>
    <script>
    $(document).ready(function() {
        var namaGuruHapus = $('#namaGuruHapus');
        var btnHapusGuru = $('#btnHapusGuru');
        var linkHapusGuru = $('#linkHapusGuru');
        $('.btn-hapus-guru').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var link = 'hapus_guru.php?id=' + encodeURIComponent(id);
            namaGuruHapus.text(nama);
            btnHapusGuru.attr('href', link);
            // linkHapusGuru.html('<small>Link: <a href="' + link + '">' + link + '</a></small>');
        });
    });
    </script>
</div>