<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Lokasi Gudang (Hapus Data)
 * File: modules/lokasi_gudang/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_lokasi, kode_lokasi FROM lokasi_gudang WHERE id_lokasi = ?");
    $stmt->execute([$id]);
    $lok = $stmt->fetch();

    if ($lok) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM lokasi_gudang WHERE id_lokasi = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Lokasi/Rak <strong>" . escape($lok['kode_lokasi']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            flash_set('danger', "Gagal menghapus: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Data lokasi gudang tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/lokasi_gudang/index.php'));
exit;
