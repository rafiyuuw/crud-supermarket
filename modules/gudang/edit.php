<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Gudang (Edit Data)
 * File: modules/gudang/edit.php
 */

$judul_halaman = 'Edit Gudang';
$menu_aktif = 'gudang';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM gudang WHERE id_gudang = ?");
$stmt->execute([$id]);
$gdg = $stmt->fetch();

if (!$gdg) {
    flash_set('danger', 'Data gudang tidak ditemukan.');
    header('Location: ' . base_url('modules/gudang/index.php'));
    exit;
}

$daftar_cabang = $pdo->query("SELECT id_cabang, nama_cabang FROM cabang WHERE status = 'aktif' ORDER BY nama_cabang ASC")->fetchAll();

$error = '';
$id_cabang = (int)$gdg['id_cabang'];
$kode_gudang = $gdg['kode_gudang'];
$nama_gudang = $gdg['nama_gudang'];
$alamat = $gdg['alamat'];
$kapasitas_m3 = (int)$gdg['kapasitas_m3'];
$status = $gdg['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_cabang = (int)($_POST['id_cabang'] ?? 0);
    $kode_gudang = trim($_POST['kode_gudang'] ?? '');
    $nama_gudang = trim($_POST['nama_gudang'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $kapasitas_num = max(1, (int)($_POST['kapasitas_m3'] ?? 0));
    $status = $_POST['status'] ?? 'aktif';

    if (empty($id_cabang) || empty($kode_gudang) || empty($nama_gudang) || empty($alamat)) {
        $error = 'Cabang, kode gudang, nama gudang, dan alamat wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM gudang WHERE kode_gudang = ? AND id_gudang != ?");
        $stmtCek->execute([$kode_gudang, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode gudang '{$kode_gudang}' sudah digunakan oleh gudang lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE gudang 
                    SET id_cabang = ?, kode_gudang = ?, nama_gudang = ?, alamat = ?, kapasitas_m3 = ?, status = ?
                    WHERE id_gudang = ?
                ");
                $stmtUpdate->execute([$id_cabang, $kode_gudang, $nama_gudang, $alamat, $kapasitas_num, $status, $id]);

                flash_set('success', "Gudang <strong>" . escape($nama_gudang) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/gudang/index.php'));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Gudang</span>
                <a href="<?= base_url('modules/gudang/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Cabang Terkait <span class="text-danger">*</span></label>
                        <select name="id_cabang" class="form-select" required>
                            <option value="">-- Pilih Cabang --</option>
                            <?php foreach ($daftar_cabang as $c): ?>
                                <option value="<?= $c['id_cabang'] ?>" <?= ($id_cabang === (int)$c['id_cabang']) ? 'selected' : '' ?>>
                                    <?= escape($c['nama_cabang']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kode Gudang <span class="text-danger">*</span></label>
                        <input type="text" name="kode_gudang" class="form-control" value="<?= escape($kode_gudang) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Fasilitas Gudang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_gudang" class="form-control" value="<?= escape($nama_gudang) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap Gudang <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="2" required><?= escape($alamat) ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Kapasitas Penyimpanan (m&sup3;) <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="kapasitas_m3" class="form-control" value="<?= $kapasitas_m3 ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Status Operasional <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/gudang/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Gudang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
