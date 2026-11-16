<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Merek Produk (Edit Data)
 * File: modules/merek_produk/edit.php
 */

$judul_halaman = 'Edit Merek Produk';
$menu_aktif = 'merek_produk';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM merek_produk WHERE id_merek = ?");
$stmt->execute([$id]);
$mrk = $stmt->fetch();

if (!$mrk) {
    flash_set('danger', 'Data merek tidak ditemukan.');
    header('Location: ' . base_url('modules/merek_produk/index.php'));
    exit;
}

$error = '';
$kode_merek = $mrk['kode_merek'];
$nama_merek = $mrk['nama_merek'];
$produsen = $mrk['produsen'] ?? '';
$status = $mrk['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_merek = trim($_POST['kode_merek'] ?? '');
    $nama_merek = trim($_POST['nama_merek'] ?? '');
    $produsen = trim($_POST['produsen'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    if (empty($kode_merek) || empty($nama_merek)) {
        $error = 'Kode merek dan nama merek wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM merek_produk WHERE kode_merek = ? AND id_merek != ?");
        $stmtCek->execute([$kode_merek, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode merek '{$kode_merek}' sudah digunakan oleh merek lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE merek_produk SET kode_merek = ?, nama_merek = ?, produsen = ?, status = ? WHERE id_merek = ?");
                $stmtUpdate->execute([$kode_merek, $nama_merek, $produsen ?: null, $status, $id]);

                flash_set('success', "Merek <strong>" . escape($nama_merek) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/merek_produk/index.php'));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Merek Produk</span>
                <a href="<?= base_url('modules/merek_produk/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Kode Merek <span class="text-danger">*</span></label>
                        <input type="text" name="kode_merek" class="form-control" value="<?= escape($kode_merek) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Merek <span class="text-danger">*</span></label>
                        <input type="text" name="nama_merek" class="form-control" value="<?= escape($nama_merek) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produsen / Manufaktur</label>
                        <input type="text" name="produsen" class="form-control" value="<?= escape($produsen) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/merek_produk/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Merek
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
