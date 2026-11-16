<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Gudang (Daftar & Read)
 * File: modules/gudang/index.php
 */

$judul_halaman = 'Data Gudang';
$menu_aktif = 'gudang';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_cbg = (int)($_GET['id_cabang'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM gudang g
    INNER JOIN cabang c ON g.id_cabang = c.id_cabang
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (g.nama_gudang LIKE :cari OR g.kode_gudang LIKE :cari OR g.alamat LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_cbg > 0) {
    $sqlCount .= " AND g.id_cabang = :id_cbg";
    $params[':id_cbg'] = $id_cbg;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        g.*,
        c.nama_cabang,
        (SELECT COUNT(*) FROM lokasi_gudang lg WHERE lg.id_gudang = g.id_gudang) AS total_lokasi,
        (SELECT COALESCE(SUM(ps.jumlah_stok), 0) FROM persediaan ps WHERE ps.id_gudang = g.id_gudang) AS total_stok_tersimpan
    FROM gudang g
    INNER JOIN cabang c ON g.id_cabang = c.id_cabang
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (g.nama_gudang LIKE :cari OR g.kode_gudang LIKE :cari OR g.alamat LIKE :cari)";
}
if ($id_cbg > 0) {
    $sqlData .= " AND g.id_cabang = :id_cbg";
}
$sqlData .= " ORDER BY g.id_gudang ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_gudang = $stmt->fetchAll();

$opsi_cabang = $pdo->query("SELECT id_cabang, nama_cabang FROM cabang ORDER BY nama_cabang ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Data Gudang (Warehouse)</h4>
        <p class="text-muted small mb-0">Kelola fasilitas pergudangan, kapasitas penyimpanan, dan keterikatan cabang.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('modules/lokasi_gudang/index.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-grid-3x3 me-1"></i> Data Rak / Lokasi
        </a>
        <a href="<?= base_url('modules/gudang/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Gudang Baru
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama, kode, alamat gudang..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-3">
                <select name="id_cabang" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($opsi_cabang as $c): ?>
                        <option value="<?= $c['id_cabang'] ?>" <?= ($id_cbg === (int)$c['id_cabang']) ? 'selected' : '' ?>>
                            <?= escape($c['nama_cabang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> gudang
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Gudang</th>
                    <th>Cabang Terhubung</th>
                    <th>Alamat Fasilitas</th>
                    <th class="text-center">Kapasitas (m&sup3;)</th>
                    <th class="text-center">Stok Fisik</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_gudang)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-house-door fs-3 d-block mb-1"></i> Data gudang tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_gudang as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_gudang']) ?></code></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_gudang']) ?></div>
                                <a href="<?= base_url('modules/lokasi_gudang/index.php?id_gudang=' . $row['id_gudang']) ?>" class="badge bg-light text-primary border text-decoration-none">
                                    <i class="bi bi-grid-3x3 me-1"></i><?= $row['total_lokasi'] ?> Rak/Bin
                                </a>
                            </td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-building me-1"></i><?= escape($row['nama_cabang']) ?></span></td>
                            <td><small><?= escape($row['alamat']) ?></small></td>
                            <td class="text-center fw-semibold"><?= number_format($row['kapasitas_m3']) ?> m&sup3;</td>
                            <td class="text-center">
                                <a href="<?= base_url('modules/persediaan/index.php?id_gudang=' . $row['id_gudang']) ?>" class="badge bg-success text-decoration-none">
                                    <?= number_format($row['total_stok_tersimpan']) ?> Unit
                                </a>
                            </td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/gudang/edit.php?id=' . $row['id_gudang']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Gudang">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/gudang/hapus.php?id=' . $row['id_gudang']) ?>" data-nama="<?= escape($row['nama_gudang']) ?>" title="Hapus Gudang">
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
            <?= render_pagination(base_url('modules/gudang/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_cabang' => $id_cbg]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
