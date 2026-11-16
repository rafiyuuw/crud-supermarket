<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Merek Produk (Daftar & Read)
 * File: modules/merek_produk/index.php
 */

$judul_halaman = 'Data Merek Produk';
$menu_aktif = 'merek_produk';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "SELECT COUNT(*) FROM merek_produk WHERE 1=1";
$params = [];
if ($cari !== '') {
    $sqlCount .= " AND (nama_merek LIKE :cari OR kode_merek LIKE :cari OR produsen LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "SELECT * FROM merek_produk WHERE 1=1";
if ($cari !== '') {
    $sqlData .= " AND (nama_merek LIKE :cari OR kode_merek LIKE :cari OR produsen LIKE :cari)";
}
$sqlData .= " ORDER BY id_merek ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_merek = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Merek Produk (Brand)</h4>
        <p class="text-muted small mb-0">Kelola daftar merek resmi dan produsen produk.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/merek_produk/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Merek
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-6 col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="cari" class="form-control" placeholder="Cari merek atau produsen..." value="<?= escape($cari) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="<?= base_url('modules/merek_produk/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6 col-md-8 text-sm-end text-muted small">
                Menampilkan <strong><?= $paginasi['dari'] ?>-<?= $paginasi['sampai'] ?></strong> dari <strong><?= $paginasi['total_data'] ?></strong> merek
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Merek</th>
                    <th>Produsen / Manufaktur</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_merek)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i> Data merek produk tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_merek as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_merek']) ?></code></td>
                            <td class="fw-semibold"><?= escape($row['nama_merek']) ?></td>
                            <td><?= escape($row['produsen'] ?? '-') ?></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/merek_produk/edit.php?id=' . $row['id_merek']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/merek_produk/hapus.php?id=' . $row['id_merek']) ?>" data-nama="<?= escape($row['nama_merek']) ?>" title="Hapus Merek">
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
            <?= render_pagination(base_url('modules/merek_produk/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
