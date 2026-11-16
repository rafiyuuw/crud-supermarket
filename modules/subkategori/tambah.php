<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Subkategori (Tambah Data)
 * File: modules/subkategori/tambah.php
 */

$judul_halaman = 'Tambah Subkategori';
$menu_aktif = 'subkategori';

require_once __DIR__ . '/../../includes/header.php';

// Ambil data kategori beserta departemennya
$stmtKat = $pdo->query("
    SELECT k.id_kategori, k.nama_kategori, d.nama_departemen 
    FROM kategori k
    INNER JOIN departemen d ON k.id_departemen = d.id_departemen
    WHERE k.status = 'aktif'
    ORDER BY d.nama_departemen ASC, k.nama_kategori ASC
");
$daftar_kategori = $stmtKat->fetchAll();

$error = '';
$id_kategori = (int)($_POST['id_kategori'] ?? 0);
$kode_subkategori = $_POST['kode_subkategori'] ?? '';
$nama_subkategori = $_POST['nama_subkategori'] ?? '';
$deskripsi = $_POST['deskripsi'] ?? '';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_subkategori = trim($kode_subkategori);
    $nama_subkategori = trim($nama_subkategori);
    $deskripsi = trim($deskripsi);

    if (empty($id_kategori) || empty($kode_subkategori) || empty($nama_subkategori)) {
        $error = 'Kategori induk, kode subkategori, dan nama subkategori wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM subkategori WHERE kode_subkategori = ?");
        $stmtCek->execute([$kode_subkategori]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode subkategori '{$kode_subkategori}' sudah terdaftar.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO subkategori (id_kategori, kode_subkategori, nama_subkategori, deskripsi, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_kategori, $kode_subkategori, $nama_subkategori, $deskripsi ?: null, $status]);

                flash_set('success', "Subkategori <strong>" . escape($nama_subkategori) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/subkategori/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Subkategori</span>
                <a href="<?= base_url('modules/subkategori/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Kategori Induk <span class="text-danger">*</span></label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori Induk --</option>
                            <?php foreach ($daftar_kategori as $k): ?>
                                <option value="<?= $k['id_kategori'] ?>" <?= ($id_kategori === (int)$k['id_kategori']) ? 'selected' : '' ?>>
                                    [<?= escape($k['nama_departemen']) ?>] &rsaquo; <?= escape($k['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kode Subkategori <span class="text-danger">*</span></label>
                        <input type="text" name="kode_subkategori" class="form-control" placeholder="Contoh: SUB-07" value="<?= escape($kode_subkategori) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Subkategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_subkategori" class="form-control" placeholder="Contoh: Susu Kedelai & Almond" value="<?= escape($nama_subkategori) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= escape($deskripsi) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/subkategori/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Subkategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
