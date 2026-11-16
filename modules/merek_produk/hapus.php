<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Merek Produk (Hapus Data)
 * File: modules/merek_produk/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_merek FROM merek_produk WHERE id_merek = ?");
    $stmt->execute([$id]);
    $mrk = $stmt->fetch();

    if ($mrk) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM merek_produk WHERE id_merek = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Merek <strong>" . escape($mrk['nama_merek']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Merek <strong>" . escape($mrk['nama_merek']) . "</strong> tidak dapat dihapus karena masih digunakan oleh data Produk!");
            } else {
                flash_set('danger', "Gagal menghapus: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data merek tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/merek_produk/index.php'));
exit;
