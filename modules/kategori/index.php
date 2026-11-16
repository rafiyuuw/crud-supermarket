<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Kategori (Daftar & Read)
 * File: modules/kategori/index.php
 */

$judul_halaman = 'Data Kategori';
$menu_aktif = 'kategori';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_dept = (int)($_GET['id_departemen'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

// Query Count
$sqlCount = "
    SELECT COUNT(*) 
    FROM kategori k
    INNER JOIN departemen d ON k.id_departemen = d.id_departemen
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (k.nama_kategori LIKE :cari OR k.kode_kategori LIKE :cari OR d.nama_departemen LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_dept > 0) {
    $sqlCount .= " AND k.id_departemen = :id_dept";
    $params[':id_dept'] = $id_dept;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

// Ambil Data
$sqlData = "
    SELECT 
        k.*,
        d.nama_departemen
    FROM kategori k
    INNER JOIN departemen d ON k.id_departemen = d.id_departemen
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (k.nama_kategori LIKE :cari OR k.kode_kategori LIKE :cari OR d.nama_departemen LIKE :cari)";
}
if ($id_dept > 0) {
    $sqlData .= " AND k.id_departemen = :id_dept";
}
$sqlData .= " ORDER BY k.id_kategori ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_kategori = $stmt->fetchAll();

// Daftar Departemen untuk Filter Dropdown
$stmtDept = $pdo->query("SELECT id_departemen, nama_departemen FROM departemen ORDER BY nama_departemen ASC");
$opsi_departemen = $stmtDept->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Kategori Produk</h4>
        <p class="text-muted small mb-0">Kelompok kategori yang berada di bawah departemen.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/kategori/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-6 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama, kode kategori..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-3">
                <select name="id_departemen" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Departemen</option>
                    <?php foreach ($opsi_departemen as $d): ?>
                        <option value="<?= $d['id_departemen'] ?>" <?= ($id_dept === $d['id_departemen']) ? 'selected' : '' ?>>
                            <?= escape($d['nama_departemen']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> kategori
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Departemen</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_kategori)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i> Data kategori tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_kategori as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_kategori']) ?></code></td>
                            <td class="fw-semibold"><?= escape($row['nama_kategori']) ?></td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-diagram-3 me-1"></i><?= escape($row['nama_departemen']) ?></span></td>
                            <td><small><?= escape($row['deskripsi'] ?? '-') ?></small></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/kategori/edit.php?id=' . $row['id_kategori']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/kategori/hapus.php?id=' . $row['id_kategori']) ?>" data-nama="<?= escape($row['nama_kategori']) ?>" title="Hapus Kategori">
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
            <?= render_pagination(base_url('modules/kategori/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_departemen' => $id_dept]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
