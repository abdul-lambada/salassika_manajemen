<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$id_pengaduan = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id_pengaduan <= 0 || !admin_validate_csrf($token)) {
    header('Location: list_pengaduan.php?status=error');
    exit;
}

try {
    $conn->beginTransaction();

    $stmt_get = $conn->prepare('SELECT file_pendukung FROM pengaduan WHERE id_pengaduan = :id');
    $stmt_get->bindParam(':id', $id_pengaduan, PDO::PARAM_INT);
    $stmt_get->execute();
    $pengaduan = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$pengaduan) {
        throw new Exception('Pengaduan tidak ditemukan.');
    }

    if (!empty($pengaduan['file_pendukung'])) {
        $file_path = realpath(__DIR__ . '/../../uploads/' . $pengaduan['file_pendukung']);
        $uploadsDir = realpath(__DIR__ . '/../../uploads');
        if ($file_path && strpos($file_path, $uploadsDir) === 0 && is_file($file_path)) {
            @unlink($file_path);
        }
    }

    $stmt = $conn->prepare('DELETE FROM pengaduan WHERE id_pengaduan = :id');
    $stmt->bindParam(':id', $id_pengaduan, PDO::PARAM_INT);
    $stmt->execute();

    $conn->commit();
    header('Location: list_pengaduan.php?status=delete_success');
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    header('Location: list_pengaduan.php?status=error&message=' . urlencode($e->getMessage()));
    exit;
}