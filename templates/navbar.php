<?php
// templates/navbar.php
?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>
    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">
        <!-- Divider -->
        <div class="topbar-divider d-none d-sm-block"></div>
        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?php
                    echo htmlspecialchars(isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Pengguna');
                    ?>
                </span>
                <img class="img-profile rounded-circle"
                    src="<?php echo isset($_SESSION['user']['avatar']) && $_SESSION['user']['avatar'] ? '/absensi_sekolah/' . $_SESSION['user']['avatar'] : '/absensi_sekolah/assets/img/undraw_profile.svg'; ?>"
                    alt="Profil">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="userDropdown">
                <a class="dropdown-item" href="/absensi_sekolah/profil.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profil
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="/absensi_sekolah/auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav> 