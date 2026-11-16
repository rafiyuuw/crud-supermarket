<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Kategori (Hapus Data)
 * File: modules/kategori/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
    $stmt->execute([$id]);
    $kat = $stmt->fetch();

    if ($kat) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Kategori <strong>" . escape($kat['nama_kategori']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Kategori <strong>" . escape($kat['nama_kategori']) . "</strong> tidak dapat dihapus karena masih digunakan pada data Subkategori!");
            } else {
                flash_set('danger', "Gagal menghapus kategori: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data kategori tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/kategori/index.php'));
exit;
