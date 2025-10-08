<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendAbsensiNotification($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Ganti sesuai SMTP Anda
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com'; // Ganti email pengirim
        $mail->Password = 'your_app_password'; // Ganti password aplikasi/email
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('your_email@gmail.com', 'Absensi Sekolah');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email gagal: ' . $mail->ErrorInfo);
        return false;
    }
}

function sendDeviceOfflineNotification($device, $error) {
    $admin_email = 'admin@yourdomain.com'; // Ganti dengan email admin
    $subject = "Peringatan: Device Fingerprint Offline";
    $body = "<p>Device fingerprint <b>{$device['nama_lokasi']}</b> (IP: {$device['ip']}:{$device['port']}) <b>OFFLINE</b> pada ".date('d-m-Y H:i').".<br>Error: $error</p>";
    return sendAbsensiNotification($admin_email, $subject, $body);
} 