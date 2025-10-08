<?php
session_start();
include '../../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Proses hapus data user
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $conn->beginTransaction();
        
        // Cek apakah user terkait dengan guru atau siswa
        $stmt_check_guru = $conn->prepare("SELECT id_guru FROM guru WHERE user_id = ?");
        $stmt_check_guru->execute([$id]);
        $guru = $stmt_check_guru->fetch(PDO::FETCH_ASSOC);
        
        $stmt_check_siswa = $conn->prepare("SELECT id_siswa FROM siswa WHERE user_id = ?");
        $stmt_check_siswa->execute([$id]);
        $siswa = $stmt_check_siswa->fetch(PDO::FETCH_ASSOC);
        
        if ($guru || $siswa) {
            throw new Exception("User tidak dapat dihapus karena masih terkait dengan data guru atau siswa");
        }
        
        // Hapus data dari tabel users
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $conn->commit();
        // Redirect ke halaman list users dengan status success
        header("Location: list_users.php?status=delete_success");
        exit();
    } catch (\PDOException $e) {
        $conn->rollBack();
        // Redirect ke halaman list users dengan status error
        header("Location: list_users.php?status=error");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        // Redirect ke halaman list users dengan status error
        header("Location: list_users.php?status=error");
        exit();
    }
}
?>