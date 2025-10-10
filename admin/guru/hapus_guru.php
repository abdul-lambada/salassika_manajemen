<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$id_guru = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id_guru <= 0 || !admin_validate_csrf($token)) {
    header('Location: list_guru.php?status=error&msg=' . urlencode('Permintaan hapus tidak valid.'));
    exit;
}

try {
    $conn->beginTransaction();

    $stmt_get = $conn->prepare('SELECT user_id FROM guru WHERE id_guru = ?');
    $stmt_get->execute([$id_guru]);
    $guru = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$guru) {
        throw new Exception('Data guru tidak ditemukan.');
    }

    if (!empty($guru['user_id'])) {
        $stmt_check = $conn->prepare('SELECT COUNT(*) FROM siswa WHERE user_id = ?');
        $stmt_check->execute([$guru['user_id']]);
        if ($stmt_check->fetchColumn() == 0) {
            $stmt_user = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt_user->execute([$guru['user_id']]);
        }
    }

    $stmt = $conn->prepare('DELETE FROM guru WHERE id_guru = ?');
    $stmt->execute([$id_guru]);

    $conn->commit();
    header('Location: list_guru.php?status=delete_success');
    exit;
} catch (Throwable $e) {
    $conn->rollBack();
    header('Location: list_guru.php?status=error&msg=' . urlencode($e->getMessage()));
    exit;
}