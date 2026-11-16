<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Departemen (Tambah Data)
 * File: modules/departemen/tambah.php
 */

$judul_halaman = 'Tambah Departemen';
$menu_aktif = 'departemen';

require_once __DIR__ . '/../../includes/header.php';

$error = '';
$kode_departemen = $_POST['kode_departemen'] ?? '';
$nama_departemen = $_POST['nama_departemen'] ?? '';
$deskripsi = $_POST['deskripsi'] ?? '';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_departemen = trim($kode_departemen);
    $nama_departemen = trim($nama_departemen);
    $deskripsi = trim($deskripsi);

    if (empty($kode_departemen) || empty($nama_departemen)) {
        $error = 'Kode dan nama departemen wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM departemen WHERE kode_departemen = ?");
        $stmtCek->execute([$kode_departemen]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode departemen '{$kode_departemen}' sudah digunakan.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO departemen (kode_departemen, nama_departemen, deskripsi, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_departemen, $nama_departemen, $deskripsi ?: null, $status]);

                flash_set('success', "Departemen <strong>" . escape($nama_departemen) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/departemen/index.php'));
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
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Departemen</span>
                <a href="<?= base_url('modules/departemen/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Kode Departemen <span class="text-danger">*</span></label>
                        <input type="text" name="kode_departemen" class="form-control" placeholder="Contoh: DEP-05" value="<?= escape($kode_departemen) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Departemen <span class="text-danger">*</span></label>
                        <input type="text" name="nama_departemen" class="form-control" placeholder="Contoh: Alat Tulis & Kantor" value="<?= escape($nama_departemen) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Keterangan singkat mengenai departemen ini"><?= escape($deskripsi) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/departemen/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Departemen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
