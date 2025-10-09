<?php if ($is_admin): ?>
    <!-- Heading -->
    <div class="sidebar-heading">Manajemen Data</div>
    <li class="nav-item dropdown <?= ($active_page==='guru' || $active_page==='kelas' || $active_page==='users'?'active':'') ?>">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Data Guru</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenu">
            <a class="dropdown-item" href="<?= $prefix ?>/guru/list_guru.php">Data Guru</a>
            <a class="dropdown-item" href="<?= $prefix ?>/kelas/list_kelas.php">Kelas</a>
            <a class="dropdown-item" href="<?= $prefix ?>/users/list_users.php">Pengguna</a>
        </div>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Fingerprint</div>
    <li class="nav-item dropdown <?= ($active_page==='fingerprint_devices' || $active_page==='fingerprint_users' || $active_page==='fingerprint_logs'?'active':'') ?>">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-fingerprint"></i>
            <span>Fingerprint</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenu">
            <a class="dropdown-item" href="<?= $prefix ?>/fingerprint/manage_devices.php">Perangkat</a>
            <a class="dropdown-item" href="<?= $prefix ?>/fingerprint/manage_fingerprint_users.php">Pengguna Fingerprint</a>
            <a class="dropdown-item" href="<?= $prefix ?>/fingerprint/view_logs.php">Log Fingerprint</a>
        </div>
    </li>
    <li class="nav-item <?= ($active_page==='sinkronisasi'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/jalankan_sinkronisasi.php">
            <i class="fas fa-sync"></i>
            <span>Sinkronisasi</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='auto_sync'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/fingerprint/auto_sync_fingerprint.php">
            <i class="fas fa-redo-alt"></i>
            <span>Auto Sync</span></a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan & Pengaturan</div>
    <li class="nav-item dropdown <?= ($active_page==='laporan_absensi' || $active_page==='laporan_guru' || $active_page==='laporan_siswa'?'active':'') ?>">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-file-alt"></i>
            <span>Laporan</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenu">
            <a class="dropdown-item" href="<?= $prefix ?>/laporan/laporan_absensi.php">Laporan Absensi</a>
            <a class="dropdown-item" href="<?= $prefix ?>/laporan/laporan_guru.php">Laporan Guru</a>
            <a class="dropdown-item" href="<?= $prefix ?>/laporan/laporan_siswa.php">Laporan Siswa</a>
        </div>
    </li>
    <li class="nav-item <?= ($active_page==='export_excel'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/export_excel.php">
            <i class="fas fa-file-excel"></i>
            <span>Export Excel</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='export_pdf'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/laporan/export_pdf.php">
            <i class="fas fa-file-pdf"></i>
            <span>Export PDF</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='pengaturan_jam_kerja'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/pengaturan_jam_kerja.php">
            <i class="fas fa-clock"></i>
            <span>Pengaturan Jam Kerja</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='optimize_database'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/optimize_database.php">
            <i class="fas fa-database"></i>
            <span>Optimasi Database</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='whatsapp'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/whatsapp/index.php">
            <i class="fab fa-whatsapp"></i>
            <span>Notifikasi WhatsApp</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='pengaduan'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/pengaduan/index.php">
            <i class="fas fa-comments"></i>
            <span>Pengaduan</span></a>
    </li>
    <li class="nav-item <?= ($active_page==='realtime'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/realtime/index.php">
            <i class="fas fa-broadcast-tower"></i>
            <span>Realtime Monitoring</span></a>
    </li>

    <?php elseif ($is_guru): ?>
    <!-- Heading -->
    <div class="sidebar-heading">Absensi</div>
    <li class="nav-item dropdown <?= ($active_page==='absensi_guru' || $active_page==='absensi_siswa'?'active':'') ?>">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user-check"></i>
            <span>Absensi</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenu">
            <a class="dropdown-item" href="<?= $prefix ?>/absensi_guru.php">Absensi Guru</a>
            <a class="dropdown-item" href="<?= $prefix ?>/absensi_siswa.php">Absensi Siswa</a>
        </div>
    </li>
    <li class="nav-item <?= ($active_page==='log_absensi'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/log_absensi.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Log Absensi</span></a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Monitoring</div>
    <li class="nav-item dropdown <?= ($active_page==='monitor_fingerprint' || $active_page==='realtime_attendance'?'active':'') ?>">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-fingerprint"></i>
            <span>Monitoring</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenu">
            <a class="dropdown-item" href="<?= $prefix ?>/monitor_fingerprint.php">Monitor Fingerprint</a>
            <a class="dropdown-item" href="<?= $prefix ?>/realtime_attendance.php">Realtime Attendance</a>
        </div>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Laporan & Pengguna</div>
    <li class="nav-item dropdown <?= ($active_page==='laporan_guru' || $active_page==='laporan_siswa'?'active':'') ?>">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-file-alt"></i>
            <span>Laporan</span>
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenu">
            <a class="dropdown-item" href="<?= $prefix ?>/laporan_guru.php">Laporan Guru</a>
            <a class="dropdown-item" href="<?= $prefix ?>/laporan_siswa.php">Laporan Siswa</a>
        </div>
    </li>
    <li class="nav-item <?= ($active_page==='list_users_guru'?'active':'') ?>">
        <a class="nav-link" href="<?= $prefix ?>/list_users_guru.php">
            <i class="fas fa-user-friends"></i>
            <span>Daftar Pengguna</span></a>
    </li>
    <?php endif; ?>
</ul>
<!-- End of Sidebar -->
