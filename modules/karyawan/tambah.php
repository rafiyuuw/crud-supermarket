<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Karyawan (Tambah Data)
 * File: modules/karyawan/tambah.php
 */

$judul_halaman = 'Tambah Karyawan Baru';
$menu_aktif = 'karyawan';

require_once __DIR__ . '/../../includes/header.php';

$daftar_jabatan = $pdo->query("SELECT id_jabatan, nama_jabatan FROM jabatan ORDER BY nama_jabatan ASC")->fetchAll();
$daftar_cabang = $pdo->query("SELECT id_cabang, nama_cabang FROM cabang WHERE status = 'aktif' ORDER BY nama_cabang ASC")->fetchAll();

$error = '';
$nik = 'KRY-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);
$nama_karyawan = $_POST['nama_karyawan'] ?? '';
$id_jabatan = (int)($_POST['id_jabatan'] ?? 0);
$id_cabang = (int)($_POST['id_cabang'] ?? 0);
$jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
$nomor_telepon = $_POST['nomor_telepon'] ?? '';
$email = $_POST['email'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$tanggal_masuk = $_POST['tanggal_masuk'] ?? date('Y-m-d');
$status = $_POST['status'] ?? 'aktif';

// Input opsional pembuatan akun login langsung
$buat_akun = !empty($_POST['buat_akun']);
$username_baru = $_POST['username_baru'] ?? '';
$password_baru = $_POST['password_baru'] ?? 'admin123';
$peran_baru = $_POST['peran_baru'] ?? 'kasir';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nik = trim($_POST['nik'] ?? '');
    $nama_karyawan = trim($nama_karyawan);
    $nomor_telepon = trim($nomor_telepon);
    $email = trim($email);
    $alamat = trim($alamat);

    if (empty($nik) || empty($nama_karyawan) || empty($id_jabatan) || empty($id_cabang) || empty($nomor_telepon)) {
        $error = 'NIK, nama karyawan, jabatan, cabang, dan nomor telepon wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM karyawan WHERE nik = ?");
        $stmtCek->execute([$nik]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "NIK '{$nik}' sudah terdaftar.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Simpan Data Karyawan
                $stmt = $pdo->prepare("
                    INSERT INTO karyawan 
                    (nik, nama_karyawan, id_jabatan, id_cabang, jenis_kelamin, nomor_telepon, email, alamat, tanggal_masuk, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nik,
                    $nama_karyawan,
                    $id_jabatan,
                    $id_cabang,
                    $jenis_kelamin,
                    $nomor_telepon,
                    $email ?: null,
                    $alamat ?: null,
                    $tanggal_masuk,
                    $status
                ]);
                $id_karyawan_baru = (int)$pdo->lastInsertId();

                // 2. Buat Akun Login jika dipilih
                if ($buat_akun && !empty(trim($username_baru)) && !empty($password_baru)) {
                    $u_clean = trim($username_baru);
                    $stmtCekUser = $pdo->prepare("SELECT COUNT(*) FROM akun_karyawan WHERE username = ?");
                    $stmtCekUser->execute([$u_clean]);
                    if ($stmtCekUser->fetchColumn() > 0) {
                        throw new Exception("Username '{$u_clean}' sudah digunakan. Karyawan tersimpan namun akun belum dibuat.");
                    }

                    $hashPass = password_hash($password_baru, PASSWORD_DEFAULT);
                    $stmtAkun = $pdo->prepare("
                        INSERT INTO akun_karyawan (id_karyawan, username, password, peran, status)
                        VALUES (?, ?, ?, ?, 'aktif')
                    ");
                    $stmtAkun->execute([$id_karyawan_baru, $u_clean, $hashPass, $peran_baru]);
                }

                $pdo->commit();

                flash_set('success', "Karyawan <strong>" . escape($nama_karyawan) . "</strong> berhasil ditambahkan!");
                header('Location: ' . base_url('modules/karyawan/index.php'));
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Formulir Tambah Data Karyawan</span>
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
                            <input type="text" name="nama_karyawan" class="form-control" placeholder="Nama lengkap pegawai" value="<?= escape($nama_karyawan) ?>" required>
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
                            <input type="text" name="nomor_telepon" class="form-control" placeholder="0812-xxxx-xxxx" value="<?= escape($nomor_telepon) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-control" placeholder="karyawan@supermarket.id" value="<?= escape($email) ?>">
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

                    <!-- Pembuatan Akun Login Sekaligus -->
                    <div class="card mt-4 bg-light border">
                        <div class="card-body p-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="checkBuatAkun" name="buat_akun" value="1" <?= $buat_akun ? 'checked' : '' ?> onchange="toggleFormAkun()">
                                <label class="form-check-label fw-bold" for="checkBuatAkun">
                                    <i class="bi bi-key me-1 text-primary"></i> Sekaligus Buat Akun Login Aplikasi
                                </label>
                            </div>
                            <div id="boxFormAkun" style="display: <?= $buat_akun ? 'block' : 'none' ?>;">
                                <div class="row g-2 pt-2">
                                    <div class="col-md-4">
                                        <label class="form-label small">Username Login</label>
                                        <input type="text" name="username_baru" class="form-control form-control-sm" placeholder="Contoh: ahmad.rizki" value="<?= escape($username_baru) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Password Awal</label>
                                        <input type="text" name="password_baru" class="form-control form-control-sm" value="<?= escape($password_baru) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Hak Akses / Peran</label>
                                        <select name="peran_baru" class="form-select form-select-sm">
                                            <option value="kasir" <?= ($peran_baru === 'kasir') ? 'selected' : '' ?>>Kasir</option>
                                            <option value="gudang" <?= ($peran_baru === 'gudang') ? 'selected' : '' ?>>Gudang</option>
                                            <option value="manajer" <?= ($peran_baru === 'manajer') ? 'selected' : '' ?>>Manajer</option>
                                            <option value="admin" <?= ($peran_baru === 'admin') ? 'selected' : '' ?>>Administrator</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/karyawan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Karyawan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFormAkun() {
    const isChecked = document.getElementById('checkBuatAkun').checked;
    document.getElementById('boxFormAkun').style.display = isChecked ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
