<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Dashboard Utama Aplikasi
 * File: dashboard.php
 */

$judul_halaman = 'Dashboard Utama';
$menu_aktif = 'dashboard';

require_once __DIR__ . '/includes/header.php';

// 1. Ambil Statistik Ringkasan dari MySQL
// Total Produk
$stmt = $pdo->query("SELECT COUNT(*) FROM produk");
$total_produk = (int)$stmt->fetchColumn();

// Total Pelanggan
$stmt = $pdo->query("SELECT COUNT(*) FROM pelanggan");
$total_pelanggan = (int)$stmt->fetchColumn();

// Total Pemasok
$stmt = $pdo->query("SELECT COUNT(*) FROM pemasok");
$total_pemasok = (int)$stmt->fetchColumn();

// Total Karyawan
$stmt = $pdo->query("SELECT COUNT(*) FROM karyawan");
$total_karyawan = (int)$stmt->fetchColumn();

// Total Transaksi Penjualan
$stmt = $pdo->query("SELECT COUNT(*), COALESCE(SUM(total_akhir), 0) FROM penjualan WHERE status_pembayaran = 'lunas'");
$res_penjualan = $stmt->fetch(PDO::FETCH_NUM);
$total_transaksi = (int)$res_penjualan[0];
$total_omzet = (float)$res_penjualan[1];

// Total Stok Produk di Seluruh Gudang
$stmt = $pdo->query("SELECT COALESCE(SUM(jumlah_stok), 0) FROM persediaan");
$total_stok = (int)$stmt->fetchColumn();

// Penjualan Hari Ini
$stmt = $pdo->query("SELECT COALESCE(SUM(total_akhir), 0), COUNT(*) FROM penjualan WHERE DATE(tanggal_penjualan) = CURDATE() AND status_pembayaran = 'lunas'");
$res_hari_ini = $stmt->fetch(PDO::FETCH_NUM);
$omzet_hari_ini = (float)$res_hari_ini[0];
$transaksi_hari_ini = (int)$res_hari_ini[1];

// Penjualan Bulan Ini
$stmt = $pdo->query("SELECT COALESCE(SUM(total_akhir), 0), COUNT(*) FROM penjualan WHERE MONTH(tanggal_penjualan) = MONTH(CURDATE()) AND YEAR(tanggal_penjualan) = YEAR(CURDATE()) AND status_pembayaran = 'lunas'");
$res_bulan_ini = $stmt->fetch(PDO::FETCH_NUM);
$omzet_bulan_ini = (float)$res_bulan_ini[0];

// 2. Data Transaksi Penjualan Terbaru (Limit 5)
$stmt = $pdo->query("
    SELECT 
        p.id_penjualan,
        p.nomor_faktur,
        p.tanggal_penjualan,
        p.total_akhir,
        p.status_pembayaran,
        COALESCE(pl.nama_lengkap, 'Pelanggan Umum (Non-Member)') AS nama_pelanggan,
        k.nama_karyawan AS nama_kasir
    FROM penjualan p
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    INNER JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    ORDER BY p.tanggal_penjualan DESC
    LIMIT 5
");
$transaksi_terbaru = $stmt->fetchAll();

// 3. Produk dengan Stok Hampir Habis (Peringatan Stok Minimum)
$stmt = $pdo->query("
    SELECT 
        pr.kode_produk,
        pr.nama_produk,
        g.nama_gudang,
        ps.jumlah_stok,
        ps.stok_minimum,
        s.simbol AS satuan
    FROM persediaan ps
    INNER JOIN produk pr ON ps.id_produk = pr.id_produk
    INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
    INNER JOIN satuan_produk s ON pr.id_satuan = s.id_satuan
    WHERE ps.jumlah_stok <= ps.stok_minimum
    ORDER BY ps.jumlah_stok ASC
    LIMIT 6
");
$stok_menipis = $stmt->fetchAll();

// 4. Produk Terbaru yang Ditambahkan
$stmt = $pdo->query("
    SELECT 
        p.kode_produk,
        p.nama_produk,
        m.nama_merek,
        sk.nama_subkategori,
        p.harga_jual,
        s.simbol AS satuan
    FROM produk p
    INNER JOIN merek_produk m ON p.id_merek = m.id_merek
    INNER JOIN subkategori sk ON p.id_subkategori = sk.id_subkategori
    INNER JOIN satuan_produk s ON p.id_satuan = s.id_satuan
    ORDER BY p.id_produk DESC
    LIMIT 5
");
$produk_terbaru = $stmt->fetchAll();
?>

<!-- Header Dashboard -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Dashboard Administrasi Supermarket</h4>
        <p class="text-muted small mb-0">Selamat datang, <strong><?= escape($user_aktif['nama_karyawan']) ?></strong>! Ringkasan aktivitas dan inventaris supermarket.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('pos.php') ?>" class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-1">
            <i class="bi bi-cart-plus-fill"></i> Transaksi Kasir Baru
        </a>
        <a href="<?= base_url('modules/pesanan_pembelian/tambah.php') ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-circle"></i> Buat Pesanan (PO)
        </a>
    </div>
</div>

<!-- 6 STATISTIK KARTU UTAMA -->
<div class="row g-3 mb-4">
    <!-- Total Produk -->
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Produk</small>
                <h4 class="fw-bold mb-0 mt-1"><?= number_format($total_produk) ?></h4>
                <a href="<?= base_url('modules/produk/index.php') ?>" class="text-decoration-none small" style="font-size: 0.72rem;">Lihat Produk &rarr;</a>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
    </div>

    <!-- Total Stok -->
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Stok</small>
                <h4 class="fw-bold mb-0 mt-1 text-success"><?= number_format($total_stok) ?></h4>
                <a href="<?= base_url('modules/persediaan/index.php') ?>" class="text-decoration-none small text-success" style="font-size: 0.72rem;">Lihat Stok &rarr;</a>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-stack"></i>
            </div>
        </div>
    </div>

    <!-- Total Pelanggan -->
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Pelanggan</small>
                <h4 class="fw-bold mb-0 mt-1"><?= number_format($total_pelanggan) ?></h4>
                <a href="<?= base_url('modules/pelanggan/index.php') ?>" class="text-decoration-none small" style="font-size: 0.72rem;">Data Member &rarr;</a>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-people"></i>
            </div>
        </div>
    </div>

    <!-- Total Pemasok -->
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Pemasok</small>
                <h4 class="fw-bold mb-0 mt-1"><?= number_format($total_pemasok) ?></h4>
                <a href="<?= base_url('modules/pemasok/index.php') ?>" class="text-decoration-none small" style="font-size: 0.72rem;">Mitra Pemasok &rarr;</a>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-truck"></i>
            </div>
        </div>
    </div>

    <!-- Total Karyawan -->
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Karyawan</small>
                <h4 class="fw-bold mb-0 mt-1"><?= number_format($total_karyawan) ?></h4>
                <a href="<?= base_url('modules/karyawan/index.php') ?>" class="text-decoration-none small" style="font-size: 0.72rem;">Data Staff &rarr;</a>
            </div>
            <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-person-badge"></i>
            </div>
        </div>
    </div>

    <!-- Total Transaksi -->
    <div class="col-6 col-lg-2">
        <div class="stat-card">
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Transaksi</small>
                <h4 class="fw-bold mb-0 mt-1 text-primary"><?= number_format($total_transaksi) ?></h4>
                <a href="<?= base_url('modules/penjualan/index.php') ?>" class="text-decoration-none small" style="font-size: 0.72rem;">Riwayat &rarr;</a>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-receipt"></i>
            </div>
        </div>
    </div>
</div>

<!-- RINGKASAN OMZET PENJUALAN -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">Penjualan Hari Ini</span>
                    <h5 class="fw-bold text-primary mb-0 mt-1"><?= rupiah($omzet_hari_ini) ?></h5>
                    <small class="text-muted"><?= $transaksi_hari_ini ?> transaksi berhasil</small>
                </div>
                <i class="bi bi-calendar2-day text-primary fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">Penjualan Bulan Ini</span>
                    <h5 class="fw-bold text-success mb-0 mt-1"><?= rupiah($omzet_bulan_ini) ?></h5>
                    <small class="text-muted">Bulan <?= date('F Y') ?></small>
                </div>
                <i class="bi bi-calendar2-month text-success fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold">Total Omzet Keseluruhan</span>
                    <h5 class="fw-bold text-info mb-0 mt-1"><?= rupiah($total_omzet) ?></h5>
                    <small class="text-muted">Akumulasi <?= $total_transaksi ?> transaksi lunas</small>
                </div>
                <i class="bi bi-cash-coin text-info fs-2"></i>
            </div>
        </div>
    </div>
</div>

<!-- KONTEN UTAMA: TRANSAKSI TERBARU & PERINGATAN STOK -->
<div class="row g-4">
    <!-- Transaksi Penjualan Terbaru -->
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-receipt me-2 text-primary"></i>Transaksi Penjualan Terbaru</span>
                <a href="<?= base_url('modules/penjualan/index.php') ?>" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">Semua Penjualan</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No. Faktur</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th class="text-end">Total Akhir</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksi_terbaru)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada transaksi penjualan tercatat.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksi_terbaru as $trx): ?>
                                <tr>
                                    <td class="fw-semibold text-primary">
                                        <a href="<?= base_url('modules/penjualan/edit.php?id=' . $trx['id_penjualan']) ?>" class="text-decoration-none">
                                            <?= escape($trx['nomor_faktur']) ?>
                                        </a>
                                    </td>
                                    <td><small><?= tanggal_indo($trx['tanggal_penjualan'], true) ?></small></td>
                                    <td><?= escape($trx['nama_pelanggan']) ?></td>
                                    <td><small class="text-muted"><?= escape($trx['nama_kasir']) ?></small></td>
                                    <td class="text-end fw-semibold"><?= rupiah($trx['total_akhir']) ?></td>
                                    <td><?= badge_status($trx['status_pembayaran']) ?></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('cetak_faktur.php?id=' . $trx['id_penjualan']) ?>" target="_blank" class="btn btn-light btn-sm border py-0 px-2" title="Cetak Nota">
                                            <i class="bi bi-printer text-secondary"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Peringatan Stok Menipis -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-warning bg-opacity-10 border-warning border-opacity-25 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Peringatan Stok Menipis</span>
                <span class="badge bg-warning text-dark"><?= count($stok_menipis) ?> Item</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stok_menipis)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                        Semua stok persediaan dalam kondisi aman (di atas stok minimum).
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stok_menipis as $stk): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold small"><?= escape($stk['nama_produk']) ?></div>
                                    <small class="text-muted d-block">Gudang: <?= escape($stk['nama_gudang']) ?></small>
                                    <small class="text-muted">Min: <?= $stk['stok_minimum'] ?> <?= escape($stk['satuan']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger fs-6"><?= $stk['jumlah_stok'] ?> <?= escape($stk['satuan']) ?></span>
                                    <a href="<?= base_url('modules/pesanan_pembelian/tambah.php') ?>" class="d-block small text-primary text-decoration-none mt-1">
                                        Restok &rarr;
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-top text-center py-2">
                <a href="<?= base_url('modules/persediaan/index.php') ?>" class="text-decoration-none small text-muted">Kelola Seluruh Persediaan &rarr;</a>
            </div>
        </div>
    </div>
</div>

<!-- BARIS TAMBAHAN: PRODUK TERBARU -->
<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-box-seam me-2 text-primary"></i>Katalog Produk Baru</span>
                <a href="<?= base_url('modules/produk/index.php') ?>" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">Semua Produk</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Kode Produk</th>
                            <th>Nama Produk</th>
                            <th>Subkategori</th>
                            <th>Merek</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produk_terbaru as $prd): ?>
                            <tr>
                                <td class="fw-semibold"><code><?= escape($prd['kode_produk']) ?></code></td>
                                <td class="fw-semibold"><?= escape($prd['nama_produk']) ?></td>
                                <td><?= escape($prd['nama_subkategori']) ?></td>
                                <td><?= escape($prd['nama_merek']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= escape($prd['satuan']) ?></span></td>
                                <td class="text-end fw-semibold text-success"><?= rupiah($prd['harga_jual']) ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('modules/produk/index.php?cari=' . urlencode($prd['kode_produk'])) ?>" class="btn btn-light btn-sm border py-0 px-2">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
