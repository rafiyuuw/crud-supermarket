<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Alamat Pelanggan (Hapus Data)
 * File: modules/alamat_pelanggan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT label_alamat, id_pelanggan FROM alamat_pelanggan WHERE id_alamat = ?");
    $stmt->execute([$id]);
    $almt = $stmt->fetch();

    if ($almt) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM alamat_pelanggan WHERE id_alamat = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Alamat <strong>" . escape($almt['label_alamat']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            flash_set('danger', "Gagal menghapus alamat: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Data alamat tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/alamat_pelanggan/index.php'));
exit;
