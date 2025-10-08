<?php
session_start();
include '../../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Proses hapus data guru
if (isset($_GET['id'])) {
    $id_guru = $_GET['id'];
    try {
        $conn->beginTransaction();
        // Ambil user_id dari guru
        $stmt_get = $conn->prepare("SELECT user_id FROM guru WHERE id_guru = ?");
        $stmt_get->execute([$id_guru]);
        $guru = $stmt_get->fetch(PDO::FETCH_ASSOC);
        if ($guru && $guru['user_id']) {
            // Cek apakah user_id dipakai di tabel siswa
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE user_id = ?");
            $stmt_check->execute([$guru['user_id']]);
            $count = $stmt_check->fetchColumn();
            if ($count == 0) {
                // Hapus data dari tabel users jika tidak dipakai entitas lain
                $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt_user->execute([$guru['user_id']]);
            }
        }
        // Hapus data dari tabel guru
        $stmt = $conn->prepare("DELETE FROM guru WHERE id_guru = ?");
        $stmt->execute([$id_guru]);
        $conn->commit();
        header("Location: list_guru.php?status=delete_success");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        header("Location: list_guru.php?status=error");
        exit();
    }
}
exit;
?>