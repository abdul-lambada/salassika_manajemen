<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre>";

class AutomatedTester {
    private $conn;
    private $test_count = 0;
    private $success_count = 0;

    public function __construct() {
        echo "=============================================\n";
        echo "     MEMULAI PENGUJIAN OTOMATIS SISTEM\n";
        echo "=============================================\n\n";
    }

    private function run_test($description, callable $test_function) {
        $this->test_count++;
        echo "TEST " . $this->test_count . ": " . str_pad($description, 50);
        try {
            $result = $test_function();
            if ($result === true) {
                echo "[\033[32mSUCCESS\033[0m]\n";
                $this->success_count++;
            } else {
                echo "[\033[31mFAILED\033[0m]\n";
                if (is_string($result)) {
                    echo "  └─ Pesan: " . $result . "\n";
                }
            }
        } catch (Exception $e) {
            echo "[\033[31mERROR\033[0m]\n";
            echo "  └─ Exception: " . $e->getMessage() . "\n";
        }
    }

    public function test_database_connection() {
        $this->run_test("Koneksi ke Database (includes/db.php)", function() {
            include __DIR__ . '/../includes/db.php';
            if (isset($conn) && $conn instanceof PDO) {
                $this->conn = $conn;
                return true;
            }
            return "Variabel \$conn tidak ditemukan atau bukan objek PDO.";
        });
    }

    public function test_fingerprint_connection() {
        $this->run_test("Koneksi ke Device Fingerprint (X100-C)", function() {
            if (!file_exists(__DIR__ . '/../includes/fingerprint_config.php')) {
                return "File konfigurasi fingerprint tidak ditemukan.";
            }
            include __DIR__ . '/../includes/fingerprint_config.php';
            if (!defined('FINGERPRINT_IP') || !defined('FINGERPRINT_PORT')) {
                return "Konstanta FINGERPRINT_IP atau FINGERPRINT_PORT tidak terdefinisi.";
            }
            require_once __DIR__ . '/../includes/zklib/zklibrary.php';
            $zk = new ZKLibrary(FINGERPRINT_IP, FINGERPRINT_PORT);
            if ($zk->connect()) {
                $zk->disconnect();
                return true;
            }
            return "Gagal terhubung ke device di " . FINGERPRINT_IP . ":" . FINGERPRINT_PORT;
        });
    }

    public function check_file_availability() {
        $this->run_test("Ketersediaan file login (auth/login.php)", function() {
            return file_exists(__DIR__ . '/../auth/login.php') ? true : "File tidak ditemukan.";
        });
        $this->run_test("Ketersediaan dashboard admin (admin/index.php)", function() {
            return file_exists(__DIR__ . '/index.php') ? true : "File tidak ditemukan.";
        });
        $this->run_test("Ketersediaan dashboard guru (guru/index.php)", function() {
            return file_exists(__DIR__ . '/../guru/index.php') ? true : "File tidak ditemukan.";
        });
    }

    public function test_admin_features() {
        $this->run_test("Admin: Mengambil daftar guru", function() {
            if (!$this->conn) return "Koneksi DB Gagal.";
            $stmt = $this->conn->query("SELECT COUNT(*) FROM guru");
            return $stmt->execute() ? true : "Query gagal.";
        });

        $this->run_test("Admin: Mengambil daftar siswa", function() {
            if (!$this->conn) return "Koneksi DB Gagal.";
            $stmt = $this->conn->query("SELECT COUNT(*) FROM siswa");
            return $stmt->execute() ? true : "Query gagal.";
        });
    }

    public function test_guru_features() {
        $this->run_test("Guru: Cek halaman absensi", function() {
            return file_exists(__DIR__ . '/../guru/absensi_guru.php') ? true : "File tidak ditemukan.";
        });
        $this->run_test("Guru: Cek monitor fingerprint", function() {
            return file_exists(__DIR__ . '/../guru/monitor_fingerprint.php') ? true : "File tidak ditemukan.";
        });
    }
    
    public function __destruct() {
        echo "\n=============================================\n";
        echo "           PENGUJIAN SELESAI\n";
        echo "=============================================\n";
        echo "Total Tes      : " . $this->test_count . "\n";
        echo "Berhasil       : " . $this->success_count . " (\033[32m" . round(($this->success_count / $this->test_count) * 100) . "%\033[0m)\n";
        echo "Gagal / Error  : " . ($this->test_count - $this->success_count) . " (\033[31m" . round((($this->test_count - $this->success_count) / $this->test_count) * 100) . "%\033[0m)\n";
        echo "</pre>";
    }
}

// Menjalankan semua tes
$tester = new AutomatedTester();
$tester->test_database_connection();
$tester->test_fingerprint_connection();
$tester->check_file_availability();
$tester->test_admin_features();
$tester->test_guru_features();

?> 