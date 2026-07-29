<?php
session_start();

// Load credential config
$configFile = __DIR__ . '/../../config/auth.php';
if (!file_exists($configFile)) {
    die('Error: File konfigurasi auth.php tidak ditemukan. Silakan copy config/auth.php.example menjadi config/auth.php');
}
require_once $configFile;

// Jika admin sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi credentials dari config
    if (isset(ADMIN_CREDENTIALS[$username])) {
        $user = ADMIN_CREDENTIALS[$username];
        
        // Verifikasi password hash
        if (password_verify($password, $user['password_hash'])) {
            // Login berhasil, set session
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect ke dashboard admin
            header('Location: ../dashboard.php');
            exit;
        }
    }
    
    // Login gagal
    $error = 'Username atau password yang Anda masukkan tidak valid.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - TMB RRI Malang</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a; /* Slate 900 - Lebih profesional daripada biru terang */
            --primary-hover: #1e293b;
            --accent: #2563eb;
            --bg-light: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --error-border: #fecaca;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body {
            display: flex;
            min-height: 100vh;
            background: var(--bg-light);
        }

        /* --- LEFT PANEL: BRANDING & VISUAL --- */
        .brand-panel {
            flex: 1;
            /* Background foto dengan overlay gradient gelap agar teks tetap mudah dibaca */
            /* GANTI URL di bawah dengan foto asli RRI Malang Anda, contoh: url('../assets/img/rri-malang.jpg') */
            background: linear-gradient(160deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.90) 100%), 
                        url('https://rricoid-assets.obs.ap-southeast-4.myhuaweicloud.com/berita/Malang/o/1755923254787-IMG_6458/04zyr9j3l5v0l9o.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }

        /* Subtle background pattern agar foto tidak terlihat polos dan lebih premium */
        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
            opacity: 0.6;
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            max-width: 480px;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2rem;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-logo i {
            font-size: 2rem;
            color: #38bdf8; /* Sky blue accent */
        }

        .brand-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
        }

        .brand-content p {
            font-size: 1.1rem;
            color: #cbd5e1; /* Sedikit lebih terang agar kontras dengan background foto */
            line-height: 1.6;
        }

        .brand-features {
            margin-top: 3rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        .feature-item i {
            color: #38bdf8;
            font-size: 1.1rem;
        }

        /* --- RIGHT PANEL: LOGIN FORM --- */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #ffffff;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .alert-danger {
            background: var(--error-bg);
            color: var(--error-text);
            padding: 0.875rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border: 1px solid var(--error-border);
            line-height: 1.4;
        }

        .alert-danger i {
            font-size: 1.1rem;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.75rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text-main);
            transition: all 0.2s;
            outline: none;
            background: #fafafa;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-control:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control:focus + .input-icon,
        .input-icon-wrap:focus-within .input-icon {
            color: var(--accent);
        }

        .btn-login {
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0.85rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .login-footer a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .login-footer a:hover {
            color: var(--accent);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 900px) {
            .brand-panel {
                display: none; /* Sembunyikan panel kiri di mobile agar fokus ke form */
            }
            .form-panel {
                padding: 1.5rem;
            }
            .login-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- LEFT PANEL: Branding & Context -->
    <div class="brand-panel">
        <div class="brand-content">
            <div class="brand-logo">
                <i class="ri-broadcast-line"></i>
                <span>TMB RRI Malang</span>
            </div>
            <h1>Sistem Manajemen Operasional</h1>
            <p>Platform terpadu untuk mengelola agenda siaran, checklist perangkat, dan dokumentasi kegiatan Tim TMB secara real-time.</p>
            
            <div class="brand-features">
                <div class="feature-item">
                    <i class="ri-checkbox-circle-line"></i>
                    <span>Monitoring kesiapan perangkat harian</span>
                </div>
                <div class="feature-item">
                    <i class="ri-calendar-schedule-line"></i>
                    <span>Penjadwalan agenda dan live streaming</span>
                </div>
                <div class="feature-item">
                    <i class="ri-shield-check-line"></i>
                    <span>Keamanan data dan akses terpusat</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL: Login Form -->
    <div class="form-panel">
        <div class="login-card">
            <div class="form-header">
                <h2>Selamat Datang</h2>
                <p>Silakan masukkan kredensial Anda untuk melanjutkan.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-danger">
                    <i class="ri-error-warning-fill"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-icon-wrap">
                        <i class="ri-user-3-line input-icon"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon-wrap">
                        <i class="ri-lock-2-line input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <span>Masuk ke Dashboard</span>
                    <i class="ri-arrow-right-line"></i>
                </button>
            </form>

            <div class="login-footer">
                <a href="../../index.php">
                    <i class="ri-arrow-left-line"></i> Kembali ke Halaman Utama
                </a>
            </div>
        </div>
    </div>

</body>
</html>