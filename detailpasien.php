<?php
session_start();
include '../koneksi.php';

// Cek jika pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Ambil ID pasien dari URL
$pasien_id = $_GET['id'] ?? 0;
if (!$pasien_id) {
    header("Location: manajemenpasien.php?error=invalid_id");
    exit();
}

// --- PERBAIKAN DI SINI: Menambahkan kolom 'b.jumlah' ---
// Query untuk mengambil data lengkap pasien beserta semua riwayat MCU-nya
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        j.jadwal_id, j.tanggal_mcu, j.status_mcu,
        h.status_kelayakan,
        b.status_bayar,
        b.jumlah AS jumlah_bayar -- Mengambil kolom jumlah dari tabel pembayaran
    FROM pasien p
    LEFT JOIN jadwalmcu j ON p.pasien_id = j.pasien_id
    LEFT JOIN hasilmcu h ON j.jadwal_id = h.jadwal_id
    LEFT JOIN pembayaran b ON j.jadwal_id = b.jadwal_id
    WHERE p.pasien_id = ?
    ORDER BY j.tanggal_mcu DESC
");
$stmt->bind_param("i", $pasien_id);
$stmt->execute();
$result = $stmt->get_result();
$riwayat_mcu = [];
$data_pasien = null;

while ($row = $result->fetch_assoc()) {
    if ($data_pasien === null) {
        // Ambil data pasien dari baris pertama saja
        $data_pasien = $row;
    }
    // Kumpulkan semua riwayat MCU
    if ($row['jadwal_id']) {
        $riwayat_mcu[] = $row;
    }
}
$stmt->close();

if ($data_pasien === null) {
    header("Location: manajemenpasien.php?error=notfound");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pasien - <?= htmlspecialchars($data_pasien['nama_pasien']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* CSS Lengkap dari Dashboard Admin */
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
        .btn-secondary { background: #E3EED4; color: #375534; border: 2px solid #AEC3B0; }
        .btn-secondary:hover { background: #AEC3B0; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
        .detail-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 2rem; }
        .detail-card h3 { color: var(--dark-green); font-size: 1.2rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 0.75rem;}
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-item { display: flex; flex-direction: column; }
        .detail-label { font-weight: 500; color: #6B9071; font-size: 0.85rem; margin-bottom: 0.25rem; }
        .detail-value { font-weight: 600; color: var(--dark-green); }
        .table-section { background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #375534, #6B9071); }
        thead th { padding: 1rem; text-align: left; color: #E3EED4; font-weight: 600; font-size: 0.9rem; white-space: nowrap; }
        tbody tr { border-bottom: 1px solid #E3EED4; }
        tbody td { padding: 1rem; color: #0F2A1D; font-size: 0.9rem; }
        tbody tr:last-child { border-bottom: none; }
        .badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .status-lunas { background: #D1FAE5; color: #065F46; }
        .status-belum, .status-belumbayar { background: #FFE4E6; color: #E11D48; }
        .status-selesai { background: #D1FAE5; color: #065F46; }
        .status-dijadwalkan { background: #FEF3C7; color: #92400E; }
        .status-layak { background: #D1FAE5; color: #065F46; }
        .status-tidak-layak, .status-tidak\ layak { background: #FFE4E6; color: #E11D48; }
        .status-layak-dengan-catatan { background: #FEF9C3; color: #854d0e; }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo-section"><div class="logo-icon">🕌</div><h2>MCU Haji</h2><p>Sistem Informasi</p></div>
        <div class="user-info">
            <div class="avatar">👑</div>
            <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <div class="role">Admin</div>
        </div>
        <nav class="nav-menu">
            <a href="dashboardadmin.php"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="manajemenpasien.php" class="active"><span class="icon">👥</span><span>Manajemen Pasien</span></a>
            <a href="manajemenmcu.php"><span class="icon">🩺</span><span>Manajemen MCU</span></a>
        </nav>
        <a href="#" onclick="confirmLogout(event)" class="logout-btn">🚪 Logout</a>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Detail Biodata Pasien</h1>
            <p class="subtitle">Informasi lengkap dan riwayat pemeriksaan pasien.</p>
        </div>

        <div class="detail-card">
            <h3>👤 Informasi Personal</h3>
            <div class="detail-grid">
                <div class="detail-item"><span class="detail-label">Nama Lengkap</span> <span class="detail-value"><?= htmlspecialchars($data_pasien['nama_pasien']) ?></span></div>
                <div class="detail-item"><span class="detail-label">NIK</span> <span class="detail-value"><?= htmlspecialchars($data_pasien['nik']) ?></span></div>
                <div class="detail-item"><span class="detail-label">ID Pasien</span> <span class="detail-value"><?= htmlspecialchars($data_pasien['pasien_id']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Tanggal Lahir</span> <span class="detail-value"><?= date('d F Y', strtotime($data_pasien['tgl_lahir'])) ?></span></div>
                <div class="detail-item"><span class="detail-label">Jenis Kelamin</span> <span class="detail-value"><?= $data_pasien['jk'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></span></div>
                <div class="detail-item"><span class="detail-label">No. Handphone</span> <span class="detail-value"><?= htmlspecialchars($data_pasien['no_hp']) ?></span></div>
                <div class="detail-item" style="grid-column: 1 / -1;"><span class="detail-label">Alamat</span> <span class="detail-value"><?= htmlspecialchars($data_pasien['alamat']) ?></span></div>
            </div>
        </div>
        
        <div class="table-section">
            <h3>🕒 Riwayat Pemeriksaan MCU</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal MCU</th>
                        <th>Status MCU</th>
                        <th>Status Bayar</th>
                        <th>Jumlah Bayar</th>
                        <th>Status Kelayakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($riwayat_mcu)): ?>
                        <?php foreach($riwayat_mcu as $riwayat): ?>
                        <tr>
                            <td><?= date('d M Y, H:i', strtotime($riwayat['tanggal_mcu'])) ?></td>
                            <td><span class="badge status-<?= strtolower($riwayat['status_mcu'] ?? 'belum') ?>"><?= htmlspecialchars(ucfirst($riwayat['status_mcu'] ?? 'Belum')) ?></span></td>
                            <td><span class="badge status-<?= strtolower(str_replace(' ', '', $riwayat['status_bayar'] ?? 'belum')) ?>"><?= htmlspecialchars(ucfirst($riwayat['status_bayar'] ?? 'Belum')) ?></span></td>
                            <td><?= !empty($riwayat['jumlah_bayar']) ? 'Rp ' . number_format($riwayat['jumlah_bayar'], 0, ',', '.') : '-' ?></td>
                            <td>
                                <?php if(!empty($riwayat['status_kelayakan'])): ?>
                                    <span class="badge status-<?= str_replace(' ', '-', strtolower($riwayat['status_kelayakan'])) ?>"><?= htmlspecialchars($riwayat['status_kelayakan']) ?></span>
                                <?php else: ?>
                                    <span style="opacity: 0.6;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 2rem;">Pasien ini belum pernah dijadwalkan MCU.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: right; margin-top: 1.5rem;">
            <a href="manajemenpasien.php" class="btn btn-secondary">⬅️ Kembali ke Manajemen Pasien</a>
        </div>
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