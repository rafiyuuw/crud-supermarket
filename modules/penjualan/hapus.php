<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Penjualan (Hapus Data)
 * File: modules/penjualan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nomor_faktur FROM penjualan WHERE id_penjualan = ?");
    $stmt->execute([$id]);
    $pj = $stmt->fetch();

    if ($pj) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM penjualan WHERE id_penjualan = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Transaksi penjualan <strong>" . escape($pj['nomor_faktur']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            flash_set('danger', "Gagal menghapus transaksi: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Data penjualan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/penjualan/index.php'));
exit;
