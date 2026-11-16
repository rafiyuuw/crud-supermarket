<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Akun Karyawan (Daftar & Read)
 * File: modules/akun_karyawan/index.php
 */

$judul_halaman = 'Data Akun Karyawan';
$menu_aktif = 'akun_karyawan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$peran_filter = trim($_GET['peran'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM akun_karyawan a
    INNER JOIN karyawan k ON a.id_karyawan = k.id_karyawan
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (a.username LIKE :cari OR k.nama_karyawan LIKE :cari OR k.nik LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($peran_filter !== '') {
    $sqlCount .= " AND a.peran = :peran";
    $params[':peran'] = $peran_filter;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        a.*,
        k.nik,
        k.nama_karyawan,
        j.nama_jabatan,
        c.nama_cabang
    FROM akun_karyawan a
    INNER JOIN karyawan k ON a.id_karyawan = k.id_karyawan
    INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    INNER JOIN cabang c ON k.id_cabang = c.id_cabang
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (a.username LIKE :cari OR k.nama_karyawan LIKE :cari OR k.nik LIKE :cari)";
}
if ($peran_filter !== '') {
    $sqlData .= " AND a.peran = :peran";
}
$sqlData .= " ORDER BY a.id_akun ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_akun = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Akun Pengguna (User Account)</h4>
        <p class="text-muted small mb-0">Kelola akses login, otorisasi peran (role), dan status aktifasi pengguna sistem.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/akun_karyawan/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-key-fill me-1"></i> Buat Akun Pengguna
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari username atau nama karyawan..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-3">
                <select name="peran" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Peran / Role</option>
                    <option value="admin" <?= ($peran_filter === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="kasir" <?= ($peran_filter === 'kasir') ? 'selected' : '' ?>>Kasir</option>
                    <option value="gudang" <?= ($peran_filter === 'gudang') ? 'selected' : '' ?>>Gudang</option>
                    <option value="manajer" <?= ($peran_filter === 'manajer') ? 'selected' : '' ?>>Manajer</option>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-3 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> akun pengguna
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nama Pengguna (Username)</th>
                    <th>Nama Pegawai / Karyawan</th>
                    <th>Posisi & Cabang</th>
                    <th>Hak Akses / Peran</th>
                    <th>Terakhir Masuk</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_akun)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-key fs-3 d-block mb-1"></i> Data akun karyawan tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_akun as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <span class="badge bg-light text-primary border font-monospace fs-6">
                                    <i class="bi bi-person-badge me-1"></i> <?= escape($row['username']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_karyawan']) ?></div>
                                <small class="text-muted"><code><?= escape($row['nik']) ?></code></small>
                            </td>
                            <td>
                                <div><small><?= escape($row['nama_jabatan']) ?></small></div>
                                <small class="text-muted"><?= escape($row['nama_cabang']) ?></small>
                            </td>
                            <td>
                                <?php
                                $badge_role = match($row['peran']) {
                                    'admin' => 'bg-danger',
                                    'kasir' => 'bg-success',
                                    'manajer' => 'bg-primary',
                                    'gudang' => 'bg-info text-dark',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badge_role ?> text-uppercase" style="font-size: 0.72rem;"><?= escape($row['peran']) ?></span>
                            </td>
                            <td><small><?= $row['terakhir_login'] ? tanggal_indo($row['terakhir_login'], true) : '<span class="text-muted">Belum pernah</span>' ?></small></td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/akun_karyawan/edit.php?id=' . $row['id_akun']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Akun / Reset Password">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?php if ($row['id_akun'] !== $user_aktif['id_akun']): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/akun_karyawan/hapus.php?id=' . $row['id_akun']) ?>" data-nama="Akun <?= escape($row['username']) ?> (<?= escape($row['nama_karyawan']) ?>)" title="Hapus Akun">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
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
            <?= render_pagination(base_url('modules/akun_karyawan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'peran' => $peran_filter]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
