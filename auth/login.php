<?php
session_start();
ob_start();
include '../includes/db.php';

$error = "";
$usernameError = "";
$passwordError = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validasi input tidak boleh kosong
    if (empty($username) || empty($password)) {
        $error = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                    <i class='fas fa-exclamation-triangle'></i> Username/UID dan password harus diisi.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                  </div>";
        if (empty($username)) $usernameError = "is-invalid";
        if (empty($password)) $passwordError = "is-invalid";
    } else {
        // Cari user berdasarkan username/name atau UID
        $stmt = $conn->prepare("SELECT * FROM users WHERE name = :username OR uid = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Ambil data tambahan berdasarkan role
            $additional_data = [];
            
            if ($user['role'] === 'guru') {
                $stmt_guru = $conn->prepare("SELECT * FROM guru WHERE user_id = :user_id");
                $stmt_guru->bindParam(':user_id', $user['id']);
                $stmt_guru->execute();
                $guru_data = $stmt_guru->fetch(PDO::FETCH_ASSOC);
                
                if ($guru_data) {
                    $additional_data = [
                        'id_guru' => $guru_data['id_guru'],
                        'nip' => $guru_data['nip'],
                        'nama_guru' => $guru_data['nama_guru']
                    ];
                }
            } elseif ($user['role'] === 'siswa') {
                $stmt_siswa = $conn->prepare("SELECT * FROM siswa WHERE user_id = :user_id");
                $stmt_siswa->bindParam(':user_id', $user['id']);
                $stmt_siswa->execute();
                $siswa_data = $stmt_siswa->fetch(PDO::FETCH_ASSOC);
                
                if ($siswa_data) {
                    $additional_data = [
                        'id_siswa' => $siswa_data['id_siswa'],
                        'nis' => $siswa_data['nis'],
                        'nama_siswa' => $siswa_data['nama_siswa']
                    ];
                }
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'uid' => $user['uid'],
                'avatar' => $user['avatar']
            ];

            // Gabungkan data tambahan ke session
            $_SESSION['user'] = array_merge($_SESSION['user'], $additional_data);

            // Redirect berdasarkan role - semua ke dashboard tunggal
            if ($user['role'] === 'admin') {
                header('Location: /absensi_sekolah/admin/index.php');
            } elseif ($user['role'] === 'guru') {
                header('Location: /absensi_sekolah/guru/index.php');
            } else {
                // Siswa tidak bisa login ke sistem admin
                $error = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                            <i class='fas fa-ban'></i> Siswa tidak dapat mengakses sistem admin.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                          </div>";
                $usernameError = "is-invalid";
                $passwordError = "is-invalid";
            }
            
            if (empty($error)) {
                exit;
            }
        } else {
            $error = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class='fas fa-times-circle'></i> Username/UID atau password salah.
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                      </div>";
            $usernameError = "is-invalid";
            $passwordError = "is-invalid";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistem Absensi Sekolah</title>
    <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        .title {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-control.is-invalid {
            border-color: #e53e3e;
            background: #fff5f5;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .form-control:focus + .input-icon {
            color: #667eea;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
            padding: 15px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #fed7d7;
            color: #c53030;
        }

        .alert-warning {
            background: #fef5e7;
            color: #d69e2e;
        }

        .login-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            font-size: 0.85rem;
        }

        .login-info h6 {
            margin-bottom: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-info ul {
            margin: 0;
            padding-left: 20px;
        }

        .login-info li {
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .invalid-feedback {
            display: block;
            color: #e53e3e;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .title {
                font-size: 1.3rem;
            }
            
            .form-control {
                padding: 12px 15px 12px 45px;
            }
            
            .input-icon {
                left: 15px;
            }
            
            .password-toggle {
                right: 15px;
            }
        }

        .loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="loader" id="loader">
        <div class="spinner"></div>
    </div>

    <div class="login-container">
        <div class="logo-container">
            <img src="../assets/img/logo.jpg" alt="Logo" class="logo">
            <h1 class="title">Sistem Absensi</h1>
            <p class="subtitle">Sekolah Salassika</p>
        </div>

        <div class="login-info">
            <h6><i class="fas fa-info-circle"></i> Informasi Login</h6>
            <ul>
                <li><strong>Admin:</strong> admin / admin123</li>
                <li><strong>Guru:</strong> nama guru / password</li>
                <li><strong>UID:</strong> bisa menggunakan UID fingerprint</li>
            </ul>
        </div>

        <?php if (!empty($error)): ?>
            <?= $error ?>
        <?php endif; ?>

        <form method="POST" action="" onsubmit="showLoader()">
            <div class="form-group">
                <input type="text" 
                       name="username" 
                       class="form-control <?= $usernameError ?>" 
                       placeholder="Username atau UID" 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                       required>
                <i class="fas fa-user input-icon"></i>
                <?php if (!empty($usernameError)): ?>
                    <div class="invalid-feedback">Username/UID tidak valid</div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <input type="password" 
                       name="password" 
                       id="passwordInput" 
                       class="form-control <?= $passwordError ?>" 
                       placeholder="Password" 
                       required>
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
                <?php if (!empty($passwordError)): ?>
                    <div class="invalid-feedback">Password tidak valid</div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
    </div>

    <script>
        function showLoader() {
            document.getElementById('loader').style.display = 'flex';
        }

        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Sembunyikan loader jika ada error
        if (document.querySelector('.alert')) {
            document.getElementById('loader').style.display = 'none';
        }

        // Auto focus pada input username
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.querySelector('input[name="username"]');
            if (usernameInput) {
                usernameInput.focus();
            }
        });
    </script>
</body>

</html>