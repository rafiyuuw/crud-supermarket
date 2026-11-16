<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Akun Karyawan (Edit Data)
 * File: modules/akun_karyawan/edit.php
 */

$judul_halaman = 'Edit Akun Karyawan';
$menu_aktif = 'akun_karyawan';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT a.*, k.nama_karyawan, k.nik, j.nama_jabatan 
    FROM akun_karyawan a
    INNER JOIN karyawan k ON a.id_karyawan = k.id_karyawan
    INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    WHERE a.id_akun = ?
");
$stmt->execute([$id]);
$akun = $stmt->fetch();

if (!$akun) {
    flash_set('danger', 'Data akun tidak ditemukan.');
    header('Location: ' . base_url('modules/akun_karyawan/index.php'));
    exit;
}

$error = '';
$username = $akun['username'];
$peran = $akun['peran'];
$status = $akun['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password_baru = $_POST['password_baru'] ?? '';
    $peran = $_POST['peran'] ?? 'kasir';
    $status = $_POST['status'] ?? 'aktif';

    if (empty($username)) {
        $error = 'Nama pengguna (username) wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM akun_karyawan WHERE username = ? AND id_akun != ?");
        $stmtCek->execute([$username, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Username '{$username}' sudah digunakan oleh pengguna lain.";
        } else {
            try {
                if (!empty($password_baru)) {
                    // Update dengan password baru
                    $hashPass = password_hash($password_baru, PASSWORD_DEFAULT);
                    $stmtUpdate = $pdo->prepare("UPDATE akun_karyawan SET username = ?, password = ?, peran = ?, status = ? WHERE id_akun = ?");
                    $stmtUpdate->execute([$username, $hashPass, $peran, $status, $id]);
                } else {
                    // Update tanpa mengubah password lama
                    $stmtUpdate = $pdo->prepare("UPDATE akun_karyawan SET username = ?, peran = ?, status = ? WHERE id_akun = ?");
                    $stmtUpdate->execute([$username, $peran, $status, $id]);
                }

                flash_set('success', "Akun <strong>" . escape($username) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/akun_karyawan/index.php'));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Akun Pengguna</span>
                <a href="<?= base_url('modules/akun_karyawan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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

                <div class="alert alert-light border py-2 mb-3">
                    <small class="text-muted d-block">Pemilik Akun:</small>
                    <span class="fw-bold text-dark"><?= escape($akun['nama_karyawan']) ?></span>
                    <span class="text-muted small">(<?= escape($akun['nik']) ?> &bull; <?= escape($akun['nama_jabatan']) ?>)</span>
                </div>

                <form action="" method="POST">
                    <?= csrf_input() ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Pengguna (Username) <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" value="<?= escape($username) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ganti Kata Sandi (Password Baru)</label>
                        <input type="password" name="password_baru" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <small class="text-muted">Isi hanya jika ingin mereset password akun pengguna ini.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Hak Akses / Peran <span class="text-danger">*</span></label>
                            <select name="peran" class="form-select" required>
                                <option value="kasir" <?= ($peran === 'kasir') ? 'selected' : '' ?>>Kasir</option>
                                <option value="gudang" <?= ($peran === 'gudang') ? 'selected' : '' ?>>Gudang</option>
                                <option value="manajer" <?= ($peran === 'manajer') ? 'selected' : '' ?>>Manajer</option>
                                <option value="admin" <?= ($peran === 'admin') ? 'selected' : '' ?>>Administrator</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Status Akun <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/akun_karyawan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
