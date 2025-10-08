<?php
session_start();
include '../../includes/db.php';
require_once '../../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';

// Cek hak akses
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'guru'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Anda tidak memiliki akses ke halaman ini.");
}

// Ambil parameter filter dari GET request
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$tipe_user = isset($_GET['tipe_user']) ? $_GET['tipe_user'] : 'all';
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';
$status_kehadiran = isset($_GET['status_kehadiran']) ? $_GET['status_kehadiran'] : '';

try {
    // Logika query sama persis dengan di halaman laporan_absensi.php
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

    $query = "
        SELECT 
            u.name as nama_user, u.role as tipe_user, g.nip, s.nis, k.nama_kelas,
            kh.timestamp as waktu_fingerprint, kh.status as status_fingerprint,
            ag.status_kehadiran as status_guru, ag.catatan as catatan_guru,
            asis.status_kehadiran as status_siswa, asis.catatan as catatan_siswa,
            COALESCE(ag.tanggal, asis.tanggal) as tanggal_absensi
        FROM users u
        LEFT JOIN guru g ON u.id = g.user_id
        LEFT JOIN siswa s ON u.id = s.user_id
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id AND DATE(kh.timestamp) BETWEEN :tanggal_mulai AND :tanggal_akhir
        LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru AND ag.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa AND asis.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        $where_clause
        ORDER BY u.name, tanggal_absensi DESC
    ";

    $params[':tanggal_mulai'] = $tanggal_mulai;
    $params[':tanggal_akhir'] = $tanggal_akhir;

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $laporan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Membuat objek PHPExcel
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator("Sistem Absensi Sekolah")
                                 ->setLastModifiedBy("Sistem Absensi Sekolah")
                                 ->setTitle("Laporan Absensi");
    
    $sheet = $objPHPExcel->setActiveSheetIndex(0);

    // Set Header
    $headers = ['Nama', 'Tipe', 'NIP/NIS', 'Kelas', 'Tanggal', 'Waktu Fingerprint', 'Status Manual', 'Status Fingerprint', 'Catatan'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }

    // Mengisi data
    $rowNum = 2;
    foreach ($laporan_data as $data) {
        $status_manual = $data['status_guru'] ?: $data['status_siswa'];
        $catatan = $data['catatan_guru'] ?: $data['catatan_siswa'];

        $sheet->setCellValue('A' . $rowNum, $data['nama_user']);
        $sheet->setCellValue('B' . $rowNum, ucfirst($data['tipe_user']));
        $sheet->setCellValue('C' . $rowNum, $data['nip'] ?: $data['nis']);
        $sheet->setCellValue('D' . $rowNum, $data['nama_kelas'] ?: '-');
        $sheet->setCellValue('E' . $rowNum, $data['tanggal_absensi']);
        $sheet->setCellValue('F' . $rowNum, $data['waktu_fingerprint'] ? date('H:i:s', strtotime($data['waktu_fingerprint'])) : '-');
        $sheet->setCellValue('G' . $rowNum, $status_manual ?: '-');
        $sheet->setCellValue('H' . $rowNum, $data['status_fingerprint'] ?: '-');
        $sheet->setCellValue('I' . $rowNum, $catatan ?: '-');
        $rowNum++;
    }
    
    // Set response header untuk download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="laporan_absensi_'.date('Ymd').'.xls"');
    header('Cache-Control: max-age=0');
    
    // Tulis file ke output
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error saat generate Excel: " . $e->getMessage());
}
?> 