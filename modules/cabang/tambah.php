<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Cabang (Tambah Data)
 * File: modules/cabang/tambah.php
 */

$judul_halaman = 'Tambah Cabang';
$menu_aktif = 'cabang';

require_once __DIR__ . '/../../includes/header.php';

$error = '';
$kode_cabang = $_POST['kode_cabang'] ?? '';
$nama_cabang = $_POST['nama_cabang'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$telepon = $_POST['telepon'] ?? '';
$email = $_POST['email'] ?? '';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_cabang = trim($kode_cabang);
    $nama_cabang = trim($nama_cabang);
    $alamat = trim($alamat);
    $telepon = trim($telepon);
    $email = trim($email);

    if (empty($kode_cabang) || empty($nama_cabang) || empty($alamat) || empty($telepon)) {
        $error = 'Seluruh kolom bertanda bintang (*) wajib diisi.';
    } else {
        // Cek keunikan kode_cabang
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM cabang WHERE kode_cabang = ?");
        $stmtCek->execute([$kode_cabang]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode cabang '{$kode_cabang}' sudah digunakan. Silakan gunakan kode lain.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO cabang (kode_cabang, nama_cabang, alamat, telepon, email, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$kode_cabang, $nama_cabang, $alamat, $telepon, $email ?: null, $status]);

                flash_set('success', "Cabang <strong>" . escape($nama_cabang) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/cabang/index.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan data cabang: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Formulir Tambah Cabang</span>
                <a href="<?= base_url('modules/cabang/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <div class="col-md-6">
                            <label class="form-label">Kode Cabang <span class="text-danger">*</span></label>
                            <input type="text" name="kode_cabang" class="form-control" placeholder="Contoh: CBG-004" value="<?= escape($kode_cabang) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Cabang <span class="text-danger">*</span></label>
                            <input type="text" name="nama_cabang" class="form-control" placeholder="Contoh: Supermarket Surabaya Gubeng" value="<?= escape($nama_cabang) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Masukkan alamat lengkap cabang" required><?= escape($alamat) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="telepon" class="form-control" placeholder="Contoh: 021-1234567" value="<?= escape($telepon) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Cabang</label>
                            <input type="email" name="email" class="form-control" placeholder="cabang@supermarket.id" value="<?= escape($email) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Operasional <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/cabang/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Data Cabang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
