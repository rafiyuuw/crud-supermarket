<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Alamat Pelanggan (Daftar & Read)
 * File: modules/alamat_pelanggan/index.php
 */

$judul_halaman = 'Data Alamat Pelanggan';
$menu_aktif = 'alamat_pelanggan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_plg = (int)($_GET['id_pelanggan'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM alamat_pelanggan ap
    INNER JOIN pelanggan p ON ap.id_pelanggan = p.id_pelanggan
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (p.nama_lengkap LIKE :cari OR p.kode_pelanggan LIKE :cari OR ap.alamat_lengkap LIKE :cari OR ap.kota LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_plg > 0) {
    $sqlCount .= " AND ap.id_pelanggan = :id_plg";
    $params[':id_plg'] = $id_plg;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        ap.*,
        p.kode_pelanggan,
        p.nama_lengkap,
        p.nomor_telepon
    FROM alamat_pelanggan ap
    INNER JOIN pelanggan p ON ap.id_pelanggan = p.id_pelanggan
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (p.nama_lengkap LIKE :cari OR p.kode_pelanggan LIKE :cari OR ap.alamat_lengkap LIKE :cari OR ap.kota LIKE :cari)";
}
if ($id_plg > 0) {
    $sqlData .= " AND ap.id_pelanggan = :id_plg";
}
$sqlData .= " ORDER BY ap.id_alamat DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_alamat = $stmt->fetchAll();

$opsi_pelanggan = $pdo->query("SELECT id_pelanggan, kode_pelanggan, nama_lengkap FROM pelanggan ORDER BY nama_lengkap ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Alamat Pelanggan</h4>
        <p class="text-muted small mb-0">Kelola alamat domisili dan tujuan pengiriman pelanggan (1 pelanggan bisa memiliki banyak alamat).</p>
    </div>
    <div>
        <a href="<?= base_url('modules/alamat_pelanggan/tambah.php' . ($id_plg ? '?id_pelanggan=' . $id_plg : '')) ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-geo-alt-fill me-1"></i> Tambah Alamat Baru
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama member, jalan, kota..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-4">
                <select name="id_pelanggan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Pelanggan</option>
                    <?php foreach ($opsi_pelanggan as $plg): ?>
                        <option value="<?= $plg['id_pelanggan'] ?>" <?= ($id_plg === (int)$plg['id_pelanggan']) ? 'selected' : '' ?>>
                            [<?= escape($plg['kode_pelanggan']) ?>] <?= escape($plg['nama_lengkap']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> alamat
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Pelanggan</th>
                    <th>Label Alamat</th>
                    <th>Alamat Lengkap</th>
                    <th>Kota & Provinsi</th>
                    <th>Kode Pos</th>
                    <th>Alamat Utama?</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_alamat)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-geo-alt fs-3 d-block mb-1"></i> Data alamat pelanggan tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_alamat as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_lengkap']) ?></div>
                                <small class="text-muted"><code><?= escape($row['kode_pelanggan']) ?></code> &bull; <?= escape($row['nomor_telepon']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-tag me-1"></i><?= escape($row['label_alamat']) ?></span></td>
                            <td><small><?= escape($row['alamat_lengkap']) ?></small></td>
                            <td><small><?= escape($row['kota']) ?>, <?= escape($row['provinsi']) ?></small></td>
                            <td><code><?= escape($row['kode_pos']) ?></code></td>
                            <td><?= badge_status($row['status_utama']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/alamat_pelanggan/edit.php?id=' . $row['id_alamat']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Alamat">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/alamat_pelanggan/hapus.php?id=' . $row['id_alamat']) ?>" data-nama="<?= escape($row['label_alamat']) ?> (<?= escape($row['nama_lengkap']) ?>)" title="Hapus Alamat">
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
            <?= render_pagination(base_url('modules/alamat_pelanggan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_pelanggan' => $id_plg]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
