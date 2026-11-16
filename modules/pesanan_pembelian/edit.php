<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pesanan Pembelian (Edit Data)
 * File: modules/pesanan_pembelian/edit.php
 */

$judul_halaman = 'Edit Pesanan Pembelian (PO)';
$menu_aktif = 'pesanan_pembelian';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM pesanan_pembelian WHERE id_pesanan = ?");
$stmt->execute([$id]);
$po = $stmt->fetch();

if (!$po) {
    flash_set('danger', 'Data pesanan pembelian tidak ditemukan.');
    header('Location: ' . base_url('modules/pesanan_pembelian/index.php'));
    exit;
}

$daftar_pemasok = $pdo->query("SELECT id_pemasok, nama_pemasok FROM pemasok WHERE status = 'aktif' ORDER BY nama_pemasok ASC")->fetchAll();
$daftar_karyawan = $pdo->query("SELECT id_karyawan, nama_karyawan FROM karyawan WHERE status = 'aktif' ORDER BY nama_karyawan ASC")->fetchAll();
$daftar_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang WHERE status = 'aktif' ORDER BY nama_gudang ASC")->fetchAll();

$error = '';
$nomor_pesanan = $po['nomor_pesanan'];
$id_pemasok = (int)$po['id_pemasok'];
$id_karyawan = (int)$po['id_karyawan'];
$id_gudang = (int)$po['id_gudang'];
$tanggal_pesanan = $po['tanggal_pesanan'];
$tanggal_estimasi = $po['tanggal_estimasi'] ?? '';
$status_lama = $po['status'];
$status = $po['status'];
$catatan = $po['catatan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nomor_pesanan = trim($_POST['nomor_pesanan'] ?? '');
    $id_pemasok = (int)($_POST['id_pemasok'] ?? 0);
    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $id_gudang = (int)($_POST['id_gudang'] ?? 0);
    $tanggal_pesanan = $_POST['tanggal_pesanan'] ?? '';
    $tanggal_estimasi = $_POST['tanggal_estimasi'] ?? null;
    $status_baru = $_POST['status'] ?? 'draft';
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($nomor_pesanan) || empty($id_pemasok) || empty($id_karyawan) || empty($id_gudang) || empty($tanggal_pesanan)) {
        $error = 'Seluruh kolom bertanda bintang (*) wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM pesanan_pembelian WHERE nomor_pesanan = ? AND id_pesanan != ?");
        $stmtCek->execute([$nomor_pesanan, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Nomor pesanan '{$nomor_pesanan}' sudah digunakan pada PO lain.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Update header PO
                $stmtUpdate = $pdo->prepare("
                    UPDATE pesanan_pembelian 
                    SET nomor_pesanan = ?, id_pemasok = ?, id_karyawan = ?, id_gudang = ?, 
                        tanggal_pesanan = ?, tanggal_estimasi = ?, status = ?, catatan = ?
                    WHERE id_pesanan = ?
                ");
                $stmtUpdate->execute([
                    $nomor_pesanan,
                    $id_pemasok,
                    $id_karyawan,
                    $id_gudang,
                    $tanggal_pesanan,
                    $tanggal_estimasi ?: null,
                    $status_baru,
                    $catatan ?: null,
                    $id
                ]);

                // 2. Logika Penerimaan Barang: Jika status berubah menjadi 'diterima'
                if ($status_lama !== 'diterima' && $status_baru === 'diterima') {
                    // Ambil seluruh detail item PO
                    $stmtItems = $pdo->prepare("
                        SELECT dp.*, pr.nama_produk 
                        FROM detail_pesanan_pembelian dp
                        INNER JOIN produk pr ON dp.id_produk = pr.id_produk
                        WHERE dp.id_pesanan = ?
                    ");
                    $stmtItems->execute([$id]);
                    $items_po = $stmtItems->fetchAll();

                    foreach ($items_po as $item) {
                        update_stok_persediaan(
                            $pdo,
                            $item['id_produk'],
                            $id_gudang,
                            $item['jumlah'],
                            'masuk',
                            $user_aktif['id_karyawan'],
                            $nomor_pesanan,
                            "Penerimaan barang dari PO {$nomor_pesanan} ({$item['nama_produk']} x {$item['jumlah']})"
                        );
                    }
                }

                $pdo->commit();

                flash_set('success', "Pesanan pembelian <strong>" . escape($nomor_pesanan) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/pesanan_pembelian/index.php'));
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Pesanan Pembelian</span>
                <div>
                    <a href="<?= base_url('modules/detail_pesanan_pembelian/index.php?id_pesanan=' . $id) ?>" class="btn btn-outline-info btn-sm me-1">
                        <i class="bi bi-list-task"></i> Rincian Barang
                    </a>
                    <a href="<?= base_url('modules/pesanan_pembelian/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
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
                        <div class="col-md-6">
                            <label class="form-label">Nomor Pesanan (PO) <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_pesanan" class="form-control" value="<?= escape($nomor_pesanan) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pemasok Mitra <span class="text-danger">*</span></label>
                            <select name="id_pemasok" class="form-select" required>
                                <option value="">-- Pilih Pemasok --</option>
                                <?php foreach ($daftar_pemasok as $spl): ?>
                                    <option value="<?= $spl['id_pemasok'] ?>" <?= ($id_pemasok === (int)$spl['id_pemasok']) ? 'selected' : '' ?>>
                                        <?= escape($spl['nama_pemasok']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Petugas Pembelian <span class="text-danger">*</span></label>
                            <select name="id_karyawan" class="form-select" required>
                                <?php foreach ($daftar_karyawan as $kry): ?>
                                    <option value="<?= $kry['id_karyawan'] ?>" <?= ($id_karyawan === (int)$kry['id_karyawan']) ? 'selected' : '' ?>>
                                        <?= escape($kry['nama_karyawan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tujuan Gudang <span class="text-danger">*</span></label>
                            <select name="id_gudang" class="form-select" required>
                                <?php foreach ($daftar_gudang as $gdg): ?>
                                    <option value="<?= $gdg['id_gudang'] ?>" <?= ($id_gudang === (int)$gdg['id_gudang']) ? 'selected' : '' ?>>
                                        <?= escape($gdg['nama_gudang']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pemesanan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_pesanan" class="form-control" value="<?= escape($tanggal_pesanan) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estimasi Tanggal Tiba</label>
                            <input type="date" name="tanggal_estimasi" class="form-control" value="<?= escape($tanggal_estimasi) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Pesanan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Draft</option>
                                <option value="dipesan" <?= ($status === 'dipesan') ? 'selected' : '' ?>>Dipesan</option>
                                <option value="diterima" <?= ($status === 'diterima') ? 'selected' : '' ?>>Diterima (Restok Masuk Gudang)</option>
                                <option value="dibatalkan" <?= ($status === 'dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                            <small class="text-muted">Mengubah ke 'Diterima' akan otomatis menambahkan stok ke persediaan gudang.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan Pemesanan</label>
                            <textarea name="catatan" class="form-control" rows="2"><?= escape($catatan) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/pesanan_pembelian/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Data PO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
