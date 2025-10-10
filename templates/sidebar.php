<?php
if (!function_exists('template_asset')) {
    function template_asset(string $path): string
    {
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : rtrim(admin_app_url(''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('template_url')) {
    function template_url(string $path = ''): string
    {
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : rtrim(admin_app_url(''), '/');
        $trimmed = ltrim($path, '/');
        return $base . ($trimmed !== '' ? '/' . $trimmed : '');
    }
}

if (!isset($currentUser) || !is_array($currentUser)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $currentUser = $GLOBALS['currentUser'] ?? ($_SESSION['user'] ?? []);
}

$active_page = $active_page ?? '';
$role = strtolower($currentUser['role'] ?? '');
$is_admin = ($role === 'admin');
$is_guru = ($role === 'guru');

$sectionPrefix = $is_admin ? 'admin' : ($is_guru ? 'guru' : '');
$sectionBase = $sectionPrefix !== '' ? template_url($sectionPrefix) : template_url();
$brandHref = $sectionBase !== '' ? $sectionBase : template_url();
$dashboardHref = rtrim($sectionBase, '/') . '/index.php';

$navActive = function (array $keys) use ($active_page): string {
    return in_array($active_page, $keys, true) ? 'active' : '';
};
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= htmlspecialchars($brandHref, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Salassika <sup>2</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?= $navActive(['dashboard']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($dashboardHref, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <?php if ($is_admin): ?>
    <div class="sidebar-heading">Manajemen Data</div>

    <li class="nav-item <?= $navActive(['guru','list_guru','tambah_guru','edit_guru']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/guru/list_guru.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Data Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['siswa','list_siswa','tambah_siswa','edit_siswa']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/siswa/list_siswa.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-users"></i>
            <span>Data Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['jurusan','list_jurusan','tambah_jurusan','edit_jurusan']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/jurusan/list_jurusan.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-stream"></i>
            <span>Jurusan</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['kelas','list_kelas','tambah_kelas','edit_kelas']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/kelas/list_kelas.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-school"></i>
            <span>Kelas</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['users','list_users','tambah_users','edit_users']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/users/list_users.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-user-cog"></i>
            <span>Pengguna</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Fingerprint</div>

    <li class="nav-item <?= $navActive(['fingerprint','manage_devices']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/fingerprint/manage_devices.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-fingerprint"></i>
            <span>Perangkat</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['fingerprint']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/fingerprint/manage_fingerprint_users.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-id-card"></i>
            <span>Pengguna Fingerprint</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['fingerprint']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/fingerprint/view_logs.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Log Fingerprint</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['sinkronisasi']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/jalankan_sinkronisasi.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-sync"></i>
            <span>Sinkronisasi</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['fingerprint']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/fingerprint/auto_sync_fingerprint.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-redo-alt"></i>
            <span>Auto Sync</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan & Pengaturan</div>

    <li class="nav-item <?= $navActive(['laporan','laporan_absensi']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan/laporan_absensi.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-file-alt"></i>
            <span>Laporan Absensi</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan/laporan_guru.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-user-tie"></i>
            <span>Laporan Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan/laporan_siswa.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-user-graduate"></i>
            <span>Laporan Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan/laporan_project_absensi_sekolah.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-project-diagram"></i>
            <span>Laporan Project</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan/export_excel.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-file-excel"></i>
            <span>Export Excel</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan/export_pdf.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-file-pdf"></i>
            <span>Export PDF</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['jam_kerja']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/pengaturan_jam_kerja.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-clock"></i>
            <span>Pengaturan Jam Kerja</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['optimize_database']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/optimize_database.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-database"></i>
            <span>Optimasi Database</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['whatsapp']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/whatsapp/automation_settings.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fab fa-whatsapp"></i>
            <span>Notifikasi WhatsApp</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['pengaduan']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/pengaduan/index.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-comments"></i>
            <span>Pengaduan</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['realtime']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/realtime/dashboard_realtime.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-broadcast-tower"></i>
            <span>Realtime Monitoring</span>
        </a>
    </li>

    <?php elseif ($is_guru): ?>
    <div class="sidebar-heading">Absensi</div>

    <li class="nav-item <?= $navActive(['absensi_guru']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/absensi_guru.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-user-check"></i>
            <span>Absensi Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['absensi_siswa']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/absensi_siswa.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-users"></i>
            <span>Absensi Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['log_absensi']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/log_absensi.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Log Absensi</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Monitoring</div>

    <li class="nav-item <?= $navActive(['monitor']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/monitor_fingerprint.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-fingerprint"></i>
            <span>Monitor Fingerprint</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['realtime']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/realtime_attendance.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-broadcast-tower"></i>
            <span>Realtime Attendance</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan & Pengguna</div>

    <li class="nav-item <?= $navActive(['laporan_guru']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan_guru.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-file-alt"></i>
            <span>Laporan Guru</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['laporan_siswa']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/laporan_siswa.php', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-user-graduate"></i>
            <span>Laporan Siswa</span>
        </a>
    </li>
    <li class="nav-item <?= $navActive(['list_users_guru']) ?>">
        <a class="nav-link" href="<?= htmlspecialchars($sectionBase . '/list_users_guru.php', ENT_QUOTES, 'UTF-8'); ?>">
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
        <img class="sidebar-card-illustration mb-2" src="<?= htmlspecialchars(template_asset('assets/img/undraw_posting_photo.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="Ilustrasi">
        <p class="text-center mb-2"><strong>Salassika</strong> menggunakan template SB Admin 2.</p>
        <a class="btn btn-success btn-sm" href="#">Pelajari lebih lanjut</a>
    </div>

</ul>
<!-- End of Sidebar -->
