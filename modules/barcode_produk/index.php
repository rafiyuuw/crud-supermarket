<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Barcode Produk (Daftar & Read)
 * File: modules/barcode_produk/index.php
 */

$judul_halaman = 'Data Barcode Produk';
$menu_aktif = 'barcode_produk';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_prd = (int)($_GET['id_produk'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM barcode_produk b
    INNER JOIN produk p ON b.id_produk = p.id_produk
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (b.kode_barcode LIKE :cari OR p.nama_produk LIKE :cari OR p.kode_produk LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_prd > 0) {
    $sqlCount .= " AND b.id_produk = :id_prd";
    $params[':id_prd'] = $id_prd;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        b.*,
        p.kode_produk,
        p.nama_produk
    FROM barcode_produk b
    INNER JOIN produk p ON b.id_produk = p.id_produk
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (b.kode_barcode LIKE :cari OR p.nama_produk LIKE :cari OR p.kode_produk LIKE :cari)";
}
if ($id_prd > 0) {
    $sqlData .= " AND b.id_produk = :id_prd";
}
$sqlData .= " ORDER BY b.id_barcode DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_barcode = $stmt->fetchAll();

$daftar_produk_opsi = $pdo->query("SELECT id_produk, kode_produk, nama_produk FROM produk ORDER BY nama_produk ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Barcode Produk</h4>
        <p class="text-muted small mb-0">Kelola kode barcode fisik (EAN-13, QR Code, Code-128) per produk.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/barcode_produk/tambah.php' . ($id_prd ? '?id_produk=' . $id_prd : '')) ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Barcode
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari barcode atau nama produk..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-4">
                <select name="id_produk" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Produk</option>
                    <?php foreach ($daftar_produk_opsi as $p): ?>
                        <option value="<?= $p['id_produk'] ?>" <?= ($id_prd === (int)$p['id_produk']) ? 'selected' : '' ?>>
                            [<?= escape($p['kode_produk']) ?>] <?= escape($p['nama_produk']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> barcode
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode Barcode</th>
                    <th>Produk Terkait</th>
                    <th>Format / Tipe</th>
                    <th>Status</th>
                    <th>Catatan</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_barcode)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-upc-scan fs-3 d-block mb-1"></i> Data barcode tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_barcode as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold">
                                <span class="badge bg-light text-dark border font-monospace fs-6">
                                    <i class="bi bi-upc me-1 text-primary"></i> <?= escape($row['kode_barcode']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_produk']) ?></div>
                                <small class="text-muted"><code><?= escape($row['kode_produk']) ?></code></small>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-25 text-dark"><?= escape($row['jenis_barcode']) ?></span></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td><small><?= escape($row['catatan'] ?? '-') ?></small></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/barcode_produk/edit.php?id=' . $row['id_barcode']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Barcode">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/barcode_produk/hapus.php?id=' . $row['id_barcode']) ?>" data-nama="<?= escape($row['kode_barcode']) ?> (<?= escape($row['nama_produk']) ?>)" title="Hapus Barcode">
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
            <?= render_pagination(base_url('modules/barcode_produk/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_produk' => $id_prd]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
