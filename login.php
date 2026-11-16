<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Halaman Masuk (Login)
 * File: login.php
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/fungsi.php';

// Jika sudah login, langsung alihkan ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . base_url('dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan masukkan nama pengguna (username) dan kata sandi.';
    } else {
        // Cari akun karyawan berdasarkan username
        $query = "
            SELECT 
                a.id_akun, 
                a.id_karyawan, 
                a.username, 
                a.password, 
                a.peran, 
                a.status AS status_akun,
                k.nama_karyawan,
                k.id_cabang,
                j.nama_jabatan,
                c.nama_cabang
            FROM akun_karyawan a
            INNER JOIN karyawan k ON a.id_karyawan = k.id_karyawan
            INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            INNER JOIN cabang c ON k.id_cabang = c.id_cabang
            WHERE a.username = :username
            LIMIT 1
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status_akun'] !== 'aktif') {
                $error = 'Akun Anda dinonaktifkan. Silakan hubungi Administrator.';
            } else {
                // Perbarui waktu terakhir login
                $updateStmt = $pdo->prepare("UPDATE akun_karyawan SET terakhir_login = NOW() WHERE id_akun = ?");
                $updateStmt->execute([$user['id_akun']]);

                // Simpan session
                $_SESSION['user_id'] = $user['id_akun'];
                $_SESSION['id_karyawan'] = $user['id_karyawan'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_karyawan'] = $user['nama_karyawan'];
                $_SESSION['peran'] = $user['peran'];
                $_SESSION['id_cabang'] = $user['id_cabang'];
                $_SESSION['nama_cabang'] = $user['nama_cabang'];
                $_SESSION['nama_jabatan'] = $user['nama_jabatan'];

                flash_set('success', 'Selamat datang kembali, <strong>' . escape($user['nama_karyawan']) . '</strong>!');
                header('Location: ' . base_url('dashboard.php'));
                exit;
            }
        } else {
            $error = 'Username atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Sistem Manajemen Supermarket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .login-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }
        .brand-icon {
            width: 56px;
            height: 56px;
            background: #2563eb;
            color: #ffffff;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }
        .login-body {
            padding: 2rem;
        }
        .demo-badge {
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        .demo-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="brand-icon">
            <i class="bi bi-cart3"></i>
        </div>
        <h4 class="fw-bold mb-1 text-dark">Sistem Manajemen Supermarket</h4>
        <p class="text-muted small mb-0">Tugas Pembelajaran Basis Data Relasional 25 Tabel</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center small py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                <div><?= escape($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php flash_display(); ?>

        <form action="" method="POST">
            <?= csrf_input() ?>

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold small text-muted">Nama Pengguna (Username)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" value="<?= escape($_POST['username'] ?? 'admin') ?>" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold small text-muted">Kata Sandi (Password)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" value="admin123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
            <p class="text-muted small mb-2 fw-semibold">Akun Pengujian Demo (Klik untuk mengisi):</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <span class="badge bg-primary text-white demo-badge" onclick="isiAkun('admin', 'admin123')">
                    <i class="bi bi-shield-lock me-1"></i> Admin
                </span>
                <span class="badge bg-success text-white demo-badge" onclick="isiAkun('kasir', 'admin123')">
                    <i class="bi bi-cash-coin me-1"></i> Kasir
                </span>
                <span class="badge bg-warning text-dark demo-badge" onclick="isiAkun('manajer', 'admin123')">
                    <i class="bi bi-briefcase me-1"></i> Manajer
                </span>
                <span class="badge bg-info text-dark demo-badge" onclick="isiAkun('gudang', 'admin123')">
                    <i class="bi bi-box-seam me-1"></i> Gudang
                </span>
            </div>
            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">Password default: <code>admin123</code></small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function isiAkun(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}
</script>
</body>
</html>
