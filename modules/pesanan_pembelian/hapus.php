<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pesanan Pembelian (Hapus Data)
 * File: modules/pesanan_pembelian/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nomor_pesanan, status FROM pesanan_pembelian WHERE id_pesanan = ?");
    $stmt->execute([$id]);
    $po = $stmt->fetch();

    if ($po) {
        if ($po['status'] === 'diterima') {
            flash_set('warning', "PO <strong>" . escape($po['nomor_pesanan']) . "</strong> sudah berstatus 'diterima' dan barang telah masuk ke gudang. Untuk menjaga konsistensi stok, PO ini tidak dapat dihapus sembarangan.");
        } else {
            try {
                $stmtHapus = $pdo->prepare("DELETE FROM pesanan_pembelian WHERE id_pesanan = ?");
                $stmtHapus->execute([$id]);

                flash_set('success', "Pesanan pembelian <strong>" . escape($po['nomor_pesanan']) . "</strong> berhasil dihapus.");
            } catch (PDOException $e) {
                flash_set('danger', "Gagal menghapus pesanan: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data pesanan pembelian tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/pesanan_pembelian/index.php'));
exit;
