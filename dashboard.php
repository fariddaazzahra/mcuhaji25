<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];

//Koneksi ke database
require_once __DIR__ . '/koneksi.php';
?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard MCU Haji</title>
</head>
<body>
<center>
<h1>Selamat Datang, <?php echo htmlspecialchars($username); ?>!</h1>

<?php
// Tampilan dashboard berbeda berdasarkan role
if ($role == 'admin') {
    echo "<h2>Dashboard Admin</h2>";
     // Total pasien terdaftar
    $sql = "SELECT COUNT(*) AS total_pasien FROM pasien";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $total_pasien = $row['total_pasien'];
    } else {
        $total_pasien = 'Error: ' . mysqli_error($conn);
    }
    echo "<p>Total Pasien Terdaftar: <strong>$total_pasien</strong></p>";    
}
elseif ($role == 'dokter') {
    echo "<h2>Dashboard Dokter</h2>";
}
elseif ($role == 'nakes') {
    echo "<h2>Dashboard Nakes</h2>";
}
else {
    echo "<p>Role tidak dikenali.</p>";
}
?>
</center>
  <ul>
    <?php if ($role == 'admin'): ?>
      <li><a href="manajemenpasien.php">Manajemen Pasien</a></li> <!-- ✅ Ganti ke pasien -->
      <li><a href="manajemenmcu.php">Hasil MCU</a></li>
    <?php elseif ($role == 'dokter'): ?>
    <?php elseif ($role == 'nakes'): ?>
    <?php endif; ?>
  </ul>

<p><a href="logout.php">Logout</a></p>

</body>
</html>
