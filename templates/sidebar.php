<?php
$BASE = defined('APP_URL') ? APP_URL : '';
$active_page = $active_page ?? '';
$role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : null;
$is_admin = ($role === 'admin');
$is_guru = ($role === 'guru');
$prefix = $BASE . ($is_admin ? '/admin' : ($is_guru ? '/guru' : ''));
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= $BASE ?>/">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Salassika <sup>2</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?= ($active_page==='dashboard'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <?php if ($is_admin): ?>
    <!-- Heading -->
    <div class="sidebar-heading">Manajemen</div>
    <li class="nav-item <?= ($active_page==='guru'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/guru/list_guru.php">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Data Guru</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='siswa'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/siswa/list_siswa.php">
            <i class="fas fa-users"></i>
            <span>Data Siswa</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='jurusan'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/jurusan/list_jurusan.php">
            <i class="fas fa-stream"></i>
            <span>Jurusan</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='kelas'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/kelas/list_kelas.php">
            <i class="fas fa-school"></i>
            <span>Kelas</span></a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Fingerprint</div>
    <li class="nav-item <?= ($active_page==='fingerprint'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/fingerprint/manage_devices.php">
            <i class="fas fa-fingerprint"></i>
            <span>Perangkat</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='sinkronisasi'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/jalankan_sinkronisasi.php">
            <i class="fas fa-sync"></i>
            <span>Sinkronisasi</span></a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan & Pengaturan</div>
    <li class="nav-item <?= ($active_page==='laporan'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/index.php">
            <i class="fas fa-file-alt"></i>
            <span>Laporan</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='jam_kerja'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/pengaturan_jam_kerja.php">
            <i class="fas fa-clock"></i>
            <span>Pengaturan Jam Kerja</span></a>
    </li>
    <?php elseif ($is_guru): ?>
    <!-- Heading -->
    <div class="sidebar-heading">Absensi</div>
    <li class="nav-item <?= ($active_page==='absensi_guru'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/absensi_guru.php">
            <i class="fas fa-user-check"></i>
            <span>Absensi Guru</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='absensi_siswa'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/absensi_siswa.php">
            <i class="fas fa-users"></i>
            <span>Absensi Siswa</span></a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Monitoring</div>
    <li class="nav-item <?= ($active_page==='monitor'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/monitor_fingerprint.php">
            <i class="fas fa-fingerprint"></i>
            <span>Monitor Fingerprint</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='realtime'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/realtime_attendance.php">
            <i class="fas fa-broadcast-tower"></i>
            <span>Realtime Attendance</span></a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan</div>
    <li class="nav-item <?= ($active_page==='laporan_guru'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan_guru.php">
            <i class="fas fa-file-alt"></i>
            <span>Laporan Guru</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='laporan_siswa'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan_siswa.php">
            <i class="fas fa-file-alt"></i>
            <span>Laporan Siswa</span></a>
    </li>
    <?php endif; ?>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

    <!-- Sidebar Message -->
    <div class="sidebar-card d-none d-lg-flex">
        <img class="sidebar-card-illustration mb-2" src="<?= $BASE ?>/assets/img/undraw_posting_photo.svg" alt="...">
        <p class="text-center mb-2"><strong>SB Admin 2</strong> layout adapted for this app.</p>
        <a class="btn btn-success btn-sm" href="#">Learn More</a>
    </div>

</ul>
<!-- End of Sidebar -->
