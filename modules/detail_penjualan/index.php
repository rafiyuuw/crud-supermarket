<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Detail Penjualan (Daftar & Read)
 * File: modules/detail_penjualan/index.php
 */

$judul_halaman = 'Detail Penjualan';
$menu_aktif = 'detail_penjualan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_pj = (int)($_GET['id_penjualan'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM detail_penjualan dp
    INNER JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
    INNER JOIN produk pr ON dp.id_produk = pr.id_produk
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (p.nomor_faktur LIKE :cari OR pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_pj > 0) {
    $sqlCount .= " AND dp.id_penjualan = :id_pj";
    $params[':id_pj'] = $id_pj;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        dp.*,
        p.nomor_faktur,
        p.tanggal_penjualan,
        p.status_pembayaran,
        pr.kode_produk,
        pr.nama_produk,
        s.simbol AS satuan
    FROM detail_penjualan dp
    INNER JOIN penjualan p ON dp.id_penjualan = p.id_penjualan
    INNER JOIN produk pr ON dp.id_produk = pr.id_produk
    INNER JOIN satuan_produk s ON pr.id_satuan = s.id_satuan
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (p.nomor_faktur LIKE :cari OR pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari)";
}
if ($id_pj > 0) {
    $sqlData .= " AND dp.id_penjualan = :id_pj";
}
$sqlData .= " ORDER BY dp.id_detail_penjualan DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_detail = $stmt->fetchAll();

$daftar_faktur = $pdo->query("SELECT id_penjualan, nomor_faktur FROM penjualan ORDER BY id_penjualan DESC LIMIT 50")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Rincian Item: Detail Penjualan</h4>
        <p class="text-muted small mb-0">Daftar baris item produk, kuantitas, harga satuan saat transaksi, dan diskon item.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($id_pj > 0): ?>
            <a href="<?= base_url('modules/penjualan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar Faktur
            </a>
        <?php endif; ?>
        <a href="<?= base_url('modules/detail_penjualan/tambah.php' . ($id_pj ? '?id_penjualan=' . $id_pj : '')) ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item Manual
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nomor faktur atau nama produk..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-4">
                <select name="id_penjualan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Faktur Penjualan</option>
                    <?php foreach ($daftar_faktur as $f): ?>
                        <option value="<?= $f['id_penjualan'] ?>" <?= ($id_pj === (int)$f['id_penjualan']) ? 'selected' : '' ?>>
                            Faktur: <?= escape($f['nomor_faktur']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> item
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nomor Faktur</th>
                    <th>Produk</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Diskon Item</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_detail)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-basket2 fs-3 d-block mb-1"></i> Rincian detail produk tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_detail as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <a href="<?= base_url('modules/penjualan/index.php?cari=' . urlencode($row['nomor_faktur'])) ?>" class="fw-bold text-decoration-none">
                                    <code><?= escape($row['nomor_faktur']) ?></code>
                                </a>
                                <div><small class="text-muted"><?= tanggal_indo($row['tanggal_penjualan'], true) ?></small></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_produk']) ?></div>
                                <small class="text-muted"><code><?= escape($row['kode_produk']) ?></code></small>
                            </td>
                            <td class="text-center fw-bold">
                                <?= $row['jumlah'] ?> <?= escape($row['satuan']) ?>
                            </td>
                            <td class="text-end text-muted small"><?= rupiah($row['harga_satuan']) ?></td>
                            <td class="text-end text-danger small"><?= (float)$row['diskon_item'] > 0 ? '-' . rupiah($row['diskon_item']) : '-' ?></td>
                            <td class="text-end fw-bold text-primary"><?= rupiah($row['subtotal']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/detail_penjualan/edit.php?id=' . $row['id_detail_penjualan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Item">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/detail_penjualan/hapus.php?id=' . $row['id_detail_penjualan']) ?>" data-nama="<?= escape($row['nama_produk']) ?> (Faktur: <?= escape($row['nomor_faktur']) ?>)" title="Hapus Item">
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
            <?= render_pagination(base_url('modules/detail_penjualan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_penjualan' => $id_pj]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
