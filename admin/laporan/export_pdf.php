<?php
session_start();
include '../../includes/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'guru'])) {
    header("Location: ../../auth/login.php");
    exit;
}

require '../../assets/vendor/fpdf/fpdf.php';

// Filter parameters
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$tipe_user = isset($_GET['tipe_user']) ? $_GET['tipe_user'] : 'all';
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';
$status_kehadiran = isset($_GET['status_kehadiran']) ? $_GET['status_kehadiran'] : '';

try {
    // Build query conditions
    $where_conditions = [];
    $params = [];

    if ($tipe_user === 'guru') {
        $where_conditions[] = "u.role = 'guru'";
    } elseif ($tipe_user === 'siswa') {
        $where_conditions[] = "u.role = 'siswa'";
    }

    if ($id_kelas && $tipe_user === 'siswa') {
        $where_conditions[] = "s.id_kelas = :id_kelas";
        $params[':id_kelas'] = $id_kelas;
    }

    if ($status_kehadiran) {
        $where_conditions[] = "COALESCE(ag.status_kehadiran, asis.status_kehadiran) = :status_kehadiran";
        $params[':status_kehadiran'] = $status_kehadiran;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Query untuk data absensi
    $query = "
        SELECT 
            u.name as nama_user,
            u.role as tipe_user,
            u.uid as fingerprint_uid,
            g.nip,
            s.nis,
            s.nisn,
            k.nama_kelas,
            kh.timestamp as waktu_fingerprint,
            kh.verification_mode,
            kh.status as status_fingerprint,
            ag.status_kehadiran as status_guru,
            ag.catatan as catatan_guru,
            asis.status_kehadiran as status_siswa,
            asis.catatan as catatan_siswa,
            ag.tanggal as tanggal_guru,
            asis.tanggal as tanggal_siswa
        FROM users u
        LEFT JOIN guru g ON u.id = g.user_id
        LEFT JOIN siswa s ON u.id = s.user_id
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id 
            AND DATE(kh.timestamp) BETWEEN :tanggal_mulai AND :tanggal_akhir
        LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru 
            AND ag.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa 
            AND asis.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        $where_clause
        ORDER BY u.name, COALESCE(ag.tanggal, asis.tanggal) DESC
    ";

    $params[':tanggal_mulai'] = $tanggal_mulai;
    $params[':tanggal_akhir'] = $tanggal_akhir;

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $laporan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PDF Export
    class PDF extends FPDF {
        function Header() {
            global $tanggal_mulai, $tanggal_akhir;
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10,'Laporan Absensi',0,1,'C');
            $this->SetFont('Arial','',10);
            $this->Cell(0,7,'Periode: ' . date('d M Y', strtotime($tanggal_mulai)) . ' s/d ' . date('d M Y', strtotime($tanggal_akhir)),0,1,'C');
            $this->Ln(5);
            
            // Table header
            $this->SetFont('Arial','B',8);
            $header = ['Nama', 'Tipe', 'NIP/NIS', 'Kelas', 'Tanggal', 'Waktu FP', 'Status Manual', 'Status FP', 'Catatan'];
            // Adjusted widths for better layout
            $widths = [50, 15, 35, 25, 22, 20, 25, 25, 60]; 
            for($i=0; $i < count($header); $i++) {
                $this->Cell($widths[$i], 7, $header[$i], 1, 0, 'C');
            }
            $this->Ln();
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Halaman '.$this->PageNo().'/{nb}',0,0,'C');
        }
    }

    $pdf = new PDF('L','mm','A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',8);
    // Adjusted widths to match header
    $widths = [50, 15, 35, 25, 22, 20, 25, 25, 60];

    foreach ($laporan_data as $data) {
        $status_manual = $data['status_guru'] ?: $data['status_siswa'];
        $catatan = $data['catatan_guru'] ?: $data['catatan_siswa'];
        $tanggal = $data['tanggal_guru'] ?: $data['tanggal_siswa'];
        $waktu_fp = $data['waktu_fingerprint'] ? date('H:i:s', strtotime($data['waktu_fingerprint'])) : '-';

        $pdf->Cell($widths[0], 6, $data['nama_user'], 1, 0, 'L');
        $pdf->Cell($widths[1], 6, ucfirst($data['tipe_user']), 1, 0, 'C');
        $pdf->Cell($widths[2], 6, $data['nip'] ?: $data['nis'], 1, 0, 'L');
        $pdf->Cell($widths[3], 6, $data['nama_kelas'] ?: '-', 1, 0, 'L');
        $pdf->Cell($widths[4], 6, $tanggal ? date('d-m-Y', strtotime($tanggal)) : '-', 1, 0, 'C');
        $pdf->Cell($widths[5], 6, $waktu_fp, 1, 0, 'C');
        $pdf->Cell($widths[6], 6, $status_manual ?: '-', 1, 0, 'C');
        $pdf->Cell($widths[7], 6, $data['status_fingerprint'] ?: '-', 1, 0, 'C');
        $pdf->Cell($widths[8], 6, $catatan ?: '-', 1, 0, 'L');
        $pdf->Ln();
    }
    
    $filename = 'Laporan_Absensi_' . date('Ymd') . '.pdf';
    $pdf->Output('I', $filename);
    exit;

} catch (Exception $e) {
    error_log("Export PDF Error: " . $e->getMessage());
    header("Location: laporan_absensi.php?error=export_failed");
    exit;
}
?> 