<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Sidebar Navigation Template
 * File: includes/sidebar.php
 */

$menu = $menu_aktif ?? '';
?>
<aside class="app-sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <a href="<?= base_url('dashboard.php') ?>">
            <i class="bi bi-cart3 text-primary fs-4"></i>
            <div>
                <div style="line-height: 1.1; font-size: 1rem;">SUPERMARKET</div>
                <small style="font-size: 0.65rem; color: #94a3b8; font-weight: normal; letter-spacing: 0.05em;">SISTEM MANAJEMEN</small>
            </div>
        </a>
    </div>

    <!-- Navigation Menu Items -->
    <div class="sidebar-menu">
        <!-- 1. DASHBOARD -->
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'dashboard') ? 'active' : '' ?>" href="<?= base_url('dashboard.php') ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'pos') ? 'active' : '' ?>" href="<?= base_url('pos.php') ?>">
                <i class="bi bi-calculator"></i>
                <span class="text-warning fw-semibold">Kasir Cepat (POS)</span>
            </a>
        </div>

        <!-- 2. DATA UTAMA -->
        <div class="sidebar-heading">Data Utama</div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'cabang') ? 'active' : '' ?>" href="<?= base_url('modules/cabang/index.php') ?>">
                <i class="bi bi-building"></i>
                <span>Cabang</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'departemen') ? 'active' : '' ?>" href="<?= base_url('modules/departemen/index.php') ?>">
                <i class="bi bi-diagram-3"></i>
                <span>Departemen</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'kategori') ? 'active' : '' ?>" href="<?= base_url('modules/kategori/index.php') ?>">
                <i class="bi bi-tags"></i>
                <span>Kategori</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'subkategori') ? 'active' : '' ?>" href="<?= base_url('modules/subkategori/index.php') ?>">
                <i class="bi bi-tag"></i>
                <span>Subkategori</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'merek_produk') ? 'active' : '' ?>" href="<?= base_url('modules/merek_produk/index.php') ?>">
                <i class="bi bi-patch-check"></i>
                <span>Merek Produk</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'satuan_produk') ? 'active' : '' ?>" href="<?= base_url('modules/satuan_produk/index.php') ?>">
                <i class="bi bi-rulers"></i>
                <span>Satuan Produk</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'produk') ? 'active' : '' ?>" href="<?= base_url('modules/produk/index.php') ?>">
                <i class="bi bi-box-seam"></i>
                <span>Produk</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'barcode_produk') ? 'active' : '' ?>" href="<?= base_url('modules/barcode_produk/index.php') ?>">
                <i class="bi bi-upc-scan"></i>
                <span>Barcode Produk</span>
            </a>
        </div>

        <!-- 3. PENGADAAN BARANG -->
        <div class="sidebar-heading">Pengadaan Barang</div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'pemasok') ? 'active' : '' ?>" href="<?= base_url('modules/pemasok/index.php') ?>">
                <i class="bi bi-truck"></i>
                <span>Pemasok</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'kontak_pemasok') ? 'active' : '' ?>" href="<?= base_url('modules/kontak_pemasok/index.php') ?>">
                <i class="bi bi-person-lines-fill"></i>
                <span>Kontak Pemasok</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'pesanan_pembelian') ? 'active' : '' ?>" href="<?= base_url('modules/pesanan_pembelian/index.php') ?>">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span>Pesanan Pembelian</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'detail_pesanan_pembelian') ? 'active' : '' ?>" href="<?= base_url('modules/detail_pesanan_pembelian/index.php') ?>">
                <i class="bi bi-card-checklist"></i>
                <span>Detail Pesanan Pembelian</span>
            </a>
        </div>

        <!-- 4. DATA PELANGGAN -->
        <div class="sidebar-heading">Pelanggan</div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'pelanggan') ? 'active' : '' ?>" href="<?= base_url('modules/pelanggan/index.php') ?>">
                <i class="bi bi-people"></i>
                <span>Data Pelanggan</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'alamat_pelanggan') ? 'active' : '' ?>" href="<?= base_url('modules/alamat_pelanggan/index.php') ?>">
                <i class="bi bi-geo-alt"></i>
                <span>Alamat Pelanggan</span>
            </a>
        </div>

        <!-- 5. DATA KARYAWAN -->
        <div class="sidebar-heading">Karyawan</div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'jabatan') ? 'active' : '' ?>" href="<?= base_url('modules/jabatan/index.php') ?>">
                <i class="bi bi-briefcase"></i>
                <span>Jabatan</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'karyawan') ? 'active' : '' ?>" href="<?= base_url('modules/karyawan/index.php') ?>">
                <i class="bi bi-person-badge"></i>
                <span>Data Karyawan</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'akun_karyawan') ? 'active' : '' ?>" href="<?= base_url('modules/akun_karyawan/index.php') ?>">
                <i class="bi bi-key"></i>
                <span>Akun Karyawan</span>
            </a>
        </div>

        <!-- 6. MANAJEMEN INVENTARIS -->
        <div class="sidebar-heading">Inventaris</div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'gudang') ? 'active' : '' ?>" href="<?= base_url('modules/gudang/index.php') ?>">
                <i class="bi bi-house-door"></i>
                <span>Gudang</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'lokasi_gudang') ? 'active' : '' ?>" href="<?= base_url('modules/lokasi_gudang/index.php') ?>">
                <i class="bi bi-grid-3x3"></i>
                <span>Lokasi Gudang</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'persediaan') ? 'active' : '' ?>" href="<?= base_url('modules/persediaan/index.php') ?>">
                <i class="bi bi-stack"></i>
                <span>Persediaan</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'pergerakan_stok') ? 'active' : '' ?>" href="<?= base_url('modules/pergerakan_stok/index.php') ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>Pergerakan Stok</span>
            </a>
        </div>

        <!-- 7. PENJUALAN -->
        <div class="sidebar-heading">Penjualan</div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'penjualan') ? 'active' : '' ?>" href="<?= base_url('modules/penjualan/index.php') ?>">
                <i class="bi bi-receipt"></i>
                <span>Data Penjualan</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'detail_penjualan') ? 'active' : '' ?>" href="<?= base_url('modules/detail_penjualan/index.php') ?>">
                <i class="bi bi-list-stars"></i>
                <span>Detail Penjualan</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'metode_pembayaran') ? 'active' : '' ?>" href="<?= base_url('modules/metode_pembayaran/index.php') ?>">
                <i class="bi bi-credit-card-2-front"></i>
                <span>Metode Pembayaran</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link <?= ($menu === 'pembayaran_penjualan') ? 'active' : '' ?>" href="<?= base_url('modules/pembayaran_penjualan/index.php') ?>">
                <i class="bi bi-cash-stack"></i>
                <span>Pembayaran Penjualan</span>
            </a>
        </div>

        <div style="height: 30px;"></div>
    </div>
</aside>
