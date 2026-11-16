<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pelanggan (Edit Data)
 * File: modules/pelanggan/edit.php
 */

$judul_halaman = 'Edit Pelanggan';
$menu_aktif = 'pelanggan';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$stmt->execute([$id]);
$plg = $stmt->fetch();

if (!$plg) {
    flash_set('danger', 'Data pelanggan tidak ditemukan.');
    header('Location: ' . base_url('modules/pelanggan/index.php'));
    exit;
}

$error = '';
$kode_pelanggan = $plg['kode_pelanggan'];
$nama_lengkap = $plg['nama_lengkap'];
$nomor_telepon = $plg['nomor_telepon'];
$email = $plg['email'] ?? '';
$poin_loyalitas = (int)$plg['poin_loyalitas'];
$status = $plg['status'];
$tanggal_daftar = $plg['tanggal_daftar'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $kode_pelanggan = trim($_POST['kode_pelanggan'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $poin_loyalitas = max(0, (int)($_POST['poin_loyalitas'] ?? 0));
    $status = $_POST['status'] ?? 'aktif';
    $tanggal_daftar = $_POST['tanggal_daftar'] ?? '';

    if (empty($kode_pelanggan) || empty($nama_lengkap) || empty($nomor_telepon)) {
        $error = 'Kode pelanggan, nama lengkap, dan nomor telepon wajib diisi.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM pelanggan WHERE kode_pelanggan = ? AND id_pelanggan != ?");
        $stmtCek->execute([$kode_pelanggan, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $error = "Kode pelanggan '{$kode_pelanggan}' sudah digunakan oleh pelanggan lain.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE pelanggan 
                    SET kode_pelanggan = ?, nama_lengkap = ?, nomor_telepon = ?, email = ?, poin_loyalitas = ?, status = ?, tanggal_daftar = ?
                    WHERE id_pelanggan = ?
                ");
                $stmtUpdate->execute([
                    $kode_pelanggan,
                    $nama_lengkap,
                    $nomor_telepon,
                    $email ?: null,
                    $poin_loyalitas,
                    $status,
                    $tanggal_daftar,
                    $id
                ]);

                flash_set('success', "Pelanggan <strong>" . escape($nama_lengkap) . "</strong> berhasil diperbarui!");
                header('Location: ' . base_url('modules/pelanggan/index.php'));
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
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Pelanggan</span>
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
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= escape($nama_lengkap) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nomor Handphone / WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_telepon" class="form-control" value="<?= escape($nomor_telepon) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" name="email" class="form-control" value="<?= escape($email) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Poin Loyalitas</label>
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

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/pelanggan/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Pelanggan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
