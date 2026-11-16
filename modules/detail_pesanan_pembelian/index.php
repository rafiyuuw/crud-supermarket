<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Detail Pesanan Pembelian (Daftar & Read)
 * File: modules/detail_pesanan_pembelian/index.php
 */

$judul_halaman = 'Detail Pesanan Pembelian';
$menu_aktif = 'detail_pesanan_pembelian';

require_once __DIR__ . '/../../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$id_po = (int)($_GET['id_pesanan'] ?? 0);
$halaman_aktif = (int)($_GET['halaman'] ?? 1);
$limit = 10;

// Query Count
$sqlCount = "
    SELECT COUNT(*) 
    FROM detail_pesanan_pembelian dp
    INNER JOIN pesanan_pembelian po ON dp.id_pesanan = po.id_pesanan
    INNER JOIN produk pr ON dp.id_produk = pr.id_produk
    WHERE 1=1
";
$params = [];

if ($cari !== '') {
    $sqlCount .= " AND (po.nomor_pesanan LIKE :cari OR pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari)";
    $params[':cari'] = "%{$cari}%";
}
if ($id_po > 0) {
    $sqlCount .= " AND dp.id_pesanan = :id_po";
    $params[':id_po'] = $id_po;
}

$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total_data = (int)$stmt->fetchColumn();

$paginasi = hitung_pagination($total_data, $limit, $halaman_aktif);

// Ambil Data
$sqlData = "
    SELECT 
        dp.*,
        po.nomor_pesanan,
        po.status AS status_po,
        po.tanggal_pesanan,
        p.nama_pemasok,
        pr.kode_produk,
        pr.nama_produk,
        s.simbol AS satuan
    FROM detail_pesanan_pembelian dp
    INNER JOIN pesanan_pembelian po ON dp.id_pesanan = po.id_pesanan
    INNER JOIN pemasok p ON po.id_pemasok = p.id_pemasok
    INNER JOIN produk pr ON dp.id_produk = pr.id_produk
    INNER JOIN satuan_produk s ON pr.id_satuan = s.id_satuan
    WHERE 1=1
";
if ($cari !== '') {
    $sqlData .= " AND (po.nomor_pesanan LIKE :cari OR pr.nama_produk LIKE :cari OR pr.kode_produk LIKE :cari)";
}
if ($id_po > 0) {
    $sqlData .= " AND dp.id_pesanan = :id_po";
}
$sqlData .= " ORDER BY dp.id_detail_pesanan DESC LIMIT {$paginasi['limit']} OFFSET {$paginasi['offset']}";

$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$daftar_detail = $stmt->fetchAll();

// Info PO jika difilter berdasarkan PO tertentu
$info_po = null;
if ($id_po > 0) {
    $stmtPO = $pdo->prepare("
        SELECT po.*, p.nama_pemasok, g.nama_gudang 
        FROM pesanan_pembelian po 
        INNER JOIN pemasok p ON po.id_pemasok = p.id_pemasok 
        INNER JOIN gudang g ON po.id_gudang = g.id_gudang 
        WHERE po.id_pesanan = ?
    ");
    $stmtPO->execute([$id_po]);
    $info_po = $stmtPO->fetch();
}

$daftar_po_opsi = $pdo->query("SELECT id_pesanan, nomor_pesanan FROM pesanan_pembelian ORDER BY id_pesanan DESC")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Rincian Item: Detail Pesanan Pembelian</h4>
        <p class="text-muted small mb-0">Kelola daftar item kuantitas dan harga satuan barang yang dipesan ke pemasok.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($id_po > 0): ?>
            <a href="<?= base_url('modules/pesanan_pembelian/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar PO
            </a>
        <?php endif; ?>
        <a href="<?= base_url('modules/detail_pesanan_pembelian/tambah.php' . ($id_po ? '?id_pesanan=' . $id_po : '')) ?>" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Item Produk
        </a>
    </div>
</div>

<?php if ($info_po): ?>
    <div class="alert alert-info py-3 mb-4 border shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-2"></i>PO: <?= escape($info_po['nomor_pesanan']) ?> &bull; <?= escape($info_po['nama_pemasok']) ?></h6>
                <small class="text-dark">Gudang: <strong><?= escape($info_po['nama_gudang']) ?></strong> | Tgl: <?= tanggal_indo($info_po['tanggal_pesanan']) ?> | Status: <?= badge_status($info_po['status']) ?></small>
            </div>
            <div class="text-md-end">
                <span class="text-muted small d-block">Total Nominal PO:</span>
                <span class="fs-5 fw-bold text-primary"><?= rupiah($info_po['total_nominal']) ?></span>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-5 col-md-4">
                <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nomor PO atau nama produk..." value="<?= escape($cari) ?>">
            </div>
            <div class="col-sm-4 col-md-4">
                <select name="id_pesanan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Dokumen PO</option>
                    <?php foreach ($daftar_po_opsi as $po): ?>
                        <option value="<?= $po['id_pesanan'] ?>" <?= ($id_po === (int)$po['id_pesanan']) ? 'selected' : '' ?>>
                            PO: <?= escape($po['nomor_pesanan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
            </div>
            <div class="col-md-2 text-md-end text-muted small">
                Total: <strong><?= $paginasi['total_data'] ?></strong> item
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nomor PO</th>
                    <th>Produk Dipesan</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Subtotal</th>
                    <th>Catatan</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_detail)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-card-checklist fs-3 d-block mb-1"></i> Belum ada detail item pesanan pembelian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = $paginasi['dari']; foreach ($daftar_detail as $row): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td>
                                <a href="<?= base_url('modules/pesanan_pembelian/index.php?cari=' . urlencode($row['nomor_pesanan'])) ?>" class="fw-bold text-decoration-none">
                                    <code><?= escape($row['nomor_pesanan']) ?></code>
                                </a>
                                <div><small class="text-muted"><?= escape($row['nama_pemasok']) ?></small></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= escape($row['nama_produk']) ?></div>
                                <small class="text-muted"><code><?= escape($row['kode_produk']) ?></code></small>
                            </td>
                            <td class="text-center fw-bold">
                                <?= $row['jumlah'] ?> <?= escape($row['satuan']) ?>
                            </td>
                            <td class="text-end text-muted small"><?= rupiah($row['harga_satuan']) ?></td>
                            <td class="text-end fw-bold text-primary"><?= rupiah($row['subtotal']) ?></td>
                            <td><small><?= escape($row['catatan'] ?? '-') ?></small></td>
                            <td class="text-center">
                                <div class="btn-action-group">
                                    <a href="<?= base_url('modules/detail_pesanan_pembelian/edit.php?id=' . $row['id_detail_pesanan']) ?>" class="btn btn-outline-warning btn-sm py-0 px-2" title="Ubah Item">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapus" data-url="<?= base_url('modules/detail_pesanan_pembelian/hapus.php?id=' . $row['id_detail_pesanan']) ?>" data-nama="<?= escape($row['nama_produk']) ?> (PO: <?= escape($row['nomor_pesanan']) ?>)" title="Hapus Item">
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
            <?= render_pagination(base_url('modules/detail_pesanan_pembelian/index.php'), $paginasi['total_halaman'], $paginasi['halaman_aktif'], ['cari' => $cari, 'id_pesanan' => $id_po]) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
