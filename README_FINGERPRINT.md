# Sistem Absensi Sekolah dengan Fingerprint X100-C

## Deskripsi
Sistem absensi sekolah yang terintegrasi dengan perangkat fingerprint X100-C untuk mencatat kehadiran siswa dan guru secara otomatis.

## Fitur Utama

### 1. Integrasi Fingerprint
- Koneksi langsung ke perangkat fingerprint X100-C
- Sinkronisasi data absensi otomatis
- Mapping data fingerprint dengan data siswa/guru
- Pengelolaan pengguna fingerprint

### 2. Manajemen Data
- Data Master: Jurusan, Kelas, Guru, Siswa
- Absensi: Manual dan Otomatis via Fingerprint
- Laporan: Absensi Guru dan Siswa
- Pengaduan: Sistem pengaduan online

### 3. Role-based Access
- **Admin**: Akses penuh ke semua fitur
- **Guru**: Akses terbatas untuk absensi dan laporan

## Instalasi dan Konfigurasi

### 1. Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB
- Web Server (Apache/Nginx)
- Perangkat Fingerprint X100-C

### 2. Instalasi Database
```sql
-- Import database dari file db/absensi_sekolah_new.sql
mysql -u root -p < db/absensi_sekolah_new.sql
```

### 3. Konfigurasi Database
Edit file `includes/db.php`:
```php
$host = 'localhost';
$dbname = 'absensi_sekolah';
$username = 'root';
$password = '';
```

### 4. Konfigurasi Fingerprint
Edit file `includes/fingerprint_config.php`:
```php
define('FINGERPRINT_IP', '192.168.1.201'); // IP perangkat fingerprint
define('FINGERPRINT_PORT', 4370);
```

## Penggunaan

### 1. Login Sistem
- Akses: `http://localhost/absensi_sekolah/`
- Default admin: NIP: `12345678`, Password: `password`

### 2. Sinkronisasi Fingerprint
1. Login sebagai admin
2. Menu: **Fingerprint > Sinkronisasi Data**
3. Masukkan IP perangkat fingerprint
4. Klik "Sinkronkan"

### 3. Kelola Pengguna Fingerprint
1. Menu: **Fingerprint > Kelola Pengguna**
2. Tambah pengguna baru dari data siswa/guru
3. Sinkronisasi pengguna dengan perangkat

### 4. Monitoring Absensi
1. Menu: **Absensi > Log Absensi**
2. Lihat data absensi real-time
3. Filter berdasarkan tanggal dan pengguna

## Struktur File

```
absensi_sekolah/
├── admin/                          # Panel Admin
│   ├── sync_fingerprint.php       # Sinkronisasi fingerprint
│   ├── manage_fingerprint_users.php # Kelola pengguna
│   ├── attendance_records.php      # Log absensi
│   └── ...
├── guru/                          # Panel Guru
│   ├── log_absensi.php           # Log absensi guru
│   ├── absensi_siswa.php         # Input absensi siswa
│   └── ...
├── includes/
│   ├── db.php                    # Koneksi database
│   ├── fingerprint_config.php    # Konfigurasi fingerprint
│   └── zklib/                    # Library fingerprint
│       └── zklibrary.php
├── auth/                         # Autentikasi
├── templates/                    # Template UI
└── logs/                        # Log file
```

## Konfigurasi Otomatis

### 1. Cron Job untuk Sinkronisasi Otomatis
Tambahkan ke crontab:
```bash
# Sinkronisasi setiap 5 menit
*/5 * * * * php /path/to/absensi_sekolah/admin/auto_sync_fingerprint.php
```

### 2. Log Monitoring
File log: `logs/fingerprint_sync.log`
Format: `[YYYY-MM-DD HH:MM:SS] Message`

## Troubleshooting

### 1. Koneksi Fingerprint Gagal
- Periksa IP address perangkat
- Pastikan perangkat terhubung ke jaringan
- Cek firewall dan port 4370

### 2. Data Tidak Tersinkronisasi
- Periksa mapping NIS/NIP dengan data siswa/guru
- Pastikan pengguna sudah terdaftar di perangkat
- Cek log file untuk error detail

### 3. Error Database
- Periksa koneksi database
- Pastikan tabel sudah dibuat dengan benar
- Cek permission database user

## Keamanan

### 1. Autentikasi
- Password di-hash menggunakan bcrypt
- Session management
- Role-based access control

### 2. Validasi Input
- Sanitasi input user
- Prepared statements untuk query
- Validasi file upload

### 3. Logging
- Log aktivitas sistem
- Error logging
- Audit trail untuk perubahan data

## Backup dan Maintenance

### 1. Backup Database
```bash
mysqldump -u root -p absensi_sekolah > backup_$(date +%Y%m%d).sql
```

### 2. Backup File
```bash
tar -czf absensi_sekolah_$(date +%Y%m%d).tar.gz absensi_sekolah/
```

### 3. Maintenance Rutin
- Backup database harian
- Cleanup log file mingguan
- Update sistem secara berkala

## Support

Untuk bantuan teknis:
- Email: support@example.com
- Dokumentasi: README_FINGERPRINT.md
- Log file: logs/fingerprint_sync.log

## Changelog

### v2.0.0 (Current)
- Integrasi fingerprint X100-C
- Sinkronisasi otomatis
- Mapping data siswa/guru
- UI/UX improvements

### v1.0.0
- Sistem absensi manual
- Manajemen data master
- Laporan dasar

## License

Sistem ini dikembangkan untuk penggunaan internal sekolah. 