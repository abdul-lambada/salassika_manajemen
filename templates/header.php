<?php
if (!isset($title)) {
    $title = 'Dashboard';
}

$appBaseUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : rtrim(admin_app_url(''), '/');

if (!function_exists('template_asset')) {
    function template_asset(string $path): string
    {
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : rtrim(admin_app_url(''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

include_once __DIR__ . '/active_page.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Font Awesome (CDN fallback if local not present) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-dymI6X1YQKqj4kNn7cFq8m1qVqf2u7oV3kVX0Qw3Yz5w2d2k4q5Q7lq7w7Zq9x1H1Jff7kYp4Qq3q5q2z5yLw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Core Theme CSS (SB Admin 2) -->
    <link href="<?= htmlspecialchars(template_asset('assets/css/sb-admin-2.min.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
</head>
<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">
