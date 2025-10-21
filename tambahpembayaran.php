<?php
session_start();
include '../koneksi.php';

// 🔒 Cek login role nakes
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'nakes') {
    header("Location: ../login.php");
    exit();
}

// Inisialisasi variabel
$error = "";
$success = "";
$pasien_id = $_GET['pasien_id'] ?? '';
$jadwal_id = $_GET['jadwal_id'] ?? '';
$nama_pasien = '';

// Validasi ID dari URL
if (empty($pasien_id) || empty($jadwal_id)) {
    // Redirect ke halaman pembayaran jika ID tidak lengkap
    header("Location: pembayaran.php?error=invalid_id");
    exit();
}

// Ambil nama pasien untuk ditampilkan
$queryPasien = $conn->prepare("SELECT nama_pasien FROM pasien WHERE pasien_id = ?");
$queryPasien->bind_param("i", $pasien_id);
$queryPasien->execute();
$resultPasien = $queryPasien->get_result();

if ($resultPasien->num_rows > 0) {
    $pasien = $resultPasien->fetch_assoc();
    $nama_pasien = $pasien['nama_pasien'];
} else {
    // Redirect jika pasien tidak ditemukan
    header("Location: pembayaran.php?error=notfound");
    exit();
}
$queryPasien->close();

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah = $_POST['jumlah'];
    $metode = $_POST['metode_bayar'];
    $keterangan = $_POST['keterangan'];

    // Validasi input
    if (empty($jumlah) || empty($metode)) {
        $error = "Jumlah dan Metode Pembayaran wajib diisi.";
    } elseif (!is_numeric($jumlah) || $jumlah < 0) {
        $error = "Jumlah pembayaran tidak valid.";
    } else {
        // Simpan pembayaran ke database
        $stmt = $conn->prepare("INSERT INTO pembayaran 
            (pasien_id, jadwal_id, jumlah, metode_bayar, status_bayar, keterangan, tanggal_bayar)
            VALUES (?, ?, ?, ?, 'lunas', ?, NOW())");
        $stmt->bind_param("iisss", $pasien_id, $jadwal_id, $jumlah, $metode, $keterangan);

        if ($stmt->execute()) {
            $success = "Pembayaran untuk pasien " . htmlspecialchars($nama_pasien) . " berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan pembayaran: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pembayaran - MCU Haji</title>
    <?php if (!empty($success)): ?>
        <meta http-equiv="refresh" content="3;url=pembayaran.php">
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
        .header { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 2rem; }
        .header h1 { color: #0F2A1D; font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { color: #6B9071; font-size: 0.95rem; }
        .btn { padding: 0.9rem 1.8rem; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #375534, #6B9071); color: #E3EED4; box-shadow: 0 4px 15px rgba(55, 85, 52, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(55, 85, 52, 0.4); }
        .btn-secondary { background: #E3EED4; color: #375534; border: 2px solid #AEC3B0; }
        .btn-secondary:hover { background: #AEC3B0; }
        .form-section { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; color: var(--dark-green); margin-bottom: 0.75rem; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 0.9rem 1.2rem; border: 2px solid #AEC3B0; border-radius: 12px; font-size: 0.95rem; font-family: 'Poppins', sans-serif; transition: all 0.3s ease; background-color: #F8FBF6; }
        .form-input:focus { outline: none; border-color: #6B9071; box-shadow: 0 0 0 4px rgba(107, 144, 113, 0.1); }
        .form-input:disabled { background-color: #f0f4f0; cursor: not-allowed; color: #6B9071; font-weight: 500;}
        .form-actions { margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-error { background-color: #FFE4E6; color: #E11D48; border: 1px solid #ffb2ba; }
        .alert-success { background-color: #D1FAE5; color: #065F46; border: 1px solid #6ee7b7; }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-section"><div class="logo-icon">🕌</div><h2>MCU Haji</h2><p>Sistem Informasi</p></div>
        <div class="user-info"><div class="avatar">👨‍⚕️</div><div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div><div class="role">Nakes</div></div>
        <nav class="nav-menu">
            <a href="dashboardnakes.php"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="pendaftaran.php"><span class="icon">📝</span><span>Pendaftaran</span></a>
            <a href="antrian.php"><span class="icon">🎫</span><span>Antrian</span></a>
            <a href="../dokter/pemeriksaanmcu.php"><span class="icon">🩺</span><span>Pemeriksaan MCU</span></a>
            <a href="pembayaran.php" class="active"><span class="icon">💳</span><span>Pembayaran</span></a>
        </nav>
        <a href="../logout.php" class="logout-btn">🚪 Logout</a>
    </aside>

    <main class="main-content">
        <div class="header"><h1>Tambah Pembayaran MCU</h1><p>Input detail pembayaran untuk pasien yang dipilih.</p></div>
        <div class="form-section">
            <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?>. Anda akan diarahkan kembali...</div><?php endif; ?>

            <form method="POST" action="tambahpembayaran.php?pasien_id=<?= $pasien_id ?>&jadwal_id=<?= $jadwal_id ?>">
                <div class="form-group">
                    <label>Pasien</label>
                    <input type="text" class="form-input" value="<?= htmlspecialchars($nama_pasien) ?>" disabled>
                </div>

                <div class="form-group">
                    <label for="jumlah">Jumlah (Rp)</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-input" placeholder="Contoh: 500000" min="0" required>
                </div>

                <div class="form-group">
                    <label for="metode_bayar">Metode Pembayaran</label>
                    <select id="metode_bayar" name="metode_bayar" class="form-input" required>
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer Bank</option>
                        <option value="Debit">Debit</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan (Opsional)</label>
                    <textarea id="keterangan" name="keterangan" rows="3" class="form-input" placeholder="Contoh: Pembayaran lunas untuk paket MCU dasar"></textarea>
                </div>

                <div class="form-actions">
                    <a href="pembayaran.php" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>