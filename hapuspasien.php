<?php
session_start();
include '../koneksi.php';

// Cek login dan role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'nakes') {
    header("Location: ../login.php");
    exit();
}

// Inisialisasi variabel untuk pesan
$message = "";
$status = ""; // 'success' atau 'error'
$pasien_nama = "";
$redirect = false;

// Pastikan parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $message = "Aksi tidak valid. ID pasien tidak ditemukan.";
    $status = "error";
} else {
    $pasien_id = $_GET['id'];

    // Cek apakah pasien ada di database untuk mendapatkan nama SEBELUM dihapus
    $cek = $conn->prepare("SELECT nama_pasien FROM pasien WHERE pasien_id = ?");
    $cek->bind_param("i", $pasien_id);
    $cek->execute();
    $result = $cek->get_result();

    if ($result->num_rows == 0) {
        $message = "Data pasien tidak ditemukan di database.";
        $status = "error";
    } else {
        $pasien = $result->fetch_assoc();
        $pasien_nama = $pasien['nama_pasien'];
        $cek->close();

        // Lanjutkan proses hapus
        $stmt = $conn->prepare("DELETE FROM pasien WHERE pasien_id = ?");
        $stmt->bind_param("i", $pasien_id);

        if ($stmt->execute()) {
            $message = "Data pasien atas nama <strong>" . htmlspecialchars($pasien_nama) . "</strong> berhasil dihapus.";
            $status = "success";
            $redirect = true; // Set true untuk redirect otomatis
        } else {
            // Pesan error jika ada foreign key constraint (misalnya, pasien sudah punya data pembayaran)
            if ($conn->errno == 1451) {
                $message = "Gagal menghapus: Pasien ini memiliki data terkait (misalnya pembayaran atau hasil MCU) yang tidak bisa dihapus.";
            } else {
                $message = "Gagal menghapus data pasien: " . $conn->error;
            }
            $status = "error";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Penghapusan Pasien - MCU Haji</title>
    <?php if ($redirect): ?>
        <meta http-equiv="refresh" content="3;url=pendaftaran.php">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-green: #0F2A1D;
            --medium-green-1: #375534;
            --medium-green-2: #6B9071;
            --light-green: #AEC3B0;
            --cream: #E3EED4;
            --white: #FFFFFF;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #E3EED4 0%, #AEC3B0 100%); min-height: 100vh; }
        .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #0F2A1D 0%, #375534 100%); padding: 2rem 0; box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1); z-index: 1000; overflow-y: auto; }
        .logo-section { text-align: center; padding: 0 1.5rem 2rem; border-bottom: 1px solid rgba(174, 195, 176, 0.3); }
        .logo-icon { width: 70px; height: 70px; background: linear-gradient(135deg, #6B9071, #AEC3B0); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1rem; box-shadow: 0 6px 20px rgba(107, 144, 113, 0.4); }
        .logo-section h2 { color: #E3EED4; font-size: 1.2rem; font-weight: 600; margin-bottom: 0.3rem; }
        .logo-section p { color: #AEC3B0; font-size: 0.85rem; }
        .user-info { padding: 1.5rem; background: rgba(107, 144, 113, 0.15); margin: 1.5rem; border-radius: 12px; text-align: center; }
        .user-info .avatar { width: 50px; height: 50px; background: linear-gradient(135deg, #AEC3B0, #E3EED4); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.5rem; }
        .user-info .name { color: #E3EED4; font-weight: 600; font-size: 0.95rem; }
        .user-info .role { color: #AEC3B0; font-size: 0.8rem; }
        .nav-menu { padding: 1rem 0; }
        .nav-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; color: #E3EED4; text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; border-left: 4px solid transparent; }
        .nav-menu a:hover { background: rgba(174, 195, 176, 0.1); border-left-color: #AEC3B0; padding-left: 2rem; }
        .nav-menu a.active { background: rgba(174, 195, 176, 0.2); border-left-color: #AEC3B0; font-weight: 600; }
        .nav-menu .icon { font-size: 1.2rem; width: 24px; text-align: center; }
        .logout-btn { margin: 1.5rem; padding: 0.9rem; background: linear-gradient(135deg, #ff6b6b, #ee5a6f); color: white; text-align: center; border-radius: 12px; text-decoration: none; display: block; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3); }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4); }
        .main-content { margin-left: 260px; padding: 2rem; }
        .btn { padding: 0.9rem 1.8rem; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #375534, #6B9071); color: #E3EED4; box-shadow: 0 4px 15px rgba(55, 85, 52, 0.3); }
        .status-section { background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); text-align: center; }
        .status-icon { font-size: 4rem; margin-bottom: 1.5rem; }
        .status-icon.success { color: #10B981; }
        .status-icon.error { color: #EF4444; }
        .status-message { font-size: 1.3rem; color: var(--dark-green); margin-bottom: 0.5rem; font-weight: 600; }
        .status-details { font-size: 1rem; color: #6B9071; margin-bottom: 2rem; }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-section"><div class="logo-icon">🕌</div><h2>MCU Haji</h2><p>Sistem Informasi</p></div>
        <div class="user-info"><div class="avatar">👨‍⚕️</div><div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div><div class="role">Nakes</div></div>
        <nav class="nav-menu">
            <a href="dashboardnakes.php"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="pendaftaran.php" class="active"><span class="icon">📝</span><span>Pendaftaran</span></a>
            <a href="antrian.php"><span class="icon">🎫</span><span>Antrian</span></a>
            <a href="../dokter/pemeriksaanmcu.php"><span class="icon">🩺</span><span>Pemeriksaan MCU</span></a>
            <a href="pembayaran.php"><span class="icon">💳</span><span>Pembayaran</span></a>
        </nav>
        <a href="../logout.php" class="logout-btn">🚪 Logout</a>
    </aside>

    <main class="main-content">
        <div class="status-section">
            <?php if ($status == 'success'): ?>
                <div class="status-icon success">✅</div>
                <h2 class="status-message">Berhasil!</h2>
                <div class="status-details"><?= $message ?></div>
                <p class="status-details" style="font-size: 0.9em; opacity: 0.8;">Anda akan diarahkan kembali dalam 3 detik...</p>
            <?php else: ?>
                <div class="status-icon error">❌</div>
                <h2 class="status-message">Terjadi Kesalahan!</h2>
                <div class="status-details"><?= $message ?></div>
            <?php endif; ?>
            <a href="pendaftaran.php" class="btn btn-primary">Kembali ke Pendaftaran</a>
        </div>
    </main>
</body>
</html>