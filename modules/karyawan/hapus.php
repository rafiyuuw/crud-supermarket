<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Karyawan (Hapus Data)
 * File: modules/karyawan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_karyawan FROM karyawan WHERE id_karyawan = ?");
    $stmt->execute([$id]);
    $kry = $stmt->fetch();

    if ($kry) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM karyawan WHERE id_karyawan = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Karyawan <strong>" . escape($kry['nama_karyawan']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Karyawan <strong>" . escape($kry['nama_karyawan']) . "</strong> tidak dapat dihapus karena tercatat dalam transaksi Kasir Penjualan, Pesanan Pembelian, atau Riwayat Pergerakan Stok!");
            } else {
                flash_set('danger', "Gagal menghapus karyawan: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data karyawan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/karyawan/index.php'));
exit;
