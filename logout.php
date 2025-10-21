<?php
session_start();
// Cek jika pengguna belum login, langsung arahkan ke login.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Tentukan URL dashboard berdasarkan role pengguna untuk tombol "Batal"
$dashboard_url = 'index.php'; // Halaman default jika role tidak terdefinisi
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            $dashboard_url = 'admin/dashboardadmin.php';
            break;
        case 'nakes':
            $dashboard_url = 'nakes/dashboardnakes.php';
            break;
        case 'dokter':
            $dashboard_url = 'dokter/dashboarddokter.php';
            break;
    }
}

// --- FUNGSI BARU UNTUK PROSES LOGOUT ---
// Jika ada parameter ?action=true di URL, maka proses logout
if (isset($_GET['action']) && $_GET['action'] == 'true') {
    // Hapus semua variabel sesi
    $_SESSION = array();
    // Hancurkan sesi
    session_destroy();
    // Redirect ke halaman login
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Logout - MCU Haji</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Menggunakan Style yang Sama Persis dengan Tema Anda -->
    <style>
        :root {
            --dark-green: #0F2A1D; --medium-green-1: #375534; --medium-green-2: #6B9071;
            --light-green: #AEC3B0; --cream: #E3EED4; --white: #FFFFFF;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--medium-green-1) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .confirmation-card {
            background: var(--cream);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px;
            width: 100%;
            border: 1px solid var(--light-green);
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .confirmation-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            animation: wave 2s ease-in-out infinite;
        }
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            75% { transform: rotate(-15deg); }
        }
        .confirmation-card h1 {
            color: var(--dark-green);
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
        }
        .confirmation-card p {
            color: var(--medium-green-1);
            margin-bottom: 2.5rem;
            font-size: 1rem;
            line-height: 1.6;
        }
        .button-group {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.9rem 1.8rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .btn-danger:hover {
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--white);
            color: var(--dark-green);
            border: 2px solid var(--light-green);
        }
        .btn-secondary:hover {
            background: var(--light-green);
            border-color: var(--light-green);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="confirmation-icon">👋</div>
        <h1>Anda Akan Keluar</h1>
        <p>Terima kasih telah menggunakan sistem. Pastikan semua pekerjaan Anda telah disimpan sebelum melanjutkan.</p>
        <div class="button-group">
            <a href="<?= htmlspecialchars($dashboard_url) ?>" class="btn btn-secondary">Batal & Kembali</a>
            <a href="logout.php?action=true" class="btn btn-danger">Ya, Logout Sekarang</a>
        </div>
    </div>
</body>
</html>