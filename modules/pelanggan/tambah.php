<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pelanggan (Tambah Data)
 * File: modules/pelanggan/tambah.php
 */

$judul_halaman = 'Tambah Pelanggan';
$menu_aktif = 'pelanggan';

require_once __DIR__ . '/../../includes/header.php';

$error = '';
$kode_pelanggan = 'PLG-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);
$nama_lengkap = $_POST['nama_lengkap'] ?? '';
$nomor_telepon = $_POST['nomor_telepon'] ?? '';
$email = $_POST['email'] ?? '';
$poin_loyalitas = (int)($_POST['poin_loyalitas'] ?? 0);
$status = $_POST['status'] ?? 'aktif';
$tanggal_daftar = $_POST['tanggal_daftar'] ?? date('Y-m-d');

// Input Alamat Awal (Opsional)
$alamat_lengkap = $_POST['alamat_lengkap'] ?? '';
$kota = $_POST['kota'] ?? 'Jakarta';
$provinsi = $_POST['provinsi'] ?? 'DKI Jakarta';
$kode_pos = $_POST['kode_pos'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_pelanggan = trim($_POST['kode_pelanggan'] ?? '');
    $nama_lengkap = trim($nama_lengkap);
    $nomor_telepon = trim($nomor_telepon);
    $email = trim($email);

    if (empty($kode_pelanggan) || empty($nama_lengkap) || empty($nomor_telepon)) {
        $error = 'Kode pelanggan, nama lengkap, dan nomor telepon wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM pelanggan WHERE kode_pelanggan = ?");
        $stmtCek->execute([$kode_pelanggan]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode pelanggan '{$kode_pelanggan}' sudah digunakan.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Simpan Data Pelanggan
                $stmt = $pdo->prepare("
                    INSERT INTO pelanggan (kode_pelanggan, nama_lengkap, nomor_telepon, email, poin_loyalitas, status, tanggal_daftar)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $kode_pelanggan,
                    $nama_lengkap,
                    $nomor_telepon,
                    $email ?: null,
                    $poin_loyalitas,
                    $status,
                    $tanggal_daftar
                ]);
                $id_pelanggan_baru = (int)$pdo->lastInsertId();

                // 2. Simpan Alamat Awal jika diisi
                if (!empty(trim($alamat_lengkap))) {
                    $stmtAlamat = $pdo->prepare("
                        INSERT INTO alamat_pelanggan (id_pelanggan, label_alamat, alamat_lengkap, kota, provinsi, kode_pos, status_utama)
                        VALUES (?, 'Rumah Utama', ?, ?, ?, ?, 'ya')
                    ");
                    $stmtAlamat->execute([
                        $id_pelanggan_baru,
                        trim($alamat_lengkap),
                        trim($kota),
                        trim($provinsi),
                        trim($kode_pos)
                    ]);
                }

                $pdo->commit();

                flash_set('success', "Pelanggan <strong>" . escape($nama_lengkap) . "</strong> berhasil didaftarkan!");
                header('Location: ' . base_url('modules/pelanggan/index.php'));
                exit;

            } catch (PDOException $e) {
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
                <span class="fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Registrasi Pelanggan Baru</span>
                <a href="<?= base_url('modules/pelanggan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                            <label class="form-label">Kode Pelanggan (Member) <span class="text-danger">*</span></label>
                            <input type="text" name="kode_pelanggan" class="form-control" value="<?= escape($kode_pelanggan) ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nama Lengkap Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama sesuai KTP/identitas" value="<?= escape($nama_lengkap) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor Handphone / WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_telepon" class="form-control" placeholder="0812-xxxx-xxxx" value="<?= escape($nomor_telepon) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= escape($email) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Poin Loyalitas Awal</label>
                            <input type="number" min="0" name="poin_loyalitas" class="form-control" value="<?= $poin_loyalitas ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status Keanggotaan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Bergabung <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_daftar" class="form-control" value="<?= escape($tanggal_daftar) ?>" required>
                        </div>
                    </div>

                    <!-- Input Alamat Awal -->
                    <div class="card mt-4 bg-light border">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-geo-alt text-primary me-2"></i>Alamat Tempat Tinggal (Opsional)</h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label small">Alamat Lengkap</label>
                                    <textarea name="alamat_lengkap" class="form-control form-control-sm" rows="2" placeholder="Jalan, nomor rumah, RT/RW, kelurahan"><?= escape($alamat_lengkap) ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Kota / Kabupaten</label>
                                    <input type="text" name="kota" class="form-control form-control-sm" value="<?= escape($kota) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Provinsi</label>
                                    <input type="text" name="provinsi" class="form-control form-control-sm" value="<?= escape($provinsi) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Kode Pos</label>
                                    <input type="text" name="kode_pos" class="form-control form-control-sm" placeholder="Contoh: 12130" value="<?= escape($kode_pos) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/pelanggan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-save me-1"></i> Simpan Pelanggan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
