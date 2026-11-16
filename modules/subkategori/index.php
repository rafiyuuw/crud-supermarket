<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Subkategori (Daftar & Read)
 * File: modules/subkategori/index.php
 */

$judul_halaman = 'Data Subkategori';
$menu_aktif = 'subkategori';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_kat = (int)($_GET['id_kategori'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM subkategori sk
    INNER JOIN kategori k ON sk.id_kategori = k.id_kategori
    INNER JOIN departemen d ON k.id_departemen = d.id_departemen
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (sk.nama_subkategori LIKE :cari OR sk.kode_subkategori LIKE :cari OR k.nama_kategori LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_kat > 0) {
    $sqlCount .= " AND sk.id_kategori = :id_kat";
    $params[':id_kat'] = $id_kat;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        sk.*,
        k.nama_kategori,
        d.nama_departemen
    FROM subkategori sk
    INNER JOIN kategori k ON sk.id_kategori = k.id_kategori
    INNER JOIN departemen d ON k.id_departemen = d.id_departemen
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (sk.nama_subkategori LIKE :cari OR sk.kode_subkategori LIKE :cari OR k.nama_kategori LIKE :cari)";
}
if ($id_kat > 0) {
    $sqlData .= " AND sk.id_kategori = :id_kat";
}
$sqlData .= " ORDER BY sk.id_subkategori ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_subkategori = $stmt->fetchAll();

$stmtKat = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
$opsi_kategori = $stmtKat->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Subkategori Produk</h4>
        <p class="text-muted small mb-0">Rincian subkategori yang langsung menaungi produk-produk supermarket.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/subkategori/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Subkategori
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-6 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama atau kode subkategori..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-3">
                <select name="id_kategori" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($opsi_kategori as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>" <?= ($id_kat === $k['id_kategori']) ? 'selected' : '' ?>>
                            <?= escape($k['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> subkategori
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Subkategori</th>
                    <th>Kategori Induk</th>
                    <th>Departemen</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_subkategori)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i> Data subkategori tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_subkategori as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_subkategori']) ?></code></td>
                            <td class="fw-semibold"><?= escape($row['nama_subkategori']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= escape($row['nama_kategori']) ?></span></td>
                            <td><small class="text-muted"><?= escape($row['nama_departemen']) ?></small></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/subkategori/edit.php?id=' . $row['id_subkategori']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/subkategori/hapus.php?id=' . $row['id_subkategori']) ?>" data-nama="<?= escape($row['nama_subkategori']) ?>" title="Hapus Subkategori">
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
            <?= render_pagination(base_url('modules/subkategori/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_kategori' => $id_kat]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
