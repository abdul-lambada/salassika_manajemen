# Panduan Guru - Sistem Absensi Sekolah dengan Fingerprint

## Daftar Isi
1. [Dashboard Guru](#dashboard-guru)
2. [Absensi Siswa](#absensi-siswa)
3. [Absensi Guru](#absensi-guru)
4. [Monitor Fingerprint](#monitor-fingerprint)
5. [Laporan Absensi](#laporan-absensi)
6. [Tips dan Trik](#tips-dan-trik)

---

## Dashboard Guru

### Fitur Utama
- **Statistik Real-time**: Melihat jumlah siswa dan guru yang hadir hari ini
- **Status Fingerprint**: Informasi absensi fingerprint hari ini
- **Absensi Terbaru**: Daftar 5 absensi terbaru hari ini
- **Aksi Cepat**: Tombol untuk mengakses fitur utama

### Cara Menggunakan
1. Login sebagai guru
2. Dashboard akan menampilkan statistik otomatis
3. Klik tombol "Lihat Absensi Real-time" untuk monitoring detail
4. Gunakan tombol aksi cepat untuk navigasi cepat

---

## Absensi Siswa

### Fitur Baru
- **Integrasi Fingerprint**: Menampilkan data absensi fingerprint
- **Statistik Per Kelas**: Grafik absensi berdasarkan status
- **Update Real-time**: Bisa mengupdate absensi yang sudah ada
- **Visual Status**: Badge berwarna untuk status kehadiran

### Langkah-langkah
1. **Pilih Kelas**
   - Klik dropdown "Pilih Kelas"
   - Pilih kelas yang akan diabsensi
   - Klik "Tampilkan Siswa"

2. **Lihat Statistik**
   - Total siswa di kelas
   - Jumlah hadir, telat, sakit, ijin, tidak hadir
   - Grafik visual untuk setiap status

3. **Input Absensi**
   - Status fingerprint akan ditampilkan otomatis
   - Pilih status kehadiran manual jika berbeda
   - Tambahkan catatan jika diperlukan
   - Klik "Simpan Absensi"

4. **Riwayat Absensi**
   - Lihat riwayat 50 absensi terbaru
   - Informasi fingerprint dan manual
   - Filter berdasarkan tanggal

### Tips
- **Fingerprint vs Manual**: Data fingerprint ditampilkan dengan badge biru
- **Update Data**: Bisa mengupdate absensi yang sudah disimpan
- **Catatan**: Gunakan untuk informasi tambahan (sakit, ijin, dll)

---

## Absensi Guru

### Fitur Baru
- **Monitoring Fingerprint**: Lihat siapa yang sudah absen fingerprint
- **Statistik Guru**: Grafik kehadiran guru hari ini
- **Pagination**: Riwayat absensi dengan halaman
- **Status Visual**: Badge berwarna untuk setiap status

### Langkah-langkah
1. **Lihat Statistik**
   - Total guru yang hadir hari ini
   - Breakdown berdasarkan status (hadir, telat, izin, sakit, alfa)

2. **Input Absensi**
   - Data fingerprint ditampilkan otomatis
   - Pilih status kehadiran untuk setiap guru
   - Tambahkan catatan jika diperlukan
   - Klik "Simpan Absensi"

3. **Riwayat Absensi**
   - Lihat riwayat dengan pagination
   - Informasi fingerprint dan manual
   - Navigasi antar halaman

### Tips
- **Fingerprint Priority**: Data fingerprint lebih akurat
- **Manual Override**: Bisa mengubah status manual jika ada kesalahan
- **Catatan Penting**: Catat alasan khusus (rapat, dinas, dll)

---

## Monitor Fingerprint

### Fitur Utama
- **Real-time Monitoring**: Data absensi fingerprint live
- **Statistik Per Jam**: Grafik absensi berdasarkan waktu
- **Siswa Belum Absen**: Daftar siswa yang belum absen
- **Auto Refresh**: Update otomatis setiap 30 detik

### Cara Menggunakan
1. **Dashboard Statistik**
   - Total absensi hari ini
   - Total user yang absen
   - Waktu absen pertama dan terakhir

2. **Grafik Per Jam**
   - Visualisasi absensi berdasarkan jam
   - Identifikasi jam sibuk absensi
   - Analisis pola kehadiran

3. **Siswa Belum Absen**
   - Daftar siswa yang belum absen fingerprint
   - Informasi kelas dan NIS
   - Monitoring kehadiran

4. **Absensi Real-time**
   - Daftar absensi terbaru
   - Informasi detail (waktu, mode, status)
   - Filter berdasarkan tipe user

### Tips
- **Refresh Manual**: Klik tombol refresh untuk update manual
- **Jam Sibuk**: Perhatikan jam 07:00-08:00 untuk absen pagi
- **Status Monitoring**: Perhatikan status SUCCESS/FAILED

---

## Laporan Absensi

### Laporan Siswa
1. **Filter Data**
   - Pilih periode (tanggal awal - akhir)
   - Pilih kelas atau semua kelas
   - Pilih status kehadiran

2. **Export Data**
   - Export ke Excel/PDF
   - Print laporan
   - Download data

### Laporan Guru
1. **Filter Data**
   - Pilih periode
   - Pilih guru atau semua guru
   - Pilih status kehadiran

2. **Analisis Data**
   - Grafik kehadiran
   - Statistik per guru
   - Perbandingan fingerprint vs manual

---

## Tips dan Trik

### 1. Optimalisasi Workflow
- **Pagi**: Monitor fingerprint real-time
- **Siang**: Input absensi manual untuk yang belum fingerprint
- **Sore**: Review dan generate laporan

### 2. Troubleshooting
- **Fingerprint Error**: Cek koneksi device
- **Data Tidak Sync**: Hubungi admin untuk sinkronisasi
- **Status Salah**: Gunakan fitur update manual

### 3. Best Practices
- **Backup Data**: Export laporan secara berkala
- **Validasi**: Cross-check fingerprint dengan manual
- **Komunikasi**: Koordinasi dengan admin untuk masalah teknis

### 4. Keyboard Shortcuts
- **Ctrl + R**: Refresh halaman
- **Ctrl + F**: Cari data
- **Tab**: Navigasi antar field

---

## FAQ (Frequently Asked Questions)

### Q: Apa beda data fingerprint dan manual?
**A**: Data fingerprint otomatis dari device, manual input oleh guru. Fingerprint lebih akurat untuk waktu absen.

### Q: Bisa mengubah data yang sudah disimpan?
**A**: Ya, bisa mengupdate absensi yang sudah ada dengan fitur edit.

### Q: Bagaimana jika device fingerprint rusak?
**A**: Gunakan input manual sementara, hubungi admin untuk perbaikan device.

### Q: Apakah data tersimpan otomatis?
**A**: Ya, data tersimpan otomatis ke database. Backup dilakukan secara berkala.

### Q: Bisa melihat riwayat lama?
**A**: Ya, bisa melihat riwayat absensi dengan filter tanggal.

---

## Kontak Support

Jika mengalami masalah teknis:
- **Admin IT**: [Nomor Telepon]
- **Email**: [Email Support]
- **WhatsApp**: [Nomor WhatsApp]

---

*Dokumen ini akan diperbarui secara berkala sesuai dengan perkembangan sistem.* 