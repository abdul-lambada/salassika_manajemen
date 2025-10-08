<?php
/**
 * REST API Framework for School Attendance System
 * Provides standardized API endpoints for integrations
 */

class RestAPI {
    private $conn;
    private $request_method;
    private $endpoint;
    private $params;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->request_method = $_SERVER["REQUEST_METHOD"];
        $this->endpoint = $this->getEndpoint();
        $this->params = $this->getParams();
        
        // Set JSON response headers
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        
        // Handle preflight requests
        if ($this->request_method === "OPTIONS") {
            http_response_code(200);
            exit;
        }
    }
    
    private function getEndpoint() {
        $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $path = trim($path, "/");
        $segments = explode("/", $path);
        return end($segments);
    }
    
    private function getParams() {
        $input = json_decode(file_get_contents("php://input"), true);
        return $input ?: $_REQUEST;
    }
    
    public function processRequest() {
        try {
            switch ($this->endpoint) {
                case "attendance":
                    return $this->handleAttendance();
                case "users":
                    return $this->handleUsers();
                case "reports":
                    return $this->handleReports();
                case "whatsapp":
                    return $this->handleWhatsApp();
                case "health":
                    return $this->handleHealthCheck();
                default:
                    return $this->response(["error" => "Endpoint not found"], 404);
            }
        } catch (Exception $e) {
            return $this->response(["error" => $e->getMessage()], 500);
        }
    }
    
    private function handleAttendance() {
        switch ($this->request_method) {
            case "GET":
                return $this->getAttendance();
            case "POST":
                return $this->createAttendance();
            case "PUT":
                return $this->updateAttendance();
            case "DELETE":
                return $this->deleteAttendance();
            default:
                return $this->response(["error" => "Method not allowed"], 405);
        }
    }
    
    private function getAttendance() {
        $date = isset($this->params["date"]) ? $this->params["date"] : date("Y-m-d");
        $user_type = isset($this->params["user_type"]) ? $this->params["user_type"] : "all";
        
        $query = "
            SELECT 
                u.name, u.role, 
                COALESCE(ag.status_kehadiran, asis.status_kehadiran) as status,
                COALESCE(ag.tanggal, asis.tanggal) as tanggal
            FROM users u
            LEFT JOIN guru g ON u.id = g.user_id
            LEFT JOIN siswa s ON u.id = s.user_id
            LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru AND ag.tanggal = :date
            LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa AND asis.tanggal = :date
        ";
        
        if ($user_type !== "all") {
            $query .= " WHERE u.role = :user_type";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":date", $date);
        if ($user_type !== "all") {
            $stmt->bindParam(":user_type", $user_type);
        }
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->response(["data" => $results, "count" => count($results)]);
    }
    
    private function handleHealthCheck() {
        $health = [
            "status" => "healthy",
            "timestamp" => date("Y-m-d H:i:s"),
            "database" => $this->checkDatabase(),
            "whatsapp_api" => $this->checkWhatsAppAPI(),
            "disk_space" => $this->checkDiskSpace(),
            "memory_usage" => $this->getMemoryUsage()
        ];
        
        $overall_status = "healthy";
        foreach ($health as $key => $value) {
            if (is_array($value) && isset($value["status"]) && $value["status"] !== "healthy") {
                $overall_status = "degraded";
                break;
            }
        }
        
        $health["overall_status"] = $overall_status;
        return $this->response($health);
    }
    
    private function checkDatabase() {
        try {
            $stmt = $this->conn->query("SELECT 1");
            return ["status" => "healthy", "connection" => "active"];
        } catch (Exception $e) {
            return ["status" => "unhealthy", "error" => $e->getMessage()];
        }
    }
    
    private function checkWhatsAppAPI() {
        // Check WhatsApp API configuration
        try {
            $stmt = $this->conn->prepare("SELECT * FROM whatsapp_config LIMIT 1");
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config && $config["api_key"]) {
                return ["status" => "healthy", "configured" => true];
            } else {
                return ["status" => "degraded", "configured" => false];
            }
        } catch (Exception $e) {
            return ["status" => "unhealthy", "error" => $e->getMessage()];
        }
    }
    
    private function checkDiskSpace() {
        $free_bytes = disk_free_space(".");
        $total_bytes = disk_total_space(".");
        $used_percentage = (($total_bytes - $free_bytes) / $total_bytes) * 100;
        
        $status = $used_percentage > 90 ? "critical" : ($used_percentage > 80 ? "warning" : "healthy");
        
        return [
            "status" => $status,
            "used_percentage" => round($used_percentage, 2),
            "free_space_mb" => round($free_bytes / 1024 / 1024, 2)
        ];
    }
    
    private function getMemoryUsage() {
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get("memory_limit");
        
        return [
            "current_usage_mb" => round($memory_usage / 1024 / 1024, 2),
            "memory_limit" => $memory_limit,
            "peak_usage_mb" => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
    }
    
    private function response($data, $status_code = 200) {
        http_response_code($status_code);
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

// Initialize API if accessed directly
if (basename($_SERVER["SCRIPT_NAME"]) === "api.php") {
    require_once "../includes/db.php";
    $api = new RestAPI($conn);
    echo $api->processRequest();
}
?>