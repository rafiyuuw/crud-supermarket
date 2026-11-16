<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Penjualan (Tambah Data Manual)
 * File: modules/penjualan/tambah.php
 */

$judul_halaman = 'Tambah Penjualan';
$menu_aktif = 'penjualan';

require_once __DIR__ . '/../../includes/header.php';

$daftar_cabang = $pdo->query("SELECT id_cabang, nama_cabang FROM cabang WHERE status = 'aktif' ORDER BY nama_cabang ASC")->fetchAll();
$daftar_pelanggan = $pdo->query("SELECT id_pelanggan, kode_pelanggan, nama_lengkap FROM pelanggan WHERE status = 'aktif' ORDER BY nama_lengkap ASC")->fetchAll();
$daftar_karyawan = $pdo->query("SELECT id_karyawan, nama_karyawan FROM karyawan WHERE status = 'aktif' ORDER BY nama_karyawan ASC")->fetchAll();

$error = '';
$nomor_faktur = 'INV-' . date('Ymd') . '-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);
$id_cabang = (int)($_POST['id_cabang'] ?? $user_aktif['id_cabang']);
$id_pelanggan = !empty($_POST['id_pelanggan']) ? (int)$_POST['id_pelanggan'] : null;
$id_karyawan = (int)($_POST['id_karyawan'] ?? $user_aktif['id_karyawan']);
$tanggal_penjualan = $_POST['tanggal_penjualan'] ?? date('Y-m-d\TH:i');
$total_harga = (float)($_POST['total_harga'] ?? 0);
$diskon = (float)($_POST['diskon'] ?? 0);
$pajak = (float)($_POST['pajak'] ?? 0);
$status_pembayaran = $_POST['status_pembayaran'] ?? 'lunas';
$catatan = $_POST['catatan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nomor_faktur = trim($_POST['nomor_faktur'] ?? '');
    $id_cabang = (int)($_POST['id_cabang'] ?? 0);
    $id_pelanggan = !empty($_POST['id_pelanggan']) ? (int)$_POST['id_pelanggan'] : null;
    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $tanggal_penjualan = $_POST['tanggal_penjualan'] ?? '';
    $total_harga = max(0, (float)($_POST['total_harga'] ?? 0));
    $diskon = max(0, (float)($_POST['diskon'] ?? 0));
    $pajak = max(0, (float)($_POST['pajak'] ?? 0));
    $total_akhir = $total_harga - $diskon + $pajak;
    $status_pembayaran = $_POST['status_pembayaran'] ?? 'lunas';
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($nomor_faktur) || empty($id_cabang) || empty($id_karyawan) || empty($tanggal_penjualan)) {
        $error = 'Nomor faktur, cabang, kasir, dan tanggal penjualan wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM penjualan WHERE nomor_faktur = ?");
        $stmtCek->execute([$nomor_faktur]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Nomor faktur '{$nomor_faktur}' sudah terdaftar.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO penjualan 
                    (nomor_faktur, id_cabang, id_pelanggan, id_karyawan, tanggal_penjualan, total_harga, diskon, pajak, total_akhir, status_pembayaran, catatan)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nomor_faktur,
                    $id_cabang,
                    $id_pelanggan,
                    $id_karyawan,
                    $tanggal_penjualan,
                    $total_harga,
                    $diskon,
                    $pajak,
                    $total_akhir,
                    $status_pembayaran,
                    $catatan ?: null
                ]);
                $id_baru = (int)$pdo->lastInsertId();

                flash_set('success', "Transaksi penjualan <strong>" . escape($nomor_faktur) . "</strong> berhasil disimpan!");
                header('Location: ' . base_url('modules/detail_penjualan/index.php?id_penjualan=' . $id_baru));
                exit;

            } catch (PDOException $e) {
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="alert alert-info py-2 small d-flex justify-content-between align-items-center mb-3 shadow-sm">
            <div>
                <i class="bi bi-info-circle-fill me-1"></i> Untuk transaksi kasir cepat otomatis dengan barcode & hitung kembalian, gunakan menu Kasir POS.
            </div>
            <a href="<?= base_url('pos.php') ?>" class="btn btn-primary btn-sm py-0 px-2 text-nowrap">Buka POS Kasir</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Data Penjualan</span>
                <a href="<?= base_url('modules/penjualan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                            <label class="form-label">Nomor Faktur / Invoice <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_faktur" class="form-control" value="<?= escape($nomor_faktur) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Waktu Transaksi <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="tanggal_penjualan" class="form-control" value="<?= escape($tanggal_penjualan) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Cabang Transaksi <span class="text-danger">*</span></label>
                            <select name="id_cabang" class="form-select" required>
                                <?php foreach ($daftar_cabang as $c): ?>
                                    <option value="<?= $c['id_cabang'] ?>" <?= ($id_cabang === (int)$c['id_cabang']) ? 'selected' : '' ?>>
                                        <?= escape($c['nama_cabang']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Petugas Kasir <span class="text-danger">*</span></label>
                            <select name="id_karyawan" class="form-select" required>
                                <?php foreach ($daftar_karyawan as $kry): ?>
                                    <option value="<?= $kry['id_karyawan'] ?>" <?= ($id_karyawan === (int)$kry['id_karyawan']) ? 'selected' : '' ?>>
                                        <?= escape($kry['nama_karyawan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pelanggan Member (Opsional)</label>
                            <select name="id_pelanggan" class="form-select">
                                <option value="">Pelanggan Umum (Non-Member)</option>
                                <?php foreach ($daftar_pelanggan as $plg): ?>
                                    <option value="<?= $plg['id_pelanggan'] ?>" <?= ($id_pelanggan === (int)$plg['id_pelanggan']) ? 'selected' : '' ?>>
                                        [<?= escape($plg['kode_pelanggan']) ?>] <?= escape($plg['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Pembayaran <span class="text-danger">*</span></label>
                            <select name="status_pembayaran" class="form-select" required>
                                <option value="lunas" <?= ($status_pembayaran === 'lunas') ? 'selected' : '' ?>>Lunas</option>
                                <option value="belum_lunas" <?= ($status_pembayaran === 'belum_lunas') ? 'selected' : '' ?>>Belum Lunas</option>
                                <option value="batal" <?= ($status_pembayaran === 'batal') ? 'selected' : '' ?>>Batal</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Total Harga Barang (Rp)</label>
                            <input type="number" step="0.01" min="0" name="total_harga" id="totalHarga" class="form-control" value="<?= $total_harga ?>" oninput="hitungTotalAkhir()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Diskon Transaksi (Rp)</label>
                            <input type="number" step="0.01" min="0" name="diskon" id="diskon" class="form-control" value="<?= $diskon ?>" oninput="hitungTotalAkhir()">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Pajak Transaksi (Rp)</label>
                            <input type="number" step="0.01" min="0" name="pajak" id="pajak" class="form-control" value="<?= $pajak ?>" oninput="hitungTotalAkhir()">
                        </div>

                        <div class="col-12 p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-muted">Total Akhir Pembayaran:</span>
                                <span class="fs-4 fw-bold text-primary" id="labelTotalAkhir">Rp 0</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="catatan" class="form-control" rows="2"><?= escape($catatan) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/penjualan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Penjualan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function hitungTotalAkhir() {
    let harga = parseFloat(document.getElementById('totalHarga').value) || 0;
    let disc = parseFloat(document.getElementById('diskon').value) || 0;
    let pjk = parseFloat(document.getElementById('pajak').value) || 0;
    let total = Math.max(0, harga - disc + pjk);
    document.getElementById('labelTotalAkhir').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
window.addEventListener('DOMContentLoaded', hitungTotalAkhir);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
