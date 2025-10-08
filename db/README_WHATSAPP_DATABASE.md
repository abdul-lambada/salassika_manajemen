# WhatsApp Database Schema Documentation

## Overview
File `whatsapp_complete.sql` berisi skema database lengkap untuk integrasi WhatsApp dengan sistem absensi sekolah. File ini menggabungkan semua fitur terbaik dari versi sebelumnya dan menambahkan fitur enterprise-level.

## Fitur Utama

### 1. **Configuration Management**
- API key dan URL konfigurasi
- Device ID untuk multiple devices
- Delay dan retry settings
- Webhook configuration
- Template language settings

### 2. **Message Tracking**
- Log semua pengiriman pesan
- Tracking status (pending, sent, delivered, read, failed)
- Message ID tracking
- Response logging
- Retry count tracking

### 3. **Professional Template System**
- Template approval workflow (PENDING, APPROVED, REJECTED)
- Category classification (AUTHENTICATION, MARKETING, UTILITY)
- Variable management dengan JSON
- Multi-language support
- Button dan component support

### 4. **Advanced Features**
- Webhook event logging
- Device status monitoring
- Rate limiting management
- Performance optimization dengan indexes
- Views untuk easy querying
- Stored procedures untuk common operations

## Struktur Database

### Tables

#### 1. `whatsapp_config`
```sql
-- Konfigurasi API WhatsApp
- api_key: API key dari Fonnte
- api_url: Endpoint API
- country_code: Kode negara (default: 62)
- device_id: ID device (untuk multiple devices)
- delay: Delay antar pesan (default: 2 detik)
- retry: Jumlah retry untuk pesan gagal
- callback_url: URL webhook
- template_language: Bahasa template (default: id)
- webhook_secret: Secret untuk webhook
```

#### 2. `whatsapp_logs`
```sql
-- Log pengiriman pesan
- phone_number: Nomor telepon
- message: Isi pesan
- message_id: ID pesan dari API
- message_type: Tipe pesan (text, template, image, dll)
- template_name: Nama template jika menggunakan template
- status: Status pengiriman
- status_detail: Detail status
- response: Response dari API
- sent_at: Waktu terkirim
- delivered_at: Waktu terdeliver
- read_at: Waktu dibaca
- retry_count: Jumlah retry
```

#### 3. `whatsapp_message_templates`
```sql
-- Template pesan profesional
- name: Nama template (unique)
- display_name: Nama yang ditampilkan
- category: Kategori (AUTHENTICATION, MARKETING, UTILITY)
- language: Bahasa template
- status: Status approval (PENDING, APPROVED, REJECTED)
- template_id: ID template dari WhatsApp Business
- header: Header template
- body: Body template
- footer: Footer template
- variables: Array JSON variabel
- buttons: JSON buttons
- components: JSON components
- example: JSON contoh
- is_active: Status aktif
```

#### 4. `whatsapp_webhook_logs`
```sql
-- Log event webhook
- event_type: Tipe event
- message_id: ID pesan
- phone_number: Nomor telepon
- status: Status event
- timestamp: Timestamp event
- raw_data: Data mentah webhook
- processed: Status processing
```

#### 5. `whatsapp_device_status`
```sql
-- Status device WhatsApp
- device_id: ID device
- status: Status device (online, offline, connecting, error)
- last_seen: Terakhir terlihat
- battery_level: Level baterai
- signal_strength: Kekuatan sinyal
- error_message: Pesan error
```

#### 6. `whatsapp_rate_limits`
```sql
-- Rate limiting management
- phone_number: Nomor telepon
- message_type: Tipe pesan
- count: Jumlah pesan dalam window
- window_start: Awal window
- window_end: Akhir window
```

### Views

#### 1. `vw_recent_whatsapp_logs`
```sql
-- View untuk log pesan terbaru dengan status color
SELECT * FROM vw_recent_whatsapp_logs LIMIT 10;
```

#### 2. `vw_whatsapp_stats`
```sql
-- View untuk statistik WhatsApp
SELECT * FROM vw_whatsapp_stats WHERE date = CURDATE();
```

#### 3. `vw_active_templates`
```sql
-- View untuk template yang aktif dan approved
SELECT * FROM vw_active_templates;
```

### Stored Procedures

#### 1. `sp_send_whatsapp_message`
```sql
-- Kirim pesan dengan logging
CALL sp_send_whatsapp_message('628123456789', 'Halo, ini pesan test!', 'text', NULL);
```

#### 2. `sp_update_message_status`
```sql
-- Update status pesan
CALL sp_update_message_status(1, 'sent', 'msg_123', '{"status": "success"}');
```

#### 3. `sp_clean_rate_limits`
```sql
-- Bersihkan rate limit lama
CALL sp_clean_rate_limits();
```

## Template Default

File ini sudah menyertakan 5 template default untuk sistem absensi:

1. **absensi_berhasil** - Notifikasi absensi berhasil
2. **absensi_telat** - Notifikasi absensi telat
3. **notifikasi_sistem** - Notifikasi sistem
4. **pemberitahuan_keterlambatan** - Pemberitahuan keterlambatan untuk orang tua
5. **pemberitahuan_ketidakhadiran** - Pemberitahuan ketidakhadiran untuk orang tua

## Cara Penggunaan

### 1. Import Database
```bash
mysql -u root -p absensi_sekolah < db/whatsapp_complete.sql
```

### 2. Konfigurasi API
```sql
UPDATE whatsapp_config SET 
    api_key = 'YOUR_API_KEY',
    device_id = 'YOUR_DEVICE_ID',
    callback_url = 'https://yourdomain.com/webhook.php'
WHERE id = 1;
```

### 3. Kirim Pesan
```php
// Menggunakan stored procedure
$stmt = $conn->prepare("CALL sp_send_whatsapp_message(?, ?, ?, ?)");
$stmt->execute(['628123456789', 'Halo, ini pesan test!', 'text', null]);
$result = $stmt->fetch();
$logId = $result['log_id'];

// Update status setelah pengiriman
$stmt = $conn->prepare("CALL sp_update_message_status(?, ?, ?, ?)");
$stmt->execute([$logId, 'sent', 'msg_123', json_encode($response)]);
```

### 4. Monitor Logs
```sql
-- Lihat log terbaru
SELECT * FROM vw_recent_whatsapp_logs LIMIT 10;

-- Lihat statistik hari ini
SELECT * FROM vw_whatsapp_stats WHERE date = CURDATE();

-- Lihat template aktif
SELECT * FROM vw_active_templates;
```

## Performance Optimization

### Indexes
- Semua kolom yang sering diquery sudah di-index
- Composite indexes untuk kombinasi kolom
- Index untuk foreign key relationships

### Views
- Views untuk query yang sering digunakan
- Pre-computed status colors
- Aggregated statistics

### Stored Procedures
- Optimized queries untuk common operations
- Reduced network traffic
- Better security dengan parameterized queries

## Security Features

### 1. Input Validation
- Semua input divalidasi sebelum disimpan
- SQL injection protection dengan prepared statements
- XSS protection dengan proper escaping

### 2. Rate Limiting
- Built-in rate limiting system
- Configurable limits per phone number
- Automatic cleanup of old records

### 3. Audit Trail
- Complete logging of all operations
- Timestamp tracking
- User action tracking

## Maintenance

### 1. Regular Cleanup
```sql
-- Bersihkan rate limits lama
CALL sp_clean_rate_limits();

-- Bersihkan webhook logs lama (lebih dari 30 hari)
DELETE FROM whatsapp_webhook_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Bersihkan device status lama
DELETE FROM whatsapp_device_status 
WHERE updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### 2. Backup Strategy
```bash
# Backup WhatsApp tables
mysqldump -u root -p absensi_sekolah \
  whatsapp_config \
  whatsapp_logs \
  whatsapp_message_templates \
  whatsapp_webhook_logs \
  whatsapp_device_status \
  whatsapp_rate_limits > whatsapp_backup.sql
```

### 3. Monitoring Queries
```sql
-- Cek device status
SELECT * FROM whatsapp_device_status WHERE status != 'online';

-- Cek failed messages
SELECT COUNT(*) FROM whatsapp_logs WHERE status = 'failed' AND created_at >= CURDATE();

-- Cek rate limits
SELECT phone_number, COUNT(*) as message_count 
FROM whatsapp_rate_limits 
WHERE window_end > NOW() 
GROUP BY phone_number 
HAVING message_count > 10;
```

## Troubleshooting

### 1. Device Offline
```sql
-- Cek status device
SELECT * FROM whatsapp_device_status;

-- Update status device
UPDATE whatsapp_device_status SET status = 'online' WHERE device_id = 'your_device_id';
```

### 2. Failed Messages
```sql
-- Cek pesan yang gagal
SELECT * FROM whatsapp_logs WHERE status = 'failed' ORDER BY created_at DESC;

-- Cek response error
SELECT phone_number, response FROM whatsapp_logs WHERE status = 'failed';
```

### 3. Rate Limiting Issues
```sql
-- Cek rate limits
SELECT * FROM whatsapp_rate_limits WHERE phone_number = '628123456789';

-- Reset rate limits untuk nomor tertentu
DELETE FROM whatsapp_rate_limits WHERE phone_number = '628123456789';
```

## Migration dari Versi Lama

Jika Anda memiliki database dengan struktur lama, gunakan query berikut:

```sql
-- Backup data lama
CREATE TABLE whatsapp_logs_backup AS SELECT * FROM whatsapp_logs;
CREATE TABLE whatsapp_config_backup AS SELECT * FROM whatsapp_config;

-- Import struktur baru
SOURCE db/whatsapp_complete.sql;

-- Migrate data lama (jika diperlukan)
INSERT INTO whatsapp_logs (phone_number, message, status, created_at)
SELECT phone_number, message, status, created_at 
FROM whatsapp_logs_backup;
```

## Support

Untuk bantuan lebih lanjut:
- Dokumentasi Fonnte: https://fonnte.com/docs
- Email support: support@fonnte.com
- GitHub Issues: [Repository URL]
