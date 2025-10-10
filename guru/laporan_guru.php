<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$normalizeDateParam = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return ($date !== false && $date->format('Y-m-d') === $value) ? $value : '';
};

$normalizeIdParam = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '' || !ctype_digit($value)) {
        return '';
    }

    return ltrim($value, '0') === '' ? '0' : ltrim($value, '0');
};

$title = 'Laporan Absensi Guru';
$active_page = 'laporan_guru';
$required_role = ($currentUser['role'] ?? '') === 'admin' ? null : 'guru';

$tanggal_awal = $normalizeDateParam($_GET['tanggal_awal'] ?? '');
$tanggal_akhir = $normalizeDateParam($_GET['tanggal_akhir'] ?? '');
$id_guru_param = $normalizeIdParam($_GET['id_guru'] ?? '');
$id_guru = $id_guru_param !== '' ? (int) $id_guru_param : null;

$downloadAction = ($_GET['download'] ?? '') === 'pdf' ? 'pdf' : '';

$downloadParams = array_filter(
    [
        'tanggal_awal' => $tanggal_awal,
        'tanggal_akhir' => $tanggal_akhir,
        'id_guru' => $id_guru_param,
        'download' => 'pdf',
    ],
    static function ($value) {
        return $value !== '' && $value !== null;
    }
);
$downloadQuery = http_build_query($downloadParams);

$guru_list = [];
$laporan_list = [];
$error_message = '';

try {
    $stmtGuru = $conn->query('SELECT id_guru, nama_guru FROM guru ORDER BY nama_guru');
    $guru_list = $stmtGuru->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT
            ag.tanggal,
            g.nama_guru,
            g.nip,
            ag.status_kehadiran,
            ag.catatan,
            COALESCE(kh.timestamp, '') AS waktu_fingerprint,
            COALESCE(kh.verification_mode, '') AS mode_verifikasi
        FROM absensi_guru ag
        JOIN guru g ON ag.id_guru = g.id_guru
        LEFT JOIN users u ON g.user_id = u.id
        LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id AND DATE(kh.timestamp) = ag.tanggal
        WHERE 1 = 1
    ";

    $params = [];

    if ($tanggal_awal !== '' && $tanggal_akhir !== '') {
        $sql .= ' AND ag.tanggal BETWEEN :tanggal_awal AND :tanggal_akhir';
        $params[':tanggal_awal'] = $tanggal_awal;
        $params[':tanggal_akhir'] = $tanggal_akhir;
    }

    if ($id_guru !== null) {
        $sql .= ' AND ag.id_guru = :id_guru';
        $params[':id_guru'] = $id_guru;
    }

    $sql .= ' ORDER BY ag.tanggal DESC, g.nama_guru ASC';

    $stmtLaporan = $conn->prepare($sql);
    $stmtLaporan->execute($params);
    $laporan_list = $stmtLaporan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    admin_log_message('laporan_guru_errors.log', 'Database error: ' . $e->getMessage(), 'ERROR');
    $error_message = 'Terjadi kesalahan saat mengambil data laporan absensi guru.';
}

if ($downloadAction === 'pdf') {
    $fpdfPaths = [
        '../vendor/fpdf/fpdf.php',
        '../fpdf/fpdf.php',
        '../lib/fpdf/fpdf.php',
        '../includes/fpdf/fpdf.php',
        '../assets/vendor/fpdf/fpdf.php'
    ];

    $fpdfFound = false;
    foreach ($fpdfPaths as $path) {
        if (file_exists($path)) {
            require $path;
            $fpdfFound = true;
            break;
        }
    }

    if (!$fpdfFound) {
        echo 'FPDF library not found. Please install FPDF or correct the path.';
        echo "<br><a href='laporan_guru.php'>Back to Report</a>";
        exit;
    }

    class PDF extends FPDF
    {
        public function Header()
        {
            $logoPath = '../../assets/img/logo.jpg';
            if (file_exists($logoPath)) {
                $this->Image($logoPath, 10, 10, 20);
            }

            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 5, 'YAYASAN ISLAM AL-AMIIN', 0, 1, 'C');
            $this->Cell(0, 5, 'SMK AL-AMIIN SANGKANHURIP', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Jl. Cibiru No. 01 Desa Sangkanhurip Kecamatan Sindang Kabupaten Majalengka 45471', 0, 1, 'C');
            $this->Cell(0, 5, 'Website: www.smkalamiin.sch.id | E-mail: smkalamiin.sch@gmail.com | Telp: 0233-8514332', 0, 1, 'C');
            $this->Ln(10);
            $this->SetLineWidth(0.5);
            $this->Line(10, 35, 200, 35);
            $this->Ln(10);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'LAPORAN ABSENSI GURU', 0, 1, 'C');
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
            $marginLeft = 10;
            $marginRight = 10;
            $pageWidth = 210 - $marginLeft - $marginRight;
            $columnWidths = [
                $pageWidth * 0.16,
                $pageWidth * 0.24,
                $pageWidth * 0.15,
                $pageWidth * 0.2,
                $pageWidth * 0.25,
            ];

            $this->SetFillColor(230, 230, 230);
            $this->SetTextColor(0);
            $this->SetDrawColor(128, 128, 128);
            $this->SetLineWidth(0.3);
            $this->SetFont('Arial', 'B', 10);

            foreach ($header as $index => $col) {
                $this->Cell($columnWidths[$index], 10, $col, 1, 0, 'C', true);
            }
            $this->Ln();

            $this->SetFillColor(245, 245, 245);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 10);

            $fill = false;
            foreach ($data as $row) {
                $this->Cell($columnWidths[0], 10, $row['tanggal'], 1, 0, 'C', $fill);
                $this->Cell($columnWidths[1], 10, $row['nama_guru'], 1, 0, 'L', $fill);
                $this->Cell($columnWidths[2], 10, $row['status_kehadiran'], 1, 0, 'C', $fill);
                $this->Cell($columnWidths[3], 10, $row['waktu_fingerprint'], 1, 0, 'C', $fill);

                $x = $this->GetX();
                $y = $this->GetY();
                $this->MultiCell($columnWidths[4], 10, $row['catatan'], 1, 'L', $fill);
                $this->SetXY($x + $columnWidths[4], $y);

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

    if ($id_guru !== null) {
        $selectedGuru = array_values(array_filter($guru_list, static function ($guru) use ($id_guru) {
            return (int) ($guru['id_guru'] ?? 0) === $id_guru;
        }));
        $guruName = $selectedGuru[0]['nama_guru'] ?? '';
        if ($guruName !== '') {
            $pdf->Cell(0, 8, 'Guru: ' . $guruName, 0, 1, 'L');
        }
    }

    $pdfData = array_map(static function ($row) {
        return [
            'tanggal' => $row['tanggal'] ?? '',
            'nama_guru' => $row['nama_guru'] ?? '',
            'status_kehadiran' => $row['status_kehadiran'] ?? '',
            'waktu_fingerprint' => $row['waktu_fingerprint'] ?? '',
            'catatan' => $row['catatan'] ?? '',
        ];
    }, $laporan_list);

    $header = ['Tanggal', 'Nama Guru', 'Status', 'Fingerprint', 'Catatan'];
    $pdf->FancyTable($header, $pdfData);

    if (ob_get_length()) {
        ob_end_clean();
    }

    $pdf->Output('D', 'laporan_absensi_guru.pdf');
    exit;
}
?>
<?php include __DIR__ . '/../templates/layout_start.php'; ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <?php if ($error_message !== ''): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan Absensi Guru</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="tanggal_awal">Tanggal Awal</label>
                                        <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="<?= htmlspecialchars($tanggal_awal, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="tanggal_akhir">Tanggal Akhir</label>
                                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="id_guru">Guru</label>
                                        <select name="id_guru" id="id_guru" class="form-control">
                                            <option value="">-- Semua Guru --</option>
                                            <?php foreach ($guru_list as $guru): ?>
                                                <?php $guruId = (string) ($guru['id_guru'] ?? ''); ?>
                                                <option value="<?= htmlspecialchars($guruId, ENT_QUOTES, 'UTF-8'); ?>" <?= ((string) $id_guru === $guruId) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($guru['nama_guru'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                                        <a href="?<?= htmlspecialchars($downloadQuery, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-danger">Download PDF</a>
                                    </div>
                                    <a href="laporan_guru.php" class="btn btn-secondary">Reset</a>
                                </div>
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
                                <table class="table table-bordered table-responsive-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nama Guru</th>
                                            <th>NIP</th>
                                            <th>Status Kehadiran</th>
                                            <th>Fingerprint</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($laporan_list)): ?>
                                            <?php foreach ($laporan_list as $laporan): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($laporan['tanggal'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($laporan['nama_guru'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($laporan['nip'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($laporan['status_kehadiran'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <?php if (!empty($laporan['waktu_fingerprint'])): ?>
                                                            <span class="badge badge-success">
                                                                <?= htmlspecialchars(date('H:i', strtotime($laporan['waktu_fingerprint'])), ENT_QUOTES, 'UTF-8'); ?>
                                                            </span>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars($laporan['mode_verifikasi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($laporan['catatan'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Tidak ada data absensi guru untuk filter ini.</td>
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
<?php include __DIR__ . '/../templates/layout_end.php'; ?>