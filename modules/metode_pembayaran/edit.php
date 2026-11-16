<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Metode Pembayaran (Edit Data)
 * File: modules/metode_pembayaran/edit.php
 */

$judul_halaman = 'Edit Metode Pembayaran';
$menu_aktif = 'metode_pembayaran';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM metode_pembayaran WHERE id_metode = ?");
$stmt->execute([$id]);
$mtd = $stmt->fetch();

if (!$mtd) {
    flash_set('danger', 'Data metode pembayaran tidak ditemukan.');
    header('Location: ' . base_url('modules/metode_pembayaran/index.php'));
    exit;
}

$error = '';
$kode_metode = $mtd['kode_metode'];
$nama_metode = $mtd['nama_metode'];
$kategori = $mtd['kategori'];
$status = $mtd['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_metode = trim($_POST['kode_metode'] ?? '');
    $nama_metode = trim($_POST['nama_metode'] ?? '');
    $kategori = $_POST['kategori'] ?? 'tunai';
    $status = $_POST['status'] ?? 'aktif';

    if (empty($kode_metode) || empty($nama_metode)) {
        $error = 'Kode metode dan nama metode pembayaran wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM metode_pembayaran WHERE kode_metode = ? AND id_metode != ?");
        $stmtCek->execute([$kode_metode, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode metode '{$kode_metode}' sudah digunakan oleh metode lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE metode_pembayaran SET kode_metode = ?, nama_metode = ?, kategori = ?, status = ? WHERE id_metode = ?");
                $stmtUpdate->execute([$kode_metode, $nama_metode, $kategori, $status, $id]);

                flash_set('success', "Metode pembayaran <strong>" . escape($nama_metode) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/metode_pembayaran/index.php'));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Metode Pembayaran</span>
                <a href="<?= base_url('modules/metode_pembayaran/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Kode Metode <span class="text-danger">*</span></label>
                        <input type="text" name="kode_metode" class="form-control" value="<?= escape($kode_metode) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Metode Pembayaran <span class="text-danger">*</span></label>
                        <input type="text" name="nama_metode" class="form-control" value="<?= escape($nama_metode) ?>" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Kategori Alat Pembayaran <span class="text-danger">*</span></label>
                            <select name="kategori" class="form-select" required>
                                <option value="tunai" <?= ($kategori === 'tunai') ? 'selected' : '' ?>>Uang Tunai (Cash)</option>
                                <option value="kartu" <?= ($kategori === 'kartu') ? 'selected' : '' ?>>Kartu Debit / Kredit</option>
                                <option value="dompet_digital" <?= ($kategori === 'dompet_digital') ? 'selected' : '' ?>>Dompet Digital (QRIS / E-Wallet)</option>
                                <option value="transfer" <?= ($kategori === 'transfer') ? 'selected' : '' ?>>Transfer Bank (Virtual Account)</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/metode_pembayaran/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Metode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
