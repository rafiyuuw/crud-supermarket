-- ==============================================================================
-- DATABASE: SISTEM MANAJEMEN SUPERMARKET
-- File: database.sql
-- Keterangan: Dibuat untuk implementasi 25 tabel relasional lengkap
-- DBMS: MySQL 8.0+ / MariaDB (InnoDB)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `db_supermarket` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_supermarket`;

-- Nonaktifkan pengecekan foreign key sementara untuk drop table aman jika ada
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `pembayaran_penjualan`;
DROP TABLE IF EXISTS `detail_penjualan`;
DROP TABLE IF EXISTS `penjualan`;
DROP TABLE IF EXISTS `metode_pembayaran`;

DROP TABLE IF EXISTS `pergerakan_stok`;
DROP TABLE IF EXISTS `persediaan`;
DROP TABLE IF EXISTS `lokasi_gudang`;
DROP TABLE IF EXISTS `gudang`;

DROP TABLE IF EXISTS `detail_pesanan_pembelian`;
DROP TABLE IF EXISTS `pesanan_pembelian`;
DROP TABLE IF EXISTS `kontak_pemasok`;
DROP TABLE IF EXISTS `pemasok`;

DROP TABLE IF EXISTS `alamat_pelanggan`;
DROP TABLE IF EXISTS `pelanggan`;

DROP TABLE IF EXISTS `akun_karyawan`;
DROP TABLE IF EXISTS `karyawan`;
DROP TABLE IF EXISTS `jabatan`;

DROP TABLE IF EXISTS `barcode_produk`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `satuan_produk`;
DROP TABLE IF EXISTS `merek_produk`;
DROP TABLE IF EXISTS `subkategori`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `departemen`;
DROP TABLE IF EXISTS `cabang`;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- 1. TABEL: cabang (Data Utama)
-- ==============================================================================
CREATE TABLE `cabang` (
    `id_cabang` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_cabang` VARCHAR(20) NOT NULL UNIQUE,
    `nama_cabang` VARCHAR(100) NOT NULL,
    `alamat` TEXT NOT NULL,
    `telepon` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cabang_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 2. TABEL: departemen (Data Utama)
-- ==============================================================================
CREATE TABLE `departemen` (
    `id_departemen` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_departemen` VARCHAR(20) NOT NULL UNIQUE,
    `nama_departemen` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    INDEX `idx_departemen_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 3. TABEL: kategori (Data Utama)
-- ==============================================================================
CREATE TABLE `kategori` (
    `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
    `id_departemen` INT NOT NULL,
    `kode_kategori` VARCHAR(20) NOT NULL UNIQUE,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    INDEX `idx_kategori_departemen` (`id_departemen`),
    CONSTRAINT `fk_kategori_departemen` FOREIGN KEY (`id_departemen`) 
        REFERENCES `departemen` (`id_departemen`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 4. TABEL: subkategori (Data Utama)
-- ==============================================================================
CREATE TABLE `subkategori` (
    `id_subkategori` INT AUTO_INCREMENT PRIMARY KEY,
    `id_kategori` INT NOT NULL,
    `kode_subkategori` VARCHAR(20) NOT NULL UNIQUE,
    `nama_subkategori` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    INDEX `idx_subkategori_kategori` (`id_kategori`),
    CONSTRAINT `fk_subkategori_kategori` FOREIGN KEY (`id_kategori`) 
        REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 5. TABEL: merek_produk (Data Utama)
-- ==============================================================================
CREATE TABLE `merek_produk` (
    `id_merek` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_merek` VARCHAR(20) NOT NULL UNIQUE,
    `nama_merek` VARCHAR(100) NOT NULL,
    `produsen` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    INDEX `idx_merek_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 6. TABEL: satuan_produk (Data Utama)
-- ==============================================================================
CREATE TABLE `satuan_produk` (
    `id_satuan` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_satuan` VARCHAR(20) NOT NULL UNIQUE,
    `nama_satuan` VARCHAR(50) NOT NULL,
    `simbol` VARCHAR(10) NOT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 7. TABEL: produk (Data Utama)
-- ==============================================================================
CREATE TABLE `produk` (
    `id_produk` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_produk` VARCHAR(30) NOT NULL UNIQUE,
    `nama_produk` VARCHAR(150) NOT NULL,
    `id_subkategori` INT NOT NULL,
    `id_merek` INT NOT NULL,
    `id_satuan` INT NOT NULL,
    `harga_beli` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `harga_jual` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `stok_minimum` INT NOT NULL DEFAULT 5,
    `deskripsi` TEXT DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_produk_subkategori` (`id_subkategori`),
    INDEX `idx_produk_merek` (`id_merek`),
    INDEX `idx_produk_satuan` (`id_satuan`),
    INDEX `idx_produk_nama` (`nama_produk`),
    CONSTRAINT `fk_produk_subkategori` FOREIGN KEY (`id_subkategori`) 
        REFERENCES `subkategori` (`id_subkategori`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_produk_merek` FOREIGN KEY (`id_merek`) 
        REFERENCES `merek_produk` (`id_merek`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_produk_satuan` FOREIGN KEY (`id_satuan`) 
        REFERENCES `satuan_produk` (`id_satuan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 8. TABEL: barcode_produk (Data Utama)
-- ==============================================================================
CREATE TABLE `barcode_produk` (
    `id_barcode` INT AUTO_INCREMENT PRIMARY KEY,
    `id_produk` INT NOT NULL,
    `kode_barcode` VARCHAR(50) NOT NULL UNIQUE,
    `jenis_barcode` ENUM('EAN-13', 'UPC-A', 'Code-128', 'QR-Code', 'Lainnya') NOT NULL DEFAULT 'EAN-13',
    `status` ENUM('utama', 'tambahan', 'nonaktif') NOT NULL DEFAULT 'utama',
    `catatan` VARCHAR(100) DEFAULT NULL,
    INDEX `idx_barcode_produk` (`id_produk`),
    CONSTRAINT `fk_barcode_produk` FOREIGN KEY (`id_produk`) 
        REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 9. TABEL: pemasok (Pengadaan Barang)
-- ==============================================================================
CREATE TABLE `pemasok` (
    `id_pemasok` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_pemasok` VARCHAR(20) NOT NULL UNIQUE,
    `nama_pemasok` VARCHAR(150) NOT NULL,
    `alamat` TEXT NOT NULL,
    `telepon` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `npwp` VARCHAR(30) DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pemasok_nama` (`nama_pemasok`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 10. TABEL: kontak_pemasok (Pengadaan Barang)
-- ==============================================================================
CREATE TABLE `kontak_pemasok` (
    `id_kontak` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pemasok` INT NOT NULL,
    `nama_kontak` VARCHAR(100) NOT NULL,
    `jabatan` VARCHAR(50) DEFAULT NULL,
    `nomor_hp` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('utama', 'cadangan') NOT NULL DEFAULT 'utama',
    INDEX `idx_kontak_pemasok` (`id_pemasok`),
    CONSTRAINT `fk_kontak_pemasok` FOREIGN KEY (`id_pemasok`) 
        REFERENCES `pemasok` (`id_pemasok`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 11. TABEL: jabatan (Data Karyawan)
-- ==============================================================================
CREATE TABLE `jabatan` (
    `id_jabatan` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_jabatan` VARCHAR(20) NOT NULL UNIQUE,
    `nama_jabatan` VARCHAR(50) NOT NULL,
    `gaji_pokok` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `deskripsi` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 12. TABEL: karyawan (Data Karyawan)
-- ==============================================================================
CREATE TABLE `karyawan` (
    `id_karyawan` INT AUTO_INCREMENT PRIMARY KEY,
    `nik` VARCHAR(30) NOT NULL UNIQUE,
    `nama_karyawan` VARCHAR(100) NOT NULL,
    `id_jabatan` INT NOT NULL,
    `id_cabang` INT NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `nomor_telepon` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `alamat` TEXT DEFAULT NULL,
    `tanggal_masuk` DATE NOT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    INDEX `idx_karyawan_jabatan` (`id_jabatan`),
    INDEX `idx_karyawan_cabang` (`id_cabang`),
    CONSTRAINT `fk_karyawan_jabatan` FOREIGN KEY (`id_jabatan`) 
        REFERENCES `jabatan` (`id_jabatan`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_karyawan_cabang` FOREIGN KEY (`id_cabang`) 
        REFERENCES `cabang` (`id_cabang`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 13. TABEL: akun_karyawan (Data Karyawan)
-- ==============================================================================
CREATE TABLE `akun_karyawan` (
    `id_akun` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL UNIQUE,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `peran` ENUM('admin', 'kasir', 'gudang', 'manajer') NOT NULL DEFAULT 'admin',
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `terakhir_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_akun_karyawan` FOREIGN KEY (`id_karyawan`) 
        REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 14. TABEL: gudang (Manajemen Inventaris)
-- ==============================================================================
CREATE TABLE `gudang` (
    `id_gudang` INT AUTO_INCREMENT PRIMARY KEY,
    `id_cabang` INT NOT NULL,
    `kode_gudang` VARCHAR(20) NOT NULL UNIQUE,
    `nama_gudang` VARCHAR(100) NOT NULL,
    `alamat` TEXT NOT NULL,
    `kapasitas_m3` INT NOT NULL DEFAULT 100,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    INDEX `idx_gudang_cabang` (`id_cabang`),
    CONSTRAINT `fk_gudang_cabang` FOREIGN KEY (`id_cabang`) 
        REFERENCES `cabang` (`id_cabang`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 15. TABEL: lokasi_gudang (Manajemen Inventaris)
-- ==============================================================================
CREATE TABLE `lokasi_gudang` (
    `id_lokasi` INT AUTO_INCREMENT PRIMARY KEY,
    `id_gudang` INT NOT NULL,
    `kode_lokasi` VARCHAR(20) NOT NULL UNIQUE,
    `nama_lokasi` VARCHAR(50) NOT NULL,
    `keterangan` VARCHAR(100) DEFAULT NULL,
    INDEX `idx_lokasi_gudang` (`id_gudang`),
    CONSTRAINT `fk_lokasi_gudang` FOREIGN KEY (`id_gudang`) 
        REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 16. TABEL: persediaan (Manajemen Inventaris)
-- ==============================================================================
CREATE TABLE `persediaan` (
    `id_persediaan` INT AUTO_INCREMENT PRIMARY KEY,
    `id_produk` INT NOT NULL,
    `id_gudang` INT NOT NULL,
    `id_lokasi` INT DEFAULT NULL,
    `jumlah_stok` INT NOT NULL DEFAULT 0,
    `stok_minimum` INT NOT NULL DEFAULT 5,
    `terakhir_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_produk_gudang` (`id_produk`, `id_gudang`),
    INDEX `idx_persediaan_produk` (`id_produk`),
    INDEX `idx_persediaan_gudang` (`id_gudang`),
    INDEX `idx_persediaan_lokasi` (`id_lokasi`),
    CONSTRAINT `fk_persediaan_produk` FOREIGN KEY (`id_produk`) 
        REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_persediaan_gudang` FOREIGN KEY (`id_gudang`) 
        REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_persediaan_lokasi` FOREIGN KEY (`id_lokasi`) 
        REFERENCES `lokasi_gudang` (`id_lokasi`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 17. TABEL: pergerakan_stok (Manajemen Inventaris)
-- ==============================================================================
CREATE TABLE `pergerakan_stok` (
    `id_pergerakan` INT AUTO_INCREMENT PRIMARY KEY,
    `id_produk` INT NOT NULL,
    `id_gudang` INT NOT NULL,
    `id_karyawan` INT NOT NULL,
    `jenis_pergerakan` ENUM('masuk', 'keluar', 'penyesuaian', 'retur') NOT NULL,
    `jumlah` INT NOT NULL,
    `stok_sebelum` INT NOT NULL,
    `stok_sesudah` INT NOT NULL,
    `referensi` VARCHAR(50) DEFAULT NULL,
    `keterangan` TEXT DEFAULT NULL,
    `tanggal` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pergerakan_produk` (`id_produk`),
    INDEX `idx_pergerakan_gudang` (`id_gudang`),
    INDEX `idx_pergerakan_karyawan` (`id_karyawan`),
    CONSTRAINT `fk_pergerakan_produk` FOREIGN KEY (`id_produk`) 
        REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_pergerakan_gudang` FOREIGN KEY (`id_gudang`) 
        REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_pergerakan_karyawan` FOREIGN KEY (`id_karyawan`) 
        REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 18. TABEL: pesanan_pembelian (Pengadaan Barang)
-- ==============================================================================
CREATE TABLE `pesanan_pembelian` (
    `id_pesanan` INT AUTO_INCREMENT PRIMARY KEY,
    `nomor_pesanan` VARCHAR(30) NOT NULL UNIQUE,
    `id_pemasok` INT NOT NULL,
    `id_karyawan` INT NOT NULL,
    `id_gudang` INT NOT NULL,
    `tanggal_pesanan` DATE NOT NULL,
    `tanggal_estimasi` DATE DEFAULT NULL,
    `total_nominal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft', 'dipesan', 'diterima', 'dibatalkan') NOT NULL DEFAULT 'draft',
    `catatan` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_po_pemasok` (`id_pemasok`),
    INDEX `idx_po_karyawan` (`id_karyawan`),
    INDEX `idx_po_gudang` (`id_gudang`),
    CONSTRAINT `fk_po_pemasok` FOREIGN KEY (`id_pemasok`) 
        REFERENCES `pemasok` (`id_pemasok`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_po_karyawan` FOREIGN KEY (`id_karyawan`) 
        REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_po_gudang` FOREIGN KEY (`id_gudang`) 
        REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 19. TABEL: detail_pesanan_pembelian (Pengadaan Barang)
-- ==============================================================================
CREATE TABLE `detail_pesanan_pembelian` (
    `id_detail_pesanan` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pesanan` INT NOT NULL,
    `id_produk` INT NOT NULL,
    `jumlah` INT NOT NULL,
    `harga_satuan` DECIMAL(15,2) NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `catatan` VARCHAR(100) DEFAULT NULL,
    INDEX `idx_detail_po_pesanan` (`id_pesanan`),
    INDEX `idx_detail_po_produk` (`id_produk`),
    CONSTRAINT `fk_detail_po_pesanan` FOREIGN KEY (`id_pesanan`) 
        REFERENCES `pesanan_pembelian` (`id_pesanan`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_detail_po_produk` FOREIGN KEY (`id_produk`) 
        REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 20. TABEL: pelanggan (Data Pelanggan)
-- ==============================================================================
CREATE TABLE `pelanggan` (
    `id_pelanggan` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_pelanggan` VARCHAR(20) NOT NULL UNIQUE,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `nomor_telepon` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `poin_loyalitas` INT NOT NULL DEFAULT 0,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `tanggal_daftar` DATE NOT NULL,
    INDEX `idx_pelanggan_nama` (`nama_lengkap`),
    INDEX `idx_pelanggan_telepon` (`nomor_telepon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 21. TABEL: alamat_pelanggan (Data Pelanggan)
-- ==============================================================================
CREATE TABLE `alamat_pelanggan` (
    `id_alamat` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pelanggan` INT NOT NULL,
    `label_alamat` VARCHAR(50) NOT NULL,
    `alamat_lengkap` TEXT NOT NULL,
    `kota` VARCHAR(50) NOT NULL,
    `provinsi` VARCHAR(50) NOT NULL,
    `kode_pos` VARCHAR(10) NOT NULL,
    `status_utama` ENUM('ya', 'tidak') NOT NULL DEFAULT 'tidak',
    INDEX `idx_alamat_pelanggan` (`id_pelanggan`),
    CONSTRAINT `fk_alamat_pelanggan` FOREIGN KEY (`id_pelanggan`) 
        REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 22. TABEL: metode_pembayaran (Penjualan)
-- ==============================================================================
CREATE TABLE `metode_pembayaran` (
    `id_metode` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_metode` VARCHAR(20) NOT NULL UNIQUE,
    `nama_metode` VARCHAR(50) NOT NULL,
    `kategori` ENUM('tunai', 'kartu', 'dompet_digital', 'transfer') NOT NULL DEFAULT 'tunai',
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 23. TABEL: penjualan (Penjualan)
-- ==============================================================================
CREATE TABLE `penjualan` (
    `id_penjualan` INT AUTO_INCREMENT PRIMARY KEY,
    `nomor_faktur` VARCHAR(30) NOT NULL UNIQUE,
    `id_cabang` INT NOT NULL,
    `id_pelanggan` INT DEFAULT NULL,
    `id_karyawan` INT NOT NULL,
    `tanggal_penjualan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total_harga` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `diskon` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `pajak` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_akhir` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status_pembayaran` ENUM('lunas', 'belum_lunas', 'batal') NOT NULL DEFAULT 'lunas',
    `catatan` TEXT DEFAULT NULL,
    INDEX `idx_penjualan_faktur` (`nomor_faktur`),
    INDEX `idx_penjualan_cabang` (`id_cabang`),
    INDEX `idx_penjualan_pelanggan` (`id_pelanggan`),
    INDEX `idx_penjualan_karyawan` (`id_karyawan`),
    CONSTRAINT `fk_penjualan_cabang` FOREIGN KEY (`id_cabang`) 
        REFERENCES `cabang` (`id_cabang`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_penjualan_pelanggan` FOREIGN KEY (`id_pelanggan`) 
        REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_penjualan_karyawan` FOREIGN KEY (`id_karyawan`) 
        REFERENCES `karyawan` (`id_karyawan`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 24. TABEL: detail_penjualan (Penjualan)
-- ==============================================================================
CREATE TABLE `detail_penjualan` (
    `id_detail_penjualan` INT AUTO_INCREMENT PRIMARY KEY,
    `id_penjualan` INT NOT NULL,
    `id_produk` INT NOT NULL,
    `harga_satuan` DECIMAL(15,2) NOT NULL,
    `jumlah` INT NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `diskon_item` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    INDEX `idx_detail_penjualan_header` (`id_penjualan`),
    INDEX `idx_detail_penjualan_produk` (`id_produk`),
    CONSTRAINT `fk_detail_penjualan_header` FOREIGN KEY (`id_penjualan`) 
        REFERENCES `penjualan` (`id_penjualan`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_detail_penjualan_produk` FOREIGN KEY (`id_produk`) 
        REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 25. TABEL: pembayaran_penjualan (Penjualan)
-- ==============================================================================
CREATE TABLE `pembayaran_penjualan` (
    `id_pembayaran` INT AUTO_INCREMENT PRIMARY KEY,
    `id_penjualan` INT NOT NULL,
    `id_metode` INT NOT NULL,
    `nomor_transaksi` VARCHAR(50) DEFAULT NULL,
    `jumlah_bayar` DECIMAL(15,2) NOT NULL,
    `jumlah_kembalian` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tanggal_bayar` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('berhasil', 'gagal', 'menunggu') NOT NULL DEFAULT 'berhasil',
    INDEX `idx_bayar_penjualan` (`id_penjualan`),
    INDEX `idx_bayar_metode` (`id_metode`),
    CONSTRAINT `fk_bayar_penjualan` FOREIGN KEY (`id_penjualan`) 
        REFERENCES `penjualan` (`id_penjualan`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_bayar_metode` FOREIGN KEY (`id_metode`) 
        REFERENCES `metode_pembayaran` (`id_metode`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- SAMPLE DATA (DATA PENGUJIAN LENGKAP UNTUK KE-25 TABEL)
-- ==============================================================================

-- 1. Cabang
INSERT INTO `cabang` (`id_cabang`, `kode_cabang`, `nama_cabang`, `alamat`, `telepon`, `email`, `status`) VALUES
(1, 'CBG-001', 'Supermarket Sentral Thamrin', 'Jl. M.H. Thamrin No. 10, Jakarta Pusat', '021-3901122', 'sentral@supermarket.id', 'aktif'),
(2, 'CBG-002', 'Supermarket Sudirman Express', 'Jl. Jend. Sudirman Kav. 25, Jakarta Selatan', '021-5203344', 'sudirman@supermarket.id', 'aktif'),
(3, 'CBG-003', 'Supermarket Bandung Juanda', 'Jl. Ir. H. Juanda No. 88, Dago, Bandung', '022-2505566', 'bandung@supermarket.id', 'aktif');

-- 2. Departemen
INSERT INTO `departemen` (`id_departemen`, `kode_departemen`, `nama_departemen`, `deskripsi`, `status`) VALUES
(1, 'DEP-01', 'Makanan & Minuman', 'Produk pangan kemasan, sembako, dan minuman segar', 'aktif'),
(2, 'DEP-02', 'Perawatan & Kecantikan', 'Produk mandi, sabun, sampo, dan kosmetik higienis', 'aktif'),
(3, 'DEP-03', 'Kebutuhan Rumah Tangga', 'Deterjen, pembersih lantai, tisu, dan peralatan dapur', 'aktif'),
(4, 'DEP-04', 'Produk Segar', 'Buah-buahan lokal, sayuran, dan daging segar', 'aktif');

-- 3. Kategori
INSERT INTO `kategori` (`id_kategori`, `id_departemen`, `kode_kategori`, `nama_kategori`, `deskripsi`, `status`) VALUES
(1, 1, 'KAT-01', 'Makanan Instan & Sembako', 'Mi instan, beras, minyak goreng, dan tepung', 'aktif'),
(2, 1, 'KAT-02', 'Minuman Ringan & Susu', 'Air mineral, susu UHT, jus, teh, dan kopi', 'aktif'),
(3, 2, 'KAT-03', 'Perawatan Tubuh & Rambut', 'Sabun mandi cair/batang, sampo, dan kondisioner', 'aktif'),
(4, 3, 'KAT-04', 'Pembersih & Perawatan Rumah', 'Deterjen bubuk/cair, pelembut pakaian, dan karbol', 'aktif');

-- 4. Subkategori
INSERT INTO `subkategori` (`id_subkategori`, `id_kategori`, `kode_subkategori`, `nama_subkategori`, `deskripsi`, `status`) VALUES
(1, 1, 'SUB-01', 'Mi Instan', 'Mi kuah, mi goreng, dan bihun instan', 'aktif'),
(2, 1, 'SUB-02', 'Minyak Goreng & Mentega', 'Minyak kelapa sawit kemasan pouch dan botol', 'aktif'),
(3, 2, 'SUB-03', 'Susu UHT & Steril', 'Susu cair rasa cokelat, vanila, dan plain', 'aktif'),
(4, 2, 'SUB-04', 'Air Mineral', 'Air mineral kemasan botol dan galon', 'aktif'),
(5, 3, 'SUB-05', 'Sabun Mandi', 'Sabun antibakteri dan kecantikan', 'aktif'),
(6, 4, 'SUB-06', 'Deterjen Pakaian', 'Deterjen bubuk konsentrat dan deterjen cair', 'aktif');

-- 5. Merek Produk
INSERT INTO `merek_produk` (`id_merek`, `kode_merek`, `nama_merek`, `produsen`, `status`) VALUES
(1, 'MRK-01', 'Indomie', 'PT Indofood CBP Sukses Makmur Tbk', 'aktif'),
(2, 'MRK-02', 'Ultra Milk', 'PT Ultrajaya Milk Industry Tbk', 'aktif'),
(3, 'MRK-03', 'Bimoli', 'PT Salim Ivomas Pratama Tbk', 'aktif'),
(4, 'MRK-04', 'Aqua', 'PT Danone Aqua Indonesia', 'aktif'),
(5, 'MRK-05', 'Lifebuoy', 'PT Unilever Indonesia Tbk', 'aktif'),
(6, 'MRK-06', 'Rinso', 'PT Unilever Indonesia Tbk', 'aktif');

-- 6. Satuan Produk
INSERT INTO `satuan_produk` (`id_satuan`, `kode_satuan`, `nama_satuan`, `simbol`, `status`) VALUES
(1, 'SAT-01', 'Pieces / Buah', 'Pcs', 'aktif'),
(2, 'SAT-02', 'Botol', 'Btl', 'aktif'),
(3, 'SAT-03', 'Kotak / Box', 'Box', 'aktif'),
(4, 'SAT-04', 'Pouch / Kantong', 'Pouch', 'aktif'),
(5, 'SAT-05', 'Kartor / Dus', 'Karton', 'aktif'),
(6, 'SAT-06', 'Kilogram', 'Kg', 'aktif');

-- 7. Produk
INSERT INTO `produk` (`id_produk`, `kode_produk`, `nama_produk`, `id_subkategori`, `id_merek`, `id_satuan`, `harga_beli`, `harga_jual`, `stok_minimum`, `deskripsi`, `status`) VALUES
(1, 'PRD-0001', 'Indomie Goreng Spesial 85g', 1, 1, 1, 2700.00, 3500.00, 50, 'Mi instan goreng legendaris dengan bawang goreng renyah', 'aktif'),
(2, 'PRD-0002', 'Indomie Kuah Kari Ayam 72g', 1, 1, 1, 2600.00, 3400.00, 40, 'Mi kuah rasa kari ayam yang gurih dan beraroma rempah', 'aktif'),
(3, 'PRD-0003', 'Minyak Goreng Bimoli Spesial Pouch 2L', 2, 3, 4, 34000.00, 39500.00, 20, 'Minyak goreng kelapa sawit jernih murni higienis', 'aktif'),
(4, 'PRD-0004', 'Ultra Milk Cokelat 1000ml', 3, 2, 3, 17500.00, 21500.00, 15, 'Susu UHT segar sumber kalsium dan vitamin D', 'aktif'),
(5, 'PRD-0005', 'Aqua Air Mineral Botol 600ml', 4, 4, 2, 2800.00, 4000.00, 60, 'Air minum dalam kemasan murni dari mata air pegunungan', 'aktif'),
(6, 'PRD-0006', 'Lifebuoy Sabun Cair Total 10 Refill 450ml', 5, 5, 4, 21000.00, 26500.00, 25, 'Sabun cair pelindung dari kuman dan bakteri 10 kali lebih kuat', 'aktif'),
(7, 'PRD-0007', 'Rinso Molto Deterjen Cair Japanese Peach 750ml', 6, 6, 4, 18500.00, 23900.00, 20, 'Deterjen cair pembersih noda mendalam dengan keharuman tahan lama', 'aktif');

-- 8. Barcode Produk
INSERT INTO `barcode_produk` (`id_barcode`, `id_produk`, `kode_barcode`, `jenis_barcode`, `status`, `catatan`) VALUES
(1, 1, '8998866200224', 'EAN-13', 'utama', 'Kemasan satuan eceran'),
(2, 2, '8998866200118', 'EAN-13', 'utama', 'Kemasan satuan eceran'),
(3, 3, '8991001100221', 'EAN-13', 'utama', 'Kemasan pouch 2 liter'),
(4, 4, '8992753110234', 'EAN-13', 'utama', 'Kemasan tetra pak 1 liter'),
(5, 5, '8886008101053', 'EAN-13', 'utama', 'Kemasan botol 600 ml'),
(6, 6, '8999999551234', 'EAN-13', 'utama', 'Kemasan pouch refill'),
(7, 7, '8999999554321', 'EAN-13', 'utama', 'Kemasan pouch refill 750ml');

-- 9. Pemasok
INSERT INTO `pemasok` (`id_pemasok`, `kode_pemasok`, `nama_pemasok`, `alamat`, `telepon`, `email`, `npwp`, `status`) VALUES
(1, 'SPL-001', 'PT Indomarco Adi Prima', 'Kawasan Industri Pulogadung Blok B No. 4, Jakarta Timur', '021-4601122', 'order@indomarco.co.id', '01.234.567.8-011.000', 'aktif'),
(2, 'SPL-002', 'PT Unilever Distribusi Indonesia', 'Green Office Park 9, BSD City, Tangerang', '021-5299100', 'distribusi@unilever.co.id', '01.345.678.9-012.000', 'aktif'),
(3, 'SPL-003', 'PT Ultrajaya Distributor Logistik', 'Jl. Raya Cimareme No. 131, Padalarang, Bandung Barat', '022-6654321', 'sales@ultrajaya.co.id', '01.456.789.0-013.000', 'aktif'),
(4, 'SPL-004', 'PT Tirta Investama (Aqua)', 'Cyber 2 Tower Lt. 12, Jl. HR Rasuna Said, Jakarta', '021-2996100', 'supply@danone.co.id', '01.567.890.1-014.000', 'aktif');

-- 10. Kontak Pemasok
INSERT INTO `kontak_pemasok` (`id_kontak`, `id_pemasok`, `nama_kontak`, `jabatan`, `nomor_hp`, `email`, `status`) VALUES
(1, 1, 'Hendra Gunawan', 'Key Account Manager', '0812-9876-5431', 'hendra.g@indomarco.co.id', 'utama'),
(2, 1, 'Dewi Lestari', 'Admin Logistik', '0813-1122-3344', 'dewi.l@indomarco.co.id', 'cadangan'),
(3, 2, 'Budi Santoso', 'Area Sales Manager', '0811-2233-4455', 'budi.santoso@unilever.co.id', 'utama'),
(4, 3, 'Rina Anggraini', 'Supervisor Distribusi', '0856-7788-9900', 'rina.a@ultrajaya.co.id', 'utama'),
(5, 4, 'Andi Wijaya', 'Sales Representative', '0878-5544-3322', 'andi.w@danone.co.id', 'utama');

-- 11. Jabatan
INSERT INTO `jabatan` (`id_jabatan`, `kode_jabatan`, `nama_jabatan`, `gaji_pokok`, `deskripsi`) VALUES
(1, 'JAB-01', 'Store Manager', 12500000.00, 'Penanggung jawab operasional seluruh supermarket dan cabang'),
(2, 'JAB-02', 'Supervisor Operasional', 7500000.00, 'Pengawas divisi penjualan dan layanan pelanggan'),
(3, 'JAB-03', 'Kasir Utama', 4800000.00, 'Pelaksana transaksi kasir, pembayaran dan laporan harian'),
(4, 'JAB-04', 'Petugas Gudang & Logistik', 4600000.00, 'Pengelola stok barang masuk, persediaan, dan pergerakan rak');

-- 12. Karyawan
INSERT INTO `karyawan` (`id_karyawan`, `nik`, `nama_karyawan`, `id_jabatan`, `id_cabang`, `jenis_kelamin`, `nomor_telepon`, `email`, `alamat`, `tanggal_masuk`, `status`) VALUES
(1, 'KRY-2023-001', 'Ahmad Rizki Pratama', 1, 1, 'L', '0812-1000-2001', 'ahmad.rizki@supermarket.id', 'Jl. Kebon Sirih No. 12, Menteng, Jakarta', '2023-01-15', 'aktif'),
(2, 'KRY-2023-002', 'Siti Nurhaliza', 2, 1, 'P', '0812-1000-2002', 'siti.nur@supermarket.id', 'Jl. Cempaka Putih Timur No. 45, Jakarta', '2023-03-01', 'aktif'),
(3, 'KRY-2023-003', 'Bambang Kusuma', 3, 1, 'L', '0812-1000-2003', 'bambang.kasir@supermarket.id', 'Jl. Tebet Barat Dalam No. 17, Jakarta Selatan', '2023-05-10', 'aktif'),
(4, 'KRY-2023-004', 'Dedi Kurniawan', 4, 1, 'L', '0812-1000-2004', 'dedi.gudang@supermarket.id', 'Jl. Utan Kayu Raya No. 8, Jakarta Timur', '2023-06-20', 'aktif');

-- 13. Akun Karyawan
-- Password untuk semua akun default: admin123 (hash password bcrypt PHP)
-- Hash '$2y$10$wWk1f4Q9f8r2YVjS1Q9m.eN2rXbB7iYyU0h/Z8m9C5hB7O/H4a8bW'
-- Kami generate hash password standar kompatibel password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `akun_karyawan` (`id_akun`, `id_karyawan`, `username`, `password`, `peran`, `status`, `created_at`) VALUES
(1, 1, 'admin', '$2y$10$cOCK6SgKehiZb9myX59VauUEcaqwTW6urs6pYSeR2ThumCLpCiAAK', 'admin', 'aktif', NOW()),
(2, 2, 'manajer', '$2y$10$cOCK6SgKehiZb9myX59VauUEcaqwTW6urs6pYSeR2ThumCLpCiAAK', 'manajer', 'aktif', NOW()),
(3, 3, 'kasir', '$2y$10$cOCK6SgKehiZb9myX59VauUEcaqwTW6urs6pYSeR2ThumCLpCiAAK', 'kasir', 'aktif', NOW()),
(4, 4, 'gudang', '$2y$10$cOCK6SgKehiZb9myX59VauUEcaqwTW6urs6pYSeR2ThumCLpCiAAK', 'gudang', 'aktif', NOW());

-- 14. Gudang
INSERT INTO `gudang` (`id_gudang`, `id_cabang`, `kode_gudang`, `nama_gudang`, `alamat`, `kapasitas_m3`, `status`) VALUES
(1, 1, 'GDG-001', 'Gudang Utama Thamrin', 'Basement 1, Gedung Supermarket Thamrin, Jakarta Pusat', 500, 'aktif'),
(2, 1, 'GDG-002', 'Gudang Suhu Dingin (Cold Storage)', 'Area Sayur & Susu, Thamrin, Jakarta Pusat', 150, 'aktif'),
(3, 2, 'GDG-003', 'Gudang Sudirman Sentral', 'Ruko Sentra Sudirman Blok C No. 5, Jakarta Selatan', 350, 'aktif');

-- 15. Lokasi Gudang
INSERT INTO `lokasi_gudang` (`id_lokasi`, `id_gudang`, `kode_lokasi`, `nama_lokasi`, `keterangan`) VALUES
(1, 1, 'RAK-A1-01', 'Rak Makanan Instan Lantai 1', 'Khusus mi instan dan pasta kemasan'),
(2, 1, 'RAK-A2-01', 'Rak Minyak & Bumbu Dapur', 'Khusus minyak goreng jeriken dan pouch'),
(3, 2, 'CHILL-B1', 'Chiller Pendingin Susu', 'Suhu 2 - 4 derajat Celsius'),
(4, 1, 'RAK-C1-02', 'Rak Deterjen & Pembersih', 'Khusus sabun dan deterjen pakaian');

-- 16. Persediaan
INSERT INTO `persediaan` (`id_persediaan`, `id_produk`, `id_gudang`, `id_lokasi`, `jumlah_stok`, `stok_minimum`, `terakhir_diperbarui`) VALUES
(1, 1, 1, 1, 250, 50, NOW()),
(2, 2, 1, 1, 180, 40, NOW()),
(3, 3, 1, 2, 85, 20, NOW()),
(4, 4, 2, 3, 40, 15, NOW()),
(5, 5, 1, 1, 320, 60, NOW()),
(6, 6, 1, 4, 90, 25, NOW()),
(7, 7, 1, 4, 65, 20, NOW());

-- 17. Pergerakan Stok
INSERT INTO `pergerakan_stok` (`id_pergerakan`, `id_produk`, `id_gudang`, `id_karyawan`, `jenis_pergerakan`, `jumlah`, `stok_sebelum`, `stok_sesudah`, `referensi`, `keterangan`, `tanggal`) VALUES
(1, 1, 1, 4, 'masuk', 250, 0, 250, 'PO-2026-0001', 'Penerimaan barang dari PT Indomarco Adi Prima', NOW()),
(2, 2, 1, 4, 'masuk', 180, 0, 180, 'PO-2026-0001', 'Penerimaan barang dari PT Indomarco Adi Prima', NOW()),
(3, 3, 1, 4, 'masuk', 85, 0, 85, 'PO-2026-0001', 'Penerimaan pasokan minyak goreng pouch', NOW()),
(4, 4, 2, 4, 'masuk', 40, 0, 40, 'PO-2026-0002', 'Penerimaan pasokan Ultra Milk ke chiller', NOW());

-- 18. Pesanan Pembelian
INSERT INTO `pesanan_pembelian` (`id_pesanan`, `nomor_pesanan`, `id_pemasok`, `id_karyawan`, `id_gudang`, `tanggal_pesanan`, `tanggal_estimasi`, `total_nominal`, `status`, `catatan`, `created_at`) VALUES
(1, 'PO-2026-0001', 1, 4, 1, '2026-11-01', '2026-11-03', 4033000.00, 'diterima', 'Pesanan rutin bulanan sembako dan mi instan', NOW()),
(2, 'PO-2026-0002', 3, 4, 2, '2026-11-05', '2026-11-07', 700000.00, 'diterima', 'Pesanan susu UHT segar', NOW()),
(3, 'PO-2026-0003', 2, 4, 1, '2026-11-10', '2026-11-15', 2500000.00, 'dipesan', 'Pesanan restok sabun dan deterjen', NOW());

-- 19. Detail Pesanan Pembelian
INSERT INTO `detail_pesanan_pembelian` (`id_detail_pesanan`, `id_pesanan`, `id_produk`, `jumlah`, `harga_satuan`, `subtotal`, `catatan`) VALUES
(1, 1, 1, 250, 2700.00, 675000.00, 'Dus isi 40 pcs x 6 dus + 10'),
(2, 1, 2, 180, 2600.00, 468000.00, 'Varian kari ayam'),
(3, 1, 3, 85, 34000.00, 2890000.00, 'Minyak pouch 2L'),
(4, 2, 4, 40, 17500.00, 700000.00, 'Karton 1000ml isi 12 x 3 + 4'),
(5, 3, 6, 60, 21000.00, 1260000.00, 'Lifebuoy refill 450ml'),
(6, 3, 7, 50, 18500.00, 925000.00, 'Rinso cair peach');

-- 20. Pelanggan
INSERT INTO `pelanggan` (`id_pelanggan`, `kode_pelanggan`, `nama_lengkap`, `nomor_telepon`, `email`, `poin_loyalitas`, `status`, `tanggal_daftar`) VALUES
(1, 'PLG-001', 'Hendra Setiawan', '0812-8899-7766', 'hendra.setiawan@gmail.com', 120, 'aktif', '2025-02-10'),
(2, 'PLG-002', 'Dewi Sandra Kusuma', '0813-7766-5544', 'dewi.sandra@yahoo.com', 250, 'aktif', '2025-04-18'),
(3, 'PLG-003', 'Eko Prasetyo', '0857-4433-2211', 'eko.prasetyo@gmail.com', 45, 'aktif', '2025-08-22'),
(4, 'PLG-004', 'Maria Fransisca', '0878-1122-9988', 'maria.fransisca@outlook.com', 310, 'aktif', '2025-10-05');

-- 21. Alamat Pelanggan
INSERT INTO `alamat_pelanggan` (`id_alamat`, `id_pelanggan`, `label_alamat`, `alamat_lengkap`, `kota`, `provinsi`, `kode_pos`, `status_utama`) VALUES
(1, 1, 'Rumah', 'Jl. Gandaria I No. 14, Kebayoran Baru', 'Jakarta Selatan', 'DKI Jakarta', '12130', 'ya'),
(2, 1, 'Kantor', 'Gedung Menara Astra Lt. 18, Jl. Sudirman', 'Jakarta Pusat', 'DKI Jakarta', '10220', 'tidak'),
(3, 2, 'Rumah Utama', 'Jl. Cisangkuy No. 32, Citarum', 'Bandung', 'Jawa Barat', '40115', 'ya'),
(4, 3, 'Apartemen', 'Green Pramuka City Tower Chrysant No. 12A', 'Jakarta Pusat', 'DKI Jakarta', '10570', 'ya'),
(5, 4, 'Rumah', 'Jl. Pluit Kencana No. 55', 'Jakarta Utara', 'DKI Jakarta', '14450', 'ya');

-- 22. Metode Pembayaran
INSERT INTO `metode_pembayaran` (`id_metode`, `kode_metode`, `nama_metode`, `kategori`, `status`) VALUES
(1, 'MET-01', 'Uang Tunai (Cash)', 'tunai', 'aktif'),
(2, 'MET-02', 'QRIS (Gopay / OVO / Dana)', 'dompet_digital', 'aktif'),
(3, 'MET-03', 'Kartu Debit BCA', 'kartu', 'aktif'),
(4, 'MET-04', 'Kartu Kredit Mandiri', 'kartu', 'aktif'),
(5, 'MET-05', 'Transfer Bank Virtual Account', 'transfer', 'aktif');

-- 23. Penjualan
INSERT INTO `penjualan` (`id_penjualan`, `nomor_faktur`, `id_cabang`, `id_pelanggan`, `id_karyawan`, `tanggal_penjualan`, `total_harga`, `diskon`, `pajak`, `total_akhir`, `status_pembayaran`, `catatan`) VALUES
(1, 'INV-202611-0001', 1, 1, 3, '2026-11-15 10:15:00', 82500.00, 2500.00, 0.00, 80000.00, 'lunas', 'Transaksi belanja pagi member'),
(2, 'INV-202611-0002', 1, 2, 3, '2026-11-15 14:30:00', 47400.00, 0.00, 0.00, 47400.00, 'lunas', 'Transaksi belanja perlengkapan mandi'),
(3, 'INV-202611-0003', 1, NULL, 3, '2026-11-16 09:00:00', 21500.00, 0.00, 0.00, 21500.00, 'lunas', 'Transaksi pembeli umum / non-member');

-- 24. Detail Penjualan
INSERT INTO `detail_penjualan` (`id_detail_penjualan`, `id_penjualan`, `id_produk`, `harga_satuan`, `jumlah`, `subtotal`, `diskon_item`) VALUES
(1, 1, 1, 3500.00, 5, 17500.00, 0.00),
(2, 1, 3, 39500.00, 1, 39500.00, 2500.00),
(3, 1, 4, 21500.00, 1, 21500.00, 0.00),
(4, 1, 5, 4000.00, 1, 4000.00, 0.00),
(5, 2, 6, 26500.00, 1, 26500.00, 0.00),
(6, 2, 7, 23900.00, 1, 23900.00, 3000.00),
(7, 3, 4, 21500.00, 1, 21500.00, 0.00);

-- 25. Pembayaran Penjualan
INSERT INTO `pembayaran_penjualan` (`id_pembayaran`, `id_penjualan`, `id_metode`, `nomor_transaksi`, `jumlah_bayar`, `jumlah_kembalian`, `tanggal_bayar`, `status`) VALUES
(1, 1, 1, 'TRX-CASH-001', 100000.00, 20000.00, '2026-11-15 10:16:00', 'berhasil'),
(2, 2, 2, 'QRIS-GOPAY-887123', 47400.00, 0.00, '2026-11-15 14:31:00', 'berhasil'),
(3, 3, 3, 'DEBIT-BCA-998811', 21500.00, 0.00, '2026-11-16 09:01:00', 'berhasil');
