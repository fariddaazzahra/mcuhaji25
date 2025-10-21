<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

session_start();
include '../koneksi.php';

// Cek login dan role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'nakes') {
    header("Location: ../login.php");
    exit();
}

// --- PERUBAHAN 1: Logika untuk Notifikasi Toast ---
$notification = null;
if (isset($_GET['update']) && $_GET['update'] == 'success') {
    $notification = ['type' => 'success', 'text' => 'Data pasien berhasil diperbarui.'];
}
if (isset($_GET['delete']) && $_GET['delete'] == 'success') {
    $notification = ['type' => 'success', 'text' => 'Data pasien berhasil dihapus.'];
}
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'notfound') {
        $notification = ['type' => 'error', 'text' => 'Data pasien tidak ditemukan.'];
    } elseif ($_GET['error'] == 'invalid_id') {
         $notification = ['type' => 'error', 'text' => 'ID tidak valid atau tidak lengkap.'];
    }
}


// Pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%" . $search . "%";

// Query data pasien (MENGGUNAKAN PREPARED STATEMENT UNTUK KEAMANAN)
$sql = "
    SELECT 
        p.pasien_id, p.nama_pasien, p.nik, p.tgl_lahir, p.jk, p.alamat, p.no_hp, p.tgl_daftar, j.tanggal_mcu
    FROM pasien p
    LEFT JOIN jadwalmcu j ON p.pasien_id = j.pasien_id
    WHERE p.nama_pasien LIKE ?
       OR p.nik LIKE ?
       OR p.alamat LIKE ?
       OR p.jk LIKE ?
       OR p.no_hp LIKE ?
    ORDER BY p.pasien_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Pasien - MCU Haji</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Library SweetAlert2 (sudah ada di kode Anda) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Semua CSS Anda disalin ke sini -->
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
        .action-section { background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .search-form { display: flex; gap: 1rem; align-items: center; flex: 1; }
        .search-input { flex: 1; min-width: 250px; padding: 0.9rem 1.2rem; border: 2px solid #AEC3B0; border-radius: 12px; font-size: 0.95rem; font-family: 'Poppins', sans-serif; transition: all 0.3s ease; }
        .search-input:focus { outline: none; border-color: #6B9071; box-shadow: 0 0 0 4px rgba(107, 144, 113, 0.1); }
        .btn { padding: 0.9rem 1.8rem; border: none; border-radius: 12px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; font-family: 'Poppins', sans-serif; }
        .btn-primary { background: linear-gradient(135deg, #375534, #6B9071); color: #E3EED4; box-shadow: 0 4px 15px rgba(55, 85, 52, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(55, 85, 52, 0.4); }
        .btn-secondary { background: #E3EED4; color: #375534; border: 2px solid #AEC3B0; }
        .btn-secondary:hover { background: #AEC3B0; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.85rem; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
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
        .badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-male { background: #D3E4FF; color: #2563EB; }
        .badge-female { background: #FFE4E6; color: #E11D48; }
        .action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } .action-section, .search-form { flex-direction: column; } .search-input, .btn { width: 100%; } }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #E3EED4; }
        ::-webkit-scrollbar-thumb { background: #6B9071; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #375534; }
    </style>
</head>
<body>
    <!-- Sidebar -->
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="header"><h1>Pendaftaran Pasien MCU Haji</h1><p class="subtitle">Kelola data pasien yang terdaftar untuk Medical Check Up Haji</p></div>
        <div class="action-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="🔍 Cari pasien..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
                <a href="pendaftaran.php" class="btn btn-secondary">Reset</a>
            </form>
            <a href="tambahpasien.php" class="btn btn-success">➕ Tambah Pasien Baru</a>
        </div>
        <div class="table-section">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th><th>ID</th><th>Nama Pasien</th><th>NIK</th><th>Jenis Kelamin</th><th>No HP</th><th>Tgl Daftar</th><th>Jadwal MCU</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($row['pasien_id']) ?></strong></td>
                                <td><?= htmlspecialchars($row['nama_pasien']) ?></td>
                                <td><?= htmlspecialchars($row['nik']) ?></td>
                                <td>
<?php $jkVal = trim($row['jk']); $isMale = (strcasecmp($jkVal, 'L') === 0) || (strcasecmp($jkVal, 'Laki-laki') === 0); ?>
                                    <span class="badge <?= $isMale ? 'badge-male' : 'badge-female' ?>">
                                        <?= $isMale ? 'Laki-laki' : 'Perempuan' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['no_hp']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tgl_daftar'])) ?></td>
                                <td><?= $row['tanggal_mcu'] ? date('d/m/Y H:i', strtotime($row['tanggal_mcu'])) : '<span style="opacity:0.6;">Belum dijadwalkan</span>' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="editpasien.php?id=<?= $row['pasien_id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                        
                                        <!-- --- PERUBAHAN 2: Tombol Hapus yang Diperbarui --- -->
                                        <a href="#" 
                                           onclick="confirmDelete('hapuspasien.php?id=<?= $row['pasien_id'] ?>', '<?= htmlspecialchars(addslashes($row['nama_pasien'])) ?>')" 
                                           class="btn btn-danger btn-sm">🗑️ Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state"><div class="icon">📭</div><p>Tidak ada data pasien ditemukan</p></div>
            <?php endif; ?>
        </div>
    </main>
    
<script>
// --- PERUBAHAN 3: Notifikasi "Toast" untuk Sukses ---
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

// --- PERUBAHAN 4: Fungsi JavaScript untuk Konfirmasi Hapus ---
function confirmDelete(deleteUrl, patientName) {
    // Mencegah event default dari link (yaitu navigasi)
    event.preventDefault(); 
    
    Swal.fire({
        title: 'Anda Yakin?',
        html: `Anda akan menghapus data pasien bernama <strong>${patientName}</strong>.<br>Tindakan ini tidak dapat dibatalkan!`,
        icon: 'warning',
        iconColor: '#d33',
        showCancelButton: true,
        confirmButtonColor: '#375534',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Data!',
        cancelButtonText: 'Batal',
        background: 'var(--white)',
        color: 'var(--dark-green)'
    }).then((result) => {
        if (result.isConfirmed) {
            // Jika dikonfirmasi, arahkan ke URL penghapusan
            window.location.href = deleteUrl;
        }
    });
}
</script>

</body>
</html>