<?php
// Periksa apakah sesi sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo isset(
        $title) ? $title : 'Absensi Sekolah'; ?></title>
    <link href="/absensi_sekolah/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/absensi_sekolah/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/absensi_sekolah/assets/css/mobile-enhancements.css" rel="stylesheet">
    <link href="/absensi_sekolah/assets/css/charts-mobile.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="/absensi_sekolah/assets/img/logo.jpg">
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
</head>
<body id="page-top">
    <div id="wrapper">
