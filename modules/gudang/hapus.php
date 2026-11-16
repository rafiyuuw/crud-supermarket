<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Gudang (Hapus Data)
 * File: modules/gudang/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_gudang FROM gudang WHERE id_gudang = ?");
    $stmt->execute([$id]);
    $gdg = $stmt->fetch();

    if ($gdg) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM gudang WHERE id_gudang = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Gudang <strong>" . escape($gdg['nama_gudang']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Gudang <strong>" . escape($gdg['nama_gudang']) . "</strong> tidak dapat dihapus karena masih memuat data Rak/Lokasi, Persediaan Stok, atau PO Pembelian!");
            } else {
                flash_set('danger', "Gagal menghapus gudang: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data gudang tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/gudang/index.php'));
exit;
