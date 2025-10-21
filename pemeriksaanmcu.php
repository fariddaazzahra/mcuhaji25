<?php
session_start();
// Menggunakan file koneksi yang konsisten
include '../koneksi.php';

// 🔒 Cek role dokter
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'dokter') {
    header("Location: ../login.php");
    exit();
}

// Ambil ID jadwal dari URL
$jadwal_id = $_GET['jadwal_id'] ?? null;
if (!$jadwal_id) {
    header("Location: dashboarddokter.php?error=invalid_id");
    exit();
}

// Ambil data pasien + jadwal MCU
$sql = "SELECT j.*, p.nama_pasien, p.nik, p.jk, p.tgl_lahir 
        FROM jadwalmcu j 
        JOIN pasien p ON j.pasien_id = p.pasien_id 
        WHERE j.jadwal_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $jadwal_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: dashboarddokter.php?error=notfound");
    exit();
}
$data = $result->fetch_assoc();
$stmt->close();

// Ambil data hasil MCU dan pemeriksaan penunjang (jika sudah ada untuk diedit)
$hasil = $conn->query("SELECT * FROM hasilmcu WHERE jadwal_id='$jadwal_id'")->fetch_assoc() ?? [];
$pempenunjang = $conn->query("SELECT * FROM pempenunjang WHERE jadwal_id='$jadwal_id'")->fetch_assoc() ?? [];

// Folder upload
$upload_dir = '../uploads/';
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        // --- 1. Skrining ---
        $td = $_POST['td']; $tb = $_POST['tb']; $bb = $_POST['bb'];
        $kolesterol = $_POST['kolesterol']; $asam_urat = $_POST['asam_urat'];

        // --- 2. Pemeriksaan Penunjang ---
        $tes_urin = $_POST['tes_urin'] ?? ''; $tes_hcv = $_POST['tes_hcv'] ?? '';
        $tes_hiv = $_POST['tes_hiv'] ?? ''; $tes_bta = $_POST['tes_bta'] ?? '';
        $tes_hav = $_POST['tes_hav'] ?? ''; $tes_hbv = $_POST['tes_hbv'] ?? '';
        $usg = $_POST['usg'] ?? ''; $rongten = $_POST['rongten'] ?? '';

        // --- 3. Catatan & Status ---
        $catatan_dokter = $_POST['catatan_dokter']; $status_kelayakan = $_POST['status_kelayakan'];

        // --- 4. Proses Upload File ---
        $hasil_usg = $pempenunjang['hasil_usg'] ?? ''; // Default ke file lama
        if (isset($_FILES['hasil_usg']) && $_FILES['hasil_usg']['error'] == 0) {
            $file_name = time().'_usg_'.basename($_FILES['hasil_usg']['name']);
            move_uploaded_file($_FILES['hasil_usg']['tmp_name'], $upload_dir . $file_name);
            $hasil_usg = $file_name;
        }
        $hasil_rongten = $pempenunjang['hasil_rongten'] ?? ''; // Default ke file lama
        if (isset($_FILES['hasil_rongten']) && $_FILES['hasil_rongten']['error'] == 0) {
            $file_name = time().'_rontgen_'.basename($_FILES['hasil_rongten']['name']);
            move_uploaded_file($_FILES['hasil_rongten']['tmp_name'], $upload_dir . $file_name);
            $hasil_rongten = $file_name;
        }

        // --- 5. Simpan/Update Hasil MCU ---
        if (!empty($hasil)) {
            $stmt1 = $conn->prepare("UPDATE hasilmcu SET td=?, tb=?, bb=?, kolesterol=?, asam_urat=?, catatan_dokter=?, status_kelayakan=? WHERE jadwal_id=?");
            $stmt1->bind_param("sssssssi", $td, $tb, $bb, $kolesterol, $asam_urat, $catatan_dokter, $status_kelayakan, $jadwal_id);
        } else {
            $stmt1 = $conn->prepare("INSERT INTO hasilmcu (jadwal_id, td, tb, bb, kolesterol, asam_urat, catatan_dokter, status_kelayakan) VALUES (?,?,?,?,?,?,?,?)");
            $stmt1->bind_param("isssssss", $jadwal_id, $td, $tb, $bb, $kolesterol, $asam_urat, $catatan_dokter, $status_kelayakan);
        }
        $stmt1->execute();
        $stmt1->close();

        // --- 6. Simpan/Update Pemeriksaan Penunjang ---
        if (!empty($pempenunjang)) {
            $stmt2 = $conn->prepare("UPDATE pempenunjang SET tes_urin=?, tes_hcv=?, tes_hiv=?, tes_bta=?, tes_hav=?, tes_hbv=?, usg=?, hasil_usg=?, rongten=?, hasil_rongten=? WHERE jadwal_id=?");
            $stmt2->bind_param("ssssssssssi", $tes_urin, $tes_hcv, $tes_hiv, $tes_bta, $tes_hav, $tes_hbv, $usg, $hasil_usg, $rongten, $hasil_rongten, $jadwal_id);
        } else {
            $stmt2 = $conn->prepare("INSERT INTO pempenunjang (jadwal_id, tes_urin, tes_hcv, tes_hiv, tes_bta, tes_hav, tes_hbv, usg, hasil_usg, rongten, hasil_rongten) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt2->bind_param("issssssssss", $jadwal_id, $tes_urin, $tes_hcv, $tes_hiv, $tes_bta, $tes_hav, $tes_hbv, $usg, $hasil_usg, $rongten, $hasil_rongten);
        }
        $stmt2->execute();
        $stmt2->close();
        
        // --- 7. Update status jadwal menjadi 'selesai' ---
        $stmt3 = $conn->prepare("UPDATE jadwalmcu SET status_mcu='selesai' WHERE jadwal_id=?");
        $stmt3->bind_param("i", $jadwal_id);
        $stmt3->execute();
        $stmt3->close();

        $conn->commit();
        header("Location: dashboarddokter.php?save=success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemeriksaan MCU - <?= htmlspecialchars($data['nama_pasien']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --dark-green: #0F2A1D; --medium-green-1: #375534; --medium-green-2: #6B9071;
            --light-green: #AEC3B0; --cream: #E3EED4; --white: #FFFFFF;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #E3EED4 0%, #AEC3B0 100%); min-height: 100vh; padding-bottom: 2rem; }
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
        .header .subtitle { color: #6B9071; font-size: 0.95rem; }
        .btn { padding: 0.9rem 1.8rem; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: linear-gradient(135deg, #375534, #6B9071); color: #E3EED4; box-shadow: 0 4px 15px rgba(55, 85, 52, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(55, 85, 52, 0.4); }
        .btn-secondary { background: #E3EED4; color: #375534; border: 2px solid #AEC3B0; }
        .btn-secondary:hover { background: #AEC3B0; }
        .form-card { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 2rem; }
        .form-card h3 { color: var(--dark-green); font-size: 1.2rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 0.75rem;}
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: var(--dark-green); margin-bottom: 0.75rem; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 0.9rem 1.2rem; border: 2px solid #AEC3B0; border-radius: 12px; font-size: 0.95rem; font-family: 'Poppins', sans-serif; transition: all 0.3s ease; background-color: #F8FBF6; }
        .form-input:focus { outline: none; border-color: #6B9071; box-shadow: 0 0 0 4px rgba(107, 144, 113, 0.1); }
        .form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem; }
        .patient-info-bar { display: flex; justify-content: space-around; background: var(--dark-green); color: var(--cream); padding: 1rem; border-radius: 15px; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .info-item { text-align: center; }
        .info-item .label { font-size: 0.8rem; color: var(--light-green); }
        .info-item .value { font-size: 1rem; font-weight: 600; }
        .upload-preview { max-width: 150px; border-radius: 10px; margin-top: 0.5rem; }
        .alert-error { background-color: #FFE4E6; color: #E11D48; border: 1px solid #ffb2ba; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-section"><div class="logo-icon">🕌</div><h2>MCU Haji</h2><p>Sistem Informasi</p></div>
        <div class="user-info">
            <div class="avatar">🩺</div>
            <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <div class="role">Dokter</div>
        </div>
        <nav class="nav-menu">
            <a href="dashboarddokter.php" class="active"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="hasilmcu.php"><span class="icon">📄</span><span>Riwayat Hasil MCU</span></a>
        </nav>
        <a href="#" onclick="confirmLogout(event)" class="logout-btn">🚪 Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Formulir Pemeriksaan MCU</h1>
            <p class="subtitle">Lengkapi semua hasil pemeriksaan untuk pasien.</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>

        <div class="patient-info-bar">
            <div class="info-item"><div class="label">Nama Pasien</div><div class="value"><?= htmlspecialchars($data['nama_pasien']) ?></div></div>
            <div class="info-item"><div class="label">NIK</div><div class="value"><?= htmlspecialchars($data['nik']) ?></div></div>
            <div class="info-item"><div class="label">Usia</div><div class="value"><?= date_diff(date_create($data['tgl_lahir']), date_create('today'))->y ?> tahun</div></div>
            <div class="info-item"><div class="label">Jenis Kelamin</div><div class="value"><?= $data['jk'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></div></div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <!-- Card 1: Skrining -->
            <div class="form-card">
                <h3>🔬 Skrining Awal</h3>
                <div class="form-grid">
                    <div class="form-group"><label for="td">Tekanan Darah (mmHg)</label><input type="text" id="td" name="td" class="form-input" value="<?= htmlspecialchars($hasil['td'] ?? '') ?>" placeholder="cth: 120/80" required></div>
                    <div class="form-group"><label for="tb">Tinggi Badan (cm)</label><input type="number" id="tb" step="0.1" name="tb" class="form-input" value="<?= htmlspecialchars($hasil['tb'] ?? '') ?>" placeholder="cth: 170.5" required></div>
                    <div class="form-group"><label for="bb">Berat Badan (kg)</label><input type="number" id="bb" step="0.1" name="bb" class="form-input" value="<?= htmlspecialchars($hasil['bb'] ?? '') ?>" placeholder="cth: 65.5" required></div>
                    <div class="form-group"><label for="kolesterol">Kolesterol (mg/dL)</label><input type="number" id="kolesterol" name="kolesterol" class="form-input" value="<?= htmlspecialchars($hasil['kolesterol'] ?? '') ?>" placeholder="cth: 190"></div>
                    <div class="form-group"><label for="asam_urat">Asam Urat (mg/dL)</label><input type="number" id="asam_urat" step="0.1" name="asam_urat" class="form-input" value="<?= htmlspecialchars($hasil['asam_urat'] ?? '') ?>" placeholder="cth: 5.7"></div>
                </div>
            </div>

            <!-- Card 2: Pemeriksaan Penunjang -->
            <div class="form-card">
                <h3>🧪 Pemeriksaan Penunjang</h3>
                <div class="form-grid">
                    <div class="form-group"><label for="tes_urin">Tes Urin</label><select id="tes_urin" name="tes_urin" class="form-input"><option value="">-- Pilih Hasil --</option><option value="negatif" <?= ($pempenunjang['tes_urin'] ?? '')=='negatif'?'selected':'' ?>>Negatif</option><option value="positif" <?= ($pempenunjang['tes_urin'] ?? '')=='positif'?'selected':'' ?>>Positif</option></select></div>
                    <div class="form-group"><label for="tes_hcv">Tes HCV</label><select id="tes_hcv" name="tes_hcv" class="form-input"><option value="">-- Pilih Hasil --</option><option value="reaktif" <?= ($pempenunjang['tes_hcv'] ?? '')=='reaktif'?'selected':'' ?>>Reaktif</option><option value="non-reaktif" <?= ($pempenunjang['tes_hcv'] ?? '')=='non-reaktif'?'selected':'' ?>>Non-Reaktif</option></select></div>
                    <div class="form-group"><label for="tes_hiv">Tes HIV</label><select id="tes_hiv" name="tes_hiv" class="form-input"><option value="">-- Pilih Hasil --</option><option value="negatif" <?= ($pempenunjang['tes_hiv'] ?? '')=='negatif'?'selected':'' ?>>Negatif</option><option value="positif" <?= ($pempenunjang['tes_hiv'] ?? '')=='positif'?'selected':'' ?>>Positif</option></select></div>
                    <div class="form-group"><label for="tes_bta">Tes BTA</label><select id="tes_bta" name="tes_bta" class="form-input"><option value="">-- Pilih Hasil --</option><option value="negatif" <?= ($pempenunjang['tes_bta'] ?? '')=='negatif'?'selected':'' ?>>Negatif</option><option value="positif" <?= ($pempenunjang['tes_bta'] ?? '')=='positif'?'selected':'' ?>>Positif</option></select></div>
                    <div class="form-group"><label for="tes_hav">Tes HAV</label><select id="tes_hav" name="tes_hav" class="form-input"><option value="">-- Pilih Hasil --</option><option value="negatif" <?= ($pempenunjang['tes_hav'] ?? '')=='negatif'?'selected':'' ?>>Negatif</option><option value="positif" <?= ($pempenunjang['tes_hav'] ?? '')=='positif'?'selected':'' ?>>Positif</option></select></div>
                    <div class="form-group"><label for="tes_hbv">Tes HBV</label><select id="tes_hbv" name="tes_hbv" class="form-input"><option value="">-- Pilih Hasil --</option><option value="negatif" <?= ($pempenunjang['tes_hbv'] ?? '')=='negatif'?'selected':'' ?>>Negatif</option><option value="positif" <?= ($pempenunjang['tes_hbv'] ?? '')=='positif'?'selected':'' ?>>Positif</option></select></div>
                    <div class="form-group"><label for="usg">Hasil USG (Teks)</label><input type="text" id="usg" name="usg" class="form-input" value="<?= htmlspecialchars($pempenunjang['usg'] ?? '') ?>" placeholder="Tulis hasil singkat..."></div>
                    <div class="form-group"><label for="hasil_usg">Upload File USG</label><input type="file" id="hasil_usg" name="hasil_usg" class="form-input" accept="image/*,application/pdf"> <?php if (!empty($pempenunjang['hasil_usg'])): ?><a href="../uploads/<?= htmlspecialchars($pempenunjang['hasil_usg']) ?>" target="_blank">Lihat file saat ini</a><?php endif; ?></div>
                    <div class="form-group"><label for="rongten">Hasil Rontgen (Teks)</label><input type="text" id="rongten" name="rongten" class="form-input" value="<?= htmlspecialchars($pempenunjang['rongten'] ?? '') ?>" placeholder="Tulis hasil singkat..."></div>
                    <div class="form-group"><label for="hasil_rongten">Upload File Rontgen</label><input type="file" id="hasil_rongten" name="hasil_rongten" class="form-input" accept="image/*,application/pdf"> <?php if (!empty($pempenunjang['hasil_rongten'])): ?><a href="../uploads/<?= htmlspecialchars($pempenunjang['hasil_rongten']) ?>" target="_blank">Lihat file saat ini</a><?php endif; ?></div>
                </div>
            </div>

            <!-- Card 3: Catatan & Kelayakan -->
            <div class="form-card">
                <h3>✅ Kesimpulan & Status Kelayakan</h3>
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;"><label for="catatan_dokter">Catatan Dokter</label><textarea id="catatan_dokter" name="catatan_dokter" rows="5" class="form-input" placeholder="Tuliskan kesimpulan, diagnosis, atau rekomendasi di sini..."><?= htmlspecialchars($hasil['catatan_dokter'] ?? '') ?></textarea></div>
                    <div class="form-group"><label for="status_kelayakan">Status Kelayakan</label><select id="status_kelayakan" name="status_kelayakan" class="form-input" required><option value="">-- Pilih Status --</option><option value="layak" <?= ($hasil['status_kelayakan'] ?? '')=='layak'?'selected':'' ?>>Layak</option><option value="tidak layak" <?= ($hasil['status_kelayakan'] ?? '')=='tidak layak'?'selected':'' ?>>Tidak Layak</option></select></div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="dashboarddokter.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Simpan Hasil Pemeriksaan</button>
            </div>
        </form>
    </main>

<script>
// Fungsi Konfirmasi Logout
function confirmLogout(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Logout', text: "Apakah Anda yakin ingin keluar?", icon: 'question',
        iconColor: '#AEC3B0', showCancelButton: true, confirmButtonColor: '#d33',
        cancelButtonColor: '#375534', confirmButtonText: 'Ya, Logout!', cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = '../logout.php'; }
    });
}
</script>
</body>
</html>