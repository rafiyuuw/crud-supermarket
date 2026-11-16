<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Alamat Pelanggan (Edit Data)
 * File: modules/alamat_pelanggan/edit.php
 */

$judul_halaman = 'Edit Alamat Pelanggan';
$menu_aktif = 'alamat_pelanggan';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM alamat_pelanggan WHERE id_alamat = ?");
$stmt->execute([$id]);
$almt = $stmt->fetch();

if (!$almt) {
    flash_set('danger', 'Data alamat tidak ditemukan.');
    header('Location: ' . base_url('modules/alamat_pelanggan/index.php'));
    exit;
}

$daftar_pelanggan = $pdo->query("SELECT id_pelanggan, kode_pelanggan, nama_lengkap FROM pelanggan WHERE status = 'aktif' ORDER BY nama_lengkap ASC")->fetchAll();

$error = '';
$id_pelanggan = (int)$almt['id_pelanggan'];
$label_alamat = $almt['label_alamat'];
$alamat_lengkap = $almt['alamat_lengkap'];
$kota = $almt['kota'];
$provinsi = $almt['provinsi'];
$kode_pos = $almt['kode_pos'];
$status_utama = $almt['status_utama'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);
    $label_alamat = trim($_POST['label_alamat'] ?? '');
    $alamat_lengkap = trim($_POST['alamat_lengkap'] ?? '');
    $kota = trim($_POST['kota'] ?? '');
    $provinsi = trim($_POST['provinsi'] ?? '');
    $kode_pos = trim($_POST['kode_pos'] ?? '');
    $status_utama = $_POST['status_utama'] ?? 'tidak';

    if (empty($id_pelanggan) || empty($label_alamat) || empty($alamat_lengkap) || empty($kota) || empty($kode_pos)) {
        $error = 'Seluruh kolom bertanda bintang (*) wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            if ($status_utama === 'ya') {
                $stmtReset = $pdo->prepare("UPDATE alamat_pelanggan SET status_utama = 'tidak' WHERE id_pelanggan = ? AND id_alamat != ?");
                $stmtReset->execute([$id_pelanggan, $id]);
            }

            $stmtUpdate = $pdo->prepare("
                UPDATE alamat_pelanggan 
                SET id_pelanggan = ?, label_alamat = ?, alamat_lengkap = ?, kota = ?, provinsi = ?, kode_pos = ?, status_utama = ?
                WHERE id_alamat = ?
            ");
            $stmtUpdate->execute([$id_pelanggan, $label_alamat, $alamat_lengkap, $kota, $provinsi, $kode_pos, $status_utama, $id]);

            $pdo->commit();

            flash_set('success', "Alamat '{$label_alamat}' berhasil diperbarui!");
            header('Location: ' . base_url('modules/alamat_pelanggan/index.php?id_pelanggan=' . $id_pelanggan));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal memperbarui: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Alamat Pelanggan</span>
                <a href="<?= base_url('modules/alamat_pelanggan/index.php?id_pelanggan=' . $id_pelanggan) ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Pelanggan Pemilik Alamat <span class="text-danger">*</span></label>
                        <select name="id_pelanggan" class="form-select" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($daftar_pelanggan as $plg): ?>
                                <option value="<?= $plg['id_pelanggan'] ?>" <?= ($id_pelanggan === (int)$plg['id_pelanggan']) ? 'selected' : '' ?>>
                                    [<?= escape($plg['kode_pelanggan']) ?>] <?= escape($plg['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Label Alamat <span class="text-danger">*</span></label>
                            <input type="text" name="label_alamat" class="form-control" value="<?= escape($label_alamat) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Status Alamat Utama? <span class="text-danger">*</span></label>
                            <select name="status_utama" class="form-select" required>
                                <option value="tidak" <?= ($status_utama === 'tidak') ? 'selected' : '' ?>>Tidak</option>
                                <option value="ya" <?= ($status_utama === 'ya') ? 'selected' : '' ?>>Ya (Alamat Utama)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea name="alamat_lengkap" class="form-control" rows="3" required><?= escape($alamat_lengkap) ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Kota / Kabupaten <span class="text-danger">*</span></label>
                            <input type="text" name="kota" class="form-control" value="<?= escape($kota) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Provinsi <span class="text-danger">*</span></label>
                            <input type="text" name="provinsi" class="form-control" value="<?= escape($provinsi) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kode Pos <span class="text-danger">*</span></label>
                            <input type="text" name="kode_pos" class="form-control" value="<?= escape($kode_pos) ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/alamat_pelanggan/index.php?id_pelanggan=' . $id_pelanggan) ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Alamat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
