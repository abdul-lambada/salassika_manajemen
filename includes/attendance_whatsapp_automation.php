<?php
/**
 * WhatsApp Automation for Attendance System
 * Automatically sends notifications for attendance events
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/wa_util.php';

class AttendanceWhatsAppAutomation {
    private $conn;
    private $waService;
    private $config;
    
    public function __construct($conn = null) {
        $globalConn = $conn;
        $this->conn = $conn ?: $globalConn;
        $this->waService = new WhatsAppService($this->conn);
        $this->loadConfig();
    }
    
    private function loadConfig() {
        // Load automation settings
        $stmt = $this->conn->prepare("
            SELECT * FROM whatsapp_automation_config 
            WHERE is_active = 1 
            LIMIT 1
        ");
        $stmt->execute();
        $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Default config if not found
        if (!$this->config) {
            $this->config = [
                'notify_late_arrival' => 1,
                'notify_absence' => 1,
                'notify_parents' => 1,
                'notify_admin' => 1,
                'late_threshold_minutes' => 15,
                'absence_check_time' => '09:00:00'
            ];
        }
    }
    
    /**
     * Process attendance and send WhatsApp notifications
     */
    public function processAttendanceNotifications($attendance_data) {
        try {
            $user_id = $attendance_data['user_id'];
            $status = $attendance_data['status_kehadiran'];
            $timestamp = $attendance_data['timestamp'];
            $user_type = $attendance_data['user_type']; // 'guru' or 'siswa'
            
            // Get user details
            $user_info = $this->getUserInfo($user_id, $user_type);
            if (!$user_info) {
                throw new Exception("User not found: $user_id");
            }
            
            // Send notifications based on status
            switch ($status) {
                case 'hadir':
                    $this->handleAttendancePresent($user_info, $timestamp);
                    break;
                case 'terlambat':
                    $this->handleAttendanceLate($user_info, $timestamp);
                    break;
                case 'tidak_hadir':
                    $this->handleAttendanceAbsent($user_info, $timestamp);
                    break;
                case 'izin':
                    $this->handleAttendancePermission($user_info, $timestamp);
                    break;
                case 'sakit':
                    $this->handleAttendanceSick($user_info, $timestamp);
                    break;
            }
            
            return ['success' => true, 'message' => 'Notifications sent successfully'];
            
        } catch (Exception $e) {
            error_log("WhatsApp Automation Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Handle present attendance notification
     */
    private function handleAttendancePresent($user_info, $timestamp) {
        $template_data = [
            'nama' => $user_info['nama'],
            'waktu' => date('H:i', strtotime($timestamp)),
            'tanggal' => date('d/m/Y', strtotime($timestamp)),
            'status' => 'HADIR'
        ];
        
        // Send to user (confirmation)
        if ($user_info['no_hp']) {
            $this->sendNotification(
                $user_info['no_hp'],
                'attendance_confirmation',
                $template_data
            );
        }
        
        // Send to parents (for students)
        if ($user_info['user_type'] === 'siswa' && $this->config['notify_parents']) {
            $this->notifyParents($user_info, 'attendance_present', $template_data);
        }
    }
    
    /**
     * Handle late attendance notification
     */
    private function handleAttendanceLate($user_info, $timestamp) {
        $late_minutes = $this->calculateLateMinutes($timestamp);
        
        $template_data = [
            'nama' => $user_info['nama'],
            'waktu' => date('H:i', strtotime($timestamp)),
            'tanggal' => date('d/m/Y', strtotime($timestamp)),
            'status' => 'TERLAMBAT',
            'menit_terlambat' => $late_minutes
        ];
        
        // Send to user
        if ($user_info['no_hp']) {
            $this->sendNotification(
                $user_info['no_hp'],
                'attendance_late',
                $template_data
            );
        }
        
        // Send to parents (for students)
        if ($user_info['user_type'] === 'siswa' && $this->config['notify_parents']) {
            $this->notifyParents($user_info, 'attendance_late', $template_data);
        }
        
        // Send to admin if late > threshold
        if ($late_minutes > $this->config['late_threshold_minutes'] && $this->config['notify_admin']) {
            $this->notifyAdmin($user_info, 'late_alert', $template_data);
        }
    }
    
    /**
     * Handle absent attendance notification
     */
    private function handleAttendanceAbsent($user_info, $timestamp) {
        $template_data = [
            'nama' => $user_info['nama'],
            'tanggal' => date('d/m/Y', strtotime($timestamp)),
            'status' => 'TIDAK HADIR'
        ];
        
        // Send to parents (for students)
        if ($user_info['user_type'] === 'siswa' && $this->config['notify_parents']) {
            $this->notifyParents($user_info, 'attendance_absent', $template_data);
        }
        
        // Send to admin
        if ($this->config['notify_admin']) {
            $this->notifyAdmin($user_info, 'absence_alert', $template_data);
        }
    }
    
    /**
     * Handle permission attendance notification
     */
    private function handleAttendancePermission($user_info, $timestamp) {
        $template_data = [
            'nama' => $user_info['nama'],
            'tanggal' => date('d/m/Y', strtotime($timestamp)),
            'status' => 'IZIN'
        ];
        
        // Send confirmation to user
        if ($user_info['no_hp']) {
            $this->sendNotification(
                $user_info['no_hp'],
                'attendance_permission_confirm',
                $template_data
            );
        }
        
        // Send to parents (for students)
        if ($user_info['user_type'] === 'siswa' && $this->config['notify_parents']) {
            $this->notifyParents($user_info, 'attendance_permission', $template_data);
        }
    }
    
    /**
     * Handle sick attendance notification
     */
    private function handleAttendanceSick($user_info, $timestamp) {
        $template_data = [
            'nama' => $user_info['nama'],
            'tanggal' => date('d/m/Y', strtotime($timestamp)),
            'status' => 'SAKIT'
        ];
        
        // Send confirmation to user
        if ($user_info['no_hp']) {
            $this->sendNotification(
                $user_info['no_hp'],
                'attendance_sick_confirm',
                $template_data
            );
        }
        
        // Send to parents (for students)
        if ($user_info['user_type'] === 'siswa' && $this->config['notify_parents']) {
            $this->notifyParents($user_info, 'attendance_sick', $template_data);
        }
    }
    
    /**
     * Send daily attendance summary
     */
    public function sendDailyAttendanceSummary($date = null) {
        $date = $date ?: date('Y-m-d');
        
        try {
            // Get attendance summary
            $summary = $this->getAttendanceSummary($date);
            
            // Send to admin
            $admin_phones = $this->getAdminPhones();
            foreach ($admin_phones as $phone) {
                $this->sendNotification(
                    $phone,
                    'daily_attendance_summary',
                    $summary
                );
            }
            
            return ['success' => true, 'summary' => $summary, 'message' => 'Daily summary sent'];
            
        } catch (Exception $e) {
            error_log("Daily Summary Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check for absent students and send notifications
     */
    public function checkAbsentStudents($date = null) {
        $date = $date ?: date('Y-m-d');
        $check_time = $this->config['absence_check_time'];
        
        try {
            // Get students who haven't checked in
            $stmt = $this->conn->prepare("
                SELECT s.*, u.name as nama, u.phone as no_hp, k.nama_kelas,
                       COALESCE(asis.status_kehadiran, 'tidak_hadir') as status
                FROM siswa s
                JOIN users u ON s.user_id = u.id
                JOIN kelas k ON s.id_kelas = k.id_kelas
                LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa 
                    AND asis.tanggal = ?
                WHERE asis.id_absensi_siswa IS NULL OR asis.status_kehadiran = 'Alfa'
            ");
            
            $stmt->execute([$date]);
            $absent_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($absent_students as $student) {
                $this->handleAttendanceAbsent([
                    'nama' => $student['nama'],
                    'no_hp' => $student['no_hp'],
                    'user_type' => 'siswa',
                    'kelas' => $student['nama_kelas'],
                    'no_hp_ortu' => null // Parent phone not available in current schema
                ], $date . ' ' . $check_time);
            }
            
            return ['success' => true, 'absent_count' => count($absent_students)];
            
        } catch (Exception $e) {
            error_log("Absent Check Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Send notification to parents
     */
    private function notifyParents($user_info, $template_name, $template_data) {
        // Check if parent phone number exists (handle missing column gracefully)
        $parent_phone = null;
        if (isset($user_info['no_hp_ortu']) && $user_info['no_hp_ortu']) {
            $parent_phone = $user_info['no_hp_ortu'];
        } elseif (isset($user_info['phone']) && $user_info['phone']) {
            // Fallback to student's phone number if parent phone is not available
            $parent_phone = $user_info['phone'];
        }
        
        if ($parent_phone) {
            $parent_data = array_merge($template_data, [
                'nama_anak' => $user_info['nama'],
                'kelas' => $user_info['kelas'] ? $user_info['kelas'] : '-'
            ]);
            
            $this->sendNotification(
                $parent_phone,
                $template_name . '_parent',
                $parent_data
            );
        }
    }
    
    /**
     * Send notification to admin
     */
    private function notifyAdmin($user_info, $template_name, $template_data) {
        $admin_phones = $this->getAdminPhones();
        
        $admin_data = array_merge($template_data, [
            'user_type' => $user_info['user_type'],
            'kelas' => $user_info['kelas'] ? $user_info['kelas'] : '-'
        ]);
        
        foreach ($admin_phones as $phone) {
            $this->sendNotification($phone, $template_name, $admin_data);
        }
    }
    
    /**
     * Send WhatsApp notification
     */
    private function sendNotification($phone, $template_name, $data) {
        try {
            $result = $this->waService->sendTemplateMessage($phone, $template_name, $data);
            
            if (!$result['success']) {
                error_log("WhatsApp send failed: " . $result['message']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("WhatsApp notification error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get user information
     */
    private function getUserInfo($user_id, $user_type) {
        if ($user_type === 'siswa') {
            $stmt = $this->conn->prepare("
                SELECT s.*, u.name as nama, u.phone as no_hp, k.nama_kelas as kelas, 
                       NULL as no_hp_ortu, 'siswa' as user_type
                FROM siswa s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
                WHERE s.user_id = ?
            ");
        } else {
            $stmt = $this->conn->prepare("
                SELECT g.*, u.name as nama, u.phone as no_hp, 'guru' as user_type
                FROM guru g
                JOIN users u ON g.user_id = u.id
                WHERE g.user_id = ?
            ");
        }
        
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate late minutes
     */
    private function calculateLateMinutes($timestamp) {
        $stmt = $this->conn->prepare("SELECT jam_masuk FROM tbl_jam_kerja WHERE id = 1");
        $stmt->execute();
        $jam_kerja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$jam_kerja) return 0;
        
        $jam_masuk = new DateTime(date('Y-m-d') . ' ' . $jam_kerja['jam_masuk']);
        $waktu_absensi = new DateTime($timestamp);
        
        if ($waktu_absensi > $jam_masuk) {
            $diff = $waktu_absensi->diff($jam_masuk);
            return ($diff->h * 60) + $diff->i;
        }
        
        return 0;
    }
    
    /**
     * Get attendance summary for a date
     */
    private function getAttendanceSummary($date) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'terlambat' THEN 1 END) as terlambat,
                COUNT(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'tidak_hadir' THEN 1 END) as tidak_hadir,
                COUNT(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'izin' THEN 1 END) as izin,
                COUNT(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'sakit' THEN 1 END) as sakit
            FROM users u
            LEFT JOIN guru g ON u.id = g.user_id
            LEFT JOIN siswa s ON u.id = s.user_id
            LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru AND ag.tanggal = ?
            LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa AND asis.tanggal = ?
        ");
        
        $stmt->execute([$date, $date]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return array_merge($summary, [
            'tanggal' => date('d/m/Y', strtotime($date)),
            'total' => array_sum($summary)
        ]);
    }
    
    /**
     * Get admin phone numbers
     */
    private function getAdminPhones() {
        $stmt = $this->conn->prepare("
            SELECT phone as no_hp FROM users 
            WHERE role = 'admin' AND phone IS NOT NULL
        ");
        $stmt->execute();
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'no_hp');
    }
    
    /**
     * Return daily attendance summary (for testing)
     */
    public function returnDailyAttendanceSummary($date = null) {
        $date = $date ?: date('Y-m-d');
        return $this->getAttendanceSummary($date);
    }
}

// Helper function for daily summary
function sendDailyAttendanceSummary($date = null) {
    global $conn;
    $automation = new AttendanceWhatsAppAutomation($conn);
    return $automation->sendDailyAttendanceSummary($date);
}

// Helper function for absent check
function checkAbsentStudents($date = null) {
    global $conn;
    $automation = new AttendanceWhatsAppAutomation($conn);
    return $automation->checkAbsentStudents($date);
}

// Helper function for easy access
function sendAttendanceNotification($attendance_data) {
    global $conn;
    $automation = new AttendanceWhatsAppAutomation($conn);
    return $automation->processAttendanceNotifications($attendance_data);
}
?>
