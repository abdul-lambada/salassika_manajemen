<?php
session_start();
include '../../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Proses hapus data kelas
if (isset($_GET['id'])) {
    $id_kelas = $_GET['id'];

    try {
        // Cek apakah kelas masih digunakan oleh siswa
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE id_kelas = :id_kelas");
        $stmt_check->bindParam(':id_kelas', $id_kelas);
        $stmt_check->execute();
        $siswa_count = $stmt_check->fetchColumn();

        if ($siswa_count > 0) {
            // Jika masih digunakan, redirect dengan pesan error
            header("Location: list_kelas.php?status=error&message=Kelas tidak bisa dihapus karena masih digunakan oleh $siswa_count siswa.");
            exit();
        }

        // Jika tidak digunakan, lanjutkan proses hapus
        $stmt = $conn->prepare("DELETE FROM kelas WHERE id_kelas = :id_kelas");
        $stmt->bindParam(':id_kelas', $id_kelas);
        $stmt->execute();

        // Redirect ke halaman list kelas dengan status success
        header("Location: list_kelas.php?status=delete_success");
        exit();
    } catch (\PDOException $e) {
        // Redirect ke halaman list kelas dengan status error
        header("Location: list_kelas.php?status=error");
        exit();
    }
}
exit;
?>