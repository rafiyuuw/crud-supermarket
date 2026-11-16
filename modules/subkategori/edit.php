<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Subkategori (Edit Data)
 * File: modules/subkategori/edit.php
 */

$judul_halaman = 'Edit Subkategori';
$menu_aktif = 'subkategori';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM subkategori WHERE id_subkategori = ?");
$stmt->execute([$id]);
$sub = $stmt->fetch();

if (!$sub) {
    flash_set('danger', 'Data subkategori tidak ditemukan.');
    header('Location: ' . base_url('modules/subkategori/index.php'));
    exit;
}

$stmtKat = $pdo->query("
    SELECT k.id_kategori, k.nama_kategori, d.nama_departemen 
    FROM kategori k
    INNER JOIN departemen d ON k.id_departemen = d.id_departemen
    WHERE k.status = 'aktif'
    ORDER BY d.nama_departemen ASC, k.nama_kategori ASC
");
$daftar_kategori = $stmtKat->fetchAll();

$error = '';
$id_kategori = (int)$sub['id_kategori'];
$kode_subkategori = $sub['kode_subkategori'];
$nama_subkategori = $sub['nama_subkategori'];
$deskripsi = $sub['deskripsi'] ?? '';
$status = $sub['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $kode_subkategori = trim($_POST['kode_subkategori'] ?? '');
    $nama_subkategori = trim($_POST['nama_subkategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = $_POST['status'] ?? 'aktif';

    if (empty($id_kategori) || empty($kode_subkategori) || empty($nama_subkategori)) {
        $error = 'Kategori induk, kode subkategori, dan nama subkategori wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM subkategori WHERE kode_subkategori = ? AND id_subkategori != ?");
        $stmtCek->execute([$kode_subkategori, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode subkategori '{$kode_subkategori}' sudah digunakan oleh subkategori lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE subkategori SET id_kategori = ?, kode_subkategori = ?, nama_subkategori = ?, deskripsi = ?, status = ? WHERE id_subkategori = ?");
                $stmtUpdate->execute([$id_kategori, $kode_subkategori, $nama_subkategori, $deskripsi ?: null, $status, $id]);

                flash_set('success', "Subkategori <strong>" . escape($nama_subkategori) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/subkategori/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Subkategori</span>
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
                        <input type="text" name="kode_subkategori" class="form-control" value="<?= escape($kode_subkategori) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Subkategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_subkategori" class="form-control" value="<?= escape($nama_subkategori) ?>" required>
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
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Subkategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
