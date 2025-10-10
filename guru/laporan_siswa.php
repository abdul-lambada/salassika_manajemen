<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$title = 'Laporan Absensi Siswa';
$active_page = 'laporan_siswa';
$required_role = ($currentUser['role'] ?? '') === 'admin' ? null : 'guru';

$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$id_kelas = $_GET['id_kelas'] ?? '';

$stmt_kelas = $conn->prepare('SELECT * FROM Kelas ORDER BY nama_kelas');
$stmt_kelas->execute();
$kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

$query = "
    SELECT
        asis.tanggal,
        COALESCE(u.name, s.nama_siswa) AS nama_siswa,
        k.nama_kelas,
        asis.status_kehadiran,
        asis.catatan
    FROM absensi_siswa asis
    JOIN siswa s ON asis.id_siswa = s.id_siswa
    JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN users u ON s.user_id = u.id
    WHERE 1=1
";

$params = [];
if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
    $query .= ' AND asis.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir';
    $params[':tanggal_awal'] = $tanggal_awal;
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

if ($id_kelas !== '') {
    $query .= ' AND k.id_kelas = :id_kelas';
    $params[':id_kelas'] = $id_kelas;
}

$query .= ' ORDER BY asis.tanggal DESC';

$stmt_absensi = $conn->prepare($query);
$stmt_absensi->execute($params);
$absensi_list = $stmt_absensi->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
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
            require $path;
            $fpdf_found = true;
            break;
        }
    }

    if (!$fpdf_found) {
        echo 'FPDF library not found. Please install FPDF or correct the path.';
        echo "<br><a href='laporan_siswa.php'>Back to Report</a>";
        exit;
    }

    class PDF extends FPDF
    {
        public function Header()
        {
            $logo_path = '../../assets/img/logo.jpg';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 10, 20);
            } else {
                $this->SetFont('Arial', 'I', 10);
                $this->Cell(0, 5, 'Logo not found', 0, 1, 'L');
            }

            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 5, 'YAYAN ISLAM AL-AMIIN', 0, 1, 'C');
            $this->Cell(0, 5, 'SMK AL-AMIIN SANGKANHURIP', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Jl. Cibiru No. 01 Desa Sangkanhurip Kecamatan Sindang Kabupaten Majalengka 45471', 0, 1, 'C');
            $this->Cell(0, 5, 'Website: www.smkalamiin.sch.id | E-mail: smkalamiin.sch@gmail.com | Telp: 0233-8514332', 0, 1, 'C');
            $this->Ln(10);
            $this->SetLineWidth(0.5);
            $this->Line(10, 35, 200, 35);
            $this->Ln(10);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'LAPORAN ABSENSI SISWA', 0, 1, 'C');
            $this->Ln(5);
        }

        public function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
        }

        public function FancyTable(array $header, array $data): void
        {
            $margin_left = 10;
            $margin_right = 10;
            $page_width = 210 - $margin_left - $margin_right;
            $column_width = $page_width / count($header);

            $this->SetFillColor(230, 230, 230);
            $this->SetTextColor(0);
            $this->SetDrawColor(128, 128, 128);
            $this->SetLineWidth(0.3);
            $this->SetFont('Arial', 'B', 10);

            foreach ($header as $col) {
                $this->Cell($column_width, 10, $col, 1, 0, 'C', true);
            }
            $this->Ln();

            $this->SetFillColor(245, 245, 245);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 10);

            $fill = false;
            foreach ($data as $row) {
                $this->Cell($column_width, 10, $row['tanggal'], 1, 0, 'C', $fill);
                $this->Cell($column_width, 10, $row['nama_siswa'], 1, 0, 'L', $fill);
                $this->Cell($column_width, 10, $row['nama_kelas'], 1, 0, 'L', $fill);
                $this->Cell($column_width, 10, $row['status_kehadiran'], 1, 0, 'C', $fill);

                $x = $this->GetX();
                $y = $this->GetY();
                $this->MultiCell($column_width, 10, $row['catatan'], 1, 'L', $fill);
                $this->SetXY($x + $column_width, $y);

                $fill = !$fill;
                $this->Ln();
            }
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
        $pdf->Cell(0, 10, 'Periode: ' . $tanggal_awal . ' s/d ' . $tanggal_akhir, 0, 1, 'L');
        $pdf->Ln(5);
    }

    $header = ['Tanggal', 'Nama Siswa', 'Kelas', 'Status', 'Catatan'];
    $pdf->FancyTable($header, $absensi_list);

    if (ob_get_length()) {
        ob_end_clean();
    }

    $pdf->Output('D', 'laporan_absensi_siswa.pdf');
    exit;
}

$downloadParams = $_GET;
$downloadParams['download'] = 'pdf';
$downloadQuery = http_build_query($downloadParams);
?>
<?php include __DIR__ . '/../templates/layout_start.php'; ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Absensi Siswa</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <label>Tanggal Awal:</label>
                                <input type="date" name="tanggal_awal" class="form-control" value="<?= htmlspecialchars($tanggal_awal); ?>"><br>

                                <label>Tanggal Akhir:</label>
                                <input type="date" name="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir); ?>"><br>

                                <label>Kelas:</label>
                                <select name="id_kelas" class="form-control">
                                    <option value="">-- Semua Kelas --</option>
                                    <?php foreach ($kelas_list as $kelas): ?>
                                        <?php $kelasId = (string)($kelas['id_kelas'] ?? ''); ?>
                                        <option value="<?= htmlspecialchars($kelasId); ?>" <?= ((string)$id_kelas === $kelasId) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($kelas['nama_kelas'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select><br>

                                <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                                <a href="?<?= htmlspecialchars($downloadQuery); ?>" class="btn btn-danger">Download PDF</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

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
                                                <td><?= htmlspecialchars($absensi['tanggal']); ?></td>
                                                <td><?= htmlspecialchars($absensi['nama_siswa']); ?></td>
                                                <td><?= htmlspecialchars($absensi['nama_kelas']); ?></td>
                                                <td><?= htmlspecialchars($absensi['status_kehadiran']); ?></td>
                                                <td><?= htmlspecialchars($absensi['catatan']); ?></td>
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
<?php include __DIR__ . '/../templates/layout_end.php'; ?>