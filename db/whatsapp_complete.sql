-- =====================================================
-- COMPLETE WHATSAPP DATABASE SCHEMA
-- Sistem Absensi Sekolah - WhatsApp Integration
-- =====================================================

-- WhatsApp Configuration Table
CREATE TABLE IF NOT EXISTS `whatsapp_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) NOT NULL,
  `api_url` varchar(255) NOT NULL DEFAULT 'https://api.fonnte.com/send',
  `country_code` varchar(10) NOT NULL DEFAULT '62',
  `device_id` varchar(50) NULL,
  `delay` int(11) NOT NULL DEFAULT 2 COMMENT 'Delay between messages in seconds',
  `retry` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of retries for failed messages',
  `callback_url` varchar(255) NULL,
  `template_language` varchar(10) NOT NULL DEFAULT 'id',
  `webhook_secret` varchar(255) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default configuration
INSERT INTO `whatsapp_config` (`id`, `api_key`, `api_url`, `country_code`, `delay`, `retry`, `template_language`) 
VALUES (1, '', 'https://api.fonnte.com/send', '62', 2, 0, 'id')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- WhatsApp Logs Table
CREATE TABLE IF NOT EXISTS `whatsapp_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) NOT NULL,
  `message` text,
  `message_id` varchar(100) DEFAULT NULL,
  `message_type` enum('text','template','image','document','video','audio','button','list') NOT NULL DEFAULT 'text',
  `template_name` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `status_detail` varchar(50) NULL,
  `response` longtext,
  `sent_at` datetime NULL,
  `delivered_at` datetime NULL,
  `read_at` datetime NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone_number` (`phone_number`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WhatsApp Message Templates Table (Professional Version)
CREATE TABLE IF NOT EXISTS `whatsapp_message_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `category` enum('AUTHENTICATION','MARKETING','UTILITY') NOT NULL DEFAULT 'UTILITY',
  `language` varchar(10) NOT NULL DEFAULT 'id',
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `template_id` varchar(100) NULL,
  `header` text NULL,
  `body` text NOT NULL,
  `footer` text NULL,
  `variables` text NULL COMMENT 'JSON array of variable names',
  `buttons` text NULL COMMENT 'JSON buttons data',
  `components` text NULL COMMENT 'JSON components data',
  `example` text NULL COMMENT 'JSON example data',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default templates for attendance system
INSERT INTO `whatsapp_message_templates` (`name`, `display_name`, `category`, `language`, `status`, `body`, `variables`, `is_active`) VALUES
('absensi_berhasil', 'Absensi Berhasil', 'UTILITY', 'id', 'APPROVED', 'Halo {{nama}}, absensi Anda pada {{tanggal}} pukul {{waktu}} telah berhasil dicatat dengan status {{status}}. Terima kasih!', '["nama", "tanggal", "waktu", "status"]', 1),
('absensi_telat', 'Absensi Telat', 'UTILITY', 'id', 'APPROVED', 'Halo {{nama}}, absensi Anda pada {{tanggal}} pukul {{waktu}} tercatat sebagai telat. {{keterangan}}', '["nama", "tanggal", "waktu", "keterangan"]', 1),
('notifikasi_sistem', 'Notifikasi Sistem', 'UTILITY', 'id', 'APPROVED', '{{pesan}}\n\nWaktu: {{waktu}}', '["pesan", "waktu"]', 1),
('pemberitahuan_keterlambatan', 'Pemberitahuan Keterlambatan', 'UTILITY', 'id', 'APPROVED', 'Yth. Orang Tua/Wali dari {{nama}},\n\nDiberitahukan bahwa putra/putri Bapak/Ibu terlambat masuk sekolah pada tanggal {{tanggal}} pukul {{waktu}}.\n\nMohon bimbingan dan pengawasan dari Bapak/Ibu.\n\nTerima kasih.', '["nama", "tanggal", "waktu"]', 1),
('pemberitahuan_ketidakhadiran', 'Pemberitahuan Ketidakhadiran', 'UTILITY', 'id', 'APPROVED', 'Yth. Orang Tua/Wali dari {{nama}},\n\nDiberitahukan bahwa putra/putri Bapak/Ibu tidak hadir di sekolah pada tanggal {{tanggal}} dengan status {{status}}.\n\nMohon konfirmasi ketidakhadiran putra/putri Bapak/Ibu.\n\nTerima kasih.', '["nama", "tanggal", "status"]', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- WhatsApp Webhook Logs Table (for tracking webhook events)
CREATE TABLE IF NOT EXISTS `whatsapp_webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `message_id` varchar(100) NULL,
  `phone_number` varchar(20) NULL,
  `status` varchar(20) NULL,
  `timestamp` varchar(50) NULL,
  `raw_data` longtext,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WhatsApp Device Status Table (for tracking device status)
CREATE TABLE IF NOT EXISTS `whatsapp_device_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(50) NOT NULL,
  `status` enum('online','offline','connecting','error') NOT NULL DEFAULT 'offline',
  `last_seen` datetime NULL,
  `battery_level` int(3) NULL,
  `signal_strength` int(3) NULL,
  `error_message` text NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_id` (`device_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WhatsApp Rate Limiting Table (for managing rate limits)
CREATE TABLE IF NOT EXISTS `whatsapp_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) NOT NULL,
  `message_type` varchar(50) NOT NULL,
  `count` int(11) NOT NULL DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `window_end` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone_type_window` (`phone_number`, `message_type`, `window_start`),
  KEY `idx_window_end` (`window_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Note: Indexes are already defined in table creation
-- Additional indexes can be added here if needed in the future

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- View for recent WhatsApp logs
CREATE OR REPLACE VIEW `vw_recent_whatsapp_logs` AS
SELECT 
    wl.id,
    wl.phone_number,
    wl.message,
    wl.message_type,
    wl.status,
    wl.sent_at,
    wl.created_at,
    CASE 
        WHEN wl.status = 'sent' THEN 'success'
        WHEN wl.status = 'failed' THEN 'danger'
        WHEN wl.status = 'pending' THEN 'warning'
        ELSE 'secondary'
    END as status_color
FROM whatsapp_logs wl
ORDER BY wl.created_at DESC;

-- View for WhatsApp statistics
CREATE OR REPLACE VIEW `vw_whatsapp_stats` AS
SELECT 
    DATE(created_at) as date,
    message_type,
    status,
    COUNT(*) as total_messages,
    COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_count,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
FROM whatsapp_logs 
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), message_type, status;

-- View for active templates
CREATE OR REPLACE VIEW `vw_active_templates` AS
SELECT 
    id,
    name,
    display_name,
    category,
    language,
    body,
    variables
FROM whatsapp_message_templates 
WHERE is_active = 1 AND status = 'APPROVED'
ORDER BY display_name;

-- =====================================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- =====================================================

DELIMITER //

-- Procedure to send WhatsApp message with logging
CREATE PROCEDURE `sp_send_whatsapp_message`(
    IN p_phone_number VARCHAR(20),
    IN p_message TEXT,
    IN p_message_type ENUM('text','template','image','document','video','audio','button','list'),
    IN p_template_name VARCHAR(100)
)
BEGIN
    DECLARE v_log_id INT;
    DECLARE v_config_id INT;
    
    -- Get configuration
    SELECT id INTO v_config_id FROM whatsapp_config LIMIT 1;
    
    -- Log the message
    INSERT INTO whatsapp_logs (phone_number, message, message_type, template_name, status)
    VALUES (p_phone_number, p_message, p_message_type, p_template_name, 'pending');
    
    SET v_log_id = LAST_INSERT_ID();
    
    -- Return log ID for external processing
    SELECT v_log_id as log_id;
END //

-- Procedure to update message status
CREATE PROCEDURE `sp_update_message_status`(
    IN p_log_id INT,
    IN p_status VARCHAR(20),
    IN p_message_id VARCHAR(100),
    IN p_response TEXT
)
BEGIN
    UPDATE whatsapp_logs 
    SET 
        status = p_status,
        message_id = COALESCE(p_message_id, message_id),
        response = COALESCE(p_response, response),
        sent_at = CASE WHEN p_status = 'sent' THEN NOW() ELSE sent_at END,
        delivered_at = CASE WHEN p_status = 'delivered' THEN NOW() ELSE delivered_at END,
        read_at = CASE WHEN p_status = 'read' THEN NOW() ELSE read_at END
    WHERE id = p_log_id;
END //

-- Procedure to clean old rate limit records
CREATE PROCEDURE `sp_clean_rate_limits`()
BEGIN
    DELETE FROM whatsapp_rate_limits 
    WHERE window_end < NOW();
END //

DELIMITER ;

-- =====================================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =====================================================

-- Trigger to update updated_at timestamp
DELIMITER //

CREATE TRIGGER `tr_whatsapp_config_update` 
BEFORE UPDATE ON `whatsapp_config`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //

CREATE TRIGGER `tr_whatsapp_message_templates_update` 
BEFORE UPDATE ON `whatsapp_message_templates`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //

DELIMITER ;

-- =====================================================
-- COMMENTS AND DOCUMENTATION
-- =====================================================

/*
WhatsApp Database Schema for Absensi Sekolah System

This schema provides a complete WhatsApp integration solution with:
1. Configuration management
2. Message logging and tracking
3. Template management with approval workflow
4. Webhook event logging
5. Device status monitoring
6. Rate limiting
7. Performance optimization with indexes and views
8. Stored procedures for common operations

Tables:
- whatsapp_config: API configuration and settings
- whatsapp_logs: Message delivery tracking
- whatsapp_message_templates: Professional template management
- whatsapp_webhook_logs: Webhook event tracking
- whatsapp_device_status: Device connectivity monitoring
- whatsapp_rate_limits: Rate limiting management

Views:
- vw_recent_whatsapp_logs: Recent message logs with status colors
- vw_whatsapp_stats: Statistical data for reporting
- vw_active_templates: Approved and active templates

Stored Procedures:
- sp_send_whatsapp_message: Send message with logging
- sp_update_message_status: Update message delivery status
- sp_clean_rate_limits: Clean old rate limit records

Usage:
1. Import this file to create all tables and initial data
2. Configure API settings in whatsapp_config table
3. Use stored procedures for message operations
4. Monitor logs and statistics through views
*/
