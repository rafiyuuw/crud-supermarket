<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Produk (Daftar & Read)
 * File: modules/produk/index.php
 */

$judul_halaman = 'Data Produk';
$menu_aktif = 'produk';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_sub = (int)($_GET['id_subkategori'] ?? 0);
$id_mrk = (int)($_GET['id_merek'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM produk p
    INNER JOIN subkategori sk ON p.id_subkategori = sk.id_subkategori
    INNER JOIN merek_produk m ON p.id_merek = m.id_merek
    INNER JOIN satuan_produk s ON p.id_satuan = s.id_satuan
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (p.nama_produk LIKE :cari OR p.kode_produk LIKE :cari OR m.nama_merek LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_sub > 0) {
    $sqlCount .= " AND p.id_subkategori = :id_sub";
    $params[':id_sub'] = $id_sub;
}
if ($id_mrk > 0) {
    $sqlCount .= " AND p.id_merek = :id_mrk";
    $params[':id_mrk'] = $id_mrk;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

// Ambil Data Produk lengkap dengan total stok persediaan
$sqlData = "
    SELECT 
        p.*,
        sk.nama_subkategori,
        m.nama_merek,
        s.simbol AS satuan,
        COALESCE(SUM(ps.jumlah_stok), 0) AS total_stok,
        (SELECT COUNT(*) FROM barcode_produk b WHERE b.id_produk = p.id_produk) AS jumlah_barcode
    FROM produk p
    INNER JOIN subkategori sk ON p.id_subkategori = sk.id_subkategori
    INNER JOIN merek_produk m ON p.id_merek = m.id_merek
    INNER JOIN satuan_produk s ON p.id_satuan = s.id_satuan
    LEFT JOIN persediaan ps ON p.id_produk = ps.id_produk
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (p.nama_produk LIKE :cari OR p.kode_produk LIKE :cari OR m.nama_merek LIKE :cari)";
}
if ($id_sub > 0) {
    $sqlData .= " AND p.id_subkategori = :id_sub";
}
if ($id_mrk > 0) {
    $sqlData .= " AND p.id_merek = :id_mrk";
}
$sqlData .= " GROUP BY p.id_produk ORDER BY p.id_produk DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_produk = $stmt->fetchAll();

// Dropdown Opsi Filter
$daftar_subkategori = $pdo->query("SELECT id_subkategori, nama_subkategori FROM subkategori ORDER BY nama_subkategori ASC")->fetchAll();
$daftar_merek = $pdo->query("SELECT id_merek, nama_merek FROM merek_produk ORDER BY nama_merek ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Katalog Produk</h4>
        <p class="text-muted small mb-0">Kelola master data produk supermarket, harga beli, harga jual, dan stok minimum.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/produk/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari kode atau nama produk..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-3 col-md-3">
                <select name="id_subkategori" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Subkategori</option>
                    <?php foreach ($daftar_subkategori as $sub): ?>
                        <option value="<?= $sub['id_subkategori'] ?>" <?= ($id_sub === (int)$sub['id_subkategori']) ? 'selected' : '' ?>>
                            <?= escape($sub['nama_subkategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-3">
                <select name="id_merek" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Merek</option>
                    <?php foreach ($daftar_merek as $mrk): ?>
                        <option value="<?= $mrk['id_merek'] ?>" <?= ($id_mrk === (int)$mrk['id_merek']) ? 'selected' : '' ?>>
                            <?= escape($mrk['nama_merek']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-1 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th>Subkategori / Merek</th>
                    <th class="text-end">Harga Beli</th>
                    <th class="text-end">Harga Jual</th>
                    <th class="text-center">Stok</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_produk)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i> Data produk tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_produk as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_produk']) ?></code></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_produk']) ?></div>
                                <div class="d-flex gap-1 mt-1">
                                    <a href="<?= base_url('modules/barcode_produk/index.php?cari=' . urlencode($row['kode_produk'])) ?>" class="badge bg-light text-primary border text-decoration-none" title="Lihat barcode produk ini">
                                        <i class="bi bi-upc-scan me-1"></i><?= $row['jumlah_barcode'] ?> Barcode
                                    </a>
                                    <span class="badge bg-light text-muted border">Min: <?= $row['stok_minimum'] ?> <?= escape($row['satuan']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div><small class="text-dark fw-semibold"><?= escape($row['nama_merek']) ?></small></div>
                                <small class="text-muted"><?= escape($row['nama_subkategori']) ?></small>
                            </td>
                            <td class="text-end text-muted small"><?= rupiah($row['harga_beli']) ?></td>
                            <td class="text-end fw-bold text-success"><?= rupiah($row['harga_jual']) ?></td>
                            <td class="text-center">
                                <?php if ($row['total_stok'] <= $row['stok_minimum']): ?>
                                    <span class="badge bg-danger" title="Stok berada di bawah atau sama dengan stok minimum!">
                                        <?= $row['total_stok'] ?> <?= escape($row['satuan']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <?= $row['total_stok'] ?> <?= escape($row['satuan']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/barcode_produk/tambah.php?id_produk=' . $row['id_produk']) ?>" class="btn btn-outline-info btn-sm py-0 px-2" title="Tambah Barcode">
                                        <i class="bi bi-upc"></i>
                                    </a>
                                    <a href="<?= base_url('modules/produk/edit.php?id=' . $row['id_produk']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Produk">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/produk/hapus.php?id=' . $row['id_produk']) ?>" data-nama="<?= escape($row['nama_produk']) ?>" title="Hapus Produk">
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
            <?= render_pagination(base_url('modules/produk/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_subkategori' => $id_sub, 'id_merek' => $id_mrk]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
