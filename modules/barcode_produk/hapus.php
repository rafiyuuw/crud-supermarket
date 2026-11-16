<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Barcode Produk (Hapus Data)
 * File: modules/barcode_produk/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT kode_barcode FROM barcode_produk WHERE id_barcode = ?");
    $stmt->execute([$id]);
    $bar = $stmt->fetch();

    if ($bar) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM barcode_produk WHERE id_barcode = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Barcode <strong>" . escape($bar['kode_barcode']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            flash_set('danger', "Gagal menghapus barcode: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Data barcode tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/barcode_produk/index.php'));
exit;
