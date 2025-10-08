<?php
/**
 * Advanced Statistics Helper
 * Provides comprehensive statistical analysis for attendance data
 */

class AdvancedStatsHelper {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Calculate attendance percentage for a user/class/period
     */
    public function calculateAttendancePercentage($user_id = null, $class_id = null, $start_date = null, $end_date = null, $user_type = "siswa") {
        try {
            $start_date = $start_date ?: date("Y-m-01");
            $end_date = $end_date ?: date("Y-m-d");
            
            $table = ($user_type === "guru") ? "absensi_guru" : "absensi_siswa";
            $user_table = ($user_type === "guru") ? "guru" : "siswa";
            
            $where_conditions = ["DATE(tanggal) BETWEEN :start_date AND :end_date"];
            $params = [":start_date" => $start_date, ":end_date" => $end_date];
            
            if ($user_id) {
                $where_conditions[] = "a.user_id = :user_id";
                $params[":user_id"] = $user_id;
            }
            
            if ($class_id && $user_type === "siswa") {
                $where_conditions[] = "s.id_kelas = :class_id";
                $params[":class_id"] = $class_id;
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            
            $query = "
                SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN a.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir_count,
                    SUM(CASE WHEN a.status_kehadiran = 'Telat' THEN 1 ELSE 0 END) as telat_count,
                    SUM(CASE WHEN a.status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) as sakit_count,
                    SUM(CASE WHEN a.status_kehadiran = 'Izin' THEN 1 ELSE 0 END) as izin_count,
                    SUM(CASE WHEN a.status_kehadiran = 'Alfa' THEN 1 ELSE 0 END) as alfa_count
                FROM $table a
                JOIN $user_table ut ON a.user_id = ut.user_id
                " . ($class_id && $user_type === "siswa" ? "JOIN siswa s ON a.user_id = s.user_id" : "") . "
                WHERE $where_clause
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result["total_days"] > 0) {
                $attendance_rate = ($result["hadir_count"] + $result["telat_count"]) / $result["total_days"] * 100;
                $punctuality_rate = $result["hadir_count"] / $result["total_days"] * 100;
                
                return [
                    "total_days" => $result["total_days"],
                    "hadir_count" => $result["hadir_count"],
                    "telat_count" => $result["telat_count"],
                    "sakit_count" => $result["sakit_count"],
                    "izin_count" => $result["izin_count"],
                    "alfa_count" => $result["alfa_count"],
                    "attendance_rate" => round($attendance_rate, 2),
                    "punctuality_rate" => round($punctuality_rate, 2),
                    "absence_rate" => round(100 - $attendance_rate, 2)
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Attendance percentage calculation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get monthly trends for attendance
     */
    public function getMonthlyTrends($months = 6, $user_type = "siswa") {
        try {
            $table = ($user_type === "guru") ? "absensi_guru" : "absensi_siswa";
            
            $query = "
                SELECT 
                    DATE_FORMAT(tanggal, '%Y-%m') as month,
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status_kehadiran = 'Telat' THEN 1 ELSE 0 END) as telat,
                    SUM(CASE WHEN status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status_kehadiran = 'Izin' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status_kehadiran = 'Alfa' THEN 1 ELSE 0 END) as alfa
                FROM $table
                WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
                ORDER BY month DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([":months" => $months]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate percentages
            foreach ($results as &$result) {
                if ($result["total_records"] > 0) {
                    $result["attendance_rate"] = round(($result["hadir"] + $result["telat"]) / $result["total_records"] * 100, 2);
                    $result["punctuality_rate"] = round($result["hadir"] / $result["total_records"] * 100, 2);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Monthly trends error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Compare class performance
     */
    public function compareClassPerformance($start_date = null, $end_date = null) {
        try {
            $start_date = $start_date ?: date("Y-m-01");
            $end_date = $end_date ?: date("Y-m-d");
            
            $query = "
                SELECT 
                    k.nama_kelas,
                    k.id_kelas,
                    COUNT(*) as total_records,
                    SUM(CASE WHEN a.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN a.status_kehadiran = 'Telat' THEN 1 ELSE 0 END) as telat,
                    SUM(CASE WHEN a.status_kehadiran = 'Alfa' THEN 1 ELSE 0 END) as alfa,
                    COUNT(DISTINCT a.user_id) as total_students
                FROM absensi_siswa a
                JOIN siswa s ON a.user_id = s.user_id
                JOIN kelas k ON s.id_kelas = k.id_kelas
                WHERE DATE(a.tanggal) BETWEEN :start_date AND :end_date
                GROUP BY k.id_kelas, k.nama_kelas
                ORDER BY k.nama_kelas
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([":start_date" => $start_date, ":end_date" => $end_date]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate performance metrics
            foreach ($results as &$result) {
                if ($result["total_records"] > 0) {
                    $result["attendance_rate"] = round(($result["hadir"] + $result["telat"]) / $result["total_records"] * 100, 2);
                    $result["punctuality_rate"] = round($result["hadir"] / $result["total_records"] * 100, 2);
                    $result["absence_rate"] = round($result["alfa"] / $result["total_records"] * 100, 2);
                    $result["avg_attendance_per_student"] = round($result["total_records"] / $result["total_students"], 2);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Class performance comparison error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Analyze late arrival patterns
     */
    public function analyzeLatePatterns($user_id = null, $days = 30) {
        try {
            $where_conditions = ["DATE(tanggal) >= DATE_SUB(CURDATE(), INTERVAL :days DAY)", "status_kehadiran = 'Telat'"];
            $params = [":days" => $days];
            
            if ($user_id) {
                $where_conditions[] = "user_id = :user_id";
                $params[":user_id"] = $user_id;
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            
            $query = "
                SELECT 
                    DAYNAME(tanggal) as day_name,
                    DAYOFWEEK(tanggal) as day_number,
                    COUNT(*) as late_count,
                    AVG(TIME_TO_SEC(jam_masuk)) as avg_late_time_seconds
                FROM absensi_siswa
                WHERE $where_clause
                GROUP BY DAYOFWEEK(tanggal), DAYNAME(tanggal)
                ORDER BY day_number
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert seconds to readable time
            foreach ($results as &$result) {
                $result["avg_late_time"] = gmdate("H:i:s", $result["avg_late_time_seconds"]);
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Late pattern analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top performers and bottom performers
     */
    public function getPerformanceRanking($limit = 10, $user_type = "siswa", $start_date = null, $end_date = null) {
        try {
            $start_date = $start_date ?: date("Y-m-01");
            $end_date = $end_date ?: date("Y-m-d");
            
            $table = ($user_type === "guru") ? "absensi_guru" : "absensi_siswa";
            $user_table = ($user_type === "guru") ? "guru" : "siswa";
            $name_field = ($user_type === "guru") ? "g.nip" : "s.nis";
            
            $query = "
                SELECT 
                    u.name as user_name,
                    $name_field as identifier,
                    " . ($user_type === "siswa" ? "k.nama_kelas," : "") . "
                    COUNT(*) as total_days,
                    SUM(CASE WHEN a.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir_count,
                    SUM(CASE WHEN a.status_kehadiran = 'Telat' THEN 1 ELSE 0 END) as telat_count,
                    SUM(CASE WHEN a.status_kehadiran = 'Alfa' THEN 1 ELSE 0 END) as alfa_count,
                    ROUND(((SUM(CASE WHEN a.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) + 
                           SUM(CASE WHEN a.status_kehadiran = 'Telat' THEN 1 ELSE 0 END)) / COUNT(*)) * 100, 2) as attendance_rate
                FROM $table a
                JOIN users u ON a.user_id = u.id
                JOIN $user_table ut ON a.user_id = ut.user_id
                " . ($user_type === "siswa" ? "JOIN kelas k ON ut.id_kelas = k.id_kelas" : "") . "
                WHERE DATE(a.tanggal) BETWEEN :start_date AND :end_date
                GROUP BY a.user_id, u.name, $name_field" . ($user_type === "siswa" ? ", k.nama_kelas" : "") . "
                HAVING COUNT(*) >= 5
                ORDER BY attendance_rate DESC
                LIMIT :limit
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":start_date", $start_date);
            $stmt->bindValue(":end_date", $end_date);
            $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Performance ranking error: " . $e->getMessage());
            return [];
        }
    }
}

// Initialize global instance if database connection exists
if (isset($conn)) {
    $advancedStats = new AdvancedStatsHelper($conn);
}
?>
