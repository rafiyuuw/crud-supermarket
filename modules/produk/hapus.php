<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Produk (Hapus Data)
 * File: modules/produk/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_produk FROM produk WHERE id_produk = ?");
    $stmt->execute([$id]);
    $prd = $stmt->fetch();

    if ($prd) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM produk WHERE id_produk = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Produk <strong>" . escape($prd['nama_produk']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Produk <strong>" . escape($prd['nama_produk']) . "</strong> tidak dapat dihapus karena masih terkait dalam transaksi Penjualan, Pesanan Pembelian, Persediaan Gudang, atau Pergerakan Stok!");
            } else {
                flash_set('danger', "Gagal menghapus produk: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data produk tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/produk/index.php'));
exit;
