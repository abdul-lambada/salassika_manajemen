<?php
$BASE = defined('APP_URL') ? APP_URL : '';
$active_page = $active_page ?? '';
$role = $_SESSION['user']['role'] ?? null;
$is_admin = ($role === 'admin');
$is_guru = ($role === 'guru');
$prefix = $BASE . ($is_admin ? '/admin' : ($is_guru ? '/guru' : ''));
$brandHref = $prefix ?: ($BASE ?: '/');
$dashboardHref = ($prefix ?: ($BASE ?: '')) . '/index.php';
$navActive = function (array $keys) use ($active_page) {
    return in_array($active_page, $keys, true) ? 'active' : '';
};
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= $brandHref ?>">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Salassika <sup>2</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?= $navActive(['dashboard']) ?>">
        <a class="nav-link" href="<?= $dashboardHref ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <?php if ($is_admin): ?>
    <div class="sidebar-heading">Manajemen Data</div>

    <li class="nav-item <?= $navActive(['guru']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/guru/list_guru.php">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Data Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['siswa']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/siswa/list_siswa.php">
            <i class="fas fa-users"></i>
            <span>Data Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['jurusan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/jurusan/list_jurusan.php">
            <i class="fas fa-stream"></i>
            <span>Jurusan</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['kelas']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/kelas/list_kelas.php">
            <i class="fas fa-school"></i>
            <span>Kelas</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['users']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/users/list_users.php">
            <i class="fas fa-user-cog"></i>
            <span>Pengguna</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Fingerprint</div>

    <li class="nav-item <?= $navActive(['fingerprint']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/fingerprint/manage_devices.php">
            <i class="fas fa-fingerprint"></i>
            <span>Perangkat</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['fingerprint']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/fingerprint/manage_fingerprint_users.php">
            <i class="fas fa-id-card"></i>
            <span>Pengguna Fingerprint</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['fingerprint']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/fingerprint/view_logs.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Log Fingerprint</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['sinkronisasi']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/jalankan_sinkronisasi.php">
            <i class="fas fa-sync"></i>
            <span>Sinkronisasi</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['fingerprint']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/fingerprint/auto_sync_fingerprint.php">
            <i class="fas fa-redo-alt"></i>
            <span>Auto Sync</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan & Pengaturan</div>

    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/laporan_absensi.php">
            <i class="fas fa-file-alt"></i>
            <span>Laporan Absensi</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/laporan_guru.php">
            <i class="fas fa-user-tie"></i>
            <span>Laporan Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/laporan_siswa.php">
            <i class="fas fa-user-graduate"></i>
            <span>Laporan Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/laporan_project_absensi_sekolah.php">
            <i class="fas fa-project-diagram"></i>
            <span>Laporan Project</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/export_excel.php">
            <i class="fas fa-file-excel"></i>
            <span>Export Excel</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/export_pdf.php">
            <i class="fas fa-file-pdf"></i>
            <span>Export PDF</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['jam_kerja']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/pengaturan_jam_kerja.php">
            <i class="fas fa-clock"></i>
            <span>Pengaturan Jam Kerja</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['optimize_database']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/optimize_database.php">
            <i class="fas fa-database"></i>
            <span>Optimasi Database</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['whatsapp']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/whatsapp/automation_settings.php">
            <i class="fab fa-whatsapp"></i>
            <span>Notifikasi WhatsApp</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['pengaduan']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/pengaduan/index.php">
            <i class="fas fa-comments"></i>
            <span>Pengaduan</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['realtime']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/realtime/dashboard_realtime.php">
            <i class="fas fa-broadcast-tower"></i>
            <span>Realtime Monitoring</span>
        </a>
    </li>

    <?php elseif ($is_guru): ?>
    <div class="sidebar-heading">Absensi</div>

    <li class="nav-item <?= $navActive(['absensi_guru']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/absensi_guru.php">
            <i class="fas fa-user-check"></i>
            <span>Absensi Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['absensi_siswa']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/absensi_siswa.php">
            <i class="fas fa-users"></i>
            <span>Absensi Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['log_absensi']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/log_absensi.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Log Absensi</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Monitoring</div>

    <li class="nav-item <?= $navActive(['monitor']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/monitor_fingerprint.php">
            <i class="fas fa-fingerprint"></i>
            <span>Monitor Fingerprint</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['realtime']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/realtime_attendance.php">
            <i class="fas fa-broadcast-tower"></i>
            <span>Realtime Attendance</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan & Pengguna</div>

    <li class="nav-item <?= $navActive(['laporan_guru']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan_guru.php">
            <i class="fas fa-file-alt"></i>
            <span>Laporan Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan_siswa']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan_siswa.php">
            <i class="fas fa-user-graduate"></i>
            <span>Laporan Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['list_users_guru']) ?>">
        <a class="nav-link" href="<?= $prefix ?>/list_users_guru.php">
            <i class="fas fa-user-friends"></i>
            <span>Daftar Pengguna</span>
        </a>
    </li>
    <?php endif; ?>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

    <div class="sidebar-card d-none d-lg-flex">
        <img class="sidebar-card-illustration mb-2" src="<?= $BASE ?>/assets/img/undraw_posting_photo.svg" alt="...">
        <p class="text-center mb-2"><strong>Salassika</strong> menggunakan template SB Admin 2.</p>
        <a class="btn btn-success btn-sm" href="#">Pelajari lebih lanjut</a>
    </div>

</ul>
<!-- End of Sidebar -->
