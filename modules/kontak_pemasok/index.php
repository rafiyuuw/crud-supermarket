<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Kontak Pemasok (Daftar & Read)
 * File: modules/kontak_pemasok/index.php
 */

$judul_halaman = 'Data Kontak Pemasok';
$menu_aktif = 'kontak_pemasok';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_spl = (int)($_GET['id_pemasok'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM kontak_pemasok kp
    INNER JOIN pemasok p ON kp.id_pemasok = p.id_pemasok
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (kp.nama_kontak LIKE :cari OR kp.nomor_hp LIKE :cari OR p.nama_pemasok LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_spl > 0) {
    $sqlCount .= " AND kp.id_pemasok = :id_spl";
    $params[':id_spl'] = $id_spl;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        kp.*,
        p.kode_pemasok,
        p.nama_pemasok
    FROM kontak_pemasok kp
    INNER JOIN pemasok p ON kp.id_pemasok = p.id_pemasok
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (kp.nama_kontak LIKE :cari OR kp.nomor_hp LIKE :cari OR p.nama_pemasok LIKE :cari)";
}
if ($id_spl > 0) {
    $sqlData .= " AND kp.id_pemasok = :id_spl";
}
$sqlData .= " ORDER BY kp.id_kontak DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_kontak = $stmt->fetchAll();

$opsi_pemasok = $pdo->query("SELECT id_pemasok, nama_pemasok FROM pemasok ORDER BY nama_pemasok ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Kontak Person Pemasok</h4>
        <p class="text-muted small mb-0">Daftar PIC (Person In Charge), sales representative, dan admin logistik pemasok.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/kontak_pemasok/tambah.php' . ($id_spl ? '?id_pemasok=' . $id_spl : '')) ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kontak PIC
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama kontak, nomor HP, pemasok..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-4">
                <select name="id_pemasok" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Pemasok</option>
                    <?php foreach ($opsi_pemasok as $spl): ?>
                        <option value="<?= $spl['id_pemasok'] ?>" <?= ($id_spl === (int)$spl['id_pemasok']) ? 'selected' : '' ?>>
                            <?= escape($spl['nama_pemasok']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> kontak
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nama Kontak (PIC)</th>
                    <th>Perusahaan Pemasok</th>
                    <th>Jabatan</th>
                    <th>Nomor HP</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_kontak)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-person-x fs-3 d-block mb-1"></i> Data kontak pemasok tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_kontak as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold">
                                <i class="bi bi-person-circle me-1 text-primary"></i> <?= escape($row['nama_kontak']) ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_pemasok']) ?></div>
                                <small class="text-muted"><code><?= escape($row['kode_pemasok']) ?></code></small>
                            </td>
                            <td><?= escape($row['jabatan'] ?? '-') ?></td>
                            <td><a href="tel:<?= escape($row['nomor_hp']) ?>" class="text-decoration-none"><i class="bi bi-telephone me-1"></i><?= escape($row['nomor_hp']) ?></a></td>
                            <td><small><?= escape($row['email'] ?? '-') ?></small></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/kontak_pemasok/edit.php?id=' . $row['id_kontak']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/kontak_pemasok/hapus.php?id=' . $row['id_kontak']) ?>" data-nama="<?= escape($row['nama_kontak']) ?> (<?= escape($row['nama_pemasok']) ?>)" title="Hapus Kontak">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paginasi['total_halaman'] > 1): ?>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Halaman <?= $paginasi['halaman_aktif'] ?> dari <?= $paginasi['total_halaman'] ?></span>
            <?= render_pagination(base_url('modules/kontak_pemasok/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_pemasok' => $id_spl]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
