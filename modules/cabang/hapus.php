<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Cabang (Hapus Data)
 * File: modules/cabang/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    // Ambil data untuk pesan info
    $stmt = $pdo->prepare("SELECT nama_cabang FROM cabang WHERE id_cabang = ?");
    $stmt->execute([$id]);
    $cabang = $stmt->fetch();

    if ($cabang) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM cabang WHERE id_cabang = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Cabang <strong>" . escape($cabang['nama_cabang']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Cabang <strong>" . escape($cabang['nama_cabang']) . "</strong> tidak dapat dihapus karena masih terhubung dengan data Karyawan, Gudang, atau Transaksi Penjualan.");
            } else {
                flash_set('danger', "Gagal menghapus cabang: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data cabang tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/cabang/index.php'));
exit;
