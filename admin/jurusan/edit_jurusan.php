<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

include '../../includes/db.php';
$message = '';
$alert_class = '';
if (!isset($_GET['id'])) {
    header('Location: list_jurusan.php?status=error');
    exit;
}
$id_jurusan = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_jurusan = trim($_POST['nama_jurusan']);
    if (!empty($nama_jurusan)) {
        $stmt = $conn->prepare("UPDATE jurusan SET nama_jurusan = ? WHERE id_jurusan = ?");
        if ($stmt->execute([$nama_jurusan, $id_jurusan])) {
            header('Location: list_jurusan.php?status=edit_success');
            exit;
        } else {
            $message = 'Gagal mengedit jurusan.';
            $alert_class = 'alert-danger';
        }
    } else {
        $message = 'Nama jurusan tidak boleh kosong.';
        $alert_class = 'alert-warning';
    }
}

$stmt = $conn->prepare("SELECT * FROM jurusan WHERE id_jurusan = ?");
$stmt->execute([$id_jurusan]);
$jurusan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$jurusan) {
    header('Location: list_jurusan.php?status=error');
    exit;
}

$title = "Edit Jurusan";
$active_page = "edit_jurusan";
include '../../templates/header.php';
include '../../templates/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Edit Jurusan</h1> -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Form Edit Jurusan</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="nama_jurusan">Nama Jurusan</label>
                                    <input type="text" class="form-control" id="nama_jurusan" name="nama_jurusan" value="<?php echo htmlspecialchars($jurusan['nama_jurusan']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-success">Simpan</button>
                                <a href="list_jurusan.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>