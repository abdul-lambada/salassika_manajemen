-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 08 Okt 2025 pada 16.35
-- Versi Server: 10.1.21-MariaDB
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_sekolah`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`dpgwgcvf_salassika`@`localhost` PROCEDURE `sp_clean_rate_limits` ()  BEGIN
    DELETE FROM whatsapp_rate_limits 
    WHERE window_end < NOW();
END$$

CREATE DEFINER=`dpgwgcvf_salassika`@`localhost` PROCEDURE `sp_send_whatsapp_message` (IN `p_phone_number` VARCHAR(20), IN `p_message` TEXT, IN `p_message_type` ENUM('text','template','image','document','video','audio','button','list'), IN `p_template_name` VARCHAR(100))  BEGIN
    DECLARE v_log_id INT;
    DECLARE v_config_id INT;
    
        SELECT id INTO v_config_id FROM whatsapp_config LIMIT 1;
    
        INSERT INTO whatsapp_logs (phone_number, message, message_type, template_name, status)
    VALUES (p_phone_number, p_message, p_message_type, p_template_name, 'pending');
    
    SET v_log_id = LAST_INSERT_ID();
    
        SELECT v_log_id as log_id;
END$$

CREATE DEFINER=`dpgwgcvf_salassika`@`localhost` PROCEDURE `sp_update_message_status` (IN `p_log_id` INT, IN `p_status` VARCHAR(20), IN `p_message_id` VARCHAR(100), IN `p_response` TEXT)  BEGIN
    UPDATE whatsapp_logs 
    SET 
        status = p_status,
        message_id = COALESCE(p_message_id, message_id),
        response = COALESCE(p_response, response),
        sent_at = CASE WHEN p_status = 'sent' THEN NOW() ELSE sent_at END,
        delivered_at = CASE WHEN p_status = 'delivered' THEN NOW() ELSE delivered_at END,
        read_at = CASE WHEN p_status = 'read' THEN NOW() ELSE read_at END
    WHERE id = p_log_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi_guru`
--

CREATE TABLE `absensi_guru` (
  `id_absensi_guru` int(11) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status_kehadiran` enum('Hadir','Telat','Izin','Sakit','Alfa') NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `catatan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi_siswa`
--

CREATE TABLE `absensi_siswa` (
  `id_absensi_siswa` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status_kehadiran` enum('Hadir','Telat','Sakit','Izin','Tidak Hadir') NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `catatan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `backup_logs`
--

CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `type` enum('full','incremental') NOT NULL,
  `size_bytes` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `backup_logs`
--

INSERT INTO `backup_logs` (`id`, `filename`, `type`, `size_bytes`, `created_at`) VALUES
(1, 'backup_full_2025-08-07_08-06-30.sql.gz', 'full', 6101, '2025-08-07 06:06:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache_metadata`
--

CREATE TABLE `cache_metadata` (
  `cache_key` varchar(191) NOT NULL,
  `tags` text,
  `size` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `fingerprint_devices`
--

CREATE TABLE `fingerprint_devices` (
  `id` int(11) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `port` int(11) DEFAULT '4370',
  `nama_lokasi` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data untuk tabel `fingerprint_devices`
--

INSERT INTO `fingerprint_devices` (`id`, `ip`, `port`, `nama_lokasi`, `keterangan`, `is_active`, `created_at`, `updated_at`) VALUES
(2, '192.168.1.201', 4370, 'Lobby 1', 'Fingerprint Guru', 1, '2025-07-24 10:30:49', '2025-07-24 10:30:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `fingerprint_logs`
--

CREATE TABLE `fingerprint_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('success','error','warning') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `guru`
--

CREATE TABLE `guru` (
  `id_guru` int(11) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `guru`
--

INSERT INTO `guru` (`id_guru`, `nama_guru`, `nip`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `phone`, `user_id`) VALUES
(8, 'Budi Santoso', '12345678901', 'Laki-laki', '1980-05-15', 'Jl. Merdeka No. 10, Jakarta', NULL, 36);

-- --------------------------------------------------------

--
-- Struktur dari tabel `jurusan`
--

CREATE TABLE `jurusan` (
  `id_jurusan` int(11) NOT NULL,
  `nama_jurusan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `jurusan`
--

INSERT INTO `jurusan` (`id_jurusan`, `nama_jurusan`) VALUES
(1, 'Teknik Komputer dan Jaringan'),
(2, 'Teknik Kendaraan Ringan dan Otomotif'),
(3, 'Akuntansi Keuangan dan Lembaga');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `id_jurusan` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `nama_kelas`, `id_jurusan`) VALUES
(1, 'XI - TKJ 2', 1),
(3, 'XI - TKJ 1', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_absensi`
--

CREATE TABLE `laporan_absensi` (
  `id_laporan` int(11) NOT NULL,
  `id_absensi_guru` int(11) DEFAULT NULL,
  `id_absensi_siswa` int(11) DEFAULT NULL,
  `periode` enum('Harian','Mingguan','Bulanan') NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_akhir` date NOT NULL,
  `jumlah_hadir` int(11) NOT NULL,
  `jumlah_tidak_hadir` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text,
  `status` enum('success','warning','error') DEFAULT 'success',
  `execution_time` decimal(10,3) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id_pengaduan` int(11) NOT NULL,
  `nama_pelapor` varchar(255) NOT NULL,
  `no_wa` varchar(15) DEFAULT NULL,
  `email_pelapor` varchar(255) DEFAULT NULL,
  `role_pelapor` enum('siswa','guru','umum') NOT NULL,
  `kategori` enum('saran','kritik','pembelajaran','organisasi','administrasi','lainnya') NOT NULL,
  `judul_pengaduan` varchar(255) NOT NULL,
  `isi_pengaduan` text NOT NULL,
  `keterangan` text,
  `file_pendukung` varchar(255) DEFAULT NULL,
  `status` enum('pending','diproses','selesai') DEFAULT 'pending',
  `tanggal_pengaduan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `performance_metrics`
--

CREATE TABLE `performance_metrics` (
  `id` int(11) NOT NULL,
  `metrics_data` longtext,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `data` mediumtext,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `alamat` text NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nisn`, `nama_siswa`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `id_kelas`, `nis`, `phone`, `user_id`) VALUES
(2, '3333', 'RICKY', 'Laki-laki', '1990-06-06', 'Majalengka', 3, '1111', NULL, 37);

-- --------------------------------------------------------

--
-- Struktur dari tabel `system_stats`
--

CREATE TABLE `system_stats` (
  `id` int(11) NOT NULL,
  `stat_key` varchar(100) NOT NULL,
  `stat_value` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `system_stats`
--

INSERT INTO `system_stats` (`id`, `stat_key`, `stat_value`, `updated_at`) VALUES
(1, 'system_version', '1.0.0', '2025-08-07 07:52:00'),
(2, 'last_maintenance', '2025-08-07 14:52:00', '2025-08-07 07:52:00'),
(3, 'total_users', '0', '2025-08-07 07:52:00'),
(4, 'attendance_today', '0', '2025-08-07 07:52:00'),
(5, 'attendance_month', '0', '2025-08-07 07:52:00'),
(6, 'attendance_rate', '0', '2025-08-07 07:52:00'),
(7, 'whatsapp_sent_today', '0', '2025-08-07 07:52:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tbl_jam_kerja`
--

CREATE TABLE `tbl_jam_kerja` (
  `id` int(11) NOT NULL,
  `nama_jam_kerja` varchar(100) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `toleransi_telat_menit` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `tbl_jam_kerja`
--

INSERT INTO `tbl_jam_kerja` (`id`, `nama_jam_kerja`, `jam_masuk`, `jam_pulang`, `toleransi_telat_menit`, `created_at`, `updated_at`) VALUES
(1, '', '06:30:00', '15:00:00', 5, '2025-07-28 14:18:04', '2025-07-28 14:19:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tbl_kehadiran`
--

CREATE TABLE `tbl_kehadiran` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `verification_mode` varchar(50) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Belum diproses, 1: Sudah diproses',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `tbl_kehadiran`
--

INSERT INTO `tbl_kehadiran` (`id`, `user_id`, `user_name`, `timestamp`, `verification_mode`, `status`, `is_processed`, `created_at`) VALUES
(8, 1, 'ABDULKHOLIK', '2025-07-22 18:13:48', 'Unknown', 'Masuk', 1, '2025-07-24 07:35:48'),
(9, 1, 'ABDULKHOLIK', '2025-07-22 21:50:30', 'Unknown', 'Masuk', 1, '2025-07-24 07:35:48'),
(10, 1, 'ABDULKHOLIK', '2025-07-22 21:53:35', 'Unknown', 'Masuk', 1, '2025-07-24 07:35:48'),
(11, 1, 'ABDULKHOLIK', '2025-07-22 22:02:29', 'Unknown', 'Masuk', 1, '2025-07-24 07:35:48'),
(12, 1, 'ABDULKHOLIK', '2025-07-22 22:44:59', 'Unknown', 'Masuk', 1, '2025-07-24 07:35:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guru','siswa') NOT NULL,
  `uid` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `avatar`, `password`, `role`, `uid`, `created_at`) VALUES
(1, 'admin', NULL, 'uploads/avatar/avatar_1_1753099882.jpg', '$2y$10$MLZxHgbKIYYexDd6Z7NETOiQmqUO9SD1Nd.Tx1PgslwkwSTRoeB86', 'admin', 'null', '2025-03-05 09:07:00'),
(36, 'ABDULKHOLIK', NULL, 'uploads/avatar/avatar_36_1753708721.jpg', '$2y$10$RPKfbEiTV1zp784ZMds99.d7MMW5FVoyvn4C2C1GCgh64dTBncnNW', 'guru', '1', '2025-07-24 06:42:19'),
(37, 'RICKY', NULL, NULL, '$2y$10$DOHJ6fYvzpHTfvRSw9/GoOlcgyHvz1RNoSPQIsRQ80Q.aNuUrBmJS', '', '2', '2025-07-24 06:59:04'),
(41, 'Budi Santoso', NULL, NULL, '$2y$10$TuCi9vVAxxR2gN1yfYceweV3Ce6/ZBRnvJ1H2nJch0T7lXR3HobSS', 'guru', NULL, '2025-07-28 15:06:36');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_active_templates`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `vw_active_templates` (
`id` int(11)
,`name` varchar(100)
,`display_name` varchar(255)
,`category` enum('AUTHENTICATION','MARKETING','UTILITY')
,`language` varchar(10)
,`body` text
,`variables` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_recent_whatsapp_logs`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `vw_recent_whatsapp_logs` (
`id` int(11)
,`phone_number` varchar(20)
,`message` text
,`message_type` enum('text','template','image','document','button','list')
,`status` enum('pending','success','failed')
,`sent_at` datetime
,`created_at` timestamp
,`status_color` varchar(9)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_whatsapp_stats`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `vw_whatsapp_stats` (
`date` date
,`message_type` enum('text','template','image','document','button','list')
,`status` enum('pending','success','failed')
,`total_messages` bigint(21)
,`sent_count` bigint(21)
,`failed_count` bigint(21)
,`pending_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_automation_config`
--

CREATE TABLE `whatsapp_automation_config` (
  `id` int(11) NOT NULL,
  `notify_late_arrival` tinyint(1) NOT NULL DEFAULT '1',
  `notify_absence` tinyint(1) NOT NULL DEFAULT '1',
  `notify_parents` tinyint(1) NOT NULL DEFAULT '1',
  `notify_admin` tinyint(1) NOT NULL DEFAULT '1',
  `late_threshold_minutes` int(11) NOT NULL DEFAULT '15',
  `absence_check_time` time NOT NULL DEFAULT '09:00:00',
  `daily_summary_time` time NOT NULL DEFAULT '16:00:00',
  `weekend_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `whatsapp_automation_config`
--

INSERT INTO `whatsapp_automation_config` (`id`, `notify_late_arrival`, `notify_absence`, `notify_parents`, `notify_admin`, `late_threshold_minutes`, `absence_check_time`, `daily_summary_time`, `weekend_notifications`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 5, '06:30:00', '15:00:00', 0, 1, '2025-08-06 11:40:37', '2025-08-06 12:18:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_automation_logs`
--

CREATE TABLE `whatsapp_automation_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('guru','siswa') NOT NULL,
  `attendance_status` varchar(20) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `recipient_type` enum('user','parent','admin') NOT NULL,
  `template_used` varchar(100) DEFAULT NULL,
  `message_sent` tinyint(1) NOT NULL DEFAULT '0',
  `whatsapp_log_id` int(11) DEFAULT NULL,
  `error_message` text,
  `attendance_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_config`
--

CREATE TABLE `whatsapp_config` (
  `id` int(11) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `api_url` varchar(255) NOT NULL DEFAULT 'https://api.fonnte.com/send',
  `country_code` varchar(5) NOT NULL DEFAULT '62',
  `device_id` varchar(50) DEFAULT NULL,
  `delay` int(11) NOT NULL DEFAULT '2' COMMENT 'Delay between messages in seconds',
  `retry` int(11) NOT NULL DEFAULT '0' COMMENT 'Number of retries for failed messages',
  `callback_url` varchar(255) DEFAULT NULL,
  `template_language` varchar(10) NOT NULL DEFAULT 'id',
  `webhook_secret` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `whatsapp_config`
--

INSERT INTO `whatsapp_config` (`id`, `api_key`, `api_url`, `country_code`, `device_id`, `delay`, `retry`, `callback_url`, `template_language`, `webhook_secret`, `updated_at`) VALUES
(1, 'r6QxiHzS8d7zvxbE1bnA', 'https://api.fonnte.com', '62', '6285156553226', 2, 4, '', 'id', '', '2025-08-06 05:45:03');

--
-- Trigger `whatsapp_config`
--
DELIMITER $$
CREATE TRIGGER `tr_whatsapp_config_update` BEFORE UPDATE ON `whatsapp_config` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_device_status`
--

CREATE TABLE `whatsapp_device_status` (
  `id` int(11) NOT NULL,
  `device_id` varchar(50) NOT NULL,
  `status` enum('online','offline','connecting','error') NOT NULL DEFAULT 'offline',
  `last_seen` datetime DEFAULT NULL,
  `battery_level` int(3) DEFAULT NULL,
  `signal_strength` int(3) DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message_id` varchar(100) DEFAULT NULL,
  `message_type` enum('text','template','image','document','button','list') NOT NULL DEFAULT 'text',
  `template_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `status_detail` varchar(50) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT '0',
  `response` longtext,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `whatsapp_logs`
--

INSERT INTO `whatsapp_logs` (`id`, `phone_number`, `message_id`, `message_type`, `template_name`, `message`, `status`, `status_detail`, `sent_at`, `delivered_at`, `read_at`, `retry_count`, `response`, `created_at`) VALUES
(1, '6285156553226', '[\"112744390\"]', 'text', NULL, 'Ini adalah pesan test dari Sistem Absensi Sekolah.', '', NULL, '2025-08-06 13:27:29', NULL, NULL, 0, '{\"detail\":\"success! message in queue\",\"id\":[\"112744390\"],\"process\":\"pending\",\"quota\":{\"6285156553226\":{\"details\":\"deduced from total quota\",\"quota\":994,\"remaining\":993,\"used\":1}},\"requestid\":51786000,\"status\":true,\"target\":[\"6285156553226\"]}', '2025-08-06 11:27:28'),
(2, '6283807099585', '[\"112877289\"]', 'text', NULL, 'Ini adalah pesan test dari Sistem Absensi Sekolah.', '', NULL, '2025-08-07 04:02:12', NULL, NULL, 0, '{\"detail\":\"success! message in queue\",\"id\":[\"112877289\"],\"process\":\"pending\",\"quota\":{\"6285156553226\":{\"details\":\"deduced from total quota\",\"quota\":993,\"remaining\":992,\"used\":1}},\"requestid\":52273804,\"status\":true,\"target\":[\"6283807099585\"]}', '2025-08-07 02:02:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_message_templates`
--

CREATE TABLE `whatsapp_message_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `category` enum('AUTHENTICATION','MARKETING','UTILITY') NOT NULL DEFAULT 'UTILITY',
  `language` varchar(10) NOT NULL DEFAULT 'id',
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `template_id` varchar(100) DEFAULT NULL,
  `header` text,
  `body` text NOT NULL,
  `footer` text,
  `variables` text COMMENT 'JSON array of variable names',
  `buttons` text COMMENT 'JSON buttons data',
  `components` text COMMENT 'JSON components data',
  `example` text COMMENT 'JSON example data',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `whatsapp_message_templates`
--

INSERT INTO `whatsapp_message_templates` (`id`, `name`, `display_name`, `category`, `language`, `status`, `template_id`, `header`, `body`, `footer`, `variables`, `buttons`, `components`, `example`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'absensi_berhasil', 'Absensi Berhasil', 'UTILITY', 'id', 'APPROVED', NULL, NULL, 'Halo {{nama}}, absensi Anda pada {{tanggal}} pukul {{waktu}} telah berhasil dicatat dengan status {{status}}. Terima kasih!', NULL, '[\"nama\", \"tanggal\", \"waktu\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 04:05:17', '2025-08-06 04:05:17'),
(2, 'absensi_telat', 'Absensi Telat', 'UTILITY', 'id', 'APPROVED', NULL, NULL, 'Halo {{nama}}, absensi Anda pada {{tanggal}} pukul {{waktu}} tercatat sebagai telat. {{keterangan}}', NULL, '[\"nama\", \"tanggal\", \"waktu\", \"keterangan\"]', NULL, NULL, NULL, 1, '2025-08-06 04:05:17', '2025-08-06 04:05:17'),
(3, 'notifikasi_sistem', 'Notifikasi Sistem', 'UTILITY', 'id', 'APPROVED', NULL, NULL, '{{pesan}}\n\nWaktu: {{waktu}}', NULL, '[\"pesan\", \"waktu\"]', NULL, NULL, NULL, 1, '2025-08-06 04:05:17', '2025-08-06 04:05:17'),
(4, 'pemberitahuan_keterlambatan', 'Pemberitahuan Keterlambatan', 'UTILITY', 'id', 'APPROVED', NULL, NULL, 'Yth. Orang Tua/Wali dari {{nama}},\n\nDiberitahukan bahwa putra/putri Bapak/Ibu terlambat masuk sekolah pada tanggal {{tanggal}} pukul {{waktu}}.\n\nMohon bimbingan dan pengawasan dari Bapak/Ibu.\n\nTerima kasih.', NULL, '[\"nama\", \"tanggal\", \"waktu\"]', NULL, NULL, NULL, 1, '2025-08-06 04:05:17', '2025-08-06 04:05:17'),
(5, 'pemberitahuan_ketidakhadiran', 'Pemberitahuan Ketidakhadiran', 'UTILITY', 'id', 'APPROVED', NULL, NULL, 'Yth. Orang Tua/Wali dari {{nama}},\n\nDiberitahukan bahwa putra/putri Bapak/Ibu tidak hadir di sekolah pada tanggal {{tanggal}} dengan status {{status}}.\n\nMohon konfirmasi ketidakhadiran putra/putri Bapak/Ibu.\n\nTerima kasih.', NULL, '[\"nama\", \"tanggal\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 04:05:17', '2025-08-06 04:05:17'),
(6, 'attendance_confirmation', 'Konfirmasi Kehadiran', '', 'id', 'PENDING', NULL, NULL, '✅ *KONFIRMASI KEHADIRAN*\n\nHalo {{nama}},\n\nKehadiran Anda telah tercatat:\n📅 Tanggal: {{tanggal}}\n⏰ Waktu: {{waktu}}\n📍 Status: {{status}}\n\nTerima kasih telah hadir tepat waktu! 🙏', NULL, '[\"nama\", \"tanggal\", \"waktu\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(7, 'attendance_late', 'Notifikasi Terlambat', '', 'id', 'PENDING', NULL, NULL, '⚠️ *NOTIFIKASI KETERLAMBATAN*\n\nHalo {{nama}},\n\nAnda tercatat terlambat:\n📅 Tanggal: {{tanggal}}\n⏰ Waktu kedatangan: {{waktu}}\n⏱️ Terlambat: {{menit_terlambat}} menit\n\nHarap lebih memperhatikan waktu kedatangan. Terima kasih! 🙏', NULL, '[\"nama\", \"tanggal\", \"waktu\", \"menit_terlambat\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(8, 'attendance_permission_confirm', 'Konfirmasi Izin', '', 'id', 'PENDING', NULL, NULL, 'ℹ️ *KONFIRMASI IZIN*\n\nHalo {{nama}},\n\nIzin Anda telah tercatat:\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nSemoga cepat pulih dan dapat kembali beraktivitas normal. 🙏', NULL, '[\"nama\", \"tanggal\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(9, 'attendance_sick_confirm', 'Konfirmasi Sakit', '', 'id', 'PENDING', NULL, NULL, '🏥 *KONFIRMASI SAKIT*\n\nHalo {{nama}},\n\nStatus sakit Anda telah tercatat:\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nSemoga lekas sembuh dan dapat kembali beraktivitas. Get well soon! 🙏', NULL, '[\"nama\", \"tanggal\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(10, 'attendance_present_parent', 'Notifikasi Orang Tua - Hadir', '', 'id', 'PENDING', NULL, NULL, '✅ *NOTIFIKASI KEHADIRAN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n⏰ Waktu: {{waktu}}\n📍 Status: {{status}}\n\nAnak Anda telah hadir di sekolah. Terima kasih! 🙏', NULL, '[\"nama_anak\", \"kelas\", \"tanggal\", \"waktu\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(11, 'attendance_late_parent', 'Notifikasi Orang Tua - Terlambat', '', 'id', 'PENDING', NULL, NULL, '⚠️ *NOTIFIKASI KETERLAMBATAN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n⏰ Waktu kedatangan: {{waktu}}\n⏱️ Terlambat: {{menit_terlambat}} menit\n\nMohon perhatian untuk kedisiplinan waktu anak. Terima kasih! 🙏', NULL, '[\"nama_anak\", \"kelas\", \"tanggal\", \"waktu\", \"menit_terlambat\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(12, 'attendance_absent_parent', 'Notifikasi Orang Tua - Tidak Hadir', '', 'id', 'PENDING', NULL, NULL, '❌ *NOTIFIKASI KETIDAKHADIRAN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nAnak Anda belum hadir di sekolah hari ini. Jika berhalangan, mohon konfirmasi ke sekolah. Terima kasih! 🙏', NULL, '[\"nama_anak\", \"kelas\", \"tanggal\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(13, 'attendance_permission_parent', 'Notifikasi Orang Tua - Izin', '', 'id', 'PENDING', NULL, NULL, 'ℹ️ *NOTIFIKASI IZIN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nIzin anak Anda telah tercatat. Terima kasih atas informasinya! 🙏', NULL, '[\"nama_anak\", \"kelas\", \"tanggal\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(14, 'attendance_sick_parent', 'Notifikasi Orang Tua - Sakit', '', 'id', 'PENDING', NULL, NULL, '🏥 *NOTIFIKASI SAKIT ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nSemoga anak Anda lekas sembuh dan dapat kembali bersekolah. Get well soon! 🙏', NULL, '[\"nama_anak\", \"kelas\", \"tanggal\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(15, 'late_alert', 'Alert Admin - Keterlambatan', '', 'id', 'PENDING', NULL, NULL, '⚠️ *ALERT KETERLAMBATAN*\n\n👤 Nama: {{nama}}\n👥 Tipe: {{user_type}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n⏰ Waktu: {{waktu}}\n⏱️ Terlambat: {{menit_terlambat}} menit\n\nPerlu perhatian khusus untuk kedisiplinan.', NULL, '[\"nama\", \"user_type\", \"kelas\", \"tanggal\", \"waktu\", \"menit_terlambat\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(16, 'absence_alert', 'Alert Admin - Ketidakhadiran', '', 'id', 'PENDING', NULL, NULL, '❌ *ALERT KETIDAKHADIRAN*\n\n👤 Nama: {{nama}}\n👥 Tipe: {{user_type}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nPerlu tindak lanjut untuk ketidakhadiran ini.', NULL, '[\"nama\", \"user_type\", \"kelas\", \"tanggal\", \"status\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39'),
(17, 'daily_attendance_summary', 'Ringkasan Harian Admin', '', 'id', 'PENDING', NULL, NULL, '📊 *RINGKASAN KEHADIRAN HARIAN*\n\n📅 Tanggal: {{tanggal}}\n\n✅ Hadir: {{hadir}} orang\n⚠️ Terlambat: {{terlambat}} orang\n❌ Tidak Hadir: {{tidak_hadir}} orang\nℹ️ Izin: {{izin}} orang\n🏥 Sakit: {{sakit}} orang\n\n📈 Total: {{total}} orang\n\nLaporan otomatis sistem absensi sekolah.', NULL, '[\"tanggal\", \"hadir\", \"terlambat\", \"tidak_hadir\", \"izin\", \"sakit\", \"total\"]', NULL, NULL, NULL, 1, '2025-08-06 11:40:39', '2025-08-06 11:40:39');

--
-- Trigger `whatsapp_message_templates`
--
DELIMITER $$
CREATE TRIGGER `tr_whatsapp_message_templates_update` BEFORE UPDATE ON `whatsapp_message_templates` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_rate_limits`
--

CREATE TABLE `whatsapp_rate_limits` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message_type` varchar(50) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '1',
  `window_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `window_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_templates`
--

CREATE TABLE `whatsapp_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `whatsapp_templates`
--

INSERT INTO `whatsapp_templates` (`id`, `name`, `message`, `created_at`, `updated_at`) VALUES
(1, 'Pemberitahuan Keterlambatan', 'Yth. Orang Tua/Wali dari {nama},\n\nDiberitahukan bahwa putra/putri Bapak/Ibu terlambat masuk sekolah pada tanggal {tanggal} pukul {waktu}.\n\nMohon bimbingan dan pengawasan dari Bapak/Ibu.\n\nTerima kasih.', '2025-08-05 12:30:18', '2025-08-05 12:30:18'),
(2, 'Pemberitahuan Ketidakhadiran', 'Yth. Orang Tua/Wali dari {nama},\n\nDiberitahukan bahwa putra/putri Bapak/Ibu tidak hadir di sekolah pada tanggal {tanggal} dengan status {status}.\n\nMohon konfirmasi ketidakhadiran putra/putri Bapak/Ibu.\n\nTerima kasih.', '2025-08-05 12:30:18', '2025-08-05 12:30:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `whatsapp_webhook_logs`
--

CREATE TABLE `whatsapp_webhook_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `message_id` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `timestamp` varchar(50) DEFAULT NULL,
  `raw_data` longtext,
  `processed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur untuk view `vw_active_templates`
--
DROP TABLE IF EXISTS `vw_active_templates`;

CREATE ALGORITHM=UNDEFINED DEFINER=`dpgwgcvf_salassika`@`localhost` SQL SECURITY DEFINER VIEW `vw_active_templates`  AS  select `whatsapp_message_templates`.`id` AS `id`,`whatsapp_message_templates`.`name` AS `name`,`whatsapp_message_templates`.`display_name` AS `display_name`,`whatsapp_message_templates`.`category` AS `category`,`whatsapp_message_templates`.`language` AS `language`,`whatsapp_message_templates`.`body` AS `body`,`whatsapp_message_templates`.`variables` AS `variables` from `whatsapp_message_templates` where ((`whatsapp_message_templates`.`is_active` = 1) and (`whatsapp_message_templates`.`status` = 'APPROVED')) order by `whatsapp_message_templates`.`display_name` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `vw_recent_whatsapp_logs`
--
DROP TABLE IF EXISTS `vw_recent_whatsapp_logs`;

CREATE ALGORITHM=UNDEFINED DEFINER=`dpgwgcvf_salassika`@`localhost` SQL SECURITY DEFINER VIEW `vw_recent_whatsapp_logs`  AS  select `wl`.`id` AS `id`,`wl`.`phone_number` AS `phone_number`,`wl`.`message` AS `message`,`wl`.`message_type` AS `message_type`,`wl`.`status` AS `status`,`wl`.`sent_at` AS `sent_at`,`wl`.`created_at` AS `created_at`,(case when (`wl`.`status` = 'sent') then 'success' when (`wl`.`status` = 'failed') then 'danger' when (`wl`.`status` = 'pending') then 'warning' else 'secondary' end) AS `status_color` from `whatsapp_logs` `wl` order by `wl`.`created_at` desc ;

-- --------------------------------------------------------

--
-- Struktur untuk view `vw_whatsapp_stats`
--
DROP TABLE IF EXISTS `vw_whatsapp_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`dpgwgcvf_salassika`@`localhost` SQL SECURITY DEFINER VIEW `vw_whatsapp_stats`  AS  select cast(`whatsapp_logs`.`created_at` as date) AS `date`,`whatsapp_logs`.`message_type` AS `message_type`,`whatsapp_logs`.`status` AS `status`,count(0) AS `total_messages`,count((case when (`whatsapp_logs`.`status` = 'sent') then 1 end)) AS `sent_count`,count((case when (`whatsapp_logs`.`status` = 'failed') then 1 end)) AS `failed_count`,count((case when (`whatsapp_logs`.`status` = 'pending') then 1 end)) AS `pending_count` from `whatsapp_logs` where (`whatsapp_logs`.`created_at` >= (curdate() - interval 30 day)) group by cast(`whatsapp_logs`.`created_at` as date),`whatsapp_logs`.`message_type`,`whatsapp_logs`.`status` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD PRIMARY KEY (`id_absensi_guru`),
  ADD KEY `id_guru` (`id_guru`),
  ADD KEY `idx_absensi_guru_guru_tanggal` (`id_guru`,`tanggal`),
  ADD KEY `idx_absensi_guru_tanggal` (`tanggal`);

--
-- Indexes for table `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  ADD PRIMARY KEY (`id_absensi_siswa`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `idx_absensi_siswa_siswa_tanggal` (`id_siswa`,`tanggal`),
  ADD KEY `idx_absensi_siswa_tanggal` (`tanggal`);

--
-- Indexes for table `backup_logs`
--
ALTER TABLE `backup_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache_metadata`
--
ALTER TABLE `cache_metadata`
  ADD PRIMARY KEY (`cache_key`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `fingerprint_devices`
--
ALTER TABLE `fingerprint_devices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fingerprint_logs`
--
ALTER TABLE `fingerprint_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `idx_guru_nip` (`nip`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_guru_user_id` (`user_id`);

--
-- Indexes for table `jurusan`
--
ALTER TABLE `jurusan`
  ADD PRIMARY KEY (`id_jurusan`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `id_jurusan` (`id_jurusan`);

--
-- Indexes for table `laporan_absensi`
--
ALTER TABLE `laporan_absensi`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `id_absensi_guru` (`id_absensi_guru`),
  ADD KEY `id_absensi_siswa` (`id_absensi_siswa`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id_pengaduan`);

--
-- Indexes for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `idx_siswa_nisn` (`nisn`),
  ADD UNIQUE KEY `idx_siswa_nis` (`nis`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_siswa_user_id` (`user_id`),
  ADD KEY `idx_siswa_kelas` (`id_kelas`);

--
-- Indexes for table `system_stats`
--
ALTER TABLE `system_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stat_key` (`stat_key`);

--
-- Indexes for table `tbl_jam_kerja`
--
ALTER TABLE `tbl_jam_kerja`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_kehadiran`
--
ALTER TABLE `tbl_kehadiran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_kehadiran_user_timestamp` (`user_id`,`timestamp`),
  ADD KEY `idx_kehadiran_timestamp` (`timestamp`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD UNIQUE KEY `idx_users_uid` (`uid`),
  ADD KEY `role` (`role`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `whatsapp_automation_config`
--
ALTER TABLE `whatsapp_automation_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_automation_logs`
--
ALTER TABLE `whatsapp_automation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_notification_type` (`notification_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `whatsapp_log_id` (`whatsapp_log_id`);

--
-- Indexes for table `whatsapp_config`
--
ALTER TABLE `whatsapp_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_device_status`
--
ALTER TABLE `whatsapp_device_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_device_id` (`device_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_template_name` (`template_name`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `whatsapp_message_templates`
--
ALTER TABLE `whatsapp_message_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_name` (`name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_language` (`language`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_whatsapp_templates_category` (`category`),
  ADD KEY `idx_whatsapp_templates_active` (`is_active`);

--
-- Indexes for table `whatsapp_rate_limits`
--
ALTER TABLE `whatsapp_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_phone_type_window` (`phone_number`,`message_type`,`window_start`),
  ADD KEY `idx_window_end` (`window_end`);

--
-- Indexes for table `whatsapp_templates`
--
ALTER TABLE `whatsapp_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `whatsapp_webhook_logs`
--
ALTER TABLE `whatsapp_webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  MODIFY `id_absensi_guru` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  MODIFY `id_absensi_siswa` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `backup_logs`
--
ALTER TABLE `backup_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `fingerprint_devices`
--
ALTER TABLE `fingerprint_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `fingerprint_logs`
--
ALTER TABLE `fingerprint_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id_guru` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `jurusan`
--
ALTER TABLE `jurusan`
  MODIFY `id_jurusan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `laporan_absensi`
--
ALTER TABLE `laporan_absensi`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id_pengaduan` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `system_stats`
--
ALTER TABLE `system_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `tbl_jam_kerja`
--
ALTER TABLE `tbl_jam_kerja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `tbl_kehadiran`
--
ALTER TABLE `tbl_kehadiran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;
--
-- AUTO_INCREMENT for table `whatsapp_automation_config`
--
ALTER TABLE `whatsapp_automation_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `whatsapp_automation_logs`
--
ALTER TABLE `whatsapp_automation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `whatsapp_config`
--
ALTER TABLE `whatsapp_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `whatsapp_device_status`
--
ALTER TABLE `whatsapp_device_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `whatsapp_message_templates`
--
ALTER TABLE `whatsapp_message_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT for table `whatsapp_rate_limits`
--
ALTER TABLE `whatsapp_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `whatsapp_templates`
--
ALTER TABLE `whatsapp_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `whatsapp_webhook_logs`
--
ALTER TABLE `whatsapp_webhook_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD CONSTRAINT `absensi_guru_ibfk_1` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `absensi_siswa`
--
ALTER TABLE `absensi_siswa`
  ADD CONSTRAINT `absensi_siswa_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `guru_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan_absensi`
--
ALTER TABLE `laporan_absensi`
  ADD CONSTRAINT `laporan_absensi_ibfk_1` FOREIGN KEY (`id_absensi_guru`) REFERENCES `absensi_guru` (`id_absensi_guru`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `laporan_absensi_ibfk_2` FOREIGN KEY (`id_absensi_siswa`) REFERENCES `absensi_siswa` (`id_absensi_siswa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `siswa_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `whatsapp_automation_logs`
--
ALTER TABLE `whatsapp_automation_logs`
  ADD CONSTRAINT `whatsapp_automation_logs_ibfk_1` FOREIGN KEY (`whatsapp_log_id`) REFERENCES `whatsapp_logs` (`id`) ON DELETE SET NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
