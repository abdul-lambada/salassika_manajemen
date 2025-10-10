<?php
if (!isset($currentUser)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $currentUser = $GLOBALS['currentUser'] ?? ($_SESSION['user'] ?? null);
}

if (!is_array($currentUser) || empty($currentUser)) {
    header('Location: ' . admin_login_url());
    exit;
}

$GLOBALS['currentUser'] = $currentUser;
$GLOBALS['currentUserRole'] = strtolower($currentUser['role'] ?? '');

$userRole = strtolower($currentUser['role'] ?? '');

if (isset($required_role) && $required_role) {
    $expectedRole = strtolower($required_role);
    if ($userRole !== $expectedRole) {
        header('Location: ' . admin_login_url('access_denied'));
        exit;
    }
}

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/navbar.php'; ?>
