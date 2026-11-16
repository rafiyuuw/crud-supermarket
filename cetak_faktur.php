<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Cetak Nota Penjualan / Struk Transaksi
 * File: cetak_faktur.php
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

cek_login();

$id = (int)($_GET['id'] ?? 0);

// Ambil data penjualan
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        c.nama_cabang, c.alamat AS alamat_cabang, c.telepon AS telp_cabang,
        k.nama_karyawan AS nama_kasir,
        COALESCE(pl.nama_lengkap, 'Pelanggan Umum') AS nama_pelanggan,
        COALESCE(pl.kode_pelanggan, '-') AS kode_pelanggan
    FROM penjualan p
    INNER JOIN cabang c ON p.id_cabang = c.id_cabang
    INNER JOIN karyawan k ON p.id_karyawan = k.id_karyawan
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    WHERE p.id_penjualan = ?
");
$stmt->execute([$id]);
$penjualan = $stmt->fetch();

if (!$penjualan) {
    die("Transaksi penjualan tidak ditemukan.");
}

// Ambil detail item produk
$stmt = $pdo->prepare("
    SELECT 
        dp.*,
        pr.kode_produk, pr.nama_produk,
        s.simbol AS satuan
    FROM detail_penjualan dp
    INNER JOIN produk pr ON dp.id_produk = pr.id_produk
    INNER JOIN satuan_produk s ON pr.id_satuan = s.id_satuan
    WHERE dp.id_penjualan = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Ambil data pembayaran
$stmt = $pdo->prepare("
    SELECT pp.*, m.nama_metode 
    FROM pembayaran_penjualan pp
    INNER JOIN metode_pembayaran m ON pp.id_metode = m.id_metode
    WHERE pp.id_penjualan = ?
");
$stmt->execute([$id]);
$pembayaran = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Penjualan #<?= escape($penjualan['nomor_faktur']) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #000;
            background: #f1f5f9;
            padding: 20px;
            margin: 0;
        }
        .struk-container {
            max-width: 380px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px dashed #999;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .header h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
        }
        .info-table, .item-table, .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 0;
            font-size: 11px;
        }
        .item-table {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            margin: 8px 0;
        }
        .item-table th {
            text-align: left;
            padding: 4px 0;
            font-size: 11px;
            border-bottom: 1px dashed #aaa;
        }
        .item-table td {
            padding: 4px 0;
            font-size: 11px;
        }
        .total-table td {
            padding: 2px 0;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 11px;
        }
        .btn-print {
            display: block;
            width: 100%;
            max-width: 380px;
            margin: 15px auto 0;
            padding: 8px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .struk-container {
                border: none;
                box-shadow: none;
                width: 100%;
                max-width: 100%;
                padding: 0;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="struk-container">
    <div class="header">
        <h3><?= escape($penjualan['nama_cabang']) ?></h3>
        <p><?= escape($penjualan['alamat_cabang']) ?></p>
        <p>Telp: <?= escape($penjualan['telp_cabang']) ?></p>
    </div>

    <table class="info-table">
        <tr>
            <td>No Faktur</td>
            <td>: <strong><?= escape($penjualan['nomor_faktur']) ?></strong></td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: <?= tanggal_indo($penjualan['tanggal_penjualan'], true) ?></td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td>: <?= escape($penjualan['nama_kasir']) ?></td>
        </tr>
        <tr>
            <td>Pelanggan</td>
            <td>: <?= escape($penjualan['nama_pelanggan']) ?></td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Harga</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td colspan="4" style="padding-top: 5px; font-weight: bold;"><?= escape($item['nama_produk']) ?></td>
                </tr>
                <tr>
                    <td><small><?= escape($item['kode_produk']) ?></small></td>
                    <td style="text-align: center;"><?= $item['jumlah'] ?></td>
                    <td style="text-align: right;"><?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="total-table">
        <tr>
            <td>Subtotal</td>
            <td style="text-align: right;"><?= rupiah($penjualan['total_harga']) ?></td>
        </tr>
        <?php if ((float)$penjualan['diskon'] > 0): ?>
            <tr>
                <td>Diskon</td>
                <td style="text-align: right;">-<?= rupiah($penjualan['diskon']) ?></td>
            </tr>
        <?php endif; ?>
        <?php if ((float)$penjualan['pajak'] > 0): ?>
            <tr>
                <td>Pajak</td>
                <td style="text-align: right;"><?= rupiah($penjualan['pajak']) ?></td>
            </tr>
        <?php endif; ?>
        <tr style="font-weight: bold; font-size: 14px;">
            <td>TOTAL AKHIR</td>
            <td style="text-align: right;"><?= rupiah($penjualan['total_akhir']) ?></td>
        </tr>
        <?php foreach ($pembayaran as $bayar): ?>
            <tr>
                <td><?= escape($bayar['nama_metode']) ?></td>
                <td style="text-align: right;"><?= rupiah($bayar['jumlah_bayar']) ?></td>
            </tr>
            <tr>
                <td>Kembalian</td>
                <td style="text-align: right;"><?= rupiah($bayar['jumlah_kembalian']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="footer">
        <p>*** TERIMA KASIH ATAS KUNJUNGAN ANDA ***</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian sebelumnya.</p>
    </div>
</div>

<button class="btn-print" onclick="window.print()">
    <i class="bi bi-printer"></i> Cetak Struk (Print)
</button>
<a href="<?= base_url('pos.php') ?>" class="btn-print" style="background: #475569; margin-top: 8px;">
    &larr; Kembali ke Kasir (POS)
</a>

</body>
</html>
