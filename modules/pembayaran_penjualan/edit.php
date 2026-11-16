<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pembayaran Penjualan (Edit Data)
 * File: modules/pembayaran_penjualan/edit.php
 */

$judul_halaman = 'Edit Pembayaran Penjualan';
$menu_aktif = 'pembayaran_penjualan';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM pembayaran_penjualan WHERE id_pembayaran = ?");
$stmt->execute([$id]);
$bayar = $stmt->fetch();

if (!$bayar) {
    flash_set('danger', 'Data pembayaran tidak ditemukan.');
    header('Location: ' . base_url('modules/pembayaran_penjualan/index.php'));
    exit;
}

$daftar_penjualan = $pdo->query("SELECT id_penjualan, nomor_faktur, total_akhir, status_pembayaran FROM penjualan ORDER BY id_penjualan DESC LIMIT 100")->fetchAll();
$daftar_metode = $pdo->query("SELECT id_metode, nama_metode, tipe FROM metode_pembayaran WHERE status = 'aktif' ORDER BY nama_metode ASC")->fetchAll();

$error = '';
$id_penjualan = (int)$bayar['id_penjualan'];
$id_metode = (int)$bayar['id_metode'];
$nomor_transaksi = $bayar['nomor_transaksi'] ?? '';
$jumlah_bayar = $bayar['jumlah_bayar'];
$jumlah_kembalian = $bayar['jumlah_kembalian'];
$tanggal_bayar = date('Y-m-d\TH:i', strtotime($bayar['tanggal_bayar']));
$status = $bayar['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_penjualan = (int)($_POST['id_penjualan'] ?? 0);
    $id_metode = (int)($_POST['id_metode'] ?? 0);
    $nomor_transaksi = trim($_POST['nomor_transaksi'] ?? '');
    $jumlah_bayar = max(0, (float)($_POST['jumlah_bayar'] ?? 0));
    $jumlah_kembalian = max(0, (float)($_POST['jumlah_kembalian'] ?? 0));
    $tanggal_bayar = $_POST['tanggal_bayar'] ?? date('Y-m-d H:i:s');
    $status = $_POST['status'] ?? 'berhasil';

    if (empty($id_penjualan) || empty($id_metode) || $jumlah_bayar <= 0) {
        $error = 'Faktur penjualan, metode pembayaran, dan jumlah bayar wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtUpdate = $pdo->prepare("
                UPDATE pembayaran_penjualan 
                SET id_penjualan = ?, id_metode = ?, nomor_transaksi = ?, jumlah_bayar = ?, jumlah_kembalian = ?, tanggal_bayar = ?, status = ?
                WHERE id_pembayaran = ?
            ");
            $stmtUpdate->execute([
                $id_penjualan,
                $id_metode,
                $nomor_transaksi ?: null,
                $jumlah_bayar,
                $jumlah_kembalian,
                $tanggal_bayar,
                $status,
                $id
            ]);

            // Sinkronisasi status pembayaran pada header faktur penjualan
            if ($status === 'berhasil') {
                $stmtCek = $pdo->prepare("SELECT total_akhir FROM penjualan WHERE id_penjualan = ?");
                $stmtCek->execute([$id_penjualan]);
                $total_akhir = (float)$stmtCek->fetchColumn();

                $stmtTotalBayar = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_penjualan WHERE id_penjualan = ? AND status = 'berhasil'");
                $stmtTotalBayar->execute([$id_penjualan]);
                $total_terbayar = (float)$stmtTotalBayar->fetchColumn();

                if ($total_terbayar >= $total_akhir) {
                    $stmtUpdatePJ = $pdo->prepare("UPDATE penjualan SET status_pembayaran = 'lunas' WHERE id_penjualan = ?");
                    $stmtUpdatePJ->execute([$id_penjualan]);
                }
            }

            $pdo->commit();

            flash_set('success', 'Data pembayaran penjualan berhasil diperbarui!');
            header('Location: ' . base_url('modules/pembayaran_penjualan/index.php?id_penjualan=' . $id_penjualan));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal memperbarui pembayaran: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Edit Pembayaran Penjualan</h4>
                <p class="text-muted small mb-0">Ubah rincian pembayaran atau status verifikasi pembayaran transaksi.</p>
            </div>
            <a href="<?= base_url('modules/pembayaran_penjualan/index.php?id_penjualan=' . $id_penjualan) ?>" class="btn btn-outline-secondary btn-sm">
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
                        <select name="id_penjualan" id="id_penjualan" class="form-select" required onchange="updateTotalFaktur(this)">
                            <option value="">-- Pilih Faktur Penjualan --</option>
                            <?php foreach ($daftar_penjualan as $p): ?>
                                <option value="<?= $p['id_penjualan'] ?>" data-total="<?= $p['total_akhir'] ?>" <?= ($id_penjualan === (int)$p['id_penjualan']) ? 'selected' : '' ?>>
                                    <?= escape($p['nomor_faktur']) ?> - Tagihan: <?= rupiah($p['total_akhir']) ?> (<?= ucfirst($p['status_pembayaran']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="id_metode" class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="id_metode" id="id_metode" class="form-select" required>
                                <option value="">-- Pilih Metode --</option>
                                <?php foreach ($daftar_metode as $m): ?>
                                    <option value="<?= $m['id_metode'] ?>" <?= ($id_metode === (int)$m['id_metode']) ? 'selected' : '' ?>>
                                        <?= escape($m['nama_metode']) ?> (<?= ucfirst($m['tipe']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="nomor_transaksi" class="form-label fw-semibold">No. Transaksi / Referensi</label>
                            <input type="text" name="nomor_transaksi" id="nomor_transaksi" class="form-control" value="<?= escape($nomor_transaksi) ?>" placeholder="Contoh: TRX-CASH-001, REF-QRIS-99">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="jumlah_bayar" class="form-label fw-semibold">Jumlah Bayar (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="jumlah_bayar" id="jumlah_bayar" class="form-control" value="<?= escape($jumlah_bayar) ?>" required placeholder="0.00" oninput="hitungKembalian()">
                        </div>
                        <div class="col-md-6">
                            <label for="jumlah_kembalian" class="form-label fw-semibold">Kembalian (Rp)</label>
                            <input type="number" step="0.01" min="0" name="jumlah_kembalian" id="jumlah_kembalian" class="form-control" value="<?= escape($jumlah_kembalian) ?>" placeholder="0.00">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="tanggal_bayar" class="form-label fw-semibold">Tanggal & Waktu Bayar <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="tanggal_bayar" id="tanggal_bayar" class="form-control" value="<?= escape($tanggal_bayar) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Status Pembayaran <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="berhasil" <?= ($status === 'berhasil') ? 'selected' : '' ?>>Berhasil</option>
                                <option value="menunggu" <?= ($status === 'menunggu') ? 'selected' : '' ?>>Menunggu</option>
                                <option value="gagal" <?= ($status === 'gagal') ? 'selected' : '' ?>>Gagal</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= base_url('modules/pembayaran_penjualan/index.php?id_penjualan=' . $id_penjualan) ?>" class="btn btn-secondary">Batal</a>
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
let totalTagihanFaktur = 0;

function updateTotalFaktur(select) {
    const opt = select.options[select.selectedIndex];
    totalTagihanFaktur = parseFloat(opt.getAttribute('data-total')) || 0;
    hitungKembalian();
}

function hitungKembalian() {
    const bayar = parseFloat(document.getElementById('jumlah_bayar').value) || 0;
    const kembalian = Math.max(0, bayar - totalTagihanFaktur);
    document.getElementById('jumlah_kembalian').value = kembalian.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('id_penjualan');
    if (sel && sel.selectedIndex >= 0) {
        updateTotalFaktur(sel);
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
