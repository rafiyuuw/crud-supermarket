<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Detail Penjualan (Hapus Data)
 * File: modules/detail_penjualan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id_penjualan FROM detail_penjualan WHERE id_detail_penjualan = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        $id_penjualan = (int)$item['id_penjualan'];
        try {
            $pdo->beginTransaction();

            $stmtHapus = $pdo->prepare("DELETE FROM detail_penjualan WHERE id_detail_penjualan = ?");
            $stmtHapus->execute([$id]);

            // Perbarui total_harga dan total_akhir header penjualan
            $stmtUpdateTotal = $pdo->prepare("
                UPDATE penjualan 
                SET total_harga = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?),
                    total_akhir = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_penjualan WHERE id_penjualan = ?) - diskon + pajak
                WHERE id_penjualan = ?
            ");
            $stmtUpdateTotal->execute([$id_penjualan, $id_penjualan, $id_penjualan]);

            $pdo->commit();

            flash_set('success', "Item rincian penjualan berhasil dihapus dan total faktur telah disesuaikan.");
            header('Location: ' . base_url('modules/detail_penjualan/index.php?id_penjualan=' . $id_penjualan));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash_set('danger', "Gagal menghapus item: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Item detail penjualan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/detail_penjualan/index.php'));
exit;
