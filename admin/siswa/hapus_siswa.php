<?php
session_start();
include '../../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Proses hapus data siswa
if (isset($_GET['id'])) {
    $id_siswa = $_GET['id'];

    try {
        $conn->beginTransaction();
        
        // Ambil user_id dari siswa
        $stmt_get = $conn->prepare("SELECT user_id FROM siswa WHERE id_siswa = ?");
        $stmt_get->execute([$id_siswa]);
        $siswa = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if ($siswa && $siswa['user_id']) {
            // Cek apakah user_id dipakai di tabel guru
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM guru WHERE user_id = ?");
            $stmt_check->execute([$siswa['user_id']]);
            $count = $stmt_check->fetchColumn();
            if ($count == 0) {
                // Hapus data dari tabel users jika tidak dipakai entitas lain
                $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt_user->execute([$siswa['user_id']]);
            }
        }
        
        // Hapus data dari tabel siswa
        $stmt = $conn->prepare("DELETE FROM siswa WHERE id_siswa = ?");
        $stmt->execute([$id_siswa]);

        $conn->commit();
        header("Location: list_siswa.php?status=delete_success");
        exit();
    } catch (\PDOException $e) {
        $conn->rollBack();
        header("Location: list_siswa.php?status=error");
        exit();
    }
}
?>