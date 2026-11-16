<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul: Akun Karyawan (Hapus Data)
 * File: modules/akun_karyawan/hapus.php
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/fungsi.php';

cek_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id = (int)($_GET['id'] ?? 0);

    // Mencegah menghapus akun sendiri
    if ($id === (int)$_SESSION['user_id']) {
        flash_set('danger', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk!');
        header('Location: ' . base_url('modules/akun_karyawan/index.php'));
        exit;
    }

    $stmt = $pdo->prepare("SELECT username FROM akun_karyawan WHERE id_akun = ?");
    $stmt->execute([$id]);
    $akun = $stmt->fetch();

    if ($akun) {
        try {
            $stmtHapus = $pdo->prepare("DELETE FROM akun_karyawan WHERE id_akun = ?");
            $stmtHapus->execute([$id]);

            flash_set('success', "Akun pengguna <strong>" . escape($akun['username']) . "</strong> berhasil dihapus.");
        } catch (PDOException $e) {
            flash_set('danger', "Gagal menghapus akun: " . $e->getMessage());
        }
    } else {
        flash_set('danger', 'Data akun tidak ditemukan.');
    }
}

header('Location: ' . base_url('modules/akun_karyawan/index.php'));
exit;
