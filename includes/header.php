<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Header Layout Template
 * File: includes/header.php
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Pastikan session sudah login
cek_login();
$user_aktif = user_login();

// Penentuan judul halaman jika belum didefinisikan
if (!isset($judul_halaman)) {
    $judul_halaman = 'Sistem Manajemen Supermarket';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($judul_halaman) ?> - Sistem Manajemen Supermarket</title>

    <!-- Bootstrap 5 CSS (CDN dengan fallback lokal) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

<div class="sidebar-overlay"></div>

<div class="app-wrapper">
    <!-- Include Sidebar Navigation -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar Header -->
        <header class="app-header shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-md-block">
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-shop me-1 text-primary"></i> <?= escape($user_aktif['nama_cabang']) ?>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Tombol Cepat Kasir / POS -->
                <a href="<?= base_url('pos.php') ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm px-3">
                    <i class="bi bi-cart-check-fill"></i>
                    <span class="d-none d-sm-inline">Kasir (POS)</span>
                </a>

                <!-- User Dropdown Menu -->
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2 border" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem; font-weight: bold;">
                            <?= strtoupper(substr($user_aktif['nama_karyawan'], 0, 1)) ?>
                        </div>
                        <div class="text-start d-none d-sm-block">
                            <span class="d-block fw-semibold" style="font-size: 0.82rem; line-height: 1.1;"><?= escape($user_aktif['nama_karyawan']) ?></span>
                            <span class="text-muted" style="font-size: 0.72rem;"><?= ucfirst($user_aktif['peran']) ?></span>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="userMenuDropdown">
                        <li class="px-3 py-2 border-bottom">
                            <small class="text-muted d-block">Masuk sebagai</small>
                            <span class="fw-bold"><?= escape($user_aktif['username']) ?></span>
                            <div class="badge bg-info text-dark mt-1"><?= escape($user_aktif['nama_jabatan']) ?></div>
                        </li>
                        <li><a class="dropdown-item py-2" href="<?= base_url('dashboard.php') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="<?= base_url('logout.php') ?>" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
                                <i class="bi bi-box-arrow-right me-2"></i>Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Body Content -->
        <main class="content-body">
            <!-- Flash Notification -->
            <?php flash_display(); ?>
