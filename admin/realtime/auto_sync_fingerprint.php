<?php
// Set zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Path ke file konfigurasi dan library
$base_path = dirname(__DIR__, 2); // Kembali dua level dari admin/realtime/ ke root
include $base_path . '/includes/db.php';
include $base_path . '/includes/fingerprint_config.php';
require_once $base_path . '/includes/zklib/zklibrary.php';
include_once __DIR__ . '/../../includes/wa_util.php';

// Path untuk file log cron
$log_file = $base_path . '/logs/cron_sync.log';

/**
 * Fungsi untuk menulis log ke file
 * @param string $message Pesan log
 * @param string $status  Status [INFO|SUCCESS|ERROR]
 */
function write_log($message, $status = 'INFO') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$status] - $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

write_log('Memulai proses sinkronisasi fingerprint...');

// Ambil IP dari konfigurasi
$ip_address = FINGERPRINT_IP;

if (empty($ip_address)) {
    write_log('IP address mesin fingerprint tidak diatur di fingerprint_config.php.', 'ERROR');
    exit('IP Address tidak dikonfigurasi.');
}

try {
    // Inisialisasi koneksi ke mesin
    $zk = new ZKLibrary($ip_address, FINGERPRINT_PORT);
    $zk->connect();
    $zk->disableDevice();

    write_log("Berhasil terhubung ke mesin di IP: $ip_address");

    // Ambil data log kehadiran dari mesin
    $log_kehadiran_mesin = $zk->getAttendance();

    if (empty($log_kehadiran_mesin)) {
        write_log('Tidak ada data absensi baru di mesin.');
    } else {
        write_log('Ditemukan ' . count($log_kehadiran_mesin) . ' data absensi baru. Memproses...');
        
        // Simpan data ke database
        $new_records = 0;
        foreach ($log_kehadiran_mesin as $row) {
            $user_id = $row[1];
            $timestamp = date('Y-m-d H:i:s', strtotime($row[3]));

            // Cek apakah data sudah ada di database untuk menghindari duplikat
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM tbl_kehadiran WHERE user_id = ? AND timestamp = ?");
            $stmt_check->execute([$user_id, $timestamp]);
            $exists = $stmt_check->fetchColumn();

            if (!$exists) {
                $status = isset($ATTENDANCE_STATUS[$row[2]]) ? $ATTENDANCE_STATUS[$row[2]] : 'Unknown';
                $verification_mode = isset($VERIFICATION_MODES[$row[4]]) ? $VERIFICATION_MODES[$row[4]] : 'Unknown';
                
                // Ambil nama dari tabel users
                $stmt_user = $conn->prepare("SELECT name FROM users WHERE id = ?");
                $stmt_user->execute([$user_id]);
                $user_name = $stmt_user->fetchColumn();

                $stmt_insert = $conn->prepare("
                    INSERT INTO tbl_kehadiran (user_id, user_name, timestamp, verification_mode, status)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_insert->execute([$user_id, $user_name, $timestamp, $verification_mode, $status]);
                $new_records++;
            }
        }
        write_log("Berhasil menyimpan $new_records data absensi baru ke database.", 'SUCCESS');
    }

    // Aktifkan kembali mesin dan putuskan koneksi
    $zk->enableDevice();
    $zk->disconnect();

    write_log('Proses sinkronisasi selesai.');

} catch (Exception $e) {
    write_log('Gagal terhubung atau memproses data: ' . $e->getMessage(), 'ERROR');
}

// Setelah sinkronisasi fingerprint selesai, lakukan deteksi telat/tidak absen fingerprint 3 hari berturut-turut
$today = date('Y-m-d');
for ($i = 0; $i < 3; $i++) {
    $dates[] = date('Y-m-d', strtotime("-$i days", strtotime($today)));
}
// Deteksi guru
$stmt = $conn->prepare("
    SELECT g.id_guru, g.nama_guru, u.uid, u.name, u.phone
    FROM guru g
    JOIN users u ON g.user_id = u.id
    WHERE u.uid IS NOT NULL
");
$stmt->execute();
$guru_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($guru_list as $guru) {
    $uid = $guru['uid'];
    $telat = 0; $alfa = 0;
    foreach ($dates as $d) {
        $stmt_fp = $conn->prepare("SELECT timestamp FROM tbl_kehadiran WHERE user_id = ? AND DATE(timestamp) = ?");
        $stmt_fp->execute([$guru['user_id'], $d]);
        $fp = $stmt_fp->fetch(PDO::FETCH_ASSOC);
        if (!$fp) { $alfa++; }
        else {
            $jam_masuk = '07:00:00'; $toleransi = 15*60;
            $waktu_fp = strtotime($fp['timestamp']);
            $waktu_masuk = strtotime($d.' '.$jam_masuk);
            if ($waktu_fp > $waktu_masuk + $toleransi) { $telat++; }
        }
    }
    if ($telat == 3 || $alfa == 3) {
        $no_wa = $guru['phone'];
        if ($no_wa) {
            $pesan = "Yth. {$guru['name']}, Anda tercatat TELAT atau TIDAK ABSEN fingerprint selama 3 hari berturut-turut. Mohon periksa kehadiran Anda.";
            sendWaNotification($no_wa, $pesan);
        }
    }
}
// Deteksi siswa
$stmt = $conn->prepare("
    SELECT s.id_siswa, s.nama_siswa, u.uid, u.name, u.phone
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    WHERE u.uid IS NOT NULL
");
$stmt->execute();
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($siswa_list as $siswa) {
    $uid = $siswa['uid'];
    $telat = 0; $alfa = 0;
    foreach ($dates as $d) {
        $stmt_fp = $conn->prepare("SELECT timestamp FROM tbl_kehadiran WHERE user_id = ? AND DATE(timestamp) = ?");
        $stmt_fp->execute([$siswa['user_id'], $d]);
        $fp = $stmt_fp->fetch(PDO::FETCH_ASSOC);
        if (!$fp) { $alfa++; }
        else {
            $jam_masuk = '07:00:00'; $toleransi = 15*60;
            $waktu_fp = strtotime($fp['timestamp']);
            $waktu_masuk = strtotime($d.' '.$jam_masuk);
            if ($waktu_fp > $waktu_masuk + $toleransi) { $telat++; }
        }
    }
    if ($telat == 3 || $alfa == 3) {
        $no_wa = $siswa['phone'];
        if ($no_wa) {
            $pesan = "Yth. {$siswa['name']}, Anda tercatat TELAT atau TIDAK ABSEN fingerprint selama 3 hari berturut-turut. Mohon periksa kehadiran Anda.";
            sendWaNotification($no_wa, $pesan);
        }
    }
}

exit('Proses selesai.'); 