<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Jabatan (Daftar & Read)
 * File: modules/jabatan/index.php
 */

$judul_halaman = 'Data Jabatan Karyawan';
$menu_aktif = 'jabatan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "SELECT COUNT(*) FROM jabatan WHERE 1=1";
$params = [];
if ($cari !== '') {
    $sqlCount .= " AND (nama_jabatan LIKE :cari OR kode_jabatan LIKE :cari OR deskripsi LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        j.*,
        (SELECT COUNT(*) FROM karyawan k WHERE k.id_jabatan = j.id_jabatan) AS total_karyawan
    FROM jabatan j
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (j.nama_jabatan LIKE :cari OR j.kode_jabatan LIKE :cari OR j.deskripsi LIKE :cari)";
}
$sqlData .= " ORDER BY j.id_jabatan ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_jabatan = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Jabatan Karyawan</h4>
        <p class="text-muted small mb-0">Kelola struktur posisi jabatan kerja dan standar gaji pokok supermarket.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/jabatan/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Jabatan Baru
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-6 col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama atau kode jabatan..." value="<?= escape($cari) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="<?= base_url('modules/jabatan/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6 col-md-8 text-sm-end text-muted small">
                Menampilkan <strong><?= $paginasi['dari'] ?>-<?= $paginasi['sampai'] ?></strong> dari <strong><?= $paginasi['total_data'] ?></strong> posisi jabatan
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Posisi Jabatan</th>
                    <th class="text-end">Gaji Pokok</th>
                    <th>Deskripsi Pekerjaan</th>
                    <th class="text-center">Karyawan</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_jabatan)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-briefcase fs-3 d-block mb-1"></i> Data jabatan tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_jabatan as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_jabatan']) ?></code></td>
                            <td class="fw-semibold text-dark"><?= escape($row['nama_jabatan']) ?></td>
                            <td class="text-end fw-bold text-success"><?= rupiah($row['gaji_pokok']) ?></td>
                            <td><small><?= escape($row['deskripsi'] ?? '-') ?></small></td>
                            <td class="text-center">
                                <a href="<?= base_url('modules/karyawan/index.php?id_jabatan=' . $row['id_jabatan']) ?>" class="badge bg-light text-primary border text-decoration-none">
                                    <i class="bi bi-people me-1"></i><?= $row['total_karyawan'] ?> Orang
                                </a>
                            </td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/jabatan/edit.php?id=' . $row['id_jabatan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Jabatan">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/jabatan/hapus.php?id=' . $row['id_jabatan']) ?>" data-nama="<?= escape($row['nama_jabatan']) ?>" title="Hapus Jabatan">
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
            <?= render_pagination(base_url('modules/jabatan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
