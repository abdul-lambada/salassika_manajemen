<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$title = 'Halaman Tidak Ditemukan';
$active_page = 'not_found';
$required_role = null;

include __DIR__ . '/../templates/layout_start.php';
include __DIR__ . '/../templates/404.php';
include __DIR__ . '/../templates/layout_end.php';