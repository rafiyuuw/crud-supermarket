<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Persediaan (Daftar & Read)
 * File: modules/persediaan/index.php
 */

$judul_halaman = 'Data Persediaan Stok';
$menu_aktif = 'persediaan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_gdg = (int)($_GET['id_gudang'] ?? 0);
$filter_stok = trim($_GET['kondisi_stok'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM persediaan ps
    INNER JOIN produk pr ON ps.id_produk = pr.id_produk
    INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari OR g.nama_gudang LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_gdg > 0) {
    $sqlCount .= " AND ps.id_gudang = :id_gdg";
    $params[':id_gdg'] = $id_gdg;
}
if ($filter_stok === 'menipis') {
    $sqlCount .= " AND ps.jumlah_stok <= ps.stok_minimum";
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        ps.*,
        pr.kode_produk,
        pr.nama_produk,
        pr.harga_beli,
        pr.harga_jual,
        s.simbol AS satuan,
        g.nama_gudang,
        lg.kode_lokasi,
        lg.nama_lokasi
    FROM persediaan ps
    INNER JOIN produk pr ON ps.id_produk = pr.id_produk
    INNER JOIN satuan_produk s ON pr.id_satuan = s.id_satuan
    INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
    LEFT JOIN lokasi_gudang lg ON ps.id_lokasi = lg.id_lokasi
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari OR g.nama_gudang LIKE :cari)";
}
if ($id_gdg > 0) {
    $sqlData .= " AND ps.id_gudang = :id_gdg";
}
if ($filter_stok === 'menipis') {
    $sqlData .= " AND ps.jumlah_stok <= ps.stok_minimum";
}
$sqlData .= " ORDER BY (ps.jumlah_stok <= ps.stok_minimum) DESC, ps.jumlah_stok ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_persediaan = $stmt->fetchAll();

$opsi_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang WHERE status = 'aktif' ORDER BY nama_gudang ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Manajemen Persediaan Stok Gudang</h4>
        <p class="text-muted small mb-0">Pantau kuantitas stok produk fisik per gudang dan per lokasi rak secara real-time.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('modules/pergerakan_stok/index.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Log Pergerakan Stok
        </a>
        <a href="<?= base_url('modules/persediaan/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Masukkan Persediaan Produk
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
                <select name="id_gudang" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Gudang</option>
                    <?php foreach ($opsi_gudang as $gdg): ?>
                        <option value="<?= $gdg['id_gudang'] ?>" <?= ($id_gdg === (int)$gdg['id_gudang']) ? 'selected' : '' ?>>
                            <?= escape($gdg['nama_gudang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2 col-md-3">
                <select name="kondisi_stok" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kondisi</option>
                    <option value="menipis" <?= ($filter_stok === 'menipis') ? 'selected' : '' ?>>Stok Menipis (&le; Minimum)</option>
                </select>
            </div>
            <div class="col-sm-2 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Produk</th>
                    <th>Fasilitas Gudang</th>
                    <th>Lokasi Rak / Bin</th>
                    <th class="text-center">Stok Fisik</th>
                    <th class="text-center">Batas Min</th>
                    <th>Kondisi Status</th>
                    <th>Update Terakhir</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_persediaan)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-stack fs-3 d-block mb-1"></i> Data persediaan stok tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_persediaan as $row): ?>
                        <?php $is_menipis = ($row['jumlah_stok'] <= $row['stok_minimum']); ?>
                        <tr class="<?= $is_menipis ? 'table-warning bg-opacity-25' : '' ?>">
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_produk']) ?></div>
                                <small class="text-muted"><code><?= escape($row['kode_produk']) ?></code></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-house-door me-1"></i><?= escape($row['nama_gudang']) ?></span></td>
                            <td>
                                <?php if ($row['kode_lokasi']): ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark border font-monospace">
                                        <?= escape($row['kode_lokasi']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small"><em>- Belum ditempatkan -</em></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $is_menipis ? 'bg-danger' : 'bg-success' ?> fs-6 px-2">
                                    <?= $row['jumlah_stok'] ?> <?= escape($row['satuan']) ?>
                                </span>
                            </td>
                            <td class="text-center text-muted small">
                                <?= $row['stok_minimum'] ?> <?= escape($row['satuan']) ?>
                            </td>
                            <td>
                                <?php if ($row['jumlah_stok'] <= 0): ?>
                                    <span class="badge bg-danger">Habis (0)</span>
                                <?php elseif ($is_menipis): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Menipis</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Aman</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= tanggal_indo($row['terakhir_diperbarui'], true) ?></small></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/persediaan/edit.php?id=' . $row['id_persediaan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Sesuaikan Stok">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/persediaan/hapus.php?id=' . $row['id_persediaan']) ?>" data-nama="<?= escape($row['nama_produk']) ?> di <?= escape($row['nama_gudang']) ?>" title="Hapus Persediaan">
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
            <?= render_pagination(base_url('modules/persediaan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_gudang' => $id_gdg, 'kondisi_stok' => $filter_stok]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
