<?php
session_start();
include './includes/db.php';
$title = "Profil Saya";
$active_page = "profil";
if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php");
    exit;
}
$user_id = $_SESSION['user']['id'];
// Ambil data user dari database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$success = $error = '';
$avatar_path = !empty($user['avatar']) ? '/absensi_sekolah/' . $user['avatar'] : '/absensi_sekolah/assets/img/undraw_profile.svg';
// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_password = trim($_POST['password']);
    $avatar_file = isset($_FILES['avatar']) ? $_FILES['avatar'] : null;
    $avatar_db = $user['avatar'];
    // Validasi nama
    if ($new_name === '') {
        $error = 'Nama tidak boleh kosong.';
    } else {
        // Proses upload avatar jika ada file
        if ($avatar_file && $avatar_file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif'];
            $ext = strtolower(pathinfo($avatar_file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = './uploads/avatar/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $target = $upload_dir . $filename;
                if (move_uploaded_file($avatar_file['tmp_name'], $target)) {
                    $avatar_db = 'uploads/avatar/' . $filename;
                    $avatar_path = '/absensi_sekolah/' . $avatar_db;
                } else {
                    $error = 'Gagal upload avatar.';
                }
            } else {
                $error = 'File avatar harus berupa gambar (jpg, jpeg, png, gif).';
            }
        }
        if (!$error) {
            if ($new_password !== '') {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET name = ?, password = ?, avatar = ? WHERE id = ?");
                $update->execute([$new_name, $hashed, $avatar_db, $user_id]);
            } else {
                $update = $conn->prepare("UPDATE users SET name = ?, avatar = ? WHERE id = ?");
                $update->execute([$new_name, $avatar_db, $user_id]);
            }
            $_SESSION['user']['name'] = $new_name;
            $_SESSION['user']['avatar'] = $avatar_db;
            $success = 'Profil berhasil diperbarui.';
            // Refresh data user
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $avatar_path = !empty($user['avatar']) ? '/absensi_sekolah/' . $user['avatar'] : '/absensi_sekolah/assets/img/undraw_profile.svg';
        }
    }
}
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/templates/navbar.php'; ?>
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="card shadow mb-4 mt-4">
                        <div class="card-header py-3">
                            <h1 class="h4 m-0 font-weight-bold text-primary">Profil Saya</h1>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= $success ?></div>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            <form method="post" enctype="multipart/form-data">
                                <div class="form-group text-center">
                                    <img src="<?= htmlspecialchars($avatar_path) ?>" alt="Avatar" class="rounded-circle mb-2" style="width:100px;height:100px;object-fit:cover;">
                                    <div>
                                        <input type="file" name="avatar" accept="image/*" class="form-control-file mt-2">
                                        <small class="text-muted">Format: jpg, jpeg, png, gif. Maks 2MB.</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Username/UID</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['uid']) ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['role']) ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Password Baru <small>(Kosongkan jika tidak ingin mengubah)</small></label>
                                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/templates/footer.php'; ?>
</div>

<?php include __DIR__ . '/templates/scripts.php'; ?>

</body>
</html> 