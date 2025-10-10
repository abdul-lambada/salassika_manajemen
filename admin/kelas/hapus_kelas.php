<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$id_kelas = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id_kelas <= 0 || !admin_validate_csrf($token)) {
    header("Location: list_kelas.php?status=error&message=" . urlencode('Permintaan hapus tidak valid.'));
    exit;
}

try {
    // Cek apakah kelas masih digunakan oleh siswa
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE id_kelas = :id_kelas");
    $stmt_check->bindParam(':id_kelas', $id_kelas);
    $stmt_check->execute();
    $siswa_count = $stmt_check->fetchColumn();

    if ($siswa_count > 0) {
        header("Location: list_kelas.php?status=error&message=" . urlencode("Kelas tidak bisa dihapus karena masih digunakan oleh $siswa_count siswa."));
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM kelas WHERE id_kelas = :id_kelas");
    $stmt->bindParam(':id_kelas', $id_kelas);
    $stmt->execute();

    header("Location: list_kelas.php?status=delete_success");
    exit();
} catch (Throwable $e) {
    header("Location: list_kelas.php?status=error");
    exit();
}