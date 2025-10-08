<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "Template Pesan WhatsApp";
$active_page = "whatsapp_templates";
include '../../templates/header.php';
include '../../templates/sidebar.php';

// Koneksi ke database
include '../../includes/db.php';
require_once __DIR__ . '/../../includes/wa_util.php';

$waService = new WhatsAppService($conn);

// Inisialisasi variabel pesan
$message = '';
$alert_class = '';

// Handle form submission untuk menambah/edit template
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $name = trim($_POST['name']);
                $display_name = trim($_POST['display_name']);
                $category = trim($_POST['category']);
                $language = trim($_POST['language']);
                $body = trim($_POST['body']);
                $variables = isset($_POST['variables']) ? json_encode($_POST['variables']) : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($name) || empty($display_name) || empty($body)) {
                    throw new Exception("Nama template, display name, dan body tidak boleh kosong");
                }
                
                $sql = "INSERT INTO whatsapp_message_templates (name, display_name, category, language, body, variables, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    throw new Exception("Gagal mempersiapkan statement: " . $conn->errorInfo()[2]);
                }
                
                if ($stmt->execute([$name, $display_name, $category, $language, $body, $variables, $is_active])) {
                    $message = 'Template berhasil ditambahkan';
                    $alert_class = 'alert-success';
                } else {
                    throw new Exception('Gagal menambahkan template: ' . $stmt->errorInfo()[2]);
                }
            } 
            elseif ($_POST['action'] === 'edit') {
                $id = (int)$_POST['id'];
                $name = trim($_POST['name']);
                $display_name = trim($_POST['display_name']);
                $category = trim($_POST['category']);
                $language = trim($_POST['language']);
                $body = trim($_POST['body']);
                $variables = isset($_POST['variables']) ? json_encode($_POST['variables']) : null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($name) || empty($display_name) || empty($body)) {
                    throw new Exception("Nama template, display name, dan body tidak boleh kosong");
                }
                
                $sql = "UPDATE whatsapp_message_templates SET name = ?, display_name = ?, category = ?, language = ?, body = ?, variables = ?, is_active = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    throw new Exception("Gagal mempersiapkan statement: " . $conn->errorInfo()[2]);
                }
                
                if ($stmt->execute([$name, $display_name, $category, $language, $body, $variables, $is_active, $id])) {
                    $message = 'Template berhasil diperbarui';
                    $alert_class = 'alert-success';
                } else {
                    throw new Exception('Gagal memperbarui template: ' . $stmt->errorInfo()[2]);
                }
            }
            elseif ($_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                
                $sql = "DELETE FROM whatsapp_message_templates WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    throw new Exception("Gagal mempersiapkan statement: " . $conn->errorInfo()[2]);
                }
                
                if ($stmt->execute([$id])) {
                    $message = 'Template berhasil dihapus';
                    $alert_class = 'alert-success';
                } else {
                    throw new Exception('Gagal menghapus template: ' . $stmt->errorInfo()[2]);
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $alert_class = 'alert-danger';
        }
    }
}

// Ambil semua template
try {
    $sql = "SELECT * FROM whatsapp_message_templates ORDER BY display_name ASC";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan statement: " . $conn->errorInfo()[2]);
    }
    
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = 'Error mengambil data template: ' . $e->getMessage();
    $alert_class = 'alert-danger';
    $templates = [];
}
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        
        <div class="container-fluid">
            <!-- Begin Alert SB Admin 2 -->
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            <!-- End Alert SB Admin 2 -->
            
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Template Pesan WhatsApp</h1>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTemplateModal">
                    <i class="fas fa-plus"></i> Tambah Template
                </button>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Template</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Display Name</th>
                                    <th>Kategori</th>
                                    <th>Bahasa</th>
                                    <th>Status</th>
                                    <th>Body</th>
                                    <th>Variables</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($templates)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Tidak ada template tersedia</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($template['name']); ?></td>
                                        <td><?php echo htmlspecialchars($template['display_name']); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($template['category']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary"><?php echo htmlspecialchars($template['language']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($template['status'] == 'APPROVED'): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php elseif ($template['status'] == 'PENDING'): ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $bodyText = htmlspecialchars($template['body']);
                                            if (strlen($bodyText) > 50) {
                                                echo substr($bodyText, 0, 50) . '...';
                                            } else {
                                                echo $bodyText;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($template['variables'])) {
                                                $variables = json_decode($template['variables'], true);
                                                if (is_array($variables)) {
                                                    echo implode(', ', $variables);
                                                } else {
                                                    echo htmlspecialchars($template['variables']);
                                                }
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editTemplateModal<?php echo $template['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['display_name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Template Modal -->
                                    <div class="modal fade" id="editTemplateModal<?php echo $template['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editTemplateModalLabel">Edit Template</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                                        
                                                        <div class="form-group">
                                                            <label for="name">Nama Template <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($template['name']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label for="display_name">Display Name <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo htmlspecialchars($template['display_name']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="category">Kategori</label>
                                                                    <select class="form-control" id="category" name="category">
                                                                        <option value="UTILITY" <?php echo $template['category'] == 'UTILITY' ? 'selected' : ''; ?>>Utility</option>
                                                                        <option value="MARKETING" <?php echo $template['category'] == 'MARKETING' ? 'selected' : ''; ?>>Marketing</option>
                                                                        <option value="AUTHENTICATION" <?php echo $template['category'] == 'AUTHENTICATION' ? 'selected' : ''; ?>>Authentication</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="language">Bahasa</label>
                                                                    <select class="form-control" id="language" name="language">
                                                                        <option value="id" <?php echo $template['language'] == 'id' ? 'selected' : ''; ?>>Indonesia</option>
                                                                        <option value="en" <?php echo $template['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label for="body">Body <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" id="body" name="body" rows="5" required><?php echo htmlspecialchars($template['body']); ?></textarea>
                                                            <small class="form-text text-muted">Gunakan {{variable}} untuk variabel dinamis</small>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label for="variables">Variables (JSON array)</label>
                                                            <input type="text" class="form-control" id="variables" name="variables" value="<?php echo htmlspecialchars($template['variables']); ?>">
                                                            <small class="form-text text-muted">Contoh: ["nama", "tanggal", "waktu"]</small>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?php echo $template['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="custom-control-label" for="is_active">Template Aktif</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../../templates/footer.php'; ?>
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTemplateModalLabel">Tambah Template Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Nama Template <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_name">Display Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="display_name" name="display_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category">Kategori</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="UTILITY">Utility</option>
                                    <option value="MARKETING">Marketing</option>
                                    <option value="AUTHENTICATION">Authentication</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="language">Bahasa</label>
                                <select class="form-control" id="language" name="language">
                                    <option value="id">Indonesia</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="body">Body <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="body" name="body" rows="5" required></textarea>
                        <small class="form-text text-muted">Gunakan {{variable}} untuk variabel dinamis</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="variables">Variables (JSON array)</label>
                        <input type="text" class="form-control" id="variables" name="variables">
                        <small class="form-text text-muted">Contoh: ["nama", "tanggal", "waktu"]</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                            <label class="custom-control-label" for="is_active">Template Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/scripts.php'; ?>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[ 1, "asc" ]],
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});

function deleteTemplate(id, name) {
    if (confirm('Apakah Anda yakin ingin menghapus template "' + name + '"? Tindakan ini tidak dapat dibatalkan.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                        '<input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>