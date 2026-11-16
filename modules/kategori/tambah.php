<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Kategori (Tambah Data)
 * File: modules/kategori/tambah.php
 */

$judul_halaman = 'Tambah Kategori';
$menu_aktif = 'kategori';

require_once __DIR__ . '/../../includes/header.php';

// Ambil data departemen untuk dropdown relasi
$stmtDept = $pdo->query("SELECT id_departemen, nama_departemen FROM departemen WHERE status = 'aktif' ORDER BY nama_departemen ASC");
$daftar_departemen = $stmtDept->fetchAll();

$error = '';
$id_departemen = (int)($_POST['id_departemen'] ?? 0);
$kode_kategori = $_POST['kode_kategori'] ?? '';
$nama_kategori = $_POST['nama_kategori'] ?? '';
$deskripsi = $_POST['deskripsi'] ?? '';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_kategori = trim($kode_kategori);
    $nama_kategori = trim($nama_kategori);
    $deskripsi = trim($deskripsi);

    if (empty($id_departemen) || empty($kode_kategori) || empty($nama_kategori)) {
        $error = 'Departemen, kode kategori, dan nama kategori wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE kode_kategori = ?");
        $stmtCek->execute([$kode_kategori]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode kategori '{$kode_kategori}' sudah terdaftar.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO kategori (id_departemen, kode_kategori, nama_kategori, deskripsi, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_departemen, $kode_kategori, $nama_kategori, $deskripsi ?: null, $status]);

                flash_set('success', "Kategori <strong>" . escape($nama_kategori) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/kategori/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan kategori: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Kategori</span>
                <a href="<?= base_url('modules/kategori/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Departemen Terkait <span class="text-danger">*</span></label>
                        <select name="id_departemen" class="form-select" required>
                            <option value="">-- Pilih Departemen --</option>
                            <?php foreach ($daftar_departemen as $d): ?>
                                <option value="<?= $d['id_departemen'] ?>" <?= ($id_departemen === (int)$d['id_departemen']) ? 'selected' : '' ?>>
                                    <?= escape($d['nama_departemen']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Pilih departemen induk dari kategori ini.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kode Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="kode_kategori" class="form-control" placeholder="Contoh: KAT-05" value="<?= escape($kode_kategori) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Bumbu & Rempah Pilihan" value="<?= escape($nama_kategori) ?>" required>
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
                        <a href="<?= base_url('modules/kategori/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
