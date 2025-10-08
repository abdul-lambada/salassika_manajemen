# Troubleshooting WhatsApp API Integration

## Error: "Invalid JSON response" atau "Received HTML response"

### Penyebab Umum:
1. **URL API yang salah** - Menggunakan URL web interface alih-alih API endpoint
2. **API Key yang tidak valid** - API Key tidak terdaftar atau salah
3. **Endpoint yang tidak ada** - Mengakses endpoint yang tidak tersedia
4. **Masalah koneksi** - Tidak dapat terhubung ke server Fonnte

### Solusi:

#### 1. Periksa URL API
**URL yang Benar:**
```
https://api.fonnte.com
```

**URL yang Salah:**
```
https://api.fonnte.com/send
https://md.fonnte.com/new/send.php
http://api.fonnte.com
https://fonnte.com/api
```

#### 2. Periksa API Key
- Masuk ke dashboard Fonnte
- Salin API Key yang benar
- Pastikan API Key aktif dan memiliki izin yang cukup

#### 3. Periksa Device Status
- Pastikan device WhatsApp terhubung dan online
- Periksa status device di dashboard Fonnte
- Restart device jika diperlukan

#### 4. Test Koneksi
1. Buka halaman "Konfigurasi WhatsApp"
2. Masukkan API Key dan URL yang benar
3. Klik "Test Koneksi"
4. Periksa pesan error yang muncul

### Error Messages dan Solusi:

#### "Cannot GET /send/device/status"
- Endpoint device status yang benar adalah `/device` (bukan `/device/status`)
- Periksa dokumentasi Fonnte terbaru untuk endpoint yang benar
- Coba endpoint alternatif jika tersedia

#### "URL API tidak valid atau endpoint tidak ditemukan"
- Periksa URL API di konfigurasi
- Pastikan menggunakan `https://api.fonnte.com/send`
- Periksa apakah endpoint tersedia di dokumentasi Fonnte

#### "API Key tidak valid atau tidak memiliki izin yang cukup"
- Periksa API Key di dashboard Fonnte
- Pastikan API Key aktif
- Periksa izin yang diberikan kepada API Key

#### "Endpoint tidak ditemukan"
- Periksa endpoint yang digunakan
- Pastikan endpoint sesuai dengan dokumentasi Fonnte
- Coba endpoint yang berbeda

#### "Masalah koneksi jaringan"
- Periksa koneksi internet
- Periksa firewall atau proxy
- Coba akses dari jaringan yang berbeda

### Langkah Debugging:

1. **Periksa Log Error**
   - Buka file log PHP untuk melihat error detail
   - Periksa response dari API

2. **Test dengan cURL**
   ```bash
   curl -X POST "https://api.fonnte.com/send" \
        -H "Authorization: YOUR_API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"target":"6281234567890","message":"Test"}'
   ```

3. **Periksa Response**
   - Jika menerima HTML, berarti URL salah
   - Jika menerima JSON error, periksa parameter
   - Jika timeout, periksa koneksi

### Konfigurasi yang Benar:

```php
// Contoh konfigurasi yang benar
$config = array(
    'api_key' => 'YOUR_FONNTE_API_KEY',
    'api_url' => 'https://api.fonnte.com',
    'country_code' => '62',
    'delay' => 2,
    'template_language' => 'id'
);
```

### Endpoint yang Tersedia:

- `POST /send` - Kirim pesan teks
- `POST /send-template` - Kirim template
- `POST /send-image` - Kirim gambar
- `POST /send-video` - Kirim video
- `POST /send-document` - Kirim dokumen
- `POST /send-audio` - Kirim audio
- `POST /send-button` - Kirim button
- `GET /device` - Status device
- `GET /message/{id}` - Status pesan

### Endpoint Alternatif untuk Testing:

Jika endpoint `/device` tidak berfungsi, coba endpoint alternatif:

1. **Test dengan send message sederhana**:
   ```bash
   curl -X POST "https://api.fonnte.com/send" \
        -H "Authorization: YOUR_API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"target":"6281234567890","message":"Test"}'
   ```

2. **Periksa dokumentasi Fonnte terbaru** untuk endpoint yang benar

### Tips Tambahan:

1. **Gunakan HTTPS** - Selalu gunakan URL dengan HTTPS
2. **Periksa Rate Limit** - Jangan kirim terlalu banyak request
3. **Validasi Nomor** - Pastikan format nomor telepon benar
4. **Test di Environment Terpisah** - Test di development sebelum production
5. **Periksa Versi API** - Pastikan menggunakan versi API yang benar

### Kontak Support:

Jika masalah masih berlanjut:
1. Periksa dokumentasi Fonnte terbaru
2. Hubungi support Fonnte
3. Periksa status server Fonnte
4. Coba di waktu yang berbeda
5. Periksa apakah ada perubahan endpoint di API Fonnte
