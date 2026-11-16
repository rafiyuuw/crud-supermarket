<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Karyawan (Edit Data)
 * File: modules/karyawan/edit.php
 */

$judul_halaman = 'Edit Data Karyawan';
$menu_aktif = 'karyawan';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM karyawan WHERE id_karyawan = ?");
$stmt->execute([$id]);
$kry = $stmt->fetch();

if (!$kry) {
    flash_set('danger', 'Data karyawan tidak ditemukan.');
    header('Location: ' . base_url('modules/karyawan/index.php'));
    exit;
}

$daftar_jabatan = $pdo->query("SELECT id_jabatan, nama_jabatan FROM jabatan ORDER BY nama_jabatan ASC")->fetchAll();
$daftar_cabang = $pdo->query("SELECT id_cabang, nama_cabang FROM cabang WHERE status = 'aktif' ORDER BY nama_cabang ASC")->fetchAll();

$error = '';
$nik = $kry['nik'];
$nama_karyawan = $kry['nama_karyawan'];
$id_jabatan = (int)$kry['id_jabatan'];
$id_cabang = (int)$kry['id_cabang'];
$jenis_kelamin = $kry['jenis_kelamin'];
$nomor_telepon = $kry['nomor_telepon'];
$email = $kry['email'] ?? '';
$alamat = $kry['alamat'] ?? '';
$tanggal_masuk = $kry['tanggal_masuk'];
$status = $kry['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nik = trim($_POST['nik'] ?? '');
    $nama_karyawan = trim($_POST['nama_karyawan'] ?? '');
    $id_jabatan = (int)($_POST['id_jabatan'] ?? 0);
    $id_cabang = (int)($_POST['id_cabang'] ?? 0);
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tanggal_masuk = $_POST['tanggal_masuk'] ?? '';
    $status = $_POST['status'] ?? 'aktif';

    if (empty($nik) || empty($nama_karyawan) || empty($id_jabatan) || empty($id_cabang) || empty($nomor_telepon)) {
        $error = 'NIK, nama karyawan, jabatan, cabang, dan nomor telepon wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM karyawan WHERE nik = ? AND id_karyawan != ?");
        $stmtCek->execute([$nik, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "NIK '{$nik}' sudah digunakan oleh karyawan lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE karyawan 
                    SET nik = ?, nama_karyawan = ?, id_jabatan = ?, id_cabang = ?, jenis_kelamin = ?, 
                        nomor_telepon = ?, email = ?, alamat = ?, tanggal_masuk = ?, status = ?
                    WHERE id_karyawan = ?
                ");
                $stmtUpdate->execute([
                    $nik,
                    $nama_karyawan,
                    $id_jabatan,
                    $id_cabang,
                    $jenis_kelamin,
                    $nomor_telepon,
                    $email ?: null,
                    $alamat ?: null,
                    $tanggal_masuk,
                    $status,
                    $id
                ]);

                flash_set('success', "Data karyawan <strong>" . escape($nama_karyawan) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/karyawan/index.php'));
                exit;

            } catch (PDOException $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Karyawan</span>
                <a href="<?= base_url('modules/karyawan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <div class="col-md-4">
                            <label class="form-label">Nomor Induk Karyawan (NIK) <span class="text-danger">*</span></label>
                            <input type="text" name="nik" class="form-control" value="<?= escape($nik) ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nama Lengkap Karyawan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_karyawan" class="form-control" value="<?= escape($nama_karyawan) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Posisi Jabatan <span class="text-danger">*</span></label>
                            <select name="id_jabatan" class="form-select" required>
                                <option value="">-- Pilih Jabatan --</option>
                                <?php foreach ($daftar_jabatan as $j): ?>
                                    <option value="<?= $j['id_jabatan'] ?>" <?= ($id_jabatan === (int)$j['id_jabatan']) ? 'selected' : '' ?>>
                                        <?= escape($j['nama_jabatan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Penempatan Cabang <span class="text-danger">*</span></label>
                            <select name="id_cabang" class="form-select" required>
                                <option value="">-- Pilih Cabang --</option>
                                <?php foreach ($daftar_cabang as $c): ?>
                                    <option value="<?= $c['id_cabang'] ?>" <?= ($id_cabang === (int)$c['id_cabang']) ? 'selected' : '' ?>>
                                        <?= escape($c['nama_cabang']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L" <?= ($jenis_kelamin === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= ($jenis_kelamin === 'P') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nomor Telepon / WA <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_telepon" class="form-control" value="<?= escape($nomor_telepon) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-control" value="<?= escape($email) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Alamat Domisili</label>
                            <textarea name="alamat" class="form-control" rows="2"><?= escape($alamat) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tanggal Masuk Kerja <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_masuk" class="form-control" value="<?= escape($tanggal_masuk) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status Kepegawaian <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif / Resign</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/karyawan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Karyawan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
