<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Modul Kasir Cepat (Point of Sale / POS)
 * File: pos.php
 */

$judul_halaman = 'Kasir Cepat (Point of Sale)';
$menu_aktif = 'pos';

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

cek_login();
$user_aktif = user_login();

// Ambil data pelanggan aktif
$stmt = $pdo->query("SELECT id_pelanggan, kode_pelanggan, nama_lengkap, nomor_telepon FROM pelanggan WHERE status = 'aktif' ORDER BY nama_lengkap ASC");
$daftar_pelanggan = $stmt->fetchAll();

// Ambil metode pembayaran aktif
$stmt = $pdo->query("SELECT id_metode, nama_metode, kategori FROM metode_pembayaran WHERE status = 'aktif' ORDER BY id_metode ASC");
$daftar_metode = $stmt->fetchAll();

// Ambil daftar gudang utama untuk pemotongan stok kasir
$stmt = $pdo->prepare("SELECT id_gudang, nama_gudang FROM gudang WHERE id_cabang = ? AND status = 'aktif' ORDER BY id_gudang ASC LIMIT 1");
$stmt->execute([$user_aktif['id_cabang']]);
$gudang_utama = $stmt->fetch();
$id_gudang_kasir = $gudang_utama ? $gudang_utama['id_gudang'] : 1;

// Ambil daftar seluruh produk aktif beserta barcode & stok di gudang kasir
$queryProduk = "
    SELECT 
        p.id_produk,
        p.kode_produk,
        p.nama_produk,
        p.harga_jual,
        s.simbol AS satuan,
        COALESCE(b.kode_barcode, '') AS kode_barcode,
        COALESCE(ps.jumlah_stok, 0) AS jumlah_stok
    FROM produk p
    INNER JOIN satuan_produk s ON p.id_satuan = s.id_satuan
    LEFT JOIN barcode_produk b ON p.id_produk = b.id_produk AND b.status = 'utama'
    LEFT JOIN persediaan ps ON p.id_produk = ps.id_produk AND ps.id_gudang = ?
    WHERE p.status = 'aktif'
    ORDER BY p.nama_produk ASC
";
$stmt = $pdo->prepare($queryProduk);
$stmt->execute([$id_gudang_kasir]);
$katalog_produk = $stmt->fetchAll();

// PROSES SUBMIT TRANSAKSI PENJUALAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_transaksi'])) {
    csrf_verify();

    $id_pelanggan = !empty($_POST['id_pelanggan']) ? (int)$POST['id_pelanggan'] : null;
    $id_metode = (int)($_POST['id_metode'] ?? 1);
    $items = json_decode($_POST['keranjang_json'] ?? '[]', true);
    $nominal_bayar = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_bayar'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($items)) {
        flash_set('danger', 'Keranjang belanja kosong! Silakan pilih produk terlebih dahulu.');
        header('Location: ' . base_url('pos.php'));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Hitung total belanja
        $total_kotor = 0;
        $detail_items = [];

        foreach ($items as $item) {
            $id_p = (int)$item['id_produk'];
            $qty = (int)$item['qty'];
            if ($qty <= 0) continue;

            // Ambil harga dan stok terkini
            $stmtCek = $pdo->prepare("
                SELECT p.id_produk, p.nama_produk, p.harga_jual, COALESCE(ps.jumlah_stok, 0) AS stok_saat_ini
                FROM produk p
                LEFT JOIN persediaan ps ON p.id_produk = ps.id_produk AND ps.id_gudang = ?
                WHERE p.id_produk = ?
                FOR UPDATE
            ");
            $stmtCek->execute([$id_gudang_kasir, $id_p]);
            $prd = $stmtCek->fetch();

            if (!$prd) {
                throw new Exception("Produk dengan ID {$id_p} tidak ditemukan!");
            }
            if ($prd['stok_saat_ini'] < $qty) {
                throw new Exception("Stok untuk '{$prd['nama_produk']}' tidak mencukupi! Tersedia: {$prd['stok_saat_ini']}, dibeli: {$qty}");
            }

            $subtotal = (float)$prd['harga_jual'] * $qty;
            $total_kotor += $subtotal;

            $detail_items[] = [
                'id_produk' => $id_p,
                'nama_produk' => $prd['nama_produk'],
                'harga_satuan' => (float)$prd['harga_jual'],
                'jumlah' => $qty,
                'subtotal' => $subtotal
            ];
        }

        if (empty($detail_items)) {
            throw new Exception("Tidak ada barang valid dalam keranjang.");
        }

        $diskon = 0.00;
        $pajak = 0.00;
        $total_akhir = $total_kotor - $diskon + $pajak;

        if ($nominal_bayar < $total_akhir) {
            throw new Exception("Nominal pembayaran kurang! Total belanja: " . rupiah($total_akhir) . ", dibayar: " . rupiah($nominal_bayar));
        }

        $kembalian = $nominal_bayar - $total_akhir;

        // 2. Generate Nomor Faktur Unik
        $nomor_faktur = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // 3. Simpan ke tabel penjualan (header)
        $stmtPenjualan = $pdo->prepare("
            INSERT INTO penjualan 
            (nomor_faktur, id_cabang, id_pelanggan, id_karyawan, tanggal_penjualan, total_harga, diskon, pajak, total_akhir, status_pembayaran, catatan)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'lunas', ?)
        ");
        $stmtPenjualan->execute([
            $nomor_faktur,
            $user_aktif['id_cabang'],
            $id_pelanggan,
            $user_aktif['id_karyawan'],
            $total_kotor,
            $diskon,
            $pajak,
            $total_akhir,
            $catatan
        ]);
        $id_penjualan = (int)$pdo->lastInsertId();

        // 4. Simpan ke tabel detail_penjualan & potong persediaan & catat pergerakan_stok
        $stmtDetail = $pdo->prepare("
            INSERT INTO detail_penjualan 
            (id_penjualan, id_produk, harga_satuan, jumlah, subtotal, diskon_item)
            VALUES (?, ?, ?, ?, ?, 0.00)
        ");

        foreach ($detail_items as $d) {
            $stmtDetail->execute([
                $id_penjualan,
                $d['id_produk'],
                $d['harga_satuan'],
                $d['jumlah'],
                $d['subtotal']
            ]);

            // Potong stok dan catat mutasi keluar
            update_stok_persediaan(
                $pdo,
                $d['id_produk'],
                $id_gudang_kasir,
                $d['jumlah'],
                'keluar',
                $user_aktif['id_karyawan'],
                $nomor_faktur,
                "Penjualan POS Faktur {$nomor_faktur} ({$d['nama_produk']} x {$d['jumlah']})"
            );
        }

        // 5. Simpan ke tabel pembayaran_penjualan
        $nomor_trx_bayar = 'PAY-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmtBayar = $pdo->prepare("
            INSERT INTO pembayaran_penjualan 
            (id_penjualan, id_metode, nomor_transaksi, jumlah_bayar, jumlah_kembalian, tanggal_bayar, status)
            VALUES (?, ?, ?, ?, ?, NOW(), 'berhasil')
        ");
        $stmtBayar->execute([
            $id_penjualan,
            $id_metode,
            $nomor_trx_bayar,
            $nominal_bayar,
            $kembalian
        ]);

        // 6. Tambah poin loyalitas pelanggan jika member
        if ($id_pelanggan) {
            $tambah_poin = (int)floor($total_akhir / 10000); // 1 poin per kelipatan 10rb
            if ($tambah_poin > 0) {
                $stmtPoin = $pdo->prepare("UPDATE pelanggan SET poin_loyalitas = poin_loyalitas + ? WHERE id_pelanggan = ?");
                $stmtPoin->execute([$tambah_poin, $id_pelanggan]);
            }
        }

        $pdo->commit();

        flash_set('success', "Transaksi berhasil disimpan! No. Faktur: <strong>{$nomor_faktur}</strong>. Kembalian: <strong>" . rupiah($kembalian) . "</strong>");
        header('Location: ' . base_url('cetak_faktur.php?id=' . $id_penjualan));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash_set('danger', 'Gagal memproses transaksi: ' . $e->getMessage());
        header('Location: ' . base_url('pos.php'));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-3">
    <!-- Kolom Kiri: Katalog Produk & Pencarian -->
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <span class="fw-bold"><i class="bi bi-grid-fill text-primary me-2"></i>Pilih Produk Belanja</span>
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" id="pencarianProduk" class="form-control" placeholder="Cari nama atau barcode..." autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="card-body p-3" style="max-height: 580px; overflow-y: auto;">
                <div class="row g-2" id="daftarProdukGrid">
                    <?php foreach ($katalog_produk as $prd): ?>
                        <div class="col-sm-6 col-md-4 item-produk-card" 
                             data-id="<?= $prd['id_produk'] ?>" 
                             data-kode="<?= strtolower(escape($prd['kode_produk'])) ?>"
                             data-nama="<?= strtolower(escape($prd['nama_produk'])) ?>"
                             data-barcode="<?= strtolower(escape($prd['kode_barcode'])) ?>"
                             data-harga="<?= (float)$prd['harga_jual'] ?>"
                             data-stok="<?= (int)$prd['jumlah_stok'] ?>">
                            <div class="card h-100 border p-2 text-start position-relative <?= ($prd['jumlah_stok'] <= 0) ? 'bg-light text-muted opacity-75' : 'hover-shadow cursor-pointer' ?>"
                                 onclick="tambahKeKeranjang(<?= $prd['id_produk'] ?>, '<?= addslashes(escape($prd['nama_produk'])) ?>', <?= (float)$prd['harga_jual'] ?>, <?= (int)$prd['jumlah_stok'] ?>)">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="badge bg-light text-dark border" style="font-size: 0.68rem;"><?= escape($prd['kode_produk']) ?></span>
                                    <span class="badge <?= ($prd['jumlah_stok'] > 5) ? 'bg-success' : (($prd['jumlah_stok'] > 0) ? 'bg-warning text-dark' : 'bg-danger') ?>" style="font-size: 0.68rem;">
                                        Stok: <?= $prd['jumlah_stok'] ?>
                                    </span>
                                </div>
                                <div class="fw-semibold text-truncate small mb-1" title="<?= escape($prd['nama_produk']) ?>">
                                    <?= escape($prd['nama_produk']) ?>
                                </div>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <span class="text-primary fw-bold" style="font-size: 0.88rem;"><?= rupiah($prd['harga_jual']) ?></span>
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" <?= ($prd['jumlah_stok'] <= 0) ? 'disabled' : '' ?>>
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Keranjang Kasir & Pembayaran -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100 d-flex flex-column">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-cart3 text-primary me-2"></i>Keranjang Kasir</span>
                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="kosongkanKeranjang()">
                    <i class="bi bi-trash"></i> Kosongkan
                </button>
            </div>

            <!-- Tabel Daftar Belanja -->
            <div class="card-body p-0 flex-grow-1" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm table-striped align-middle mb-0" id="tabelKeranjang">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-center" style="width: 100px;">Qty</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-center" style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="isiKeranjang">
                        <tr id="barisKosong">
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-basket2 fs-3 d-block mb-1"></i>
                                Keranjang belanja masih kosong.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Formulir Pembayaran -->
            <div class="card-footer bg-light p-3 border-top">
                <form action="" method="POST" id="formKasir">
                    <?= csrf_input() ?>
                    <input type="hidden" name="proses_transaksi" value="1">
                    <input type="hidden" name="keranjang_json" id="keranjangJson" value="[]">

                    <!-- Pilihan Pelanggan -->
                    <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Pelanggan</label>
                            <select name="id_pelanggan" class="form-select form-select-sm">
                                <option value="">Pelanggan Umum (Non-Member)</option>
                                <?php foreach ($daftar_pelanggan as $plg): ?>
                                    <option value="<?= $plg['id_pelanggan'] ?>">
                                        <?= escape($plg['nama_lengkap']) ?> (<?= escape($plg['nomor_telepon']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Metode Bayar</label>
                            <select name="id_metode" class="form-select form-select-sm" required>
                                <?php foreach ($daftar_metode as $mtd): ?>
                                    <option value="<?= $mtd['id_metode'] ?>">
                                        <?= escape($mtd['nama_metode']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Ringkasan Total Tagihan -->
                    <div class="bg-white p-2 rounded border mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Total Belanja:</span>
                            <span class="fs-4 fw-bold text-primary" id="tampilanTotal">Rp 0</span>
                        </div>
                    </div>

                    <!-- Input Nominal Bayar & Kembalian -->
                    <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                            <label class="form-label small mb-1 fw-bold">Uang Diterima (Rp)</label>
                            <input type="number" step="500" min="0" name="nominal_bayar" id="nominalBayar" class="form-control form-control-sm fw-bold fs-6" placeholder="0" required oninput="hitungKembalian()">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Kembalian</label>
                            <input type="text" id="tampilanKembalian" class="form-control form-control-sm bg-light fw-bold text-success fs-6" value="Rp 0" readonly>
                        </div>
                    </div>

                    <!-- Tombol Cepat Nominal Uang Pas / Pecahan -->
                    <div class="d-flex gap-1 mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="setUangPas()">Uang Pas</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="tambahNominal(20000)">+20rb</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="tambahNominal(50000)">+50rb</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="tambahNominal(100000)">+100rb</button>
                    </div>

                    <button type="submit" id="btnSimpanTransaksi" class="btn btn-success w-100 py-2 fw-bold shadow-sm" disabled>
                        <i class="bi bi-printer me-1"></i> Proses Bayar & Cetak Nota
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let keranjang = [];
let totalBelanja = 0;

function tambahKeKeranjang(id, nama, harga, stok) {
    if (stok <= 0) {
        alert("Maaf, stok produk ini habis!");
        return;
    }

    let existing = keranjang.find(item => item.id_produk === id);
    if (existing) {
        if (existing.qty + 1 > stok) {
            alert("Jumlah beli melebihi sisa stok yang tersedia (" + stok + ")!");
            return;
        }
        existing.qty += 1;
    } else {
        keranjang.push({
            id_produk: id,
            nama_produk: nama,
            harga: harga,
            qty: 1,
            stok_max: stok
        });
    }

    renderKeranjang();
}

function ubahQty(id, delta) {
    let item = keranjang.find(i => i.id_produk === id);
    if (!item) return;

    let targetQty = item.qty + delta;
    if (targetQty <= 0) {
        hapusDariKeranjang(id);
        return;
    }
    if (targetQty > item.stok_max) {
        alert("Jumlah beli melebihi stok tersedia (" + item.stok_max + ")!");
        return;
    }

    item.qty = targetQty;
    renderKeranjang();
}

function hapusDariKeranjang(id) {
    keranjang = keranjang.filter(item => item.id_produk !== id);
    renderKeranjang();
}

function kosongkanKeranjang() {
    if (keranjang.length === 0) return;
    if (confirm("Apakah Anda yakin ingin mengosongkan seluruh keranjang?")) {
        keranjang = [];
        renderKeranjang();
    }
}

function renderKeranjang() {
    const tbody = document.getElementById('isiKeranjang');
    const inputJson = document.getElementById('keranjangJson');
    const labelTotal = document.getElementById('tampilanTotal');
    const btnBayar = document.getElementById('btnSimpanTransaksi');

    if (keranjang.length === 0) {
        tbody.innerHTML = `
            <tr id="barisKosong">
                <td colspan="4" class="text-center py-4 text-muted">
                    <i class="bi bi-basket2 fs-3 d-block mb-1"></i>
                    Keranjang belanja masih kosong.
                </td>
            </tr>
        `;
        totalBelanja = 0;
        labelTotal.textContent = 'Rp 0';
        inputJson.value = '[]';
        btnBayar.disabled = true;
        hitungKembalian();
        return;
    }

    tbody.innerHTML = '';
    totalBelanja = 0;

    keranjang.forEach(item => {
        let subtotal = item.qty * item.harga;
        totalBelanja += subtotal;

        let tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="fw-semibold small text-truncate" style="max-width: 140px;">${item.nama_produk}</div>
                <small class="text-muted">@ Rp ${item.harga.toLocaleString('id-ID')}</small>
            </td>
            <td class="text-center">
                <div class="input-group input-group-sm justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="ubahQty(${item.id_produk}, -1)">-</button>
                    <span class="input-group-text py-0 px-2 fw-bold">${item.qty}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="ubahQty(${item.id_produk}, 1)">+</button>
                </div>
            </td>
            <td class="text-end fw-bold small">
                Rp ${subtotal.toLocaleString('id-ID')}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-link text-danger p-0" onclick="hapusDariKeranjang(${item.id_produk})">
                    <i class="bi bi-x-circle"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    labelTotal.textContent = 'Rp ' + totalBelanja.toLocaleString('id-ID');
    inputJson.value = JSON.stringify(keranjang);
    btnBayar.disabled = false;
    hitungKembalian();
}

function hitungKembalian() {
    let inputBayar = document.getElementById('nominalBayar');
    let labelKembalian = document.getElementById('tampilanKembalian');
    let btnBayar = document.getElementById('btnSimpanTransaksi');

    let nominal = parseFloat(inputBayar.value) || 0;
    let kembalian = nominal - totalBelanja;

    if (totalBelanja > 0 && nominal >= totalBelanja) {
        labelKembalian.value = 'Rp ' + kembalian.toLocaleString('id-ID');
        labelKembalian.classList.remove('text-danger');
        labelKembalian.classList.add('text-success');
        btnBayar.disabled = false;
    } else {
        if (nominal > 0) {
            labelKembalian.value = 'Kurang Rp ' + Math.abs(kembalian).toLocaleString('id-ID');
            labelKembalian.classList.remove('text-success');
            labelKembalian.classList.add('text-danger');
        } else {
            labelKembalian.value = 'Rp 0';
            labelKembalian.classList.remove('text-danger');
            labelKembalian.classList.add('text-success');
        }
        btnBayar.disabled = (totalBelanja === 0 || nominal < totalBelanja);
    }
}

function setUangPas() {
    document.getElementById('nominalBayar').value = totalBelanja;
    hitungKembalian();
}

function tambahNominal(nominal) {
    let current = parseFloat(document.getElementById('nominalBayar').value) || 0;
    document.getElementById('nominalBayar').value = current + nominal;
    hitungKembalian();
}

// Live Filter Pencarian Produk
document.getElementById('pencarianProduk').addEventListener('input', function (e) {
    let q = e.target.value.toLowerCase().trim();
    let cards = document.querySelectorAll('.item-produk-card');

    cards.forEach(card => {
        let nama = card.getAttribute('data-nama');
        let kode = card.getAttribute('data-kode');
        let barcode = card.getAttribute('data-barcode');

        if (nama.includes(q) || kode.includes(q) || barcode.includes(q)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
