<?php
session_start();
include '../includes/db.php';
$active_page = "laporan_guru"; // Untuk menandai menu aktif di sidebar

// Periksa apakah sesi 'user' tersedia
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Filter berdasarkan tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

// Query untuk mengambil data absensi guru
$query = "
    SELECT 
        ag.id_absensi_guru,
        ag.tanggal, 
        g.nama_guru, 
        g.nip, 
        g.jenis_kelamin, 
        ag.status_kehadiran AS status_kehadiran,
        ag.catatan
    FROM absensi_guru ag
    JOIN guru g ON ag.id_guru = g.id_guru
    WHERE 1=1
";

$params = [];
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $query .= " AND ag.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir";
    $params[':tanggal_awal'] = $tanggal_awal;
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

$query .= " ORDER BY ag.tanggal DESC";

$stmt_absensi = $conn->prepare($query);
$stmt_absensi->execute($params);
$absensi_list = $stmt_absensi->fetchAll(PDO::FETCH_ASSOC);

// Tombol Download Laporan
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    // Check if we can find FPDF in different locations
    $fpdf_paths = [
        '../vendor/fpdf/fpdf.php',
        '../fpdf/fpdf.php',
        '../lib/fpdf/fpdf.php',
        '../includes/fpdf/fpdf.php',
        '../assets/vendor/fpdf/fpdf.php'
    ];

    $fpdf_found = false;
    foreach ($fpdf_paths as $path) {
        if (file_exists($path)) {
            require($path);
            $fpdf_found = true;
            break;
        }
    }

    if (!$fpdf_found) {
        echo "FPDF library not found. Please install FPDF or correct the path.";
        echo "<br><a href='laporan_guru.php'>Back to Report</a>";
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
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 10, 20); // Logo sekolah
            } else {
                $this->SetFont('Arial', 'I', 10);
                $this->Cell(0, 5, 'Logo not found', 0, 1, 'L');
            }
            // Judul Laporan
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 5, 'YAYAN ISLAM AL-AMIIN', 0, 1, 'C');
            // $this->Cell(0, 5, 'DINAS PENDIDIKAN', 0, 1, 'C');
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
            $this->Cell(0, 10, 'LAPORAN ABSENSI GURU', 0, 1, 'C');
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
                $this->Cell($column_width, 10, $row['nama_guru'], 1, 0, 'L', $fill);
                $this->Cell($column_width, 10, $row['nip'], 1, 0, 'C', $fill);
                $this->Cell($column_width, 10, $row['jenis_kelamin'], 1, 0, 'C', $fill);
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
    $pdf->SetFont('Times', '', 10);

    // Filter tanggal (opsional)
    if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
        $pdf->Cell(0, 10, 'Periode: ' . $tanggal_awal . ' s/d ' . $tanggal_akhir, 0, 1, 'L');
        $pdf->Ln(5);
    }

    // Header tabel
    $header = ['Tanggal', 'Nama Guru', 'NIP', 'Jenis Kelamin', 'Status', 'Catatan'];

    // Data absensi
    $pdf->FancyTable($header, $absensi_list);

    // Bersihkan buffer output
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Output file PDF
    $pdf->Output('D', 'laporan_absensi_guru.pdf'); // Gunakan 'D' untuk memaksa unduhan
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Absensi Guru - Management Salassika</title>
    <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.css" rel="stylesheet">
</head>

<body id="page-top">
    <?php include __DIR__ . '/../templates/header.php'; ?>
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include __DIR__ . '/../templates/navbar.php'; ?>
            
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Absensi Guru</h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <!-- Filter Tanggal -->
                                    <label>Tanggal Awal:</label>
                                    <input type="date" name="tanggal_awal" class="form-control" value="<?php echo htmlspecialchars($tanggal_awal); ?>"><br>

                                    <label>Tanggal Akhir:</label>
                                    <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo htmlspecialchars($tanggal_akhir); ?>"><br>

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
                                <h6 class="m-0 font-weight-bold text-primary">Laporan Absensi Guru</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-responsive-sm">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nama Guru</th>
                                            <th>NIP</th>
                                            <th>Jenis Kelamin</th>
                                            <th>Status Kehadiran</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($absensi_list)): ?>
                                            <?php foreach ($absensi_list as $absensi): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($absensi['tanggal']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['nama_guru']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['nip']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['jenis_kelamin']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['status_kehadiran']); ?></td>
                                                    <td><?php echo htmlspecialchars($absensi['catatan']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Tidak ada data absensi.</td>
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
        <?php include __DIR__ . '/../templates/footer.php'; ?>
    </div>
</body>

</html>