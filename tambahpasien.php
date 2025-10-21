<?php
session_start();
include '../koneksi.php';

// Cek login dan role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'nakes') {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nik = trim($_POST['nik']);
    $nama_pasien = trim($_POST['nama_pasien']);
    $tgl_daftar = trim($_POST['tgl_daftar']);
    $tanggal_mcu = trim($_POST['tanggal_mcu']);
    $no_hp = trim($_POST['no_hp']);
    $jk_input = isset($_POST['jk']) ? strtoupper(trim($_POST['jk'])) : '';
    // Simpan sesuai ENUM/teks di DB: 'Laki-laki' atau 'Perempuan'
    $jk = ($jk_input === 'L') ? 'Laki-laki' : (($jk_input === 'P') ? 'Perempuan' : '');
    $tgl_lahir = trim($_POST['tgl_lahir']);
    $alamat = trim($_POST['alamat']);

    // Validasi sederhana
    if (empty($nik) || empty($nama_pasien) || empty($tgl_daftar) || empty($no_hp) || empty($jk) || empty($tgl_lahir) || empty($alamat) || empty($tanggal_mcu)) {
        $error = "Semua kolom wajib diisi.";
    } elseif (strtotime($tanggal_mcu) < strtotime($tgl_daftar)) {
        $error = "Tanggal MCU tidak boleh sebelum tanggal daftar.";
    } else {
        // Cek apakah NIK sudah terdaftar
        $cekNik = $conn->prepare("SELECT pasien_id FROM pasien WHERE nik = ?");
        $cekNik->bind_param("s", $nik);
        $cekNik->execute();
        $hasil = $cekNik->get_result();

        if ($hasil->num_rows > 0) {
            $error = "Pasien dengan NIK ini sudah terdaftar.";
        } else {
            // Mulai transaksi database
            $conn->begin_transaction();
            try {
                // Simpan data pasien
                $stmt = $conn->prepare("INSERT INTO pasien (nik, nama_pasien, tgl_daftar, no_hp, jk, tgl_lahir, alamat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $nik, $nama_pasien, $tgl_daftar, $no_hp, $jk, $tgl_lahir, $alamat);
                $stmt->execute();
                $pasien_id = $conn->insert_id;
                $stmt->close();

                // Simpan jadwal MCU
                $stmt2 = $conn->prepare("INSERT INTO jadwalmcu (pasien_id, tanggal_mcu) VALUES (?, ?)");
                $stmt2->bind_param("is", $pasien_id, $tanggal_mcu);
                $stmt2->execute();
                $stmt2->close();
                
                // Jika semua berhasil, commit transaksi
                $conn->commit();
                $success = "Data pasien dan jadwal MCU berhasil ditambahkan.";

            } catch (Exception $e) {
                // Jika ada error, batalkan semua perubahan
                $conn->rollback();
                $error = "Terjadi kesalahan. Gagal menambahkan data: " . $e->getMessage();
            }
        }
        $cekNik->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pasien Baru - MCU Haji</title>
    <?php if (!empty($success)): ?>
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
        .header { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 2rem; }
        .header h1 { color: #0F2A1D; font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header p { color: #6B9071; font-size: 0.95rem; }
        .btn { padding: 0.9rem 1.8rem; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #375534, #6B9071); color: #E3EED4; box-shadow: 0 4px 15px rgba(55, 85, 52, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(55, 85, 52, 0.4); }
        .btn-secondary { background: #E3EED4; color: #375534; border: 2px solid #AEC3B0; }
        .btn-secondary:hover { background: #AEC3B0; }

        /* Style untuk Form */
        .form-section { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 0; } /* Dihapus karena gap sudah dihandle grid */
        .full-width { grid-column: 1 / -1; } /* Untuk elemen yang butuh lebar penuh seperti alamat */
        .form-group label { display: block; font-weight: 600; color: var(--dark-green); margin-bottom: 0.75rem; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 0.9rem 1.2rem; border: 2px solid #AEC3B0; border-radius: 12px; font-size: 0.95rem; font-family: 'Poppins', sans-serif; transition: all 0.3s ease; background-color: #F8FBF6; }
        .form-input:focus { outline: none; border-color: #6B9071; box-shadow: 0 0 0 4px rgba(107, 144, 113, 0.1); }
        .form-actions { margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; }
        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert-error { background-color: #FFE4E6; color: #E11D48; border: 1px solid #ffb2ba; }
        .alert-success { background-color: #D1FAE5; color: #065F46; border: 1px solid #6ee7b7; }
        
        /* Responsive */
        @media (max-width: 1200px) { .form-grid { grid-template-columns: 1fr; } }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-section">
            <div class="logo-icon">🕌</div>
            <h2>MCU Haji</h2>
            <p>Sistem Informasi</p>
        </div>
        <div class="user-info">
            <div class="avatar">👨‍⚕️</div>
            <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <div class="role">Nakes</div>
        </div>
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
        <div class="header">
            <h1>Tambah Pasien Baru</h1>
            <p>Isi formulir di bawah ini untuk mendaftarkan pasien baru ke dalam sistem.</p>
        </div>

        <div class="form-section">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?>. Anda akan diarahkan kembali...</div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nik">NIK</label>
                        <input type="text" id="nik" name="nik" class="form-input" pattern="\d{16}" minlength="16" maxlength="16" title="NIK harus terdiri dari 16 digit angka" required>
                    </div>

                    <div class="form-group">
                        <label for="nama_pasien">Nama Pasien</label>
                        <input type="text" id="nama_pasien" name="nama_pasien" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="tgl_lahir">Tanggal Lahir</label>
                        <input type="date" id="tgl_lahir" name="tgl_lahir" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="jk">Jenis Kelamin</label>
                        <select id="jk" name="jk" class="form-input" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tgl_daftar">Tanggal Daftar</label>
                        <input type="date" id="tgl_daftar" name="tgl_daftar" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_mcu">Tanggal MCU</label>
                        <input type="datetime-local" id="tanggal_mcu" name="tanggal_mcu" class="form-input" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="no_hp">No HP</label>
                        <input type="tel" id="no_hp" name="no_hp" class="form-input" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" class="form-input" required></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="pendaftaran.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Pasien</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>