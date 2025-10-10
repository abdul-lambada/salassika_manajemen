<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$title = 'Monitor Fingerprint';
$active_page = 'monitor_fingerprint';
$required_role = ($currentUser['role'] ?? '') === 'admin' ? null : 'guru';
$error_message = '';

try {
    // Ambil statistik fingerprint hari ini
    $stmt_stats = $conn->prepare(
        "SELECT COUNT(*) as total_absensi,
                COUNT(DISTINCT user_id) as total_user,
                MIN(timestamp) as absen_pertama,
                MAX(timestamp) as absen_terakhir
         FROM tbl_kehadiran
         WHERE DATE(timestamp) = CURDATE()"
    );
    $stmt_stats->execute();
    $fingerprint_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC) ?: [
        'total_absensi' => 0,
        'total_user' => 0,
        'absen_pertama' => null,
        'absen_terakhir' => null,
    ];

    // Ambil data absensi fingerprint hari ini
    $stmt_today = $conn->prepare("
        SELECT 
            kh.timestamp,
            kh.verification_mode,
            kh.status,
            COALESCE(u.name, s.nama_siswa, g.nama_guru) as nama_user,
            u.uid as username,
            CASE 
                WHEN s.id_siswa IS NOT NULL THEN 'Siswa'
                WHEN g.id_guru IS NOT NULL THEN 'Guru'
                ELSE 'Unknown'
            END as tipe_user,
            COALESCE(s.nis, g.nip) as nomor_id,
            COALESCE(k.nama_kelas, '') as kelas
        FROM tbl_kehadiran kh
        LEFT JOIN users u ON kh.user_id = u.id
        LEFT JOIN siswa s ON s.user_id = u.id
        LEFT JOIN guru g ON g.user_id = u.id
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE DATE(kh.timestamp) = CURDATE()
        ORDER BY kh.timestamp DESC
        LIMIT 50
    ");
    $stmt_today->execute();
    $absensi_today = $stmt_today->fetchAll(PDO::FETCH_ASSOC);

    // Ambil statistik per jam
    $stmt_hourly = $conn->prepare("
        SELECT 
            HOUR(timestamp) as jam,
            COUNT(*) as jumlah
        FROM tbl_kehadiran 
        WHERE DATE(timestamp) = CURDATE()
        GROUP BY HOUR(timestamp)
        ORDER BY jam
    ");
    $stmt_hourly->execute();
    $hourly_stats = $stmt_hourly->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data siswa yang belum absen fingerprint hari ini
    $stmt_missing = $conn->prepare("
        SELECT 
            u.name as nama_siswa,
            s.nis,
            k.nama_kelas
        FROM siswa s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN tbl_kehadiran kh ON s.user_id = kh.user_id AND DATE(kh.timestamp) = CURDATE()
        WHERE kh.user_id IS NULL
        ORDER BY k.nama_kelas, u.name
        LIMIT 20
    ");
    $stmt_missing->execute();
    $missing_students = $stmt_missing->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>
<?php include __DIR__ . '/../templates/layout_start.php'; ?>
    <style>
        .fingerprint-badge {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7em;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
        }

        .status-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .real-time-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .pulsing-badge {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
    </style>
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <!-- <h1 class="h3 mb-0 font-weight-bold text-gray-800">Monitor Fingerprint</h1> -->
            <span class="badge badge-pill badge-success d-flex align-items-center px-3 py-2 pulsing-badge" style="font-size: 1rem;">
                <i class="fas fa-circle mr-2" style="font-size: 0.8rem;"></i>
                Real-time
            </span>

        <!-- Statistik Fingerprint -->
        <div class="row mb-4">
{{ ... }}
        // Auto refresh setiap 30 detik
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>