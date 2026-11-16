<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pesanan Pembelian (Tambah Data)
 * File: modules/pesanan_pembelian/tambah.php
 */

$judul_halaman = 'Tambah Pesanan Pembelian (PO)';
$menu_aktif = 'pesanan_pembelian';

require_once __DIR__ . '/../../includes/header.php';

$daftar_pemasok = $pdo->query("SELECT id_pemasok, nama_pemasok FROM pemasok WHERE status = 'aktif' ORDER BY nama_pemasok ASC")->fetchAll();
$daftar_karyawan = $pdo->query("SELECT id_karyawan, nama_karyawan FROM karyawan WHERE status = 'aktif' ORDER BY nama_karyawan ASC")->fetchAll();
$daftar_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang WHERE status = 'aktif' ORDER BY nama_gudang ASC")->fetchAll();

$error = '';
$nomor_pesanan = 'PO-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
$id_pemasok = (int)($_POST['id_pemasok'] ?? 0);
$id_karyawan = (int)($_POST['id_karyawan'] ?? $user_aktif['id_karyawan']);
$id_gudang = (int)($_POST['id_gudang'] ?? 1);
$tanggal_pesanan = $_POST['tanggal_pesanan'] ?? date('Y-m-d');
$tanggal_estimasi = $_POST['tanggal_estimasi'] ?? date('Y-m-d', strtotime('+3 days'));
$status = $_POST['status'] ?? 'draft';
$catatan = $_POST['catatan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nomor_pesanan = trim($_POST['nomor_pesanan'] ?? '');
    $id_pemasok = (int)($_POST['id_pemasok'] ?? 0);
    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $id_gudang = (int)($_POST['id_gudang'] ?? 0);
    $tanggal_pesanan = $_POST['tanggal_pesanan'] ?? '';
    $tanggal_estimasi = $_POST['tanggal_estimasi'] ?? null;
    $status = $_POST['status'] ?? 'draft';
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($nomor_pesanan) || empty($id_pemasok) || empty($id_karyawan) || empty($id_gudang) || empty($tanggal_pesanan)) {
        $error = 'Seluruh kolom bertanda bintang (*) wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM pesanan_pembelian WHERE nomor_pesanan = ?");
        $stmtCek->execute([$nomor_pesanan]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Nomor pesanan '{$nomor_pesanan}' sudah digunakan.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO pesanan_pembelian 
                    (nomor_pesanan, id_pemasok, id_karyawan, id_gudang, tanggal_pesanan, tanggal_estimasi, total_nominal, status, catatan)
                    VALUES (?, ?, ?, ?, ?, ?, 0.00, ?, ?)
                ");
                $stmt->execute([
                    $nomor_pesanan,
                    $id_pemasok,
                    $id_karyawan,
                    $id_gudang,
                    $tanggal_pesanan,
                    $tanggal_estimasi ?: null,
                    $status,
                    $catatan ?: null
                ]);
                $id_baru = (int)$pdo->lastInsertId();

                flash_set('success', "Pesanan pembelian <strong>" . escape($nomor_pesanan) . "</strong> berhasil dibuat! Silakan tambahkan rincian produk yang dipesan.");
                header('Location: ' . base_url('modules/detail_pesanan_pembelian/index.php?id_pesanan=' . $id_baru));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan pesanan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Buat Pesanan Pembelian (PO)</span>
                <a href="<?= base_url('modules/pesanan_pembelian/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                            <label class="form-label">Petugas Pembelian (Staff) <span class="text-danger">*</span></label>
                            <select name="id_karyawan" class="form-select" required>
                                <?php foreach ($daftar_karyawan as $kry): ?>
                                    <option value="<?= $kry['id_karyawan'] ?>" <?= ($id_karyawan === (int)$kry['id_karyawan']) ? 'selected' : '' ?>>
                                        <?= escape($kry['nama_karyawan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tujuan Penerimaan Gudang <span class="text-danger">*</span></label>
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
                                <option value="diterima" <?= ($status === 'diterima') ? 'selected' : '' ?>>Diterima</option>
                                <option value="dibatalkan" <?= ($status === 'dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                            <small class="text-muted">Status 'Diterima' akan otomatis memperbarui stok gudang.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan Pemesanan</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan instruksi pengiriman atau term pembayaran"><?= escape($catatan) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/pesanan_pembelian/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Lanjut Tambah Rincian Item &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
