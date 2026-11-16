<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pembayaran Penjualan (Hapus Data)
 * File: modules/pembayaran_penjualan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id_pembayaran, id_penjualan FROM pembayaran_penjualan WHERE id_pembayaran = ?");
    $stmt->execute([$id]);
    $bayar = $stmt->fetch();

    if ($bayar) {
        $id_penjualan = (int)$bayar['id_penjualan'];
        try {
            $pdo->beginTransaction();

            $stmtHapus = $pdo->prepare("DELETE FROM pembayaran_penjualan WHERE id_pembayaran = ?");
            $stmtHapus->execute([$id]);

            // Cek sisa pembayaran yang berhasil
            $stmtCek = $pdo->prepare("SELECT total_akhir FROM penjualan WHERE id_penjualan = ?");
            $stmtCek->execute([$id_penjualan]);
            $total_akhir = (float)$stmtCek->fetchColumn();

            $stmtTotal = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_penjualan WHERE id_penjualan = ? AND status = 'berhasil'");
            $stmtTotal->execute([$id_penjualan]);
            $sisa_terbayar = (float)$stmtTotal->fetchColumn();

            if ($sisa_terbayar < $total_akhir) {
                $stmtUpdatePJ = $pdo->prepare("UPDATE penjualan SET status_pembayaran = 'belum_lunas' WHERE id_penjualan = ?");
                $stmtUpdatePJ->execute([$id_penjualan]);
            }

            $pdo->commit();

            flash_set('success', 'Catatan pembayaran penjualan berhasil dihapus.');
            header('Location: ' . base_url('modules/pembayaran_penjualan/index.php?id_penjualan=' . $id_penjualan));
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash_set('danger', 'Gagal menghapus pembayaran: ' . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Data pembayaran tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/pembayaran_penjualan/index.php'));
exit;
