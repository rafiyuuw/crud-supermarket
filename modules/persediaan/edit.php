<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Persediaan (Edit & Penyesuaian Stok)
 * File: modules/persediaan/edit.php
 */

$judul_halaman = 'Penyesuaian Persediaan Stok';
$menu_aktif = 'persediaan';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT ps.*, pr.nama_produk, pr.kode_produk, g.nama_gudang, s.simbol AS satuan
    FROM persediaan ps
    INNER JOIN produk pr ON ps.id_produk = pr.id_produk
    INNER JOIN satuan_produk s ON pr.id_satuan = s.id_satuan
    INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
    WHERE ps.id_persediaan = ?
");
$stmt->execute([$id]);
$persediaan = $stmt->fetch();

if (!$persediaan) {
    flash_set('danger', 'Data persediaan tidak ditemukan.');
    header('Location: ' . base_url('modules/persediaan/index.php'));
    exit;
}

$daftar_lokasi = $pdo->prepare("SELECT id_lokasi, kode_lokasi, nama_lokasi FROM lokasi_gudang WHERE id_gudang = ? ORDER BY kode_lokasi ASC");
$daftar_lokasi->execute([$persediaan['id_gudang']]);
$opsi_lokasi = $daftar_lokasi->fetchAll();

$error = '';
$stok_sekarang = (int)$persediaan['jumlah_stok'];
$stok_baru = (int)($_POST['jumlah_stok'] ?? $stok_sekarang);
$stok_minimum = (int)($_POST['stok_minimum'] ?? $persediaan['stok_minimum']);
$id_lokasi = isset($_POST['id_lokasi']) ? (!empty($_POST['id_lokasi']) ? (int)$_POST['id_lokasi'] : null) : $persediaan['id_lokasi'];
$alasan_penyesuaian = trim($_POST['alasan_penyesuaian'] ?? 'Stock opname / penyesuaian fisik berkala');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $stok_baru = (int)($_POST['jumlah_stok'] ?? 0);
    $stok_minimum = max(0, (int)($_POST['stok_minimum'] ?? 5));
    $id_lokasi = !empty($_POST['id_lokasi']) ? (int)$_POST['id_lokasi'] : null;
    $alasan_penyesuaian = trim($_POST['alasan_penyesuaian'] ?? '');

    if ($stok_baru < 0) {
        $error = 'Stok tidak boleh bernilai negatif.';
    } else {
        try {
            $pdo->beginTransaction();

            $selisih = $stok_baru - $stok_sekarang;

            // 1. Perbarui persediaan
            $stmtUpdate = $pdo->prepare("
                UPDATE persediaan 
                SET jumlah_stok = ?, stok_minimum = ?, id_lokasi = ?, terakhir_diperbarui = NOW()
                WHERE id_persediaan = ?
            ");
            $stmtUpdate->execute([$stok_baru, $stok_minimum, $id_lokasi, $id]);

            // 2. Jika ada perubahan jumlah stok, catat di pergerakan_stok
            if ($selisih != 0) {
                $stmtLog = $pdo->prepare("
                    INSERT INTO pergerakan_stok 
                    (id_produk, id_gudang, id_karyawan, jenis_pergerakan, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, tanggal)
                    VALUES (?, ?, ?, 'penyesuaian', ?, ?, ?, 'OPNAME-MANUAL', ?, NOW())
                ");
                $stmtLog->execute([
                    $persediaan['id_produk'],
                    $persediaan['id_gudang'],
                    $user_aktif['id_karyawan'],
                    abs($selisih),
                    $stok_sekarang,
                    $stok_baru,
                    $alasan_penyesuaian ?: 'Penyesuaian stok opname manual'
                ]);
            }

            $pdo->commit();

            flash_set('success', "Persediaan untuk '<strong>" . escape($persediaan['nama_produk']) . "</strong>' berhasil diperbarui!");
            header('Location: ' . base_url('modules/persediaan/index.php'));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Penyesuaian Persediaan</span>
                <a href="<?= base_url('modules/persediaan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Produk & Lokasi:</small>
                            <h6 class="fw-bold mb-0 text-dark"><?= escape($persediaan['nama_produk']) ?></h6>
                            <small class="text-muted"><code><?= escape($persediaan['kode_produk']) ?></code> &bull; Fasilitas: <strong><?= escape($persediaan['nama_gudang']) ?></strong></small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Stok Tercatat:</small>
                            <span class="fs-5 fw-bold text-primary"><?= $stok_sekarang ?> <?= escape($persediaan['satuan']) ?></span>
                        </div>
                    </div>
                </div>

                <form action="" method="POST">
                    <?= csrf_input() ?>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Stok Fisik Baru (Hasil Hitung) <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="jumlah_stok" class="form-control fw-bold fs-6" value="<?= $stok_baru ?>" required>
                            <small class="text-muted">Perubahan akan tercatat otomatis di mutasi.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batas Stok Minimum <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="stok_minimum" class="form-control" value="<?= $stok_minimum ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Penempatan Rak / Bin di Gudang ini</label>
                        <select name="id_lokasi" class="form-select">
                            <option value="">-- Tanpa Rak Khusus --</option>
                            <?php foreach ($opsi_lokasi as $lok): ?>
                                <option value="<?= $lok['id_lokasi'] ?>" <?= ($id_lokasi === (int)$lok['id_lokasi']) ? 'selected' : '' ?>>
                                    <?= escape($lok['kode_lokasi']) ?> - <?= escape($lok['nama_lokasi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alasan Penyesuaian / Keterangan Opname</label>
                        <textarea name="alasan_penyesuaian" class="form-control" rows="2"><?= escape($alasan_penyesuaian) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/persediaan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Simpan Penyesuaian Stok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
