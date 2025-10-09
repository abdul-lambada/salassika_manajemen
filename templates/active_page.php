<?php
// Determine $active_page if not set by the page
if (!isset($active_page) || !$active_page) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');

    // Default
    $active_page = 'dashboard';

    // Admin section
    if (strpos($uri, '/admin/') !== false) {
        if (strpos($uri, '/guru/') !== false) $active_page = 'guru';
        elseif (strpos($uri, '/siswa/') !== false) $active_page = 'siswa';
        elseif (strpos($uri, '/jurusan/') !== false) $active_page = 'jurusan';
        elseif (strpos($uri, '/kelas/') !== false) $active_page = 'kelas';
        elseif (strpos($uri, '/fingerprint/') !== false) $active_page = 'fingerprint';
        elseif (strpos($uri, 'jalankan_sinkronisasi.php') !== false) $active_page = 'sinkronisasi';
        elseif (strpos($uri, '/laporan/') !== false) $active_page = 'laporan';
        elseif (strpos($uri, 'pengaturan_jam_kerja.php') !== false) $active_page = 'jam_kerja';
        elseif ($script === 'index.php') $active_page = 'dashboard';
    }

    // Guru section
    elseif (strpos($uri, '/guru/') !== false) {
        if (strpos($uri, 'absensi_guru.php') !== false) $active_page = 'absensi_guru';
        elseif (strpos($uri, 'absensi_siswa.php') !== false) $active_page = 'absensi_siswa';
        elseif (strpos($uri, 'monitor_fingerprint.php') !== false) $active_page = 'monitor';
        elseif (strpos($uri, 'realtime_attendance.php') !== false) $active_page = 'realtime';
        elseif (strpos($uri, 'laporan_guru.php') !== false) $active_page = 'laporan_guru';
        elseif (strpos($uri, 'laporan_siswa.php') !== false) $active_page = 'laporan_siswa';
        elseif ($script === 'index.php') $active_page = 'dashboard';
    }
}
