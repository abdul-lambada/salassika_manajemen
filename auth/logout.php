<?php
session_start();
session_destroy();
header("Location: /absensi_sekolah/auth/login.php");
exit;
?>