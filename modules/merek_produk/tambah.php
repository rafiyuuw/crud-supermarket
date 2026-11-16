<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Merek Produk (Tambah Data)
 * File: modules/merek_produk/tambah.php
 */

$judul_halaman = 'Tambah Merek Produk';
$menu_aktif = 'merek_produk';

require_once __DIR__ . '/../../includes/header.php';

$error = '';
$kode_merek = $_POST['kode_merek'] ?? '';
$nama_merek = $_POST['nama_merek'] ?? '';
$produsen = $_POST['produsen'] ?? '';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_merek = trim($kode_merek);
    $nama_merek = trim($nama_merek);
    $produsen = trim($produsen);

    if (empty($kode_merek) || empty($nama_merek)) {
        $error = 'Kode merek dan nama merek wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM merek_produk WHERE kode_merek = ?");
        $stmtCek->execute([$kode_merek]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode merek '{$kode_merek}' sudah terdaftar.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO merek_produk (kode_merek, nama_merek, produsen, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_merek, $nama_merek, $produsen ?: null, $status]);

                flash_set('success', "Merek <strong>" . escape($nama_merek) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/merek_produk/index.php'));
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
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Merek Produk</span>
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
                        <input type="text" name="kode_merek" class="form-control" placeholder="Contoh: MRK-07" value="<?= escape($kode_merek) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Merek <span class="text-danger">*</span></label>
                        <input type="text" name="nama_merek" class="form-control" placeholder="Contoh: SilverQueen" value="<?= escape($nama_merek) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produsen / Manufaktur</label>
                        <input type="text" name="produsen" class="form-control" placeholder="Contoh: PT Petra Foods Tbk" value="<?= escape($produsen) ?>">
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
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Merek
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
