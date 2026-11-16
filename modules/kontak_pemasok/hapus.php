<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Kontak Pemasok (Hapus Data)
 * File: modules/kontak_pemasok/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_kontak, id_pemasok FROM kontak_pemasok WHERE id_kontak = ?");
    $stmt->execute([$id]);
    $kp = $stmt->fetch();

    if ($kp) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM kontak_pemasok WHERE id_kontak = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Kontak <strong>" . escape($kp['nama_kontak']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            flash_set('danger', "Gagal menghapus kontak: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Data kontak tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/kontak_pemasok/index.php'));
exit;
