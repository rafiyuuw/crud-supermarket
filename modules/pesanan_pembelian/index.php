<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pesanan Pembelian / Purchase Order (Daftar & Read)
 * File: modules/pesanan_pembelian/index.php
 */

$judul_halaman = 'Data Pesanan Pembelian';
$menu_aktif = 'pesanan_pembelian';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM pesanan_pembelian po
    INNER JOIN pemasok p ON po.id_pemasok = p.id_pemasok
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (po.nomor_pesanan LIKE :cari OR p.nama_pemasok LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($status_filter !== '') {
    $sqlCount .= " AND po.status = :status";
    $params[':status'] = $status_filter;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        po.*,
        p.nama_pemasok,
        k.nama_karyawan,
        g.nama_gudang,
        (SELECT COUNT(*) FROM detail_pesanan_pembelian dp WHERE dp.id_pesanan = po.id_pesanan) AS jumlah_item
    FROM pesanan_pembelian po
    INNER JOIN pemasok p ON po.id_pemasok = p.id_pemasok
    INNER JOIN karyawan k ON po.id_karyawan = k.id_karyawan
    INNER JOIN gudang g ON po.id_gudang = g.id_gudang
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (po.nomor_pesanan LIKE :cari OR p.nama_pemasok LIKE :cari)";
}
if ($status_filter !== '') {
    $sqlData .= " AND po.status = :status";
}
$sqlData .= " ORDER BY po.id_pesanan DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_po = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengadaan: Pesanan Pembelian (PO)</h4>
        <p class="text-muted small mb-0">Kelola pengadaan barang masuk dan restok dari pemasok ke gudang.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('modules/detail_pesanan_pembelian/index.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-card-checklist me-1"></i> Semua Detail Item PO
        </a>
        <a href="<?= base_url('modules/pesanan_pembelian/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Buat Pesanan Baru (PO)
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nomor PO atau nama pemasok..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="draft" <?= ($status_filter === 'draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="dipesan" <?= ($status_filter === 'dipesan') ? 'selected' : '' ?>>Dipesan</option>
                    <option value="diterima" <?= ($status_filter === 'diterima') ? 'selected' : '' ?>>Diterima</option>
                    <option value="dibatalkan" <?= ($status_filter === 'dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> pesanan
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nomor PO</th>
                    <th>Pemasok</th>
                    <th>Tujuan Gudang</th>
                    <th>Tgl Pesan / Estimasi</th>
                    <th class="text-end">Total Nominal</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_po)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-file-earmark-x fs-3 d-block mb-1"></i> Data pesanan pembelian tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_po as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-primary"><code><?= escape($row['nomor_pesanan']) ?></code></div>
                                <small class="text-muted"><i class="bi bi-person me-1"></i><?= escape($row['nama_karyawan']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_pemasok']) ?></div>
                                <a href="<?= base_url('modules/detail_pesanan_pembelian/index.php?id_pesanan=' . $row['id_pesanan']) ?>" class="badge bg-light text-primary border text-decoration-none">
                                    <i class="bi bi-box me-1"></i><?= $row['jumlah_item'] ?> Item Barang
                                </a>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= escape($row['nama_gudang']) ?></span></td>
                            <td>
                                <div><small><?= tanggal_indo($row['tanggal_pesanan']) ?></small></div>
                                <?php if ($row['tanggal_estimasi']): ?>
                                    <small class="text-muted">Est: <?= tanggal_indo($row['tanggal_estimasi']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold"><?= rupiah($row['total_nominal']) ?></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/detail_pesanan_pembelian/index.php?id_pesanan=' . $row['id_pesanan']) ?>" class="btn btn-outline-info btn-sm py-0 px-2" title="Kelola Item Rincian">
                                        <i class="bi bi-list-task"></i>
                                    </a>
                                    <a href="<?= base_url('modules/pesanan_pembelian/edit.php?id=' . $row['id_pesanan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah PO">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/pesanan_pembelian/hapus.php?id=' . $row['id_pesanan']) ?>" data-nama="PO <?= escape($row['nomor_pesanan']) ?> (<?= escape($row['nama_pemasok']) ?>)" title="Hapus PO">
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
            <?= render_pagination(base_url('modules/pesanan_pembelian/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'status' => $status_filter]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
