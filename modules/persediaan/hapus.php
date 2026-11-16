<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Persediaan (Hapus Data)
 * File: modules/persediaan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT ps.jumlah_stok, pr.nama_produk, g.nama_gudang 
        FROM persediaan ps
        INNER JOIN produk pr ON ps.id_produk = pr.id_produk
        INNER JOIN gudang g ON ps.id_gudang = g.id_gudang
        WHERE ps.id_persediaan = ?
    ");
    $stmt->execute([$id]);
    $ps = $stmt->fetch();

    if ($ps) {
        if ($ps['jumlah_stok'] > 0) {
            flash_set('danger', "Persediaan <strong>" . escape($ps['nama_produk']) . "</strong> di <strong>" . escape($ps['nama_gudang']) . "</strong> tidak dapat dihapus karena masih memiliki sisa stok ({$ps['jumlah_stok']} unit). Harap kosongkan stok terlebih dahulu melalui menu penyesuaian.");
        } else {
            try {
                $stmtHapus = $pdo->prepare("DELETE FROM persediaan WHERE id_persediaan = ?");
                $stmtHapus->execute([$id]);

                flash_set('success', "Baris persediaan produk berhasil dihapus.");
            } catch (PDOException $e) {
                flash_set('danger', "Gagal menghapus persediaan: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data persediaan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/persediaan/index.php'));
exit;
