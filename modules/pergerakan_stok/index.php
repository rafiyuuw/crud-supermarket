<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pergerakan Stok (Daftar & Read)
 * File: modules/pergerakan_stok/index.php
 */

$judul_halaman = 'Data Pergerakan Stok';
$menu_aktif = 'pergerakan_stok';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$jenis_filter = trim($_GET['jenis'] ?? '');
$id_gdg = (int)($_GET['id_gudang'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "
    SELECT COUNT(*) 
    FROM pergerakan_stok ps
    INNER JOIN produk pr ON ps.id_produk = pr.id_produk
    INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari OR ps.referensi LIKE :cari OR ps.keterangan LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($jenis_filter !== '') {
    $sqlCount .= " AND ps.jenis_pergerakan = :jenis";
    $params[':jenis'] = $jenis_filter;
}
if ($id_gdg > 0) {
    $sqlCount .= " AND ps.id_gudang = :id_gdg";
    $params[':id_gdg'] = $id_gdg;
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
        s.simbol AS satuan,
        g.nama_gudang,
        k.nama_karyawan
    FROM pergerakan_stok ps
    INNER JOIN produk pr ON ps.id_produk = pr.id_produk
    INNER JOIN satuan_produk s ON pr.id_satuan = s.id_satuan
    INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
    INNER JOIN karyawan k ON ps.id_karyawan = k.id_karyawan
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari OR ps.referensi LIKE :cari OR ps.keterangan LIKE :cari)";
}
if ($jenis_filter !== '') {
    $sqlData .= " AND ps.jenis_pergerakan = :jenis";
}
if ($id_gdg > 0) {
    $sqlData .= " AND ps.id_gudang = :id_gdg";
}
$sqlData .= " ORDER BY ps.id_pergerakan DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_pergerakan = $stmt->fetchAll();

$opsi_gudang = $pdo->query("SELECT id_gudang, nama_gudang FROM gudang ORDER BY nama_gudang ASC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Riwayat & Mutasi Pergerakan Stok</h4>
        <p class="text-muted small mb-0">Buku besar log pergerakan keluar, masuk, retur, dan penyesuaian stok produk.</p>
    </div>
    <div>
        <a href="<?= base_url('modules/pergerakan_stok/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Catat Mutasi Manual
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-4 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari produk, nomor ref, ket..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-3 col-md-3">
                <select name="jenis" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Jenis Mutasi</option>
                    <option value="masuk" <?= ($jenis_filter === 'masuk') ? 'selected' : '' ?>>Barang Masuk (In)</option>
                    <option value="keluar" <?= ($jenis_filter === 'keluar') ? 'selected' : '' ?>>Barang Keluar (Out)</option>
                    <option value="penyesuaian" <?= ($jenis_filter === 'penyesuaian') ? 'selected' : '' ?>>Penyesuaian (Opname)</option>
                    <option value="retur" <?= ($jenis_filter === 'retur') ? 'selected' : '' ?>>Retur Barang</option>
                </select>
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
                    <th>Waktu / Tanggal</th>
                    <th>Produk</th>
                    <th>Gudang</th>
                    <th class="text-center">Jenis</th>
                    <th class="text-center">Qty Mutasi</th>
                    <th class="text-center">Sebelum &rsaquo; Sesudah</th>
                    <th>Referensi & Keterangan</th>
                    <th>Petugas</th>
                    <th class="text-center" style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_pergerakan)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-arrow-left-right fs-3 d-block mb-1"></i> Belum ada riwayat pergerakan stok.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_pergerakan as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td><small><?= tanggal_indo($row['tanggal'], true) ?></small></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_produk']) ?></div>
                                <small class="text-muted"><code><?= escape($row['kode_produk']) ?></code></small>
                            </td>
                            <td><small class="badge bg-light text-dark border"><?= escape($row['nama_gudang']) ?></small></td>
                            <td class="text-center"><?= badge_status($row['jenis_pergerakan']) ?></td>
                            <td class="text-center fw-bold">
                                <?php if ($row['jenis_pergerakan'] === 'keluar'): ?>
                                    <span class="text-danger">-<?= $row['jumlah'] ?> <?= escape($row['satuan']) ?></span>
                                <?php elseif ($row['jenis_pergerakan'] === 'masuk'): ?>
                                    <span class="text-success">+<?= $row['jumlah'] ?> <?= escape($row['satuan']) ?></span>
                                <?php else: ?>
                                    <span class="text-primary"><?= $row['jumlah'] ?> <?= escape($row['satuan']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center font-monospace small">
                                <?= $row['stok_sebelum'] ?> &rarr; <strong class="text-dark"><?= $row['stok_sesudah'] ?></strong>
                            </td>
                            <td>
                                <?php if ($row['referensi']): ?>
                                    <div><code><?= escape($row['referensi']) ?></code></div>
                                <?php endif; ?>
                                <small class="text-muted"><?= escape($row['keterangan'] ?? '-') ?></small>
                            </td>
                            <td><small><i class="bi bi-person me-1 text-muted"></i><?= escape($row['nama_karyawan']) ?></small></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/pergerakan_stok/edit.php?id=' . $row['id_pergerakan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Catatan">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/pergerakan_stok/hapus.php?id=' . $row['id_pergerakan']) ?>" data-nama="Mutasi <?= escape($row['nama_produk']) ?> (<?= escape($row['referensi'] ?? '-') ?>)" title="Hapus Riwayat">
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
            <?= render_pagination(base_url('modules/pergerakan_stok/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'jenis' => $jenis_filter, 'id_gudang' => $id_gdg]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
