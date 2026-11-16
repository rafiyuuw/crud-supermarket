<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Satuan Produk (Hapus Data)
 * File: modules/satuan_produk/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_satuan FROM satuan_produk WHERE id_satuan = ?");
    $stmt->execute([$id]);
    $sat = $stmt->fetch();

    if ($sat) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM satuan_produk WHERE id_satuan = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Satuan <strong>" . escape($sat['nama_satuan']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Satuan <strong>" . escape($sat['nama_satuan']) . "</strong> tidak dapat dihapus karena masih digunakan oleh data Produk!");
            } else {
                flash_set('danger', "Gagal menghapus: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data satuan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/satuan_produk/index.php'));
exit;
