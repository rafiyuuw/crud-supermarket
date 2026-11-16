<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pergerakan Stok (Edit Keterangan Log)
 * File: modules/pergerakan_stok/edit.php
 */

$judul_halaman = 'Edit Riwayat Pergerakan Stok';
$menu_aktif = 'pergerakan_stok';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT ps.*, pr.nama_produk, pr.kode_produk, g.nama_gudang, k.nama_karyawan
    FROM pergerakan_stok ps
    INNER JOIN produk pr ON ps.id_produk = pr.id_produk
    INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
    INNER JOIN karyawan k ON ps.id_karyawan = k.id_karyawan
    WHERE ps.id_pergerakan = ?
");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) {
    flash_set('danger', 'Data riwayat pergerakan tidak ditemukan.');
    header('Location: ' . base_url('modules/pergerakan_stok/index.php'));
    exit;
}

$error = '';
$referensi = $log['referensi'] ?? '';
$keterangan = $log['keterangan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $referensi = trim($_POST['referensi'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    try {
        $stmtUpdate = $pdo->prepare("UPDATE pergerakan_stok SET referensi = ?, keterangan = ? WHERE id_pergerakan = ?");
        $stmtUpdate->execute([$referensi ?: null, $keterangan ?: null, $id]);

        flash_set('success', 'Catatan riwayat pergerakan stok berhasil diperbarui!');
        header('Location: ' . base_url('modules/pergerakan_stok/index.php'));
        exit;

    } catch (PDOException $e) {
        $error = 'Gagal memperbarui: ' . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Ubah Catatan Pergerakan Stok</span>
                <a href="<?= base_url('modules/pergerakan_stok/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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

                <div class="alert alert-light border py-3 mb-3">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Produk:</small>
                            <span class="fw-bold"><?= escape($log['nama_produk']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Gudang:</small>
                            <span class="fw-bold"><?= escape($log['nama_gudang']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Jenis Mutasi:</small>
                            <?= badge_status($log['jenis_pergerakan']) ?>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Perubahan Stok:</small>
                            <span class="font-monospace fw-bold"><?= $log['stok_sebelum'] ?> &rarr; <?= $log['stok_sesudah'] ?> (Qty: <?= $log['jumlah'] ?>)</span>
                        </div>
                    </div>
                </div>

                <form action="" method="POST">
                    <?= csrf_input() ?>

                    <div class="mb-3">
                        <label class="form-label">Nomor Referensi Dokumen</label>
                        <input type="text" name="referensi" class="form-control" value="<?= escape($referensi) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan / Catatan Penjelasan</label>
                        <textarea name="keterangan" class="form-control" rows="3"><?= escape($keterangan) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/pergerakan_stok/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Catatan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
