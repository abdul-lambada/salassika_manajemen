<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$title = 'Laporan Absensi Guru';
$active_page = 'laporan_guru';
$required_role = null;
$csrfToken = admin_get_csrf_token();

if (isset($_GET['csrf_token']) && $_GET['csrf_token'] !== '') {
    if (!admin_validate_csrf($_GET['csrf_token'])) {
        header('Location: laporan_guru.php?status=error&message=' . urlencode('Token tidak valid.'));
        exit;
    }
}

// Filter berdasarkan tanggal
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

// Query untuk mengambil data absensi guru dengan informasi fingerprint
$query = "
    SELECT 
        ag.id_absensi_guru,
        ag.tanggal, 
        g.nama_guru, 
        g.nip, 
        g.jenis_kelamin, 
        ag.status_kehadiran AS status_kehadiran,
        ag.catatan,
        ag.jam_masuk,
        ag.jam_keluar,
        CASE 
            WHEN kh.user_id IS NOT NULL THEN 'Fingerprint'
            ELSE 'Manual'
        END AS sumber_data
    FROM absensi_guru ag
    JOIN guru g ON ag.id_guru = g.id_guru
    LEFT JOIN users u ON g.user_id = u.id
    LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id AND DATE(kh.timestamp) = ag.tanggal
    WHERE 1=1
";

$params = [];
if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
    $query .= " AND ag.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir";
    $params[':tanggal_awal'] = $tanggal_awal;
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

$query .= " ORDER BY ag.tanggal DESC, ag.jam_masuk DESC";

$stmt_absensi = $conn->prepare($query);
$stmt_absensi->execute($params);
$absensi_list = $stmt_absensi->fetchAll(PDO::FETCH_ASSOC);

// Tombol Download Laporan
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    if (!admin_validate_csrf($_GET['token'] ?? null)) {
        header('Location: laporan_guru.php?status=error&message=' . urlencode('Token tidak valid.'));
        exit;
    }
    $fpdf_path = '../../assets/vendor/fpdf/fpdf.php';
    if (file_exists($fpdf_path)) {
        require($fpdf_path);
    } else {
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
            $this->SetFont('Arial', 'B', 8);

            // Cetak header
            foreach ($header as $col) {
                $this->Cell($column_width, 8, $col, 1, 0, 'C', true);
            }
            $this->Ln();

            // Warna baris
            $this->SetFillColor(245, 245, 245);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 7);

            // Data
            $fill = false;
            foreach ($data as $row) {
                $this->Cell($column_width, 8, $row['tanggal'], 1, 0, 'C', $fill);
                $this->Cell($column_width, 8, $row['nama_guru'], 1, 0, 'L', $fill);
                $this->Cell($column_width, 8, $row['nip'], 1, 0, 'C', $fill);
                $this->Cell($column_width, 8, $row['jenis_kelamin'], 1, 0, 'C', $fill);
                $this->Cell($column_width, 8, $row['status_kehadiran'], 1, 0, 'C', $fill);
                $this->Cell($column_width, 8, $row['jam_masuk'] ?: '-', 1, 0, 'C', $fill);
                $this->Cell($column_width, 8, $row['jam_keluar'] ?: '-', 1, 0, 'C', $fill);
                $this->Cell($column_width, 8, $row['sumber_data'], 1, 0, 'C', $fill);

                // Untuk kolom "Catatan", gunakan MultiCell jika teks panjang
                $x = $this->GetX();
                $y = $this->GetY();
                $this->MultiCell($column_width, 8, $row['catatan'] ?: '-', 1, 'L', $fill);
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
    $header = ['Tanggal', 'Nama Guru', 'NIP', 'JK', 'Status', 'Jam Masuk', 'Jam Keluar', 'Sumber', 'Catatan'];

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
include '../../templates/layout_start.php';
?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Absensi Guru</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
                                <label>Tanggal Awal:</label>
                                <input type="date" name="tanggal_awal" class="form-control" value="<?= htmlspecialchars($tanggal_awal); ?>"><br>

                                <label>Tanggal Akhir:</label>
                                <input type="date" name="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir); ?>"><br>

                                <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                                <a href="?<?= http_build_query(array_merge($_GET, ['download' => 'pdf', 'token' => $csrfToken])); ?>" class="btn btn-danger">Download PDF</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Laporan Absensi Guru</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nama Guru</th>
                                            <th>NIP</th>
                                            <th>Jenis Kelamin</th>
                                            <th>Status Kehadiran</th>
                                            <th>Jam Masuk</th>
                                            <th>Jam Keluar</th>
                                            <th>Sumber Data</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($absensi_list)): ?>
                                            <?php foreach ($absensi_list as $absensi): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($absensi['tanggal']); ?></td>
                                                    <td><?= htmlspecialchars($absensi['nama_guru']); ?></td>
                                                    <td><?= htmlspecialchars($absensi['nip']); ?></td>
                                                    <td><?= htmlspecialchars($absensi['jenis_kelamin']); ?></td>
                                                    <td>
                                                        <?php
                                                        $status = $absensi['status_kehadiran'];
                                                        $badge_class = '';
                                                        switch ($status) {
                                                            case 'Hadir':
                                                                $badge_class = 'badge-success';
                                                                break;
                                                            case 'Sakit':
                                                                $badge_class = 'badge-warning';
                                                                break;
                                                            case 'Izin':
                                                                $badge_class = 'badge-info';
                                                                break;
                                                            case 'Alpha':
                                                                $badge_class = 'badge-danger';
                                                                break;
                                                            default:
                                                                $badge_class = 'badge-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge <?= $badge_class; ?>">
                                                            <?= htmlspecialchars($status); ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $absensi['jam_masuk'] ? htmlspecialchars($absensi['jam_masuk']) : '-'; ?></td>
                                                    <td><?= $absensi['jam_keluar'] ? htmlspecialchars($absensi['jam_keluar']) : '-'; ?></td>
                                                    <td>
                                                        <?php
                                                        $sumber = $absensi['sumber_data'];
                                                        $sumber_badge = $sumber === 'Fingerprint' ? 'badge-primary' : 'badge-secondary';
                                                        ?>
                                                        <span class="badge <?= $sumber_badge; ?>">
                                                            <?= htmlspecialchars($sumber); ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $absensi['catatan'] ? htmlspecialchars($absensi['catatan']) : '-'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">Tidak ada data absensi.</td>
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
<?php include '../../templates/layout_end.php'; ?>