<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pergerakan Stok (Hapus Data)
 * File: modules/pergerakan_stok/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    try {
        $stmtHapus = $pdo->prepare("DELETE FROM pergerakan_stok WHERE id_pergerakan = ?");
        $stmtHapus->execute([$id]);

        flash_set('success', 'Baris riwayat pergerakan stok berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('danger', 'Gagal menghapus riwayat: ' . $e->getMessage());
    }
}

header('Location: ' . base_url('modules/pergerakan_stok/index.php'));
exit;
