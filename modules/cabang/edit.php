<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Cabang (Edit Data)
 * File: modules/cabang/edit.php
 */

$judul_halaman = 'Edit Cabang';
$menu_aktif = 'cabang';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

// Ambil data cabang
$stmt = $pdo->prepare("SELECT * FROM cabang WHERE id_cabang = ?");
$stmt->execute([$id]);
$cabang = $stmt->fetch();

if (!$cabang) {
    flash_set('danger', 'Data cabang tidak ditemukan.');
    header('Location: ' . base_url('modules/cabang/index.php'));
    exit;
}

$error = '';
$kode_cabang = $cabang['kode_cabang'];
$nama_cabang = $cabang['nama_cabang'];
$alamat = $cabang['alamat'];
$telepon = $cabang['telepon'];
$email = $cabang['email'] ?? '';
$status = $cabang['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_cabang = trim($_POST['kode_cabang'] ?? '');
    $nama_cabang = trim($_POST['nama_cabang'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    if (empty($kode_cabang) || empty($nama_cabang) || empty($alamat) || empty($telepon)) {
        $error = 'Seluruh kolom bertanda bintang (*) wajib diisi.';
    } else {
        // Cek duplikasi kode cabang kecuali dirinya sendiri
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM cabang WHERE kode_cabang = ? AND id_cabang != ?");
        $stmtCek->execute([$kode_cabang, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode cabang '{$kode_cabang}' sudah digunakan oleh cabang lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE cabang 
                    SET kode_cabang = ?, nama_cabang = ?, alamat = ?, telepon = ?, email = ?, status = ?
                    WHERE id_cabang = ?
                ");
                $stmtUpdate->execute([$kode_cabang, $nama_cabang, $alamat, $telepon, $email ?: null, $status, $id]);

                flash_set('success', "Data cabang <strong>" . escape($nama_cabang) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/cabang/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Cabang</span>
                <a href="<?= base_url('modules/cabang/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                        <div><?= escape($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <?= csrf_input() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Cabang <span class="text-danger">*</span></label>
                            <input type="text" name="kode_cabang" class="form-control" value="<?= escape($kode_cabang) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Cabang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_cabang" class="form-control" value="<?= escape($nama_cabang) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="alamat" class="form-control" rows="2" required><?= escape($alamat) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="telepon" class="form-control" value="<?= escape($telepon) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Cabang</label>
                            <input type="email" name="email" class="form-control" value="<?= escape($email) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Operasional <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/cabang/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Data Cabang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
