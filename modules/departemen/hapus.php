<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Departemen (Hapus Data)
 * File: modules/departemen/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_departemen FROM departemen WHERE id_departemen = ?");
    $stmt->execute([$id]);
    $dept = $stmt->fetch();

    if ($dept) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM departemen WHERE id_departemen = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Departemen <strong>" . escape($dept['nama_departemen']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Departemen <strong>" . escape($dept['nama_departemen']) . "</strong> tidak dapat dihapus karena masih memiliki relasi data Kategori Produk!");
            } else {
                flash_set('danger', "Gagal menghapus: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data departemen tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/departemen/index.php'));
exit;
