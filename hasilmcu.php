<?php
session_start();
// Menggunakan file koneksi yang konsisten
include '../koneksi.php';

// Cek jika pengguna sudah login dan memiliki role dokter atau nakes
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['dokter', 'nakes'])) {
    header("Location: ../login.php");
    exit();
}

// Logika untuk notifikasi Toast
$notification = null;
if (isset($_GET['update']) && $_GET['update'] == 'success') {
    $notification = ['type' => 'success', 'text' => 'Hasil MCU berhasil diperbarui.'];
}
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'notfound') {
        $notification = ['type' => 'error', 'text' => 'Data hasil MCU tidak ditemukan.'];
    } elseif ($_GET['error'] == 'invalid_id') {
         $notification = ['type' => 'error', 'text' => 'ID tidak valid atau tidak lengkap.'];
    }
}

// Ambil pilihan pencarian & kata kunci
$filter = $_GET['filter'] ?? 'nama'; // Default filter ke nama
$keyword = $_GET['keyword'] ?? '';

// Query dasar (hanya tampilkan yang status_mcu = 'selesai')
$sql = "
    SELECT 
        j.jadwal_id, j.tanggal_mcu, j.status_mcu,
        p.pasien_id, p.nama_pasien, p.nik
    FROM jadwalmcu j
    JOIN pasien p ON j.pasien_id = p.pasien_id
    WHERE j.status_mcu = 'selesai'
";

// Tambahkan filter pencarian jika ada input
$params = [];
$types = "";
if (!empty($keyword)) {
    $keyword_param = "%" . $keyword . "%";
    if ($filter == 'nama') {
        $sql .= " AND p.nama_pasien LIKE ?";
        $params[] = $keyword_param;
        $types .= "s";
    } elseif ($filter == 'nik') {
        $sql .= " AND p.nik LIKE ?";
        $params[] = $keyword_param;
        $types .= "s";
    } elseif ($filter == 'tanggal') {
        $sql .= " AND DATE(j.tanggal_mcu) = ?";
        $params[] = $keyword; // Untuk tanggal, tidak perlu wildcard
        $types .= "s";
    }
}

$sql .= " ORDER BY j.tanggal_mcu DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Hasil MCU - MCU Haji</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Menggunakan CSS yang sama dengan Dashboard -->
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
        .action-section { background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 2rem; }
        .search-form { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .search-form select, .search-form input { padding: 0.9rem 1.2rem; border: 2px solid #AEC3B0; border-radius: 12px; font-size: 0.95rem; font-family: 'Poppins', sans-serif; background-color: #F8FBF6; }
        .search-form input { flex-grow: 1; }
        .search-form select:focus, .search-form input:focus { outline: none; border-color: #6B9071; box-shadow: 0 0 0 4px rgba(107, 144, 113, 0.1); }
        .btn { padding: 0.9rem 1.8rem; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: linear-gradient(135deg, #375534, #6B9071); color: #E3EED4; box-shadow: 0 4px 15px rgba(55, 85, 52, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(55, 85, 52, 0.4); }
        .btn-secondary { background: #E3EED4; color: #375534; border: 2px solid #AEC3B0; }
        .btn-secondary:hover { background: #AEC3B0; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
        .table-section { background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #375534, #6B9071); }
        thead th { padding: 1rem; text-align: left; color: #E3EED4; font-weight: 600; font-size: 0.9rem; white-space: nowrap; }
        thead th:first-child { border-radius: 12px 0 0 0; }
        thead th:last-child { border-radius: 0 12px 0 0; }
        tbody tr { border-bottom: 1px solid #E3EED4; transition: background-color 0.2s ease; }
        tbody tr:hover { background-color: #F8FBF6; }
        tbody td { padding: 1rem; color: #0F2A1D; font-size: 0.9rem; }
        tbody tr:last-child { border-bottom: none; }
        .empty-state { text-align: center; padding: 3rem 1rem; color: #6B9071; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state p { font-size: 1.1rem; font-weight: 500; }
        .badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .status-selesai { background: #D1FAE5; color: #065F46; }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #E3EED4; }
        ::-webkit-scrollbar-thumb { background: #6B9071; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #375534; }
    </style>
</head>
<body>
    <!-- Sidebar (Disesuaikan untuk role saat ini) -->
    <aside class="sidebar">
        <div class="logo-section"><div class="logo-icon">🕌</div><h2>MCU Haji</h2><p>Sistem Informasi</p></div>
        <div class="user-info">
            <div class="avatar"><?= $_SESSION['role'] == 'dokter' ? '🩺' : '👨‍⚕️' ?></div>
            <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <div class="role"><?= ucfirst(htmlspecialchars($_SESSION['role'])) ?></div>
        </div>
        <nav class="nav-menu">
            <?php if ($_SESSION['role'] == 'dokter'): ?>
                <a href="dashboarddokter.php"><span class="icon">📊</span><span>Dashboard</span></a>
                <a href="hasilmcu.php" class="active"><span class="icon">📄</span><span>Riwayat Hasil MCU</span></a>
            <?php else: // Nakes ?>
                <a href="../nakes/dashboardnakes.php"><span class="icon">📊</span><span>Dashboard</span></a>
                <a href="../nakes/pendaftaran.php"><span class="icon">📝</span><span>Pendaftaran</span></a>
                <a href="../nakes/pembayaran.php"><span class="icon">💳</span><span>Pembayaran</span></a>
                 <a href="hasilmcu.php" class="active"><span class="icon">📄</span><span>Riwayat Hasil MCU</span></a>
            <?php endif; ?>
        </nav>
        <a href="#" onclick="confirmLogout(event)" class="logout-btn">🚪 Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Riwayat Hasil MCU</h1>
            <p class="subtitle">Daftar riwayat pemeriksaan pasien yang telah selesai.</p>
        </div>
        
        <div class="action-section">
            <form method="GET" action="" class="search-form">
                <select name="filter" id="filter" onchange="updateInputType()">
                    <option value="nama" <?= $filter == 'nama' ? 'selected' : '' ?>>Nama Pasien</option>
                    <option value="nik" <?= $filter == 'nik' ? 'selected' : '' ?>>NIK</option>
                    <option value="tanggal" <?= $filter == 'tanggal' ? 'selected' : '' ?>>Tanggal MCU</option>
                </select>
                <input type="<?= $filter == 'tanggal' ? 'date' : 'text' ?>" name="keyword" id="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Masukkan kata kunci..." required>
                <button type="submit" class="btn btn-primary">🔍 Cari</button>
                <a href="hasilmcu.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <div class="table-section">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th><th>Tanggal MCU</th><th>ID Pasien</th><th>Nama Pasien</th>
                            <th>NIK</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d M Y, H:i', strtotime($row['tanggal_mcu'])) ?></td>
                                <td><strong><?= htmlspecialchars($row['pasien_id']) ?></strong></td>
                                <td><?= htmlspecialchars($row['nama_pasien']) ?></td>
                                <td><?= htmlspecialchars($row['nik']) ?></td>
                                <td><span class="badge status-selesai"><?= htmlspecialchars(ucfirst($row['status_mcu'])) ?></span></td>
                                <td><a href="detailhasilmcu.php?id=<?= $row['jadwal_id'] ?>" class="btn btn-primary btn-sm">👁️ Lihat Detail</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🗂️</div>
                    <p>Tidak ada riwayat hasil MCU yang ditemukan.</p>
                </div>
            <?php endif; ?>
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

// Fungsi untuk mengubah tipe input pencarian
function updateInputType() {
    const filter = document.getElementById('filter');
    const keywordInput = document.getElementById('keyword');
    if (filter.value === 'tanggal') {
        keywordInput.type = 'date';
        keywordInput.placeholder = '';
    } else {
        keywordInput.type = 'text';
        keywordInput.placeholder = 'Masukkan kata kunci...';
    }
}

// Menjalankan notifikasi Toast jika ada
<?php if ($notification): ?>
document.addEventListener('DOMContentLoaded', function() {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: '<?= $notification['type'] ?>',
        title: '<?= addslashes($notification['text']) ?>'
    });
});
<?php endif; ?>
</script>
</body>
</html>