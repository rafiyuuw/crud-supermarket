<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Akun Karyawan (Tambah Data)
 * File: modules/akun_karyawan/tambah.php
 */

$judul_halaman = 'Tambah Akun Karyawan';
$menu_aktif = 'akun_karyawan';

require_once __DIR__ . '/../../includes/header.php';

// Ambil karyawan yang belum memiliki akun
$daftar_karyawan = $pdo->query("
    SELECT k.id_karyawan, k.nik, k.nama_karyawan, j.nama_jabatan 
    FROM karyawan k
    INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    LEFT JOIN akun_karyawan ak ON k.id_karyawan = ak.id_karyawan
    WHERE ak.id_akun IS NULL AND k.status = 'aktif'
    ORDER BY k.nama_karyawan ASC
")->fetchAll();

$error = '';
$id_karyawan = (int)($_POST['id_karyawan'] ?? ($_GET['id_karyawan'] ?? 0));
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$peran = $_POST['peran'] ?? 'kasir';
$status = $_POST['status'] ?? 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $peran = $_POST['peran'] ?? 'kasir';
    $status = $_POST['status'] ?? 'aktif';

    if (empty($id_karyawan) || empty($username) || empty($password)) {
        $error = 'Karyawan, nama pengguna (username), dan kata sandi wajib diisi.';
    } else {
        $stmtCekUser = $pdo->prepare("SELECT COUNT(*) FROM akun_karyawan WHERE username = ?");
        $stmtCekUser->execute([$username]);
        if ($stmtCekUser->fetchColumn() > 0) {
            $error = "Username '{$username}' sudah digunakan.";
        } else {
            $stmtCekKry = $pdo->prepare("SELECT COUNT(*) FROM akun_karyawan WHERE id_karyawan = ?");
            $stmtCekKry->execute([$id_karyawan]);
            if ($stmtCekKry->fetchColumn() > 0) {
                $error = "Karyawan ini sudah memiliki akun pengguna.";
            } else {
                try {
                    $hashPass = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO akun_karyawan (id_karyawan, username, password, peran, status, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$id_karyawan, $username, $hashPass, $peran, $status]);

                    flash_set('success', "Akun pengguna <strong>" . escape($username) . "</strong> berhasil dibuat!");
                    header('Location: ' . base_url('modules/akun_karyawan/index.php'));
                    exit;
                } catch (PDOException $e) {
                    $error = 'Gagal membuat akun: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-key-fill text-primary me-2"></i>Formulir Buat Akun Pengguna</span>
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

                <?php if (empty($daftar_karyawan) && empty($id_karyawan)): ?>
                    <div class="alert alert-info py-3 small text-center mb-0">
                        <i class="bi bi-check-circle-fill fs-4 d-block mb-2 text-info"></i>
                        Semua karyawan yang aktif sudah memiliki akun pengguna login.
                        <div class="mt-2">
                            <a href="<?= base_url('modules/karyawan/tambah.php') ?>" class="btn btn-primary btn-sm">Tambah Karyawan Baru</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form action="" method="POST">
                        <?= csrf_input() ?>

                        <div class="mb-3">
                            <label class="form-label">Pilih Karyawan <span class="text-danger">*</span></label>
                            <select name="id_karyawan" class="form-select" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($daftar_karyawan as $kry): ?>
                                    <option value="<?= $kry['id_karyawan'] ?>" <?= ($id_karyawan === (int)$kry['id_karyawan']) ? 'selected' : '' ?>>
                                        <?= escape($kry['nama_karyawan']) ?> (<?= escape($kry['nik']) ?> - <?= escape($kry['nama_jabatan']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Pengguna (Username) <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: bambang.kasir" value="<?= escape($username) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kata Sandi (Password) <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" value="<?= escape($password) ?>" required>
                            <small class="text-muted">Password akan dienkripsi otomatis secara aman menggunakan <code>password_hash()</code>.</small>
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
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-save me-1"></i> Simpan Akun
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
