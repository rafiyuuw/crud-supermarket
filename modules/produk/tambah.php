<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Produk (Tambah Data)
 * File: modules/produk/tambah.php
 */

$judul_halaman = 'Tambah Produk Baru';
$menu_aktif = 'produk';

require_once __DIR__ . '/../../includes/header.php';

// Ambil data untuk dropdown relasi Foreign Key
$daftar_subkategori = $pdo->query("
    SELECT sk.id_subkategori, sk.nama_subkategori, k.nama_kategori 
    FROM subkategori sk
    INNER JOIN kategori k ON sk.id_kategori = k.id_kategori
    WHERE sk.status = 'aktif'
    ORDER BY k.nama_kategori ASC, sk.nama_subkategori ASC
")->fetchAll();

$daftar_merek = $pdo->query("SELECT id_merek, nama_merek FROM merek_produk WHERE status = 'aktif' ORDER BY nama_merek ASC")->fetchAll();
$daftar_satuan = $pdo->query("SELECT id_satuan, nama_satuan, simbol FROM satuan_produk WHERE status = 'aktif' ORDER BY nama_satuan ASC")->fetchAll();
$daftar_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang WHERE status = 'aktif' ORDER BY nama_gudang ASC")->fetchAll();

$error = '';
$kode_produk = $_POST['kode_produk'] ?? '';
$nama_produk = $_POST['nama_produk'] ?? '';
$id_subkategori = (int)($_POST['id_subkategori'] ?? 0);
$id_merek = (int)($_POST['id_merek'] ?? 0);
$id_satuan = (int)($_POST['id_satuan'] ?? 0);
$harga_beli = $_POST['harga_beli'] ?? '';
$harga_jual = $_POST['harga_jual'] ?? '';
$stok_minimum = $_POST['stok_minimum'] ?? 5;
$deskripsi = $_POST['deskripsi'] ?? '';
$status = $_POST['status'] ?? 'aktif';

// Input opsional barcode awal & stok awal
$barcode_awal = trim($_POST['barcode_awal'] ?? '');
$stok_awal = (int)($_POST['stok_awal'] ?? 0);
$id_gudang_awal = (int)($_POST['id_gudang_awal'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_produk = trim($kode_produk);
    $nama_produk = trim($nama_produk);
    $harga_beli_num = (float)$harga_beli;
    $harga_jual_num = (float)$harga_jual;
    $stok_min_num = max(0, (int)$stok_minimum);

    if (empty($kode_produk) || empty($nama_produk) || empty($id_subkategori) || empty($id_merek) || empty($id_satuan)) {
        $error = 'Seluruh kolom bertanda bintang (*) wajib dipilih/diisi.';
    } elseif ($harga_jual_num < 0 || $harga_beli_num < 0) {
        $error = 'Harga beli dan harga jual tidak boleh bernilai negatif.';
    } else {
        // Cek keunikan kode_produk
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kode_produk = ?");
        $stmtCek->execute([$kode_produk]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode produk '{$kode_produk}' sudah terdaftar.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Simpan Produk
                $stmt = $pdo->prepare("
                    INSERT INTO produk 
                    (kode_produk, nama_produk, id_subkategori, id_merek, id_satuan, harga_beli, harga_jual, stok_minimum, deskripsi, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $kode_produk,
                    $nama_produk,
                    $id_subkategori,
                    $id_merek,
                    $id_satuan,
                    $harga_beli_num,
                    $harga_jual_num,
                    $stok_min_num,
                    $deskripsi ?: null,
                    $status
                ]);
                $id_produk_baru = (int)$pdo->lastInsertId();

                // 2. Simpan Barcode Awal jika diisi
                if (!empty($barcode_awal)) {
                    $stmtBar = $pdo->prepare("INSERT INTO barcode_produk (id_produk, kode_barcode, jenis_barcode, status) VALUES (?, ?, 'EAN-13', 'utama')");
                    $stmtBar->execute([$id_produk_baru, $barcode_awal]);
                }

                // 3. Simpan Stok Awal jika diisi
                if ($stok_awal > 0 && $id_gudang_awal > 0) {
                    $stmtPersediaan = $pdo->prepare("
                        INSERT INTO persediaan (id_produk, id_gudang, jumlah_stok, stok_minimum)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtPersediaan->execute([$id_produk_baru, $id_gudang_awal, $stok_awal, $stok_min_num]);

                    // Catat riwayat pergerakan stok awal
                    $stmtLog = $pdo->prepare("
                        INSERT INTO pergerakan_stok (id_produk, id_gudang, id_karyawan, jenis_pergerakan, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan)
                        VALUES (?, ?, ?, 'masuk', ?, 0, ?, 'SALDO-AWAL', 'Pencatatan persediaan stok awal produk')
                    ");
                    $stmtLog->execute([$id_produk_baru, $id_gudang_awal, $user_aktif['id_karyawan'], $stok_awal, $stok_awal]);
                }

                $pdo->commit();

                flash_set('success', "Produk <strong>" . escape($nama_produk) . "</strong> berhasil ditambahkan ke katalog!");
                header('Location: ' . base_url('modules/produk/index.php'));
                exit;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal menyimpan produk: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-box-seam text-primary me-2"></i>Formulir Tambah Produk</span>
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
                        <!-- Kode Produk -->
                        <div class="col-md-4">
                            <label class="form-label">Kode Produk <span class="text-danger">*</span></label>
                            <input type="text" name="kode_produk" class="form-control" placeholder="Contoh: PRD-0008" value="<?= escape($kode_produk) ?>" required>
                        </div>

                        <!-- Nama Produk -->
                        <div class="col-md-8">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="nama_produk" class="form-control" placeholder="Nama lengkap produk beserta ukuran/varian" value="<?= escape($nama_produk) ?>" required>
                        </div>

                        <!-- Subkategori (FK) -->
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

                        <!-- Merek (FK) -->
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

                        <!-- Satuan (FK) -->
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

                        <!-- Harga Beli -->
                        <div class="col-md-4">
                            <label class="form-label">Harga Beli (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" min="0" name="harga_beli" class="form-control" placeholder="0" value="<?= escape($harga_beli) ?>" required>
                            </div>
                        </div>

                        <!-- Harga Jual -->
                        <div class="col-md-4">
                            <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" min="0" name="harga_jual" class="form-control" placeholder="0" value="<?= escape($harga_jual) ?>" required>
                            </div>
                        </div>

                        <!-- Stok Minimum -->
                        <div class="col-md-4">
                            <label class="form-label">Batas Stok Minimum <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="stok_minimum" class="form-control" value="<?= escape($stok_minimum) ?>" required>
                            <small class="text-muted">Peringatan muncul jika stok &le; angka ini.</small>
                        </div>

                        <!-- Deskripsi -->
                        <div class="col-12">
                            <label class="form-label">Deskripsi Produk</label>
                            <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan tambahan spesifikasi atau komposisi"><?= escape($deskripsi) ?></textarea>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <label class="form-label">Status Produk <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif Dijual</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif / Discontinued</option>
                            </select>
                        </div>

                        <!-- Barcode Awal (Opsional) -->
                        <div class="col-md-8">
                            <label class="form-label">Barcode Awal (Opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" name="barcode_awal" class="form-control" placeholder="Contoh: 8998866200999" value="<?= escape($barcode_awal) ?>">
                            </div>
                            <small class="text-muted">Dapat ditambahkan lagi nanti pada modul Barcode Produk.</small>
                        </div>
                    </div>

                    <!-- Bagian Stok Awal (Opsional) -->
                    <div class="card mt-4 bg-light border">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-stack text-primary me-2"></i>Persediaan Stok Awal (Opsional)</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Lokasi Gudang Simpan</label>
                                    <select name="id_gudang_awal" class="form-select form-select-sm">
                                        <?php foreach ($daftar_gudang as $gdg): ?>
                                            <option value="<?= $gdg['id_gudang'] ?>"><?= escape($gdg['nama_gudang']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Jumlah Stok Masuk Pertama</label>
                                    <input type="number" min="0" name="stok_awal" class="form-control form-control-sm" placeholder="0" value="<?= $stok_awal ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/produk/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
