<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pelanggan (Hapus Data)
 * File: modules/pelanggan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_lengkap FROM pelanggan WHERE id_pelanggan = ?");
    $stmt->execute([$id]);
    $plg = $stmt->fetch();

    if ($plg) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Pelanggan <strong>" . escape($plg['nama_lengkap']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Pelanggan <strong>" . escape($plg['nama_lengkap']) . "</strong> tidak dapat dihapus karena sudah memiliki riwayat Transaksi Penjualan!");
            } else {
                flash_set('danger', "Gagal menghapus pelanggan: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data pelanggan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/pelanggan/index.php'));
exit;
