<?php
require_once __DIR__ . '/db.php';

class WhatsAppService {
    private $conn;
    private $config;
    private $lastError = '';
    
    public function __construct($conn = null) {
        $this->conn = $conn;
        $this->loadConfig();
    }
    
    private function loadConfig() {
        $sql = "SELECT * FROM whatsapp_config LIMIT 1";
        if (!$this->conn) throw new Exception('Database connection is not set');
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception('Database error: ' . $this->conn->errorInfo()[2]);
        if (!$stmt->execute()) throw new Exception('Database error: ' . $stmt->errorInfo()[2]);
        
        $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$this->config) throw new Exception('WhatsApp configuration not found');
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Log automation event
     */
    public function logAutomationEvent($user_id, $user_type, $attendance_status, $notification_type, $recipient_phone, $recipient_type, $template_used = null, $message_sent = false, $error_message = null, $attendance_date = null) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO whatsapp_automation_logs (user_id, user_type, attendance_status, notification_type, recipient_phone, recipient_type, template_used, message_sent, error_message, attendance_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $user_id,
                $user_type,
                $attendance_status,
                $notification_type,
                $recipient_phone,
                $recipient_type,
                $template_used,
                $message_sent ? 1 : 0,
                $error_message,
                $attendance_date ?: date('Y-m-d')
            ]);
            
            return $result ? $this->conn->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Automation log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Format phone number to international format
     */
    public function formatPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If number starts with 0, replace with 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // If number doesn't start with country code, add it
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }
    
    private function logMessage($data) {
        // Initialize default values with proper type handling
        $phone = isset($data['phone_number']) ? (is_array($data['phone_number']) ? json_encode($data['phone_number']) : (string)$data['phone_number']) : null;
        $message = isset($data['message']) ? (is_array($data['message']) ? json_encode($data['message']) : (string)$data['message']) : null;
        $messageId = isset($data['message_id']) ? (is_array($data['message_id']) ? json_encode($data['message_id']) : (string)$data['message_id']) : null;
        $messageType = isset($data['message_type']) ? (is_array($data['message_type']) ? json_encode($data['message_type']) : (string)$data['message_type']) : 'text';
        $templateName = isset($data['template_name']) ? (is_array($data['template_name']) ? json_encode($data['template_name']) : (string)$data['template_name']) : null;
        $status = isset($data['status']) ? (is_array($data['status']) ? json_encode($data['status']) : (string)$data['status']) : 'pending';
        $statusDetail = isset($data['status_detail']) ? (is_array($data['status_detail']) ? json_encode($data['status_detail']) : (string)$data['status_detail']) : null;
        
        // Handle response data - ensure it's always a string
        $response = null;
        if (isset($data['response'])) {
            if (is_array($data['response'])) {
                $response = json_encode($data['response']);
            } elseif (is_string($data['response'])) {
                $response = $data['response'];
            } else {
                $response = (string)$data['response'];
            }
        }
        
        $sql = "INSERT INTO whatsapp_logs (
            phone_number, message, message_id, message_type, 
            template_name, status, status_detail, response, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $this->conn->errorInfo()[2]);
        }
        
        $success = $stmt->execute([
            $phone,
            $message,
            $messageId,
            $messageType,
            $templateName,
            $status,
            $statusDetail,
            $response
        ]);
        
        if (!$success) {
            throw new Exception('Database error: ' . $stmt->errorInfo()[2]);
        }
        return $this->conn->lastInsertId();
    }
    
    private function updateLog($logId, $data) {
        $updates = [];
        $params = [];
        
        foreach (['status', 'status_detail', 'response', 'message_id', 'sent_at', 'delivered_at', 'read_at', 'retry_count'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = ?";
                
                // Handle all fields to ensure they're always strings
                if (is_array($data[$field])) {
                    $params[] = json_encode($data[$field]);
                } elseif (is_null($data[$field])) {
                    $params[] = null;
                } else {
                    $params[] = (string)$data[$field];
                }
            }
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE whatsapp_logs SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $logId;
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception('Database error: ' . $this->conn->errorInfo()[2]);
        
        return $stmt->execute($params);
    }
    
    private function makeApiRequest($endpoint, $data, $method = 'POST') {
        $url = rtrim($this->config['api_url'], '/') . '/' . ltrim($endpoint, '/');
        $headers = array(
            'Authorization: ' . $this->config['api_key'],
            'Content-Type: application/json'
        );
        
        $curl = curl_init();
        if ($curl === false) {
            throw new Exception('Failed to initialize cURL');
        }
        
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        );
        
        if ($method === 'POST' && !empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        // Check if response is HTML instead of JSON
        if (strpos($contentType, 'text/html') !== false || strpos($response, '<!DOCTYPE html>') !== false || strpos($response, '<html') !== false) {
            throw new Exception('Received HTML response instead of JSON. Please check your API URL and credentials. Response: ' . substr($response, 0, 500));
        }
        
        // Try to decode JSON
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If it's not valid JSON, provide a more helpful error message
            $errorMsg = 'Invalid JSON response';
            if (strlen($response) > 200) {
                $errorMsg .= ': ' . substr($response, 0, 200) . '...';
            } else {
                $errorMsg .= ': ' . $response;
            }
            throw new Exception($errorMsg);
        }
        
        // Check for API errors with specific handling for Method Not Allowed
        if ($httpCode >= 400) {
            $errorMsg = isset($result['message']) ? $result['message'] : 'Unknown API error';
            
            // Handle specific error cases
            if ($httpCode === 405) {
                throw new Exception('Method Not Allowed: Endpoint ' . $endpoint . ' does not support ' . $method . ' method. Please check the API documentation.');
            } elseif ($httpCode === 401) {
                throw new Exception('Unauthorized: API Key is invalid or expired. Please check your API credentials.');
            } elseif ($httpCode === 404) {
                throw new Exception('Not Found: Endpoint ' . $endpoint . ' does not exist. Please check the API URL and endpoint.');
            } else {
                throw new Exception('API Error (' . $httpCode . '): ' . $errorMsg);
            }
        }
        
        return $result;
    }
    
    /**
     * Send single text message
     * Based on: https://fonnte.com/tutorial/mengirim-pesan-whatsapp-php-api/#single
     */
    public function sendText($phone, $message, $options = array()) {
        try {
            $phone = $this->formatPhoneNumber($phone);
            
            $logId = $this->logMessage(array(
                'phone_number' => $phone,
                'message' => $message,
                'message_type' => 'text',
                'status' => 'sending'
            ));
            
            // Prepare data according to Fonnte documentation
            $data = array(
                'target' => $phone,
                'message' => $message
            );
            
            // Add optional parameters
            if (isset($options['delay'])) {
                $data['delay'] = $options['delay'];
            } elseif (!empty($this->config['delay'])) {
                $data['delay'] = $this->config['delay'];
            }
            
            if (isset($options['countryCode'])) {
                $data['countryCode'] = $options['countryCode'];
            } elseif (!empty($this->config['country_code'])) {
                $data['countryCode'] = $this->config['country_code'];
            }
            
            $response = $this->makeApiRequest('send', $data);
            
            $this->updateLog($logId, array(
                'status' => (isset($response['status']) && $response['status']) ? 'sent' : 'failed',
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response,
                'sent_at' => date('Y-m-d H:i:s')
            ));
            
            return array(
                'success' => isset($response['status']) ? $response['status'] : false,
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response
            );
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if (isset($logId)) {
                $this->updateLog($logId, array(
                    'status' => 'error',
                    'status_detail' => $this->lastError
                ));
            }
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Send bulk messages
     * Based on: https://fonnte.com/tutorial/mengirim-pesan-whatsapp-php-api/#bulk
     */
    public function sendBulk($targets, $message, $options = array()) {
        try {
            // Format all phone numbers
            $formattedTargets = array();
            foreach ($targets as $phone) {
                $formattedTargets[] = $this->formatPhoneNumber($phone);
            }
            
            $logId = $this->logMessage(array(
                'phone_number' => implode(',', $formattedTargets),
                'message' => $message,
                'message_type' => 'bulk_text',
                'status' => 'sending'
            ));
            
            // Prepare data according to Fonnte documentation
            $data = array(
                'target' => $formattedTargets,
                'message' => $message
            );
            
            // Add optional parameters
            if (isset($options['delay'])) {
                $data['delay'] = $options['delay'];
            } elseif (!empty($this->config['delay'])) {
                $data['delay'] = $this->config['delay'];
            }
            
            if (isset($options['countryCode'])) {
                $data['countryCode'] = $options['countryCode'];
            } elseif (!empty($this->config['country_code'])) {
                $data['countryCode'] = $this->config['country_code'];
            }
            
            $response = $this->makeApiRequest('send', $data);
            
            $this->updateLog($logId, array(
                'status' => (isset($response['status']) && $response['status']) ? 'sent' : 'failed',
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response,
                'sent_at' => date('Y-m-d H:i:s')
            ));
            
            return array(
                'success' => isset($response['status']) ? $response['status'] : false,
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response
            );
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if (isset($logId)) {
                $this->updateLog($logId, array(
                    'status' => 'error',
                    'status_detail' => $this->lastError
                ));
            }
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Send dynamic message with variables
     * Based on: https://fonnte.com/tutorial/mengirim-pesan-whatsapp-php-api/#dynamic
     */
    public function sendDynamic($phone, $message, $variables, $options = array()) {
        try {
            $phone = $this->formatPhoneNumber($phone);
            
            $logId = $this->logMessage(array(
                'phone_number' => $phone,
                'message' => $message,
                'message_type' => 'dynamic_text',
                'status' => 'sending'
            ));
            
            // Prepare data according to Fonnte documentation
            $data = array(
                'target' => $phone,
                'message' => $message,
                'variables' => $variables
            );
            
            // Add optional parameters
            if (isset($options['delay'])) {
                $data['delay'] = $options['delay'];
            } elseif (!empty($this->config['delay'])) {
                $data['delay'] = $this->config['delay'];
            }
            
            if (isset($options['countryCode'])) {
                $data['countryCode'] = $options['countryCode'];
            } elseif (!empty($this->config['country_code'])) {
                $data['countryCode'] = $this->config['country_code'];
            }
            
            $response = $this->makeApiRequest('send', $data);
            
            $this->updateLog($logId, array(
                'status' => (isset($response['status']) && $response['status']) ? 'sent' : 'failed',
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response,
                'sent_at' => date('Y-m-d H:i:s')
            ));
            
            return array(
                'success' => isset($response['status']) ? $response['status'] : false,
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response
            );
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if (isset($logId)) {
                $this->updateLog($logId, array(
                    'status' => 'error',
                    'status_detail' => $this->lastError
                ));
            }
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Send template message
     * Based on: https://fonnte.com/tutorial/mengirim-pesan-whatsapp-php-api/#template
     */
    public function sendTemplate($phone, $templateName, $templateVars = array(), $language = null) {
        try {
            $phone = $this->formatPhoneNumber($phone);
            $logId = $this->logMessage(array(
                'phone_number' => $phone,
                'message' => $templateName,
                'message_type' => 'template',
                'template_name' => $templateName,
                'status' => 'sending'
            ));
            // Fonnte: Template message dikirim ke endpoint 'send' dengan parameter 'template', 'language', dan 'data' (atau 'variables')
            $data = array(
                'target' => $phone,
                'template' => $templateName
            );
            if ($language) {
                $data['language'] = $language;
            }
            // Add template variables if provided
            if (!empty($templateVars)) {
                $data['data'] = $templateVars; // Fonnte expects 'data' for template variables
            }
            $response = $this->makeApiRequest('send', $data);
            $this->updateLog($logId, array(
                'status' => (isset($response['status']) && $response['status']) ? 'sent' : 'failed',
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response,
                'sent_at' => date('Y-m-d H:i:s')
            ));
            return array(
                'success' => isset($response['status']) ? $response['status'] : false,
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response
            );
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if (isset($logId)) {
                $this->updateLog($logId, array(
                    'status' => 'error',
                    'status_detail' => $this->lastError
                ));
            }
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Send media message (image, video, document, audio)
     */
    public function sendMedia($phone, $mediaUrl, $caption = '', $type = 'image', $options = array()) {
        try {
            $phone = $this->formatPhoneNumber($phone);
            
            $logId = $this->logMessage(array(
                'phone_number' => $phone,
                'message' => $caption,
                'message_type' => 'media_' . $type,
                'status' => 'sending'
            ));
            
            // Prepare data according to Fonnte documentation
            $data = array(
                'target' => $phone,
                'url' => $mediaUrl
            );
            
            // Add caption if provided
            if (!empty($caption)) {
                $data['caption'] = $caption;
            }
            
            // Add optional parameters
            if (isset($options['delay'])) {
                $data['delay'] = $options['delay'];
            } elseif (!empty($this->config['delay'])) {
                $data['delay'] = $this->config['delay'];
            }
            
            if (isset($options['countryCode'])) {
                $data['countryCode'] = $options['countryCode'];
            } elseif (!empty($this->config['country_code'])) {
                $data['countryCode'] = $this->config['country_code'];
            }
            
            // Use appropriate endpoint based on media type
            $endpoint = 'send-' . $type;
            $response = $this->makeApiRequest($endpoint, $data);
            
            $this->updateLog($logId, array(
                'status' => (isset($response['status']) && $response['status']) ? 'sent' : 'failed',
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response,
                'sent_at' => date('Y-m-d H:i:s')
            ));
            
            return array(
                'success' => isset($response['status']) ? $response['status'] : false,
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response
            );
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if (isset($logId)) {
                $this->updateLog($logId, array(
                    'status' => 'error',
                    'status_detail' => $this->lastError
                ));
            }
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Send button message
     */
    public function sendButtons($phone, $message, $buttons, $footer = null) {
        try {
            $phone = $this->formatPhoneNumber($phone);
            
            $logId = $this->logMessage(array(
                'phone_number' => $phone,
                'message' => $message,
                'message_type' => 'buttons',
                'status' => 'sending'
            ));
            
            // Prepare data according to Fonnte documentation
            $data = array(
                'target' => $phone,
                'message' => $message,
                'buttons' => $buttons
            );
            
            // Add footer if provided
            if (!empty($footer)) {
                $data['footer'] = $footer;
            }
            
            $response = $this->makeApiRequest('send-button', $data);
            
            $this->updateLog($logId, array(
                'status' => (isset($response['status']) && $response['status']) ? 'sent' : 'failed',
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response,
                'sent_at' => date('Y-m-d H:i:s')
            ));
            
            return array(
                'success' => isset($response['status']) ? $response['status'] : false,
                'message_id' => isset($response['id']) ? $response['id'] : null,
                'response' => $response
            );
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            if (isset($logId)) {
                $this->updateLog($logId, array(
                    'status' => 'error',
                    'status_detail' => $this->lastError
                ));
            }
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Get device status - Updated to use a more reliable approach
     * Since /device endpoint might not be available, we'll test with a simple connection test
     */
    public function getDeviceStatus() {
        try {
            // Instead of using /device endpoint which might not be available,
            // we'll test the connection by making a simple request to verify API is working
            $testData = array(
                'target' => '6281234567890', // Test number
                'message' => 'Connection test - ' . date('Y-m-d H:i:s')
            );
            
            // Try to make a test request to verify API connection
            $response = $this->makeApiRequest('send', $testData, 'POST');
            
            // If we get here, the API is working
            return array(
                'success' => true,
                'data' => array(
                    'status' => 'connected',
                    'message' => 'API connection successful',
                    'timestamp' => date('Y-m-d H:i:s')
                )
            );
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Alternative method to check if API is working without using device endpoint
     */
    public function checkApiConnection() {
        try {
            // Make a simple test request to verify API is accessible
            $testData = array(
                'target' => '6281234567890', // Test number
                'message' => 'Connection test - ' . date('Y-m-d H:i:s')
            );
            
            // Try to make a test request to verify API connection
            $response = $this->makeApiRequest('send', $testData, 'POST');
            
            // If we get here, the API is working
            return array(
                'success' => true,
                'data' => array(
                    'status' => 'connected',
                    'message' => 'API connection successful',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'note' => 'Device status check via send endpoint'
                )
            );
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Get message status
     */
    public function getMessageStatus($messageId) {
        try {
            $response = $this->makeApiRequest('message/' . $messageId, array(), 'GET');
            return array(
                'success' => true,
                'data' => $response
            );
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return array('success' => false, 'error' => $this->lastError);
        }
    }
    
    /**
     * Get message templates from database
     */
    public function getTemplates($status = 'APPROVED', $isActive = 1) {
        try {
            $sql = "SELECT * FROM whatsapp_message_templates WHERE status = ? AND is_active = ? ORDER BY display_name";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) throw new Exception('Database error: ' . $this->conn->errorInfo()[2]);
            
            $stmt->execute([$status, $isActive]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return array();
        }
    }
    
    /**
     * Get recent logs
     */
    public function getRecentLogs($limit = 50) {
        try {
            $sql = "SELECT * FROM whatsapp_logs ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) throw new Exception('Database error: ' . $this->conn->errorInfo()[2]);
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return array();
        }
    }
    
    /**
     * Get statistics
     */
    public function getStats($days = 30) {
        try {
            $sql = "SELECT 
                        DATE(created_at) as date,
                        message_type,
                        status,
                        COUNT(*) as total_messages,
                        COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_count,
                        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
                    FROM whatsapp_logs 
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    GROUP BY DATE(created_at), message_type, status
                    ORDER BY date DESC";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) throw new Exception('Database error: ' . $this->conn->errorInfo()[2]);
            
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return array();
        }
    }

    /**
     * Test method for service functionality
     * This method is used for testing purposes only
     */
    public function testService() {
        $results = array();
        
        // Test configuration loading
        $config = $this->getConfig();
        $results['config'] = !empty($config);
        
        // Test phone number formatting
        $testPhone = '081234567890';
        $formattedPhone = $this->formatPhoneNumber($testPhone);
        $results['phone_formatting'] = strpos($formattedPhone, '62') === 0;
        
        // Test API credentials
        $results['api_configured'] = !empty($config['api_key']) && !empty($config['api_url']);
        
        // Test database connection
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) FROM whatsapp_logs LIMIT 1");
            $results['database'] = true;
        } catch (Exception $e) {
            $results['database'] = false;
        }
        
        return $results;
    }
}

/**
 * Helper function to get message template with variables
 */
function getMessageTemplate($template_name, $data = array()) {
    $templates = array(
        'absensi_berhasil' => array(
            'name' => 'Absensi Berhasil',
            'language' => 'id',
            'variables' => array('nama', 'tanggal', 'waktu', 'status')
        ),
        'absensi_telat' => array(
            'name' => 'Absensi Telat',
            'language' => 'id',
            'variables' => array('nama', 'tanggal', 'waktu', 'keterangan')
        ),
        'notifikasi_sistem' => array(
            'name' => 'Notifikasi Sistem',
            'language' => 'id',
            'variables' => array('pesan', 'waktu')
        ),
        'pemberitahuan_keterlambatan' => array(
            'name' => 'Pemberitahuan Keterlambatan',
            'language' => 'id',
            'variables' => array('nama', 'tanggal', 'waktu')
        ),
        'pemberitahuan_ketidakhadiran' => array(
            'name' => 'Pemberitahuan Ketidakhadiran',
            'language' => 'id',
            'variables' => array('nama', 'tanggal', 'status')
        )
    );
    
    return isset($templates[$template_name]) ? $templates[$template_name] : null;
}