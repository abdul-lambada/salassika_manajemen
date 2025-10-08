<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$title = "Detail Pengaduan";
include '../../templates/header.php';
include '../../includes/db.php';

// Ambil ID pengaduan dari query string
$id_pengaduan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query data pengaduan
$stmt = $conn->prepare("SELECT * FROM pengaduan WHERE id_pengaduan = :id_pengaduan");
$stmt->bindParam(':id_pengaduan', $id_pengaduan);
$stmt->execute();
$pengaduan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pengaduan) {
    echo "<script>alert('Data pengaduan tidak ditemukan!'); window.location.href = 'list_pengaduan.php';</script>";
    exit;
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detail Pengaduan</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12 table-responsive">
                            <table class="table table-striped">
                                <tr>
                                    <th>Nama Pelapor</th>
                                    <td><?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?></td>
                                </tr>
                                <tr>
                                    <th>Nomor WhatsApp</th>
                                    <td><?php echo htmlspecialchars($pengaduan['no_wa']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email Pelapor</th>
                                    <td><?php echo htmlspecialchars($pengaduan['email_pelapor']); ?></td>
                                </tr>
                                <tr>
                                    <th>Peran Pelapor</th>
                                    <td><?php echo htmlspecialchars($pengaduan['role_pelapor']); ?></td>
                                </tr>
                                <tr>
                                    <th>Kategori</th>
                                    <td><?php echo htmlspecialchars($pengaduan['kategori']); ?></td>
                                </tr>
                                <tr>
                                    <th>Judul Pengaduan</th>
                                    <td><?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?></td>
                                </tr>
                                <tr>
                                    <th>Pesan</th>
                                    <td><?php echo nl2br(htmlspecialchars($pengaduan['isi_pengaduan'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Keterangan Tambahan</th>
                                    <td><?php echo nl2br(htmlspecialchars($pengaduan['keterangan'])); ?></td>
                                </tr>
                                <tr>
                                    <th>File Pendukung</th>
                                    <td>
                                        <?php if (!empty($pengaduan['file_pendukung'])): ?>
                                            <?php
                                            // Daftar ekstensi gambar yang didukung
                                            $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                                            $file_extension = pathinfo($pengaduan['file_pendukung'], PATHINFO_EXTENSION);

                                            if (in_array(strtolower($file_extension), $image_extensions)): ?>
                                                <!-- Tampilkan gambar jika file adalah gambar -->
                                                <img src="../../uploads/<?php echo htmlspecialchars($pengaduan['file_pendukung']); ?>" alt="File Pendukung" style="max-width: 200px; max-height: 200px;">
                                            <?php else: ?>
                                                <!-- Tampilkan link unduh jika file bukan gambar -->
                                                <a href="../../uploads/<?php echo htmlspecialchars($pengaduan['file_pendukung']); ?>" target="_blank">
                                                    Unduh File
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Tidak ada file
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
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
                                </tr>
                                <tr>
                                    <th>Tanggal Pengaduan</th>
                                    <td><?php echo htmlspecialchars($pengaduan['tanggal_pengaduan']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="list_pengaduan.php" class="btn btn-secondary">Kembali ke List Pengaduan</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>