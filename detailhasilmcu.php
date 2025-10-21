<?php
session_start();
// Menggunakan file koneksi yang konsisten
include '../koneksi.php';

// --- PERBAIKAN UTAMA: Izin Akses ---
// Cek jika pengguna sudah login DAN rolenya adalah dokter, nakes, ATAU admin.
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['dokter', 'nakes', 'admin'])) {
    // Jika tidak, tendang ke halaman login.
    header("Location: ../login.php");
    exit();
}

// --- PERBAIKAN LOGIKA REDIRECT ---
// Tentukan halaman kembali berdasarkan role pengguna jika terjadi eror
$redirect_page = 'hasilmcu.php'; // Default untuk dokter
if ($_SESSION['role'] == 'admin') {
    $redirect_page = '../admin/manajemenmcu.php';
} elseif ($_SESSION['role'] == 'nakes') {
    $redirect_page = '../nakes/pendaftaran.php'; // Nakes kembali ke pendaftaran
}

// Ambil ID jadwal dari URL
$jadwal_id = $_GET['id'] ?? 0;
if (!$jadwal_id) {
    // Redirect ke halaman yang sesuai dengan role jika ID tidak ada
    header("Location: $redirect_page?error=invalid_id");
    exit();
}

// Ambil semua data yang diperlukan
$stmt = $conn->prepare("
    SELECT 
        j.jadwal_id, j.tanggal_mcu, j.status_mcu,
        p.pasien_id, p.nama_pasien, p.nik, p.jk, p.tgl_lahir, p.goldar, p.alamat, p.no_hp
    FROM jadwalmcu j
    JOIN pasien p ON j.pasien_id = p.pasien_id
    WHERE j.jadwal_id = ?
");
$stmt->bind_param("i", $jadwal_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    // Redirect ke halaman yang sesuai jika data tidak ditemukan
    header("Location: $redirect_page?error=notfound");
    exit();
}

// Ambil data hasil MCU
$stmt_hasil = $conn->prepare("SELECT * FROM hasilmcu WHERE jadwal_id = ?");
$stmt_hasil->bind_param("i", $jadwal_id);
$stmt_hasil->execute();
$hasil = $stmt_hasil->get_result()->fetch_assoc();

// Ambil data hasil penunjang
$stmt_penunjang = $conn->prepare("SELECT * FROM pempenunjang WHERE jadwal_id = ?");
$stmt_penunjang->bind_param("i", $jadwal_id);
$stmt_penunjang->execute();
$pempenunjang = $stmt_penunjang->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Hasil MCU - <?= htmlspecialchars($data['nama_pasien']) ?></title>
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
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .btn { padding: 0.9rem 1.8rem; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); }
        .btn-warning:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(217, 119, 6, 0.4); }
        .btn-secondary { background: #E3EED4; color: #375534; border: 2px solid #AEC3B0; }
        .btn-secondary:hover { background: #AEC3B0; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 2rem; }
        .detail-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
        .detail-card h3 { color: var(--dark-green); font-size: 1.2rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 0.75rem;}
        .detail-item { display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 0; border-bottom: 1px solid #f5f5f5; gap: 1rem; }
        .detail-item:last-child { border-bottom: none; }
        .detail-label { font-weight: 500; color: #6B9071; white-space: nowrap; }
        .detail-value { font-weight: 600; color: var(--dark-green); text-align: right; }
        .badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .status-selesai { background: #D1FAE5; color: #065F46; }
        .status-dijadwalkan { background: #FEF3C7; color: #92400E; }
        .status-layak { background: #D1FAE5; color: #065F46; }
        .status-tidak-layak, .status-tidak\ layak { background: #FFE4E6; color: #E11D48; }
        .status-layak-dengan-catatan, .status-layak\ dengan\ catatan { background: #FEF9C3; color: #854d0e; }
        .file-link { margin-top: 0.5rem; }
        .catatan-dokter { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        .catatan-dokter .detail-value { text-align: left; width: 100%; font-weight: 500; line-height: 1.6; }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <!-- Sidebar (Disesuaikan untuk role saat ini) -->
    <aside class="sidebar">
        <div class="logo-section"><div class="logo-icon">🕌</div><h2>MCU Haji</h2><p>Sistem Informasi</p></div>
        <div class="user-info">
            <div class="avatar"><?= $_SESSION['role'] == 'admin' ? '👑' : ($_SESSION['role'] == 'dokter' ? '🩺' : '👨‍⚕️') ?></div>
            <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <div class="role"><?= ucfirst(htmlspecialchars($_SESSION['role'])) ?></div>
        </div>
        <nav class="nav-menu">
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="../admin/dashboardadmin.php"><span class="icon">📊</span><span>Dashboard</span></a>
                <a href="../admin/manajemenpasien.php"><span class="icon">👥</span><span>Manajemen Pasien</span></a>
                <a href="../admin/manajemenmcu.php" class="active"><span class="icon">🩺</span><span>Manajemen MCU</span></a>
            <?php elseif ($_SESSION['role'] == 'dokter'): ?>
                <a href="dashboarddokter.php"><span class="icon">📊</span><span>Dashboard</span></a>
                <a href="hasilmcu.php" class="active"><span class="icon">📄</span><span>Riwayat Hasil MCU</span></a>
            <?php else: // Nakes ?>
                <a href="../nakes/dashboardnakes.php"><span class="icon">📊</span><span>Dashboard</span></a>
                <a href="../nakes/pendaftaran.php"><span class="icon">📝</span><span>Pendaftaran</span></a>
                <a href="../nakes/pembayaran.php"><span class="icon">💳</span><span>Pembayaran</span></a>
            <?php endif; ?>
        </nav>
        <a href="#" onclick="confirmLogout(event)" class="logout-btn">🚪 Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Detail Hasil MCU</h1>
            <p class="subtitle">Laporan lengkap hasil pemeriksaan untuk pasien: <strong><?= htmlspecialchars($data['nama_pasien']) ?></strong></p>
        </div>
        
       <div class="action-bar">
            <?php 
                // URL kembali berdasarkan role
                $kembali_url = '#';
                if ($_SESSION['role'] == 'admin') $kembali_url = '../admin/manajemenmcu.php';
                if ($_SESSION['role'] == 'dokter') $kembali_url = 'hasilmcu.php';
                if ($_SESSION['role'] == 'nakes') $kembali_url = '../nakes/pendaftaran.php';
            ?>
            <a href="<?= $kembali_url ?>" class="btn btn-secondary">⬅️ Kembali</a>
            
            <?php if ($_SESSION['role'] == 'dokter'): ?>
                <div style="display: inline-flex; gap: 0.5rem;">
                    <a href="editdetailhasilmcu.php?id=<?= $jadwal_id ?>" class="btn btn-warning">✏️ Edit Hasil MCU</a>
                    <a href="downloadhasilmcu.php?id=<?= $jadwal_id ?>" class="btn btn-danger" target="_blank">📄 Download PDF</a>
                </div>
            <?php endif; ?>
        </div>


        <div class="details-grid">
            <!-- Card Data Pasien -->
            <div class="detail-card">
                <h3>👤 Data Pasien</h3>
                <div class="detail-item"><span class="detail-label">Nama Pasien</span> <span class="detail-value"><?= htmlspecialchars($data['nama_pasien'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">NIK</span> <span class="detail-value"><?= htmlspecialchars($data['nik'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tgl Lahir / Usia</span> <span class="detail-value"><?= !empty($data['tgl_lahir']) ? date('d M Y', strtotime($data['tgl_lahir'])) . ' (' . date_diff(date_create($data['tgl_lahir']), date_create('today'))->y . ' thn)' : '-' ?></span></div>
                <div class="detail-item"><span class="detail-label">Jenis Kelamin</span> <span class="detail-value"><?= htmlspecialchars($data['jk'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">No. HP</span> <span class="detail-value"><?= htmlspecialchars($data['no_hp'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tanggal MCU</span> <span class="detail-value"><?= !empty($data['tanggal_mcu']) ? date('d M Y, H:i', strtotime($data['tanggal_mcu'])) : '-' ?></span></div>
                <div class="detail-item"><span class="detail-label">Status MCU</span> <span class="detail-value"><span class="badge status-<?= str_replace(' ', '-', strtolower($data['status_mcu'] ?? '')) ?>"><?= htmlspecialchars($data['status_mcu'] ?? '-') ?></span></span></div>
            </div>

            <!-- Card Hasil Skrining -->
            <div class="detail-card">
                <h3>🔬 Hasil Skrining</h3>
                <div class="detail-item"><span class="detail-label">Tekanan Darah</span> <span class="detail-value"><?= htmlspecialchars($hasil['td'] ?? '-') ?> mmHg</span></div>
                <div class="detail-item"><span class="detail-label">Tinggi / Berat Badan</span> <span class="detail-value"><?= htmlspecialchars($hasil['tb'] ?? '-') ?> cm / <?= htmlspecialchars($hasil['bb'] ?? '-') ?> kg</span></div>
                <div class="detail-item"><span class="detail-label">Golongan Darah</span> <span class="detail-value"><?= htmlspecialchars($data['goldar'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Kolesterol</span> <span class="detail-value"><?= htmlspecialchars($hasil['kolesterol'] ?? '-') ?> mg/dL</span></div>
                <div class="detail-item"><span class="detail-label">Asam Urat</span> <span class="detail-value"><?= htmlspecialchars($hasil['asam_urat'] ?? '-') ?> mg/dL</span></div>
                <div class="detail-item"><span class="detail-label">Status Kelayakan</span> <span class="detail-value"><span class="badge status-<?= str_replace(' ', '-', strtolower(str_replace('/',' ',$hasil['status_kelayakan'] ?? ''))) ?>"><?= htmlspecialchars($hasil['status_kelayakan'] ?? '-') ?></span></span></div>
                <div class="detail-item catatan-dokter"><span class="detail-label">Catatan Dokter</span> <span class="detail-value"><?= !empty($hasil['catatan_dokter']) ? nl2br(htmlspecialchars($hasil['catatan_dokter'])) : '-' ?></span></div>
            </div>

            <!-- Card Hasil Penunjang -->
            <div class="detail-card">
                <h3>🧪 Hasil Pemeriksaan Penunjang</h3>
                <div class="detail-item"><span class="detail-label">Tes Urin</span> <span class="detail-value"><?= htmlspecialchars($pempenunjang['tes_urin'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tes HCV</span> <span class="detail-value"><?= htmlspecialchars($pempenunjang['tes_hcv'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tes HIV</span> <span class="detail-value"><?= htmlspecialchars($pempenunjang['tes_hiv'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tes BTA</span> <span class="detail-value"><?= htmlspecialchars($pempenunjang['tes_bta'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tes HAV</span> <span class="detail-value"><?= htmlspecialchars($pempenunjang['tes_hav'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">Tes HBV</span> <span class="detail-value"><?= htmlspecialchars($pempenunjang['tes_hbv'] ?? '-') ?></span></div>
                <div class="detail-item"><span class="detail-label">USG</span> 
                    <span class="detail-value">
                        <?= htmlspecialchars($pempenunjang['usg'] ?? '-') ?>
                        <?php if (!empty($pempenunjang['hasil_usg'])): ?>
                            <div class="file-link"><a href="../uploads/<?= htmlspecialchars($pempenunjang['hasil_usg']) ?>" target="_blank" class="btn btn-secondary btn-sm">Lihat File</a></div>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-item"><span class="detail-label">Rontgen</span> 
                    <span class="detail-value">
                        <?= htmlspecialchars($pempenunjang['rongten'] ?? '-') ?>
                        <?php if (!empty($pempenunjang['hasil_rongten'])): ?>
                            <div class="file-link"><a href="../uploads/<?= htmlspecialchars($pempenunjang['hasil_rongten']) ?>" target="_blank" class="btn btn-secondary btn-sm">Lihat File</a></div>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </main>

<a href="downloadhasilmcu.php?id=<?= $jadwal_id ?>" class="btn btn-danger" target="_blank">
    <i class="fas fa-file-pdf"></i> Download PDF
</a>


<script>
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