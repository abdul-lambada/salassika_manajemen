<?php
session_start();
include '../../includes/db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}
// Ambil daftar jurusan untuk dropdown
$stmt_jurusan = $conn->query("SELECT * FROM jurusan");
$jurusan_list = $stmt_jurusan->fetchAll(PDO::FETCH_ASSOC);
// Proses tambah data kelas
$message = '';
$alert_class = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kelas = trim($_POST['nama_kelas']);
    $id_jurusan = trim($_POST['id_jurusan']);
    if (!empty($nama_kelas) && !empty($id_jurusan)) {
        try {
            $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, id_jurusan) VALUES (:nama_kelas, :id_jurusan)");
            $stmt->bindParam(':nama_kelas', $nama_kelas);
            $stmt->bindParam(':id_jurusan', $id_jurusan);
            $stmt->execute();
            header("Location: list_kelas.php?status=add_success");
            exit();
        } catch (\PDOException $e) {
            header("Location: list_kelas.php?status=error");
            exit();
        }
    } else {
        $message = 'Nama kelas dan jurusan tidak boleh kosong.';
        $alert_class = 'alert-warning';
    }
}
$title = "Tambah Kelas";
$active_page = "tambah_kelas";
include '../../templates/header.php';
include '../../templates/sidebar.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Tambah Kelas</h1> -->
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
                            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Kelas</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="nama_kelas">Nama Kelas:</label>
                                    <input type="text" name="nama_kelas" id="nama_kelas" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="id_jurusan">Jurusan:</label>
                                    <select name="id_jurusan" id="id_jurusan" class="form-control" required>
                                        <option value="">Pilih Jurusan</option>
                                        <?php foreach ($jurusan_list as $jurusan): ?>
                                            <option value="<?php echo $jurusan['id_jurusan']; ?>">
                                                <?php echo $jurusan['nama_jurusan']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">Simpan</button>
                                <a href="list_kelas.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>