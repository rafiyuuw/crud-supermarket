<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Pemasok (Hapus Data)
 * File: modules/pemasok/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_pemasok FROM pemasok WHERE id_pemasok = ?");
    $stmt->execute([$id]);
    $spl = $stmt->fetch();

    if ($spl) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM pemasok WHERE id_pemasok = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Pemasok <strong>" . escape($spl['nama_pemasok']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Pemasok <strong>" . escape($spl['nama_pemasok']) . "</strong> tidak dapat dihapus karena masih terhubung dengan data Kontak Pemasok atau Pesanan Pembelian (PO)!");
            } else {
                flash_set('danger', "Gagal menghapus pemasok: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data pemasok tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/pemasok/index.php'));
exit;
