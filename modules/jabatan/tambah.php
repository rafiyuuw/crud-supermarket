<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Jabatan (Tambah Data)
 * File: modules/jabatan/tambah.php
 */

$judul_halaman = 'Tambah Jabatan';
$menu_aktif = 'jabatan';

require_once __DIR__ . '/../../includes/header.php';

$error = '';
$kode_jabatan = $_POST['kode_jabatan'] ?? '';
$nama_jabatan = $_POST['nama_jabatan'] ?? '';
$gaji_pokok = $_POST['gaji_pokok'] ?? '';
$deskripsi = $_POST['deskripsi'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_jabatan = trim($kode_jabatan);
    $nama_jabatan = trim($nama_jabatan);
    $gaji_num = (float)$gaji_pokok;
    $deskripsi = trim($deskripsi);

    if (empty($kode_jabatan) || empty($nama_jabatan) || $gaji_num < 0) {
        $error = 'Kode jabatan, nama jabatan, dan gaji pokok wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM jabatan WHERE kode_jabatan = ?");
        $stmtCek->execute([$kode_jabatan]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode jabatan '{$kode_jabatan}' sudah digunakan.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO jabatan (kode_jabatan, nama_jabatan, gaji_pokok, deskripsi) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_jabatan, $nama_jabatan, $gaji_num, $deskripsi ?: null]);

                flash_set('success', "Jabatan <strong>" . escape($nama_jabatan) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/jabatan/index.php'));
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
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Posisi Jabatan</span>
                <a href="<?= base_url('modules/jabatan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Kode Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="kode_jabatan" class="form-control" placeholder="Contoh: JAB-05" value="<?= escape($kode_jabatan) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_jabatan" class="form-control" placeholder="Contoh: Staff IT Support" value="<?= escape($nama_jabatan) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Standar Gaji Pokok (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" step="0.01" min="0" name="gaji_pokok" class="form-control" placeholder="0" value="<?= escape($gaji_pokok) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi Pekerjaan (Job Description)</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tanggung jawab dan uraian tugas jabatan"><?= escape($deskripsi) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/jabatan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Jabatan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
