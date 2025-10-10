<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$id_jurusan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id_jurusan <= 0 || !admin_validate_csrf($token)) {
    header('Location: list_jurusan.php?status=error&message=' . urlencode('Permintaan hapus tidak valid.'));
    exit;
}

try {
    $stmt_check = $conn->prepare('SELECT COUNT(*) FROM kelas WHERE id_jurusan = :id_jurusan');
    $stmt_check->bindParam(':id_jurusan', $id_jurusan, PDO::PARAM_INT);
    $stmt_check->execute();
    $kelas_count = (int)$stmt_check->fetchColumn();

    if ($kelas_count > 0) {
        header('Location: list_jurusan.php?status=error&message=' . urlencode("Jurusan tidak bisa dihapus karena masih digunakan oleh $kelas_count kelas."));
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM jurusan WHERE id_jurusan = :id_jurusan');
    $stmt->bindParam(':id_jurusan', $id_jurusan, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: list_jurusan.php?status=delete_success');
    exit;
} catch (Throwable $e) {
    header('Location: list_jurusan.php?status=error&message=' . urlencode('Terjadi kesalahan saat menghapus jurusan.'));
    exit;
}