<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Lokasi Gudang (Edit Data)
 * File: modules/lokasi_gudang/edit.php
 */

$judul_halaman = 'Edit Lokasi Gudang';
$menu_aktif = 'lokasi_gudang';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM lokasi_gudang WHERE id_lokasi = ?");
$stmt->execute([$id]);
$lok = $stmt->fetch();

if (!$lok) {
    flash_set('danger', 'Data lokasi gudang tidak ditemukan.');
    header('Location: ' . base_url('modules/lokasi_gudang/index.php'));
    exit;
}

$daftar_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang WHERE status = 'aktif' ORDER BY nama_gudang ASC")->fetchAll();

$error = '';
$id_gudang = (int)$lok['id_gudang'];
$kode_lokasi = $lok['kode_lokasi'];
$nama_lokasi = $lok['nama_lokasi'];
$keterangan = $lok['keterangan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_gudang = (int)($_POST['id_gudang'] ?? 0);
    $kode_lokasi = trim($_POST['kode_lokasi'] ?? '');
    $nama_lokasi = trim($_POST['nama_lokasi'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($id_gudang) || empty($kode_lokasi) || empty($nama_lokasi)) {
        $error = 'Gudang, kode lokasi, dan nama lokasi wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM lokasi_gudang WHERE kode_lokasi = ? AND id_lokasi != ?");
        $stmtCek->execute([$kode_lokasi, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode lokasi '{$kode_lokasi}' sudah digunakan oleh lokasi lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE lokasi_gudang SET id_gudang = ?, kode_lokasi = ?, nama_lokasi = ?, keterangan = ? WHERE id_lokasi = ?");
                $stmtUpdate->execute([$id_gudang, $kode_lokasi, $nama_lokasi, $keterangan ?: null, $id]);

                flash_set('success', "Lokasi <strong>" . escape($nama_lokasi) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/lokasi_gudang/index.php?id_gudang=' . $id_gudang));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Lokasi / Rak</span>
                <a href="<?= base_url('modules/lokasi_gudang/index.php?id_gudang=' . $id_gudang) ?>" class="btn btn-outline-secondary btn-sm">
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

                    <div class="mb-3">
                        <label class="form-label">Kode Lokasi / Rak <span class="text-danger">*</span></label>
                        <input type="text" name="kode_lokasi" class="form-control" value="<?= escape($kode_lokasi) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Spesifik Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lokasi" class="form-control" value="<?= escape($nama_lokasi) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan / Catatan Suhu</label>
                        <textarea name="keterangan" class="form-control" rows="2"><?= escape($keterangan) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/lokasi_gudang/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Lokasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
