<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Jabatan (Hapus Data)
 * File: modules/jabatan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama_jabatan FROM jabatan WHERE id_jabatan = ?");
    $stmt->execute([$id]);
    $jab = $stmt->fetch();

    if ($jab) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM jabatan WHERE id_jabatan = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Jabatan <strong>" . escape($jab['nama_jabatan']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1451')) {
                flash_set('danger', "Jabatan <strong>" . escape($jab['nama_jabatan']) . "</strong> tidak dapat dihapus karena masih digunakan oleh data Karyawan!");
            } else {
                flash_set('danger', "Gagal menghapus jabatan: " . $e->getMessage());
            }
        }
    } else {
        flash_set('danger', 'Data jabatan tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/jabatan/index.php'));
exit;
