<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = defined('APP_URL') ? APP_URL : '';
if (!isset($_SESSION['user'])) {
    $login = $baseUrl ? $baseUrl . '/auth/login.php' : '/auth/login.php';
    header('Location: ' . $login);
    exit;
}
$userRole = strtolower($_SESSION['user']['role'] ?? '');
if (isset($required_role) && $required_role) {
    $expectedRole = strtolower($required_role);
    if ($userRole !== $expectedRole) {
        $login = $baseUrl ? $baseUrl . '/auth/login.php' : '/auth/login.php';
        header('Location: ' . $login);
        exit;
    }
}
include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/navbar.php'; ?>
