<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Kumpulan Fungsi Pembantu (Helper Functions)
 * File: includes/fungsi.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Menghasilkan URL absolut aplikasi secara dinamis
 * Bekerja baik untuk /supermarket/ maupun /supermarket-management/
 */
function base_url($path = '') {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $parts = explode('/', trim($scriptName, '/'));
    $rootFolder = $parts[0] ?? '';
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $base = $protocol . '://' . $host . '/' . $rootFolder;
    if ($path !== '') {
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
    return rtrim($base, '/');
}

/**
 * Sanitasi output HTML untuk mencegah XSS
 */
function escape($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format mata uang Rupiah
 */
function rupiah($angka, $dengan_rp = true) {
    $hasil = number_format((float)$angka, 0, ',', '.');
    return $dengan_rp ? 'Rp ' . $hasil : $hasil;
}

/**
 * Format tanggal Indonesia
 */
function tanggal_indo($datetime, $dengan_waktu = false) {
    if (empty($datetime) || $datetime === '0000-00-00' || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($datetime);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $tgl = date('d', $timestamp);
    $bln = (int)date('m', $timestamp);
    $thn = date('Y', $timestamp);
    
    $hasil = $tgl . ' ' . ($bulan[$bln] ?? '') . ' ' . $thn;
    
    if ($dengan_waktu) {
        $hasil .= ' ' . date('H:i', $timestamp) . ' WIB';
    }
    
    return $hasil;
}

/**
 * Render badge status dengan warna Bootstrap yang sesuai
 */
function badge_status($status) {
    $status_str = strtolower(trim((string)$status));
    $badge_class = 'bg-secondary';
    $label = ucfirst($status);

    switch ($status_str) {
        case 'aktif':
        case 'lunas':
        case 'diterima':
        case 'berhasil':
        case 'utama':
        case 'ya':
            $badge_class = 'bg-success';
            $label = ($status_str === 'ya') ? 'Ya' : ucfirst($status_str);
            break;

        case 'dipesan':
        case 'menunggu':
        case 'tambahan':
        case 'cadangan':
        case 'draft':
            $badge_class = 'bg-warning text-dark';
            $label = ucfirst($status_str);
            break;

        case 'nonaktif':
        case 'batal':
        case 'dibatalkan':
        case 'gagal':
        case 'belum_lunas':
        case 'tidak':
            $badge_class = 'bg-danger';
            $label = ($status_str === 'belum_lunas') ? 'Belum Lunas' : ucfirst($status_str);
            break;

        case 'masuk':
            $badge_class = 'bg-info text-dark';
            $label = 'Masuk';
            break;
        case 'keluar':
            $badge_class = 'bg-danger';
            $label = 'Keluar';
            break;
        case 'penyesuaian':
            $badge_class = 'bg-primary';
            $label = 'Penyesuaian';
            break;
        case 'retur':
            $badge_class = 'bg-warning text-dark';
            $label = 'Retur';
            break;
    }

    return "<span class=\"badge {$badge_class} px-2 py-1\">" . escape($label) . "</span>";
}

/**
 * Menyimpan pesan flash session (success, danger, warning, info)
 */
function flash_set($tipe, $pesan) {
    $_SESSION['flash'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

/**
 * Menampilkan pesan flash lalu menghapusnya
 */
function flash_display() {
    if (isset($_SESSION['flash'])) {
        $tipe = $_SESSION['flash']['tipe'];
        $pesan = $_SESSION['flash']['pesan'];
        unset($_SESSION['flash']);

        $icon = match($tipe) {
            'success' => 'bi-check-circle-fill',
            'danger' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill'
        };

        echo "
        <div class=\"alert alert-{$tipe} alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm\" role=\"alert\">
            <i class=\"bi {$icon} me-2 fs-5\"></i>
            <div class=\"flex-grow-1\">{$pesan}</div>
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
        </div>";
    }
}

/**
 * CSRF Protection
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    $token = csrf_token();
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
}

function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die("
            <div style='font-family: sans-serif; padding: 20px; background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; border-radius: 8px; margin: 40px auto; max-width: 500px; text-align: center;'>
                <h3>Permintaan Ditolak (CSRF Token Tidak Valid)</h3>
                <p>Silakan muat ulang halaman formulir dan coba kirim kembali.</p>
                <a href='javascript:history.back()' style='display: inline-block; margin-top: 10px; padding: 8px 16px; background: #c62828; color: white; text-decoration: none; border-radius: 4px;'>Kembali</a>
            </div>
            ");
        }
    }
}

/**
 * Cek status login
 */
function cek_login() {
    if (empty($_SESSION['user_id'])) {
        flash_set('warning', 'Silakan masuk ke akun Anda terlebih dahulu.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Ambil data user yang sedang login
 */
function user_login() {
    return [
        'id_akun' => $_SESSION['user_id'] ?? null,
        'id_karyawan' => $_SESSION['id_karyawan'] ?? null,
        'username' => $_SESSION['username'] ?? 'Tamu',
        'nama_karyawan' => $_SESSION['nama_karyawan'] ?? 'Pengguna',
        'peran' => $_SESSION['peran'] ?? 'guest',
        'nama_jabatan' => $_SESSION['nama_jabatan'] ?? 'Staff',
        'nama_cabang' => $_SESSION['nama_cabang'] ?? 'Pusat'
    ];
}

/**
 * Cek otorisasi peran
 */
function has_role($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    $user_role = $_SESSION['peran'] ?? '';
    return in_array($user_role, $roles, true);
}

/**
 * Helper Pagination
 */
function hitung_pagination($total_data, $limit = 10, $halaman_aktif = 1) {
    $total_data = max(0, (int)$total_data);
    $limit = max(1, (int)$limit);
    $total_halaman = max(1, (int)ceil($total_data / $limit));
    $halaman_aktif = max(1, min((int)$halaman_aktif, $total_halaman));
    $offset = ($halaman_aktif - 1) * $limit;

    $dari = $total_data > 0 ? $offset + 1 : 0;
    $sampai = min($offset + $limit, $total_data);

    return [
        'total_data' => $total_data,
        'limit' => $limit,
        'halaman_aktif' => $halaman_aktif,
        'total_halaman' => $total_halaman,
        'offset' => $offset,
        'dari' => $dari,
        'sampai' => $sampai
    ];
}

/**
 * Render pagination Bootstrap 5
 */
function render_pagination($base_page_url, $total_halaman, $halaman_aktif, $query_params = []) {
    if ($total_halaman <= 1) return '';

    $html = '<nav aria-label="Navigasi Halaman"><ul class="pagination pagination-sm mb-0">';
    
    // Tombol Sebelumnya
    $prev_disabled = ($halaman_aktif <= 1) ? ' disabled' : '';
    $prev_page = max(1, $halaman_aktif - 1);
    $query_params['halaman'] = $prev_page;
    $prev_url = $base_page_url . '?' . http_build_query($query_params);
    $html .= "<li class=\"page-item{$prev_disabled}\"><a class=\"page-link\" href=\"{$prev_url}\"><i class=\"bi bi-chevron-left\"></i> Sebelumnya</a></li>";

    // Nomor Halaman
    $start = max(1, $halaman_aktif - 2);
    $end = min($total_halaman, $halaman_aktif + 2);

    if ($start > 1) {
        $query_params['halaman'] = 1;
        $url = $base_page_url . '?' . http_build_query($query_params);
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$url}\">1</a></li>";
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $halaman_aktif) ? ' active' : '';
        $query_params['halaman'] = $i;
        $url = $base_page_url . '?' . http_build_query($query_params);
        $html .= "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"{$url}\">{$i}</a></li>";
    }

    if ($end < $total_halaman) {
        if ($end < $total_halaman - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $query_params['halaman'] = $total_halaman;
        $url = $base_page_url . '?' . http_build_query($query_params);
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$url}\">{$total_halaman}</a></li>";
    }

    // Tombol Selanjutnya
    $next_disabled = ($halaman_aktif >= $total_halaman) ? ' disabled' : '';
    $next_page = min($total_halaman, $halaman_aktif + 1);
    $query_params['halaman'] = $next_page;
    $next_url = $base_page_url . '?' . http_build_query($query_params);
    $html .= "<li class=\"page-item{$next_disabled}\"><a class=\"page-link\" href=\"{$next_url}\">Selanjutnya <i class=\"bi bi-chevron-right\"></i></a></li>";

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Logika Bisnis Stok: Mencatat pergerakan stok dan memperbarui tabel persediaan
 */
function update_stok_persediaan($pdo, $id_produk, $id_gudang, $jumlah_perubahan, $jenis_pergerakan, $id_karyawan, $referensi = null, $keterangan = null) {
    // 1. Ambil stok saat ini dari persediaan
    $stmt = $pdo->prepare("SELECT id_persediaan, jumlah_stok FROM persediaan WHERE id_produk = ? AND id_gudang = ? FOR UPDATE");
    $stmt->execute([$id_produk, $id_gudang]);
    $persediaan = $stmt->fetch();

    $stok_sebelum = $persediaan ? (int)$persediaan['jumlah_stok'] : 0;
    $stok_sesudah = $stok_sebelum;

    if ($jenis_pergerakan === 'masuk' || $jenis_pergerakan === 'retur') {
        $stok_sesudah = $stok_sebelum + abs($jumlah_perubahan);
    } elseif ($jenis_pergerakan === 'keluar') {
        if ($stok_sebelum < abs($jumlah_perubahan)) {
            throw new Exception("Stok tidak mencukupi! Stok saat ini: {$stok_sebelum}, permintaan: " . abs($jumlah_perubahan));
        }
        $stok_sesudah = $stok_sebelum - abs($jumlah_perubahan);
    } elseif ($jenis_pergerakan === 'penyesuaian') {
        // Pada penyesuaian, jumlah_perubahan adalah stok baru aktual
        $stok_sesudah = max(0, (int)$jumlah_perubahan);
    }

    // 2. Perbarui atau buat baris di tabel persediaan
    if ($persediaan) {
        $stmtUpdate = $pdo->prepare("UPDATE persediaan SET jumlah_stok = ?, terakhir_diperbarui = NOW() WHERE id_persediaan = ?");
        $stmtUpdate->execute([$stok_sesudah, $persediaan['id_persediaan']]);
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO persediaan (id_produk, id_gudang, jumlah_stok, stok_minimum, terakhir_diperbarui) VALUES (?, ?, ?, 5, NOW())");
        $stmtInsert->execute([$id_produk, $id_gudang, $stok_sesudah]);
    }

    // 3. Catat di tabel pergerakan_stok
    $stmtLog = $pdo->prepare("
        INSERT INTO pergerakan_stok 
        (id_produk, id_gudang, id_karyawan, jenis_pergerakan, jumlah, stok_sebelum, stok_sesudah, referensi, keterangan, tanggal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmtLog->execute([
        $id_produk,
        $id_gudang,
        $id_karyawan,
        $jenis_pergerakan,
        abs($jumlah_perubahan),
        $stok_sebelum,
        $stok_sesudah,
        $referensi,
        $keterangan
    ]);

    return true;
}
