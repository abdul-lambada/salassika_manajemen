<?php
require_once '../../assets/vendor/fpdf/fpdf.php';

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,'LAPORAN PROJECT ABSENSI SEKOLAH SALASSIKA',0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,7,'Generated: '.date('d-m-Y H:i'),0,1,'C');
        $this->Ln(2);
    }
    function SectionTitle($label) {
        $this->SetFont('Arial','B',12);
        $this->Cell(0,8,$label,0,1);
    }
    function SectionBody($text) {
        $this->SetFont('Arial','',10);
        $this->MultiCell(0,6,$text);
        $this->Ln(2);
    }
    function BulletList($items) {
        $this->SetFont('Arial','',10);
        foreach ($items as $item) {
            $this->Cell(5);
            $this->Cell(0,6,"- $item",0,1);
        }
        $this->Ln(1);
    }
    function NumberList($items) {
        $this->SetFont('Arial','',10);
        $i = 1;
        foreach ($items as $item) {
            $this->Cell(5);
            $this->Cell(0,6,"$i. $item",0,1);
            $i++;
        }
        $this->Ln(1);
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->AddPage();

// 1. Deskripsi
$pdf->SectionTitle('1. Deskripsi Singkat');
$pdf->SectionBody('Aplikasi Absensi Sekolah adalah sistem web untuk manajemen absensi guru dan siswa, terintegrasi fingerprint (X100-C), pelaporan, pengelolaan data, dan pengaturan jam kerja.');

// 2. Struktur Fitur Utama
$pdf->SectionTitle('2. Struktur Fitur Utama');
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'A. Modul Admin',0,1);
$pdf->BulletList([
    'Manajemen Data Master: Guru, Siswa, Kelas, Jurusan, User',
    'Fingerprint: Sinkronisasi user, mapping UID, test koneksi, log',
    'Laporan: Absensi guru & siswa, export Excel/PDF',
    'Pengaduan: Manajemen pengaduan',
    'Pengaturan Jam Kerja: Jam masuk, pulang, toleransi telat',
    'Sinkronisasi Absensi: Otomatisasi status Hadir/Telat',
    'Realtime Monitoring: Dashboard fingerprint real-time'
]);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'B. Modul Guru',0,1);
$pdf->BulletList([
    'Absensi Guru & Siswa: Input harian manual/otomatis fingerprint',
    'Status Hadir/Telat otomatis sesuai jam kerja admin',
    'Laporan Absensi: Riwayat, statistik, filter',
    'Monitoring Fingerprint: Status absensi real-time',
    'Log Absensi: Melihat & ambil data log fingerprint'
]);

// 3. Integrasi Fingerprint
$pdf->SectionTitle('3. Integrasi Fingerprint');
$pdf->BulletList([
    'Perangkat: ZKTeco X100-C (ZKLib)',
    'Mapping UID fingerprint ke user (guru/siswa) dengan filter role/hak',
    'Sinkronisasi data user & absensi dari device ke database',
    'Test koneksi, troubleshooting, log error',
    'Guru: hanya UID privilege User (0)',
    'Siswa: hanya UID role/hak Pendaftar'
]);

// 4. Pengaturan Jam Kerja
$pdf->SectionTitle('4. Pengaturan Jam Kerja');
$pdf->SectionBody('Disimpan di tabel tbl_jam_kerja. Parameter: Jam Masuk, Jam Pulang, Toleransi Keterlambatan (menit). Setiap perubahan jam kerja oleh admin langsung diikuti oleh penentuan status Hadir/Telat di absensi guru & siswa.');

// 5. Otomatisasi Status Hadir/Telat
$pdf->SectionTitle('5. Otomatisasi Status Hadir/Telat');
$pdf->NumberList([
    'Jika waktu fingerprint <= jam_masuk + toleransi: Hadir',
    'Jika waktu fingerprint > jam_masuk + toleransi: Telat',
    'Jika tidak ada fingerprint, status bisa diisi manual',
    'Dropdown status disable jika fingerprint ada, status otomatis dikirim ke backend'
]);

// 6. Keamanan & Hak Akses
$pdf->SectionTitle('6. Keamanan & Hak Akses');
$pdf->BulletList([
    'Session & Role: Setiap halaman dicek session dan role user',
    'Admin dan guru punya hak akses fitur sesuai rolenya',
    'Validasi unik NIS/NISN/NIP/UID',
    'Validasi input form dan error handling'
]);

// 7. Struktur File & Kode
$pdf->SectionTitle('7. Struktur File & Kode');
$pdf->BulletList([
    'admin: fitur manajemen, fingerprint, laporan, pengaturan, sinkronisasi',
    'guru: absensi, laporan, monitoring, log fingerprint',
    'includes: koneksi database, konfigurasi fingerprint, ZKLib',
    'templates: header, sidebar, navbar, footer, scripts',
    'assets: CSS, JS, gambar, vendor library'
]);

// 8. Testing & Debugging
$pdf->SectionTitle('8. Testing & Debugging');
$pdf->BulletList([
    'Skrip pengujian otomatis: admin/automatic_test.php',
    'Debugging fingerprint: admin/debug_test.php dan test koneksi di menu fingerprint'
]);

// 9. Catatan Pengembangan
$pdf->SectionTitle('9. Catatan Pengembangan');
$pdf->BulletList([
    'Semua fitur utama sudah berjalan dan terintegrasi',
    'Perubahan jam kerja oleh admin langsung berdampak ke absensi guru/siswa',
    'Hak UID fingerprint sudah sesuai role',
    'Status Hadir/Telat sudah otomatis dan dinamis',
    'Struktur file rapi dan mudah dikembangkan'
]);

// 10. Saran Pengembangan Lanjutan
$pdf->SectionTitle('10. Saran Pengembangan Lanjutan');
$pdf->BulletList([
    'Notifikasi otomatis untuk absensi telat/izin',
    'Dashboard statistik lebih interaktif',
    'API mobile untuk absensi online',
    'Multi device fingerprint',
    'Pengelolaan shift/jam kerja khusus'
]);

$pdf->Output('I', 'Laporan_Project_Absensi_Sekolah.pdf');
exit; 