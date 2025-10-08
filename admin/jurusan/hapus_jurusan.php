<?php
session_start();
include '../../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Proses hapus data jurusan
if (isset($_GET['id'])) {
    $id_jurusan = $_GET['id'];

    try {
        // Cek apakah jurusan masih digunakan oleh kelas
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE id_jurusan = :id_jurusan");
        $stmt_check->bindParam(':id_jurusan', $id_jurusan);
        $stmt_check->execute();
        $kelas_count = $stmt_check->fetchColumn();

        if ($kelas_count > 0) {
            // Jika masih digunakan, redirect dengan pesan error
            header("Location: list_jurusan.php?status=error&message=Jurusan tidak bisa dihapus karena masih digunakan oleh $kelas_count kelas.");
            exit();
        }

        // Jika tidak digunakan, lanjutkan proses hapus
        $stmt = $conn->prepare("DELETE FROM jurusan WHERE id_jurusan = :id_jurusan");
        $stmt->bindParam(':id_jurusan', $id_jurusan);
        $stmt->execute();

        // Redirect ke halaman list jurusan dengan status success
        header("Location: list_jurusan.php?status=delete_success");
        exit();
    } catch (\PDOException $e) {
        // Redirect ke halaman list jurusan dengan status error
        header("Location: list_jurusan.php?status=error");
        exit();
    }
}
exit;
?>