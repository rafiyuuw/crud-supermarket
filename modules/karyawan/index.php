<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Karyawan (Daftar & Read)
 * File: modules/karyawan/index.php
 */

$judul_halaman = 'Data Karyawan';
$menu_aktif = 'karyawan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_jab = (int)($_GET['id_jabatan'] ?? 0);
$id_cbg = (int)($_GET['id_cabang'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM karyawan k
    INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    INNER JOIN cabang c ON k.id_cabang = c.id_cabang
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (k.nama_karyawan LIKE :cari OR k.nik LIKE :cari OR k.nomor_telepon LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_jab > 0) {
    $sqlCount .= " AND k.id_jabatan = :id_jab";
    $params[':id_jab'] = $id_jab;
}
if ($id_cbg > 0) {
    $sqlCount .= " AND k.id_cabang = :id_cbg";
    $params[':id_cbg'] = $id_cbg;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        k.*,
        j.nama_jabatan,
        c.nama_cabang,
        (SELECT COUNT(*) FROM akun_karyawan ak WHERE ak.id_karyawan = k.id_karyawan) AS punya_akun,
        (SELECT ak.username FROM akun_karyawan ak WHERE ak.id_karyawan = k.id_karyawan LIMIT 1) AS username_akun
    FROM karyawan k
    INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan
    INNER JOIN cabang c ON k.id_cabang = c.id_cabang
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (k.nama_karyawan LIKE :cari OR k.nik LIKE :cari OR k.nomor_telepon LIKE :cari)";
}
if ($id_jab > 0) {
    $sqlData .= " AND k.id_jabatan = :id_jab";
}
if ($id_cbg > 0) {
    $sqlData .= " AND k.id_cabang = :id_cbg";
}
$sqlData .= " ORDER BY k.id_karyawan ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_karyawan = $stmt->fetchAll();

$opsi_jabatan = $pdo->query("SELECT id_jabatan, nama_jabatan FROM jabatan ORDER BY nama_jabatan ASC")->fetchAll();
$opsi_cabang = $pdo->query("SELECT id_cabang, nama_cabang FROM cabang ORDER BY nama_cabang ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Data Karyawan</h4>
        <p class="text-muted small mb-0">Kelola informasi staff pegawai supermarket, penempatan cabang, dan jabatan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('modules/akun_karyawan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-key me-1"></i> Kelola Akun Login
        </a>
        <a href="<?= base_url('modules/karyawan/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Karyawan Baru
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-4 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari NIK, nama, atau no telp..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-3 col-md-3">
                <select name="id_jabatan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Jabatan</option>
                    <?php foreach ($opsi_jabatan as $j): ?>
                        <option value="<?= $j['id_jabatan'] ?>" <?= ($id_jab === (int)$j['id_jabatan']) ? 'selected' : '' ?>>
                            <?= escape($j['nama_jabatan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-3">
                <select name="id_cabang" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($opsi_cabang as $c): ?>
                        <option value="<?= $c['id_cabang'] ?>" <?= ($id_cbg === (int)$c['id_cabang']) ? 'selected' : '' ?>>
                            <?= escape($c['nama_cabang']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>NIK & Nama</th>
                    <th>Jabatan & Cabang</th>
                    <th>Gender & Kontak</th>
                    <th>Tgl Masuk</th>
                    <th>Akun Sistem</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_karyawan)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-3 d-block mb-1"></i> Data karyawan tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_karyawan as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_karyawan']) ?></div>
                                <small class="text-muted"><code><?= escape($row['nik']) ?></code></small>
                            </td>
                            <td>
                                <div><span class="badge bg-light text-primary border"><i class="bi bi-briefcase me-1"></i><?= escape($row['nama_jabatan']) ?></span></div>
                                <small class="text-muted"><i class="bi bi-building me-1"></i><?= escape($row['nama_cabang']) ?></small>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-secondary bg-opacity-25 text-dark me-1"><?= ($row['jenis_kelamin'] === 'L') ? 'Laki-laki' : 'Perempuan' ?></span>
                                    <small><?= escape($row['nomor_telepon']) ?></small>
                                </div>
                                <small class="text-muted"><?= escape($row['email'] ?? '-') ?></small>
                            </td>
                            <td><small><?= tanggal_indo($row['tanggal_masuk']) ?></small></td>
                            <td>
                                <?php if ($row['punya_akun'] > 0): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        <i class="bi bi-check-circle me-1"></i><?= escape($row['username_akun']) ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?= base_url('modules/akun_karyawan/tambah.php?id_karyawan=' . $row['id_karyawan']) ?>" class="badge bg-light text-warning border text-decoration-none">
                                        <i class="bi bi-plus-circle me-1"></i>Buat Akun
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/karyawan/edit.php?id=' . $row['id_karyawan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/karyawan/hapus.php?id=' . $row['id_karyawan']) ?>" data-nama="<?= escape($row['nama_karyawan']) ?> (<?= escape($row['nik']) ?>)" title="Hapus Karyawan">
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
            <?= render_pagination(base_url('modules/karyawan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_jabatan' => $id_jab, 'id_cabang' => $id_cbg]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
