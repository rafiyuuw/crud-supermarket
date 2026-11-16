<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Barcode Produk (Edit Data)
 * File: modules/barcode_produk/edit.php
 */

$judul_halaman = 'Edit Barcode Produk';
$menu_aktif = 'barcode_produk';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM barcode_produk WHERE id_barcode = ?");
$stmt->execute([$id]);
$bar = $stmt->fetch();

if (!$bar) {
    flash_set('danger', 'Data barcode tidak ditemukan.');
    header('Location: ' . base_url('modules/barcode_produk/index.php'));
    exit;
}

$daftar_produk = $pdo->query("SELECT id_produk, kode_produk, nama_produk FROM produk WHERE status = 'aktif' ORDER BY nama_produk ASC")->fetchAll();

$error = '';
$id_produk = (int)$bar['id_produk'];
$kode_barcode = $bar['kode_barcode'];
$jenis_barcode = $bar['jenis_barcode'];
$status = $bar['status'];
$catatan = $bar['catatan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $kode_barcode = trim($_POST['kode_barcode'] ?? '');
    $jenis_barcode = $_POST['jenis_barcode'] ?? 'EAN-13';
    $status = $_POST['status'] ?? 'utama';
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($id_produk) || empty($kode_barcode)) {
        $error = 'Produk dan kode barcode wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM barcode_produk WHERE kode_barcode = ? AND id_barcode != ?");
        $stmtCek->execute([$kode_barcode, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode barcode '{$kode_barcode}' sudah digunakan pada data lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE barcode_produk SET id_produk = ?, kode_barcode = ?, jenis_barcode = ?, status = ?, catatan = ? WHERE id_barcode = ?");
                $stmtUpdate->execute([$id_produk, $kode_barcode, $jenis_barcode, $status, $catatan ?: null, $id]);

                flash_set('success', "Barcode <strong>" . escape($kode_barcode) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/barcode_produk/index.php?id_produk=' . $id_produk));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Barcode</span>
                <a href="<?= base_url('modules/barcode_produk/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Produk Terkait <span class="text-danger">*</span></label>
                        <select name="id_produk" class="form-select" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($daftar_produk as $p): ?>
                                <option value="<?= $p['id_produk'] ?>" <?= ($id_produk === (int)$p['id_produk']) ? 'selected' : '' ?>>
                                    [<?= escape($p['kode_produk']) ?>] <?= escape($p['nama_produk']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor / Kode Barcode <span class="text-danger">*</span></label>
                        <input type="text" name="kode_barcode" class="form-control" value="<?= escape($kode_barcode) ?>" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Jenis / Format Barcode <span class="text-danger">*</span></label>
                            <select name="jenis_barcode" class="form-select" required>
                                <option value="EAN-13" <?= ($jenis_barcode === 'EAN-13') ? 'selected' : '' ?>>EAN-13</option>
                                <option value="UPC-A" <?= ($jenis_barcode === 'UPC-A') ? 'selected' : '' ?>>UPC-A</option>
                                <option value="Code-128" <?= ($jenis_barcode === 'Code-128') ? 'selected' : '' ?>>Code-128</option>
                                <option value="QR-Code" <?= ($jenis_barcode === 'QR-Code') ? 'selected' : '' ?>>QR-Code</option>
                                <option value="Lainnya" <?= ($jenis_barcode === 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Status Barcode <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="utama" <?= ($status === 'utama') ? 'selected' : '' ?>>Utama (Primer)</option>
                                <option value="tambahan" <?= ($status === 'tambahan') ? 'selected' : '' ?>>Tambahan</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="catatan" class="form-control" value="<?= escape($catatan) ?>">
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/barcode_produk/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Barcode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
