<?php
session_start();
include '../../includes/db.php';
$active_page = "laporan_siswa"; // Untuk menandai menu aktif di sidebar

// Periksa apakah sesi 'user' tersedia
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Filter berdasarkan tanggal dan kelas
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';

// Query untuk mengambil daftar kelas
$stmt_kelas = $conn->prepare("SELECT * FROM kelas");
$stmt_kelas->execute();
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

// Query untuk mengambil data absensi siswa
$query = "
    SELECT 
        asis.tanggal,
        s.nama_siswa,
        k.nama_kelas,
        asis.status_kehadiran AS status_kehadiran,
        asis.catatan
    FROM absensi_siswa asis
    JOIN siswa s ON asis.id_siswa = s.id_siswa
    JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE 1=1
";
$params = [];

// Filter tanggal
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $query .= " AND asis.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir";
    $params[':tanggal_awal'] = $tanggal_awal;
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

// Filter kelas
if (!empty($id_kelas)) {
    $query .= " AND k.id_kelas = :id_kelas";
    $params[':id_kelas'] = $id_kelas;
}

$query .= " ORDER BY asis.tanggal DESC";

// Eksekusi query
$stmt_absensi = $conn->prepare($query);
$stmt_absensi->execute($params);
$absensi_list = $stmt_absensi->fetchAll(PDO::FETCH_ASSOC);

// Tombol Download Laporan
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    $fpdf_path = '../../assets/vendor/fpdf/fpdf.php';
    if (file_exists($fpdf_path)) {
        require($fpdf_path);
    } else {
        echo "FPDF library not found. Please install FPDF or correct the path.";
        echo "<br><a href='laporan_siswa.php'>Back to Report</a>";
        exit;
    }

    // Inisialisasi FPDF
    class PDF extends FPDF
    {
        // Kop Surat
        function Header()
        {
            // Logo
            $logo_path = '../../assets/img/logo.jpg';
            // $logo_path = '../assets/img/logo.jpg';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 10, 20); // Logo sekolah
            } else {
                $this->SetFont('Arial', 'I', 10);
                $this->Cell(0, 5, 'Logo not found', 0, 1, 'L');
            }
            // Judul Laporan
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 5, 'YAYAN ISLAM AL-AMIIN', 0, 1, 'C');
            $this->Cell(0, 5, 'SMK AL-AMIIN SANGKANHURIP', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Jl. Cibiru No. 01 Desa Sangkanhurip Kecamatan Sindang Kabupaten Majalengka 45471', 0, 1, 'C');
            $this->Cell(0, 5, 'Website: www.smkalamiin.sch.id | E-mail: smkalamiin.sch@gmail.com | Telp: 0233-8514332', 0, 1, 'C');
            $this->Ln(10);
            // Garis pemisah
            $this->SetLineWidth(0.5);
            $this->Line(10, 35, 200, 35);
            $this->Ln(10);
            // Judul Laporan
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'LAPORAN ABSENSI SISWA', 0, 1, 'C');
            $this->Ln(5);
        }

        // Footer
        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
        }

        // Tabel Laporan
        function FancyTable($header, $data)
        {
            // Margin kiri dan kanan
            $margin_left = 10;
            $margin_right = 10;

            // Total lebar halaman (A4 = 210 mm)
            $page_width = 210 - $margin_left - $margin_right;

            // Hitung lebar kolom otomatis berdasarkan jumlah kolom
            $num_columns = count($header);
            $column_width = $page_width / $num_columns;

            // Warna header
            $this->SetFillColor(230, 230, 230);
            $this->SetTextColor(0);
            $this->SetDrawColor(128, 128, 128);
            $this->SetLineWidth(0.3);
            $this->SetFont('Arial', 'B', 10);

            // Cetak header
            foreach ($header as $col) {
                $this->Cell($column_width, 10, $col, 1, 0, 'C', true);
            }
            $this->Ln();

            // Warna baris
            $this->SetFillColor(245, 245, 245);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 10);

            // Data
            $fill = false;
            foreach ($data as $row) {
                $this->Cell($column_width, 10, $row['tanggal'], 1, 0, 'C', $fill);
                $this->Cell($column_width, 10, $row['nama_siswa'], 1, 0, 'L', $fill);
                $this->Cell($column_width, 10, $row['nama_kelas'], 1, 0, 'L', $fill);
                $this->Cell($column_width, 10, $row['status_kehadiran'], 1, 0, 'C', $fill);

                // Untuk kolom "Catatan", gunakan MultiCell jika teks panjang
                $x = $this->GetX();
                $y = $this->GetY();
                $this->MultiCell($column_width, 10, $row['catatan'], 1, 'L', $fill);
                $this->SetXY($x + $column_width, $y);

                $fill = !$fill;
                $this->Ln();
            }
        }
    }

    // Inisialisasi objek PDF
    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // Filter tanggal (opsional)
    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
        $pdf->Cell(0, 10, 'Periode: ' . $tanggal_awal . ' s/d ' . $tanggal_akhir, 0, 1, 'L');
        $pdf->Ln(5);
    }

    // Header tabel
    $header = ['Tanggal', 'Nama Siswa', 'Kelas', 'Status', 'Catatan'];

    // Data absensi
    $pdf->FancyTable($header, $absensi_list);

    // Bersihkan buffer output
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Output file PDF
    $pdf->Output('D', 'laporan_absensi_siswa.pdf'); // Gunakan 'D' untuk memaksa unduhan
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Absensi Siswa - Management Salassika</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.css" rel="stylesheet">
</head>
<body id="page-top">
    <?php include '../../templates/header.php'; ?>
    <?php include '../../templates/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../../templates/navbar.php'; ?>
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <!-- <h1 class="h3 mb-0 text-gray-800">Laporan Absensi Siswa</h1> -->
            </nav>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Absensi Siswa</h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <!-- Filter Tanggal -->
                                    <label>Tanggal Awal:</label>
                                    <input type="date" name="tanggal_awal" class="form-control" value="<?php echo htmlspecialchars($tanggal_awal); ?>"><br>
                                    <label>Tanggal Akhir:</label>
                                    <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo htmlspecialchars($tanggal_akhir); ?>"><br>

                                    <!-- Filter Kelas -->
                                    <label>Kelas:</label>
                                    <select name="id_kelas" class="form-control">
                                        <option value="">-- Semua Kelas --</option>
                                        <?php foreach ($kelas_list as $kelas): ?>
                                            <option value="<?php echo htmlspecialchars($kelas['id_kelas']); ?>"
                                                <?php echo ($id_kelas == $kelas['id_kelas']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select><br>

                                    <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                                    <a href="?<?php echo http_build_query($_GET); ?>&download=pdf" class="btn btn-danger">Download PDF</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Laporan Absensi -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Laporan Absensi Siswa</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-responsive-sm">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nama Siswa</th>
                                            <th>Kelas</th>
                                            <th>Status Kehadiran</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($absensi_list)): ?>
                                            <?php foreach ($absensi_list as $absensi): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($absensi['tanggal']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['nama_siswa']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['nama_kelas']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['status_kehadiran']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['catatan']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data absensi.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../../templates/footer.php'; ?>
    </div>
</body>
</html>