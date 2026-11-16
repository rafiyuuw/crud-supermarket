<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pembayaran Penjualan (Daftar & Read)
 * File: modules/pembayaran_penjualan/index.php
 */

$judul_halaman = 'Pembayaran Penjualan';
$menu_aktif = 'pembayaran_penjualan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_pj = (int)($_GET['id_penjualan'] ?? 0);
$id_metode = (int)($_GET['id_metode'] ?? 0);
$status = trim($_GET['status'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM pembayaran_penjualan pp
    INNER JOIN penjualan p ON pp.id_penjualan = p.id_penjualan
    INNER JOIN metode_pembayaran mp ON pp.id_metode = mp.id_metode
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (p.nomor_faktur LIKE :cari OR pp.nomor_transaksi LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_pj > 0) {
    $sqlCount .= " AND pp.id_penjualan = :id_pj";
    $params[':id_pj'] = $id_pj;
}
if ($id_metode > 0) {
    $sqlCount .= " AND pp.id_metode = :id_metode";
    $params[':id_metode'] = $id_metode;
}
if ($status !== '') {
    $sqlCount .= " AND pp.status = :status";
    $params[':status'] = $status;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        pp.*,
        p.nomor_faktur,
        p.tanggal_penjualan,
        p.total_akhir,
        mp.nama_metode,
        mp.tipe AS tipe_metode
    FROM pembayaran_penjualan pp
    INNER JOIN penjualan p ON pp.id_penjualan = p.id_penjualan
    INNER JOIN metode_pembayaran mp ON pp.id_metode = mp.id_metode
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (p.nomor_faktur LIKE :cari OR pp.nomor_transaksi LIKE :cari)";
}
if ($id_pj > 0) {
    $sqlData .= " AND pp.id_penjualan = :id_pj";
}
if ($id_metode > 0) {
    $sqlData .= " AND pp.id_metode = :id_metode";
}
if ($status !== '') {
    $sqlData .= " AND pp.status = :status";
}
$sqlData .= " ORDER BY pp.id_pembayaran DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_pembayaran = $stmt->fetchAll();

$daftar_metode = $pdo->query("SELECT id_metode, nama_metode FROM metode_pembayaran WHERE status = 'aktif' ORDER BY nama_metode ASC")->fetchAll();
$daftar_faktur = $pdo->query("SELECT id_penjualan, nomor_faktur FROM penjualan ORDER BY id_penjualan DESC LIMIT 50")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pembayaran Penjualan</h4>
        <p class="text-muted small mb-0">Kelola dan pantau catatan pembayaran faktur penjualan kasir atau online.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($id_pj > 0): ?>
            <a href="<?= base_url('modules/penjualan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali ke Penjualan
            </a>
        <?php endif; ?>
        <a href="<?= base_url('modules/pembayaran_penjualan/tambah.php' . ($id_pj ? '?id_penjualan=' . $id_pj : '')) ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Catat Pembayaran
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-4 col-md-3">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari faktur / no transaksi..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-3 col-md-3">
                <select name="id_metode" class="form-select form-select-sm">
                    <option value="">Semua Metode Pembayaran</option>
                    <?php foreach ($daftar_metode as $m): ?>
                        <option value="<?= $m['id_metode'] ?>" <?= ($id_metode === (int)$m['id_metode']) ? 'selected' : '' ?>>
                            <?= escape($m['nama_metode']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="berhasil" <?= ($status === 'berhasil') ? 'selected' : '' ?>>Berhasil</option>
                    <option value="menunggu" <?= ($status === 'menunggu') ? 'selected' : '' ?>>Menunggu</option>
                    <option value="gagal" <?= ($status === 'gagal') ? 'selected' : '' ?>>Gagal</option>
                </select>
            </div>
            <div class="col-sm-2 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> pembayaran
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nomor Faktur</th>
                    <th>Metode</th>
                    <th>No. Transaksi</th>
                    <th>Waktu Pembayaran</th>
                    <th class="text-end">Jumlah Bayar</th>
                    <th class="text-end">Kembalian</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_pembayaran)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-cash-coin fs-3 d-block mb-1"></i> Data pembayaran penjualan tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_pembayaran as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <a href="<?= base_url('modules/penjualan/index.php?cari=' . urlencode($row['nomor_faktur'])) ?>" class="fw-bold text-decoration-none">
                                    <code><?= escape($row['nomor_faktur']) ?></code>
                                </a>
                                <div><small class="text-muted">Total: <?= rupiah($row['total_akhir']) ?></small></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= escape($row['nama_metode']) ?>
                                </span>
                                <div><small class="text-muted"><?= ucfirst($row['tipe_metode']) ?></small></div>
                            </td>
                            <td>
                                <?= !empty($row['nomor_transaksi']) ? '<code>' . escape($row['nomor_transaksi']) . '</code>' : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="small text-muted">
                                <?= tanggal_indo($row['tanggal_bayar'], true) ?>
                            </td>
                            <td class="text-end fw-bold text-success">
                                <?= rupiah($row['jumlah_bayar']) ?>
                            </td>
                            <td class="text-end text-muted small">
                                <?= (float)$row['jumlah_kembalian'] > 0 ? rupiah($row['jumlah_kembalian']) : '-' ?>
                            </td>
                            <td class="text-center">
                                <?= badge_status($row['status']) ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/pembayaran_penjualan/edit.php?id=' . $row['id_pembayaran']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Pembayaran">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/pembayaran_penjualan/hapus.php?id=' . $row['id_pembayaran']) ?>" data-nama="Pembayaran <?= escape($row['nomor_faktur']) ?> (<?= rupiah($row['jumlah_bayar']) ?>)" title="Hapus Pembayaran">
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
            <?= render_pagination(base_url('modules/pembayaran_penjualan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], [
                'cari' => $cari,
                'id_penjualan' => $id_pj,
                'id_metode' => $id_metode,
                'status' => $status
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
