<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pemasok (Edit Data)
 * File: modules/pemasok/edit.php
 */

$judul_halaman = 'Edit Pemasok';
$menu_aktif = 'pemasok';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM pemasok WHERE id_pemasok = ?");
$stmt->execute([$id]);
$spl = $stmt->fetch();

if (!$spl) {
    flash_set('danger', 'Data pemasok tidak ditemukan.');
    header('Location: ' . base_url('modules/pemasok/index.php'));
    exit;
}

$error = '';
$kode_pemasok = $spl['kode_pemasok'];
$nama_pemasok = $spl['nama_pemasok'];
$alamat = $spl['alamat'];
$telepon = $spl['telepon'];
$email = $spl['email'] ?? '';
$npwp = $spl['npwp'] ?? '';
$status = $spl['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_pemasok = trim($_POST['kode_pemasok'] ?? '');
    $nama_pemasok = trim($_POST['nama_pemasok'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $npwp = trim($_POST['npwp'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    if (empty($kode_pemasok) || empty($nama_pemasok) || empty($alamat) || empty($telepon)) {
        $error = 'Kode pemasok, nama pemasok, alamat, dan nomor telepon wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM pemasok WHERE kode_pemasok = ? AND id_pemasok != ?");
        $stmtCek->execute([$kode_pemasok, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode pemasok '{$kode_pemasok}' sudah digunakan oleh pemasok lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE pemasok 
                    SET kode_pemasok = ?, nama_pemasok = ?, alamat = ?, telepon = ?, email = ?, npwp = ?, status = ?
                    WHERE id_pemasok = ?
                ");
                $stmtUpdate->execute([$kode_pemasok, $nama_pemasok, $alamat, $telepon, $email ?: null, $npwp ?: null, $status, $id]);

                flash_set('success', "Pemasok <strong>" . escape($nama_pemasok) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/pemasok/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Pemasok</span>
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
                            <input type="text" name="kode_pemasok" class="form-control" value="<?= escape($kode_pemasok) ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nama Perusahaan / Pemasok <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pemasok" class="form-control" value="<?= escape($nama_pemasok) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Kantor / Pergudangan <span class="text-danger">*</span></label>
                            <textarea name="alamat" class="form-control" rows="2" required><?= escape($alamat) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon Kantor <span class="text-danger">*</span></label>
                            <input type="text" name="telepon" class="form-control" value="<?= escape($telepon) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Perusahaan</label>
                            <input type="email" name="email" class="form-control" value="<?= escape($email) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor NPWP Perusahaan</label>
                            <input type="text" name="npwp" class="form-control" value="<?= escape($npwp) ?>">
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
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Pemasok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
