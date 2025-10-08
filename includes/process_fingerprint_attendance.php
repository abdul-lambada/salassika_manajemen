<?php
// Skrip ini dapat dijalankan secara manual atau sebagai cron job
// untuk memproses data mentah dari mesin fingerprint.

include __DIR__ . '/db.php';
include __DIR__ . '/attendance_whatsapp_automation.php';

function processFingerprintAttendance() {
    global $conn;
    echo "Memulai proses sinkronisasi absensi...\n";

    try {
        // 1. Ambil Pengaturan Jam Kerja
        $stmt_jam = $conn->query("SELECT jam_masuk, toleransi_telat_menit FROM tbl_jam_kerja WHERE id = 1");
        $jam_kerja = $stmt_jam->fetch(PDO::FETCH_ASSOC);

        if (!$jam_kerja) {
            return "Error: Pengaturan jam kerja tidak ditemukan.";
        }

        $jam_masuk_standar = new DateTime($jam_kerja['jam_masuk']);
        $toleransi_menit = (int)$jam_kerja['toleransi_telat_menit'];
        $batas_waktu_hadir = clone $jam_masuk_standar;
        $batas_waktu_hadir->add(new DateInterval("PT{$toleransi_menit}M"));

        // 2. Ambil Data Absensi Baru dari Fingerprint
        $stmt_kehadiran = $conn->prepare("
            SELECT kh.id, kh.user_id, kh.timestamp 
            FROM tbl_kehadiran kh
            WHERE kh.is_processed = 0
            ORDER BY kh.timestamp ASC
        ");
        $stmt_kehadiran->execute();
        $data_mentah = $stmt_kehadiran->fetchAll(PDO::FETCH_ASSOC);

        if (count($data_mentah) == 0) {
            return "Tidak ada data absensi baru untuk diproses.";
        }

        $processed_count = 0;
        
        // 3. Loop & Proses Setiap Data
        foreach ($data_mentah as $data) {
            $conn->beginTransaction();
            
            $user_id = $data['user_id'];
            $timestamp = new DateTime($data['timestamp']);
            $tanggal_absensi = $timestamp->format('Y-m-d');
            $waktu_absensi = $timestamp->format('H:i:s');

            // Cek apakah user adalah guru atau siswa
            $stmt_user = $conn->prepare("
                SELECT u.role, g.id_guru, s.id_siswa 
                FROM users u
                LEFT JOIN guru g ON u.id = g.user_id
                LEFT JOIN siswa s ON u.id = s.user_id
                WHERE u.id = ?
            ");
            $stmt_user->execute([$user_id]);
            $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

            if (!$user_info) {
                // Tandai sebagai diproses agar tidak diulang
                $stmt_mark = $conn->prepare("UPDATE tbl_kehadiran SET is_processed = 1 WHERE id = ?");
                $stmt_mark->execute([$data['id']]);
                $conn->commit();
                continue; // Skip jika user tidak ditemukan
            }

            // Tentukan status kehadiran
            $status_kehadiran = ($timestamp <= $batas_waktu_hadir) ? 'Hadir' : 'Telat';

            $tabel_absensi = '';
            $kolom_id = '';
            $id_entitas = null;

            if ($user_info['role'] === 'guru' && $user_info['id_guru']) {
                $tabel_absensi = 'absensi_guru';
                $kolom_id = 'id_guru';
                $id_entitas = $user_info['id_guru'];
            } elseif ($user_info['role'] === 'siswa' && $user_info['id_siswa']) {
                $tabel_absensi = 'absensi_siswa';
                $kolom_id = 'id_siswa';
                $id_entitas = $user_info['id_siswa'];
            }

            if ($tabel_absensi && $id_entitas) {
                // Cek apakah sudah ada entri absensi manual untuk hari ini
                $stmt_check = $conn->prepare("SELECT id_absensi_$tabel_absensi FROM $tabel_absensi WHERE $kolom_id = ? AND tanggal = ?");
                $stmt_check->execute([$id_entitas, $tanggal_absensi]);

                if ($stmt_check->fetch()) {
                    // Jika sudah ada, update jam masuknya (jika kosong)
                    $stmt_update = $conn->prepare("UPDATE $tabel_absensi SET jam_masuk = ? WHERE $kolom_id = ? AND tanggal = ? AND jam_masuk IS NULL");
                    $stmt_update->execute([$waktu_absensi, $id_entitas, $tanggal_absensi]);
                } else {
                    // Jika belum ada, buat entri baru
                    $stmt_insert = $conn->prepare(
                        "INSERT INTO $tabel_absensi ($kolom_id, tanggal, jam_masuk, status_kehadiran, catatan) 
                         VALUES (?, ?, ?, ?, 'Absensi via Fingerprint')"
                    );
                    $stmt_insert->execute([$id_entitas, $tanggal_absensi, $waktu_absensi, $status_kehadiran]);

                    // Update status kehadiran di tabel kehadiran
                    $stmt_update = $conn->prepare("UPDATE tbl_kehadiran SET status_kehadiran = ?, timestamp = ? WHERE id = ? AND DATE(timestamp) = DATE(?)");
                    $stmt_update->execute([$status_kehadiran, $timestamp, $data['id'], $timestamp]);

                    // Kirim notifikasi WhatsApp otomatis jika ada pengaturan
                    try {
                        $automation = new AttendanceWhatsAppAutomation($conn);
                        $automation->processAttendanceNotifications([
                            'user_id' => $user_id,
                            'status_kehadiran' => $status_kehadiran,
                            'timestamp' => $timestamp,
                            'user_type' => $user_info['role']
                        ]);
                    } catch (Exception $e) {
                        error_log("WhatsApp automation error: " . $e->getMessage());
                    }
                }
            }

            // 4. Tandai data sebagai sudah diproses
            $stmt_mark = $conn->prepare("UPDATE tbl_kehadiran SET is_processed = 1 WHERE id = ?");
            $stmt_mark->execute([$data['id']]);
            
            $conn->commit();
            $processed_count++;
        }

        return "Proses selesai. Berhasil memproses {$processed_count} data absensi.";

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return "Error: " . $e->getMessage();
    }
}

// Jika file ini dijalankan dari command line, panggil fungsinya
if (php_sapi_name() == 'cli') {
    // Jalankan migrasi kolom 'is_processed' jika belum ada
    try {
        $conn->query("SELECT is_processed FROM tbl_kehadiran LIMIT 1");
    } catch (PDOException $e) {
        if ($e->getCode() == '42S22') { // Kolom tidak ditemukan
            echo "Menambahkan kolom 'is_processed' ke tabel kehadiran...\n";
            $sql = file_get_contents(__DIR__ . '/../migrations/002_add_is_processed_to_kehadiran.sql');
            $conn->exec($sql);
            echo "Kolom berhasil ditambahkan.\n";
        }
    }
    
    echo processFingerprintAttendance() . "\n";
}
?> 