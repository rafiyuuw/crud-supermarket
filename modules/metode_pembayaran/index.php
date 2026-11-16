<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Metode Pembayaran (Daftar & Read)
 * File: modules/metode_pembayaran/index.php
 */

$judul_halaman = 'Data Metode Pembayaran';
$menu_aktif = 'metode_pembayaran';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$kategori_filter = trim($_GET['kategori'] ?? '');
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

$sqlCount = "SELECT COUNT(*) FROM metode_pembayaran WHERE 1=1";
$params = [];
if ($cari !== '') {
    $sqlCount .= " AND (nama_metode LIKE :cari OR kode_metode LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($kategori_filter !== '') {
    $sqlCount .= " AND kategori = :kategori";
    $params[':kategori'] = $kategori_filter;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

$sqlData = "
    SELECT 
        m.*,
        (SELECT COUNT(*) FROM pembayaran_penjualan pp WHERE pp.id_metode = m.id_metode) AS total_transaksi_pakai
    FROM metode_pembayaran m
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (m.nama_metode LIKE :cari OR m.kode_metode LIKE :cari)";
}
if ($kategori_filter !== '') {
    $sqlData .= " AND m.kategori = :kategori";
}
$sqlData .= " ORDER BY m.id_metode ASC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_metode = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengelolaan Metode Pembayaran</h4>
        <p class="text-muted small mb-0">Kelola opsi pembayaran kasir (Tunai, Debit, Kartu Kredit, QRIS, Transfer VA).</p>
    </div>
    <div>
        <a href="<?= base_url('modules/metode_pembayaran/tambah.php') ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Metode Pembayaran
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama atau kode metode..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-4">
                <select name="kategori" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <option value="tunai" <?= ($kategori_filter === 'tunai') ? 'selected' : '' ?>>Uang Tunai (Cash)</option>
                    <option value="kartu" <?= ($kategori_filter === 'kartu') ? 'selected' : '' ?>>Kartu Debit / Kredit</option>
                    <option value="dompet_digital" <?= ($kategori_filter === 'dompet_digital') ? 'selected' : '' ?>>Dompet Digital (QRIS / E-Wallet)</option>
                    <option value="transfer" <?= ($kategori_filter === 'transfer') ? 'selected' : '' ?>>Transfer Virtual Account</option>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> metode
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Kode</th>
                    <th>Nama Metode Pembayaran</th>
                    <th>Kategori Alat Bayar</th>
                    <th class="text-center">Penggunaan</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_metode)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-credit-card fs-3 d-block mb-1"></i> Data metode pembayaran tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_metode as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="fw-semibold"><code><?= escape($row['kode_metode']) ?></code></td>
                            <td class="fw-semibold text-dark">
                                <i class="bi bi-wallet2 me-1 text-primary"></i> <?= escape($row['nama_metode']) ?>
                            </td>
                            <td>
                                <?php
                                $badge_kat = match($row['kategori']) {
                                    'tunai' => 'bg-success',
                                    'kartu' => 'bg-primary',
                                    'dompet_digital' => 'bg-info text-dark',
                                    'transfer' => 'bg-warning text-dark',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badge_kat ?> text-capitalize"><?= str_replace('_', ' ', $row['kategori']) ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('modules/pembayaran_penjualan/index.php?id_metode=' . $row['id_metode']) ?>" class="badge bg-light text-primary border text-decoration-none">
                                    <?= $row['total_transaksi_pakai'] ?> Transaksi
                                </a>
                            </td>
                            <td><?= badge_status($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/metode_pembayaran/edit.php?id=' . $row['id_metode']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/metode_pembayaran/hapus.php?id=' . $row['id_metode']) ?>" data-nama="<?= escape($row['nama_metode']) ?>" title="Hapus Metode">
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
            <?= render_pagination(base_url('modules/metode_pembayaran/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'kategori' => $kategori_filter]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
