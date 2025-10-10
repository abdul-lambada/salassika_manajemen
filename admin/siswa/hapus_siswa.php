<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id <= 0 || !admin_validate_csrf($token)) {
    header("Location: list_siswa.php?status=error");
    exit;
}

try {
    $conn->beginTransaction();

    $stmt_get = $conn->prepare("SELECT user_id FROM siswa WHERE id_siswa = ?");
    $stmt_get->execute([$id]);
    $siswa = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        throw new Exception('Data siswa tidak ditemukan.');
    }

    if ($siswa['user_id']) {
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM guru WHERE user_id = ?");
        $stmt_check->execute([$siswa['user_id']]);
        if ((int)$stmt_check->fetchColumn() === 0) {
            $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_user->execute([$siswa['user_id']]);
        }
    }

    $stmt = $conn->prepare("DELETE FROM siswa WHERE id_siswa = ?");
    $stmt->execute([$id]);

    $conn->commit();
    header("Location: list_siswa.php?status=delete_success");
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    header("Location: list_siswa.php?status=error&message=" . urlencode($e->getMessage()));
    exit;
}