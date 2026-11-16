<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Kontak Pemasok (Edit Data)
 * File: modules/kontak_pemasok/edit.php
 */

$judul_halaman = 'Edit Kontak Pemasok';
$menu_aktif = 'kontak_pemasok';

require_once __DIR__ . '/../../includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM kontak_pemasok WHERE id_kontak = ?");
$stmt->execute([$id]);
$kp = $stmt->fetch();

if (!$kp) {
    flash_set('danger', 'Data kontak tidak ditemukan.');
    header('Location: ' . base_url('modules/kontak_pemasok/index.php'));
    exit;
}

$daftar_pemasok = $pdo->query("SELECT id_pemasok, nama_pemasok FROM pemasok WHERE status = 'aktif' ORDER BY nama_pemasok ASC")->fetchAll();

$error = '';
$id_pemasok = (int)$kp['id_pemasok'];
$nama_kontak = $kp['nama_kontak'];
$jabatan = $kp['jabatan'] ?? '';
$nomor_hp = $kp['nomor_hp'];
$email = $kp['email'] ?? '';
$status = $kp['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id_pemasok = (int)($_POST['id_pemasok'] ?? 0);
    $nama_kontak = trim($_POST['nama_kontak'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $nomor_hp = trim($_POST['nomor_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'utama';

    if (empty($id_pemasok) || empty($nama_kontak) || empty($nomor_hp)) {
        $error = 'Pemasok, nama kontak, dan nomor handphone wajib diisi.';
    } else {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE kontak_pemasok SET id_pemasok = ?, nama_kontak = ?, jabatan = ?, nomor_hp = ?, email = ?, status = ? WHERE id_kontak = ?");
            $stmtUpdate->execute([$id_pemasok, $nama_kontak, $jabatan ?: null, $nomor_hp, $email ?: null, $status, $id]);

            flash_set('success', "Kontak <strong>" . escape($nama_kontak) . "</strong> berhasil diperbarui!");
            header('Location: ' . base_url('modules/kontak_pemasok/index.php?id_pemasok=' . $id_pemasok));
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i>Formulir Edit Kontak Pemasok</span>
                <a href="<?= base_url('modules/kontak_pemasok/index.php') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <label class="form-label">Perusahaan Pemasok <span class="text-danger">*</span></label>
                        <select name="id_pemasok" class="form-select" required>
                            <option value="">-- Pilih Perusahaan Pemasok --</option>
                            <?php foreach ($daftar_pemasok as $spl): ?>
                                <option value="<?= $spl['id_pemasok'] ?>" <?= ($id_pemasok === (int)$spl['id_pemasok']) ? 'selected' : '' ?>>
                                    <?= escape($spl['nama_pemasok']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap PIC (Kontak) <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kontak" class="form-control" value="<?= escape($nama_kontak) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jabatan PIC</label>
                        <input type="text" name="jabatan" class="form-control" value="<?= escape($jabatan) ?>">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor Handphone / WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_hp" class="form-control" value="<?= escape($nomor_hp) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email PIC</label>
                            <input type="email" name="email" class="form-control" value="<?= escape($email) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Prioritas Kontak <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="utama" <?= ($status === 'utama') ? 'selected' : '' ?>>Kontak Utama (Primary PIC)</option>
                            <option value="cadangan" <?= ($status === 'cadangan') ? 'selected' : '' ?>>Kontak Cadangan / Alternatif</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= base_url('modules/kontak_pemasok/index.php') ?>" class="btn btn-light btn-sm px-3">Batal</a>
                        <button type="submit" class="btn btn-warning btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Kontak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
