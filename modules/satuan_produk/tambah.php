<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Satuan Produk (Tambah Data)
 * File: modules/satuan_produk/tambah.php
 */

$judul_halaman = 'Tambah Satuan Produk';
$menu_aktif = 'satuan_produk';

require_once __DIR__ . '/../../includes/header.php';

$error = '';
$kode_satuan = $_POST['kode_satuan'] ?? '';
$nama_satuan = $_POST['nama_satuan'] ?? '';
$simbol = $_POST['simbol'] ?? '';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_satuan = trim($kode_satuan);
    $nama_satuan = trim($nama_satuan);
    $simbol = trim($simbol);

    if (empty($kode_satuan) || empty($nama_satuan) || empty($simbol)) {
        $error = 'Kode satuan, nama satuan, dan simbol satuan wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM satuan_produk WHERE kode_satuan = ?");
        $stmtCek->execute([$kode_satuan]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode satuan '{$kode_satuan}' sudah terdaftar.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO satuan_produk (kode_satuan, nama_satuan, simbol, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_satuan, $nama_satuan, $simbol, $status]);

                flash_set('success', "Satuan <strong>" . escape($nama_satuan) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/satuan_produk/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Satuan Produk</span>
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
                        <input type="text" name="kode_satuan" class="form-control" placeholder="Contoh: SAT-07" value="<?= escape($kode_satuan) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Satuan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_satuan" class="form-control" placeholder="Contoh: Renceng / Sachet" value="<?= escape($nama_satuan) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Simbol / Singkatan <span class="text-danger">*</span></label>
                        <input type="text" name="simbol" class="form-control" placeholder="Contoh: Renceng" value="<?= escape($simbol) ?>" required>
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
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Satuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
