<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "Test Koneksi WhatsApp";
$active_page = "whatsapp_test";
include '../../templates/header.php';
include '../../templates/sidebar.php';

// Koneksi ke database
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/wa_util.php';

// Initialize WhatsAppService
$waService = new WhatsAppService($conn);

// Ambil konfigurasi WhatsApp menggunakan WhatsAppService
$config = $waService->getConfig();
if (empty($config)) {
    $config = array(
        'api_key' => '',
        'api_url' => 'https://api.fonnte.com',
        'country_code' => '62',
    );
}

/**
 * Menyembunyikan informasi sensitif dalam respons API
 * 
 * @param string $jsonString Respons API dalam format JSON string
 * @return string JSON string yang telah dimodifikasi
 */
function maskSensitiveInfo($jsonString) {
    // Jika bukan JSON valid, kembalikan string asli
    $data = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Jika bukan JSON, coba sembunyikan informasi sensitif dalam string biasa
        $patterns = [
             '/(["|\']token["|\']\s*:\s*["|\'])(.*?)(["|\'])/i',
             '/(["|\']key["|\']\s*:\s*["|\'])(.*?)(["|\'])/i',
             '/(["|\']api_key["|\']\s*:\s*["|\'])(.*?)(["|\'])/i',
             '/(["|\']auth["|\']\s*:\s*["|\'])(.*?)(["|\'])/i',
             '/(["|\']password["|\']\s*:\s*["|\'])(.*?)(["|\'])/i',
             '/(["|\']secret["|\']\s*:\s*["|\'])(.*?)(["|\'])/i',
             '/([&?]token=)([^&"]+)/i',
             '/([&?]key=)([^&"]+)/i',
             '/([&?]api_key=)([^&"]+)/i',
             '/([&?]auth=)([^&"]+)/i',
             '/([&?]password=)([^&"]+)/i',
             '/([&?]secret=)([^&"]+)/i'
        ];
        
        $replacements = [
            '$1****TOKEN_HIDDEN****$3',
            '$1****KEY_HIDDEN****$3',
            '$1****API_KEY_HIDDEN****$3',
            '$1****AUTH_HIDDEN****$3',
            '$1****PASSWORD_HIDDEN****$3',
            '$1****SECRET_HIDDEN****$3',
            '$1****TOKEN_HIDDEN****',
            '$1****KEY_HIDDEN****',
            '$1****API_KEY_HIDDEN****',
            '$1****AUTH_HIDDEN****',
            '$1****PASSWORD_HIDDEN****',
            '$1****SECRET_HIDDEN****'
        ];
        
        return preg_replace($patterns, $replacements, $jsonString);
    }
    
    // Jika JSON valid, proses secara rekursif
    $maskedData = maskSensitiveInfoRecursive($data);
    return json_encode($maskedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Fungsi rekursif untuk menyembunyikan informasi sensitif dalam array/objek
 * 
 * @param array $data Data yang akan diproses
 * @return array Data yang telah dimodifikasi
 */
function maskSensitiveInfoRecursive($data) {
    if (!is_array($data)) {
        return $data;
    }
    
    $sensitiveKeys = ['token', 'key', 'api_key', 'auth', 'password', 'secret', 'apikey'];
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = maskSensitiveInfoRecursive($value);
        } else if (is_string($value) && in_array(strtolower($key), $sensitiveKeys)) {
            // Mask sensitive values but keep first and last 4 chars if long enough
            if (strlen($value) > 8) {
                $data[$key] = substr($value, 0, 4) . '****' . substr($value, -4);
            } else {
                $data[$key] = '********';
            }
        }
    }
    
    return $data;
}

$message = '';
$status = '';

// Handle form submission untuk test pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi server-side
    $errors = [];
    
    // Validasi nomor telepon
    if (empty($_POST['test_number'])) {
        $errors[] = 'Nomor telepon tidak boleh kosong';
    } elseif (!preg_match('/^[0-9]{9,15}$/', $_POST['test_number'])) {
        $errors[] = 'Format nomor telepon tidak valid (harus 9-15 digit angka)';
    }
    
    // Validasi pesan
    if (empty($_POST['test_message'])) {
        $errors[] = 'Pesan tidak boleh kosong';
    } elseif (strlen($_POST['test_message']) > 1000) {
        $errors[] = 'Pesan tidak boleh lebih dari 1000 karakter';
    }
    
    // Jika tidak ada error, proses pengiriman
    if (empty($errors)) {
        // Pastikan nomor telepon hanya berisi angka
        $test_number = preg_replace('/[^0-9]/', '', $_POST['test_number']);
        $test_message = $_POST['test_message'];
        
        // Tambahkan kode negara jika belum ada
        $country_code = $config['country_code'] ? $config['country_code'] : '62';
        if (substr($test_number, 0, strlen($country_code)) !== $country_code) {
            $test_number = $country_code . $test_number;
        }
        
        // Log attempt dengan nomor yang sudah divalidasi
        error_log("Attempting to send WhatsApp message to: {$test_number}");
        
        try {
            // Rate limiting - cek apakah sudah mengirim pesan dalam 30 detik terakhir
            $canSend = true;
            $lastSentTime = isset($_SESSION['last_wa_test_sent']) ? $_SESSION['last_wa_test_sent'] : 0;
            $currentTime = time();
            
            if (($currentTime - $lastSentTime) < 30) {
                $canSend = false;
                $status = 'error';
                $message = 'Mohon tunggu minimal 30 detik sebelum mengirim pesan test lagi.';
            }
            
            if ($canSend) {
                try {
                    // Use the new WhatsAppService
                    $result = $waService->sendText($test_number, $test_message);
                    $_SESSION['last_wa_test_sent'] = $currentTime; // Update waktu pengiriman terakhir
                    
                    if ($result['success']) {
                        $status = 'success';
                        $messageId = isset($result['message_id']) ? $result['message_id'] : 'N/A';
                        $message = 'Pesan berhasil dikirim! Message ID: ' . (is_array($messageId) ? json_encode($messageId) : $messageId);
                        error_log("WhatsApp message sent successfully to: {$test_number}");
                    } else {
                        $status = 'error';
                        $errorMsg = 'Unknown error';
                        if (isset($result['error'])) {
                            if (is_array($result['error'])) {
                                $errorMsg = json_encode($result['error']);
                            } elseif (is_string($result['error'])) {
                                $errorMsg = $result['error'];
                            } else {
                                $errorMsg = (string)$result['error'];
                            }
                        }
                        $message = 'Gagal mengirim pesan: ' . $errorMsg;
                        error_log("Failed to send WhatsApp message to: {$test_number}. Error: {$errorMsg}");
                    }
                } catch (Exception $e) {
                    $status = 'error';
                    $message = 'Error: ' . $e->getMessage();
                    error_log("Exception when sending WhatsApp message: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            $status = 'error';
            $message = 'Error: ' . $e->getMessage();
            error_log("Exception when sending WhatsApp message: " . $e->getMessage());
        }
    } else {
        // Tampilkan error validasi
        $status = 'error';
        $message = 'Validasi gagal: ' . implode(', ', $errors);
    }
}
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- Begin Alert SB Admin 2 -->
            <?php if (!empty($message)): 
                // Prepare alert class based on status
                $alert_class = $status === 'success' ? 'alert-success' : 'alert-danger';
                
                // Mask sensitive information in the message
                $displayMessage = $message;
                
                // Check if message contains API response JSON
                if (strpos($message, 'Response:') !== false) {
                    $parts = explode('Response:', $message, 2);
                    $responseText = trim($parts[1]);
                    
                    // Try to parse JSON response
                    $jsonData = json_decode($responseText, true);
                    if ($jsonData) {
                        // Mask any potential sensitive data in the response
                        $jsonData = maskSensitiveInfoRecursive($jsonData);
                        
                        // Rebuild the message with masked JSON
                        $displayMessage = $parts[0] . 'Response: ' . json_encode($jsonData, JSON_PRETTY_PRINT);
                    }
                }
            ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo nl2br(htmlspecialchars($displayMessage)); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <!-- End Alert SB Admin 2 -->
            
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Test Koneksi WhatsApp</h1>
            </div>

            <div class="row">
                <!-- Konfigurasi Saat Ini -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Konfigurasi Saat Ini</h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                    <a class="dropdown-item" href="config.php"><i class="fas fa-cog fa-sm fa-fw mr-2 text-gray-400"></i>Ubah Konfigurasi</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" id="refreshConfig"><i class="fas fa-sync-alt fa-sm fa-fw mr-2 text-gray-400"></i>Refresh</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <tbody>
                                        <tr>
                                            <th class="w-25">API Key</th>
                                            <td>
                                                <?php if (!empty($config['api_key'])): ?>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-monospace">
                                                            <?php 
                                                                // Mask API key for security (show only first 4 and last 4 characters)
                                                                $apiKey = $config['api_key'];
                                                                $length = strlen($apiKey);
                                                                if ($length > 8) {
                                                                    echo substr($apiKey, 0, 4) . str_repeat('*', $length - 8) . substr($apiKey, -4);
                                                                } else {
                                                                    echo str_repeat('*', $length);
                                                                }
                                                            ?>
                                                        </span>
                                                        <button class="btn btn-sm btn-outline-secondary btn-copy" data-clipboard-text="<?php echo htmlspecialchars($apiKey); ?>" title="Salin ke Clipboard">
                                                            <i class="far fa-copy"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum dikonfigurasi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>API URL</th>
                                            <td>
                                                <?php if (!empty($config['api_url'])): ?>
                                                    <?php 
                                                        // Mask API URL if it contains tokens or sensitive information
                                                        $apiUrl = $config['api_url'];
                                                        // Check if URL contains query parameters that might have tokens
                                                        if (strpos($apiUrl, '?') !== false) {
                                                            $urlParts = explode('?', $apiUrl, 2);
                                                            $baseUrl = $urlParts[0];
                                                            // Show base URL and indicate parameters are hidden
                                                            echo htmlspecialchars($baseUrl) . ' <span class="text-muted"><small><i>(parameter disembunyikan untuk keamanan)</i></small></span>';
                                                        } else {
                                                            echo htmlspecialchars($apiUrl);
                                                        }
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum dikonfigurasi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Kode Negara</th>
                                            <td>
                                                <?php if (!empty($config['country_code'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="font-weight-bold mr-2">+<?php echo htmlspecialchars($config['country_code']); ?></span>
                                                        <span class="text-muted">
                                                            <small>(contoh: +<?php echo htmlspecialchars($config['country_code']); ?>8123456789)</small>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum dikonfigurasi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                        
                <!-- Test Pengiriman Pesan -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Test Pengiriman Pesan</h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownTestMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownTestMenu">
                                    <a class="dropdown-item" href="monitoring.php">
                                        <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>Lihat Riwayat
                                    </a>
                                    <a class="dropdown-item" href="templates.php">
                                        <i class="fas fa-envelope fa-sm fa-fw mr-2 text-gray-400"></i>Kelola Template
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" onsubmit="return validateForm()">
                                <div class="form-group">
                                    <label for="test_number" class="font-weight-bold text-gray-700">
                                        Nomor WhatsApp Tujuan
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">+<?php echo htmlspecialchars($config['country_code'] ? $config['country_code'] : '62'); ?></span>
                                        </div>
                                        <input type="text" 
                                               class="form-control <?php echo isset($errors['test_number']) ? 'is-invalid' : ''; ?>" 
                                               id="test_number" 
                                               name="test_number" 
                                               placeholder="8123456789 (tanpa kode negara)" 
                                               pattern="[0-9]{9,15}" 
                                               title="Nomor telepon harus berisi 9-15 digit angka" 
                                               value="<?php echo isset($_POST['test_number']) ? htmlspecialchars($_POST['test_number']) : ''; ?>" 
                                               required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="clearNumber">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <?php if (isset($errors['test_number'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo htmlspecialchars($errors['test_number']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <small class="form-text text-muted mt-1">
                                        Masukkan nomor tanpa kode negara (contoh: 8123456789). Kode negara akan ditambahkan otomatis.
                                    </small>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <label for="test_message" class="font-weight-bold text-gray-700">
                                        Pesan Test
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control <?php echo isset($errors['test_message']) ? 'is-invalid' : ''; ?>" 
                                              id="test_message" 
                                              name="test_message" 
                                              rows="5" 
                                              placeholder="Masukkan pesan test" 
                                              maxlength="1000" 
                                              required><?php echo isset($_POST['test_message']) ? htmlspecialchars($_POST['test_message']) : 'Ini adalah pesan test dari Sistem Absensi Sekolah.'; ?></textarea>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="form-text text-muted">
                                            <span id="charCount">0</span>/1000 karakter
                                        </small>
                                        <small class="text-gray-600">
                                            <i class="fas fa-info-circle"></i> Gunakan variabel seperti {{nama}} untuk personalisasi
                                        </small>
                                    </div>
                                    <?php if (isset($errors['test_message'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo htmlspecialchars($errors['test_message']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-check mt-3">
                                    <input type="checkbox" class="form-check-input <?php echo isset($errors['confirmSend']) ? 'is-invalid' : ''; ?>" 
                                           id="confirmSend" 
                                           name="confirmSend" 
                                           <?php echo isset($_POST['confirmSend']) ? 'checked' : ''; ?> 
                                           required>
                                    <label class="form-check-label" for="confirmSend">
                                        Saya konfirmasi untuk mengirim pesan test ini
                                    </label>
                                    <?php if (isset($errors['confirmSend'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo htmlspecialchars($errors['confirmSend']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" id="resetForm">
                                        <i class="fas fa-undo fa-sm fa-fw mr-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="sendButton">
                                        <i class="fas fa-paper-plane fa-sm fa-fw mr-1"></i> Kirim Pesan Test
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
            <?php include __DIR__ . '/../../templates/footer.php'; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../../templates/scripts.php'; ?>
    
    <!-- Toastr for notifications -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- Clipboard.js for copy to clipboard -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
    
    <script>
        // Initialize clipboard.js
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize clipboard.js
            new ClipboardJS('.btn-copy');
            
            // Show tooltip on copy
            $('.btn-copy').tooltip({
                trigger: 'click',
                placement: 'top',
                title: 'Tersalin!'
            });
            
            // Hide tooltip after 1 second
            $('.btn-copy').on('shown.bs.tooltip', function() {
                setTimeout(function() {
                    $('.btn-copy').tooltip('hide');
                }, 1000);
            });
            
            // Initialize character counter
            const messageTextarea = document.getElementById('test_message');
            const charCountSpan = document.getElementById('charCount');
            
            // Initial count
            if (messageTextarea && charCountSpan) {
                charCountSpan.textContent = messageTextarea.value.length;
                updateCharCountColor(messageTextarea.value.length);
                
                // Update count on input
                messageTextarea.addEventListener('input', function() {
                    const length = this.value.length;
                    charCountSpan.textContent = length;
                    updateCharCountColor(length);
                });
            }
            
            // Format phone number input to remove non-numeric characters
            const phoneInput = document.getElementById('test_number');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    validatePhoneNumber(this);
                });
                
                // Validate on blur
                phoneInput.addEventListener('blur', function() {
                    validatePhoneNumber(this);
                });
            }
            
            // Clear number button
            const clearNumberBtn = document.getElementById('clearNumber');
            if (clearNumberBtn) {
                clearNumberBtn.addEventListener('click', function() {
                    if (phoneInput) {
                        phoneInput.value = '';
                        phoneInput.focus();
                    }
                });
            }
            
            // Reset form button
            const resetFormBtn = document.getElementById('resetForm');
            if (resetFormBtn) {
                resetFormBtn.addEventListener('click', function() {
                    if (confirm('Apakah Anda yakin ingin mereset form?')) {
                        document.querySelector('form').reset();
                        if (charCountSpan) charCountSpan.textContent = '0';
                        if (messageTextarea) messageTextarea.dispatchEvent(new Event('input'));
                    }
                });
            }
            
            // Refresh config button
            const refreshConfigBtn = document.getElementById('refreshConfig');
            if (refreshConfigBtn) {
                refreshConfigBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.reload();
                });
            }
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeTo(500, 0).slideUp(500, function(){
                    $(this).remove(); 
                });
            }, 5000);
        });
        
        // Update character count color based on length
        function updateCharCountColor(length) {
            const charCountSpan = document.getElementById('charCount');
            if (!charCountSpan) return;
            
            // Remove all color classes
            charCountSpan.classList.remove('text-success', 'text-warning', 'text-danger');
            
            // Add appropriate color class
            if (length === 0) {
                charCountSpan.classList.add('text-muted');
            } else if (length > 0 && length <= 900) {
                charCountSpan.classList.add('text-success');
            } else if (length > 900 && length <= 1000) {
                charCountSpan.classList.add('text-warning');
            } else {
                charCountSpan.classList.add('text-danger');
            }
        }
        
        // Validate phone number format
        function validatePhoneNumber(input) {
            const phonePattern = /^[0-9]{9,15}$/;
            const isValid = phonePattern.test(input.value);
            
            if (input.value && !isValid) {
                input.classList.add('is-invalid');
                return false;
            } else {
                input.classList.remove('is-invalid');
                return true;
            }
        }
        
        // Form validation
        function validateForm() {
            const phoneInput = document.getElementById('test_number');
            const messageInput = document.getElementById('test_message');
            const confirmCheckbox = document.getElementById('confirmSend');
            let isValid = true;
            
            // Reset all error states
            [phoneInput, messageInput, confirmCheckbox].forEach(el => {
                if (el) el.classList.remove('is-invalid');
            });
            
            // Validate phone number
            if (!validatePhoneNumber(phoneInput)) {
                showError('Nomor telepon harus berisi 9-15 digit angka tanpa spasi atau karakter khusus.');
                phoneInput.focus();
                isValid = false;
            }
            
            // Validate message
            if (!messageInput.value.trim()) {
                showError('Pesan tidak boleh kosong.');
                messageInput.classList.add('is-invalid');
                messageInput.focus();
                isValid = false;
            } else if (messageInput.value.length > 1000) {
                showError('Pesan tidak boleh lebih dari 1000 karakter.');
                messageInput.classList.add('is-invalid');
                messageInput.focus();
                isValid = false;
            }
            
            // Validate confirmation
            if (!confirmCheckbox.checked) {
                showError('Anda harus mengkonfirmasi pengiriman pesan test ini.');
                confirmCheckbox.classList.add('is-invalid');
                confirmCheckbox.focus();
                isValid = false;
            }
            
            // If form is valid, show confirmation
            if (isValid) {
                // Show loading state
                const sendButton = document.getElementById('sendButton');
                if (sendButton) {
                    sendButton.disabled = true;
                    sendButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';
                }
                
                return confirm('Apakah Anda yakin ingin mengirim pesan test ini?');
            }
            
            return false;
        }
        
        // Show error message using Toastr
        function showError(message) {
            toastr.error(message, 'Error', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 5000,
                extendedTimeOut: 2000
            });
        }
        
        // Initialize Toastr options
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "2000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    </script>
    
    <?php include __DIR__ . '/../../templates/footer.php'; ?>
</body>
</html>