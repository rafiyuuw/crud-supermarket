<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Detail Penjualan (Edit Data)
 * File: modules/detail_penjualan/edit.php
 */

$judul_halaman = 'Edit Item Penjualan';
$menu_aktif = 'detail_penjualan';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM detail_penjualan WHERE id_detail_penjualan = ?");
$stmt->execute([$id]);
$detail = $stmt->fetch();

if (!$detail) {
    flash_set('danger', 'Data item penjualan tidak ditemukan.');
    header('Location: ' . base_url('modules/detail_penjualan/index.php'));
    exit;
}

$daftar_penjualan = $pdo->query("SELECT id_penjualan, nomor_faktur, tanggal_penjualan FROM penjualan ORDER BY id_penjualan DESC LIMIT 100")->fetchAll();
$daftar_produk = $pdo->query("SELECT id_produk, kode_produk, nama_produk, harga_jual FROM produk WHERE status = 'aktif' ORDER BY nama_produk ASC")->fetchAll();

$error = '';
$id_penjualan = (int)$detail['id_penjualan'];
$id_produk = (int)$detail['id_produk'];
$jumlah = (int)$detail['jumlah'];
$harga_satuan = $detail['harga_satuan'];
$diskon_item = $detail['diskon_item'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_penjualan_baru = (int)($_POST['id_penjualan'] ?? 0);
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
    $harga_satuan = max(0, (float)($_POST['harga_satuan'] ?? 0));
    $diskon_item = max(0, (float)($_POST['diskon_item'] ?? 0));

    if (empty($id_penjualan_baru) || empty($id_produk) || $harga_satuan <= 0) {
        $error = 'Faktur penjualan, produk, dan harga satuan wajib diisi dengan benar.';
    } else {
        $subtotal = max(0, ($harga_satuan * $jumlah) - $diskon_item);

        try {
            $pdo->beginTransaction();

            $id_penjualan_lama = (int)$detail['id_penjualan'];

            // 1. Update data baris detail penjualan
            $stmtUpdate = $pdo->prepare("
                UPDATE detail_penjualan 
                SET id_penjualan = ?, id_produk = ?, harga_satuan = ?, jumlah = ?, subtotal = ?, diskon_item = ?
                WHERE id_detail_penjualan = ?
            ");
            $stmtUpdate->execute([
                $id_penjualan_baru,
                $id_produk,
                $harga_satuan,
                $jumlah,
                $subtotal,
                $diskon_item,
                $id
            ]);

            // 2. Perbarui total header penjualan baru
            $stmtUpdateTotal = $pdo->prepare("
                UPDATE penjualan 
                SET total_harga = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?),
                    total_akhir = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?) - diskon + pajak
                WHERE id_penjualan = ?
            ");
            $stmtUpdateTotal->execute([$id_penjualan_baru, $id_penjualan_baru, $id_penjualan_baru]);

            // 3. Jika faktur berubah, perbarui juga header faktur lama
            if ($id_penjualan_baru !== $id_penjualan_lama) {
                $stmtUpdateTotal->execute([$id_penjualan_lama, $id_penjualan_lama, $id_penjualan_lama]);
            }

            $pdo->commit();

            flash_set('success', 'Item penjualan berhasil diperbarui!');
            header('Location: ' . base_url('modules/detail_penjualan/index.php?id_penjualan=' . $id_penjualan_baru));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal memperbarui item penjualan: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Edit Item Penjualan</h4>
                <p class="text-muted small mb-0">Ubah kuantitas, harga, diskon, atau faktur terkait.</p>
            </div>
            <a href="<?= base_url('modules/detail_penjualan/index.php?id_penjualan=' . $id_penjualan) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= escape($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <?= csrf_input() ?>

                    <div class="mb-3">
                        <label for="id_penjualan" class="form-label fw-semibold">Faktur Penjualan <span class="text-danger">*</span></label>
                        <select name="id_penjualan" id="id_penjualan" class="form-select" required>
                            <option value="">-- Pilih Faktur Penjualan --</option>
                            <?php foreach ($daftar_penjualan as $p): ?>
                                <option value="<?= $p['id_penjualan'] ?>" <?= ($id_penjualan === (int)$p['id_penjualan']) ? 'selected' : '' ?>>
                                    <?= escape($p['nomor_faktur']) ?> (<?= tanggal_indo($p['tanggal_penjualan'], true) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="id_produk" class="form-label fw-semibold">Produk <span class="text-danger">*</span></label>
                        <select name="id_produk" id="id_produk" class="form-select" required onchange="updateHargaProduk(this)">
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($daftar_produk as $pr): ?>
                                <option value="<?= $pr['id_produk'] ?>" data-harga="<?= $pr['harga_jual'] ?>" <?= ($id_produk === (int)$pr['id_produk']) ? 'selected' : '' ?>>
                                    <?= escape($pr['nama_produk']) ?> (Kode: <?= escape($pr['kode_produk']) ?>) - <?= rupiah($pr['harga_jual']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="harga_satuan" class="form-label fw-semibold">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="harga_satuan" id="harga_satuan" class="form-control" value="<?= escape($harga_satuan) ?>" placeholder="0.00" required oninput="hitungSubtotal()">
                        </div>
                        <div class="col-md-6">
                            <label for="jumlah" class="form-label fw-semibold">Jumlah (Kuantitas) <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="jumlah" id="jumlah" class="form-control" value="<?= escape($jumlah) ?>" required oninput="hitungSubtotal()">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="diskon_item" class="form-label fw-semibold">Diskon Item (Rp)</label>
                            <input type="number" step="0.01" min="0" name="diskon_item" id="diskon_item" class="form-control" value="<?= escape($diskon_item) ?>" placeholder="0.00" oninput="hitungSubtotal()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estimasi Subtotal</label>
                            <div class="form-control bg-light fw-bold text-primary fs-5" id="label_subtotal">
                                Rp 0
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= base_url('modules/detail_penjualan/index.php?id_penjualan=' . $id_penjualan) ?>" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateHargaProduk(select) {
    const option = select.options[select.selectedIndex];
    const harga = option.getAttribute('data-harga');
    if (harga) {
        document.getElementById('harga_satuan').value = parseFloat(harga).toFixed(2);
        hitungSubtotal();
    }
}

function hitungSubtotal() {
    const harga = parseFloat(document.getElementById('harga_satuan').value) || 0;
    const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
    const diskon = parseFloat(document.getElementById('diskon_item').value) || 0;

    let subtotal = (harga * jumlah) - diskon;
    if (subtotal < 0) subtotal = 0;

    document.getElementById('label_subtotal').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', hitungSubtotal);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
