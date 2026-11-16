<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Metode Pembayaran (Hapus Data)
 * File: modules/metode_pembayaran/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_metode FROM metode_pembayaran WHERE id_metode = ?");
    $stmt->execute([$id]);
    $mtd = $stmt->fetch();

    if ($mtd) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM metode_pembayaran WHERE id_metode = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Metode pembayaran <strong>" . escape($mtd['nama_metode']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Metode <strong>" . escape($mtd['nama_metode']) . "</strong> tidak dapat dihapus karena sudah memiliki riwayat Transaksi Pembayaran!");
            } else {
                flash_set('danger', "Gagal menghapus: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data metode pembayaran tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/metode_pembayaran/index.php'));
exit;
