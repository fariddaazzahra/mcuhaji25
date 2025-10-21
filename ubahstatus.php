<?php
include '../koneksi.php';

$jadwal_id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if (!$jadwal_id || !$status) {
    die("Parameter tidak lengkap.");
}

// Pastikan status sesuai ENUM di database
$allowed = ['dijadwalkan', 'selesai', 'batal'];
if (!in_array($status, $allowed)) {
    die("Status tidak valid.");
}

// Update status MCU
$stmt = $conn->prepare("UPDATE jadwalmcu SET status_mcu=? WHERE jadwal_id=?");
$stmt->bind_param("si", $status, $jadwal_id);
$stmt->execute();
$stmt->close();

header("Location: antrian.php");
exit();
?>
