<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Detail Pesanan Pembelian (Tambah Data)
 * File: modules/detail_pesanan_pembelian/tambah.php
 */

$judul_halaman = 'Tambah Item Pesanan Pembelian';
$menu_aktif = 'detail_pesanan_pembelian';

require_once __DIR__ . '/../../includes/header.php';

$daftar_po = $pdo->query("SELECT id_pesanan, nomor_pesanan FROM pesanan_pembelian ORDER BY id_pesanan DESC")->fetchAll();
$daftar_produk = $pdo->query("SELECT id_produk, kode_produk, nama_produk, harga_beli FROM produk WHERE status = 'aktif' ORDER BY nama_produk ASC")->fetchAll();

$error = '';
$id_pesanan = (int)($_POST['id_pesanan'] ?? ($_GET['id_pesanan'] ?? 0));
$id_produk = (int)($_POST['id_produk'] ?? 0);
$jumlah = (int)($_POST['jumlah'] ?? 1);
$harga_satuan = $_POST['harga_satuan'] ?? '';
$catatan = $_POST['catatan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_pesanan = (int)($_POST['id_pesanan'] ?? 0);
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
    $harga_satuan = (float)($_POST['harga_satuan'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($id_pesanan) || empty($id_produk) || $harga_satuan < 0) {
        $error = 'Nomor PO, produk, dan harga satuan wajib diisi.';
    } else {
        $subtotal = $jumlah * $harga_satuan;

        try {
            $pdo->beginTransaction();

            // 1. Simpan baris detail item
            $stmt = $pdo->prepare("
                INSERT INTO detail_pesanan_pembelian (id_pesanan, id_produk, jumlah, harga_satuan, subtotal, catatan)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pesanan, $id_produk, $jumlah, $harga_satuan, $subtotal, $catatan ?: null]);

            // 2. Perbarui total_nominal pada header pesanan_pembelian
            $stmtTotal = $pdo->prepare("
                UPDATE pesanan_pembelian 
                SET total_nominal = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_pesanan_pembelian WHERE id_pesanan = ?)
                WHERE id_pesanan = ?
            ");
            $stmtTotal->execute([$id_pesanan, $id_pesanan]);

            $pdo->commit();

            flash_set('success', 'Item produk berhasil ditambahkan ke dalam pesanan pembelian!');
            header('Location: ' . base_url('modules/detail_pesanan_pembelian/index.php?id_pesanan=' . $id_pesanan));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal menyimpan item: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Item Produk ke PO</span>
                <a href="<?= base_url('modules/detail_pesanan_pembelian/index.php' . ($id_pesanan ? '?id_pesanan=' . $id_pesanan : '')) ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Nomor Pesanan Pembelian (PO) <span class="text-danger">*</span></label>
                        <select name="id_pesanan" class="form-select" required>
                            <option value="">-- Pilih Dokumen PO --</option>
                            <?php foreach ($daftar_po as $po): ?>
                                <option value="<?= $po['id_pesanan'] ?>" <?= ($id_pesanan === (int)$po['id_pesanan']) ? 'selected' : '' ?>>
                                    PO: <?= escape($po['nomor_pesanan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produk yang Dipesan <span class="text-danger">*</span></label>
                        <select name="id_produk" id="selectProdukPO" class="form-select" required onchange="updateHargaBeli()">
                            <option value="" data-harga="0">-- Pilih Produk --</option>
                            <?php foreach ($daftar_produk as $p): ?>
                                <option value="<?= $p['id_produk'] ?>" data-harga="<?= (float)$p['harga_beli'] ?>" <?= ($id_produk === (int)$p['id_produk']) ? 'selected' : '' ?>>
                                    [<?= escape($p['kode_produk']) ?>] <?= escape($p['nama_produk']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Jumlah / Qty <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="jumlah" id="inputJumlah" class="form-control" value="<?= $jumlah ?>" required oninput="hitungSubtotal()">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Harga Satuan Beli (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="harga_satuan" id="inputHarga" class="form-control" value="<?= escape($harga_satuan) ?>" required oninput="hitungSubtotal()">
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted fw-semibold">Estimasi Subtotal:</span>
                            <span class="fs-5 fw-bold text-primary" id="labelSubtotal">Rp 0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Spesifik Item</label>
                        <input type="text" name="catatan" class="form-control" placeholder="Contoh: Kemasan karton isi 40" value="<?= escape($catatan) ?>">
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/detail_pesanan_pembelian/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Item PO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateHargaBeli() {
    const sel = document.getElementById('selectProdukPO');
    const selectedOption = sel.options[sel.selectedIndex];
    const harga = selectedOption.getAttribute('data-harga') || 0;
    document.getElementById('inputHarga').value = harga;
    hitungSubtotal();
}

function hitungSubtotal() {
    const qty = parseFloat(document.getElementById('inputJumlah').value) || 0;
    const harga = parseFloat(document.getElementById('inputHarga').value) || 0;
    const subtotal = qty * harga;
    document.getElementById('labelSubtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
}
window.addEventListener('DOMContentLoaded', hitungSubtotal);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
