<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Persediaan (Tambah Data)
 * File: modules/persediaan/tambah.php
 */

$judul_halaman = 'Tambah Persediaan Stok';
$menu_aktif = 'persediaan';

require_once __DIR__ . '/../../includes/header.php';

$daftar_produk = $pdo->query("SELECT id_produk, kode_produk, nama_produk FROM produk WHERE status = 'aktif' ORDER BY nama_produk ASC")->fetchAll();
$daftar_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang WHERE status = 'aktif' ORDER BY nama_gudang ASC")->fetchAll();
$daftar_lokasi = $pdo->query("SELECT id_lokasi, id_gudang, kode_lokasi, nama_lokasi FROM lokasi_gudang ORDER BY kode_lokasi ASC")->fetchAll();

$error = '';
$id_produk = (int)($_POST['id_produk'] ?? 0);
$id_gudang = (int)($_POST['id_gudang'] ?? 1);
$id_lokasi = !empty($_POST['id_lokasi']) ? (int)$_POST['id_lokasi'] : null;
$jumlah_stok = (int)($_POST['jumlah_stok'] ?? 0);
$stok_minimum = (int)($_POST['stok_minimum'] ?? 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $id_gudang = (int)($_POST['id_gudang'] ?? 0);
    $id_lokasi = !empty($_POST['id_lokasi']) ? (int)$_POST['id_lokasi'] : null;
    $jumlah_stok = (int)($_POST['jumlah_stok'] ?? 0);
    $stok_minimum = max(0, (int)($_POST['stok_minimum'] ?? 5));

    if (empty($id_produk) || empty($id_gudang)) {
        $error = 'Produk dan gudang tujuan wajib dipilih.';
    } elseif ($jumlah_stok < 0) {
        $error = 'Jumlah stok tidak boleh bernilai negatif.';
    } else {
        // Cek apakah produk sudah terdaftar di gudang ini (UNIQUE KEY `uk_produk_gudang`)
        $stmtCek = $pdo->prepare("SELECT id_persediaan, jumlah_stok FROM persediaan WHERE id_produk = ? AND id_gudang = ?");
        $stmtCek->execute([$id_produk, $id_gudang]);
        $cek = $stmtCek->fetch();

        if ($cek) {
            $error = "Produk ini sudah tercatat di gudang tersebut (ID Persediaan: #{$cek['id_persediaan']}). Silakan gunakan menu Edit Persediaan untuk menyesuaikan kuantitas.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Masukkan ke tabel persediaan
                $stmt = $pdo->prepare("
                    INSERT INTO persediaan (id_produk, id_gudang, id_lokasi, jumlah_stok, stok_minimum, terakhir_diperbarui)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$id_produk, $id_gudang, $id_lokasi, $jumlah_stok, $stok_minimum]);

                // 2. Catat riwayat di pergerakan_stok
                $stmtLog = $pdo->prepare("
                    INSERT INTO pergerakan_stok 
                    (id_produk, id_gudang, id_karyawan, jenis_pergerakan, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, tanggal)
                    VALUES (?, ?, ?, 'masuk', ?, 0, ?, 'INPUT-AWAL', 'Pencatatan persediaan stok produk baru ke gudang', NOW())
                ");
                $stmtLog->execute([
                    $id_produk,
                    $id_gudang,
                    $user_aktif['id_karyawan'],
                    $jumlah_stok,
                    $jumlah_stok
                ]);

                $pdo->commit();

                flash_set('success', 'Persediaan produk di gudang berhasil disimpan dan dicatat dalam mutasi pergerakan stok!');
                header('Location: ' . base_url('modules/persediaan/index.php'));
                exit;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal menyimpan persediaan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Persediaan Produk</span>
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
                        <div class="col-md-6">
                            <label class="form-label">Fasilitas Gudang <span class="text-danger">*</span></label>
                            <select name="id_gudang" class="form-select" required>
                                <option value="">-- Pilih Gudang --</option>
                                <?php foreach ($daftar_gudang as $gdg): ?>
                                    <option value="<?= $gdg['id_gudang'] ?>" <?= ($id_gudang === (int)$gdg['id_gudang']) ? 'selected' : '' ?>>
                                        <?= escape($gdg['nama_gudang']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lokasi Rak / Bin (Opsional)</label>
                            <select name="id_lokasi" class="form-select">
                                <option value="">-- Tanpa Rak Khusus --</option>
                                <?php foreach ($daftar_lokasi as $lok): ?>
                                    <option value="<?= $lok['id_lokasi'] ?>" <?= ($id_lokasi === (int)$lok['id_lokasi']) ? 'selected' : '' ?>>
                                        <?= escape($lok['kode_lokasi']) ?> - <?= escape($lok['nama_lokasi']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Stok Fisik <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="jumlah_stok" class="form-control" value="<?= $jumlah_stok ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batas Stok Minimum <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="stok_minimum" class="form-control" value="<?= $stok_minimum ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/persediaan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Persediaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
