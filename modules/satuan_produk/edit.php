<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Satuan Produk (Edit Data)
 * File: modules/satuan_produk/edit.php
 */

$judul_halaman = 'Edit Satuan Produk';
$menu_aktif = 'satuan_produk';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM satuan_produk WHERE id_satuan = ?");
$stmt->execute([$id]);
$sat = $stmt->fetch();

if (!$sat) {
    flash_set('danger', 'Data satuan tidak ditemukan.');
    header('Location: ' . base_url('modules/satuan_produk/index.php'));
    exit;
}

$error = '';
$kode_satuan = $sat['kode_satuan'];
$nama_satuan = $sat['nama_satuan'];
$simbol = $sat['simbol'];
$status = $sat['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_satuan = trim($_POST['kode_satuan'] ?? '');
    $nama_satuan = trim($_POST['nama_satuan'] ?? '');
    $simbol = trim($_POST['simbol'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    if (empty($kode_satuan) || empty($nama_satuan) || empty($simbol)) {
        $error = 'Kode satuan, nama satuan, dan simbol satuan wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM satuan_produk WHERE kode_satuan = ? AND id_satuan != ?");
        $stmtCek->execute([$kode_satuan, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode satuan '{$kode_satuan}' sudah digunakan oleh satuan lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE satuan_produk SET kode_satuan = ?, nama_satuan = ?, simbol = ?, status = ? WHERE id_satuan = ?");
                $stmtUpdate->execute([$kode_satuan, $nama_satuan, $simbol, $status, $id]);

                flash_set('success', "Satuan <strong>" . escape($nama_satuan) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/satuan_produk/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Satuan Produk</span>
                <a href="<?= base_url('modules/satuan_produk/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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

                    <div class="mb-3">
                        <label class="form-label">Kode Satuan <span class="text-danger">*</span></label>
                        <input type="text" name="kode_satuan" class="form-control" value="<?= escape($kode_satuan) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Satuan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_satuan" class="form-control" value="<?= escape($nama_satuan) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Simbol / Singkatan <span class="text-danger">*</span></label>
                        <input type="text" name="simbol" class="form-control" value="<?= escape($simbol) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/satuan_produk/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Satuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
