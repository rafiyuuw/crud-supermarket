<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Detail Pesanan Pembelian (Hapus Data)
 * File: modules/detail_pesanan_pembelian/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id_pesanan FROM detail_pesanan_pembelian WHERE id_detail_pesanan = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        $id_pesanan = (int)$item['id_pesanan'];
        try {
            $pdo->beginTransaction();

            $stmtHapus = $pdo->prepare("DELETE FROM detail_pesanan_pembelian WHERE id_detail_pesanan = ?");
            $stmtHapus->execute([$id]);

            // Perbarui total_nominal header PO
            $stmtTotal = $pdo->prepare("
                UPDATE pesanan_pembelian 
                SET total_nominal = (SELECT COALESCE(SUM(subtotal), 0) FROM detail_pesanan_pembelian WHERE id_pesanan = ?)
                WHERE id_pesanan = ?
            ");
            $stmtTotal->execute([$id_pesanan, $id_pesanan]);

            $pdo->commit();

            flash_set('success', "Item detail pesanan berhasil dihapus dan total nominal PO telah disesuaikan.");
            header('Location: ' . base_url('modules/detail_pesanan_pembelian/index.php?id_pesanan=' . $id_pesanan));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash_set('danger', "Gagal menghapus item: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Item detail pesanan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/detail_pesanan_pembelian/index.php'));
exit;
