<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id <= 0 || !admin_validate_csrf($token)) {
    header('Location: list_users.php?status=error');
    exit;
}

try {
    $conn->beginTransaction();

    // Cek apakah user terkait dengan guru atau siswa
    $stmt_check_guru = $conn->prepare('SELECT id_guru FROM guru WHERE user_id = ?');
    $stmt_check_guru->execute([$id]);
    $guru = $stmt_check_guru->fetch(PDO::FETCH_ASSOC);

    $stmt_check_siswa = $conn->prepare('SELECT id_siswa FROM siswa WHERE user_id = ?');
    $stmt_check_siswa->execute([$id]);
    $siswa = $stmt_check_siswa->fetch(PDO::FETCH_ASSOC);

    if ($guru || $siswa) {
        throw new Exception('User tidak dapat dihapus karena masih terkait dengan data guru atau siswa');
    }

    // Hapus data dari tabel users
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);

    $conn->commit();
    header('Location: list_users.php?status=delete_success');
    exit();
} catch (Throwable $e) {
    $conn->rollBack();
    header('Location: list_users.php?status=error');
    exit();
}