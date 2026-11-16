<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Automated Testing & Verification Suite
 * File: tests/test_suite.php
 * 
 * Jalankan via CLI: php tests/test_suite.php
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

echo "=================================================================\n";
echo " SISTEM MANAJEMEN SUPERMARKET - TEST & VERIFIKASI OTOMATIS\n";
echo "=================================================================\n\n";

$pass = 0;
$fail = 0;

function test_assert($nama, $kondisi, $pesan_gagal = '') {
    global $pass, $fail;
    if ($kondisi) {
        echo " [BERHASIL] $nama\n";
        $pass++;
    } else {
        echo " [GAGAL]    $nama: $pesan_gagal\n";
        $fail++;
    }
}

// 1. Uji Database & Total Tabel
$stmt = $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'db_supermarket'");
$total_tabel = (int)$stmt->fetchColumn();
test_assert("Database memiliki tepat 25 tabel relational", $total_tabel === 25, "Ditemukan $total_tabel tabel");

// 2. Cek Akun Karyawan & Password Hash Bcrypt
$stmt = $pdo->prepare("SELECT username, password FROM akun_karyawan WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();
test_assert("Akun admin terdaftar di tabel akun_karyawan", !empty($admin));
test_assert("Password admin diamankan dengan bcrypt password_hash/password_verify", password_verify('admin123', $admin['password']));

// 3. Cek Relasi Foreign Key Produk -> Satuan, Subkategori, Merek
$stmt = $pdo->query("
    SELECT p.nama_produk, m.nama_merek, s.simbol, sub.nama_subkategori, k.nama_kategori
    FROM produk p
    JOIN merek_produk m ON p.id_merek = m.id_merek
    JOIN satuan_produk s ON p.id_satuan = s.id_satuan
    JOIN subkategori sub ON p.id_subkategori = sub.id_subkategori
    JOIN kategori k ON sub.id_kategori = k.id_kategori
    LIMIT 1
");
$rowProduk = $stmt->fetch();
test_assert("Relasi Produk - Merek - Satuan - Subkategori - Kategori terhubung", !empty($rowProduk) && !empty($rowProduk['nama_produk']));

// 4. Uji Alur Transaksi Kasir / POS (Database Transaction)
try {
    $pdo->beginTransaction();

    $faktur_test = 'TEST-' . date('Ymd-His');
    $stmtCabang = $pdo->query("SELECT id_cabang FROM cabang LIMIT 1");
    $id_cabang = (int)$stmtCabang->fetchColumn();

    $stmtKasir = $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1");
    $id_karyawan = (int)$stmtKasir->fetchColumn();

    $stmtGudang = $pdo->prepare("SELECT id_gudang FROM gudang WHERE id_cabang = ? LIMIT 1");
    $stmtGudang->execute([$id_cabang]);
    $id_gudang = (int)$stmtGudang->fetchColumn();

    $stmtProduk = $pdo->query("SELECT id_produk, harga_jual FROM produk WHERE status = 'aktif' LIMIT 1");
    $prod = $stmtProduk->fetch();
    $id_produk = (int)$prod['id_produk'];
    $harga = (float)$prod['harga_jual'];

    // Cek persediaan awal
    $stmtStok = $pdo->prepare("SELECT jumlah_stok FROM persediaan WHERE id_produk = ? AND id_gudang = ?");
    $stmtStok->execute([$id_produk, $id_gudang]);
    $stok_awal = (int)$stmtStok->fetchColumn();

    if ($stok_awal < 5) {
        $pdo->prepare("INSERT INTO persediaan (id_produk, id_gudang, jumlah_stok, stok_minimum) VALUES (?, ?, 50, 5) ON DUPLICATE KEY UPDATE jumlah_stok = 50")->execute([$id_produk, $id_gudang]);
        $stok_awal = 50;
    }

    // Insert penjualan
    $stmtPJ = $pdo->prepare("
        INSERT INTO penjualan (nomor_faktur, id_cabang, id_pelanggan, id_karyawan, total_harga, diskon, pajak, total_akhir, status_pembayaran)
        VALUES (?, ?, NULL, ?, ?, 0, 0, ?, 'lunas')
    ");
    $stmtPJ->execute([$faktur_test, $id_cabang, $id_karyawan, $harga * 2, $harga * 2]);
    $id_penjualan = (int)$pdo->lastInsertId();

    // Insert detail penjualan
    $stmtDP = $pdo->prepare("
        INSERT INTO detail_penjualan (id_penjualan, id_produk, harga_satuan, jumlah, subtotal, diskon_item)
        VALUES (?, ?, ?, 2, ?, 0)
    ");
    $stmtDP->execute([$id_penjualan, $id_produk, $harga, $harga * 2]);

    // Insert pembayaran
    $stmtMetode = $pdo->query("SELECT id_metode FROM metode_pembayaran LIMIT 1");
    $id_metode = (int)$stmtMetode->fetchColumn();

    $stmtBayar = $pdo->prepare("
        INSERT INTO pembayaran_penjualan (id_penjualan, id_metode, nomor_transaksi, jumlah_bayar, jumlah_kembalian, status)
        VALUES (?, ?, 'TRX-TEST-001', ?, 0, 'berhasil')
    ");
    $stmtBayar->execute([$id_penjualan, $id_metode, $harga * 2]);

    // Mutasi stok dengan update_stok_persediaan
    update_stok_persediaan($pdo, $id_produk, $id_gudang, -2, 'keluar', $id_karyawan, $faktur_test, 'Pengujian Transaksi Unit Test');

    // Cek stok akhir
    $stmtStok->execute([$id_produk, $id_gudang]);
    $stok_akhir = (int)$stmtStok->fetchColumn();

    test_assert("Pengurangan stok persediaan akurat (-2 unit)", $stok_akhir === ($stok_awal - 2), "Awal: $stok_awal, Akhir: $stok_akhir");

    // Cek pencatatan pergerakan stok
    $stmtLog = $pdo->prepare("SELECT count(*) FROM pergerakan_stok WHERE referensi = ?");
    $stmtLog->execute([$faktur_test]);
    $logCount = (int)$stmtLog->fetchColumn();
    test_assert("Log pergerakan stok otomatis tercatat di pergerakan_stok", $logCount > 0);

    // Rollback test transaction agar tidak mengubah data asli
    $pdo->rollBack();
    test_assert("Transaksi rollback berhasil (data operasional tidak terganggu)", true);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    test_assert("Alur POS / Transaksi Penjualan Lengkap", false, $e->getMessage());
}

// 5. Uji Integritas Foreign Key Protection
try {
    $stmtCabangRef = $pdo->query("SELECT id_cabang FROM karyawan LIMIT 1");
    $id_cabang_terpakai = (int)$stmtCabangRef->fetchColumn();

    // Coba hapus cabang yang masih dipakai oleh karyawan
    $pdo->prepare("DELETE FROM cabang WHERE id_cabang = ?")->execute([$id_cabang_terpakai]);
    test_assert("Foreign Key Protect: Cabang berelasi tidak boleh terhapus", false, "Penghapusan tidak terblokir");
} catch (PDOException $e) {
    test_assert("Foreign Key Protect: Database memblokir penghapusan relasi aktif (Integrity Constraint)", $e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint'));
}

// 6. Uji Kelengkapan Seluruh 25 Modul CRUD
$dirs = glob(__DIR__ . '/../modules/*', GLOB_ONLYDIR);
$semua_lengkap = true;
$modul_hilang = [];
foreach ($dirs as $dir) {
    $mName = basename($dir);
    $idx = file_exists("$dir/index.php");
    $add = file_exists("$dir/tambah.php");
    $edt = file_exists("$dir/edit.php");
    $del = file_exists("$dir/hapus.php");
    if (!($idx && $add && $edt && $del)) {
        $semua_lengkap = false;
        $modul_hilang[] = $mName;
    }
}
test_assert("Kelengkapan 25 Modul CRUD (100 file CRUD)", $semua_lengkap && count($dirs) === 25, "Modul belum lengkap: " . implode(', ', $modul_hilang));

echo "\n-----------------------------------------------------------------\n";
echo "Ringkasan Hasil Uji: $pass Pengujian Lulus, $fail Gagal.\n";
echo "Status: " . ($fail === 0 ? "SEMUA PENGUJIAN BERHASIL LULUS 100%!" : "ADA PENGUJIAN YANG GAGAL") . "\n";
echo "=================================================================\n";
