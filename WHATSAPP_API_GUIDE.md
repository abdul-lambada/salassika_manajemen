# Panduan Implementasi WhatsApp API dengan Fonnte

## Overview
Dokumen ini menjelaskan implementasi WhatsApp API menggunakan Fonnte untuk sistem absensi sekolah.

## Konfigurasi Database

### 1. Tabel Konfigurasi WhatsApp
```sql
CREATE TABLE `whatsapp_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) NOT NULL,
  `api_url` varchar(255) NOT NULL DEFAULT 'https://api.fonnte.com/send',
  `country_code` varchar(10) NOT NULL DEFAULT '62',
  `delay` int(11) NOT NULL DEFAULT 1,
  `template_language` varchar(10) NOT NULL DEFAULT 'id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Tabel Log WhatsApp
```sql
CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) NOT NULL,
  `message` text,
  `message_id` varchar(100) DEFAULT NULL,
  `message_type` varchar(50) NOT NULL DEFAULT 'text',
  `template_name` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `status_detail` text,
  `response` text,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone_number` (`phone_number`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. Tabel Template WhatsApp
```sql
CREATE TABLE `whatsapp_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `variables` text,
  `language` varchar(10) NOT NULL DEFAULT 'id',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Endpoint API Fonnte

### 1. Send Message (Single)
```php
// Kirim pesan teks tunggal
$data = [
    'target' => '628123456789',
    'message' => 'Halo, ini pesan test!'
];

$response = $waService->sendText('628123456789', 'Halo, ini pesan test!');
```

### 2. Send Message (Bulk)
```php
// Kirim pesan ke banyak nomor
$targets = ['628123456789', '628987654321'];
$message = 'Halo, ini pesan broadcast!';

$response = $waService->sendBulk($targets, $message);
```

### 3. Send Dynamic Message
```php
// Kirim pesan dengan variabel
$message = 'Halo {{nama}}, absensi Anda pada {{tanggal}} telah dicatat.';
$variables = [
    'nama' => 'John Doe',
    'tanggal' => '2024-01-15'
];

$response = $waService->sendDynamic('628123456789', $message, $variables);
```

### 4. Send Template
```php
// Kirim template pesan
$templateName = 'absensi_berhasil';
$templateVars = [
    'nama' => 'John Doe',
    'tanggal' => '2024-01-15',
    'waktu' => '08:00',
    'status' => 'Hadir'
];

$response = $waService->sendTemplate('628123456789', $templateName, $templateVars);
```

### 5. Send Media
```php
// Kirim gambar
$response = $waService->sendMedia('628123456789', 'https://example.com/image.jpg', 'Caption gambar');

// Kirim dokumen
$response = $waService->sendMedia('628123456789', 'https://example.com/document.pdf', 'Lampiran dokumen', 'document');

// Kirim video
$response = $waService->sendMedia('628123456789', 'https://example.com/video.mp4', 'Caption video', 'video');
```

### 6. Send Buttons
```php
// Kirim pesan dengan button
$message = 'Pilih opsi di bawah ini:';
$buttons = [
    ['id' => 'btn1', 'title' => 'Opsi 1'],
    ['id' => 'btn2', 'title' => 'Opsi 2']
];
$footer = 'Footer pesan';

$response = $waService->sendButtons('628123456789', $message, $buttons, $footer);
```

## Error Handling

### 1. Validasi Input
```php
// Validasi nomor telepon
if (empty($phone) || !preg_match('/^[0-9]{9,15}$/', $phone)) {
    throw new Exception('Format nomor telepon tidak valid');
}

// Validasi pesan
if (empty($message) || strlen($message) > 1000) {
    throw new Exception('Pesan tidak boleh kosong dan maksimal 1000 karakter');
}
```

### 2. Rate Limiting
```php
// Implementasi rate limiting
$currentTime = time();
$lastSentTime = isset($_SESSION['last_wa_sent']) ? $_SESSION['last_wa_sent'] : 0;
$timeDiff = $currentTime - $lastSentTime;

if ($timeDiff < 30) { // 30 detik cooldown
    throw new Exception('Harap tunggu ' . (30 - $timeDiff) . ' detik sebelum mengirim pesan lagi');
}
```

### 3. Logging
```php
// Log setiap pengiriman pesan
$logId = $this->logMessage([
    'phone_number' => $phone,
    'message' => $message,
    'message_type' => 'text',
    'status' => 'sending'
]);

// Update log setelah pengiriman
$this->updateLog($logId, [
    'status' => 'sent',
    'message_id' => $response['id'],
    'sent_at' => date('Y-m-d H:i:s')
]);
```

## Template Pesan

### 1. Template Absensi Berhasil
```
Nama: absensi_berhasil
Pesan: Halo {{nama}}, absensi Anda pada {{tanggal}} pukul {{waktu}} telah berhasil dicatat dengan status {{status}}. Terima kasih!
Variabel: ["nama", "tanggal", "waktu", "status"]
```

### 2. Template Absensi Telat
```
Nama: absensi_telat
Pesan: Halo {{nama}}, absensi Anda pada {{tanggal}} pukul {{waktu}} tercatat sebagai telat. {{keterangan}}
Variabel: ["nama", "tanggal", "waktu", "keterangan"]
```

### 3. Template Notifikasi Sistem
```
Nama: notifikasi_sistem
Pesan: {{pesan}}\n\nWaktu: {{waktu}}
Variabel: ["pesan", "waktu"]
```

## Monitoring dan Debugging

### 1. Device Status
```php
// Cek status device
$status = $waService->getDeviceStatus();
if ($status['success']) {
    echo "Device online: " . json_encode($status['data']);
} else {
    echo "Device offline: " . $status['error'];
}
```

### 2. Message Status
```php
// Cek status pesan
$messageStatus = $waService->getMessageStatus($messageId);
if ($messageStatus['success']) {
    echo "Message status: " . json_encode($messageStatus['data']);
}
```

### 3. Log Monitoring
```sql
-- Cek log pengiriman terbaru
SELECT * FROM whatsapp_logs 
ORDER BY created_at DESC 
LIMIT 10;

-- Cek status pengiriman
SELECT status, COUNT(*) as total 
FROM whatsapp_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY status;
```

## Best Practices

### 1. Security
- Jangan simpan API key di kode
- Gunakan environment variables
- Validasi semua input user
- Log semua aktivitas untuk audit

### 2. Performance
- Implementasi rate limiting
- Gunakan connection pooling
- Cache konfigurasi
- Batch processing untuk bulk messages

### 3. Reliability
- Implementasi retry mechanism
- Monitor device status
- Backup configuration
- Error notification

### 4. User Experience
- Loading indicators
- Success/error feedback
- Progress tracking
- Template preview

## Troubleshooting

### 1. Device Offline
- Cek koneksi internet device
- Restart device WhatsApp
- Cek QR code scanning
- Verifikasi API key

### 2. Message Not Delivered
- Cek format nomor telepon
- Verifikasi nomor terdaftar di WhatsApp
- Cek spam filter
- Monitor delivery status

### 3. API Errors
- Cek API key validity
- Verifikasi endpoint URL
- Monitor rate limits
- Check error logs

## Integration dengan Sistem Absensi

### 1. Notifikasi Absensi Berhasil
```php
// Setelah absensi berhasil
$waService->sendTemplate(
    $userPhone,
    'absensi_berhasil',
    [
        'nama' => $userName,
        'tanggal' => date('Y-m-d'),
        'waktu' => date('H:i'),
        'status' => 'Hadir'
    ]
);
```

### 2. Notifikasi Absensi Telat
```php
// Setelah absensi telat
$waService->sendTemplate(
    $userPhone,
    'absensi_telat',
    [
        'nama' => $userName,
        'tanggal' => date('Y-m-d'),
        'waktu' => date('H:i'),
        'keterangan' => 'Terlambat 15 menit'
    ]
);
```

### 3. Notifikasi Sistem
```php
// Notifikasi maintenance atau error
$waService->sendTemplate(
    $adminPhone,
    'notifikasi_sistem',
    [
        'pesan' => 'Sistem absensi sedang dalam maintenance',
        'waktu' => date('Y-m-d H:i:s')
    ]
);
```

## File Structure
```
includes/
├── wa_util.php          # WhatsApp service class
├── db.php              # Database connection
└── email_util.php      # Email utilities

admin/whatsapp/
├── config.php          # Configuration page
├── test.php           # Test page
├── monitoring.php     # Monitoring page
└── templates.php      # Template management

db/
└── whatsapp_config.sql # Database schema
```

## Dependencies
- PHP 7.4+
- MySQL 5.7+
- cURL extension
- JSON extension
- PDO extension

## Support
Untuk bantuan lebih lanjut, silakan hubungi:
- Dokumentasi Fonnte: https://fonnte.com/docs
- Email support: support@fonnte.com
