-- =====================================================
-- WHATSAPP AUTOMATION DATABASE SCHEMA
-- Sistem Absensi Sekolah - WhatsApp Automation
-- =====================================================

-- WhatsApp Automation Configuration Table
CREATE TABLE IF NOT EXISTS `whatsapp_automation_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notify_late_arrival` tinyint(1) NOT NULL DEFAULT 1,
  `notify_absence` tinyint(1) NOT NULL DEFAULT 1,
  `notify_parents` tinyint(1) NOT NULL DEFAULT 1,
  `notify_admin` tinyint(1) NOT NULL DEFAULT 1,
  `late_threshold_minutes` int(11) NOT NULL DEFAULT 15,
  `absence_check_time` time NOT NULL DEFAULT '09:00:00',
  `daily_summary_time` time NOT NULL DEFAULT '16:00:00',
  `weekend_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default configuration
INSERT INTO `whatsapp_automation_config` 
(`notify_late_arrival`, `notify_absence`, `notify_parents`, `notify_admin`, `late_threshold_minutes`, `absence_check_time`, `daily_summary_time`, `is_active`) 
VALUES (1, 1, 1, 1, 15, '09:00:00', '16:00:00', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- WhatsApp Automation Logs Table
CREATE TABLE IF NOT EXISTS `whatsapp_automation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('guru','siswa') NOT NULL,
  `attendance_status` varchar(20) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `recipient_type` enum('user','parent','admin') NOT NULL,
  `template_used` varchar(100) DEFAULT NULL,
  `message_sent` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_log_id` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_attendance_date` (`attendance_date`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`whatsapp_log_id`) REFERENCES `whatsapp_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default WhatsApp message templates for attendance automation
INSERT INTO `whatsapp_message_templates` (`name`, `display_name`, `category`, `language`, `body`, `variables`, `is_active`) VALUES
-- User confirmation templates
('attendance_confirmation', 'Konfirmasi Kehadiran', 'attendance', 'id', 
'✅ *KONFIRMASI KEHADIRAN*\n\nHalo {{nama}},\n\nKehadiran Anda telah tercatat:\n📅 Tanggal: {{tanggal}}\n⏰ Waktu: {{waktu}}\n📍 Status: {{status}}\n\nTerima kasih telah hadir tepat waktu! 🙏', 
'["nama", "tanggal", "waktu", "status"]', 1),

('attendance_late', 'Notifikasi Terlambat', 'attendance', 'id', 
'⚠️ *NOTIFIKASI KETERLAMBATAN*\n\nHalo {{nama}},\n\nAnda tercatat terlambat:\n📅 Tanggal: {{tanggal}}\n⏰ Waktu kedatangan: {{waktu}}\n⏱️ Terlambat: {{menit_terlambat}} menit\n\nHarap lebih memperhatikan waktu kedatangan. Terima kasih! 🙏', 
'["nama", "tanggal", "waktu", "menit_terlambat"]', 1),

('attendance_permission_confirm', 'Konfirmasi Izin', 'attendance', 'id', 
'ℹ️ *KONFIRMASI IZIN*\n\nHalo {{nama}},\n\nIzin Anda telah tercatat:\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nSemoga cepat pulih dan dapat kembali beraktivitas normal. 🙏', 
'["nama", "tanggal", "status"]', 1),

('attendance_sick_confirm', 'Konfirmasi Sakit', 'attendance', 'id', 
'🏥 *KONFIRMASI SAKIT*\n\nHalo {{nama}},\n\nStatus sakit Anda telah tercatat:\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nSemoga lekas sembuh dan dapat kembali beraktivitas. Get well soon! 🙏', 
'["nama", "tanggal", "status"]', 1),

-- Parent notification templates
('attendance_present_parent', 'Notifikasi Orang Tua - Hadir', 'parent_notification', 'id', 
'✅ *NOTIFIKASI KEHADIRAN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n⏰ Waktu: {{waktu}}\n📍 Status: {{status}}\n\nAnak Anda telah hadir di sekolah. Terima kasih! 🙏', 
'["nama_anak", "kelas", "tanggal", "waktu", "status"]', 1),

('attendance_late_parent', 'Notifikasi Orang Tua - Terlambat', 'parent_notification', 'id', 
'⚠️ *NOTIFIKASI KETERLAMBATAN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n⏰ Waktu kedatangan: {{waktu}}\n⏱️ Terlambat: {{menit_terlambat}} menit\n\nMohon perhatian untuk kedisiplinan waktu anak. Terima kasih! 🙏', 
'["nama_anak", "kelas", "tanggal", "waktu", "menit_terlambat"]', 1),

('attendance_absent_parent', 'Notifikasi Orang Tua - Tidak Hadir', 'parent_notification', 'id', 
'❌ *NOTIFIKASI KETIDAKHADIRAN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nAnak Anda belum hadir di sekolah hari ini. Jika berhalangan, mohon konfirmasi ke sekolah. Terima kasih! 🙏', 
'["nama_anak", "kelas", "tanggal", "status"]', 1),

('attendance_permission_parent', 'Notifikasi Orang Tua - Izin', 'parent_notification', 'id', 
'ℹ️ *NOTIFIKASI IZIN ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nIzin anak Anda telah tercatat. Terima kasih atas informasinya! 🙏', 
'["nama_anak", "kelas", "tanggal", "status"]', 1),

('attendance_sick_parent', 'Notifikasi Orang Tua - Sakit', 'parent_notification', 'id', 
'🏥 *NOTIFIKASI SAKIT ANAK*\n\nYth. Orang Tua/Wali,\n\nKami informasikan bahwa:\n👤 Nama: {{nama_anak}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nSemoga anak Anda lekas sembuh dan dapat kembali bersekolah. Get well soon! 🙏', 
'["nama_anak", "kelas", "tanggal", "status"]', 1),

-- Admin notification templates
('late_alert', 'Alert Admin - Keterlambatan', 'admin_alert', 'id', 
'⚠️ *ALERT KETERLAMBATAN*\n\n👤 Nama: {{nama}}\n👥 Tipe: {{user_type}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n⏰ Waktu: {{waktu}}\n⏱️ Terlambat: {{menit_terlambat}} menit\n\nPerlu perhatian khusus untuk kedisiplinan.', 
'["nama", "user_type", "kelas", "tanggal", "waktu", "menit_terlambat"]', 1),

('absence_alert', 'Alert Admin - Ketidakhadiran', 'admin_alert', 'id', 
'❌ *ALERT KETIDAKHADIRAN*\n\n👤 Nama: {{nama}}\n👥 Tipe: {{user_type}}\n🏫 Kelas: {{kelas}}\n📅 Tanggal: {{tanggal}}\n📍 Status: {{status}}\n\nPerlu tindak lanjut untuk ketidakhadiran ini.', 
'["nama", "user_type", "kelas", "tanggal", "status"]', 1),

('daily_attendance_summary', 'Ringkasan Harian Admin', 'admin_summary', 'id', 
'📊 *RINGKASAN KEHADIRAN HARIAN*\n\n📅 Tanggal: {{tanggal}}\n\n✅ Hadir: {{hadir}} orang\n⚠️ Terlambat: {{terlambat}} orang\n❌ Tidak Hadir: {{tidak_hadir}} orang\nℹ️ Izin: {{izin}} orang\n🏥 Sakit: {{sakit}} orang\n\n📈 Total: {{total}} orang\n\nLaporan otomatis sistem absensi sekolah.', 
'["tanggal", "hadir", "terlambat", "tidak_hadir", "izin", "sakit", "total"]', 1)

ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Create index for better performance
CREATE INDEX IF NOT EXISTS `idx_whatsapp_templates_category` ON `whatsapp_message_templates`(`category`);
CREATE INDEX IF NOT EXISTS `idx_whatsapp_templates_active` ON `whatsapp_message_templates`(`is_active`);
