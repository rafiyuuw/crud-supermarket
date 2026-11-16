<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pelanggan (Daftar & Read)
 * File: modules/pelanggan/index.php
 */

$judul_halaman = 'Data Pelanggan';
$menu_aktif = 'pelanggan';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "SELECT COUNT(*) FROM pelanggan WHERE 1=1";
$params = [];
if ($cari !== '') {
    $sqlCount .= " AND (nama_lengkap LIKE :cari OR kode_pelanggan LIKE :cari OR nomor_telepon LIKE :cari OR email LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM alamat_pelanggan ap WHERE ap.id_pelanggan = p.id_pelanggan) AS total_alamat,
        (SELECT COUNT(*) FROM penjualan pj WHERE pj.id_pelanggan = p.id_pelanggan) AS total_transaksi,
        (SELECT COALESCE(SUM(pj.total_akhir), 0) FROM penjualan pj WHERE pj.id_pelanggan = p.id_pelanggan AND pj.status_pembayaran = 'lunas') AS total_belanja
    FROM pelanggan p
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (p.nama_lengkap LIKE :cari OR p.kode_pelanggan LIKE :cari OR p.nomor_telepon LIKE :cari OR p.email LIKE :cari)";
}
$sqlData .= " ORDER BY p.id_pelanggan ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_pelanggan = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Data Pelanggan (Member)</h4>
        <p class="text-muted small mb-0">Kelola informasi member loyalitas, poin belanja, dan riwayat alamat pengiriman.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('modules/alamat_pelanggan/index.php') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-geo-alt me-1"></i> Seluruh Alamat Pelanggan
        </a>
        <a href="<?= base_url('modules/pelanggan/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Registrasi Pelanggan Baru
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-6 col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama, kode member, no telp..." value="<?= escape($cari) ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if ($cari !== ''): ?>
                        <a href="<?= base_url('modules/pelanggan/index.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6 col-md-8 text-sm-end text-muted small">
                Menampilkan <strong><?= $paginasi['dari'] ?>-<?= $paginasi['sampai'] ?></strong> dari <strong><?= $paginasi['total_data'] ?></strong> member
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode Member</th>
                    <th>Nama Lengkap</th>
                    <th>Kontak (Telepon & Email)</th>
                    <th class="text-center">Poin Loyalitas</th>
                    <th>Alamat & Belanja</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_pelanggan)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-3 d-block mb-1"></i> Data pelanggan tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_pelanggan as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td><span class="badge bg-light text-primary border font-monospace"><?= escape($row['kode_pelanggan']) ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_lengkap']) ?></div>
                                <small class="text-muted">Daftar: <?= tanggal_indo($row['tanggal_daftar']) ?></small>
                            </td>
                            <td>
                                <div><small><i class="bi bi-telephone me-1 text-muted"></i><?= escape($row['nomor_telepon']) ?></small></div>
                                <div><small><i class="bi bi-envelope me-1 text-muted"></i><?= escape($row['email'] ?? '-') ?></small></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark fs-6 px-2 py-1">
                                    <i class="bi bi-star-fill text-warning me-1"></i><?= number_format($row['poin_loyalitas']) ?> Poin
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1 mb-1">
                                    <a href="<?= base_url('modules/alamat_pelanggan/index.php?id_pelanggan=' . $row['id_pelanggan']) ?>" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-geo-alt me-1"></i><?= $row['total_alamat'] ?> Alamat
                                    </a>
                                </div>
                                <small class="text-muted d-block"><?= $row['total_transaksi'] ?>x transaksi (<?= rupiah($row['total_belanja']) ?>)</small>
                            </td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/alamat_pelanggan/tambah.php?id_pelanggan=' . $row['id_pelanggan']) ?>" class="btn btn-outline-info btn-sm py-0 px-2" title="Tambah Alamat Baru">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </a>
                                    <a href="<?= base_url('modules/pelanggan/edit.php?id=' . $row['id_pelanggan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/pelanggan/hapus.php?id=' . $row['id_pelanggan']) ?>" data-nama="<?= escape($row['nama_lengkap']) ?> (<?= escape($row['kode_pelanggan']) ?>)" title="Hapus Pelanggan">
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
            <?= render_pagination(base_url('modules/pelanggan/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
