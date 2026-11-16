<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pemasok (Tambah Data)
 * File: modules/pemasok/tambah.php
 */

$judul_halaman = 'Tambah Pemasok';
$menu_aktif = 'pemasok';

require_once __DIR__ . '/../../includes/header.php';

$error = '';
$kode_pemasok = $_POST['kode_pemasok'] ?? '';
$nama_pemasok = $_POST['nama_pemasok'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$telepon = $_POST['telepon'] ?? '';
$email = $_POST['email'] ?? '';
$npwp = $_POST['npwp'] ?? '';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_pemasok = trim($kode_pemasok);
    $nama_pemasok = trim($nama_pemasok);
    $alamat = trim($alamat);
    $telepon = trim($telepon);
    $email = trim($email);
    $npwp = trim($npwp);

    if (empty($kode_pemasok) || empty($nama_pemasok) || empty($alamat) || empty($telepon)) {
        $error = 'Kode pemasok, nama pemasok, alamat, dan nomor telepon wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM pemasok WHERE kode_pemasok = ?");
        $stmtCek->execute([$kode_pemasok]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode pemasok '{$kode_pemasok}' sudah digunakan.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO pemasok (kode_pemasok, nama_pemasok, alamat, telepon, email, npwp, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$kode_pemasok, $nama_pemasok, $alamat, $telepon, $email ?: null, $npwp ?: null, $status]);

                flash_set('success', "Pemasok <strong>" . escape($nama_pemasok) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/pemasok/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan pemasok: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Pemasok</span>
                <a href="<?= base_url('modules/pemasok/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <div class="col-md-4">
                            <label class="form-label">Kode Pemasok <span class="text-danger">*</span></label>
                            <input type="text" name="kode_pemasok" class="form-control" placeholder="Contoh: SPL-005" value="<?= escape($kode_pemasok) ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nama Perusahaan / Pemasok <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pemasok" class="form-control" placeholder="Contoh: PT Wings Surya Distribusi" value="<?= escape($nama_pemasok) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Kantor / Pergudangan <span class="text-danger">*</span></label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap distributor" required><?= escape($alamat) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon Kantor <span class="text-danger">*</span></label>
                            <input type="text" name="telepon" class="form-control" placeholder="Contoh: 021-8899123" value="<?= escape($telepon) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Perusahaan</label>
                            <input type="email" name="email" class="form-control" placeholder="info@supplier.co.id" value="<?= escape($email) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor NPWP Perusahaan</label>
                            <input type="text" name="npwp" class="form-control" placeholder="01.234.567.8-011.000" value="<?= escape($npwp) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Kemitraan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/pemasok/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Pemasok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
