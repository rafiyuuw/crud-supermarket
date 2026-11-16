<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pergerakan Stok (Tambah Data Manual)
 * File: modules/pergerakan_stok/tambah.php
 */

$judul_halaman = 'Catat Pergerakan Stok Manual';
$menu_aktif = 'pergerakan_stok';

require_once __DIR__ . '/../../includes/header.php';

$daftar_produk = $pdo->query("SELECT id_produk, kode_produk, nama_produk FROM produk WHERE status = 'aktif' ORDER BY nama_produk ASC")->fetchAll();
$daftar_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang WHERE status = 'aktif' ORDER BY nama_gudang ASC")->fetchAll();

$error = '';
$id_produk = (int)($_POST['id_produk'] ?? 0);
$id_gudang = (int)($_POST['id_gudang'] ?? 1);
$jenis_pergerakan = $_POST['jenis_pergerakan'] ?? 'masuk';
$jumlah = (int)($_POST['jumlah'] ?? 1);
$referensi = $_POST['referensi'] ?? '';
$keterangan = $_POST['keterangan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $id_gudang = (int)($_POST['id_gudang'] ?? 0);
    $jenis_pergerakan = $_POST['jenis_pergerakan'] ?? 'masuk';
    $jumlah = abs((int)($_POST['jumlah'] ?? 0));
    $referensi = trim($_POST['referensi'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($id_produk) || empty($id_gudang) || $jumlah <= 0) {
        $error = 'Produk, gudang, dan kuantitas pergerakan wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            // Gunakan helper logika bisnis untuk memperbarui persediaan dan mencatat log
            update_stok_persediaan(
                $pdo,
                $id_produk,
                $id_gudang,
                $jumlah,
                $jenis_pergerakan,
                $user_aktif['id_karyawan'],
                $referensi ?: 'MUTASI-MANUAL',
                $keterangan ?: 'Pencatatan pergerakan mutasi stok manual'
            );

            $pdo->commit();

            flash_set('success', 'Mutasi pergerakan stok berhasil dicatat dan persediaan gudang telah diperbarui!');
            header('Location: ' . base_url('modules/pergerakan_stok/index.php'));
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal mencatat mutasi: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-arrow-left-right text-primary me-2"></i>Catat Mutasi Stok Manual</span>
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

                <form action="" method="POST">
                    <?= csrf_input() ?>

                    <div class="mb-3">
                        <label class="form-label">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="id_produk" class="form-select" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($daftar_produk as $p): ?>
                                <option value="<?= $p['id_produk'] ?>" <?= ($id_produk === (int)$p['id_produk']) ? 'selected' : '' ?>>
                                    [<?= escape($p['kode_produk']) ?>] <?= escape($p['nama_produk']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Gudang Penyimpanan <span class="text-danger">*</span></label>
                            <select name="id_gudang" class="form-select" required>
                                <?php foreach ($daftar_gudang as $gdg): ?>
                                    <option value="<?= $gdg['id_gudang'] ?>" <?= ($id_gudang === (int)$gdg['id_gudang']) ? 'selected' : '' ?>>
                                        <?= escape($gdg['nama_gudang']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Jenis Mutasi <span class="text-danger">*</span></label>
                            <select name="jenis_pergerakan" class="form-select" required>
                                <option value="masuk" <?= ($jenis_pergerakan === 'masuk') ? 'selected' : '' ?>>Masuk (Penambahan Stok)</option>
                                <option value="keluar" <?= ($jenis_pergerakan === 'keluar') ? 'selected' : '' ?>>Keluar (Pengurangan Stok)</option>
                                <option value="retur" <?= ($jenis_pergerakan === 'retur') ? 'selected' : '' ?>>Retur Barang (Masuk Kembali)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Jumlah / Qty Mutasi <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="jumlah" class="form-control fw-bold" value="<?= $jumlah ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Nomor Referensi / Surat Jalan</label>
                            <input type="text" name="referensi" class="form-control" placeholder="Contoh: RET-2026-001" value="<?= escape($referensi) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan / Alasan Mutasi</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Jelaskan alasan pemindahan atau retur barang"><?= escape($keterangan) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/pergerakan_stok/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Mutasi Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
