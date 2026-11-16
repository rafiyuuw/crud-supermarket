<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Lokasi Gudang (Daftar & Read)
 * File: modules/lokasi_gudang/index.php
 */

$judul_halaman = 'Data Lokasi Gudang';
$menu_aktif = 'lokasi_gudang';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_gdg = (int)($_GET['id_gudang'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM lokasi_gudang lg
    INNER JOIN gudang g ON lg.id_gudang = g.id_gudang
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (lg.nama_lokasi LIKE :cari OR lg.kode_lokasi LIKE :cari OR g.nama_gudang LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_gdg > 0) {
    $sqlCount .= " AND lg.id_gudang = :id_gdg";
    $params[':id_gdg'] = $id_gdg;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        lg.*,
        g.kode_gudang,
        g.nama_gudang,
        c.nama_cabang,
        (SELECT COUNT(*) FROM persediaan ps WHERE ps.id_lokasi = lg.id_lokasi) AS total_produk_di_rak
    FROM lokasi_gudang lg
    INNER JOIN gudang g ON lg.id_gudang = g.id_gudang
    INNER JOIN cabang c ON g.id_cabang = c.id_cabang
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (lg.nama_lokasi LIKE :cari OR lg.kode_lokasi LIKE :cari OR g.nama_gudang LIKE :cari)";
}
if ($id_gdg > 0) {
    $sqlData .= " AND lg.id_gudang = :id_gdg";
}
$sqlData .= " ORDER BY lg.id_lokasi ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_lokasi = $stmt->fetchAll();

$opsi_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang ORDER BY nama_gudang ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Rak & Lokasi Gudang (Aisle/Bin)</h4>
        <p class="text-muted small mb-0">Kelola spesifikasi rak, lorong, chiller, dan bin penyimpanan barang di dalam gudang.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/lokasi_gudang/tambah.php' . ($id_gdg ? '?id_gudang=' . $id_gdg : '')) ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Lokasi / Rak
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari kode lokasi, rak, gudang..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-4">
                <select name="id_gudang" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Fasilitas Gudang</option>
                    <?php foreach ($opsi_gudang as $gdg): ?>
                        <option value="<?= $gdg['id_gudang'] ?>" <?= ($id_gdg === (int)$gdg['id_gudang']) ? 'selected' : '' ?>>
                            <?= escape($gdg['nama_gudang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> rak
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode Rak / Bin</th>
                    <th>Nama Lokasi</th>
                    <th>Gudang & Cabang</th>
                    <th>Keterangan</th>
                    <th class="text-center">Produk Tersimpan</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_lokasi)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-grid-3x3 fs-3 d-block mb-1"></i> Data lokasi gudang tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_lokasi as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td><span class="badge bg-light text-primary border font-monospace fs-6"><?= escape($row['kode_lokasi']) ?></span></td>
                            <td class="fw-semibold text-dark"><?= escape($row['nama_lokasi']) ?></td>
                            <td>
                                <div class="fw-semibold"><i class="bi bi-house-door me-1 text-muted"></i><?= escape($row['nama_gudang']) ?></div>
                                <small class="text-muted"><?= escape($row['nama_cabang']) ?></small>
                            </td>
                            <td><small><?= escape($row['keterangan'] ?? '-') ?></small></td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-25 text-dark"><?= $row['total_produk_di_rak'] ?> Produk</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/lokasi_gudang/edit.php?id=' . $row['id_lokasi']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Lokasi">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/lokasi_gudang/hapus.php?id=' . $row['id_lokasi']) ?>" data-nama="<?= escape($row['kode_lokasi']) ?> (<?= escape($row['nama_lokasi']) ?>)" title="Hapus Lokasi">
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
            <?= render_pagination(base_url('modules/lokasi_gudang/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_gudang' => $id_gdg]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
