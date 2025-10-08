<?php
include '../../includes/db.php';
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    require_once '../../vendor/autoload.php';
    require_once '../../vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
    $file = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = PHPExcel_IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    $header = array_map('strtolower', $rows[0]);
    $success = 0; $fail = 0; $fail_msg = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = array_combine($header, $rows[$i]);
        if (empty($row['nis']) || empty($row['nama siswa'])) continue;
        $nis = $row['nis'];
        $stmt = $conn->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
        $stmt->execute([$nis]);
        if ($stmt->rowCount() > 0) { $fail++; $fail_msg[] = "NIS $nis sudah ada di siswa"; continue; }
        $stmt_uid = $conn->prepare("SELECT id FROM users WHERE uid = ?");
        $stmt_uid->execute([$nis]);
        if ($stmt_uid->rowCount() > 0) { $fail++; $fail_msg[] = "NIS $nis sudah digunakan user lain"; continue; }
        $password = password_hash('123456', PASSWORD_DEFAULT);
        $stmt_user = $conn->prepare("INSERT INTO users (name, password, role, uid) VALUES (?, ?, 'siswa', NULL)");
        $stmt_user->execute([$row['nama siswa'], $password]);
        $user_id = $conn->lastInsertId();
        $tanggal_lahir = '';
        if (isset($row['tanggal lahir']) && !empty($row['tanggal lahir'])) {
            $tanggal_lahir = $row['tanggal lahir'];
        }
        $stmt_siswa = $conn->prepare("INSERT INTO siswa (nama_siswa, nisn, nis, jenis_kelamin, tanggal_lahir, alamat, id_kelas, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_siswa->execute([
            $row['nama siswa'],
            isset($row['nisn']) ? $row['nisn'] : '',
            $nis,
            isset($row['jenis kelamin']) ? $row['jenis kelamin'] : '',
            $tanggal_lahir,
            isset($row['alamat']) ? $row['alamat'] : '',
            isset($row['id_kelas']) ? $row['id_kelas'] : 1,
            $user_id
        ]);
        $success++;
    }
    $status = ($fail > 0) ? 'import_warning' : 'import_success';
    $msg = "Import selesai. Berhasil: $success, Gagal: $fail" . ($fail ? (" (".implode(", ", $fail_msg).")") : '');
    header("Location: list_siswa.php?status=$status&msg=" . urlencode($msg));
    exit();
}
use PhpOffice\PhpSpreadsheet\IOFactory;
$title = "List Siswa";
$active_page = "list_siswa";
include '../../templates/header.php';
include '../../templates/sidebar.php';
// include '../../templates/navbar.php';

// Konfigurasi pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil total data
$stmt_total = $conn->query("
    SELECT COUNT(*) AS total 
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
");
$totalRecords = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Query dengan join ke tabel kelas dan users
$stmt = $conn->prepare("
    SELECT 
        s.id_siswa, 
        s.nisn,
        s.nama_siswa,
        s.nis, 
        s.jenis_kelamin, 
        s.tanggal_lahir, 
        s.alamat, 
        k.nama_kelas, 
        u.name AS user_name,
        u.uid AS user_uid
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN users u ON s.user_id = u.id
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handling status message
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = '';
$alert_class = '';

switch ($status) {
    case 'add_success':
        $message = 'Data siswa berhasil ditambahkan.';
        $alert_class = 'alert-success';
        break;
    case 'edit_success':
        $message = 'Data siswa berhasil diperbarui.';
        $alert_class = 'alert-warning';
        break;
    case 'delete_success':
        $message = 'Data siswa berhasil dihapus.';
        $alert_class = 'alert-danger';
        break;
    case 'error':
        $message = 'Terjadi kesalahan saat memproses data.';
        $alert_class = 'alert-danger';
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
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <!-- <h1 class="h3 mb-0 text-gray-800">List Siswa</h1> -->
            </div>
            <?php if (!empty($message)): ?>
                <div class="alert <?= $alert_class ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Data Siswa</h6>
                    <div>
                        <a href="tambah_siswa.php" class="btn btn-success btn-sm">
                            <i class="fas fa-plus-circle"></i> Tambah Siswa
                        </a>
                        <form method="POST" action="" enctype="multipart/form-data" class="d-inline">
                            <input type="file" name="excel_file" accept=".xlsx, .xls" required>
                            <button type="submit" name="import_excel" class="btn btn-primary btn-sm">
                                <i class="fas fa-file-import"></i> Import Excel
                            </button>
                        </form>
                        <a href="../../assets/format_data_siswa.xlsx" class="btn btn-info btn-sm" download>
                            <i class="fas fa-download"></i> Unduh Format Excel
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive-sm">
                    <table class="table table-bordered" id="dataTable">
                        <thead>
                            <tr>
                                <th>ID Siswa</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>NIS</th>
                                <th>Jenis Kelamin</th>
                                <th>Tanggal Lahir</th>
                                <th>Alamat</th>
                                <th>Kelas</th>
                                <th>User</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <tr>
                                    <td><?= htmlspecialchars($siswa['id_siswa']) ?></td>
                                    <td><?= htmlspecialchars($siswa['nisn']) ?></td>
                                    <td><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                                    <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                    <td><?= htmlspecialchars($siswa['jenis_kelamin']) ?></td>
                                    <td><?= htmlspecialchars($siswa['tanggal_lahir']) ?></td>
                                    <td><?= htmlspecialchars($siswa['alamat']) ?></td>
                                    <td><?= htmlspecialchars($siswa['nama_kelas']) ?></td>
                                    <td><?= htmlspecialchars(isset($siswa['user_name']) ? $siswa['user_name'] : 'Tidak ada user') ?></td>
                                    <td>
                                        <a href="edit_siswa.php?id=<?= $siswa['id_siswa'] ?>" 
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                data-toggle="modal" 
                                                data-target="#deleteModal<?= $siswa['id_siswa'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteModal<?= $siswa['id_siswa'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Konfirmasi Hapus</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                Apakah Anda yakin ingin menghapus data ini?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                    Batal
                                                </button>
                                                <a href="hapus_siswa.php?id=<?= $siswa['id_siswa'] ?>" 
                                                   class="btn btn-danger">
                                                    Hapus
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-end">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../templates/footer.php'; ?>
</div>

<!-- JS SB Admin -->
<script src="../../assets/vendor/jquery/jquery.min.js"></script>
<script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../../assets/js/sb-admin-2.min.js"></script>

</body>
</html>