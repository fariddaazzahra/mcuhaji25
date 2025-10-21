<?php
session_start();
include '../koneksi.php';
// Pastikan halaman tidak di-cache agar grafik selalu membaca data terbaru
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Cek login dan role admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// === Total pasien hari ini ===
$total_pasien_hari_ini = $conn->query("SELECT COUNT(*) AS total FROM pasien WHERE DATE(tgl_daftar) = CURDATE()")->fetch_assoc()['total'] ?? 0;

// === Belum diperiksa ===
$query_belum = "
    SELECT COUNT(DISTINCT p.pasien_id) AS total 
    FROM pasien p
    JOIN jadwalmcu j ON p.pasien_id = j.pasien_id 
    WHERE LOWER(j.status_mcu) = 'dijadwalkan'
";
$belum_diperiksa = $conn->query($query_belum)->fetch_assoc()['total'] ?? 0;

// === Sudah diperiksa ===
$query_sudah = "
    SELECT COUNT(DISTINCT p.pasien_id) AS total 
    FROM pasien p
    JOIN jadwalmcu j ON p.pasien_id = j.pasien_id 
    WHERE LOWER(j.status_mcu) = 'selesai'
";
$sudah_diperiksa = $conn->query($query_sudah)->fetch_assoc()['total'] ?? 0;

// === Total seluruh pasien ===
$total_semua_pasien = $conn->query("SELECT COUNT(*) AS total FROM pasien")->fetch_assoc()['total'] ?? 0;

// === GRAFIK STATUS KELAYAKAN ===
$data_kelayakan = [
    'Layak' => 0,
    'Tidak Layak' => 0,
    'Layak dengan Catatan' => 0
];
$query_kelayakan = "SELECT status_kelayakan, COUNT(*) AS total FROM hasilmcu GROUP BY status_kelayakan";
$result_kelayakan = $conn->query($query_kelayakan);
if ($result_kelayakan) {
    while ($row = $result_kelayakan->fetch_assoc()) {
        $status_from_db = strtolower(trim($row['status_kelayakan']));
        $count = (int)$row['total'];

        if ($status_from_db == 'layak') {
            $data_kelayakan['Layak'] += $count;
        } elseif ($status_from_db == 'tidak layak') {
            $data_kelayakan['Tidak Layak'] += $count;
        } elseif (strpos($status_from_db, 'layak dengan') !== false) {
            $data_kelayakan['Layak dengan Catatan'] += $count;
        }
    }
}

// === GRAFIK JENIS KELAMIN ===
// Hitung langsung di SQL untuk mengakomodasi berbagai variasi nilai pada kolom jk
$sql_jk = "
    SELECT 
        SUM(CASE 
                WHEN UPPER(TRIM(jk)) IN ('L','LAKI-LAKI','LAKI LAKI','LAKI') THEN 1 
                ELSE 0 
            END) AS laki,
        SUM(CASE 
                WHEN UPPER(TRIM(jk)) IN ('P','PEREMPUAN','WANITA') THEN 1 
                ELSE 0 
            END) AS perempuan
    FROM pasien
    WHERE jk IS NOT NULL AND jk <> ''
";

$res_jk = $conn->query($sql_jk);
$row_jk = $res_jk ? $res_jk->fetch_assoc() : ['laki' => 0, 'perempuan' => 0];

$data_jk = [
    'Laki-laki' => (int)($row_jk['laki'] ?? 0),
    'Perempuan' => (int)($row_jk['perempuan'] ?? 0)
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - MCU Haji</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
        .logout-btn { margin: 1.5rem; padding: 0.9rem; background: linear-gradient(135deg, #ff6b6b, #ee5a6f); color: white; text-align: center; border-radius: 12px; text-decoration: none; display: block; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3); cursor: pointer; }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4); }
        .main-content { margin-left: 260px; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 1.5rem 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 2rem; }
        .header h1 { color: #0F2A1D; font-size: 1.8rem; font-weight: 700; }
        #datetime { background-color: var(--cream); color: var(--dark-green); padding: 0.5rem 1rem; border-radius: 10px; font-weight: 500; font-size: 0.9rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); display: flex; align-items: center; gap: 1.5rem; }
        .stat-icon { font-size: 2.5rem; width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; }
        .icon-1 { background-color: #d1fae5; color: #065f46; }
        .icon-2 { background-color: #fef3c7; color: #92400e; }
        .icon-3 { background-color: #dbeafe; color: #1e40af; }
        .icon-4 { background-color: #e0e7ff; color: #3730a3; }
        .stat-info .value { font-size: 2rem; font-weight: 700; color: var(--dark-green); }
        .stat-info .label { font-size: 0.9rem; color: #6B9071; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .chart-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); min-height: 450px; }
        .chart-container { width: 100%; height: 380px; }
        @media (max-width: 1200px) { .charts-grid { grid-template-columns: 1fr; } }
        @media (max-width: 968px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } .header { flex-direction: column; gap: 1rem; } }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #E3EED4; }
        ::-webkit-scrollbar-thumb { background: #6B9071; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #375534; }
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
            <a href="dashboardadmin.php" class="active"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="manajemenpasien.php"><span class="icon">👥</span><span>Manajemen Pasien</span></a>
            <a href="manajemenmcu.php"><span class="icon">🩺</span><span>Manajemen MCU</span></a>
        </nav>
        <a href="#" onclick="confirmLogout(event)" class="logout-btn">🚪 Logout</a>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Dashboard Admin</h1>
            <div id="datetime"></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon icon-1">📅</div><div class="stat-info"><div class="value"><?= $total_pasien_hari_ini ?></div><div class="label">Pasien Hari Ini</div></div></div>
            <div class="stat-card"><div class="stat-icon icon-2">⏳</div><div class="stat-info"><div class="value"><?= $belum_diperiksa ?></div><div class="label">Belum Diperiksa</div></div></div>
            <div class="stat-card"><div class="stat-icon icon-3">✅</div><div class="stat-info"><div class="value"><?= $sudah_diperiksa ?></div><div class="label">Sudah Diperiksa</div></div></div>
            <div class="stat-card"><div class="stat-icon icon-4">👥</div><div class="stat-info"><div class="value"><?= $total_semua_pasien ?></div><div class="label">Total Semua Pasien</div></div></div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div id="chartKelayakan" class="chart-container"></div>
            </div>
            <div class="chart-card">
                <div id="chartJK" class="chart-container"></div>
            </div>
        </div>
    </main>

<script>
// Data dari PHP
const dataJKFromPHP = {
    laki: <?= $data_jk['Laki-laki']; ?>,
    perempuan: <?= $data_jk['Perempuan']; ?>
};

console.log('Data JK dari PHP:', dataJKFromPHP);

// Update waktu real-time
function updateDateTime() {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    document.getElementById('datetime').textContent = now.toLocaleString('id-ID', options);
}
setInterval(updateDateTime, 1000);
updateDateTime();

// Fungsi Logout
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

// Load Google Charts
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawCharts);

function drawCharts() {
    console.log('=== Starting to draw charts ===');
    
    // === Pie Chart - Status Kelayakan ===
    const kelLayak = <?= $data_kelayakan['Layak']; ?>;
    const kelTidak = <?= $data_kelayakan['Tidak Layak']; ?>;
    const kelCatatan = <?= $data_kelayakan['Layak dengan Catatan']; ?>;
    const totalKelayakan = kelLayak + kelTidak + kelCatatan;

    const dataKelayakan = totalKelayakan > 0
        ? google.visualization.arrayToDataTable([
            ['Status', 'Jumlah'],
            ['Layak', kelLayak],
            ['Tidak Layak', kelTidak],
            ['Layak dengan Catatan', kelCatatan]
          ])
        : google.visualization.arrayToDataTable([
            ['Status', 'Jumlah'],
            ['Belum ada data', 1]
          ]);
    
    const optionsKelayakan = {
        title: 'Grafik Status Kelayakan Pasien',
        pieHole: 0.4,
        colors: ['#4285F4', '#d33', '#f59e0b'],
        backgroundColor: 'transparent',
        legend: { position: 'bottom', textStyle: { color: '#0F2A1D', fontSize: 12 } },
        titleTextStyle: { color: '#0F2A1D', fontName: 'Poppins', fontSize: 16, bold: true },
        chartArea: { width: '90%', height: '70%' },
        pieSliceText: 'value',
        pieSliceTextStyle: { color: 'white', fontSize: 14 }
    };
    
    const chartKelayakan = new google.visualization.PieChart(document.getElementById('chartKelayakan'));
    if (totalKelayakan === 0) {
        const placeholderOptions = Object.assign({}, optionsKelayakan, {
            colors: ['#CBD5E1'],
            legend: { position: 'none' },
            title: 'Grafik Status Kelayakan (belum ada data)'
        });
        chartKelayakan.draw(dataKelayakan, placeholderOptions);
    } else {
        chartKelayakan.draw(dataKelayakan, optionsKelayakan);
    }
    console.log('✓ Kelayakan chart drawn');

    // === Column Chart - Jenis Kelamin ===
    const lakiLaki = dataJKFromPHP.laki;
    const perempuan = dataJKFromPHP.perempuan;
    
    console.log('Drawing JK Chart with data:');
    console.log('- Laki-laki:', lakiLaki);
    console.log('- Perempuan:', perempuan);
    
    // Pastikan minimal ada nilai 1 untuk demo jika data kosong
    const lakiDisplay = lakiLaki > 0 ? lakiLaki : 0;
    const perempDisplay = perempuan > 0 ? perempuan : 0;
    
    const dataJK = google.visualization.arrayToDataTable([
        ['Jenis Kelamin', 'Jumlah Pasien', { role: 'style' }, { role: 'annotation' }],
        ['Laki-laki', lakiDisplay, '#4285F4', lakiDisplay > 0 ? lakiDisplay.toString() : '0'],
        ['Perempuan', perempDisplay, '#E91E63', perempDisplay > 0 ? perempDisplay.toString() : '0']
    ]);
    
    const maxValue = Math.max(lakiDisplay, perempDisplay, 1);
    
    const optionsJK = {
        title: 'Grafik Pasien Berdasarkan Jenis Kelamin',
        legend: { position: 'none' },
        backgroundColor: 'transparent',
        vAxis: { 
            minValue: 0,
            maxValue: maxValue + 1,
            textStyle: { color: '#0F2A1D', fontSize: 12 },
            title: 'Jumlah Pasien',
            titleTextStyle: { color: '#0F2A1D', fontSize: 13, bold: true },
            gridlines: { color: '#E3EED4', count: 5 }
        },
        hAxis: { 
            textStyle: { color: '#0F2A1D', fontSize: 13, bold: true } 
        },
        bar: { groupWidth: '60%' },
        titleTextStyle: { color: '#0F2A1D', fontName: 'Poppins', fontSize: 16, bold: true },
        chartArea: { width: '80%', height: '65%', top: 60, bottom: 80 },
        animation: {
            startup: true,
            duration: 800,
            easing: 'out'
        },
        annotations: {
            alwaysOutside: false,
            textStyle: {
                fontSize: 16,
                bold: true,
                color: '#0F2A1D'
            },
            stemColor: 'none'
        }
    };
    
    const chartJK = new google.visualization.ColumnChart(document.getElementById('chartJK'));
    chartJK.draw(dataJK, optionsJK);
    console.log('✓ JK chart drawn successfully');
}

// Redraw on resize
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        if (typeof google !== 'undefined' && google.visualization) {
            drawCharts();
        }
    }, 250);
});
</script>
</body>
</html>