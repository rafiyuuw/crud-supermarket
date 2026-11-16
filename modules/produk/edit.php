<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Produk (Edit Data)
 * File: modules/produk/edit.php
 */

$judul_halaman = 'Edit Produk';
$menu_aktif = 'produk';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    flash_set('danger', 'Data produk tidak ditemukan.');
    header('Location: ' . base_url('modules/produk/index.php'));
    exit;
}

// Ambil data dropdown
$daftar_subkategori = $pdo->query("
    SELECT sk.id_subkategori, sk.nama_subkategori, k.nama_kategori 
    FROM subkategori sk
    INNER JOIN kategori k ON sk.id_kategori = k.id_kategori
    WHERE sk.status = 'aktif'
    ORDER BY k.nama_kategori ASC, sk.nama_subkategori ASC
")->fetchAll();

$daftar_merek = $pdo->query("SELECT id_merek, nama_merek FROM merek_produk WHERE status = 'aktif' ORDER BY nama_merek ASC")->fetchAll();
$daftar_satuan = $pdo->query("SELECT id_satuan, nama_satuan, simbol FROM satuan_produk WHERE status = 'aktif' ORDER BY nama_satuan ASC")->fetchAll();

$error = '';
$kode_produk = $produk['kode_produk'];
$nama_produk = $produk['nama_produk'];
$id_subkategori = (int)$produk['id_subkategori'];
$id_merek = (int)$produk['id_merek'];
$id_satuan = (int)$produk['id_satuan'];
$harga_beli = $produk['harga_beli'];
$harga_jual = $produk['harga_jual'];
$stok_minimum = $produk['stok_minimum'];
$deskripsi = $produk['deskripsi'] ?? '';
$status = $produk['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_produk = trim($_POST['kode_produk'] ?? '');
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_subkategori = (int)($_POST['id_subkategori'] ?? 0);
    $id_merek = (int)($_POST['id_merek'] ?? 0);
    $id_satuan = (int)($_POST['id_satuan'] ?? 0);
    $harga_beli_num = (float)($_POST['harga_beli'] ?? 0);
    $harga_jual_num = (float)($_POST['harga_jual'] ?? 0);
    $stok_min_num = max(0, (int)($_POST['stok_minimum'] ?? 0));
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    if (empty($kode_produk) || empty($nama_produk) || empty($id_subkategori) || empty($id_merek) || empty($id_satuan)) {
        $error = 'Seluruh kolom bertanda bintang (*) wajib dipilih/diisi.';
    } elseif ($harga_jual_num < 0 || $harga_beli_num < 0) {
        $error = 'Harga beli dan jual tidak boleh bernilai negatif.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kode_produk = ? AND id_produk != ?");
        $stmtCek->execute([$kode_produk, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode produk '{$kode_produk}' sudah digunakan oleh produk lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE produk 
                    SET kode_produk = ?, nama_produk = ?, id_subkategori = ?, id_merek = ?, id_satuan = ?, 
                        harga_beli = ?, harga_jual = ?, stok_minimum = ?, deskripsi = ?, status = ?
                    WHERE id_produk = ?
                ");
                $stmtUpdate->execute([
                    $kode_produk,
                    $nama_produk,
                    $id_subkategori,
                    $id_merek,
                    $id_satuan,
                    $harga_beli_num,
                    $harga_jual_num,
                    $stok_min_num,
                    $deskripsi ?: null,
                    $status,
                    $id
                ]);

                flash_set('success', "Produk <strong>" . escape($nama_produk) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/produk/index.php'));
                exit;

            } catch (PDOException $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Produk</span>
                <a href="<?= base_url('modules/produk/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <div class="col-md-4">
                            <label class="form-label">Kode Produk <span class="text-danger">*</span></label>
                            <input type="text" name="kode_produk" class="form-control" value="<?= escape($kode_produk) ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="nama_produk" class="form-control" value="<?= escape($nama_produk) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Subkategori <span class="text-danger">*</span></label>
                            <select name="id_subkategori" class="form-select" required>
                                <option value="">-- Pilih Subkategori --</option>
                                <?php foreach ($daftar_subkategori as $sub): ?>
                                    <option value="<?= $sub['id_subkategori'] ?>" <?= ($id_subkategori === (int)$sub['id_subkategori']) ? 'selected' : '' ?>>
                                        <?= escape($sub['nama_kategori']) ?> &rsaquo; <?= escape($sub['nama_subkategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Merek Produk <span class="text-danger">*</span></label>
                            <select name="id_merek" class="form-select" required>
                                <option value="">-- Pilih Merek --</option>
                                <?php foreach ($daftar_merek as $mrk): ?>
                                    <option value="<?= $mrk['id_merek'] ?>" <?= ($id_merek === (int)$mrk['id_merek']) ? 'selected' : '' ?>>
                                        <?= escape($mrk['nama_merek']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Satuan Produk <span class="text-danger">*</span></label>
                            <select name="id_satuan" class="form-select" required>
                                <option value="">-- Pilih Satuan --</option>
                                <?php foreach ($daftar_satuan as $sat): ?>
                                    <option value="<?= $sat['id_satuan'] ?>" <?= ($id_satuan === (int)$sat['id_satuan']) ? 'selected' : '' ?>>
                                        <?= escape($sat['nama_satuan']) ?> (<?= escape($sat['simbol']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Harga Beli (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" min="0" name="harga_beli" class="form-control" value="<?= escape($harga_beli) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" min="0" name="harga_jual" class="form-control" value="<?= escape($harga_jual) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Batas Stok Minimum <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="stok_minimum" class="form-control" value="<?= escape($stok_minimum) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Deskripsi Produk</label>
                            <textarea name="deskripsi" class="form-control" rows="3"><?= escape($deskripsi) ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status Produk <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif Dijual</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif / Discontinued</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/produk/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
