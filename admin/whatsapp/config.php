<?php
session_start();

$title = "Konfigurasi WhatsApp";
$active_page = "whatsapp_config";

include '../../templates/header.php';
include '../../templates/sidebar.php';
include '../../includes/db.php';
require_once __DIR__ . '/../../includes/wa_util.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$waService = new WhatsAppService($conn);
$config = $waService->getConfig();
$message = '';
$status = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle fix configuration
        if (isset($_POST['fix_config'])) {
            $sql = "UPDATE whatsapp_config SET api_url = 'https://api.fonnte.com' WHERE id = 1";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->errorInfo()[2]);
            }
            
            $success = $stmt->execute();
            
            if ($success) {
                $message = 'Konfigurasi API URL berhasil diperbaiki! Sekarang menggunakan https://api.fonnte.com';
                $status = 'success';
                
                // Refresh config
                $config = $waService->getConfig();
            } else {
                throw new Exception('Gagal memperbarui konfigurasi');
            }
        } else {
            // Regular form submission
            $api_key = trim($_POST['api_key']);
            $api_url = trim($_POST['api_url']);
            $country_code = trim($_POST['country_code']);
            $device_id = trim($_POST['device_id']);
            $delay = isset($_POST['delay']) ? (int)$_POST['delay'] : 2;
            $retry = isset($_POST['retry']) ? (int)$_POST['retry'] : 0;
            $callback_url = trim($_POST['callback_url']);
            $template_language = trim($_POST['template_language']);
            $webhook_secret = trim($_POST['webhook_secret']);
            
            // Validation
            if (empty($api_key)) {
                throw new Exception('API Key tidak boleh kosong');
            }
            
            if (empty($api_url)) {
                throw new Exception('API URL tidak boleh kosong');
            }
            
            if (empty($country_code)) {
                throw new Exception('Country Code tidak boleh kosong');
            }
            
            // Remove country code prefix if present
            $country_code = ltrim($country_code, '+');
            
            // Update configuration
            $sql = "UPDATE whatsapp_config SET 
                    api_key = ?, 
                    api_url = ?, 
                    country_code = ?, 
                    device_id = ?,
                    delay = ?, 
                    retry = ?,
                    callback_url = ?,
                    template_language = ?,
                    webhook_secret = ?,
                    updated_at = NOW()
                    WHERE id = 1";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->errorInfo()[2]);
            }
            
            $success = $stmt->execute([
                $api_key,
                $api_url,
                $country_code,
                $device_id,
                $delay,
                $retry,
                $callback_url,
                $template_language,
                $webhook_secret
            ]);
            
            if (!$success) {
                throw new Exception('Database error: ' . $stmt->errorInfo()[2]);
            }
            
            $message = 'Konfigurasi WhatsApp berhasil diperbarui!';
            $status = 'success';
            
            // Refresh config
            $config = $waService->getConfig();
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $status = 'error';
    }
}

// Test connection
$testResult = null;
if (isset($_POST['test_connection'])) {
    try {
        // Check if API key is configured
        if (empty($config['api_key'])) {
            throw new Exception('API Key belum dikonfigurasi. Silakan masukkan API Key terlebih dahulu.');
        }
        
        // Check if API URL is configured
        if (empty($config['api_url'])) {
            throw new Exception('API URL belum dikonfigurasi. Silakan masukkan API URL terlebih dahulu.');
        }
        
        // Try alternative connection check first
        $testResult = $waService->checkApiConnection();
        if ($testResult['success']) {
            $message = 'Koneksi WhatsApp berhasil! API dapat diakses dan siap digunakan.';
            $status = 'success';
        } else {
            // If alternative method fails, try device status as fallback
            $testResult = $waService->getDeviceStatus();
            if ($testResult['success']) {
                $message = 'Koneksi WhatsApp berhasil! API dapat diakses dan siap digunakan.';
                $status = 'success';
            } else {
                $errorMsg = $testResult['error'];
                
                // If device endpoint fails, try a simple connection test
                if (strpos($errorMsg, 'Method Not Allowed') !== false || strpos($errorMsg, '404') !== false || strpos($errorMsg, 'device') !== false) {
                    try {
                        // Try a simple connection test with a minimal request
                        $testUrl = rtrim($config['api_url'], '/') . '/send';
                        $headers = array(
                            'Authorization: ' . $config['api_key'],
                            'Content-Type: application/json'
                        );
                        
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $testUrl,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT => 10,
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => json_encode(array('test' => 'connection'))
                        ));
                        
                        $response = curl_exec($curl);
                        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        curl_close($curl);
                        
                        if ($httpCode === 401) {
                            $message = 'Koneksi WhatsApp gagal: API Key tidak valid atau tidak memiliki izin yang cukup. Silakan periksa API Key Anda.';
                        } elseif ($httpCode === 404) {
                            $message = 'Koneksi WhatsApp gagal: Endpoint tidak ditemukan. Pastikan URL API menggunakan https://api.fonnte.com dan API Key valid.';
                        } elseif ($httpCode === 405) {
                            $message = 'Koneksi WhatsApp gagal: Method tidak didukung. Pastikan menggunakan endpoint yang benar.';
                        } else {
                            $message = 'Koneksi WhatsApp berhasil! API dapat diakses. Silakan coba kirim pesan test untuk memverifikasi pengiriman.';
                            $status = 'success';
                        }
                    } catch (Exception $e) {
                        $message = 'Koneksi WhatsApp gagal: ' . $errorMsg . ' (Alternatif test juga gagal: ' . $e->getMessage() . ')';
                    }
                } else {
                    // Provide more specific error messages
                    if (strpos($errorMsg, 'HTML response') !== false) {
                        $message = 'Koneksi WhatsApp gagal: URL API tidak valid atau endpoint tidak ditemukan. Pastikan URL API benar dan endpoint tersedia.';
                    } elseif (strpos($errorMsg, 'Cannot GET') !== false) {
                        $message = 'Koneksi WhatsApp gagal: Endpoint tidak ditemukan. Pastikan URL API menggunakan https://api.fonnte.com (bukan https://api.fonnte.com/send).';
                    } elseif (strpos($errorMsg, '401') !== false || strpos($errorMsg, 'Unauthorized') !== false) {
                        $message = 'Koneksi WhatsApp gagal: API Key tidak valid atau tidak memiliki izin yang cukup.';
                    } elseif (strpos($errorMsg, '404') !== false) {
                        $message = 'Koneksi WhatsApp gagal: Endpoint tidak ditemukan. Periksa URL API Anda.';
                    } elseif (strpos($errorMsg, 'cURL Error') !== false) {
                        $message = 'Koneksi WhatsApp gagal: Masalah koneksi jaringan. Periksa koneksi internet Anda.';
                    } else {
                        $message = 'Koneksi WhatsApp gagal: ' . $errorMsg;
                    }
                }
                if ($status !== 'success') {
                    $status = 'error';
                }
            }
        }
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        
        // Provide more specific error messages
        if (strpos($errorMsg, 'HTML response') !== false) {
            $message = 'Error testing connection: URL API tidak valid atau endpoint tidak ditemukan. Pastikan URL API benar dan endpoint tersedia.';
        } elseif (strpos($errorMsg, 'Cannot GET') !== false) {
            $message = 'Error testing connection: Endpoint tidak ditemukan. Pastikan URL API menggunakan https://api.fonnte.com (bukan https://api.fonnte.com/send).';
        } elseif (strpos($errorMsg, '401') !== false || strpos($errorMsg, 'Unauthorized') !== false) {
            $message = 'Error testing connection: API Key tidak valid atau tidak memiliki izin yang cukup.';
        } elseif (strpos($errorMsg, '404') !== false) {
            $message = 'Error testing connection: Endpoint tidak ditemukan. Periksa URL API Anda.';
        } elseif (strpos($errorMsg, 'cURL Error') !== false) {
            $message = 'Error testing connection: Masalah koneksi jaringan. Periksa koneksi internet Anda.';
        } else {
            $message = 'Error testing connection: ' . $errorMsg;
        }
        $status = 'error';
    }
}
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Konfigurasi WhatsApp API</h1>
                <div class="d-none d-sm-inline-block">
                    <span class="badge bg-primary">Fonnte API</span>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?= $status === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Warning for common configuration issues -->
            <?php if (isset($config['api_url']) && strpos($config['api_url'], '/send') !== false): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Peringatan:</strong> URL API saat ini menggunakan format yang salah. 
                    URL yang benar adalah <code>https://api.fonnte.com</code> (bukan <code>https://api.fonnte.com/send</code>).
                    <br><br>
                    <button type="submit" name="fix_config" class="btn btn-warning btn-sm">
                        <i class="fas fa-wrench"></i> Perbaiki Otomatis
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Konfigurasi Fonnte API</h6>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="form-group">
                                    <label for="api_key">API Key <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="api_key" name="api_key" 
                                           value="<?= htmlspecialchars(isset($config['api_key']) ? $config['api_key'] : '') ?>" required>
                                    <small class="form-text text-muted">
                                        Masukkan API Key dari dashboard Fonnte Anda
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="api_url">API URL <span class="text-danger">*</span></label>
                                    <input type="url" class="form-control" id="api_url" name="api_url" 
                                           value="<?= htmlspecialchars(isset($config['api_url']) ? $config['api_url'] : 'https://api.fonnte.com') ?>" required>
                                    <small class="form-text text-muted">
                                        URL endpoint API Fonnte (default: https://api.fonnte.com)
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="country_code">Country Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="country_code" name="country_code" 
                                           value="<?= htmlspecialchars(isset($config['country_code']) ? $config['country_code'] : '62') ?>" required>
                                    <small class="form-text text-muted">
                                        Kode negara tanpa tanda + (contoh: 62 untuk Indonesia)
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="device_id">Device ID</label>
                                    <input type="text" class="form-control" id="device_id" name="device_id" 
                                           value="<?= htmlspecialchars(isset($config['device_id']) ? $config['device_id'] : '') ?>">
                                    <small class="form-text text-muted">
                                        ID device WhatsApp (opsional, akan otomatis terdeteksi)
                                    </small>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="delay">Delay (detik)</label>
                                            <input type="number" class="form-control" id="delay" name="delay" 
                                                   value="<?= htmlspecialchars(isset($config['delay']) ? $config['delay'] : '2') ?>" min="1" max="60">
                                            <small class="form-text text-muted">
                                                Delay antara pengiriman pesan (1-60 detik)
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="retry">Retry Count</label>
                                            <input type="number" class="form-control" id="retry" name="retry" 
                                                   value="<?= htmlspecialchars(isset($config['retry']) ? $config['retry'] : '0') ?>" min="0" max="5">
                                            <small class="form-text text-muted">
                                                Jumlah percobaan ulang untuk pesan gagal (0-5)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="callback_url">Callback URL</label>
                                    <input type="url" class="form-control" id="callback_url" name="callback_url" 
                                           value="<?= htmlspecialchars(isset($config['callback_url']) ? $config['callback_url'] : '') ?>">
                                    <small class="form-text text-muted">
                                        URL untuk menerima webhook dari Fonnte (opsional)
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="template_language">Template Language</label>
                                    <select class="form-control" id="template_language" name="template_language">
                                        <option value="id" <?= (isset($config['template_language']) ? $config['template_language'] : 'id') === 'id' ? 'selected' : '' ?>>Indonesia</option>
                                        <option value="en" <?= (isset($config['template_language']) ? $config['template_language'] : 'id') === 'en' ? 'selected' : '' ?>>English</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Bahasa default untuk template pesan
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="webhook_secret">Webhook Secret</label>
                                    <input type="password" class="form-control" id="webhook_secret" name="webhook_secret" 
                                           value="<?= htmlspecialchars(isset($config['webhook_secret']) ? $config['webhook_secret'] : '') ?>">
                                    <small class="form-text text-muted">
                                        Secret key untuk verifikasi webhook (opsional)
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Simpan Konfigurasi
                                    </button>
                                    <button type="submit" name="test_connection" class="btn btn-info ml-2">
                                        <i class="fas fa-plug"></i> Test Koneksi
                                    </button>
                                    <a href="test.php" class="btn btn-success ml-2">
                                        <i class="fas fa-paper-plane"></i> Test Kirim Pesan
                                    </a>
                                    <button type="submit" name="fix_config" class="btn btn-warning ml-2">
                                        <i class="fas fa-wrench"></i> Perbaiki Konfigurasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Informasi API</h6>
                        </div>
                        <div class="card-body">
                            <h6>Endpoint yang Tersedia:</h6>
                            <ul class="list-unstyled">
                                <li><code>/send</code> - Kirim pesan teks</li>
                                <li><code>/send-template</code> - Kirim template</li>
                                <li><code>/send-image</code> - Kirim gambar</li>
                                <li><code>/send-video</code> - Kirim video</li>
                                <li><code>/send-document</code> - Kirim dokumen</li>
                                <li><code>/send-audio</code> - Kirim audio</li>
                                <li><code>/send-button</code> - Kirim button</li>
                                <li><code>/message/{id}</code> - Status pesan</li>
                                <li><code>/device</code> - Status device (mungkin tidak tersedia)</li>
                            </ul>
                            
                            <hr>
                            
                            <h6>Parameter Umum:</h6>
                            <ul class="list-unstyled">
                                <li><code>target</code> - Nomor telepon</li>
                                <li><code>message</code> - Isi pesan</li>
                                <li><code>delay</code> - Delay pengiriman</li>
                                <li><code>countryCode</code> - Kode negara</li>
                            </ul>
                            
                            <hr>
                            
                            <h6>Troubleshooting:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Pastikan API Key valid dari dashboard Fonnte</li>
                                <li><i class="fas fa-check text-success"></i> URL API harus: <code>https://api.fonnte.com</code></li>
                                <li><i class="fas fa-check text-success"></i> Device WhatsApp harus online</li>
                                <li><i class="fas fa-check text-success"></i> Periksa koneksi internet</li>
                                <li><i class="fas fa-exclamation-triangle text-warning"></i> Jika "Method Not Allowed": Coba kirim pesan test untuk verifikasi koneksi</li>
                                <li><i class="fas fa-exclamation-triangle text-warning"></i> Jika "401 Unauthorized": Periksa API Key dan izin akses</li>
                                <li><i class="fas fa-exclamation-triangle text-warning"></i> Jika "404 Not Found": Periksa URL API dan endpoint</li>
                            </ul>
                            
                            <hr>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tips:</strong> Pastikan device WhatsApp sudah terhubung dan online sebelum menggunakan API.
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Penting:</strong> Jika menerima error HTML, pastikan URL API benar dan bukan URL web interface Fonnte.
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Catatan:</strong> Endpoint /device mungkin tidak tersedia di semua versi API Fonnte. Jika test koneksi gagal, coba test dengan mengirim pesan untuk memverifikasi koneksi.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../../templates/footer.php'; ?>
</div>

<?php include __DIR__ . '/../../templates/scripts.php'; ?>

<script>
// Show/hide API key and webhook secret
document.addEventListener('DOMContentLoaded', function() {
    const apiKeyInput = document.getElementById('api_key');
    const webhookSecretInput = document.getElementById('webhook_secret');
    
    // API Key toggle
    const apiKeyToggleBtn = document.createElement('button');
    apiKeyToggleBtn.type = 'button';
    apiKeyToggleBtn.className = 'btn btn-sm btn-outline-secondary mt-1';
    apiKeyToggleBtn.innerHTML = '<i class="fas fa-eye"></i> Tampilkan';
    
    apiKeyToggleBtn.addEventListener('click', function() {
        if (apiKeyInput.type === 'password') {
            apiKeyInput.type = 'text';
            apiKeyToggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan';
        } else {
            apiKeyInput.type = 'password';
            apiKeyToggleBtn.innerHTML = '<i class="fas fa-eye"></i> Tampilkan';
        }
    });
    
    apiKeyInput.parentNode.appendChild(apiKeyToggleBtn);
    
    // Webhook Secret toggle
    const webhookToggleBtn = document.createElement('button');
    webhookToggleBtn.type = 'button';
    webhookToggleBtn.className = 'btn btn-sm btn-outline-secondary mt-1';
    webhookToggleBtn.innerHTML = '<i class="fas fa-eye"></i> Tampilkan';
    
    webhookToggleBtn.addEventListener('click', function() {
        if (webhookSecretInput.type === 'password') {
            webhookSecretInput.type = 'text';
            webhookToggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan';
        } else {
            webhookSecretInput.type = 'password';
            webhookToggleBtn.innerHTML = '<i class="fas fa-eye"></i> Tampilkan';
        }
    });
    
    webhookSecretInput.parentNode.appendChild(webhookToggleBtn);
});
</script>

</body>
</html>