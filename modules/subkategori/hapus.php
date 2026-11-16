<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Subkategori (Hapus Data)
 * File: modules/subkategori/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_subkategori FROM subkategori WHERE id_subkategori = ?");
    $stmt->execute([$id]);
    $sub = $stmt->fetch();

    if ($sub) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM subkategori WHERE id_subkategori = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Subkategori <strong>" . escape($sub['nama_subkategori']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Subkategori <strong>" . escape($sub['nama_subkategori']) . "</strong> tidak dapat dihapus karena masih digunakan oleh data Produk!");
            } else {
                flash_set('danger', "Gagal menghapus: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data subkategori tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/subkategori/index.php'));
exit;
