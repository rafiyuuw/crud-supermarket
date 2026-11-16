<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pemasok (Daftar & Read)
 * File: modules/pemasok/index.php
 */

$judul_halaman = 'Data Pemasok';
$menu_aktif = 'pemasok';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "SELECT COUNT(*) FROM pemasok WHERE 1=1";
$params = [];
if ($cari !== '') {
    $sqlCount .= " AND (nama_pemasok LIKE :cari OR kode_pemasok LIKE :cari OR telepon LIKE :cari OR alamat LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM kontak_pemasok kp WHERE kp.id_pemasok = p.id_pemasok) AS total_kontak,
        (SELECT COUNT(*) FROM pesanan_pembelian po WHERE po.id_pemasok = p.id_pemasok) AS total_po
    FROM pemasok p
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (p.nama_pemasok LIKE :cari OR p.kode_pemasok LIKE :cari OR p.telepon LIKE :cari OR p.alamat LIKE :cari)";
}
$sqlData .= " ORDER BY p.id_pemasok ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_pemasok = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Data Pemasok (Supplier)</h4>
        <p class="text-muted small mb-0">Kelola distributor, produsen, dan mitra rantai pasok supermarket.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/pemasok/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pemasok
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-6 col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama, kode, atau telepon pemasok..." value="<?= escape($cari) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="<?= base_url('modules/pemasok/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6 col-md-8 text-sm-end text-muted small">
                Menampilkan <strong><?= $paginasi['dari'] ?>-<?= $paginasi['sampai'] ?></strong> dari <strong><?= $paginasi['total_data'] ?></strong> pemasok
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Pemasok</th>
                    <th>Alamat</th>
                    <th>Telepon & Email</th>
                    <th>NPWP</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_pemasok)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-truck fs-3 d-block mb-1"></i> Data pemasok tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_pemasok as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_pemasok']) ?></code></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_pemasok']) ?></div>
                                <div class="mt-1 d-flex gap-1">
                                    <a href="<?= base_url('modules/kontak_pemasok/index.php?cari=' . urlencode($row['nama_pemasok'])) ?>" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-person-lines-fill me-1"></i><?= $row['total_kontak'] ?> Kontak
                                    </a>
                                    <a href="<?= base_url('modules/pesanan_pembelian/index.php?cari=' . urlencode($row['nama_pemasok'])) ?>" class="badge bg-light text-secondary border text-decoration-none">
                                        <i class="bi bi-receipt me-1"></i><?= $row['total_po'] ?> PO
                                    </a>
                                </div>
                            </td>
                            <td><small><?= escape($row['alamat']) ?></small></td>
                            <td>
                                <div><small><i class="bi bi-telephone me-1 text-muted"></i><?= escape($row['telepon']) ?></small></div>
                                <div><small><i class="bi bi-envelope me-1 text-muted"></i><?= escape($row['email'] ?? '-') ?></small></div>
                            </td>
                            <td><small><code><?= escape($row['npwp'] ?? '-') ?></code></small></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/kontak_pemasok/tambah.php?id_pemasok=' . $row['id_pemasok']) ?>" class="btn btn-outline-info btn-sm py-0 px-2" title="Tambah Kontak PIC">
                                        <i class="bi bi-person-plus"></i>
                                    </a>
                                    <a href="<?= base_url('modules/pemasok/edit.php?id=' . $row['id_pemasok']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/pemasok/hapus.php?id=' . $row['id_pemasok']) ?>" data-nama="<?= escape($row['nama_pemasok']) ?>" title="Hapus Pemasok">
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
            <?= render_pagination(base_url('modules/pemasok/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
