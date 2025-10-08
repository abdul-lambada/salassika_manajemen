<?php
try {
    $host = 'localhost';
    $dbname = 'absensi_sekolah'; // Ganti dengan nama database Anda
    $username = 'root';         // Ganti dengan username database Anda
    $password = '';             // Ganti dengan password database Anda

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Database connection successful."; // Debugging untuk memastikan koneksi berhasil
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>