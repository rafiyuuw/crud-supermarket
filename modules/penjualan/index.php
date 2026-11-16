<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Penjualan (Daftar & Read)
 * File: modules/penjualan/index.php
 */

$judul_halaman = 'Data Penjualan';
$menu_aktif = 'penjualan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM penjualan p
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    INNER JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (p.nomor_faktur LIKE :cari OR pl.nama_lengkap LIKE :cari OR k.nama_karyawan LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($status_filter !== '') {
    $sqlCount .= " AND p.status_pembayaran = :status";
    $params[':status'] = $status_filter;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        p.*,
        c.nama_cabang,
        k.nama_karyawan AS nama_kasir,
        COALESCE(pl.nama_lengkap, 'Pelanggan Umum (Non-Member)') AS nama_pelanggan,
        (SELECT COUNT(*) FROM detail_penjualan dp WHERE dp.id_penjualan = p.id_penjualan) AS total_item,
        (SELECT COUNT(*) FROM pembayaran_penjualan pp WHERE pp.id_penjualan = p.id_penjualan) AS total_pembayaran
    FROM penjualan p
    INNER JOIN cabang c ON p.id_cabang = c.id_cabang
    INNER JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (p.nomor_faktur LIKE :cari OR pl.nama_lengkap LIKE :cari OR k.nama_karyawan LIKE :cari)";
}
if ($status_filter !== '') {
    $sqlData .= " AND p.status_pembayaran = :status";
}
$sqlData .= " ORDER BY p.id_penjualan DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_penjualan = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Transaksi Penjualan</h4>
        <p class="text-muted small mb-0">Riwayat transaksi kasir, rincian faktur, dan status pembayaran belanja supermarket.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('modules/detail_penjualan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-stars me-1"></i> Seluruh Detail Item
        </a>
        <a href="<?= base_url('pos.php') ?>" class="btn btn-success btn-sm shadow-sm">
            <i class="bi bi-cart-plus me-1"></i> Buka Kasir Cepat (POS)
        </a>
        <a href="<?= base_url('modules/penjualan/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Penjualan Manual
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari faktur, pelanggan, kasir..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Status Bayar</option>
                    <option value="lunas" <?= ($status_filter === 'lunas') ? 'selected' : '' ?>>Lunas</option>
                    <option value="belum_lunas" <?= ($status_filter === 'belum_lunas') ? 'selected' : '' ?>>Belum Lunas</option>
                    <option value="batal" <?= ($status_filter === 'batal') ? 'selected' : '' ?>>Batal</option>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> faktur transaksi
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>No. Faktur</th>
                    <th>Waktu Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th>Item & Pembayaran</th>
                    <th class="text-end">Total Akhir</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_penjualan)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-receipt fs-3 d-block mb-1"></i> Data transaksi penjualan tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_penjualan as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <span class="fw-bold text-primary"><code><?= escape($row['nomor_faktur']) ?></code></span>
                                <div><small class="text-muted"><i class="bi bi-building me-1"></i><?= escape($row['nama_cabang']) ?></small></div>
                            </td>
                            <td><small><?= tanggal_indo($row['tanggal_penjualan'], true) ?></small></td>
                            <td><?= escape($row['nama_pelanggan']) ?></td>
                            <td><small><i class="bi bi-person me-1 text-muted"></i><?= escape($row['nama_kasir']) ?></small></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= base_url('modules/detail_penjualan/index.php?id_penjualan=' . $row['id_penjualan']) ?>" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-box me-1"></i><?= $row['total_item'] ?> Item
                                    </a>
                                    <a href="<?= base_url('modules/pembayaran_penjualan/index.php?id_penjualan=' . $row['id_penjualan']) ?>" class="badge bg-light text-success border text-decoration-none">
                                        <i class="bi bi-cash me-1"></i>Bayar
                                    </a>
                                </div>
                            </td>
                            <td class="text-end fw-bold text-dark"><?= rupiah($row['total_akhir']) ?></td>
                            <td><?= badge_status($row['status_pembayaran']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('cetak_faktur.php?id=' . $row['id_penjualan']) ?>" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" title="Cetak Nota">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="<?= base_url('modules/penjualan/edit.php?id=' . $row['id_penjualan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Penjualan">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/penjualan/hapus.php?id=' . $row['id_penjualan']) ?>" data-nama="Faktur <?= escape($row['nomor_faktur']) ?>" title="Hapus Penjualan">
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
            <?= render_pagination(base_url('modules/penjualan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'status' => $status_filter]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
